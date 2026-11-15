<?php

/**
 * Unit tests for DossierObjectRepository
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
 * @spec openspec/specs/anonymisation-grondslagen-summary/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use Exception;
use OCA\Filinq\Service\DossierObjectRepository;
use OCA\OpenRegister\Db\ObjectEntity;
use OCA\OpenRegister\Service\ObjectService;
use OCP\App\IAppManager;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * The repository resolves OpenRegister services and the dossier folder, loads
 * the dossier context, and writes the grondslagen freshness metadata back
 * while preserving the entity-level folder binding.
 *
 * @covers \OCA\Filinq\Service\DossierObjectRepository
 */
class DossierObjectRepositoryTest extends TestCase {

	/**
	 * App manager mock.
	 *
	 * @var IAppManager&MockObject
	 */
	private IAppManager&MockObject $appManager;

	/**
	 * Container mock.
	 *
	 * @var ContainerInterface&MockObject
	 */
	private ContainerInterface&MockObject $container;

	/**
	 * Root folder mock.
	 *
	 * @var IRootFolder&MockObject
	 */
	private IRootFolder&MockObject $rootFolder;

	/**
	 * User session mock.
	 *
	 * @var IUserSession&MockObject
	 */
	private IUserSession&MockObject $userSession;

	/**
	 * Logger mock.
	 *
	 * @var LoggerInterface&MockObject
	 */
	private LoggerInterface&MockObject $logger;

	/**
	 * Build the repository's collaborators.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->appManager = $this->createMock(IAppManager::class);
		$this->container = $this->createMock(ContainerInterface::class);
		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->logger = $this->createMock(LoggerInterface::class);

	}//end setUp()

	/**
	 * The repository under test.
	 *
	 * @return DossierObjectRepository
	 */
	private function repository(): DossierObjectRepository {
		return new DossierObjectRepository(
			$this->appManager,
			$this->container,
			$this->rootFolder,
			$this->userSession,
			$this->logger
		);

	}//end repository()

	/**
	 * Mark OpenRegister installed and let the container answer ObjectService.
	 *
	 * @param object $objectService The ObjectService double.
	 *
	 * @return void
	 */
	private function withObjectService(object $objectService): void {
		$this->appManager->method('getInstalledApps')->willReturn(['files', 'openregister']);
		$this->container->method('get')
			->with('OCA\OpenRegister\Service\ObjectService')
			->willReturn($objectService);

	}//end withObjectService()

	/**
	 * A session user with the given uid.
	 *
	 * @param string $uid The uid.
	 *
	 * @return void
	 */
	private function withUser(string $uid): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn($uid);
		$this->userSession->method('getUser')->willReturn($user);

	}//end withUser()

	/**
	 * A null or empty folder reference is refused before any lookup.
	 *
	 * @return void
	 */
	public function testResolveDossierFolderRefusesAMissingReference(): void {
		$this->userSession->expects($this->never())->method('getUser');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Grondslagen summary: dossier has no @self.folder reference.');

		$this->repository()->resolveDossierFolder('');

	}//end testResolveDossierFolderRefusesAMissingReference()

	/**
	 * A null folder reference is refused too.
	 *
	 * @return void
	 */
	public function testResolveDossierFolderRefusesANullReference(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('dossier has no @self.folder reference');

		$this->repository()->resolveDossierFolder(null);

	}//end testResolveDossierFolderRefusesANullReference()

	/**
	 * Without a session user the folder cannot be resolved.
	 *
	 * @return void
	 */
	public function testResolveDossierFolderRequiresASessionUser(): void {
		$this->userSession->method('getUser')->willReturn(null);
		$this->rootFolder->expects($this->never())->method('getUserFolder');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Grondslagen summary: no session user to resolve folder.');

		$this->repository()->resolveDossierFolder(42);

	}//end testResolveDossierFolderRequiresASessionUser()

	/**
	 * The reference is cast to int, looked up in the user's folder, and the first node returned.
	 *
	 * @return void
	 */
	public function testResolveDossierFolderReturnsTheFirstFolderNode(): void {
		$this->withUser('alice');
		$folder = $this->createMock(Folder::class);
		$other = $this->createMock(Folder::class);
		$userFolder = $this->createMock(Folder::class);
		$userFolder->expects($this->once())->method('getById')->with(42)->willReturn([$folder, $other]);
		$this->rootFolder->expects($this->once())->method('getUserFolder')->with('alice')->willReturn($userFolder);

		$this->assertSame($folder, $this->repository()->resolveDossierFolder('42'));

	}//end testResolveDossierFolderReturnsTheFirstFolderNode()

	/**
	 * An id that matches nothing names the id and the user.
	 *
	 * @return void
	 */
	public function testResolveDossierFolderReportsAnUnknownNodeId(): void {
		$this->withUser('alice');
		$userFolder = $this->createMock(Folder::class);
		$userFolder->method('getById')->willReturn([]);
		$this->rootFolder->method('getUserFolder')->willReturn($userFolder);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Grondslagen summary: folder node id 7 not found for user alice');

		$this->repository()->resolveDossierFolder(7);

	}//end testResolveDossierFolderReportsAnUnknownNodeId()

	/**
	 * A NotFoundException from the file API is wrapped with the original as previous.
	 *
	 * @return void
	 */
	public function testResolveDossierFolderWrapsNotFound(): void {
		$this->withUser('alice');
		$notFound = new NotFoundException('gone');
		$this->rootFolder->method('getUserFolder')->willThrowException($notFound);

		try {
			$this->repository()->resolveDossierFolder(9);
			$this->fail('Expected a RuntimeException.');
		} catch (RuntimeException $e) {
			$this->assertSame('Grondslagen summary: dossier folder not found (9): gone', $e->getMessage());
			$this->assertSame($notFound, $e->getPrevious());
		}

	}//end testResolveDossierFolderWrapsNotFound()

	/**
	 * A node that is a file, not a folder, is refused.
	 *
	 * @return void
	 */
	public function testResolveDossierFolderRefusesAFileNode(): void {
		$this->withUser('alice');
		$userFolder = $this->createMock(Folder::class);
		$userFolder->method('getById')->willReturn([$this->createMock(File::class)]);
		$this->rootFolder->method('getUserFolder')->willReturn($userFolder);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Grondslagen summary: dossier @self.folder (5) is not a folder node.');

		$this->repository()->resolveDossierFolder(5);

	}//end testResolveDossierFolderRefusesAFileNode()

	/**
	 * Without OpenRegister installed neither service is fetched from the container.
	 *
	 * @return void
	 */
	public function testServicesAreNullWithoutOpenRegister(): void {
		$this->appManager->method('getInstalledApps')->willReturn(['files', 'openregister_old']);
		$this->container->expects($this->never())->method('get');

		$repository = $this->repository();

		$this->assertNull($repository->objectService());
		$this->assertNull($repository->entityRelationMapper());

	}//end testServicesAreNullWithoutOpenRegister()

	/**
	 * With OpenRegister installed each service is fetched by its OR class name.
	 *
	 * @return void
	 */
	public function testServicesAreResolvedFromTheContainer(): void {
		$objectService = new \stdClass();
		$mapper = new \stdClass();
		$this->appManager->method('getInstalledApps')->willReturn(['openregister']);
		$this->container->expects($this->exactly(2))->method('get')->willReturnMap(
			[
				['OCA\OpenRegister\Service\ObjectService', $objectService],
				['OCA\OpenRegister\Db\EntityRelationMapper', $mapper],
			]
		);

		$repository = $this->repository();

		$this->assertSame($objectService, $repository->objectService());
		$this->assertSame($mapper, $repository->entityRelationMapper());

	}//end testServicesAreResolvedFromTheContainer()

	/**
	 * A container failure for ObjectService is logged and answered with null.
	 *
	 * @return void
	 */
	public function testObjectServiceFailureIsLoggedAsNull(): void {
		$this->appManager->method('getInstalledApps')->willReturn(['openregister']);
		$this->container->method('get')->willThrowException(new Exception('boom'));
		$this->logger->expects($this->once())->method('warning')->with(
			'LegalBasesSummaryService: ObjectService unavailable',
			['error' => 'boom']
		);

		$this->assertNull($this->repository()->objectService());

	}//end testObjectServiceFailureIsLoggedAsNull()

	/**
	 * A container failure for EntityRelationMapper is logged and answered with null.
	 *
	 * @return void
	 */
	public function testEntityRelationMapperFailureIsLoggedAsNull(): void {
		$this->appManager->method('getInstalledApps')->willReturn(['openregister']);
		$this->container->method('get')->willThrowException(new Exception('nope'));
		$this->logger->expects($this->once())->method('warning')->with(
			'LegalBasesSummaryService: EntityRelationMapper unavailable',
			['error' => 'nope']
		);

		$this->assertNull($this->repository()->entityRelationMapper());

	}//end testEntityRelationMapperFailureIsLoggedAsNull()

	/**
	 * The context of an ObjectEntity takes the folder from the entity column.
	 *
	 * @return void
	 */
	public function testLoadDossierContextReadsTheEntityFolderColumn(): void {
		$entity = new ObjectEntity();
		$entity->setObject(
			[
				'name' => 'Dossier A',
				'description' => 'Beschrijving',
				'checkedOn' => '2026-01-02',
				'configuration' => ['grondslagen' => ['fileId' => 3]],
				'@self' => ['folder' => 'payload-folder'],
			]
		);
		$entity->setFolder(123);

		$objectService = $this->createMock(ObjectService::class);
		$objectService->expects($this->once())->method('find')
			->with('uuid-1', 'filinq', 'dossier', false, false)
			->willReturn($entity);
		$this->withObjectService($objectService);

		$this->assertSame(
			[
				'name' => 'Dossier A',
				'description' => 'Beschrijving',
				'checkedOn' => '2026-01-02',
				'folderRef' => 123,
				'configuration' => ['grondslagen' => ['fileId' => 3]],
			],
			$this->repository()->loadDossierContext('uuid-1')
		);

	}//end testLoadDossierContextReadsTheEntityFolderColumn()

	/**
	 * An entity without a folder column falls back to the payload's @self.folder.
	 *
	 * @return void
	 */
	public function testLoadDossierContextFallsBackToThePayloadFolder(): void {
		$entity = new ObjectEntity();
		$entity->setObject(['name' => 'B', '@self' => ['folder' => 'payload-folder']]);
		$entity->setFolder('');

		$objectService = $this->createMock(ObjectService::class);
		$objectService->method('find')->willReturn($entity);
		$this->withObjectService($objectService);

		$context = $this->repository()->loadDossierContext('uuid-2');

		$this->assertSame('payload-folder', $context['folderRef']);
		$this->assertSame('B', $context['name']);
		$this->assertSame('', $context['description']);
		$this->assertSame('', $context['checkedOn']);
		$this->assertSame([], $context['configuration']);

	}//end testLoadDossierContextFallsBackToThePayloadFolder()

	/**
	 * A plain array result is used as its own payload, and has no entity folder.
	 *
	 * @return void
	 */
	public function testLoadDossierContextAcceptsAnArrayResult(): void {
		$objectService = $this->createMock(ObjectService::class);
		$objectService->method('find')->willReturn(['name' => 'C', 'description' => 'D']);
		$this->withObjectService($objectService);

		$context = $this->repository()->loadDossierContext('uuid-3');

		$this->assertSame('C', $context['name']);
		$this->assertSame('D', $context['description']);
		$this->assertNull($context['folderRef']);

	}//end testLoadDossierContextAcceptsAnArrayResult()

	/**
	 * A throwing folder getter on the entity is treated as "no folder".
	 *
	 * @return void
	 */
	public function testLoadDossierContextIgnoresAThrowingFolderGetter(): void {
		$entity = new class extends ObjectEntity {
			/**
			 * Always fails.
			 *
			 * @return int|string|null
			 */
			public function getFolder(): int|string|null {
				throw new RuntimeException('column missing');
			}
		};
		$entity->setObject(['name' => 'E', '@self' => ['folder' => 77]]);

		$objectService = $this->createMock(ObjectService::class);
		$objectService->method('find')->willReturn($entity);
		$this->withObjectService($objectService);

		$this->assertSame(77, $this->repository()->loadDossierContext('uuid-4')['folderRef']);

	}//end testLoadDossierContextIgnoresAThrowingFolderGetter()

	/**
	 * Without OpenRegister the context cannot be loaded.
	 *
	 * @return void
	 */
	public function testLoadDossierContextFailsWithoutObjectService(): void {
		$this->appManager->method('getInstalledApps')->willReturn([]);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Grondslagen summary: OpenRegister ObjectService unavailable.');

		$this->repository()->loadDossierContext('uuid-5');

	}//end testLoadDossierContextFailsWithoutObjectService()

	/**
	 * A lookup failure is wrapped with the dossier uuid and the cause.
	 *
	 * @return void
	 */
	public function testLoadDossierContextWrapsALookupFailure(): void {
		$cause = new Exception('db down');
		$objectService = $this->createMock(ObjectService::class);
		$objectService->method('find')->willThrowException($cause);
		$this->withObjectService($objectService);

		try {
			$this->repository()->loadDossierContext('uuid-6');
			$this->fail('Expected a RuntimeException.');
		} catch (RuntimeException $e) {
			$this->assertSame('Grondslagen summary: failed to load dossier uuid-6: db down', $e->getMessage());
			$this->assertSame($cause, $e->getPrevious());
		}

	}//end testLoadDossierContextWrapsALookupFailure()

	/**
	 * A lookup that finds nothing names the missing dossier.
	 *
	 * @return void
	 */
	public function testLoadDossierContextReportsAMissingDossier(): void {
		$objectService = $this->createMock(ObjectService::class);
		$objectService->method('find')->willReturn(null);
		$this->withObjectService($objectService);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Grondslagen summary: dossier not found: uuid-7');

		$this->repository()->loadDossierContext('uuid-7');

	}//end testLoadDossierContextReportsAMissingDossier()

	/**
	 * Without ObjectService the update is skipped with a warning.
	 *
	 * @return void
	 */
	public function testUpdateDossierConfigurationWarnsWithoutObjectService(): void {
		$this->appManager->method('getInstalledApps')->willReturn([]);
		$this->logger->expects($this->once())->method('warning')->with(
			'LegalBasesSummaryService: cannot update dossier configuration — ObjectService unavailable',
			['dossierUuid' => 'uuid-8']
		);

		$this->repository()->updateDossierConfiguration('uuid-8', 5);

	}//end testUpdateDossierConfigurationWarnsWithoutObjectService()

	/**
	 * A dossier that no longer exists is not saved.
	 *
	 * @return void
	 */
	public function testUpdateDossierConfigurationSkipsAMissingDossier(): void {
		$objectService = $this->createMock(ObjectService::class);
		$objectService->expects($this->once())->method('find')
			->with('uuid-9', 'filinq', 'dossier', false, false)
			->willReturn(null);
		$objectService->expects($this->never())->method('saveObject');
		$this->withObjectService($objectService);
		$this->logger->expects($this->never())->method('warning');

		$this->repository()->updateDossierConfiguration('uuid-9', 5);

	}//end testUpdateDossierConfigurationSkipsAMissingDossier()

	/**
	 * The save merges the grondslagen metadata, keeps other configuration and
	 * re-injects the entity's string folder into @self.
	 *
	 * @return void
	 */
	public function testUpdateDossierConfigurationSavesMergedMetadataAndFolder(): void {
		$entity = new ObjectEntity();
		$entity->setObject(
			[
				'name' => 'Dossier',
				'configuration' => ['other' => 'kept', 'grondslagen' => ['fileId' => 1, 'note' => 'n']],
				'@self' => ['register' => 'filinq'],
			]
		);
		$entity->setFolder('folder-abc');

		$saved = null;
		$objectService = $this->createMock(ObjectService::class);
		$objectService->method('find')->willReturn($entity);
		$objectService->expects($this->once())->method('saveObject')
			->with($this->anything(), 'filinq', 'dossier', 'uuid-10', false, false)
			->willReturnCallback(
				static function (array $object) use (&$saved) {
					$saved = $object;
					return null;
				}
			);
		$this->withObjectService($objectService);
		$this->logger->expects($this->never())->method('warning');

		$this->repository()->updateDossierConfiguration('uuid-10', 99);

		$this->assertSame('Dossier', $saved['name']);
		$this->assertSame(['register' => 'filinq', 'folder' => 'folder-abc'], $saved['@self']);
		$this->assertSame('kept', $saved['configuration']['other']);
		$this->assertSame(99, $saved['configuration']['grondslagen']['fileId']);
		$this->assertSame('n', $saved['configuration']['grondslagen']['note']);
		$this->assertMatchesRegularExpression(
			'/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/',
			$saved['configuration']['grondslagen']['lastGeneratedAt']
		);

	}//end testUpdateDossierConfigurationSavesMergedMetadataAndFolder()

	/**
	 * An array result with non-array configuration gets a fresh grondslagen block and no folder.
	 *
	 * @return void
	 */
	public function testUpdateDossierConfigurationReplacesNonArrayConfiguration(): void {
		$saved = null;
		$objectService = $this->createMock(ObjectService::class);
		$objectService->method('find')->willReturn(['name' => 'X', 'configuration' => 'broken']);
		$objectService->expects($this->once())->method('saveObject')->willReturnCallback(
			static function (array $object) use (&$saved) {
				$saved = $object;
				return null;
			}
		);
		$this->withObjectService($objectService);

		$this->repository()->updateDossierConfiguration('uuid-11', 4);

		$this->assertSame(['grondslagen'], array_keys($saved['configuration']));
		$this->assertSame(4, $saved['configuration']['grondslagen']['fileId']);
		$this->assertArrayNotHasKey('@self', $saved);

	}//end testUpdateDossierConfigurationReplacesNonArrayConfiguration()

	/**
	 * A non-array grondslagen block is replaced, and a non-array @self is reset before the folder goes in.
	 *
	 * @return void
	 */
	public function testUpdateDossierConfigurationResetsNonArrayGrondslagenAndSelf(): void {
		$entity = new ObjectEntity();
		$entity->setObject(['configuration' => ['grondslagen' => 'stale'], '@self' => 'junk']);
		$entity->setFolder('f-1');

		$saved = null;
		$objectService = $this->createMock(ObjectService::class);
		$objectService->method('find')->willReturn($entity);
		$objectService->method('saveObject')->willReturnCallback(
			static function (array $object) use (&$saved) {
				$saved = $object;
				return null;
			}
		);
		$this->withObjectService($objectService);

		$this->repository()->updateDossierConfiguration('uuid-12', 8);

		$this->assertSame(['fileId', 'lastGeneratedAt'], array_keys($saved['configuration']['grondslagen']));
		$this->assertSame(['folder' => 'f-1'], $saved['@self']);

	}//end testUpdateDossierConfigurationResetsNonArrayGrondslagenAndSelf()

	/**
	 * An integer or empty entity folder is not re-injected.
	 *
	 * @return void
	 */
	public function testUpdateDossierConfigurationDoesNotInjectANonStringFolder(): void {
		$entity = new ObjectEntity();
		$entity->setObject(['name' => 'Y']);
		$entity->setFolder(55);

		$saved = null;
		$objectService = $this->createMock(ObjectService::class);
		$objectService->method('find')->willReturn($entity);
		$objectService->method('saveObject')->willReturnCallback(
			static function (array $object) use (&$saved) {
				$saved = $object;
				return null;
			}
		);
		$this->withObjectService($objectService);

		$this->repository()->updateDossierConfiguration('uuid-13', 2);

		$this->assertArrayNotHasKey('@self', $saved);

	}//end testUpdateDossierConfigurationDoesNotInjectANonStringFolder()

	/**
	 * A failing folder probe before the save is logged and the save still happens without @self.
	 *
	 * @return void
	 */
	public function testUpdateDossierConfigurationSavesWhenTheFolderProbeFails(): void {
		$entity = new class extends ObjectEntity {
			/**
			 * Always fails.
			 *
			 * @return int|string|null
			 */
			public function getFolder(): int|string|null {
				throw new RuntimeException('probe failed');
			}
		};
		$entity->setObject(['name' => 'Z']);

		$saved = null;
		$objectService = $this->createMock(ObjectService::class);
		$objectService->method('find')->willReturn($entity);
		$objectService->expects($this->once())->method('saveObject')->willReturnCallback(
			static function (array $object) use (&$saved) {
				$saved = $object;
				return null;
			}
		);
		$this->withObjectService($objectService);
		$this->logger->expects($this->once())->method('warning')->with(
			'LegalBasesSummaryService: could not read existing folder ref before save',
			['dossierUuid' => 'uuid-14', 'error' => 'probe failed']
		);

		$this->repository()->updateDossierConfiguration('uuid-14', 3);

		$this->assertSame('Z', $saved['name']);
		$this->assertArrayNotHasKey('@self', $saved);

	}//end testUpdateDossierConfigurationSavesWhenTheFolderProbeFails()

	/**
	 * A failing save is logged, not thrown.
	 *
	 * @return void
	 */
	public function testUpdateDossierConfigurationLogsASaveFailure(): void {
		$objectService = $this->createMock(ObjectService::class);
		$objectService->method('find')->willReturn(['name' => 'Q']);
		$objectService->method('saveObject')->willThrowException(new Exception('write refused'));
		$this->withObjectService($objectService);
		$this->logger->expects($this->once())->method('warning')->with(
			'LegalBasesSummaryService: failed to update dossier configuration.grondslagen',
			['dossierUuid' => 'uuid-15', 'error' => 'write refused']
		);

		$this->repository()->updateDossierConfiguration('uuid-15', 1);

	}//end testUpdateDossierConfigurationLogsASaveFailure()
}//end class
