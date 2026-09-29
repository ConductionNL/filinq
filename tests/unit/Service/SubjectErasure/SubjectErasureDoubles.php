<?php

/**
 * In-memory files, catalogue, versions and register for the erasure tests,
 * wired to the real erasure services.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\SubjectErasure
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-erase-a-person-while-the-records-stay/tasks.md#task-5.1
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\SubjectErasure;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\FinalDocumentRepository;
use OCA\Filinq\Service\LegalHold\LegalHoldCaseRepository;
use OCA\Filinq\Service\LegalHold\LegalHoldFileFreeze;
use OCA\Filinq\Service\LegalHold\LegalHoldRecordFreeze;
use OCA\Filinq\Service\Publication\PublicationStore;
use OCA\Filinq\Service\SubjectErasure\SubjectErasureAudit;
use OCA\Filinq\Service\SubjectErasure\SubjectErasureAuthority;
use OCA\Filinq\Service\SubjectErasure\SubjectErasureCertifier;
use OCA\Filinq\Service\SubjectErasure\SubjectErasureDocumentStep;
use OCA\Filinq\Service\SubjectErasure\SubjectErasureEraser;
use OCA\Filinq\Service\SubjectErasure\SubjectErasureJob;
use OCA\Filinq\Service\SubjectErasure\SubjectErasureLocator;
use OCA\Filinq\Service\SubjectErasure\SubjectErasureObligations;
use OCA\Filinq\Service\SubjectErasure\SubjectErasurePreview;
use OCA\Filinq\Service\SubjectErasure\SubjectErasureRecordStanding;
use OCA\Filinq\Service\SubjectErasure\SubjectErasureRules;
use OCA\Filinq\Service\SubjectErasure\SubjectErasureRun;
use OCA\Filinq\Service\SubjectErasure\SubjectErasureService;
use OCA\Filinq\Service\SubjectErasure\SubjectErasureStore;
use OCA\Filinq\Tests\Unit\Service\Pseudonymisation\PseudonymDoubles;
use OCA\OpenRegister\Db\AuditTrail;
use OCA\OpenRegister\Db\AuditTrailMapper;
use OCA\OpenRegister\Db\ObjectEntity;
use OCA\OpenRegister\Service\ObjectService;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IAppConfig;
use OCP\IGroupManager;
use OCP\IUser;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;
use RuntimeException;

/**
 * The real services over in-memory Nextcloud and OpenRegister.
 */
trait SubjectErasureDoubles {
	use PseudonymDoubles;

	/**
	 * Files by id: name, content, versions (earlier contents), deleted.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	protected array $files = [];

	/**
	 * Catalogue: type => value => entity id.
	 *
	 * @var array<string, array<string, int>>
	 */
	protected array $catalogue = [];

	/**
	 * Relations: entity id => one file id per occurrence.
	 *
	 * @var array<int, array<int, int>>
	 */
	protected array $relations = [];

	/**
	 * When true the catalogue throws.
	 *
	 * @var bool
	 */
	protected bool $catalogueDown = false;

	/**
	 * OpenRegister records by uuid: their `retention` block.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	protected array $records = [];

	/**
	 * Whether loading an OpenRegister record fails.
	 *
	 * @var bool
	 */
	protected bool $recordsDown = false;

	/**
	 * When true the rewrite leaves the content as it was (a detector that missed).
	 *
	 * @var bool
	 */
	protected bool $rewriteMisses = false;

	/**
	 * Decides per audit entry whether it fails: fn (action, context): bool.
	 *
	 * @var callable|null
	 */
	protected $auditFails = null;

	/**
	 * How often the catalogue was read.
	 *
	 * @var int
	 */
	protected int $catalogueReads = 0;

	/**
	 * The group setting the authority reads.
	 *
	 * @var string
	 */
	protected string $erasureGroups = SubjectErasureAuthority::DEFAULT_GROUPS;

	/**
	 * Users: uid => groups ('admin' makes an admin).
	 *
	 * @var array<string, array<int, string>>
	 */
	protected array $members = ['petra' => ['docudesk-privacy-officer'], 'root' => ['admin'], 'bob' => ['staff']];

	/**
	 * An ObjectEntity whose jsonSerialize is OpenRegister's render shape: the
	 * fields, `@self`, and the uuid as top-level `id` (ObjectEntity::jsonSerialize),
	 * which FinalDocumentRepository reads.
	 *
	 * @param string               $uuid The uuid.
	 * @param array<string, mixed> $row  The stored fields.
	 *
	 * @return ObjectEntity The entity.
	 */
	protected function entity(string $uuid, array $row): ObjectEntity {
		$entity = new class extends ObjectEntity {
			/**
			 * The render shape.
			 *
			 * @return array<string, mixed> Fields plus `@self` and `id`.
			 */
			public function jsonSerialize(): array {
				return array_merge($this->getObject(), ['@self' => ['id' => $this->getUuid()], 'id' => $this->getUuid()]);
			}
		};
		$entity->setUuid($uuid);
		$entity->setObject($row);

		return $entity;

	}//end entity()

	/**
	 * Add a file.
	 *
	 * @param int    $fileId  The id.
	 * @param string $name    The name.
	 * @param string $content The content.
	 *
	 * @return void
	 */
	protected function addFile(int $fileId, string $name, string $content): void {
		$this->files[$fileId] = ['name' => $name, 'content' => $content, 'versions' => ['old bytes of ' . $name . ': ' . $content], 'deleted' => false];

	}//end addFile()

	/**
	 * Put a value in the catalogue with one relation per occurrence.
	 *
	 * @param string          $type    The entity type.
	 * @param string          $value   The value.
	 * @param array<int, int> $fileIds One file id per occurrence.
	 *
	 * @return void
	 */
	protected function catalogueHas(string $type, string $value, array $fileIds): void {
		$entityId = (count($this->relations) + 100);
		$this->catalogue[$type][$value] = $entityId;
		$this->relations[$entityId] = $fileIds;

	}//end catalogueHas()

	/**
	 * A File double over $this->files.
	 *
	 * @param int $fileId The id.
	 *
	 * @return File The double.
	 */
	protected function fileNode(int $fileId): File {
		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn($fileId);
		$file->method('getName')->willReturnCallback(fn (): string => (string) $this->files[$fileId]['name']);
		$file->method('getContent')->willReturnCallback(fn (): string => (string) $this->files[$fileId]['content']);
		$file->method('isUpdateable')->willReturn(true);
		$file->method('putContent')->willReturnCallback(
			function ($data) use ($fileId): void {
				// Nextcloud keeps the previous bytes as a version.
				$this->files[$fileId]['versions'][] = $this->files[$fileId]['content'];
				$this->files[$fileId]['content'] = (string) $data;
			}
		);
		$file->method('delete')->willReturnCallback(
			function () use ($fileId): void {
				$this->files[$fileId]['deleted'] = true;
			}
		);
		$owner = $this->createMock(IUser::class);
		$owner->method('getUID')->willReturn('alice');
		$file->method('getOwner')->willReturn($owner);
		$file->method('getParent')->willReturnCallback(fn (): Folder => $this->folder(name: (string) ($this->files[$fileId]['parent'] ?? 'Documents')));

		return $file;

	}//end fileNode()

	/**
	 * The folder a file sits in; an object folder is named after the record's uuid.
	 *
	 * @param string $name The folder name.
	 *
	 * @return Folder The double.
	 */
	protected function folder(string $name = 'Documents'): Folder {
		$folder = $this->createMock(Folder::class);
		$folder->method('getName')->willReturn($name);
		$folder->method('getNonExistingName')->willReturnCallback(
			function (string $name): string {
				$taken = array_column(array_filter($this->files, static fn (array $f): bool => $f['deleted'] === false), 'name');
				$candidate = $name;
				$index = 2;
				while (in_array($candidate, $taken, true) === true) {
					$candidate = $index . '-' . $name;
					$index++;
				}

				return $candidate;
			}
		);
		$folder->method('newFile')->willReturnCallback(
			function (string $path, mixed $content = null): File {
				$fileId = (max(array_keys($this->files)) + 1);
				$this->files[$fileId] = ['name' => $path, 'content' => (string) $content, 'versions' => [], 'deleted' => false];

				return $this->fileNode(fileId: $fileId);
			}
		);

		return $folder;

	}//end folder()

	/**
	 * The root folder over $this->files.
	 *
	 * @return IRootFolder The double.
	 */
	protected function rootFolder(): IRootFolder {
		$root = $this->createMock(IRootFolder::class);
		$root->method('getFirstNodeById')->willReturnCallback(
			function (int $fileId) {
				if (isset($this->files[$fileId]) === false || $this->files[$fileId]['deleted'] === true) {
					return null;
				}

				return $this->fileNode(fileId: $fileId);
			}
		);

		return $root;

	}//end rootFolder()

	/**
	 * A container answering the catalogue, the processor, the versions and ObjectService.
	 *
	 * @return ContainerInterface The container.
	 */
	protected function erasureContainer(): ContainerInterface {
		$inner = $this->objectService();
		// OpenRegister's search rows carry the uuid as top-level `id` too, which
		// FinalDocumentRepository reads; the shared double leaves it out.
		$objects = $this->createMock(ObjectService::class);
		$objects->method('searchObjectsBySlug')->willReturnCallback(
			static fn (...$args): array => array_map(
				static fn (array $row): array => array_merge($row, ['id' => $row['@self']['id']]),
				$inner->searchObjectsBySlug(...$args)
			)
		);
		$objects->method('find')->willReturnCallback(static fn (...$args) => $inner->find(...$args));
		$objects->method('saveObject')->willReturnCallback(static fn (...$args) => $inner->saveObject(...$args));
		$objects->method('deleteObject')->willReturnCallback(static fn (...$args) => $inner->deleteObject(...$args));
		$test = $this;
		$entities = new class ($test) {
			/**
			 * Constructor.
			 *
			 * @param object $test The test.
			 */
			public function __construct(private readonly object $test) {
			}

			/**
			 * One catalogue entity.
			 *
			 * @param string $value The value.
			 * @param string $type  The type.
			 *
			 * @return object|null The entity.
			 */
			public function findOneByValueAndType(string $value, string $type): ?object {
				return $this->test->catalogueEntity(value: $value, type: $type);
			}
		};
		$relations = new class ($test) {
			/**
			 * Constructor.
			 *
			 * @param object $test The test.
			 */
			public function __construct(private readonly object $test) {
			}

			/**
			 * The relations of one entity.
			 *
			 * @param int $entityId The entity id.
			 *
			 * @return array<int, object> The relations.
			 */
			public function findByEntityId(int $entityId): array {
				return $this->test->relationsOf(entityId: $entityId);
			}
		};
		$processor = new class ($test) {
			/**
			 * Constructor.
			 *
			 * @param object $test The test.
			 */
			public function __construct(private readonly object $test) {
			}

			/**
			 * OpenRegister's replaceWords: a rewritten copy beside the file.
			 *
			 * @param File                  $node         The file.
			 * @param array<string, string> $replacements Value => replacement.
			 * @param string|null           $outputName   The copy's name.
			 * @param bool                  $strict       Fail closed on residual text (PDF).
			 *
			 * @return File The copy.
			 */
			public function replaceWords(File $node, array $replacements, ?string $outputName = null, bool $strict = false): File {
				return $this->test->rewrite(node: $node, replacements: $replacements, outputName: (string) $outputName, strict: $strict);
			}
		};
		$versions = new class ($test) {
			/**
			 * Constructor.
			 *
			 * @param object $test The test.
			 */
			public function __construct(private readonly object $test) {
			}

			/**
			 * The earlier versions.
			 *
			 * @param IUser $user The owner.
			 * @param File  $file The file.
			 *
			 * @return array<int, array{fileId: int, index: int}> The versions.
			 */
			public function getVersionsForFile(IUser $user, File $file): array {
				return $this->test->versionsOf(fileId: $file->getId());
			}

			/**
			 * Delete one version.
			 *
			 * @param array{fileId: int, index: int} $version The version.
			 *
			 * @return void
			 */
			public function deleteVersion(array $version): void {
				$this->test->dropVersion(version: $version);
			}
		};

		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturnCallback(
			static fn (string $id) => match ($id) {
				'OCA\OpenRegister\Db\GdprEntityMapper' => $entities,
				'OCA\OpenRegister\Db\EntityRelationMapper' => $relations,
				'OCA\OpenRegister\Service\File\DocumentProcessingHandler' => $processor,
				'OCA\Files_Versions\Versions\IVersionManager' => $versions,
				default => $objects,
			}
		);

		return $container;

	}//end erasureContainer()

	/**
	 * Catalogue lookup (called by the mapper double).
	 *
	 * @param string $value The value.
	 * @param string $type  The type.
	 *
	 * @return object|null The entity.
	 */
	public function catalogueEntity(string $value, string $type): ?object {
		$this->catalogueReads++;
		if ($this->catalogueDown === true) {
			throw new RuntimeException('database is gone');
		}

		if (isset($this->catalogue[$type][$value]) === false) {
			return null;
		}

		$entityId = $this->catalogue[$type][$value];

		return new class ($entityId) {
			/**
			 * Constructor.
			 *
			 * @param int $entityId The id.
			 */
			public function __construct(private readonly int $entityId) {
			}

			/**
			 * The id.
			 *
			 * @return int The id.
			 */
			public function getId(): int {
				return $this->entityId;
			}
		};

	}//end catalogueEntity()

	/**
	 * Relations of an entity (called by the mapper double).
	 *
	 * @param int $entityId The entity.
	 *
	 * @return array<int, object> The relations.
	 */
	public function relationsOf(int $entityId): array {
		return array_map(
			static fn (int $fileId): object => new class ($fileId) {
				/**
				 * Constructor.
				 *
				 * @param int $fileId The file.
				 */
				public function __construct(private readonly int $fileId) {
				}

				/**
				 * The file.
				 *
				 * @return int The id.
				 */
				public function getFileId(): int {
					return $this->fileId;
				}
			},
			($this->relations[$entityId] ?? [])
		);

	}//end relationsOf()

	/**
	 * The rewrite (called by the processor double).
	 *
	 * @param File                  $node         The file.
	 * @param array<string, string> $replacements Value => replacement.
	 * @param string                $outputName   The copy's name.
	 * @param bool                  $strict       Fail closed on residual text in a PDF.
	 *
	 * @return File The copy.
	 */
	public function rewrite(File $node, array $replacements, string $outputName, bool $strict): File {
		$content = $node->getContent();
		if ($this->rewriteMisses === false) {
			$content = strtr($content, $replacements);
		}

		if ($strict === true && str_ends_with($node->getName(), '.pdf') === true) {
			foreach (array_keys($replacements) as $value) {
				if (str_contains($content, (string) $value) === true) {
					throw new RuntimeException('Residual entity text in the redacted PDF.');
				}
			}
		}

		return $this->folder()->newFile($outputName, $content);

	}//end rewrite()

	/**
	 * Earlier versions (called by the version manager double).
	 *
	 * @param int $fileId The file.
	 *
	 * @return array<int, array{fileId: int, index: int}> The versions.
	 */
	public function versionsOf(int $fileId): array {
		$versions = [];
		foreach (array_keys($this->files[$fileId]['versions']) as $index) {
			$versions[] = ['fileId' => $fileId, 'index' => $index];
		}

		return $versions;

	}//end versionsOf()

	/**
	 * Delete one version (called by the version manager double).
	 *
	 * @param array{fileId: int, index: int} $version The version.
	 *
	 * @return void
	 */
	public function dropVersion(array $version): void {
		unset($this->files[$version['fileId']]['versions'][$version['index']]);

	}//end dropVersion()

	/**
	 * The audit trail: records entries, fails when $auditFails says so.
	 *
	 * @return AuditTrailMapper The double.
	 */
	protected function auditMapper(): AuditTrailMapper {
		$mapper = $this->createMock(AuditTrailMapper::class);
		$mapper->method('createAuditTrailEntry')->willReturnCallback(
			function (ObjectEntity $object, string $action, array $context = []) {
				if ($this->auditFails !== null && ($this->auditFails)($action, $context) === true) {
					throw new RuntimeException('audit table unavailable');
				}

				$this->auditEntries[] = [$action, (string) $object->getUuid(), $context, $object->getRegister(), $object->getSchema()];

				return new AuditTrail();
			}
		);

		return $mapper;

	}//end auditMapper()

	/**
	 * The record loader over $this->records: throws when $recordsDown, as OpenRegister does when its tables are gone.
	 *
	 * @return LegalHoldRecordFreeze The double.
	 */
	protected function recordLoader(): LegalHoldRecordFreeze {
		$loader = $this->createMock(LegalHoldRecordFreeze::class);
		$loader->method('load')->willReturnCallback(
			function (string $ref): ?ObjectEntity {
				if ($this->recordsDown === true) {
					throw new RuntimeException('object table unavailable');
				}

				if (isset($this->records[$ref]) === false) {
					return null;
				}

				$entity = $this->entity(uuid: $ref, row: []);
				$entity->setRetention($this->records[$ref]);

				return $entity;
			}
		);

		return $loader;

	}//end recordLoader()

	/**
	 * The real services, wired.
	 *
	 * @return array{service: SubjectErasureService, run: SubjectErasureRun} The services.
	 */
	protected function erasure(): array {
		$container = $this->erasureContainer();
		$resolver = new DocumentObjectServiceResolver($container, $this->apps());
		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturnCallback(fn (): string => $this->erasureGroups);
		$groups = $this->createMock(IGroupManager::class);
		$groups->method('isAdmin')->willReturnCallback(fn (string $user): bool => in_array('admin', ($this->members[$user] ?? []), true));
		$groups->method('isInGroup')->willReturnCallback(fn (string $user, string $group): bool => in_array($group, ($this->members[$user] ?? []), true));

		$store = new SubjectErasureStore($resolver);
		$audit = new SubjectErasureAudit($this->auditMapper(), $this->clock(), $store);
		$finals = new FinalDocumentRepository($resolver, new NullLogger());
		$locator = new SubjectErasureLocator($container, $this->apps());
		$obligations = new SubjectErasureObligations(
			$this->rootFolder(),
			new LegalHoldCaseRepository($resolver),
			$finals,
			$this->createMock(LegalHoldRecordFreeze::class),
			$this->createMock(LegalHoldFileFreeze::class),
			new SubjectErasureRecordStanding($this->recordLoader())
		);
		$rules = new SubjectErasureRules();
		$service = new SubjectErasureService(
			new SubjectErasureAuthority($config, $groups),
			$store,
			$audit,
			$locator,
			$obligations,
			new SubjectErasurePreview($rules),
			$this->clock()
		);
		$step = new SubjectErasureDocumentStep(
			$rules,
			$obligations,
			new SubjectErasureEraser($container, $finals, $this->clock()),
			$finals,
			$this->mapService(container: $container),
			$audit
		);
		$run = new SubjectErasureRun(
			$service,
			$store,
			$audit,
			$locator,
			$obligations,
			$step,
			new SubjectErasureJob(),
			new SubjectErasureCertifier($store, $rules, new PublicationStore($resolver), $audit, $this->clock())
		);

		return ['service' => $service, 'run' => $run];

	}//end erasure()

	/**
	 * Assert a payload is accepted by the real register schema, closed.
	 *
	 * @param string               $schema  The schema slug.
	 * @param array<string, mixed> $payload The payload as written.
	 *
	 * @return void
	 */
	protected function assertValidAgainstSchema(string $schema, array $payload): void {
		$register = json_decode((string) file_get_contents(__DIR__ . '/../../../../lib/Settings/filinq_register.json'), true);
		$definition = $register['components']['schemas'][$schema];
		$json = (string) json_encode(
			['type' => 'object', 'required' => $definition['required'], 'properties' => (object) $this->validatorProperties(properties: $definition['properties']), 'additionalProperties' => false]
		);
		unset($payload['uuid'], $payload['@self']);
		$result = (new Validator())->validate(json_decode((string) json_encode($payload)), $json);
		$message = '';
		if ($result->isValid() === false) {
			$message = (string) json_encode((new ErrorFormatter())->format($result->error()));
		}

		$this->assertTrue($result->isValid(), $schema . ': ' . $message);

	}//end assertValidAgainstSchema()

	/**
	 * Register properties without OpenRegister's presentation keys, recursively.
	 *
	 * @param array<string, array<string, mixed>> $properties The properties.
	 *
	 * @return array<string, array<string, mixed>> The properties.
	 */
	private function validatorProperties(array $properties): array {
		foreach ($properties as $name => $property) {
			unset($property['required'], $property['visible'], $property['order'], $property['facetable'], $property['x-enum-labels'], $property['title']);
			if (isset($property['items']['properties']) === true) {
				$property['items']['properties'] = (object) $this->validatorProperties(properties: $property['items']['properties']);
			}

			if (isset($property['properties']) === true) {
				$property['properties'] = (object) $this->validatorProperties(properties: $property['properties']);
			}

			$properties[$name] = $property;
		}

		return $properties;

	}//end validatorProperties()
}//end class
