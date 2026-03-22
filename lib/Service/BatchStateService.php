<?php
/**
 * Batch State Service
 *
 * Manages batch anonymization state via Nextcloud ICache.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use OCP\IAppConfig;
use OCP\ICacheFactory;
use OCP\ICache;
use Psr\Log\LoggerInterface;

/**
 * Service for managing batch anonymization state in ICache
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class BatchStateService
{

    private const CACHE_TTL = 7200;
    private const DEFAULT_MAX_FILES = 100;
    private const CACHE_PREFIX = 'docudesk_batch_';

    /**
     * @var ICache
     */
    private ICache $cache;


    /**
     * Constructor for BatchStateService
     *
     * @param ICacheFactory   $cacheFactory Cache factory
     * @param IAppConfig      $appConfig    App configuration
     * @param LoggerInterface $logger       Logger
     *
     * @return void
     */
    public function __construct(
        ICacheFactory $cacheFactory,
        private readonly IAppConfig $appConfig,
        private readonly LoggerInterface $logger
    ) {
        $this->cache = $cacheFactory->createDistributed('docudesk');

    }//end __construct()


    /**
     * Get the maximum allowed batch file count
     *
     * @return int The maximum number of files per batch
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
     * Create a new batch
     *
     * @param string                           $userId The user ID
     * @param array<int, array<string, mixed>> $files  Array of file entries
     *
     * @return array<string, mixed> The created batch state
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
     * Get a batch by ID
     *
     * @param string $batchId The batch ID
     *
     * @return array<string, mixed>|null The batch state or null
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

        return $batch;

    }//end getBatch()


    /**
     * Update an existing batch
     *
     * @param string               $batchId The batch ID
     * @param array<string, mixed> $batch   The updated batch state
     *
     * @return void
     */
    public function updateBatch(string $batchId, array $batch): void
    {
        $this->cache->set(self::CACHE_PREFIX.$batchId, json_encode($batch), self::CACHE_TTL);

    }//end updateBatch()


    /**
     * Delete a batch
     *
     * @param string $batchId The batch ID
     *
     * @return void
     */
    public function deleteBatch(string $batchId): void
    {
        $this->cache->remove(self::CACHE_PREFIX.$batchId);

    }//end deleteBatch()


    /**
     * Generate a UUID v4 string
     *
     * @return string A UUID v4 string
     */
    private function generateUuid(): string
    {
        $data    = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));

    }//end generateUuid()


}//end class
