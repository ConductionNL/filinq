<?php

/**
 * Unit tests for DossierManagementService
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\DossierContextService;
use OCA\Filinq\Service\DossierFileService;
use OCA\Filinq\Service\DossierManagementService;
use OCA\Filinq\Service\DossierObjectReader;
use OCA\Filinq\Service\DossierObjectRepository;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Unit tests for DossierManagementService.
 *
 * The behaviours pinned here are the ones that fail SILENTLY when they regress:
 * a full-payload save that stops carrying a field, a lifecycle guard that stops
 * refusing, a membership union that quietly drops a reference it cannot
 * resolve, and a removal that trashes a file it should only have unlinked.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://conduction.nl
 *
 * phpcs:disable CustomSniffs.Functions.NamedParameters
 */
final class DossierManagementServiceTest extends TestCase {

	/**
	 * The repository the service reads OpenRegister through.
	 *
	 * @var DossierObjectRepository&MockObject
	 */
	private DossierObjectRepository&MockObject $repository;

	/**
	 * Nextcloud filesystem root.
	 *
	 * @var IRootFolder&MockObject
	 */
	private IRootFolder&MockObject $rootFolder;

	/**
	 * The current session.
	 *
	 * @var IUserSession&MockObject
	 */
	private IUserSession&MockObject $userSession;

	/**
	 * The service under test.
	 *
	 * @var DossierManagementService
	 */
	private DossierManagementService $service;

	/**
	 * Objects the fake ObjectService will return from findAll()/find().
	 *
	 * @var array<int, object>
	 */
	private array $objects = [];

	/**
	 * Payloads captured from saveObject(), in call order.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $saved = [];

	/**
	 * A refusal the fake ObjectService raises instead of saving.
	 *
	 * OpenRegister's lifecycle guard is what actually says this, and it says
	 * it in a sentence naming the states. The service has to carry that
	 * sentence out as a 409 rather than flattening it to a 500.
	 *
	 * @var string
	 */
	public string $saveRefusal = '';

	/**
	 * Build the service over fakes.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->repository = $this->createMock(DossierObjectRepository::class);
		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->userSession = $this->createMock(IUserSession::class);

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');
		$this->userSession->method('getUser')->willReturn($user);

		$this->repository->method('objectService')->willReturn($this->objectService());

		$files = new DossierFileService(
			$this->repository,
			$this->rootFolder,
			$this->userSession,
			$this->createMock(LoggerInterface::class),
		);
		$reader = new DossierObjectReader();

		// The collaborators are REAL here, not doubles. They were private
		// methods on this class until the decomposition, so every case below
		// was written against their behaviour; stubbing them now would keep
		// the tests green while proving nothing about the split.
		$this->service = new DossierManagementService(
			$this->repository,
			$files,
			new DossierContextService(
				$this->repository,
				$files,
				$reader,
				$this->createMock(LoggerInterface::class),
			),
			$reader,
			$this->createMock(LoggerInterface::class),
		);

	}//end setUp()

	/**
	 * A stand-in ObjectService with just the three methods the service calls.
	 *
	 * An anonymous class rather than a mock: `find`/`findAll`/`saveObject` are
	 * called with named arguments the real class declares, and a generic mock
	 * cannot express that shape without the real class being autoloadable.
	 *
	 * @return object The fake.
	 */
	private function objectService(): object {
		$test = $this;

		return new class($test) {

			/**
			 * Constructor.
			 *
			 * @param DossierManagementServiceTest $test The owning test.
			 */
			public function __construct(private readonly DossierManagementServiceTest $test) {
			}

			/**
			 * Find one object by uuid.
			 *
			 * @param string $id The uuid.
			 * @param string $register The register slug.
			 * @param string $schema The schema slug.
			 *
			 * @return object|null The object.
			 */
			public function find(string $id, string $register = '', string $schema = ''): ?object {
				foreach ($this->test->objectsForTest() as $object) {
					if (($object->jsonSerialize()['@self']['id'] ?? '') === $id) {
						return $object;
					}
				}

				return null;
			}

			/**
			 * Every object matching a config.
			 *
			 * Mirrors the REAL `ObjectService::findAll()` signature: a config
			 * array, NOT `register:` / `schema:` named arguments. Those exist on
			 * `find()` and `saveObject()` but not here, and getting it wrong
			 * throws "Unknown named parameter $register" at runtime — which
			 * every guarded caller then swallows. A fake with the convenient
			 * signature would have hidden exactly that.
			 *
			 * @param array<string, mixed> $config The find config.
			 *
			 * @return array<int, object> The objects.
			 */
			public function findAll(array $config = []): array {
				if (($config['filters']['schema'] ?? '') !== 'dossier') {
					return [];
				}

				return $this->test->objectsForTest();
			}

			/**
			 * Record a save.
			 *
			 * @param array<string, mixed> $object The payload.
			 * @param string $register The register slug.
			 * @param string $schema The schema slug.
			 *
			 * @return object The saved object.
			 */
			public function saveObject(array $object, string $register = '', string $schema = ''): object {
				if ($this->test->saveRefusal !== '') {
					throw new RuntimeException($this->test->saveRefusal);
				}

				$this->test->recordSave($object);

				return $this->test->makeObject($object);
			}
		};

	}//end objectService()

	/**
	 * The objects the fake should serve.
	 *
	 * @return array<int, object> The objects.
	 */
	public function objectsForTest(): array {
		return $this->objects;

	}//end objectsForTest()

	/**
	 * Record a captured save payload.
	 *
	 * @param array<string, mixed> $payload The payload.
	 *
	 * @return void
	 */
	public function recordSave(array $payload): void {
		$this->saved[] = $payload;

	}//end recordSave()

	/**
	 * Wrap a payload as an OpenRegister-shaped object.
	 *
	 * @param array<string, mixed> $payload The payload.
	 *
	 * @return object The object.
	 */
	public function makeObject(array $payload): object {
		return new class($payload) {

			/**
			 * Constructor.
			 *
			 * @param array<string, mixed> $payload The payload.
			 */
			public function __construct(private readonly array $payload) {
			}

			/**
			 * The payload.
			 *
			 * @return array<string, mixed> The payload.
			 */
			public function jsonSerialize(): array {
				return $this->payload;
			}
		};

	}//end makeObject()

	/**
	 * Seed one dossier.
	 *
	 * @param array<string, mixed> $payload Its fields.
	 *
	 * @return void
	 */
	private function seedDossier(array $payload): void {
		$this->objects[] = $this->makeObject($payload);

	}//end seedDossier()

	/**
	 * A dossier without a stored status reads as `open`, not as missing data.
	 *
	 * `status` was added optional and existing objects were deliberately not
	 * migrated, so every consumer has to agree on what absence means.
	 *
	 * @return void
	 */
	public function testAbsentStatusReadsAsOpen(): void {
		$this->seedDossier([
			'@self' => ['id' => 'd1', 'folder' => null],
			'name' => 'Woo 2026-001',
		]);

		$detail = $this->service->detail('d1');

		self::assertSame('open', $detail['status']);
		self::assertSame(['in-review'], $detail['availableTransitions']);

	}//end testAbsentStatusReadsAsOpen()

	/**
	 * Only the declared transitions are offered from a given status.
	 *
	 * @return void
	 */
	public function testOnlyDeclaredTransitionsAreOffered(): void {
		$this->seedDossier([
			'@self' => ['id' => 'd1', 'folder' => null],
			'name' => 'Woo 2026-001',
			'status' => 'in-review',
		]);

		self::assertSame(
			['processed', 'open'],
			$this->service->detail('d1')['availableTransitions'],
			'in-review must offer completing the review and reopening, and nothing else.'
		);

	}//end testOnlyDeclaredTransitionsAreOffered()

	/**
	 * An out-of-order transition is refused before it reaches OpenRegister.
	 *
	 * OpenRegister's lifecycle guard is the authority and would refuse this
	 * too; refusing here as well is what makes the rejection readable instead
	 * of a bare guard error.
	 *
	 * @return void
	 */
	public function testOutOfOrderTransitionIsRefused(): void {
		$this->seedDossier([
			'@self' => ['id' => 'd1', 'folder' => null],
			'name' => 'Woo 2026-001',
			'status' => 'open',
		]);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionCode(409);

		$this->service->transition('d1', 'published');

	}//end testOutOfOrderTransitionIsRefused()

	/**
	 * A legal transition writes the status and carries every other field forward.
	 *
	 * OpenRegister saves are PUT-semantic. A transition that posted only
	 * `status` would null the bases, the review date and the membership list —
	 * the exact failure the full-payload rule exists to prevent.
	 *
	 * @return void
	 */
	public function testTransitionCarriesEveryOtherFieldForward(): void {
		$this->seedDossier([
			'@self' => ['id' => 'd1', 'folder' => null],
			'name' => 'Woo 2026-001',
			'description' => 'Subsidies',
			'bases' => ['persoonsgegevens'],
			'checkedOn' => '2026-03-14T10:22:00+00:00',
			'documents' => ['42'],
			'status' => 'open',
		]);

		$this->service->transition('d1', 'in-review');

		self::assertCount(1, $this->saved);
		$payload = $this->saved[0];

		self::assertSame('in-review', $payload['status']);
		self::assertSame('Woo 2026-001', $payload['name']);
		self::assertSame('Subsidies', $payload['description']);
		self::assertSame(['persoonsgegevens'], $payload['bases']);
		self::assertSame('2026-03-14T10:22:00+00:00', $payload['checkedOn']);
		self::assertSame(['42'], $payload['documents']);

	}//end testTransitionCarriesEveryOtherFieldForward()

	/**
	 * A rename carries every other field forward too.
	 *
	 * @return void
	 */
	public function testRenameCarriesEveryOtherFieldForward(): void {
		$this->seedDossier([
			'@self' => ['id' => 'd1', 'folder' => null],
			'name' => 'Handhaving 2024',
			'bases' => ['persoonsgegevens'],
			'checkedOn' => '2026-01-20T11:00:00+00:00',
			'status' => 'in-review',
		]);

		$this->service->rename('d1', 'Handhaving 2025');

		$payload = $this->saved[0];
		self::assertSame('Handhaving 2025', $payload['name']);
		self::assertSame(['persoonsgegevens'], $payload['bases']);
		self::assertSame('2026-01-20T11:00:00+00:00', $payload['checkedOn']);
		self::assertSame('in-review', $payload['status']);

	}//end testRenameCarriesEveryOtherFieldForward()

	/**
	 * An empty rename is refused rather than written.
	 *
	 * @return void
	 */
	public function testEmptyRenameIsRefused(): void {
		$this->seedDossier([
			'@self' => ['id' => 'd1', 'folder' => null],
			'name' => 'Handhaving 2024',
		]);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionCode(400);

		$this->service->rename('d1', '   ');

	}//end testEmptyRenameIsRefused()

	/**
	 * An unreadable dossier is refused, not returned empty.
	 *
	 * @return void
	 */
	public function testUnknownDossierIsRefused(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionCode(404);

		$this->service->detail('nope');

	}//end testUnknownDossierIsRefused()

	/**
	 * A membership reference that cannot be resolved is SHOWN, not dropped.
	 *
	 * A dossier that quietly lists four of its five documents is worse than one
	 * that says the fifth is missing — the operator has no way to notice the
	 * former, and this is Woo evidence.
	 *
	 * @return void
	 */
	public function testUnresolvableMembershipReferenceIsSurfaced(): void {
		$this->seedDossier([
			'@self' => ['id' => 'd1', 'folder' => null],
			'name' => 'Woo 2026-001',
			'documents' => ['999'],
		]);

		$this->rootFolder->method('getUserFolder')->willThrowException(new \RuntimeException('no view'));

		$detail = $this->service->detail('d1');

		self::assertCount(1, $detail['documents']);
		self::assertTrue($detail['documents'][0]['missing']);
		self::assertSame(999, $detail['documents'][0]['id']);

	}//end testUnresolvableMembershipReferenceIsSurfaced()

	/**
	 * The index reports the missing count separately from the total.
	 *
	 * Folding it into the total would make a dossier that has lost a document
	 * look intact.
	 *
	 * @return void
	 */
	public function testIndexReportsMissingSeparately(): void {
		$this->seedDossier([
			'@self' => ['id' => 'd1', 'folder' => null],
			'name' => 'Woo 2026-001',
			'documents' => ['999', '998'],
		]);

		$this->rootFolder->method('getUserFolder')->willThrowException(new \RuntimeException('no view'));

		$rows = $this->service->index();

		self::assertCount(1, $rows);
		self::assertSame(2, $rows[0]['documentCount']);
		self::assertSame(2, $rows[0]['missingCount']);

	}//end testIndexReportsMissingSeparately()

	/**
	 * Linking a document appends a reference and moves nothing.
	 *
	 * @return void
	 */
	public function testLinkingAppendsAReferenceWithoutMoving(): void {
		$this->seedDossier([
			'@self' => ['id' => 'd1', 'folder' => null],
			'name' => 'Woo 2026-001',
			'documents' => ['42'],
		]);

		$node = $this->createMock(\OCP\Files\File::class);
		$node->method('getId')->willReturn(77);
		$node->method('getName')->willReturn('bijlage.pdf');
		$node->method('getPath')->willReturn('/alice/files/Elders/bijlage.pdf');

		$userFolder = $this->createMock(Folder::class);
		$userFolder->method('getById')->willReturn([$node]);
		$this->rootFolder->method('getUserFolder')->willReturn($userFolder);

		$this->service->linkDocument('d1', 77);

		self::assertCount(1, $this->saved);
		self::assertSame(['42', '77'], $this->saved[0]['documents']);

	}//end testLinkingAppendsAReferenceWithoutMoving()

	/**
	 * Linking the same document twice does not duplicate the reference.
	 *
	 * @return void
	 */
	public function testLinkingIsIdempotent(): void {
		$this->seedDossier([
			'@self' => ['id' => 'd1', 'folder' => null],
			'name' => 'Woo 2026-001',
			'documents' => ['77'],
		]);

		$node = $this->createMock(\OCP\Files\File::class);
		$node->method('getId')->willReturn(77);
		$node->method('getName')->willReturn('bijlage.pdf');
		$node->method('getPath')->willReturn('/alice/files/Elders/bijlage.pdf');

		$userFolder = $this->createMock(Folder::class);
		$userFolder->method('getById')->willReturn([$node]);
		$this->rootFolder->method('getUserFolder')->willReturn($userFolder);

		$this->service->linkDocument('d1', 77);

		self::assertSame([], $this->saved, 'An already-linked document must not be written again.');

	}//end testLinkingIsIdempotent()

	/**
	 * A document another dossier also references is UNLINKED, never trashed.
	 *
	 * Trashing a file and dropping a link are different operations behind one
	 * verb. Getting this wrong deletes a file the other dossier still needs.
	 *
	 * @return void
	 */
	public function testRemovalUnlinksWhenAnotherDossierReferencesTheFile(): void {
		$this->seedDossier([
			'@self' => ['id' => 'd1', 'folder' => null],
			'name' => 'Dossier A',
			'documents' => ['77'],
		]);
		$this->seedDossier([
			'@self' => ['id' => 'd2', 'folder' => null],
			'name' => 'Dossier B',
			'documents' => ['77'],
		]);

		$node = $this->createMock(\OCP\Files\File::class);
		$node->method('getId')->willReturn(77);
		$node->method('getPath')->willReturn('/alice/files/Elders/bijlage.pdf');
		// The file must NOT be deleted.
		$node->expects(self::never())->method('delete');

		$userFolder = $this->createMock(Folder::class);
		$userFolder->method('getById')->willReturn([$node]);
		$this->rootFolder->method('getUserFolder')->willReturn($userFolder);

		self::assertSame('unlink', $this->service->removalMode('d1', 77));

	}//end testRemovalUnlinksWhenAnotherDossierReferencesTheFile()

	/**
	 * A file the caller cannot resolve is unlinked rather than trashed.
	 *
	 * Fail safe: unable to prove the file is exclusively this dossier's, the
	 * service must not delete it.
	 *
	 * @return void
	 */
	public function testRemovalOfAnUnresolvableFileUnlinks(): void {
		$this->seedDossier([
			'@self' => ['id' => 'd1', 'folder' => null],
			'name' => 'Dossier A',
			'documents' => ['999'],
		]);

		$this->rootFolder->method('getUserFolder')->willThrowException(new \RuntimeException('no view'));

		self::assertSame('unlink', $this->service->removalMode('d1', 999));

	}//end testRemovalOfAnUnresolvableFileUnlinks()

	/**
	 * Removing drops only this dossier's reference.
	 *
	 * @return void
	 */
	public function testRemovalDropsOnlyThisDossiersReference(): void {
		$this->seedDossier([
			'@self' => ['id' => 'd1', 'folder' => null],
			'name' => 'Dossier A',
			'documents' => ['42', '77'],
		]);

		$this->rootFolder->method('getUserFolder')->willThrowException(new \RuntimeException('no view'));

		$this->service->removeDocument('d1', 77);

		self::assertCount(1, $this->saved);
		self::assertSame(['42'], $this->saved[0]['documents']);

	}//end testRemovalDropsOnlyThisDossiersReference()

	/**
	 * Creating without a name is refused before any folder is made.
	 *
	 * @return void
	 */
	public function testCreateWithoutANameIsRefused(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionCode(400);

		$this->service->create('  ');

	}//end testCreateWithoutANameIsRefused()

	/**
	 * A new dossier is stored WITH its initial status, not left to the reader.
	 *
	 * OpenRegister's lifecycle guard compares the STORED value, and an absent
	 * status is `""` to it — from which no transition is declared. Without this,
	 * a brand-new dossier could never take its first transition: the UI offered
	 * "Start review" and the save came back 409 "No transition allows moving
	 * from ''". Measured against a live instance on 2026-09-07.
	 *
	 * @return void
	 */
	public function testCreateStoresTheInitialStatus(): void {
		$folder = $this->createMock(Folder::class);
		$folder->method('getId')->willReturn(4242);

		$userFolder = $this->createMock(Folder::class);
		$userFolder->method('nodeExists')->willReturn(true);
		$userFolder->method('get')->willReturn($folder);
		$this->rootFolder->method('getUserFolder')->willReturn($userFolder);

		try {
			$this->service->create('Woo 2026-002');
		} catch (RuntimeException $e) {
			// The re-read in detail() goes through the fake, which does not
			// serve the new object. The SAVE is what this test is about.
		}

		self::assertNotSame([], $this->saved, 'create() must write the object');
		self::assertSame(
			DossierManagementService::DEFAULT_STATUS,
			$this->saved[0]['status'],
			'A new dossier must carry its initial status explicitly.'
		);

	}//end testCreateStoresTheInitialStatus()

	/**
	 * A dossier stored before `status` existed is settled before it transitions.
	 *
	 * Reading absent as `open` is a CONSUMER convention; the lifecycle guard
	 * does not share it. Every legacy dossier would otherwise be frozen — its
	 * first transition refused forever — so the initial state is written once
	 * before the requested move.
	 *
	 * @return void
	 */
	public function testLegacyDossierIsSettledBeforeItsFirstTransition(): void {
		$this->seedDossier([
			'@self' => ['id' => 'd1', 'folder' => null],
			'name' => 'Legacy dossier',
			'bases' => ['persoonsgegevens'],
		]);

		$this->service->transition('d1', 'in-review');

		self::assertCount(2, $this->saved, 'settle then transition — two writes');
		self::assertSame('open', $this->saved[0]['status']);
		self::assertSame('in-review', $this->saved[1]['status']);
		// Neither write may drop the rest of the object.
		self::assertSame(['persoonsgegevens'], $this->saved[1]['bases']);

	}//end testLegacyDossierIsSettledBeforeItsFirstTransition()

	/**
	 * The publication section reports the pipeline as absent, and is gated on it.
	 *
	 * Presence-gated capabilities must be HIDDEN when absent, never
	 * offered-and-broken.
	 *
	 * @return void
	 */
	public function testPublicationIsPresenceGated(): void {
		$this->seedDossier([
			'@self' => ['id' => 'd1', 'folder' => null],
			'name' => 'Woo 2026-001',
		]);

		$detail = $this->service->detail('d1');

		self::assertFalse($detail['capabilities']['publicationPipeline']);
		self::assertFalse($detail['publication']['installed']);

	}//end testPublicationIsPresenceGated()

	/**
	 * The Filinq directory is created when it is not there yet, and the dossier
	 * folder is made inside it.
	 *
	 * This used to read `newFolder()`'s answer directly. Ensure-then-read is
	 * one statement longer and pins the same result, so the case is worth a
	 * test of its own rather than being assumed from the create() test above,
	 * which only ever ran the branch where Filinq already exists.
	 *
	 * @return void
	 */
	public function testTheFilinqDirectoryIsCreatedWhenItIsAbsent(): void {
		$made = $this->createMock(Folder::class);
		$made->method('getId')->willReturn(4242);

		$parent = $this->createMock(Folder::class);
		$parent->method('nodeExists')->willReturn(false);
		$parent->expects(self::once())->method('newFolder')->with('Woo 2026-003')->willReturn($made);

		$userFolder = $this->createMock(Folder::class);
		$userFolder->method('nodeExists')->with('Filinq')->willReturn(false);
		$userFolder->expects(self::once())->method('newFolder')->with('Filinq');
		$userFolder->method('get')->with('Filinq')->willReturn($parent);
		$this->rootFolder->method('getUserFolder')->willReturn($userFolder);

		try {
			$this->service->create('Woo 2026-003');
		} catch (RuntimeException $e) {
			// The re-read in detail() goes through the fake, which does not
			// serve the new object. The FOLDER is what this test is about.
		}

		self::assertNotSame([], $this->saved, 'create() must write the object');
		self::assertSame(4242, $this->saved[0]['@self']['folder']);

	}//end testTheFilinqDirectoryIsCreatedWhenItIsAbsent()

	/**
	 * An existing dossier folder is reused rather than created a second time.
	 *
	 * @return void
	 */
	public function testAnExistingDossierFolderIsReused(): void {
		$existing = $this->createMock(Folder::class);
		$existing->method('getId')->willReturn(77);

		$parent = $this->createMock(Folder::class);
		$parent->method('nodeExists')->willReturn(true);
		$parent->method('get')->with('Woo 2026-004')->willReturn($existing);
		$parent->expects(self::never())->method('newFolder');

		$userFolder = $this->createMock(Folder::class);
		$userFolder->method('nodeExists')->willReturn(true);
		$userFolder->method('get')->willReturn($parent);
		$this->rootFolder->method('getUserFolder')->willReturn($userFolder);

		try {
			$this->service->create('Woo 2026-004');
		} catch (RuntimeException $e) {
			// As above: the save is what matters here.
		}

		self::assertNotSame([], $this->saved, 'create() must write the object');
		self::assertSame(77, $this->saved[0]['@self']['folder']);

	}//end testAnExistingDossierFolderIsReused()

	/**
	 * A FILE occupying the dossier name says so, rather than reporting a
	 * failure to create the folder.
	 *
	 * A user who saved "Mijn dossier" into Filinq/ occupies that name. `get()`
	 * answers a Node, so returning it against a `: Folder` signature is a
	 * TypeError, and the catch around it rewrites every TypeError into "Could
	 * not create the dossier folder" - which names the wrong cause and sends
	 * the reader looking at permissions.
	 *
	 * @return void
	 */
	public function testAFileOccupyingTheDossierNameIsNamedAsSuch(): void {
		$parent = $this->createMock(Folder::class);
		$parent->method('nodeExists')->willReturn(true);
		$parent->method('get')->willReturn($this->createMock(File::class));

		$userFolder = $this->createMock(Folder::class);
		$userFolder->method('nodeExists')->willReturn(true);
		$userFolder->method('get')->willReturn($parent);
		$this->rootFolder->method('getUserFolder')->willReturn($userFolder);

		try {
			$this->service->create('Mijn dossier');
			self::fail('a file in the way must be refused');
		} catch (RuntimeException $e) {
			self::assertStringContainsString('is a file, not a folder', $e->getMessage());
		}

		self::assertSame([], $this->saved, 'nothing may be stored when the folder cannot be made');

	}//end testAFileOccupyingTheDossierNameIsNamedAsSuch()

	/**
	 * A FILE named Filinq is refused for the same reason, one level up.
	 *
	 * @return void
	 */
	public function testAFileNamedFilinqIsRefused(): void {
		$userFolder = $this->createMock(Folder::class);
		$userFolder->method('nodeExists')->willReturn(true);
		$userFolder->method('get')->willReturn($this->createMock(File::class));
		$this->rootFolder->method('getUserFolder')->willReturn($userFolder);

		try {
			$this->service->create('Woo 2026-005');
			self::fail('a file named Filinq must be refused');
		} catch (RuntimeException $e) {
			self::assertStringContainsString('Filinq is not a folder', $e->getMessage());
		}

		self::assertSame([], $this->saved);

	}//end testAFileNamedFilinqIsRefused()

	/**
	 * 🔴 THE ONLY PATH THAT DELETES. A file that lives in this dossier's own
	 * folder and is referenced by no other dossier is trashed rather than
	 * unlinked, because unlinking it would leave a file nothing admits to
	 * holding.
	 *
	 * The existing removal tests all seed `folder: null`, so every one of them
	 * short-circuits before the exclusivity check and none of them reaches
	 * this branch. That is why it is here.
	 *
	 * @return void
	 */
	public function testAFileOnlyThisDossierHoldsIsTrashed(): void {
		$this->seedDossier([
			'@self' => ['id' => 'd1', 'folder' => 4242],
			'name' => 'Dossier A',
			'documents' => ['77'],
		]);
		$this->seedDossier([
			'@self' => ['id' => 'd2', 'folder' => null],
			'name' => 'Dossier B',
			'documents' => ['999'],
		]);

		$folder = $this->createMock(Folder::class);
		$folder->method('getPath')->willReturn('/alice/files/Filinq/Dossier A');
		$this->repository->method('resolveDossierFolder')->willReturn($folder);

		$node = $this->createMock(File::class);
		$node->method('getId')->willReturn(77);
		$node->method('getPath')->willReturn('/alice/files/Filinq/Dossier A/bijlage.pdf');

		$userFolder = $this->createMock(Folder::class);
		$userFolder->method('getById')->willReturn([$node]);
		$this->rootFolder->method('getUserFolder')->willReturn($userFolder);

		self::assertSame('trash', $this->service->removalMode('d1', 77));

	}//end testAFileOnlyThisDossierHoldsIsTrashed()

	/**
	 * The same file, once a SECOND dossier references it, is unlinked. The
	 * exclusivity check is what separates this from the case above, and both
	 * are needed: a test of only one of them passes on a check that always
	 * answers the same way.
	 *
	 * @return void
	 */
	public function testAFileAnotherDossierAlsoHoldsIsUnlinkedEvenFromItsOwnFolder(): void {
		$this->seedDossier([
			'@self' => ['id' => 'd1', 'folder' => 4242],
			'name' => 'Dossier A',
			'documents' => ['77'],
		]);
		$this->seedDossier([
			'@self' => ['id' => 'd2', 'folder' => null],
			'name' => 'Dossier B',
			'documents' => ['77'],
		]);

		$folder = $this->createMock(Folder::class);
		$folder->method('getPath')->willReturn('/alice/files/Filinq/Dossier A');
		$this->repository->method('resolveDossierFolder')->willReturn($folder);

		$node = $this->createMock(File::class);
		$node->method('getId')->willReturn(77);
		$node->method('getPath')->willReturn('/alice/files/Filinq/Dossier A/bijlage.pdf');

		$userFolder = $this->createMock(Folder::class);
		$userFolder->method('getById')->willReturn([$node]);
		$this->rootFolder->method('getUserFolder')->willReturn($userFolder);

		self::assertSame('unlink', $this->service->removalMode('d1', 77));

	}//end testAFileAnotherDossierAlsoHoldsIsUnlinkedEvenFromItsOwnFolder()

	/**
	 * A file sitting OUTSIDE the dossier's folder is unlinked, however
	 * exclusively this dossier references it. The dossier does not own a file
	 * it never held.
	 *
	 * @return void
	 */
	public function testAFileOutsideTheDossierFolderIsUnlinked(): void {
		$this->seedDossier([
			'@self' => ['id' => 'd1', 'folder' => 4242],
			'name' => 'Dossier A',
			'documents' => ['77'],
		]);

		$folder = $this->createMock(Folder::class);
		$folder->method('getPath')->willReturn('/alice/files/Filinq/Dossier A');
		$this->repository->method('resolveDossierFolder')->willReturn($folder);

		$node = $this->createMock(File::class);
		$node->method('getId')->willReturn(77);
		$node->method('getPath')->willReturn('/alice/files/Elders/bijlage.pdf');

		$userFolder = $this->createMock(Folder::class);
		$userFolder->method('getById')->willReturn([$node]);
		$this->rootFolder->method('getUserFolder')->willReturn($userFolder);

		self::assertSame('unlink', $this->service->removalMode('d1', 77));

	}//end testAFileOutsideTheDossierFolderIsUnlinked()

	/**
	 * A dossier the caller cannot read answers `unlink`, which is the safe
	 * side of a question it could not resolve.
	 *
	 * @return void
	 */
	public function testRemovalModeForAnUnknownDossierIsUnlink(): void {
		self::assertSame('unlink', $this->service->removalMode('no-such-dossier', 77));

	}//end testRemovalModeForAnUnknownDossierIsUnlink()

	/**
	 * 🔴 A LIFECYCLE REFUSAL KEEPS ITS MESSAGE. OpenRegister's guard rejects
	 * an illegal transition with a sentence naming the states; a bare 500
	 * here would leave the operator with nothing to act on.
	 *
	 * @return void
	 */
	public function testALifecycleRefusalIsSurfacedAsA409WithItsReason(): void {
		$this->seedDossier([
			'@self' => ['id' => 'd1'],
			'name' => 'Dossier A',
			'status' => 'open',
		]);
		$this->saveRefusal = 'No transition allows moving from "open"';

		try {
			$this->service->rename('d1', 'Nieuwe naam');
			self::fail('a refused save must not read as success');
		} catch (RuntimeException $e) {
			self::assertSame(409, $e->getCode());
			self::assertStringContainsString('No transition allows moving from', $e->getMessage());
		}

	}//end testALifecycleRefusalIsSurfacedAsA409WithItsReason()

}//end class
