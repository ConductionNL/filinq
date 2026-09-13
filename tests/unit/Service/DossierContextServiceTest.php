<?php

/**
 * Unit tests for DossierContextService
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\DossierContextService;
use OCA\Filinq\Service\DossierFileService;
use OCA\Filinq\Service\DossierObjectReader;
use OCA\Filinq\Service\DossierObjectRepository;
use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * What a dossier row is made of.
 *
 * EVERY LOOKUP HERE DEGRADES RATHER THAN FAILS, with one exception that is the
 * point of the class: a membership reference whose file cannot be resolved
 * comes back marked `missing`, never dropped. A dossier that quietly lists
 * four of its five documents is worse than one that says the fifth is gone,
 * because only the second is something an operator can act on.
 *
 * The file service and the reader are REAL here rather than doubles. They were
 * private methods on one class until the decomposition, so stubbing them would
 * test the stub and prove nothing about the split holding together.
 *
 * phpcs:disable CustomSniffs.Functions.NamedParameters
 *
 * @covers \OCA\Filinq\Service\DossierContextService
 *
 * @uses \OCA\Filinq\Service\DossierFileService
 * @uses \OCA\Filinq\Service\DossierObjectReader
 */
class DossierContextServiceTest extends TestCase {

	/**
	 * OpenRegister access.
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
	 * Warnings the service logged.
	 *
	 * @var array<int, string>
	 */
	private array $warnings = [];

	/**
	 * The subject.
	 *
	 * @var DossierContextService
	 */
	private DossierContextService $service;

	/**
	 * Build the subject over its real collaborators.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->repository = $this->createMock(DossierObjectRepository::class);
		$this->rootFolder = $this->createMock(IRootFolder::class);

		$userSession = $this->createMock(IUserSession::class);
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');
		$userSession->method('getUser')->willReturn($user);

		$logger = $this->createMock(LoggerInterface::class);
		$logger->method('warning')->willReturnCallback(
			function (string $message): void {
				$this->warnings[] = $message;
			}
		);

		$this->service = new DossierContextService(
			$this->repository,
			new DossierFileService($this->repository, $this->rootFolder, $userSession, $logger),
			new DossierObjectReader(),
			$logger,
		);

	}//end setUp()

	/**
	 * A file node the user folder answers with for one id.
	 *
	 * @param int $id The node id.
	 * @param string $name Its name.
	 *
	 * @return File&MockObject The node.
	 */
	private function node(int $id, string $name): File&MockObject {
		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn($id);
		$file->method('getName')->willReturn($name);
		$file->method('getMimetype')->willReturn('application/pdf');
		$file->method('getSize')->willReturn(1024);
		$file->method('getMTime')->willReturn(1788778368);
		$file->method('getType')->willReturn(FileInfo::TYPE_FILE);

		return $file;
	}//end node()

	/**
	 * Serve the given nodes from the user folder, by id.
	 *
	 * @param array<int, File> $nodes The nodes.
	 *
	 * @return void
	 */
	private function serveById(array $nodes): void {
		$userFolder = $this->createMock(Folder::class);
		$userFolder->method('getById')->willReturnCallback(
			static fn (int $id): array => (isset($nodes[$id]) === true ? [$nodes[$id]] : [])
		);
		$this->rootFolder->method('getUserFolder')->willReturn($userFolder);

	}//end serveById()

	/**
	 * An OpenRegister-shaped object over a payload.
	 *
	 * @param array<string, mixed> $payload The payload.
	 *
	 * @return object The object.
	 */
	private function object(array $payload): object {
		return new class($payload) {

			/**
			 * @param array<string, mixed> $payload The payload.
			 */
			public function __construct(private readonly array $payload) {
			}

			/**
			 * @return array<string, mixed> The payload.
			 */
			public function jsonSerialize(): array {
				return $this->payload;
			}
		};
	}//end object()

	/**
	 * A stand-in ObjectService answering findAll() with $objects.
	 *
	 * @param array<int, object>|null $objects What to answer with, or null to throw.
	 *
	 * @return object The fake.
	 */
	private function objectService(?array $objects): object {
		return new class($objects) {

			/**
			 * @param array<int, object>|null $objects The answer, or null to throw.
			 */
			public function __construct(private readonly ?array $objects) {
			}

			/**
			 * @param array<string, mixed> $config The find config.
			 *
			 * @return array<int, object> The objects.
			 */
			public function findAll(array $config = []): array {
				if ($this->objects === null) {
					throw new RuntimeException('the register is unreachable');
				}

				return $this->objects;
			}
		};
	}//end objectService()

	// ------------------------------------------------------------------
	// members
	// ------------------------------------------------------------------

	/**
	 * Membership is the UNION of the home folder and the explicit references,
	 * and a file present in both is listed once.
	 *
	 * @return void
	 */
	public function testMembershipIsAUnionAndNeverListsAFileTwice(): void {
		$inFolder = $this->node(41, 'report.pdf');
		$referenced = $this->node(42, 'appendix.pdf');
		$this->serveById([41 => $inFolder, 42 => $referenced]);

		$folder = $this->createMock(Folder::class);
		$folder->method('getDirectoryListing')->willReturn([$inFolder]);
		$this->repository->method('resolveDossierFolder')->willReturn($folder);

		$members = $this->service->members(
			(object)[],
			['@self' => ['folder' => 1], 'documents' => [41, 42]]
		);

		$this->assertSame([41, 42], array_column($members['documents'], 'id'));
		$this->assertSame(0, $members['missing']);
		$this->assertFalse($members['documents'][0]['referenced'], 'a home-folder file is not a reference');
		$this->assertTrue($members['documents'][1]['referenced']);

	}//end testMembershipIsAUnionAndNeverListsAFileTwice()

	/**
	 * 🔴 A REFERENCE THE CALLER CANNOT RESOLVE IS SHOWN, NOT DROPPED, and it
	 * is counted separately so the total cannot hide it.
	 *
	 * @return void
	 */
	public function testAnUnresolvableReferenceIsMarkedMissingAndCounted(): void {
		$this->serveById([]);
		$this->repository->method('resolveDossierFolder')
			->willThrowException(new RuntimeException('folder is gone'));

		$members = $this->service->members((object)[], ['documents' => [41, 42]]);

		$this->assertCount(2, $members['documents']);
		$this->assertSame(2, $members['missing']);
		$this->assertTrue($members['documents'][0]['missing']);
		$this->assertSame(41, $members['documents'][0]['id']);

	}//end testAnUnresolvableReferenceIsMarkedMissingAndCounted()

	/**
	 * A row carries what the list renders, read from the node rather than
	 * from the reference.
	 *
	 * @return void
	 */
	public function testADocumentRowCarriesTheNodesOwnFacts(): void {
		$row = $this->service->documentRow($this->node(41, 'report.pdf'), true);

		$this->assertSame(
			[
				'id' => 41,
				'name' => 'report.pdf',
				'mimetype' => 'application/pdf',
				'size' => 1024,
				'modified' => 1788778368,
				'missing' => false,
				'referenced' => true,
			],
			$row
		);

	}//end testADocumentRowCarriesTheNodesOwnFacts()

	// ------------------------------------------------------------------
	// resolveBases and baseLabels
	// ------------------------------------------------------------------

	/**
	 * 🔴 AN UNKNOWN GRONDSLAG IS KEPT AND FLAGGED, never dropped. A legal
	 * basis that silently disappears from a Woo dossier is a compliance
	 * problem, not a rendering detail.
	 *
	 * @return void
	 */
	public function testAnUnknownLegalBasisIsKeptAndFlagged(): void {
		$this->repository->method('objectService')->willReturn(
			$this->objectService([
				$this->object(['@self' => ['slug' => 'art-5-1-a'], 'name' => 'Eenheid van de Kroon']),
			])
		);

		$bases = $this->service->resolveBases(['bases' => ['art-5-1-a', 'art-99-retired']]);

		$this->assertSame(
			[
				['slug' => 'art-5-1-a', 'label' => 'Eenheid van de Kroon', 'known' => true],
				['slug' => 'art-99-retired', 'label' => 'art-99-retired', 'known' => false],
			],
			$bases
		);

	}//end testAnUnknownLegalBasisIsKeptAndFlagged()

	/**
	 * A dossier with no bases asks the vocabulary nothing.
	 *
	 * @return void
	 */
	public function testADossierWithoutBasesReadsNoVocabulary(): void {
		$this->repository->expects(self::never())->method('objectService');

		$this->assertSame([], $this->service->resolveBases([]));
		$this->assertSame([], $this->service->resolveBases(['bases' => 'not a list']));

	}//end testADossierWithoutBasesReadsNoVocabulary()

	/**
	 * The vocabulary is read ONCE per request, however many dossiers are
	 * rendered: the index resolves bases per row, and a read per row turns
	 * one page into one query per dossier.
	 *
	 * @return void
	 */
	public function testTheVocabularyIsReadOncePerRequest(): void {
		$this->repository->expects(self::once())
			->method('objectService')
			->willReturn($this->objectService([$this->object(['slug' => 'art-5-1-a', 'name' => 'Kroon'])]));

		$this->assertSame(['art-5-1-a' => 'Kroon'], $this->service->baseLabels());
		$this->assertSame(['art-5-1-a' => 'Kroon'], $this->service->baseLabels());

	}//end testTheVocabularyIsReadOncePerRequest()

	/**
	 * A vocabulary entry without a slug is skipped rather than keyed under ''.
	 *
	 * @return void
	 */
	public function testAVocabularyEntryWithoutASlugIsSkipped(): void {
		$this->repository->method('objectService')->willReturn(
			$this->objectService([
				$this->object(['name' => 'nameless']),
				$this->object(['slug' => 'art-5-1-c', 'name' => 'Persoonlijke beleidsopvattingen']),
			])
		);

		$this->assertSame(
			['art-5-1-c' => 'Persoonlijke beleidsopvattingen'],
			$this->service->baseLabels()
		);

	}//end testAVocabularyEntryWithoutASlugIsSkipped()

	/**
	 * OpenRegister absent is an empty vocabulary, so bases render as their
	 * own slugs rather than the page failing.
	 *
	 * @return void
	 */
	public function testAnAbsentOpenRegisterLeavesTheVocabularyEmpty(): void {
		$this->repository->method('objectService')->willReturn(null);

		$this->assertSame([], $this->service->baseLabels());
		$this->assertSame(
			[['slug' => 'art-5-1-a', 'label' => 'art-5-1-a', 'known' => false]],
			$this->service->resolveBases(['bases' => ['art-5-1-a']])
		);

	}//end testAnAbsentOpenRegisterLeavesTheVocabularyEmpty()

	/**
	 * A vocabulary read that fails is empty AND logged: the page still
	 * renders, and the cause is not invisible.
	 *
	 * @return void
	 */
	public function testAFailedVocabularyReadIsEmptyAndLogged(): void {
		$this->repository->method('objectService')->willReturn($this->objectService(null));

		$this->assertSame([], $this->service->baseLabels());
		$this->assertCount(1, $this->warnings);

	}//end testAFailedVocabularyReadIsEmptyAndLogged()

	// ------------------------------------------------------------------
	// batchRuns
	// ------------------------------------------------------------------

	/**
	 * Runs are the batches recorded against THIS dossier's folder, newest
	 * first. A batch on another folder belongs to another dossier.
	 *
	 * @return void
	 */
	public function testRunsAreThisFoldersBatchesNewestFirst(): void {
		$folder = $this->createMock(Folder::class);
		$folder->method('getId')->willReturn(4242);
		$this->repository->method('resolveDossierFolder')->willReturn($folder);

		$this->repository->method('objectService')->willReturn(
			$this->objectService([
				$this->object(['folderId' => 4242, 'batchId' => 'old', 'status' => 'done', 'fileCount' => 2, 'created' => '2026-09-01T10:00:00Z']),
				$this->object(['folderId' => 9999, 'batchId' => 'other-dossier', 'created' => '2026-09-09T10:00:00Z']),
				$this->object(['folderId' => 4242, 'batchId' => 'new', 'status' => 'running', 'fileCount' => 5, 'created' => '2026-09-07T10:00:00Z']),
			])
		);

		$runs = $this->service->batchRuns((object)[], ['@self' => ['folder' => 1]]);

		$this->assertSame(['new', 'old'], array_column($runs, 'id'));
		$this->assertSame(5, $runs[0]['fileCount']);
		$this->assertSame('running', $runs[0]['status']);

	}//end testRunsAreThisFoldersBatchesNewestFirst()

	/**
	 * A dossier with no bound folder has no runs to show, and asks for none.
	 *
	 * @return void
	 */
	public function testADossierWithoutAFolderHasNoRuns(): void {
		$this->repository->method('objectService')->willReturn($this->objectService([]));

		$this->assertSame([], $this->service->batchRuns((object)[], []));

	}//end testADossierWithoutAFolderHasNoRuns()

	/**
	 * OpenRegister absent is no runs rather than a failure.
	 *
	 * @return void
	 */
	public function testRunsAreEmptyWithoutOpenRegister(): void {
		$this->repository->method('objectService')->willReturn(null);

		$this->assertSame([], $this->service->batchRuns((object)[], ['@self' => ['folder' => 1]]));

	}//end testRunsAreEmptyWithoutOpenRegister()

	/**
	 * A failing batch read is no runs, not a broken detail page: the runs
	 * block is one section of several and the rest still has something to say.
	 *
	 * @return void
	 */
	public function testAFailingBatchReadLeavesTheRestOfTheDetailIntact(): void {
		$folder = $this->createMock(Folder::class);
		$folder->method('getId')->willReturn(4242);
		$this->repository->method('resolveDossierFolder')->willReturn($folder);
		$this->repository->method('objectService')->willReturn($this->objectService(null));

		$this->assertSame([], $this->service->batchRuns((object)[], ['@self' => ['folder' => 1]]));

	}//end testAFailingBatchReadLeavesTheRestOfTheDetailIntact()

	// ------------------------------------------------------------------
	// publication
	// ------------------------------------------------------------------

	/**
	 * Publication is presence-gated: hidden, not broken. The pipeline has not
	 * shipped, so the detail says the capability is absent rather than
	 * offering a publish action that would fail.
	 *
	 * @return void
	 */
	public function testPublicationReportsItsAbsenceRatherThanOfferingIt(): void {
		$this->assertSame(['installed' => false, 'state' => ''], $this->service->publication());

	}//end testPublicationReportsItsAbsenceRatherThanOfferingIt()

}//end class
