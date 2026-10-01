<?php

/**
 * The decision service over the real classes, for the service and controller tests
 *
 * The classificationResult rows live in the in-memory ClassificationObjectStore
 * (every write validated with Opis against the real fragment). The document
 * object is written through the real MetadataService into an OpenRegister
 * double whose saveObject validates the intakeDocument payload against its
 * real fragment too. Files and folders are resolved per user, so a file
 * another user owns is simply absent from the reviewer's folder.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service\Classification
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-10-02-inbound-auto-classification/tasks.md#4-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Classification;

require_once __DIR__ . '/../Wizard/WizardDoubles.php';
require_once __DIR__ . '/ClassificationDoubles.php';

use DateTimeImmutable;
use OCA\Filinq\Service\Classification\ClassificationDecisionService;
use OCA\Filinq\Service\Classification\ClassificationResultRepository;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\DocumentTextExtractor;
use OCA\Filinq\Service\DossierObjectRepository;
use OCA\Filinq\Service\MetadataService;
use OCA\Filinq\Service\TextAnalysisService;
use OCA\Filinq\Tests\Unit\Service\Wizard\WizardObjectStore;
use OCA\OpenRegister\Contract\ObjectEntityInterface;
use OCA\OpenRegister\Contract\ObjectServiceInterface;
use OCP\App\IAppManager;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;
use RuntimeException;

/**
 * Builds the decision service and keeps its stores inspectable.
 */
trait ClassificationDecisionFixture {

	/**
	 * The classificationResult rows.
	 *
	 * @var ClassificationObjectStore
	 */
	private ClassificationObjectStore $store;

	/**
	 * The intake document objects, by uuid.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private array $documents = [];

	/**
	 * Every intakeDocument write, in order.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $documentSaves = [];

	/**
	 * Nodes per user, by file id: what each user's folder can reach.
	 *
	 * @var array<string, array<int, mixed>>
	 */
	private array $nodes = [];

	/**
	 * Every move, as target path, in order.
	 *
	 * @var array<int, string>
	 */
	private array $moves = [];

	/**
	 * Folder file ids per dossier uuid; a missing dossier throws on lookup.
	 *
	 * @var array<string, int>
	 */
	private array $dossierFolders = [];

	/**
	 * Fresh stores.
	 *
	 * @return void
	 */
	private function resetFixture(): void {
		$this->store = new ClassificationObjectStore();
		$this->documents = ['intake-1' => ['channel' => 'scan', 'status' => 'received', 'file' => 812010, 'fileName' => 'scan.pdf']];
		$this->documentSaves = [];
		$this->nodes = [];
		$this->moves = [];
		$this->dossierFolders = [];

	}//end resetFixture()

	/**
	 * A suggested record for a file, stored as the classifier would.
	 *
	 * @param int                  $fileId The file id.
	 * @param array<string, mixed> $fields Overrides.
	 *
	 * @return string The record uuid.
	 */
	private function suggestion(int $fileId, array $fields = []): string {
		$uuid = 'classification-' . $fileId;
		$record = $fields + [
			'fileId' => $fileId,
			'fileName' => 'scan.pdf',
			'objectId' => 'intake-1',
			'objectRegister' => 'filinq',
			'objectSchema' => 'intakeDocument',
			'suggestedDocumentType' => 'factuur',
			'documentTypeConfidence' => 0.9,
			'method' => 'mixed',
			'suggestedCorrespondent' => ['name' => 'Heijmans B.V.', 'entityType' => 'ORGANIZATION', 'source' => 'ner'],
			'correspondentPending' => false,
			'suggestedDossier' => null,
			'status' => 'suggested',
		];
		WizardObjectStore::assertValid(schema: 'classificationResult', payload: $record);
		$this->store->rows['classificationResult'][$uuid] = $record;

		return $uuid;

	}//end suggestion()

	/**
	 * Make a file reachable in a user's folder.
	 *
	 * @param string $userId The user.
	 * @param int    $fileId The file id.
	 *
	 * @return void
	 */
	private function reachable(string $userId, int $fileId): void {
		$file = $this->createMock(File::class);
		$file->method('getName')->willReturn('scan.pdf');
		$file->method('move')->willReturnCallback(function (string $target) use ($file) {
			$this->moves[] = $target;

			return $file;
		});
		$this->nodes[$userId][$fileId] = $file;

	}//end reachable()

	/**
	 * A dossier whose bound folder a user can reach.
	 *
	 * @param string $userId   The user.
	 * @param string $dossier  The dossier uuid.
	 * @param int    $folderId The folder's file id.
	 *
	 * @return void
	 */
	private function dossierFolder(string $userId, string $dossier, int $folderId): void {
		$folder = $this->createMock(Folder::class);
		$folder->method('getPath')->willReturn('/' . $userId . '/files/Dossiers/Heijmans/');
		$folder->method('getNonExistingName')->willReturnArgument(0);
		$this->nodes[$userId][$folderId] = $folder;
		$this->dossierFolders[$dossier] = $folderId;

	}//end dossierFolder()

	/**
	 * The decision service over the real repository and the real MetadataService.
	 *
	 * @return ClassificationDecisionService The service.
	 */
	private function decisions(): ClassificationDecisionService {
		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($this->store);

		$root = $this->createMock(IRootFolder::class);
		$root->method('getUserFolder')->willReturnCallback(function (string $userId): Folder {
			$folder = $this->createMock(Folder::class);
			$folder->method('getFirstNodeById')->willReturnCallback(fn (int $id): mixed => ($this->nodes[$userId][$id] ?? null));

			return $folder;
		});

		$dossiers = $this->createMock(DossierObjectRepository::class);
		$dossiers->method('loadDossierContext')->willReturnCallback(function (string $dossierUuid): array {
			if (isset($this->dossierFolders[$dossierUuid]) === false) {
				throw new RuntimeException('Dossier not found');
			}

			return ['name' => 'Heijmans', 'description' => '', 'checkedOn' => '', 'folderRef' => $this->dossierFolders[$dossierUuid], 'configuration' => []];
		});

		$time = $this->createMock(ITimeFactory::class);
		$time->method('now')->willReturn(new DateTimeImmutable('2026-10-02T09:30:00+00:00'));

		return new ClassificationDecisionService(
			results: new ClassificationResultRepository(objectResolver: $resolver),
			root: $root,
			metadata: $this->metadataService(),
			dossiers: $dossiers,
			time: $time,
		);

	}//end decisions()

	/**
	 * The real MetadataService over an OpenRegister double holding the intake documents.
	 *
	 * @return MetadataService The service.
	 */
	private function metadataService(): MetadataService {
		$objects = $this->createMock(ObjectServiceInterface::class);
		$objects->method('find')->willReturnCallback(function (int|string $id): ?ObjectEntityInterface {
			if (isset($this->documents[$id]) === false) {
				return null;
			}

			// OpenRegister's ObjectEntity::getObject() puts the uuid first as `id`.
			return $this->entity(data: ['id' => (string) $id] + $this->documents[$id]);
		});
		$objects->method('saveObject')->willReturnCallback(function (array $object, ?array $extend = [], string|int|null $register = null, string|int|null $schema = null): ObjectEntityInterface {
			if ($register !== 'filinq' || $schema !== 'intakeDocument') {
				throw new RuntimeException('unexpected target ' . $register . '/' . $schema);
			}

			// The `id` makes OpenRegister update the found object instead of creating a new one.
			$id = (string) ($object['id'] ?? '');
			if (isset($this->documents[$id]) === false) {
				throw new RuntimeException('intakeDocument save would create a new object');
			}

			unset($object['id']);
			WizardObjectStore::assertValid(schema: 'intakeDocument', payload: $object);
			$this->documents[$id] = $object;
			$this->documentSaves[] = $object;

			return $this->entity(data: $object);
		});

		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturn($objects);
		$apps = $this->createMock(IAppManager::class);
		$apps->method('getInstalledApps')->willReturn(['openregister']);

		return new MetadataService(
			logger: new NullLogger(),
			container: $container,
			appManager: $apps,
			textAnalysisService: $this->createMock(TextAnalysisService::class),
			textExtractor: $this->createMock(DocumentTextExtractor::class),
		);

	}//end metadataService()

	/**
	 * An object entity carrying a payload.
	 *
	 * @param array<string, mixed> $data The payload.
	 *
	 * @return ObjectEntityInterface The entity.
	 */
	private function entity(array $data): ObjectEntityInterface {
		$entity = $this->createMock(ObjectEntityInterface::class);
		$entity->method('getObject')->willReturn($data);

		return $entity;

	}//end entity()
}//end trait
