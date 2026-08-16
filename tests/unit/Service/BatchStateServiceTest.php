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

use OCA\DocuDesk\Service\BatchStateRepository;
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
use RuntimeException;

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
class BatchStateServiceTest extends TestCase {

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
	 * Mocked BatchStateRepository — the OpenRegister store of record.
	 *
	 * Left as a bare mock here on purpose: this class covers the cache-facing
	 * behaviour of BatchStateService, so every test either hits the cache or
	 * expects a miss, and the store answers null. The store-backed behaviour is
	 * covered with real fakes in BatchStateServicePersistenceTest.
	 *
	 * @var BatchStateRepository|MockObject
	 */
	private BatchStateRepository|MockObject $mockRepository;

	/**
	 * Set up test environment
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->mockRepository = $this->createMock(originalClassName: BatchStateRepository::class);
		$this->mockCache = $this->createMock(originalClassName: ICache::class);
		$this->mockAppConfig = $this->createMock(originalClassName: IAppConfig::class);
		$this->mockLogger = $this->createMock(originalClassName: LoggerInterface::class);
		$this->mockUserSession = $this->createMock(originalClassName: IUserSession::class);
		$this->mockGroupManager = $this->createMock(originalClassName: IGroupManager::class);

		$mockCacheFactory = $this->createMock(originalClassName: ICacheFactory::class);
		$mockCacheFactory->method('createDistributed')
			->with('docudesk')
			->willReturn($this->mockCache);

		$this->service = new BatchStateService(
			cacheFactory: $mockCacheFactory,
			appConfig: $this->mockAppConfig,
			logger: $this->mockLogger,
			userSession: $this->mockUserSession,
			groupManager: $this->mockGroupManager,
			repository: $this->mockRepository
		);

	}//end setUp()

	/**
	 * Test getMaxFiles returns configured value
	 *
	 * @return void
	 */
	public function testGetMaxFilesReturnsConfiguredValue(): void {
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
	public function testGetMaxFilesReturnsDefault(): void {
		$this->mockAppConfig->method('getValueString')
			->willReturn('100');

		$result = $this->service->getMaxFiles();

		$this->assertSame(expected: 100, actual: $result);

	}//end testGetMaxFilesReturnsDefault()

	/**
	 * The legacy key still wins when the canonical one is unset.
	 *
	 * `batch.max_files_per_run` is the manifest-declared key; the legacy
	 * `docudesk_batch_max_files` is kept as a one-release fallback so an admin
	 * who set the old key before the rename does not silently get the built-in
	 * default instead of their own limit. That fallback is only reachable when
	 * the canonical key reads empty, which is why the two tests above never
	 * enter it — and a fallback nothing exercises is a fallback nobody would
	 * notice losing.
	 *
	 * @return void
	 */
	public function testGetMaxFilesFallsBackToTheLegacyKeyWhenTheCanonicalOneIsUnset(): void {
		$this->mockAppConfig->method('getValueString')
			->willReturnMap(
				[
					['docudesk', 'batch.max_files_per_run', '', false, ''],
					['docudesk', 'docudesk_batch_max_files', '100', false, '250'],
				]
			);

		$this->assertSame(
			expected: 250,
			actual: $this->service->getMaxFiles(),
			message: 'An admin-set legacy limit was ignored in favour of the built-in default.'
		);

	}//end testGetMaxFilesFallsBackToTheLegacyKeyWhenTheCanonicalOneIsUnset()

	/**
	 * With neither key set, the in-class default is what app-config is asked
	 * for and what comes back.
	 *
	 * @return void
	 */
	public function testGetMaxFilesUsesTheInClassDefaultWhenNeitherKeyIsSet(): void {
		$this->mockAppConfig->method('getValueString')
			->willReturnMap(
				[
					['docudesk', 'batch.max_files_per_run', '', false, ''],
					['docudesk', 'docudesk_batch_max_files', '100', false, '100'],
				]
			);

		$this->assertSame(expected: 100, actual: $this->service->getMaxFiles());

	}//end testGetMaxFilesUsesTheInClassDefaultWhenNeitherKeyIsSet()

	/**
	 * Test createBatch stores a batch in cache with uploading status
	 *
	 * @return void
	 */
	public function testCreateBatchStoresBatchWithUploadingStatus(): void {
		$storedJson = null;

		$this->mockCache->expects($this->once())
			->method('set')
			->willReturnCallback(
				function (string $key, string $value, int $ttl) use (&$storedJson): bool {
					$storedJson = $value;
					return true;
				}
			);

		$files = [['fileId' => 1, 'fileName' => 'test.pdf', 'status' => 'uploaded']];
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
	public function testGetBatchReturnsNullOnCacheMiss(): void {
		$this->mockCache->method('get')->willReturn(null);

		$result = $this->service->getBatch(batchId: 'non-existent');

		$this->assertNull(actual: $result);

	}//end testGetBatchReturnsNullOnCacheMiss()

	/**
	 * Test getBatch returns decoded array on cache hit (owner accessing own batch)
	 *
	 * @return void
	 */
	public function testGetBatchReturnsBatchArray(): void {
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
	 * Test getBatch returns null when a non-admin user accesses another user's batch (C2/WF3).
	 *
	 * Per WF3 security fix, getBatch returns null (not RuntimeException) for
	 * access-denied so callers see a uniform 404 for both "not found" and
	 * "found-but-denied" — throwing produced a distinct 500 that confirmed existence.
	 *
	 * @return void
	 */
	public function testGetBatchThrowsForForeignBatch(): void {
		$mockUser = $this->createMock(originalClassName: IUser::class);
		$mockUser->method('getUID')->willReturn('attacker');
		$this->mockUserSession->method('getUser')->willReturn($mockUser);
		$this->mockGroupManager->method('isAdmin')->willReturn(false);

		$batch = ['batchId' => 'abc-123', 'userId' => 'victim', 'status' => 'uploading', 'files' => []];
		$this->mockCache->method('get')->willReturn(json_encode($batch));

		$result = $this->service->getBatch(batchId: 'abc-123');

		$this->assertNull(actual: $result);

	}//end testGetBatchThrowsForForeignBatch()

	/**
	 * Test getBatch allows admin to access any batch (C2 admin bypass)
	 *
	 * @return void
	 */
	public function testGetBatchAllowsAdminToAccessForeignBatch(): void {
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
	public function testUpdateBatchCallsCacheSet(): void {
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
	public function testDeleteBatchCallsCacheRemove(): void {
		$this->mockCache->expects($this->once())
			->method('remove')
			->with($this->stringContains(string: 'abc-123'));

		$this->service->deleteBatch(batchId: 'abc-123');

	}//end testDeleteBatchCallsCacheRemove()

	/**
	 * Test getBatch returns null when the cached payload is not decodable as an array.
	 *
	 * A distributed cache entry can be truncated, half-written or written by an
	 * older release, and `json_decode()` then yields a string, a scalar or null
	 * rather than the batch record. Returning that to a caller would hand the
	 * batch endpoints something they index as an array, and the keep-alive write
	 * at the end of the method would refresh the corrupt entry's TTL for as long
	 * as anything kept polling it.
	 *
	 * ⚠️ THERE IS NO SESSION USER IN THIS TEST, AND THAT IS THE WHOLE POINT.
	 * The first version of this test stubbed a session user, and it PASSED with
	 * the `is_array()` guard deleted — because a decoded scalar has no
	 * `userId`, so the ownership check below refused it and returned null for a
	 * completely different reason, and `assertNull` cannot tell the two nulls
	 * apart. With no session user the ownership block is skipped entirely, so
	 * the corrupt guard is the only thing left that can return null. Verified
	 * both ways: with the guard disabled this test fails with
	 * `ICache::set('docudesk_batch_abc-123', '"not-a-batch-record"', 7200):
	 * mixed was not expected to be called` — i.e. it prints the corrupt value
	 * being written back.
	 *
	 * @return void
	 */
	public function testGetBatchReturnsNullOnCorruptCachePayload(): void {
		$this->mockUserSession->method('getUser')->willReturn(null);

		// Valid JSON, but a scalar — exactly what a truncated or legacy entry
		// decodes to. A non-JSON string exercises the same branch.
		$this->mockCache->method('get')->willReturn('"not-a-batch-record"');

		// The keep-alive write must NOT happen for a corrupt entry.
		$this->mockCache->expects($this->never())->method('set');

		$result = $this->service->getBatch(batchId: 'abc-123');

		$this->assertNull(actual: $result);

	}//end testGetBatchReturnsNullOnCorruptCachePayload()

	/**
	 * Test the configured cache TTL is the one actually written to the cache.
	 *
	 * `batch.cache_ttl_seconds` is the canonical manifest-declared key. A config
	 * key that is declared and documented but read nowhere is a defect this
	 * codebase has paid for before, so this asserts the value reaches
	 * `ICache::set()` rather than asserting that the getter was called: the
	 * in-class `CACHE_TTL` default is 7200, so a service that ignored the
	 * override would still write a plausible-looking number here. Verified by
	 * disabling the override branch — the test then reports
	 * "Failed asserting that 7200 is identical to 900".
	 *
	 * @return void
	 */
	public function testConfiguredCacheTtlIsUsedWhenSet(): void {
		$this->mockAppConfig->method('getValueString')
			->willReturnMap(
				[
					['docudesk', 'batch.cache_ttl_seconds', '', false, '900'],
				]
			);

		$writtenTtl = null;
		$this->mockCache->expects($this->once())
			->method('set')
			->willReturnCallback(
				function (string $key, string $value, int $ttl) use (&$writtenTtl): bool {
					$writtenTtl = $ttl;
					return true;
				}
			);

		$this->service->createBatch(userId: 'user1', files: []);

		$this->assertSame(
			expected: 900,
			actual: $writtenTtl,
			message: 'The configured batch.cache_ttl_seconds did not reach ICache::set(); '
				. 'a value of 7200 means the in-class CACHE_TTL default was written instead.'
		);

	}//end testConfiguredCacheTtlIsUsedWhenSet()
}//end class
