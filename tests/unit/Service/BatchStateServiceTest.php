<?php

/**
 * Unit tests for BatchStateService
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2025 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.DocuDesk.app
 */

namespace OCA\DocuDesk\Tests\Unit\Service;

use OCA\DocuDesk\Service\BatchStateService;
use OCP\IAppConfig;
use OCP\ICache;
use OCP\ICacheFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for BatchStateService
 *
 * Tests cover batch creation, retrieval, update, and deletion via a
 * mocked ICache. UUID format and TTL are not verified (they rely on
 * PHP internals), but the cache interaction contract is fully tested.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 * @phpstan-extends TestCase
 */
class BatchStateServiceTest extends TestCase
{

    /**
     * The BatchStateService instance under test
     *
     * @var BatchStateService
     */
    private BatchStateService $service;

    /**
     * Mocked ICache instance
     *
     * @var ICache|MockObject
     */
    private ICache|MockObject $mockCache;

    /**
     * Mocked IAppConfig
     *
     * @var IAppConfig|MockObject
     */
    private IAppConfig|MockObject $mockAppConfig;

    /**
     * Mocked LoggerInterface
     *
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface|MockObject $mockLogger;


    /**
     * Set up test environment
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockCache     = $this->createMock(ICache::class);
        $this->mockAppConfig = $this->createMock(IAppConfig::class);
        $this->mockLogger    = $this->createMock(LoggerInterface::class);

        $mockCacheFactory = $this->createMock(ICacheFactory::class);
        $mockCacheFactory->method('createDistributed')
            ->with('docudesk')
            ->willReturn($this->mockCache);

        $this->service = new BatchStateService(
            $mockCacheFactory,
            $this->mockAppConfig,
            $this->mockLogger
        );

    }//end setUp()


    /**
     * Test getMaxFiles returns configured value
     *
     * @return void
     */
    public function testGetMaxFilesReturnsConfiguredValue(): void
    {
        $this->mockAppConfig->method('getValueString')
            ->with('docudesk', 'docudesk_batch_max_files', '100')
            ->willReturn('50');

        $result = $this->service->getMaxFiles();

        $this->assertSame(50, $result);

    }//end testGetMaxFilesReturnsConfiguredValue()


    /**
     * Test getMaxFiles returns default 100 when not configured
     *
     * @return void
     */
    public function testGetMaxFilesReturnsDefault(): void
    {
        $this->mockAppConfig->method('getValueString')
            ->willReturn('100');

        $result = $this->service->getMaxFiles();

        $this->assertSame(100, $result);

    }//end testGetMaxFilesReturnsDefault()


    /**
     * Test createBatch stores a batch in cache with uploading status
     *
     * @return void
     */
    public function testCreateBatchStoresBatchWithUploadingStatus(): void
    {
        $storedJson = null;

        $this->mockCache->expects($this->once())
            ->method('set')
            ->willReturnCallback(
                function (string $key, string $value, int $ttl) use (&$storedJson): void {
                    $storedJson = $value;
                }
            );

        $files  = [['fileId' => 1, 'fileName' => 'test.pdf', 'status' => 'uploaded']];
        $result = $this->service->createBatch(userId: 'user1', files: $files);

        $this->assertSame('uploading', $result['status']);
        $this->assertSame('user1', $result['userId']);
        $this->assertCount(1, $result['files']);
        $this->assertArrayHasKey('batchId', $result);
        $this->assertNotNull($storedJson);

    }//end testCreateBatchStoresBatchWithUploadingStatus()


    /**
     * Test getBatch returns null when cache miss
     *
     * @return void
     */
    public function testGetBatchReturnsNullOnCacheMiss(): void
    {
        $this->mockCache->method('get')->willReturn(null);

        $result = $this->service->getBatch(batchId: 'non-existent');

        $this->assertNull($result);

    }//end testGetBatchReturnsNullOnCacheMiss()


    /**
     * Test getBatch returns decoded array on cache hit
     *
     * @return void
     */
    public function testGetBatchReturnsBatchArray(): void
    {
        $batch = ['batchId' => 'abc-123', 'status' => 'uploading', 'files' => []];
        $this->mockCache->method('get')
            ->willReturn(json_encode($batch));

        $result = $this->service->getBatch(batchId: 'abc-123');

        $this->assertIsArray($result);
        $this->assertSame('abc-123', $result['batchId']);
        $this->assertSame('uploading', $result['status']);

    }//end testGetBatchReturnsBatchArray()


    /**
     * Test updateBatch calls cache set with updated batch data
     *
     * @return void
     */
    public function testUpdateBatchCallsCacheSet(): void
    {
        $batch = ['batchId' => 'abc-123', 'status' => 'extracting', 'files' => []];

        $this->mockCache->expects($this->once())
            ->method('set')
            ->with($this->stringContains('abc-123'), $this->anything(), $this->anything());

        $this->service->updateBatch(batchId: 'abc-123', batch: $batch);

    }//end testUpdateBatchCallsCacheSet()


    /**
     * Test deleteBatch calls cache remove with correct key
     *
     * @return void
     */
    public function testDeleteBatchCallsCacheRemove(): void
    {
        $this->mockCache->expects($this->once())
            ->method('remove')
            ->with($this->stringContains('abc-123'));

        $this->service->deleteBatch(batchId: 'abc-123');

    }//end testDeleteBatchCallsCacheRemove()


}//end class
