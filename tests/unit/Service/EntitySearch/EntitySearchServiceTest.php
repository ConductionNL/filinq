<?php

/**
 * The entity search: gate first, tenant scope, then the read, then the log, then the answer.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\EntitySearch
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-entity-search/tasks.md#task-4.1
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\EntitySearch;

use OCA\Filinq\Exception\EntitySearchRefusedException;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\EntitySearch\EntityCatalogue;
use OCA\Filinq\Service\EntitySearch\EntityOccurrences;
use OCA\Filinq\Service\EntitySearch\EntitySearchGate;
use OCA\Filinq\Service\EntitySearch\EntitySearchLog;
use OCA\Filinq\Service\EntitySearch\EntitySearchService;
use OCA\Filinq\Service\Redaction\AnonymizationLinkReader;
use OCA\OpenRegister\Db\ObjectEntity;
use OCA\OpenRegister\Service\ObjectService;
use OCP\App\IAppManager;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IAppConfig;
use OCP\IDBConnection;
use OCP\IGroupManager;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;
use RuntimeException;

/**
 * The real gate, occurrences and log over in-memory OpenRegister, files and
 * groups. The catalogue's SQL is replaced by rows; its tenant rule is real.
 */
class EntitySearchServiceTest extends TestCase {

	private const ENTITY = '8d1c6f2e-0b7a-4f3e-9a51-2c4d6e8f0a13';

	/**
	 * Group membership per user.
	 *
	 * @var array<string, array<int, string>>
	 */
	private array $members = ['root' => ['admin'], 'petra' => ['privacy-officers'], 'bob' => ['staff']];

	/**
	 * The entity search groups setting.
	 *
	 * @var string
	 */
	private string $allowedGroups = '["privacy-officers"]';

	/**
	 * Saved OpenRegister rows by schema.
	 *
	 * @var array<string, array<int, array<string, mixed>>>
	 */
	private array $saved = [];

	/**
	 * Whether the log write fails.
	 *
	 * @var bool
	 */
	private bool $logFails = false;

	/**
	 * The organisations each user belongs to.
	 *
	 * @var array<string, array<int, string>>
	 */
	private array $organisations = ['petra' => ['org-a'], 'bob' => []];

	/**
	 * The signed-in user the organisation service answers for.
	 *
	 * @var string
	 */
	private string $current = '';

	/**
	 * The catalogue rows: entity, organisation and relations.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $entities = [];

	/**
	 * Files by id, and which users may read them.
	 *
	 * @var array<int, array{name: string, readers: array<int, string>}>
	 */
	private array $files = [];

	/**
	 * Whether the catalogue was queried.
	 *
	 * @var int
	 */
	private int $catalogueReads = 0;

	/**
	 * A person in three documents, one of them unreadable to petra.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->entities = [
			[
				'uuid' => self::ENTITY,
				'type' => 'PERSON',
				'value' => 'Jan de Vries',
				'category' => 'personal_data',
				'organisation' => 'org-a',
				'relations' => [
					['fileId' => 11, 'confidence' => 0.97, 'anonymized' => false, 'detectionMethod' => 'presidio'],
					['fileId' => 12, 'confidence' => 0.91, 'anonymized' => true, 'detectionMethod' => 'presidio'],
					['fileId' => 13, 'confidence' => 0.88, 'anonymized' => false, 'detectionMethod' => 'regex'],
					['objectId' => 5, 'confidence' => 0.8, 'anonymized' => false, 'detectionMethod' => 'regex'],
				],
			],
			['uuid' => 'other-tenant', 'type' => 'PERSON', 'value' => 'Piet de Vries', 'category' => 'personal_data', 'organisation' => 'org-b', 'relations' => []],
		];
		$this->files = [
			11 => ['name' => 'aanvraag.pdf', 'readers' => ['petra', 'root']],
			12 => ['name' => 'besluit.docx', 'readers' => ['petra', 'root']],
			13 => ['name' => 'geheim-verslag.pdf', 'readers' => ['root']],
		];

	}//end setUp()

	/**
	 * The service with its real collaborators.
	 *
	 * @return EntitySearchService The service.
	 */
	private function service(): EntitySearchService {
		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturnCallback(fn (): string => $this->allowedGroups);
		$groups = $this->createMock(IGroupManager::class);
		$groups->method('isAdmin')->willReturnCallback(fn (string $user): bool => in_array('admin', ($this->members[$user] ?? []), true));
		$groups->method('isInGroup')->willReturnCallback(fn (string $user, string $group): bool => in_array($group, ($this->members[$user] ?? []), true));

		$container = $this->createMock(ContainerInterface::class);
		$objects = $this->objectService();
		$organisations = new class ($this) {
			/**
			 * Constructor.
			 *
			 * @param object $test The test.
			 */
			public function __construct(private readonly object $test) {
			}

			/**
			 * The current user's organisations.
			 *
			 * @return array<int, object> The organisations.
			 */
			public function getUserOrganisations(): array {
				return $this->test->organisationsOfCurrent();
			}
		};
		$container->method('get')->willReturnCallback(
			static fn (string $id) => match ($id) {
				'OCA\OpenRegister\Service\OrganisationService' => $organisations,
				'OCA\OpenRegister\Service\RiskLevelService' => new class {
					/**
					 * The risk level.
					 *
					 * @param int $fileId The file.
					 *
					 * @return string The level.
					 */
					public function getRiskLevel(int $fileId): string {
						return ($fileId === 11 ? 'high' : 'low');
					}
				},
				default => $objects,
			}
		);
		$apps = $this->createMock(IAppManager::class);
		$apps->method('getInstalledApps')->willReturn(['openregister']);
		$resolver = new DocumentObjectServiceResolver($container, $apps);
		$time = $this->createMock(ITimeFactory::class);
		$time->method('getDateTime')->willReturn(new \DateTime('2026-09-29T10:00:00+00:00'));

		return new EntitySearchService(
			new EntitySearchGate($config, $groups),
			$this->catalogue(container: $container, apps: $apps),
			new EntityOccurrences($this->rootFolder(), new AnonymizationLinkReader($resolver, new NullLogger()), $resolver, $container),
			new EntitySearchLog($resolver, $time)
		);

	}//end service()

	/**
	 * The real catalogue with its SQL replaced by the rows above; scope() stays real.
	 *
	 * @param ContainerInterface $container The container.
	 * @param IAppManager        $apps      The app manager.
	 *
	 * @return EntityCatalogue The catalogue.
	 */
	private function catalogue(ContainerInterface $container, IAppManager $apps): EntityCatalogue {
		$test = $this;
		return new class ($this->createMock(IDBConnection::class), $container, $apps, $test) extends EntityCatalogue {
			/**
			 * Constructor.
			 *
			 * @param IDBConnection      $db        Unused database.
			 * @param ContainerInterface $container The container.
			 * @param IAppManager        $apps      The app manager.
			 * @param object             $test      The test holding the rows.
			 */
			public function __construct(IDBConnection $db, ContainerInterface $container, IAppManager $apps, private readonly object $test) {
				parent::__construct($db, $container, $apps);
			}

			/**
			 * The rows matching, as the SQL would.
			 *
			 * @param array<int, string>|null $scope    The organisations.
			 * @param string                  $query    The query.
			 * @param string                  $type     The type.
			 * @param string                  $category The category.
			 * @param int                     $limit    The page size.
			 * @param int                     $offset   The page start.
			 *
			 * @return array{results: array<int, array<string, mixed>>, total: int} The page.
			 */
			public function search(?array $scope, string $query, string $type, string $category, int $limit, int $offset): array {
				return $this->test->catalogueSearch($scope, $query, $type, $category, $limit, $offset);
			}

			/**
			 * The entity, as the SQL would.
			 *
			 * @param array<int, string>|null $scope The organisations.
			 * @param string                  $uuid  The uuid.
			 *
			 * @return array<string, mixed>|null The entity.
			 */
			public function find(?array $scope, string $uuid): ?array {
				return $this->test->catalogueFind($scope, $uuid);
			}
		};

	}//end catalogue()

	/**
	 * The current user's organisations (called by the organisation double).
	 *
	 * @return array<int, object> The organisations.
	 */
	public function organisationsOfCurrent(): array {
		if (isset($this->organisations[$this->current]) === false) {
			throw new RuntimeException('organisation table unavailable');
		}

		return array_map(
			static fn (string $uuid): object => new class ($uuid) {
				/**
				 * Constructor.
				 *
				 * @param string $uuid The uuid.
				 */
				public function __construct(private readonly string $uuid) {
				}

				/**
				 * The uuid.
				 *
				 * @return string The uuid.
				 */
				public function getUuid(): string {
					return $this->uuid;
				}
			},
			$this->organisations[$this->current]
		);

	}//end organisationsOfCurrent()

	/**
	 * The catalogue search over the rows (called by the catalogue double).
	 *
	 * @param array<int, string>|null $scope    The organisations.
	 * @param string                  $query    The query.
	 * @param string                  $type     The type.
	 * @param string                  $category The category.
	 * @param int                     $limit    The page size.
	 * @param int                     $offset   The page start.
	 *
	 * @return array{results: array<int, array<string, mixed>>, total: int} The page.
	 */
	public function catalogueSearch(?array $scope, string $query, string $type, string $category, int $limit, int $offset): array {
		$this->catalogueReads++;
		$hits = [];
		foreach ($this->entities as $row) {
			if ($scope !== null && in_array($row['organisation'], $scope, true) === false) {
				continue;
			}

			if (($query !== '' && stripos($row['value'], $query) === false) || ($type !== '' && $row['type'] !== $type) || ($category !== '' && $row['category'] !== $category)) {
				continue;
			}

			$hits[] = ['uuid' => $row['uuid'], 'type' => $row['type'], 'value' => $row['value'], 'category' => $row['category'], 'occurrences' => count($row['relations'])];
		}

		return ['results' => array_slice($hits, $offset, $limit), 'total' => count($hits)];

	}//end catalogueSearch()

	/**
	 * The catalogue find over the rows (called by the catalogue double).
	 *
	 * @param array<int, string>|null $scope The organisations.
	 * @param string                  $uuid  The uuid.
	 *
	 * @return array<string, mixed>|null The entity.
	 */
	public function catalogueFind(?array $scope, string $uuid): ?array {
		$this->catalogueReads++;
		foreach ($this->entities as $row) {
			if ($row['uuid'] === $uuid && ($scope === null || in_array($row['organisation'], $scope, true) === true)) {
				$relations = $row['relations'];
				unset($row['relations'], $row['organisation']);
				return ['entity' => $row, 'relations' => $relations];
			}
		}

		return null;

	}//end catalogueFind()

	/**
	 * An ObjectService that stores saves, refuses the log when told, and holds one anonymisation link.
	 *
	 * @return ObjectService The double.
	 */
	private function objectService(): ObjectService {
		$objects = $this->createMock(ObjectService::class);
		$objects->method('saveObject')->willReturnCallback(
			function (array $object = [], string $register = '', string $schema = '', ?string $uuid = null, bool $_rbac = true) {
				if ($this->logFails === true) {
					throw new RuntimeException('database is locked');
				}

				$this->saved[$schema][] = ['row' => $object, 'rbac' => $_rbac];
				$entity = new ObjectEntity();
				$entity->setUuid($schema . '-' . count($this->saved[$schema]));
				$entity->setObject($object);

				return $entity;
			}
		);
		$objects->method('searchObjectsBySlug')->willReturnCallback(
			static function (string $registerSlug, string $schemaSlug, array $filters = []) {
				if ($schemaSlug === 'anonymizationLink' && ($filters['sourceFileId'] ?? null) === 12) {
					return [['sourceFileId' => 12, 'anonymizedFileId' => 13, '@self' => ['id' => 'link-1']]];
				}

				if ($schemaSlug === 'dossier') {
					return [['name' => 'Bezwaar 2026-14', '@self' => ['id' => 'dossier-1', 'folder' => 500]]];
				}

				return [];
			}
		);

		return $objects;

	}//end objectService()

	/**
	 * A root folder whose user folders return only what that user may read.
	 *
	 * @return IRootFolder The double.
	 */
	private function rootFolder(): IRootFolder {
		$root = $this->createMock(IRootFolder::class);
		$root->method('getUserFolder')->willReturnCallback(
			function (string $userId): Folder {
				$folder = $this->createMock(Folder::class);
				$folder->method('getFirstNodeById')->willReturnCallback(
					function (int $fileId) use ($userId) {
						if (in_array($userId, ($this->files[$fileId]['readers'] ?? []), true) === false) {
							return null;
						}

						$dossierFolder = $this->createMock(Folder::class);
						$dossierFolder->method('getId')->willReturn(500);
						$dossierFolder->method('getParent')->willThrowException(new NotFoundException('the root has no parent'));
						$file = $this->createMock(File::class);
						$file->method('getName')->willReturn($this->files[$fileId]['name']);
						$file->method('getPath')->willReturn('/' . $userId . '/files/Zaken/' . $this->files[$fileId]['name']);
						$file->method('isReadable')->willReturn(true);
						$file->method('getParent')->willReturn($dossierFolder);

						return $file;
					}
				);
				$folder->method('getRelativePath')->willReturnCallback(static fn (string $path): string => substr($path, strlen('/' . $userId . '/files')));

				return $folder;
			}
		);

		return $root;

	}//end rootFolder()

	/**
	 * Run a refused call and return the reason.
	 *
	 * @param callable $action The call.
	 *
	 * @return string The reason.
	 */
	private function refusal(callable $action): string {
		try {
			$action();
		} catch (EntitySearchRefusedException $refusal) {
			return $refusal->getReason();
		}

		$this->fail('The call was expected to be refused.');

	}//end refusal()

	/**
	 * Assert a payload is accepted by the real register schema, closed.
	 *
	 * @param string               $schema  The schema slug.
	 * @param array<string, mixed> $payload The payload.
	 *
	 * @return void
	 */
	private function assertValidAgainstSchema(string $schema, array $payload): void {
		$register = json_decode((string) file_get_contents(__DIR__ . '/../../../../lib/Settings/filinq_register.json'), true);
		$definition = $register['components']['schemas'][$schema];
		$properties = [];
		foreach ($definition['properties'] as $name => $property) {
			unset($property['title']);
			$properties[$name] = $property;
		}

		$json = (string) json_encode(['type' => 'object', 'required' => $definition['required'], 'properties' => (object) $properties, 'additionalProperties' => false]);
		$result = (new Validator())->validate(json_decode((string) json_encode($payload)), $json);
		$message = '';
		if ($result->isValid() === false) {
			$message = (string) json_encode((new ErrorFormatter())->format($result->error()));
		}

		$this->assertTrue($result->isValid(), $schema . ': ' . $message);

	}//end assertValidAgainstSchema()

	/**
	 * A permitted operator finds the entity with its occurrence count.
	 *
	 * @return void
	 */
	public function testSearchByValueReturnsMatchingEntitiesWithCounts(): void {
		$this->current = 'petra';

		$page = $this->service()->search(userId: 'petra', query: 'de vries', type: '', category: '', limit: 25, offset: 0);

		$this->assertSame(1, $page['total']);
		$this->assertSame('Jan de Vries', $page['results'][0]['value']);
		$this->assertSame(4, $page['results'][0]['occurrences']);

	}//end testSearchByValueReturnsMatchingEntitiesWithCounts()

	/**
	 * A non-admin in no organisation gets an empty set, not an error.
	 *
	 * @return void
	 */
	public function testOrganisationScopingIsFailClosed(): void {
		$this->members['bob'] = ['privacy-officers'];
		$this->current = 'bob';

		$page = $this->service()->search(userId: 'bob', query: 'vries', type: '', category: '', limit: 25, offset: 0);

		$this->assertSame([], $page['results']);
		$this->assertSame(0, $page['total']);

	}//end testOrganisationScopingIsFailClosed()

	/**
	 * Organisations that cannot be read refuse the search rather than widen it.
	 *
	 * @return void
	 */
	public function testUnreadableOrganisationsRefuse(): void {
		$this->members['carol'] = ['privacy-officers'];
		$this->current = 'carol';

		$reason = $this->refusal(fn () => $this->service()->search(userId: 'carol', query: 'vries', type: '', category: '', limit: 25, offset: 0));

		$this->assertSame(EntitySearchRefusedException::REASON_CATALOGUE_UNAVAILABLE, $reason);
		$this->assertSame(0, $this->catalogueReads);
		$this->assertSame([], $this->saved);

	}//end testUnreadableOrganisationsRefuse()

	/**
	 * An admin is unscoped and sees both tenants.
	 *
	 * @return void
	 */
	public function testAnAdminSeesEveryOrganisation(): void {
		$this->current = 'root';

		$page = $this->service()->search(userId: 'root', query: 'vries', type: '', category: '', limit: 25, offset: 0);

		$this->assertSame(2, $page['total']);

	}//end testAnAdminSeesEveryOrganisation()

	/**
	 * A non-member is refused before the catalogue is read or anything is logged.
	 *
	 * @return void
	 */
	public function testANonMemberIsRefused(): void {
		$this->current = 'bob';

		$reason = $this->refusal(fn () => $this->service()->search(userId: 'bob', query: 'vries', type: '', category: '', limit: 25, offset: 0));

		$this->assertSame(EntitySearchRefusedException::REASON_NOT_ALLOWED, $reason);
		$this->assertSame(0, $this->catalogueReads);
		$this->assertSame([], $this->saved);

	}//end testANonMemberIsRefused()

	/**
	 * An empty setting means admins only; a broken one refuses admins too.
	 *
	 * @return void
	 */
	public function testEmptyConfigurationMeansAdminsOnly(): void {
		$this->allowedGroups = '[]';
		$this->current = 'petra';
		$this->assertSame(EntitySearchRefusedException::REASON_NOT_ALLOWED, $this->refusal(fn () => $this->service()->access(userId: 'petra')));
		$this->assertSame(['allowed' => true], $this->service()->access(userId: 'root'));

		$this->allowedGroups = '{"privacy-officers": true}';
		$this->assertSame(EntitySearchRefusedException::REASON_CONFIG_UNREADABLE, $this->refusal(fn () => $this->service()->access(userId: 'root')));

	}//end testEmptyConfigurationMeansAdminsOnly()

	/**
	 * A search leaves one log row with the digest, the filters and the count, and never the value.
	 *
	 * @return void
	 */
	public function testSearchProducesADigestOnlyLogEntry(): void {
		$this->current = 'petra';
		$this->entities[] = ['uuid' => 'bsn-1', 'type' => 'SSN', 'value' => '123456782', 'category' => 'personal_data', 'organisation' => 'org-a', 'relations' => []];
		$this->entities[] = ['uuid' => 'bsn-2', 'type' => 'SSN', 'value' => '1234567820', 'category' => 'personal_data', 'organisation' => 'org-a', 'relations' => []];

		$this->service()->search(userId: 'petra', query: ' 123456782 ', type: 'SSN', category: '', limit: 25, offset: 0);

		$this->assertCount(1, $this->saved['entitySearchLog']);
		$entry = $this->saved['entitySearchLog'][0];
		$this->assertFalse($entry['rbac']);
		$this->assertSame('search', $entry['row']['action']);
		$this->assertSame(hash('sha256', '123456782'), $entry['row']['queryDigest']);
		$this->assertSame('SSN', $entry['row']['typeFilter']);
		$this->assertSame(2, $entry['row']['resultCount']);
		$this->assertSame('petra', $entry['row']['performedBy']);
		$this->assertStringNotContainsString('123456782', (string) json_encode(array_diff_key($entry['row'], ['queryDigest' => true])));
		$this->assertValidAgainstSchema(schema: 'entitySearchLog', payload: $entry['row']);

	}//end testSearchProducesADigestOnlyLogEntry()

	/**
	 * When the log does not take the entry, no entity data leaves.
	 *
	 * @return void
	 */
	public function testLogWriteFailureBlocksLookup(): void {
		$this->current = 'petra';
		$this->logFails = true;

		$this->assertSame(
			EntitySearchRefusedException::REASON_LOG_UNAVAILABLE,
			$this->refusal(fn () => $this->service()->search(userId: 'petra', query: 'vries', type: '', category: '', limit: 25, offset: 0))
		);
		$this->assertSame(
			EntitySearchRefusedException::REASON_LOG_UNAVAILABLE,
			$this->refusal(fn () => $this->service()->detail(userId: 'petra', uuid: self::ENTITY))
		);

	}//end testLogWriteFailureBlocksLookup()

	/**
	 * The detail groups occurrences per document with dossier, anonymisation state and risk.
	 *
	 * @return void
	 */
	public function testDetailShowsDocumentsDossiersAndAnonymisationState(): void {
		$this->current = 'petra';

		$detail = $this->service()->detail(userId: 'petra', uuid: self::ENTITY);

		$this->assertSame('Jan de Vries', $detail['value']);
		$this->assertSame([11, 12], array_column($detail['documents'], 'fileId'));
		$this->assertSame('/Zaken/aanvraag.pdf', $detail['documents'][0]['path']);
		$this->assertSame('Bezwaar 2026-14', $detail['documents'][0]['dossier']['name']);
		$this->assertSame('high', $detail['documents'][0]['riskLevel']);
		$this->assertSame(0.97, $detail['documents'][0]['occurrences'][0]['confidence']);
		$this->assertSame('none', $detail['documents'][0]['anonymisation']['state']);
		$this->assertSame('anonymised', $detail['documents'][1]['anonymisation']['state']);
		$this->assertSame([['kind' => 'object', 'count' => 1]], $detail['other']);
		$log = $this->saved['entitySearchLog'][0]['row'];
		$this->assertSame(['detail', self::ENTITY, 4], [$log['action'], $log['entityRef'], $log['occurrenceCount']]);
		$this->assertValidAgainstSchema(schema: 'entitySearchLog', payload: $log);

	}//end testDetailShowsDocumentsDossiersAndAnonymisationState()

	/**
	 * An unreadable file is only counted: no name, no path, and its redacted copy is not named either.
	 *
	 * @return void
	 */
	public function testUnreadableFilesAreOpaque(): void {
		$this->current = 'petra';

		$detail = $this->service()->detail(userId: 'petra', uuid: self::ENTITY);

		$this->assertSame(1, $detail['noAccess']);
		$this->assertStringNotContainsString('geheim-verslag', (string) json_encode($detail));
		$this->assertNull($detail['documents'][1]['anonymisation']['counterpartFileId']);

	}//end testUnreadableFilesAreOpaque()

	/**
	 * Another tenant's entity is not found, and nothing is logged for it.
	 *
	 * @return void
	 */
	public function testAnotherTenantsEntityIsNotFound(): void {
		$this->current = 'petra';

		$this->assertSame(EntitySearchRefusedException::REASON_NOT_FOUND, $this->refusal(fn () => $this->service()->detail(userId: 'petra', uuid: 'other-tenant')));
		$this->assertSame([], $this->saved);

	}//end testAnotherTenantsEntityIsNotFound()

	/**
	 * A search with neither value nor filter is refused.
	 *
	 * @return void
	 */
	public function testAnEmptySearchIsRefused(): void {
		$this->current = 'petra';

		$this->assertSame(EntitySearchRefusedException::REASON_INVALID, $this->refusal(fn () => $this->service()->search(userId: 'petra', query: '  ', type: '', category: '', limit: 25, offset: 0)));

	}//end testAnEmptySearchIsRefused()
}//end class
