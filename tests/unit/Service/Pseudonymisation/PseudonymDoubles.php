<?php

/**
 * Shared doubles for the reversible pseudonymisation tests
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Pseudonymisation
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-reversible-pseudonymization/tasks.md#task-5.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Pseudonymisation;

use OCA\Filinq\Service\AnonymizationPersistenceService;
use OCA\Filinq\Service\ConsentCrudService;
use OCA\Filinq\Service\ConsentService;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\OpenRegisterServiceLocator;
use OCA\Filinq\Service\Pseudonymisation\PseudonymMapRecorder;
use OCA\Filinq\Service\Pseudonymisation\PseudonymMapRepository;
use OCA\Filinq\Service\Pseudonymisation\PseudonymMapService;
use OCA\Filinq\Service\Pseudonymisation\PseudonymPairs;
use OCA\Filinq\Service\Pseudonymisation\PseudonymRestoreAudit;
use OCA\Filinq\Service\Pseudonymisation\PseudonymRestoreGate;
use OCA\Filinq\Service\Pseudonymisation\PseudonymRestoreService;
use OCA\Filinq\Service\Redaction\AnonymizationLinkReader;
use OCA\OpenRegister\Db\AuditTrail;
use OCA\OpenRegister\Db\AuditTrailMapper;
use OCA\OpenRegister\Db\ObjectEntity;
use OCA\OpenRegister\Service\ObjectService;
use OCP\App\IAppManager;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IAppConfig;
use OCP\IGroupManager;
use OCP\Security\ICrypto;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;
use RuntimeException;

/**
 * An in-memory OpenRegister, a crypto that visibly transforms, and the real
 * pseudonymisation classes wired over them.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Pseudonymisation
 * @author   Conduction Development Team <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
trait PseudonymDoubles {

	/**
	 * The register id OpenRegister stores objects under in these doubles.
	 */
	protected const REGISTER_ID = '12';

	/**
	 * Schema ids by slug in these doubles.
	 */
	protected const SCHEMA_IDS = ['anonymizationLink' => '41', 'pseudonymMap' => '57'];

	/**
	 * Stored rows per schema, keyed by uuid.
	 *
	 * @var array<string, array<string, array<string, mixed>>>
	 */
	protected array $rows = [];

	/**
	 * Whether the register refuses every write, as a failed validation or
	 * an unreachable database would.
	 *
	 * @var boolean
	 */
	protected bool $registerRefusesWrites = false;

	/**
	 * Every call that passed `_rbac: false`, as "method:schema".
	 *
	 * @var array<int, string>
	 */
	protected array $bypasses = [];

	/**
	 * Audit entries written: [action, uuid, context, register id, schema id].
	 *
	 * @var array<int, array{0: string, 1: string, 2: array<string, mixed>, 3: string|null, 4: string|null}>
	 */
	protected array $auditEntries = [];

	/**
	 * Audit actions that throw when written.
	 *
	 * @var array<int, string>
	 */
	protected array $failingAuditActions = [];

	/**
	 * The entity rows OpenRegister has for the file: value => {id, type}.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	protected array $entityRows = [];

	/**
	 * The in-memory ObjectService.
	 *
	 * @return ObjectService The double.
	 */
	protected function objectService(): ObjectService {
		$objects = $this->createMock(ObjectService::class);
		$objects->method('searchObjectsBySlug')->willReturnCallback(
			function (string $registerSlug, string $schemaSlug, array $filters = [], bool $_rbac = true) {
				if ($_rbac === false) {
					$this->bypasses[] = 'search:' . $schemaSlug;
				}

				$hits = [];
				foreach (($this->rows[$schemaSlug] ?? []) as $uuid => $row) {
					foreach ($filters as $field => $value) {
						if (($row[$field] ?? null) !== $value) {
							continue 2;
						}
					}

					// A rendered read: writeOnly stripped, as OpenRegister does.
					unset($row['mappings']);
					$hits[] = array_merge($row, ['@self' => ['id' => $uuid, 'schema' => $schemaSlug]]);
				}

				return $hits;
			}
		);
		$objects->method('find')->willReturnCallback(
			function (string $id = '', string $register = '', string $schema = '', bool $_rbac = true, bool $_multitenancy = true, bool $_render = true) {
				if (isset($this->rows[$schema][$id]) === false) {
					return null;
				}

				$row = $this->rows[$schema][$id];
				if ($_render === true) {
					unset($row['mappings']);
				}

				$entity = $this->entity(uuid: $id, row: $row);
				// OpenRegister answers with the stored ids, not the slugs it was asked by.
				$entity->setRegister(self::REGISTER_ID);
				$entity->setSchema((self::SCHEMA_IDS[$schema] ?? '99'));

				return $entity;
			}
		);
		$objects->method('saveObject')->willReturnCallback(
			function (array $object = [], string $register = '', string $schema = '', ?string $uuid = null, bool $_rbac = true) {
				if ($this->registerRefusesWrites === true) {
					throw new \RuntimeException('database is unreachable');
				}

				if ($_rbac === false) {
					$this->bypasses[] = 'save:' . $schema;
				}

				$uuid = $uuid ?? ($object['@self']['id'] ?? null) ?? ($schema . '-' . (count($this->rows[$schema] ?? []) + 1));
				unset($object['@self']);
				$this->rows[$schema][$uuid] = $object;

				return $this->entity(uuid: $uuid, row: $object);
			}
		);
		$objects->method('deleteObject')->willReturnCallback(
			function (string $uuid = '', string $register = '', string $schema = '', bool $_rbac = true) {
				if ($_rbac === false) {
					$this->bypasses[] = 'delete:' . $schema;
				}

				unset($this->rows[$schema][$uuid]);

				return true;
			}
		);

		return $objects;

	}//end objectService()

	/**
	 * An ObjectEntity whose jsonSerialize is OpenRegister's render shape
	 * (fields plus `@self`), which the shared stub leaves empty.
	 *
	 * @param string $uuid The uuid.
	 * @param array<string, mixed> $row The stored fields.
	 *
	 * @return ObjectEntity The entity.
	 */
	protected function entity(string $uuid, array $row): ObjectEntity {
		$entity = new class extends ObjectEntity {
			/**
			 * The render shape.
			 *
			 * @return array<string, mixed> Fields plus `@self`.
			 */
			public function jsonSerialize(): array {
				return array_merge($this->getObject(), ['@self' => ['id' => $this->getUuid()]]);
			}
		};
		$entity->setUuid($uuid);
		$entity->setObject($row);

		return $entity;

	}//end entity()

	/**
	 * A container that answers ObjectService and EntityRelationMapper.
	 *
	 * @return ContainerInterface The container.
	 */
	protected function container(): ContainerInterface {
		$objects = $this->objectService();
		$mapper = new class ($this) {
			/**
			 * Constructor.
			 *
			 * @param object $test The test holding the entity rows.
			 */
			public function __construct(private readonly object $test) {
			}

			/**
			 * The entity rows of the file.
			 *
			 * @param int $fileId The file id.
			 *
			 * @return array<string, array<string, mixed>> The rows.
			 */
			public function findEntityIdsByValueForFile(int $fileId): array {
				return $this->test->entityRowsFor(fileId: $fileId);
			}
		};

		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturnCallback(
			static fn (string $id) => match ($id) {
				'OCA\OpenRegister\Db\EntityRelationMapper' => $mapper,
				default => $objects,
			}
		);

		return $container;

	}//end container()

	/**
	 * The entity rows for a file (read by the anonymous mapper).
	 *
	 * @param int $fileId The file id.
	 *
	 * @return array<string, array<string, mixed>> The rows.
	 */
	public function entityRowsFor(int $fileId): array {
		unset($fileId);

		return $this->entityRows;

	}//end entityRowsFor()

	/**
	 * An app manager that says OpenRegister is installed.
	 *
	 * @return IAppManager The double.
	 */
	protected function apps(): IAppManager {
		$apps = $this->createMock(IAppManager::class);
		$apps->method('getInstalledApps')->willReturn(['openregister']);

		return $apps;

	}//end apps()

	/**
	 * A crypto that transforms visibly and refuses what it did not produce.
	 *
	 * @return ICrypto The double.
	 */
	protected function crypto(): ICrypto {
		$crypto = $this->createMock(ICrypto::class);
		$crypto->method('encrypt')->willReturnCallback(static fn (string $plain): string => 'enc:' . base64_encode(strrev($plain)));
		$crypto->method('decrypt')->willReturnCallback(
			static function (string $cipher): string {
				if (str_starts_with($cipher, 'enc:') === false) {
					throw new \Exception('HMAC does not match.');
				}

				return strrev((string) base64_decode(substr($cipher, 4), true));
			}
		);

		return $crypto;

	}//end crypto()

	/**
	 * A fixed clock.
	 *
	 * @return ITimeFactory The double.
	 */
	protected function clock(): ITimeFactory {
		$time = $this->createMock(ITimeFactory::class);
		$time->method('getDateTime')->willReturn(new \DateTime('2026-09-29T10:00:00+00:00'));

		return $time;

	}//end clock()

	/**
	 * The real map service over the in-memory register.
	 *
	 * @param ContainerInterface|null $container The container, a fresh one when null.
	 *
	 * @return PseudonymMapService The service.
	 */
	protected function mapService(?ContainerInterface $container = null): PseudonymMapService {
		$container = $container ?? $this->container();

		return new PseudonymMapService(
			new PseudonymMapRepository(new DocumentObjectServiceResolver($container, $this->apps())),
			$this->crypto(),
			$this->clock()
		);

	}//end mapService()

	/**
	 * The real recorder, with the real persistence service over the same register.
	 *
	 * @return PseudonymMapRecorder The recorder.
	 */
	protected function recorder(): PseudonymMapRecorder {
		$container = $this->container();
		$locator = new OpenRegisterServiceLocator($this->apps(), $container);
		$persistence = new AnonymizationPersistenceService(
			new NullLogger(),
			$locator,
			$this->createMock(ConsentCrudService::class),
			$this->createMock(ConsentService::class)
		);

		return new PseudonymMapRecorder(new PseudonymPairs(), $this->mapService(container: $container), $persistence, $locator, new NullLogger());

	}//end recorder()

	/**
	 * An audit trail that records, and throws for the actions listed in $failingAuditActions.
	 *
	 * @param ContainerInterface|null $container The container holding the register; a fresh one when null.
	 *
	 * @return PseudonymRestoreAudit The audit service.
	 */
	protected function audit(?ContainerInterface $container = null): PseudonymRestoreAudit {
		$mapper = $this->createMock(AuditTrailMapper::class);
		$mapper->method('createAuditTrailEntry')->willReturnCallback(
			function (ObjectEntity $object, string $action, array $context = []) {
				if (in_array($action, $this->failingAuditActions, true) === true) {
					throw new RuntimeException('audit table unavailable');
				}

				$this->auditEntries[] = [$action, (string) $object->getUuid(), $context, $object->getRegister(), $object->getSchema()];

				return new AuditTrail();
			}
		);

		$resolver = new DocumentObjectServiceResolver(($container ?? $this->container()), $this->apps());

		return new PseudonymRestoreAudit($mapper, $this->clock(), new AnonymizationLinkReader($resolver, new NullLogger()));

	}//end audit()

	/**
	 * The gate over a configured group list.
	 *
	 * @param string $allowedGroups The stored config value.
	 * @param array<string, array<int, string>> $membership user => groups; a user in 'admin' is admin.
	 *
	 * @return PseudonymRestoreGate The gate.
	 */
	protected function gate(string $allowedGroups, array $membership): PseudonymRestoreGate {
		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturn($allowedGroups);
		$groups = $this->createMock(IGroupManager::class);
		$groups->method('isAdmin')->willReturnCallback(static fn (string $user): bool => in_array('admin', ($membership[$user] ?? []), true));
		$groups->method('isInGroup')->willReturnCallback(static fn (string $user, string $group): bool => in_array($group, ($membership[$user] ?? []), true));

		return new PseudonymRestoreGate($config, $groups);

	}//end gate()

	/**
	 * The restore service over one anonymised file.
	 *
	 * @param PseudonymRestoreGate $gate The gate.
	 * @param File|null $copy The anonymised copy the caller can open, null when they cannot.
	 * @param ContainerInterface $container The container holding the register.
	 *
	 * @return PseudonymRestoreService The service.
	 */
	protected function restoreService(PseudonymRestoreGate $gate, ?File $copy, ContainerInterface $container): PseudonymRestoreService {
		$userFolder = $this->createMock(Folder::class);
		$userFolder->method('getFirstNodeById')->willReturn($copy);
		$root = $this->createMock(IRootFolder::class);
		$root->method('getUserFolder')->willReturn($userFolder);
		$resolver = new DocumentObjectServiceResolver($container, $this->apps());

		return new PseudonymRestoreService(
			$gate,
			$this->audit(container: $container),
			$this->mapService(container: $container),
			new PseudonymPairs(),
			new AnonymizationLinkReader($resolver, new NullLogger()),
			$root,
			new NullLogger()
		);

	}//end restoreService()

	/**
	 * An anonymised copy whose parent records what gets written next to it.
	 *
	 * @param string $mimeType The copy's mime type.
	 * @param string $content The copy's text.
	 * @param array<int, array{0: string, 1: string}> $written Receives [name, content] of new files.
	 *
	 * @return File The copy. putContent on it fails the test.
	 */
	protected function anonymisedCopy(string $mimeType, string $content, array &$written): File {
		$parent = $this->createMock(Folder::class);
		$parent->method('getNonExistingName')->willReturnArgument(0);
		$parent->method('newFile')->willReturnCallback(
			function (string $name, $data) use (&$written) {
				$written[] = [$name, (string) $data];
				$file = $this->createMock(File::class);
				$file->method('getId')->willReturn(990001);
				$file->method('getName')->willReturn($name);
				$file->method('getPath')->willReturn('/alice/files/Woo/' . $name);

				return $file;
			}
		);

		$copy = $this->createMock(File::class);
		$copy->method('getId')->willReturn(812201);
		$copy->method('getName')->willReturn('besluit_anonymized.' . ($mimeType === 'application/pdf' ? 'pdf' : 'txt'));
		$copy->method('getMimeType')->willReturn($mimeType);
		$copy->method('getContent')->willReturn($content);
		$copy->method('getParent')->willReturn($parent);
		$copy->expects($this->never())->method('putContent');

		return $copy;

	}//end anonymisedCopy()

	/**
	 * Seed a link plus a stored map, the way a reversible run leaves them.
	 *
	 * @param ContainerInterface $container The container holding the register.
	 * @param array<int, array<string, string>> $pairs The pairs to keep.
	 *
	 * @return string The link uuid.
	 */
	protected function seedReversibleRun(ContainerInterface $container, array $pairs): string {
		$this->rows['anonymizationLink']['link-1'] = [
			'sourceFileId' => 812200,
			'anonymizedFileId' => 812201,
			'anonymizedFileName' => 'besluit_anonymized.txt',
		];
		$stored = $this->mapService(container: $container)->store(
			linkId: 'link-1',
			sourceFileId: 812200,
			pairs: $pairs,
			scope: 'document',
			userId: 'alice'
		);
		$this->rows['anonymizationLink']['link-1']['mappingRef'] = $stored['uuid'];

		return 'link-1';

	}//end seedReversibleRun()

	/**
	 * The pairs of a letter that names two people.
	 *
	 * @return array<int, array<string, string>> The pairs.
	 */
	protected function twoPeople(): array {
		return [
			['placeholder' => '[PERSOON: 1]', 'originalValue' => 'Jan Jansen', 'entityType' => 'PERSON'],
			['placeholder' => '[PERSOON: 10]', 'originalValue' => 'Marieke de Vries', 'entityType' => 'PERSON'],
		];

	}//end twoPeople()
}//end trait
