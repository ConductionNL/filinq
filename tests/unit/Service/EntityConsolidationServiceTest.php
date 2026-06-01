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
 * @spec openspec/changes/unit-test-coverage-75/tasks.md#task-4.4
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Tests\Unit\Service;

use OCA\DocuDesk\Service\EntityConsolidationService;
use OCA\DocuDesk\Service\WooProfileService;
use OCP\App\IAppManager;
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
class EntityConsolidationServiceTest extends TestCase
{

    /**
     * @var EntityConsolidationService
     */
    private EntityConsolidationService $service;

    /**
     * @var WooProfileService|MockObject
     */
    private WooProfileService|MockObject $mockWooProfile;

    /**
     * @var IAppManager|MockObject
     */
    private IAppManager|MockObject $mockAppManager;

    /**
     * @var ContainerInterface|MockObject
     */
    private ContainerInterface|MockObject $mockContainer;

    /**
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface|MockObject $mockLogger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockLogger     = $this->createMock(LoggerInterface::class);
        $this->mockWooProfile = $this->createMock(WooProfileService::class);
        $this->mockAppManager = $this->createMock(IAppManager::class);
        $this->mockContainer  = $this->createMock(ContainerInterface::class);

        $this->service = new EntityConsolidationService(
            logger: $this->mockLogger,
            wooProfile: $this->mockWooProfile,
            appManager: $this->mockAppManager,
            container: $this->mockContainer,
        );

    }//end setUp()

    /**
     * Test consolidateEntities returns empty array for batch with no extracted files.
     *
     * @return void
     */
    public function testConsolidateEntitiesReturnsEmptyForNonExtractedFiles(): void
    {
        $batch = [
            'batchId' => 'abc',
            'files'   => [
                ['fileId' => 1, 'status' => 'uploaded'],
            ],
        ];

        $result = $this->service->consolidateEntities($batch);

        $this->assertSame([], $result);

    }//end testConsolidateEntitiesReturnsEmptyForNonExtractedFiles()

    /**
     * Test consolidateEntities returns empty when OpenRegister is unavailable.
     *
     * @return void
     */
    public function testConsolidateEntitiesReturnsEmptyWhenORUnavailable(): void
    {
        $this->mockAppManager->method('isEnabledForUser')->willReturn(false);

        $batch = [
            'batchId' => 'abc',
            'files'   => [
                ['fileId' => 1, 'status' => 'extracted'],
            ],
        ];

        $result = $this->service->consolidateEntities($batch);

        $this->assertSame([], $result);

    }//end testConsolidateEntitiesReturnsEmptyWhenORUnavailable()

    /**
     * Test consolidateEntities constructor stores dependencies correctly.
     *
     * @return void
     */
    public function testConstructorStoresDependencies(): void
    {
        $this->assertInstanceOf(EntityConsolidationService::class, $this->service);

    }//end testConstructorStoresDependencies()

    /**
     * Test consolidateEntities with empty batch files array.
     *
     * @return void
     */
    public function testConsolidateEntitiesWithEmptyFilesArray(): void
    {
        $batch = ['batchId' => 'abc', 'files' => []];

        $result = $this->service->consolidateEntities($batch);

        $this->assertIsArray($result);
        $this->assertCount(0, $result);

    }//end testConsolidateEntitiesWithEmptyFilesArray()
}//end class
