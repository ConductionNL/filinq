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
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\DocuDesk\Tests\Unit\Service;

use OCA\DocuDesk\Service\BatchStateService;
use OCP\IAppConfig;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserSession;
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
 * @psalm-suppress  PropertyNotSetInConstructor
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
     * Mocked IUserSession
     *
     * @var IUserSession|MockObject
     */
    private IUserSession|MockObject $mockUserSession;

    /**
     * Mocked IGroupManager
     *
     * @var IGroupManager|MockObject
     */
    private IGroupManager|MockObject $mockGroupManager;

    /**
     * Set up test environment
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockCache        = $this->createMock(originalClassName: ICache::class);
        $this->mockAppConfig    = $this->createMock(originalClassName: IAppConfig::class);
        $this->mockLogger       = $this->createMock(originalClassName: LoggerInterface::class);
        $this->mockUserSession  = $this->createMock(originalClassName: IUserSession::class);
        $this->mockGroupManager = $this->createMock(originalClassName: IGroupManager::class);

        // Default: no user logged in (PHPUnit mock returns null for unconfigured methods).
        $mockCacheFactory = $this->createMock(originalClassName: ICacheFactory::class);
        $mockCacheFactory->method('createDistributed')
            ->with('docudesk')
            ->willReturn($this->mockCache);

        $this->service = new BatchStateService(
            cacheFactory: $mockCacheFactory,
            appConfig: $this->mockAppConfig,
            logger: $this->mockLogger,
            userSession: $this->mockUserSession,
            groupManager: $this->mockGroupManager
        );

    }//end setUp()

    /**
     * Test getMaxFiles returns configured value
     *
     * @return void
     */
    public function testGetMaxFilesReturnsConfiguredValue(): void
    {
        // Canonical key carries the admin override; legacy fallback never reached.
        $this->mockAppConfig->method('getValueString')
            ->willReturnMap(
                [
                    ['docudesk', 'batch.max_files_per_run', '', false, '50'],
                    ['docudesk', 'docudesk_batch_max_files', '100', false, '100'],
                ]
            );

        $result = $this->service->getMaxFiles();

        $this->assertSame(expected: 50, actual: $result);

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

        $this->assertSame(expected: 100, actual: $result);

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

        $this->assertSame(expected: 'uploading', actual: $result['status']);
        $this->assertSame(expected: 'user1', actual: $result['userId']);
        $this->assertCount(expectedCount: 1, haystack: $result['files']);
        $this->assertArrayHasKey(key: 'batchId', array: $result);
        $this->assertNotNull(actual: $storedJson);

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

        $this->assertNull(actual: $result);

    }//end testGetBatchReturnsNullOnCacheMiss()

    /**
     * Test getBatch returns decoded array on cache hit (owner accessing own batch)
     *
     * @return void
     */
    public function testGetBatchReturnsBatchArray(): void
    {
        $mockUser = $this->createMock(originalClassName: IUser::class);
        $mockUser->method('getUID')->willReturn('user1');
        $this->mockUserSession->method('getUser')->willReturn($mockUser);
        $this->mockGroupManager->method('isAdmin')->willReturn(false);

        $batch = ['batchId' => 'abc-123', 'userId' => 'user1', 'status' => 'uploading', 'files' => []];
        $this->mockCache->method('get')
            ->willReturn(json_encode($batch));

        $result = $this->service->getBatch(batchId: 'abc-123');

        $this->assertIsArray(actual: $result);
        $this->assertSame(expected: 'abc-123', actual: $result['batchId']);
        $this->assertSame(expected: 'uploading', actual: $result['status']);

    }//end testGetBatchReturnsBatchArray()

    /**
     * Test getBatch throws when a non-admin user accesses another user's batch (C2)
     *
     * @return void
     */
    /**
     * WF3 fix: getBatch returns null (not throws RuntimeException) for a
     * non-owner so the controller returns 404 for both "not found" and
     * "found-but-owned-by-another-user", preventing existence probing via
     * the 500-vs-404 response distinction.
     *
     * @return void
     */
    public function testGetBatchReturnsNullForForeignBatch(): void
    {
        $mockUser = $this->createMock(originalClassName: IUser::class);
        $mockUser->method('getUID')->willReturn('attacker');
        $this->mockUserSession->method('getUser')->willReturn($mockUser);
        $this->mockGroupManager->method('isAdmin')->willReturn(false);

        $batch = ['batchId' => 'abc-123', 'userId' => 'victim', 'status' => 'uploading', 'files' => []];
        $this->mockCache->method('get')->willReturn(json_encode($batch));

        // WF3: must return null, never throw (existence probing fix).
        $result = $this->service->getBatch(batchId: 'abc-123');
        $this->assertNull(actual: $result);

    }//end testGetBatchReturnsNullForForeignBatch()

    /**
     * Test getBatch allows admin to access any batch (C2 admin bypass)
     *
     * @return void
     */
    public function testGetBatchAllowsAdminToAccessForeignBatch(): void
    {
        $mockUser = $this->createMock(originalClassName: IUser::class);
        $mockUser->method('getUID')->willReturn('admin-user');
        $this->mockUserSession->method('getUser')->willReturn($mockUser);
        $this->mockGroupManager->method('isAdmin')->willReturn(true);

        $batch = ['batchId' => 'abc-123', 'userId' => 'other-user', 'status' => 'uploading', 'files' => []];
        $this->mockCache->method('get')->willReturn(json_encode($batch));

        // Admin bypass — should not throw.
        $result = $this->service->getBatch(batchId: 'abc-123');

        $this->assertIsArray(actual: $result);
        $this->assertSame(expected: 'abc-123', actual: $result['batchId']);

    }//end testGetBatchAllowsAdminToAccessForeignBatch()

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
            ->with($this->stringContains(string: 'abc-123'), $this->anything(), $this->anything());

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
            ->with($this->stringContains(string: 'abc-123'));

        $this->service->deleteBatch(batchId: 'abc-123');

    }//end testDeleteBatchCallsCacheRemove()
}//end class
