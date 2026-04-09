<?php
declare(strict_types=1);
namespace OCA\DocuDesk\Service;
use OCP\IAppConfig;
use OCP\ICacheFactory;
use OCP\ICache;
use Psr\Log\LoggerInterface;
/**
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2
 * @link     https://www.DocuDesk.app
 */
class BatchStateService
{
    private const CACHE_TTL = 7200;
    private const DEFAULT_MAX_FILES = 100;
    private const CACHE_PREFIX = 'docudesk_batch_';
    private ICache $cache;
    public function __construct(ICacheFactory $cacheFactory, private readonly IAppConfig $appConfig, private readonly LoggerInterface $logger)
    {
        $this->cache = $cacheFactory->createDistributed('docudesk');
    }
    public function getMaxFiles(): int
    {
        return (int) $this->appConfig->getValueString('docudesk', 'docudesk_batch_max_files', (string) self::DEFAULT_MAX_FILES);
    }
    /** @return array<string, mixed> */
    public function createBatch(string $userId, array $files): array
    {
        $batchId = $this->generateUuid();
        $batch = ['batchId' => $batchId, 'userId' => $userId, 'status' => 'uploading', 'files' => $files, 'createdAt' => time()];
        $this->cache->set(self::CACHE_PREFIX.$batchId, json_encode($batch), self::CACHE_TTL);
        $this->logger->info('Batch created', ['batchId' => $batchId, 'fileCount' => count($files)]);
        return $batch;
    }
    public function getBatch(string $batchId): ?array
    {
        $data = $this->cache->get(self::CACHE_PREFIX.$batchId);
        if ($data === null) { return null; }
        $batch = json_decode($data, true);
        if (is_array($batch) === false) { return null; }
        // Reset TTL on read (keep-alive pattern) so active batches don't expire during human review.
        $this->cache->set(self::CACHE_PREFIX.$batchId, $data, self::CACHE_TTL);
        return $batch;
    }
    public function updateBatch(string $batchId, array $batch): void
    {
        $this->cache->set(self::CACHE_PREFIX.$batchId, json_encode($batch), self::CACHE_TTL);
    }
    public function deleteBatch(string $batchId): void
    {
        $this->cache->remove(self::CACHE_PREFIX.$batchId);
    }
    private function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
