<?php
/**
 * Batch State Service
 *
 * Persists short-lived anonymization batch state in Nextcloud's distributed
 * cache. Each batch is keyed by UUID, JSON-encoded, and refreshed on every
 * read so active batches survive human review without stalling due to TTL.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-5
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-7
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use OCP\IAppConfig;
use OCP\ICacheFactory;
use OCP\ICache;
use Psr\Log\LoggerInterface;

/**
 * Read/write store for anonymization batch state backed by the distributed cache.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class BatchStateService
{
    private const CACHE_TTL         = 7200;
    private const DEFAULT_MAX_FILES = 100;
    private const CACHE_PREFIX      = 'docudesk_batch_';

    /**
     * Distributed cache instance used to persist batch records.
     *
     * @var ICache
     */
    private ICache $cache;

    /**
     * Constructor for BatchStateService
     *
     * @param ICacheFactory   $cacheFactory Factory used to obtain the distributed cache.
     * @param IAppConfig      $appConfig    App-config lookup for runtime limits.
     * @param LoggerInterface $logger       Logger for lifecycle events.
     *
     * @return void
     */
    public function __construct(
        ICacheFactory $cacheFactory,
        private readonly IAppConfig $appConfig,
        private readonly LoggerInterface $logger,
    ) {
        $this->cache = $cacheFactory->createDistributed('docudesk');

    }//end __construct()

    /**
     * Return the maximum number of files allowed in a single batch.
     *
     * @return int Configured or default maximum.
     */
    public function getMaxFiles(): int
    {
        return (int) $this->appConfig->getValueString(
            'docudesk',
            'docudesk_batch_max_files',
            (string) self::DEFAULT_MAX_FILES
        );

    }//end getMaxFiles()

    /**
     * Create and persist a new batch record for a user.
     *
     * @param string                           $userId User identifier to associate with the batch.
     * @param array<int, array<string, mixed>> $files  Per-file entries to seed the batch with.
     *
     * @return array<string, mixed> The newly created batch record.
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-5
     */
    public function createBatch(string $userId, array $files): array
    {
        $batchId = $this->generateUuid();
        $batch   = [
            'batchId'   => $batchId,
            'userId'    => $userId,
            'status'    => 'uploading',
            'files'     => $files,
            'createdAt' => time(),
        ];
        $this->cache->set(self::CACHE_PREFIX.$batchId, json_encode($batch), self::CACHE_TTL);
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
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-7
     */
    public function getBatch(string $batchId): ?array
    {
        $data = $this->cache->get(self::CACHE_PREFIX.$batchId);
        if ($data === null) {
            return null;
        }

        $batch = json_decode($data, true);
        if (is_array($batch) === false) {
            return null;
        }

        // Reset TTL on read (keep-alive pattern) so active batches don't expire during human review.
        $this->cache->set(self::CACHE_PREFIX.$batchId, $data, self::CACHE_TTL);
        return $batch;

    }//end getBatch()

    /**
     * Persist an updated batch record.
     *
     * @param string               $batchId Batch identifier.
     * @param array<string, mixed> $batch   Full batch record to store.
     *
     * @return void
     */
    public function updateBatch(string $batchId, array $batch): void
    {
        $this->cache->set(self::CACHE_PREFIX.$batchId, json_encode($batch), self::CACHE_TTL);

    }//end updateBatch()

    /**
     * Remove a batch record from the store.
     *
     * @param string $batchId Batch identifier.
     *
     * @return void
     */
    public function deleteBatch(string $batchId): void
    {
        $this->cache->remove(self::CACHE_PREFIX.$batchId);

    }//end deleteBatch()

    /**
     * Generate an RFC 4122 version-4 UUID for use as a batch identifier.
     *
     * @return string Hyphenated UUID string.
     */
    private function generateUuid(): string
    {
        $data    = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));

    }//end generateUuid()
}//end class
