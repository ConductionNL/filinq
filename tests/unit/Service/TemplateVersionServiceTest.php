<?php

/**
 * Unit tests for TemplateVersionService
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2025 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/advanced-template-management/tasks.md#task-4
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use Exception;
use OCA\Filinq\Service\OpenRegisterResolver;
use OCA\Filinq\Service\TemplateService;
use OCA\Filinq\Service\TemplateVersionService;
use OCA\OpenRegister\Db\ObjectEntity;
use OCA\OpenRegister\Service\ObjectService;
use OCP\App\IAppManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;

/**
 * Unit tests for TemplateVersionService
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class TemplateVersionServiceTest extends TestCase {

	/**
	 * The TemplateVersionService instance under test
	 *
	 * @var TemplateVersionService
	 */
	private TemplateVersionService $versionService;

	/**
	 * Mock ContainerInterface
	 *
	 * @var ContainerInterface|MockObject
	 */
	private ContainerInterface|MockObject $mockContainer;

	/**
	 * Mock IAppManager
	 *
	 * @var IAppManager|MockObject
	 */
	private IAppManager|MockObject $mockAppManager;

	/**
	 * Mock OpenRegisterResolver
	 *
	 * @var OpenRegisterResolver|MockObject
	 */
	private OpenRegisterResolver|MockObject $mockResolver;

	/**
	 * Set up test environment
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->mockContainer = $this->createMock(ContainerInterface::class);
		$this->mockAppManager = $this->createMock(IAppManager::class);
		$this->mockResolver = $this->createMock(OpenRegisterResolver::class);

		// Default: no openregister installed.
		$this->mockAppManager->method('getInstalledApps')->willReturn([]);

		$this->versionService = new TemplateVersionService(
			$this->mockContainer,
			$this->mockAppManager,
			$this->mockResolver
		);

	}//end setUp()

	/**
	 * Helper: re-build service with OpenRegister available
	 *
	 * @param ObjectService|MockObject $mockObjectService Mock ObjectService
	 *
	 * @return void
	 */
	private function setUpWithOpenRegister(ObjectService|MockObject $mockObjectService): void {
		$this->mockAppManager = $this->createMock(IAppManager::class);
		$this->mockAppManager->method('getInstalledApps')->willReturn(['openregister']);

		$this->mockContainer = $this->createMock(ContainerInterface::class);
		$this->mockContainer->method('get')
			->with('OCA\OpenRegister\Service\ObjectService')
			->willReturn($mockObjectService);

		$this->versionService = new TemplateVersionService(
			$this->mockContainer,
			$this->mockAppManager,
			$this->mockResolver
		);

	}//end setUpWithOpenRegister()

	/**
	 * Test createVersion throws when OpenRegister is not available
	 *
	 * @return void
	 */
	public function testCreateVersionThrowsWhenOpenRegisterUnavailable(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('OpenRegister service is not available');

		$this->mockResolver->method('getVersionRegisterAndSchema')
			->willReturn(['register' => 'reg', 'schema' => 'schema']);

		$this->versionService->createVersion(
			templateId: 'tmpl-1',
			templateState: ['content' => '<p>Hi</p>', 'name' => 'T'],
			editor: 'admin'
		);

	}//end testCreateVersionThrowsWhenOpenRegisterUnavailable()

	/**
	 * Test createVersion saves version object and returns array
	 *
	 * @return void
	 */
	public function testCreateVersionSavesObjectAndReturnsArray(): void {
		$mockEntity = $this->createMock(ObjectEntity::class);
		$mockEntity->method('jsonSerialize')->willReturn(
			[
				'id' => 'ver-1',
				'templateId' => 'tmpl-1',
				'version' => 1,
				'editor' => 'admin',
			]
		);

		$mockObjectService = $this->createMock(ObjectService::class);
		$mockObjectService->method('buildSearchQuery')->willReturn([]);
		$mockObjectService->method('searchObjectsPaginated')
			->willReturn(['results' => [], 'total' => 0]);
		$mockObjectService->expects($this->once())
			->method('saveObject')
			->willReturn($mockEntity);

		$this->setUpWithOpenRegister($mockObjectService);

		$this->mockResolver->method('getVersionRegisterAndSchema')
			->willReturn(['register' => 'reg', 'schema' => 'templateVersion']);

		$result = $this->versionService->createVersion(
			templateId: 'tmpl-1',
			templateState: [
				'content' => '<p>Hello</p>',
				'name' => 'Test Template',
				'description' => 'Desc',
				'format' => 'A4',
				'orientation' => 'P',
			],
			editor: 'admin',
			changelog: 'Initial version'
		);

		$this->assertIsArray($result);
		$this->assertEquals('ver-1', $result['id']);
		$this->assertEquals(1, $result['version']);

	}//end testCreateVersionSavesObjectAndReturnsArray()

	/**
	 * Test getVersions returns paginated result
	 *
	 * @return void
	 */
	public function testGetVersionsReturnsPaginatedResult(): void {
		$mockObjectService = $this->createMock(ObjectService::class);
		$mockObjectService->method('buildSearchQuery')->willReturn([]);
		$mockObjectService->method('searchObjectsPaginated')
			->willReturn(
				[
					'results' => [
						['id' => 'ver-2', 'version' => 2],
						['id' => 'ver-1', 'version' => 1],
					],
					'total' => 2,
				]
			);

		$this->setUpWithOpenRegister($mockObjectService);

		$this->mockResolver->method('getVersionRegisterAndSchema')
			->willReturn(['register' => 'reg', 'schema' => 'templateVersion']);

		$result = $this->versionService->getVersions(templateId: 'tmpl-1');

		$this->assertIsArray($result);
		$this->assertEquals(2, $result['total']);
		$this->assertCount(2, $result['results']);

	}//end testGetVersionsReturnsPaginatedResult()

	/**
	 * Test getVersion throws when version not found
	 *
	 * @return void
	 */
	public function testGetVersionThrowsWhenNotFound(): void {
		$this->expectException(Exception::class);
		$this->expectExceptionMessage('Version not found');

		$mockObjectService = $this->createMock(ObjectService::class);
		$mockObjectService->method('find')->willReturn(null);

		$this->setUpWithOpenRegister($mockObjectService);

		$this->mockResolver->method('getVersionRegisterAndSchema')
			->willReturn(['register' => 'reg', 'schema' => 'templateVersion']);

		$this->versionService->getVersion(versionId: 'nonexistent');

	}//end testGetVersionThrowsWhenNotFound()

	/**
	 * Test getVersion returns array when found
	 *
	 * @return void
	 */
	public function testGetVersionReturnsArrayWhenFound(): void {
		$mockEntity = $this->createMock(ObjectEntity::class);
		$mockEntity->method('jsonSerialize')->willReturn(
			[
				'id' => 'ver-1',
				'version' => 1,
				'content' => '<p>v1</p>',
			]
		);

		$mockObjectService = $this->createMock(ObjectService::class);
		$mockObjectService->method('find')->willReturn($mockEntity);

		$this->setUpWithOpenRegister($mockObjectService);

		$this->mockResolver->method('getVersionRegisterAndSchema')
			->willReturn(['register' => 'reg', 'schema' => 'templateVersion']);

		$result = $this->versionService->getVersion(versionId: 'ver-1');

		$this->assertIsArray($result);
		$this->assertEquals('ver-1', $result['id']);

	}//end testGetVersionReturnsArrayWhenFound()

	/**
	 * Test getNextVersionNumber returns count + 1
	 *
	 * @return void
	 */
	public function testGetNextVersionNumberReturnsCountPlusOne(): void {
		$mockObjectService = $this->createMock(ObjectService::class);
		$mockObjectService->method('buildSearchQuery')->willReturn([]);
		$mockObjectService->method('searchObjectsPaginated')
			->willReturn(['results' => [], 'total' => 3]);

		$this->setUpWithOpenRegister($mockObjectService);

		$this->mockResolver->method('getVersionRegisterAndSchema')
			->willReturn(['register' => 'reg', 'schema' => 'templateVersion']);

		$result = $this->versionService->getNextVersionNumber(templateId: 'tmpl-1');

		$this->assertEquals(4, $result);

	}//end testGetNextVersionNumberReturnsCountPlusOne()

	/**
	 * Test getDiff returns both versions
	 *
	 * @return void
	 */
	public function testGetDiffReturnsBothVersions(): void {
		$entityFrom = $this->createMock(ObjectEntity::class);
		$entityFrom->method('jsonSerialize')
			->willReturn(['id' => 'ver-1', 'version' => 1, 'content' => '<p>v1</p>']);

		$entityTo = $this->createMock(ObjectEntity::class);
		$entityTo->method('jsonSerialize')
			->willReturn(['id' => 'ver-2', 'version' => 2, 'content' => '<p>v2</p>']);

		$mockObjectService = $this->createMock(ObjectService::class);
		$mockObjectService->expects($this->exactly(2))
			->method('find')
			->willReturnOnConsecutiveCalls($entityFrom, $entityTo);

		$this->setUpWithOpenRegister($mockObjectService);

		$this->mockResolver->method('getVersionRegisterAndSchema')
			->willReturn(['register' => 'reg', 'schema' => 'templateVersion']);

		$result = $this->versionService->getDiff(
			versionIdFrom: 'ver-1',
			versionIdTo: 'ver-2'
		);

		$this->assertArrayHasKey('from', $result);
		$this->assertArrayHasKey('to', $result);
		$this->assertEquals('ver-1', $result['from']['id']);
		$this->assertEquals('ver-2', $result['to']['id']);

	}//end testGetDiffReturnsBothVersions()

	/**
	 * Test restoreVersion creates snapshot and updates template
	 *
	 * @return void
	 */
	public function testRestoreVersionCreatesSnapshotAndUpdatesTemplate(): void {
		$currentState = [
			'id' => 'tmpl-1',
			'content' => '<p>current</p>',
			'name' => 'Current Name',
		];

		$targetVersionData = [
			'id' => 'ver-2',
			'templateId' => 'tmpl-1',
			'version' => 2,
			'content' => '<p>v2 content</p>',
			'name' => 'Old Name',
			'description' => '',
			'format' => 'A4',
			'orientation' => 'P',
		];

		$restoredTemplate = array_merge(
			$currentState,
			[
				'content' => $targetVersionData['content'],
				'name' => $targetVersionData['name'],
			]
		);

		$targetEntity = $this->createMock(ObjectEntity::class);
		$targetEntity->method('jsonSerialize')->willReturn($targetVersionData);

		$snapshotEntity = $this->createMock(ObjectEntity::class);
		$snapshotEntity->method('jsonSerialize')
			->willReturn(['id' => 'ver-3', 'version' => 3]);

		$mockObjectService = $this->createMock(ObjectService::class);
		// First call: find target version; second call: paginated count for next version number.
		$mockObjectService->method('find')->willReturn($targetEntity);
		$mockObjectService->method('buildSearchQuery')->willReturn([]);
		$mockObjectService->method('searchObjectsPaginated')
			->willReturn(['results' => [], 'total' => 2]);
		$mockObjectService->method('saveObject')->willReturn($snapshotEntity);

		$this->setUpWithOpenRegister($mockObjectService);

		$this->mockResolver->method('getVersionRegisterAndSchema')
			->willReturn(['register' => 'reg', 'schema' => 'templateVersion']);

		$mockTemplateService = $this->createMock(TemplateService::class);
		$mockTemplateService->method('getTemplate')->willReturn($currentState);
		$mockTemplateService->method('updateTemplateWithoutVersion')
			->willReturn($restoredTemplate);

		$result = $this->versionService->restoreVersion(
			templateId: 'tmpl-1',
			versionId: 'ver-2',
			editor: 'admin',
			service: $mockTemplateService
		);

		$this->assertIsArray($result);
		$this->assertEquals('<p>v2 content</p>', $result['content']);

	}//end testRestoreVersionCreatesSnapshotAndUpdatesTemplate()

}//end class
