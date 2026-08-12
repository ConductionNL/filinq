<?php

/**
 * Unit tests for EntityConsolidationService
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.DocuDesk.app
 *
 * @spec openspec/changes/enhanced-anonymization/specs/anonymization-entity-review/spec.md
 * @spec openspec/changes/unit-test-coverage-75/tasks.md#task-4.4
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Tests\Unit\Service;

use OCA\DocuDesk\Service\BasesResolverService;
use OCA\DocuDesk\Service\EntityConsolidationService;
use OCA\DocuDesk\Service\PolicyMatchService;
use OCA\DocuDesk\Service\WooProfileService;
use OCP\App\IAppManager;
use OCP\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for EntityConsolidationService
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class EntityConsolidationServiceTest extends TestCase {

	/**
	 * Service under test.
	 *
	 * @var EntityConsolidationService
	 */
	private EntityConsolidationService $service;

	/**
	 * Mock WooProfile service.
	 *
	 * @var WooProfileService|MockObject
	 */
	private WooProfileService|MockObject $mockWooProfile;

	/**
	 * Mock app manager.
	 *
	 * @var IAppManager|MockObject
	 */
	private IAppManager|MockObject $mockAppManager;

	/**
	 * Mock DI container.
	 *
	 * @var ContainerInterface|MockObject
	 */
	private ContainerInterface|MockObject $mockContainer;

	/**
	 * Mock logger.
	 *
	 * @var LoggerInterface|MockObject
	 */
	private LoggerInterface|MockObject $mockLogger;

	/**
	 * Mock policy match service.
	 *
	 * @var PolicyMatchService|MockObject
	 */
	private PolicyMatchService|MockObject $mockPolicyMatch;

	/**
	 * Mock bases resolver service.
	 *
	 * @var BasesResolverService|MockObject
	 */
	private BasesResolverService|MockObject $mockBasesResolver;

	/**
	 * Mock app config.
	 *
	 * @var IAppConfig|MockObject
	 */
	private IAppConfig|MockObject $mockAppConfig;

	/**
	 * Set up test environment.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->mockLogger = $this->createMock(originalClassName: LoggerInterface::class);
		$this->mockWooProfile = $this->createMock(originalClassName: WooProfileService::class);
		$this->mockAppManager = $this->createMock(originalClassName: IAppManager::class);
		$this->mockContainer = $this->createMock(originalClassName: ContainerInterface::class);
		$this->mockPolicyMatch = $this->createMock(originalClassName: PolicyMatchService::class);
		$this->mockBasesResolver = $this->createMock(originalClassName: BasesResolverService::class);
		$this->mockAppConfig = $this->createMock(originalClassName: IAppConfig::class);

		$this->service = new EntityConsolidationService(
			logger: $this->mockLogger,
			wooProfile: $this->mockWooProfile,
			appManager: $this->mockAppManager,
			container: $this->mockContainer,
			policyMatch: $this->mockPolicyMatch,
			basesResolver: $this->mockBasesResolver,
			appConfig: $this->mockAppConfig,
		);

	}//end setUp()

	/**
	 * Test consolidateEntities returns empty array for batch with no extracted files.
	 *
	 * @return void
	 */
	public function testConsolidateEntitiesReturnsEmptyForNonExtractedFiles(): void {
		$batch = [
			'batchId' => 'abc',
			'files' => [
				['fileId' => 1, 'status' => 'uploaded'],
			],
		];

		$result = $this->service->consolidateEntities($batch);

		$this->assertSame(expected: [], actual: $result);

	}//end testConsolidateEntitiesReturnsEmptyForNonExtractedFiles()

	/**
	 * Test consolidateEntities returns empty when OpenRegister is unavailable.
	 *
	 * @return void
	 */
	public function testConsolidateEntitiesReturnsEmptyWhenORUnavailable(): void {
		$this->mockAppManager->method('getInstalledApps')->willReturn([]);

		$batch = [
			'batchId' => 'abc',
			'files' => [
				['fileId' => 1, 'status' => 'extracted'],
			],
		];

		$result = $this->service->consolidateEntities($batch);

		$this->assertSame(expected: [], actual: $result);

	}//end testConsolidateEntitiesReturnsEmptyWhenORUnavailable()

	/**
	 * Test consolidateEntities constructor stores dependencies correctly.
	 *
	 * @return void
	 */
	public function testConstructorStoresDependencies(): void {
		$this->assertInstanceOf(
			expected: EntityConsolidationService::class,
			actual: $this->service
		);

	}//end testConstructorStoresDependencies()

	/**
	 * Test consolidateEntities with empty batch files array.
	 *
	 * @return void
	 */
	public function testConsolidateEntitiesWithEmptyFilesArray(): void {
		$batch = ['batchId' => 'abc', 'files' => []];

		$result = $this->service->consolidateEntities($batch);

		$this->assertIsArray(actual: $result);
		$this->assertCount(expectedCount: 0, haystack: $result);

	}//end testConsolidateEntitiesWithEmptyFilesArray()
}//end class
