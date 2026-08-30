<?php

/**
 * Regression tests for batch state surviving a request boundary
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\BatchStateRepository;
use OCA\Filinq\Service\BatchStateService;
use OCA\Filinq\Service\OpenRegisterAvailabilityService;
use OCP\IAppConfig;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Batch state must survive the end of the request that created it.
 *
 * ⚠️ WHAT THIS CLASS EXISTS TO PREVENT.
 * `ICacheFactory::createDistributed()` falls back to the LOCAL cache class, and
 * that defaults to `OC\Memcache\NullCache` whenever `memcache.local` is unset —
 * every Nextcloud that has not been explicitly pointed at APCu/Redis/Memcached,
 * including a stock `occ maintenance:install`. NullCache discards writes and
 * returns null for reads, so a cache-only batch store lost the record the
 * instant the creating request ended: `POST /api/anonymization/batch/folder`
 * answered 200 with a batchId and the next call on that batchId answered 404
 * `Batch not found or expired` (CI run 31963162253).
 *
 * Every test here therefore runs against {@see NullCacheFake} — the honest
 * reproduction of that cache — and the first test asserts the fake really does
 * discard, so a fixture that quietly started storing would fail loudly instead
 * of turning the whole class into a tautology.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class BatchStateServicePersistenceTest extends TestCase {

	/**
	 * The shared OpenRegister store — one instance across every "request" so it
	 * plays the role the database plays in production.
	 *
	 * @var InMemoryObjectServiceFake
	 */
	private InMemoryObjectServiceFake $store;

	/**
	 * Set up the shared store.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->store = new InMemoryObjectServiceFake();
	}//end setUp()

	/**
	 * Build a BatchStateService as one HTTP request would see it.
	 *
	 * A fresh service instance per call is the point: it models a new PHP
	 * process with nothing carried over except the cache and the store.
	 *
	 * @param ICache $cache The cache this "request" gets.
	 * @param string $uid The signed-in user, or '' for no session user.
	 * @param bool $openRegisterAvailable Whether OpenRegister is installed.
	 *
	 * @return BatchStateService The service.
	 */
	private function serviceForRequest(
		ICache $cache,
		string $uid = 'alice',
		bool $openRegisterAvailable = true,
	): BatchStateService {
		$cacheFactory = $this->createMock(ICacheFactory::class);
		$cacheFactory->method('createDistributed')->willReturn($cache);

		$userSession = $this->createMock(IUserSession::class);
		if ($uid !== '') {
			$user = $this->createMock(IUser::class);
			$user->method('getUID')->willReturn($uid);
			$userSession->method('getUser')->willReturn($user);
		} else {
			$userSession->method('getUser')->willReturn(null);
		}

		$groupManager = $this->createMock(IGroupManager::class);
		$groupManager->method('isAdmin')->willReturn(false);

		$availability = $this->createMock(OpenRegisterAvailabilityService::class);
		$availability->method('isInstalled')->willReturn($openRegisterAvailable);
		$availability->method('getObjectService')->willReturn($this->store);

		$repository = new BatchStateRepository(
			openRegister: $availability,
			logger: $this->createMock(LoggerInterface::class)
		);

		return new BatchStateService(
			cacheFactory: $cacheFactory,
			appConfig: $this->createMock(IAppConfig::class),
			logger: $this->createMock(LoggerInterface::class),
			userSession: $userSession,
			groupManager: $groupManager,
			repository: $repository
		);
	}//end serviceForRequest()

	/**
	 * A batch created in one request is readable in the next, with NO
	 * distributed cache configured.
	 *
	 * This is the failing E2E reduced to one test. Before batch state was
	 * persisted to OpenRegister this returned null and the endpoint answered
	 * 404.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-status-endpoint
	 */
	public function testBatchSurvivesTheRequestThatCreatedItWithoutADistributedCache(): void {
		$cache = new NullCacheFake();

		// Positive control on the FIXTURE first: prove this cache really does
		// discard. A fake that quietly stored would make every assertion below
		// pass for the wrong reason.
		$cache->set('probe', 'value', 60);
		$this->assertNull(
			$cache->get('probe'),
			'The NullCache fake stored a value; it no longer reproduces the cache '
			. 'Nextcloud hands out when memcache.local is unset, and this whole '
			. 'test class would be vacuous.'
		);

		$files = [
			['fileId' => 11, 'fileName' => 'zaak-a.txt', 'status' => 'uploaded', 'entityCount' => 0],
			['fileId' => 12, 'fileName' => 'zaak-b.txt', 'status' => 'uploaded', 'entityCount' => 0],
		];

		// Request 1 — POST /api/anonymization/batch/folder
		$created = $this->serviceForRequest(cache: $cache)->createBatch(userId: 'alice', files: $files);
		$batchId = $created['batchId'];

		// Request 2 — POST /api/anonymization/batch/{batchId}/extract
		$loaded = $this->serviceForRequest(cache: $cache)->getBatch(batchId: $batchId);

		$this->assertIsArray(
			$loaded,
			'The batch was not found in the next request. That is the exact 404 the '
			. 'entity-review E2E spec reported: batch state must not live only in a cache.'
		);
		$this->assertSame($batchId, $loaded['batchId']);
		$this->assertSame('alice', $loaded['userId']);
		$this->assertSame('uploading', $loaded['status']);
		$this->assertCount(2, $loaded['files']);
		$this->assertSame(11, $loaded['files'][0]['fileId']);
		$this->assertSame('zaak-b.txt', $loaded['files'][1]['fileName']);

		// The cache was still written to on both paths; it simply threw it away.
		$this->assertGreaterThan(
			0,
			$cache->discardedWrites,
			'The cache write path was never exercised, so this test is not covering '
			. 'the cache-plus-store arrangement it claims to.'
		);
	}//end testBatchSurvivesTheRequestThatCreatedItWithoutADistributedCache()

	/**
	 * A state change written in one request is visible in the next, with no
	 * distributed cache configured.
	 *
	 * This is the folder-extraction shutdown handler's contract: it flips each
	 * file to `extracted` and the batch to `review` AFTER the response has been
	 * flushed, and the extract endpoint in the following request must see it.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/batch-anonymization/spec.md#requirement-sequential-batch-extraction
	 */
	public function testStateChangesSurviveTheRequestBoundaryWithoutADistributedCache(): void {
		$cache = new NullCacheFake();

		$created = $this->serviceForRequest(cache: $cache)->createBatch(
			userId: 'alice',
			files: [['fileId' => 11, 'fileName' => 'zaak-a.txt', 'status' => 'uploaded', 'entityCount' => 0]]
		);
		$batchId = $created['batchId'];

		// Still request 1, in the shutdown handler.
		$shutdownService = $this->serviceForRequest(cache: $cache);
		$batch = $shutdownService->getBatch(batchId: $batchId);
		$batch['files'][0]['status'] = 'extracted';
		$batch['files'][0]['entityCount'] = 7;
		$batch['status'] = 'review';
		$batch['sourceType'] = 'folder';
		$batch['folderPath'] = '/Zaakdossiers';
		$shutdownService->updateBatch(batchId: $batchId, batch: $batch);

		// Request 2.
		$loaded = $this->serviceForRequest(cache: $cache)->getBatch(batchId: $batchId);

		$this->assertIsArray($loaded);
		$this->assertSame('review', $loaded['status']);
		$this->assertSame('extracted', $loaded['files'][0]['status']);
		$this->assertSame(7, $loaded['files'][0]['entityCount']);
		$this->assertSame('folder', $loaded['sourceType']);
		$this->assertSame('/Zaakdossiers', $loaded['folderPath']);
	}//end testStateChangesSurviveTheRequestBoundaryWithoutADistributedCache()

	/**
	 * The record OpenRegister holds carries no `@self` or `id` leakage back
	 * into the batch.
	 *
	 * @return void
	 */
	public function testTheLoadedBatchDoesNotCarryOpenRegisterEnvelopeKeys(): void {
		$cache = new NullCacheFake();
		$created = $this->serviceForRequest(cache: $cache)->createBatch(userId: 'alice', files: []);

		$loaded = $this->serviceForRequest(cache: $cache)->getBatch(batchId: $created['batchId']);

		$this->assertIsArray($loaded);
		$this->assertArrayNotHasKey('@self', $loaded);
		$this->assertArrayNotHasKey('id', $loaded);
		$this->assertArrayNotHasKey('documents', $loaded);
		$this->assertArrayHasKey('files', $loaded);
	}//end testTheLoadedBatchDoesNotCarryOpenRegisterEnvelopeKeys()

	/**
	 * A configured distributed cache short-circuits the store read.
	 *
	 * The cache remains a genuine fast path — this asserts it by emptying the
	 * store afterwards and reading again: a hit that still answers proves the
	 * value came from the cache, not from OpenRegister.
	 *
	 * @return void
	 */
	public function testAConfiguredCacheServesTheReadWithoutTouchingTheStore(): void {
		$cache = new InMemoryCacheFake();
		$created = $this->serviceForRequest(cache: $cache)->createBatch(userId: 'alice', files: []);

		$this->store->stored = [];

		$loaded = $this->serviceForRequest(cache: $cache)->getBatch(batchId: $created['batchId']);

		$this->assertIsArray(
			$loaded,
			'With a working distributed cache the read must be served from it; '
			. 'emptying the store should not have been observable.'
		);
		$this->assertSame($created['batchId'], $loaded['batchId']);
	}//end testAConfiguredCacheServesTheReadWithoutTouchingTheStore()

	/**
	 * A corrupt cache entry falls through to the store rather than being
	 * reported as "no such batch".
	 *
	 * @return void
	 */
	public function testACorruptCacheEntryFallsThroughToTheStore(): void {
		$cache = new InMemoryCacheFake();
		$created = $this->serviceForRequest(cache: $cache)->createBatch(userId: 'alice', files: []);

		$cache->set('filinq_batch_' . $created['batchId'], '"not-a-batch-record"', 60);

		$loaded = $this->serviceForRequest(cache: $cache)->getBatch(batchId: $created['batchId']);

		$this->assertIsArray($loaded);
		$this->assertSame($created['batchId'], $loaded['batchId']);
	}//end testACorruptCacheEntryFallsThroughToTheStore()

	/**
	 * Ownership is enforced on a store-backed read, not only on a cached one.
	 *
	 * The ownership check used to sit behind the cache read. Moving the source
	 * of truth without moving the guard would have opened every batch to every
	 * authenticated user on exactly the installs this change was written for.
	 *
	 * @return void
	 */
	public function testOwnershipIsStillEnforcedWhenTheBatchComesFromTheStore(): void {
		$cache = new NullCacheFake();
		$created = $this->serviceForRequest(cache: $cache, uid: 'alice')->createBatch(
			userId: 'alice',
			files: []
		);

		$asMallory = $this->serviceForRequest(cache: $cache, uid: 'mallory')
			->getBatch(batchId: $created['batchId']);

		$this->assertNull(
			$asMallory,
			'A batch belonging to another user was returned from the OpenRegister-backed '
			. 'read path; the ownership guard is not covering it.'
		);
	}//end testOwnershipIsStillEnforcedWhenTheBatchComesFromTheStore()

	/**
	 * An unknown batch id is a miss, not an error.
	 *
	 * @return void
	 */
	public function testAnUnknownBatchIdReadsAsAMiss(): void {
		$loaded = $this->serviceForRequest(cache: new NullCacheFake())
			->getBatch(batchId: '00000000-0000-4000-8000-000000000000');

		$this->assertNull($loaded);
	}//end testAnUnknownBatchIdReadsAsAMiss()

	/**
	 * A delete removes the record from the store, not just from the cache.
	 *
	 * @return void
	 */
	public function testDeleteRemovesTheRecordFromTheStore(): void {
		$cache = new NullCacheFake();
		$created = $this->serviceForRequest(cache: $cache)->createBatch(userId: 'alice', files: []);

		$this->serviceForRequest(cache: $cache)->deleteBatch(batchId: $created['batchId']);

		$this->assertSame(
			[],
			$this->store->stored,
			'deleteBatch() cleared the cache but left the record in OpenRegister, '
			. 'so the batch would come back on the next read.'
		);
	}//end testDeleteRemovesTheRecordFromTheStore()

	/**
	 * With OpenRegister unavailable, a write FAILS rather than handing back an
	 * unsaved payload.
	 *
	 * Returning `$batch` here would look like a successful write to every
	 * caller, and the caller would publish a batchId that resolves to nothing —
	 * the same silent loss this change removes, one layer up.
	 *
	 * @return void
	 */
	public function testCreateBatchFailsLoudlyWhenOpenRegisterIsUnavailable(): void {
		$service = $this->serviceForRequest(
			cache: new NullCacheFake(),
			openRegisterAvailable: false
		);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('OpenRegister is not available');

		$service->createBatch(userId: 'alice', files: []);
	}//end testCreateBatchFailsLoudlyWhenOpenRegisterIsUnavailable()

	/**
	 * The same applies to an update.
	 *
	 * @return void
	 */
	public function testUpdateBatchFailsLoudlyWhenOpenRegisterIsUnavailable(): void {
		$service = $this->serviceForRequest(
			cache: new NullCacheFake(),
			openRegisterAvailable: false
		);

		$this->expectException(RuntimeException::class);

		$service->updateBatch(batchId: 'abc-123', batch: ['batchId' => 'abc-123', 'files' => []]);
	}//end testUpdateBatchFailsLoudlyWhenOpenRegisterIsUnavailable()
}//end class
