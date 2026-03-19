<?php
/**
 * Anonymization Service
 *
 * Service for orchestrating the document anonymization pipeline:
 * text extraction with entity detection, and anonymization.
 * Uses OpenRegister services for text extraction and entity recognition.
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

use Exception;
use RuntimeException;
use OCP\App\IAppManager;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for orchestrating the document anonymization pipeline
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class AnonymizationService
{


    /**
     * Constructor for AnonymizationService
     *
     * @param LoggerInterface    $logger     Logger for error reporting
     * @param ContainerInterface $container  Container for dependency injection
     * @param IAppManager        $appManager App manager interface
     *
     * @return void
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ContainerInterface $container,
        private readonly IAppManager $appManager
    ) {

    }//end __construct()


    /**
     * Get an OpenRegister service or mapper by class name
     *
     * @param string $className The fully qualified class name
     *
     * @return mixed The service instance
     *
     * @throws \RuntimeException If OpenRegister is not available
     */
    private function getOpenRegisterService(string $className): mixed
    {
        if (in_array('openregister', $this->appManager->getInstalledApps(), true) === true) {
            return $this->container->get($className);
        }

        throw new RuntimeException($className.' is not available.');

    }//end getOpenRegisterService()


    /**
     * Normalize entity data to a consistent format
     *
     * @param array<mixed> $entities Raw entity objects or arrays
     *
     * @return array<int, array<string, mixed>> Normalized entity list
     */
    private function normalizeEntities(array $entities): array
    {
        $normalizedEntities = [];
        foreach ($entities as $entity) {
            $entityData = (array) $entity;
            if (is_object($entity) === true && method_exists($entity, 'jsonSerialize') === true) {
                $entityData = $entity->jsonSerialize();
            }

            $normalizedEntities[] = [
                'type'       => $entityData['entity_type'] ?? $entityData['entityType'] ?? 'UNKNOWN',
                'value'      => $entityData['entity_value'] ?? $entityData['entityValue'] ?? '',
                'confidence' => $entityData['confidence'] ?? 0.0,
            ];
        }

        return $normalizedEntities;

    }//end normalizeEntities()


    /**
     * Extract text from a file and detect entities
     *
     * @param int $fileId The Nextcloud file ID
     *
     * @return array<string, mixed> Extraction result with entities, entityCount
     *
     * @throws Exception If extraction or detection fails
     */
    public function extractAndDetectEntities(int $fileId): array
    {
        try {
            $textExtractor = $this->getOpenRegisterService('OCA\OpenRegister\Service\TextExtractionService');
            $textExtractor->extractFile($fileId, true);

            $this->logger->debug('Text extracted from file', ['fileId' => $fileId]);

            $entityRelationMapper = $this->getOpenRegisterService('OCA\OpenRegister\Db\EntityRelationMapper');
            $entities = $entityRelationMapper->findEntitiesForFile($fileId);

            return [
                'entities'    => $this->normalizeEntities($entities),
                'entityCount' => count($entities),
            ];
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to extract and detect entities: '.$e->getMessage(),
                ['fileId' => $fileId, 'exception' => $e]
            );
            throw new Exception(
                'Failed to extract and detect entities: '.$e->getMessage(),
                0,
                $e
            );
        }//end try

    }//end extractAndDetectEntities()


    /**
     * Check if an entity text should be skipped for anonymization
     *
     * @param string              $text The entity text value
     * @param array<string, bool> $seen Already seen entity texts
     *
     * @return bool True if the entity should be skipped
     */
    private function shouldSkipEntity(string $text, array $seen): bool
    {
        if ($text === '' || strlen($text) < 3 || is_numeric($text) === true) {
            return true;
        }

        return isset($seen[$text]) === true;

    }//end shouldSkipEntity()


    /**
     * Map entities to the format expected by OpenRegister's anonymizeDocument
     *
     * @param array<array<string, mixed>> $entities The raw entities
     *
     * @return array<int, array<string, string>> Mapped entities
     */
    private function mapEntitiesForAnonymization(array $entities): array
    {
        $mappedEntities = [];
        $seen           = [];
        foreach ($entities as $entity) {
            $text = (string) ($entity['value'] ?? $entity['text'] ?? '');

            if ($this->shouldSkipEntity($text, $seen) === true) {
                continue;
            }

            $seen[$text]      = true;
            $mappedEntities[] = [
                'text'       => $text,
                'entityType' => (string) ($entity['type'] ?? $entity['entityType'] ?? 'UNKNOWN'),
                'key'        => $this->generateUuid(),
            ];
        }

        return $mappedEntities;

    }//end mapEntitiesForAnonymization()


    /**
     * Extract file info from anonymization result object
     *
     * @param mixed $result The anonymization result
     *
     * @return array{anonymizedFileId: mixed, anonymizedFileName: mixed, anonymizedFilePath: mixed}
     */
    private function extractResultFromObject(mixed $result): array
    {
        $fileName = null;
        if (method_exists($result, 'getName') === true) {
            $fileName = $result->getName();
        }

        $filePath = null;
        if (method_exists($result, 'getPath') === true) {
            $filePath = $result->getPath();
        }

        return [
            'anonymizedFileId'   => $result->getId(),
            'anonymizedFileName' => $fileName,
            'anonymizedFilePath' => $filePath,
        ];

    }//end extractResultFromObject()


    /**
     * Extract file info from anonymization result array
     *
     * @param array<string, mixed> $result The anonymization result
     *
     * @return array{anonymizedFileId: mixed, anonymizedFileName: mixed, anonymizedFilePath: mixed}
     */
    private function extractResultFromArray(array $result): array
    {
        return [
            'anonymizedFileId'   => $result['fileId'] ?? $result['id'] ?? null,
            'anonymizedFileName' => $result['fileName'] ?? $result['name'] ?? null,
            'anonymizedFilePath' => $result['filePath'] ?? $result['path'] ?? null,
        ];

    }//end extractResultFromArray()


    /**
     * Parse anonymization result into a structured array
     *
     * @param mixed $result The raw anonymization result
     *
     * @return array{anonymizedFileId: mixed, anonymizedFileName: mixed, anonymizedFilePath: mixed}
     */
    private function parseAnonymizationResult(mixed $result): array
    {
        if (is_object($result) === true && method_exists($result, 'getId') === true) {
            return $this->extractResultFromObject($result);
        }

        if (is_array($result) === true) {
            return $this->extractResultFromArray($result);
        }

        return [
            'anonymizedFileId'   => null,
            'anonymizedFileName' => null,
            'anonymizedFilePath' => null,
        ];

    }//end parseAnonymizationResult()


    /**
     * Anonymize entities in a document
     *
     * @param int                         $fileId   The Nextcloud file ID
     * @param array<array<string, mixed>> $entities The entities to anonymize
     *
     * @return array<string, mixed> Anonymization result
     *
     * @throws Exception If anonymization fails
     */
    public function anonymizeDocument(int $fileId, array $entities): array
    {
        try {
            $fileService    = $this->getOpenRegisterService('OCA\OpenRegister\Service\FileService');
            $node           = $fileService->getFileById($fileId);
            $mappedEntities = $this->mapEntitiesForAnonymization($entities);
            $result         = $fileService->anonymizeDocument($node, $mappedEntities);

            $this->logger->info('Document anonymized', ['fileId' => $fileId, 'entityCount' => count($mappedEntities)]);

            $resultInfo = $this->parseAnonymizationResult($result);
            $resultInfo['replacementCount'] = count($mappedEntities);

            return $resultInfo;
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to anonymize document: '.$e->getMessage(),
                ['fileId' => $fileId, 'exception' => $e]
            );
            throw new Exception('Failed to anonymize document: '.$e->getMessage(), 0, $e);
        }//end try

    }//end anonymizeDocument()


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
