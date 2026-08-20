<?php

/**
 * Batch State Service
 *
 * Persists anonymization batch state in OpenRegister, with Nextcloud's
 * distributed cache in front of it as a read fast path. Each batch is keyed by
 * UUID; the cached copy is refreshed on every read so an active batch never
 * has to fall back to the store during human review.
 *
 * ⚠️ OPENREGISTER IS THE SOURCE OF TRUTH, NOT THE CACHE.
 * `ICacheFactory::createDistributed()` degrades to the local cache class, and
 * that defaults to `OC\Memcache\NullCache` on any instance without
 * `memcache.local` configured — a cache that discards every write and returns
 * null for every read. A cache-only batch store therefore loses the record the
 * moment the creating request ends, so the folder-batch POST answered 200 with
 * a batchId and the very next call on that batchId answered 404. Per
 * ADR-022/ADR-083 the batch is persisted through OpenRegister's object
 * abstraction, which makes the flow correct with no cache configured at all.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-creation-via-multi-file-upload
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-status-endpoint
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use OCP\IAppConfig;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IGroupManager;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Read/write store for anonymization batch state, backed by OpenRegister and
 * fronted by the distributed cache.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-status-endpoint
 */
class BatchStateService {
	private const CACHE_TTL = 7200;
	private const DEFAULT_MAX_FILES = 100;
	private const CACHE_PREFIX = 'docudesk_batch_';

	/**
	 * Distributed cache instance used to persist batch records.
	 *
	 * @var ICache
	 */
	private ICache $cache;

	/**
	 * Constructor for BatchStateService
	 *
	 * @param ICacheFactory $cacheFactory Factory used to obtain the distributed cache.
	 * @param IAppConfig $appConfig App-config lookup for runtime limits.
	 * @param LoggerInterface $logger Logger for lifecycle events.
	 * @param IUserSession $userSession User session for ownership checks.
	 * @param IGroupManager $groupManager Group manager for admin bypass.
	 * @param BatchStateRepository $repository OpenRegister-backed store of record
	 *                                         for batch state. Injected via the
	 *                                         constructor (ADR-083) so the
	 *                                         dependency is declared where a
	 *                                         reader and a gate can both see it.
	 *
	 * @return void
	 */
	public function __construct(
		ICacheFactory $cacheFactory,
		private readonly IAppConfig $appConfig,
		private readonly LoggerInterface $logger,
		private readonly IUserSession $userSession,
		private readonly IGroupManager $groupManager,
		private readonly BatchStateRepository $repository,
	) {
		$this->cache = $cacheFactory->createDistributed('docudesk');

	}//end __construct()

	/**
	 * Return the maximum number of files allowed in a single batch.
	 *
	 * @return int Configured or default maximum.
	 *
	 * @spec openspec/specs/batch-anonymization/spec.md
	 */
	public function getMaxFiles(): int {
		// Canonical manifest-declared key (docudesk-adopt-or-abstractions task 11);
		// legacy 'docudesk_batch_max_files' kept as a one-release fallback.
		$value = $this->appConfig->getValueString(
			'docudesk',
			'batch.max_files_per_run',
			''
		);
		if ($value !== '') {
			return (int)$value;
		}

		return (int)$this->appConfig->getValueString(
			'docudesk',
			'docudesk_batch_max_files',
			(string)self::DEFAULT_MAX_FILES
		);

	}//end getMaxFiles()

	/**
	 * Get the cache TTL in seconds for batch records.
	 *
	 * Reads `docudesk.batch.cache_ttl_seconds` from app-config (canonical key
	 * declared in manifest.yaml under docudesk-adopt-or-abstractions task 11).
	 * Returns the in-class constant when unset so existing deployments behave
	 * identically until an admin overrides it.
	 *
	 * @return int TTL in seconds.
	 */
	private function getCacheTtl(): int {
		$value = $this->appConfig->getValueString(
			'docudesk',
			'batch.cache_ttl_seconds',
			''
		);
		if ($value !== '') {
			return (int)$value;
		}

		return self::CACHE_TTL;
	}//end getCacheTtl()

	/**
	 * Create and persist a new batch record for a user.
	 *
	 * @param string $userId User identifier to associate with the batch.
	 * @param array<int, array<string, mixed>> $files Per-file entries to seed the batch with.
	 *
	 * @return array<string, mixed> The newly created batch record.
	 *
	 * @throws RuntimeException When the batch cannot be persisted.
	 *
	 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-creation-via-multi-file-upload
	 */
	public function createBatch(string $userId, array $files): array {
		$batchId = $this->generateUuid();
		$batch = [
			'batchId' => $batchId,
			'userId' => $userId,
			'status' => 'uploading',
			'files' => $files,
			'createdAt' => time(),
		];

		// Store of record FIRST. If this throws, no batchId is handed to the
		// caller, which is the honest outcome: a batchId that resolves to
		// nothing on the next request is worse than an error here.
		$this->repository->save(batchId: $batchId, batch: $batch);
		$this->cache->set(self::CACHE_PREFIX . $batchId, json_encode($batch), $this->getCacheTtl());
		$this->logger->info('Batch created', ['batchId' => $batchId, 'fileCount' => count($files)]);
		return $batch;
	}//end createBatch()

	/**
	 * Load a batch record by ID and refresh its TTL.
	 *
	 * @param string $batchId Batch identifier.
	 *
	 * @return array<string, mixed>|null The decoded batch record, or null when missing or corrupt.
	 *
	 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-status-endpoint
	 */
	public function getBatch(string $batchId): ?array {
		$batch = $this->readThrough(batchId: $batchId);
		if ($batch === null) {
			return null;
		}

		// C2 / WF3 security fix: enforce batch ownership so an authenticated user
		// cannot read or drive another user's batch by guessing its ID.
		// Admins may access any batch for support/audit purposes.
		// WF3 fix: return null (not throw RuntimeException) on access-denied so
		// callers return 404 for both "not found" and "found-but-denied" — the
		// previous throw produced a distinct 500 body that confirmed existence.
		$currentUser = $this->userSession->getUser();
		if ($currentUser !== null) {
			$currentUid = $currentUser->getUID();
			$batchUserId = (string)($batch['userId'] ?? '');
			$isAdmin = $this->groupManager->isAdmin($currentUid);

			if ($isAdmin === false && $batchUserId !== $currentUid) {
				$this->logger->info(
					'Batch access denied: batchId belongs to another user',
					['batchId' => $batchId, 'requestingUid' => $currentUid]
				);
				return null;
			}
		}

		// Reset TTL on read (keep-alive pattern) so active batches don't expire
		// during human review, and warm the cache after a store read. Harmless
		// against a NullCache: it discards the write and the next read falls
		// through to OpenRegister again.
		$this->cache->set(self::CACHE_PREFIX . $batchId, json_encode($batch), $this->getCacheTtl());
		return $batch;
	}//end getBatch()

	/**
	 * Load a batch record from the cache, falling back to OpenRegister.
	 *
	 * The cache is a fast path only. A miss — or a corrupt/legacy entry that
	 * does not decode to an array — falls through to the store of record
	 * rather than being reported as "no such batch", because on an instance
	 * with no distributed cache configured EVERY read is a miss.
	 *
	 * @param string $batchId Batch identifier.
	 *
	 * @return array<string, mixed>|null The batch record, or null when absent.
	 *
	 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-status-endpoint
	 */
	private function readThrough(string $batchId): ?array {
		$data = $this->cache->get(self::CACHE_PREFIX . $batchId);
		if ($data !== null) {
			$cached = json_decode($data, true);
			if (is_array($cached) === true) {
				return $cached;
			}

			$this->logger->warning(
				'Discarding an undecodable cached batch entry; reading the store instead',
				['batchId' => $batchId]
			);
		}

		return $this->repository->find(batchId: $batchId);
	}//end readThrough()

	/**
	 * Persist an updated batch record.
	 *
	 * @param string $batchId Batch identifier.
	 * @param array<string, mixed> $batch Full batch record to store.
	 *
	 * @return void
	 *
	 * @throws RuntimeException When the batch cannot be persisted.
	 *
	 * @spec openspec/specs/batch-anonymization/spec.md
	 */
	public function updateBatch(string $batchId, array $batch): void {
		// Store of record first, cache second — so a cache that accepted the
		// write can never be the only place the new state exists.
		$this->repository->save(batchId: $batchId, batch: $batch);
		$this->cache->set(self::CACHE_PREFIX . $batchId, json_encode($batch), $this->getCacheTtl());

	}//end updateBatch()

	/**
	 * Remove a batch record from the store.
	 *
	 * @param string $batchId Batch identifier.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/batch-anonymization/spec.md
	 *
	 * @orphaned-write-capability exclude completes the store's CRUD surface
	 * beside createBatch/getBatch/updateBatch. No caller has needed it yet —
	 * but a store that can create and update and not delete is the odd shape,
	 * and now that records outlive the request in OpenRegister rather than
	 * expiring with a cache TTL, the ability to remove one is the only way a
	 * batch ever leaves the store.
	 */
	public function deleteBatch(string $batchId): void {
		$this->cache->remove(self::CACHE_PREFIX . $batchId);
		$this->repository->delete(batchId: $batchId);

	}//end deleteBatch()

	/**
	 * Generate an RFC 4122 version-4 UUID for use as a batch identifier.
	 *
	 * @return string Hyphenated UUID string.
	 */
	private function generateUuid(): string {
		$data = random_bytes(16);
		$data[6] = chr(ord($data[6]) & 0x0f | 0x40);
		$data[8] = chr(ord($data[8]) & 0x3f | 0x80);
		return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
	}//end generateUuid()
}//end class
