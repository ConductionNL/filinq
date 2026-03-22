<?php
/**
 * Anonymization Service
 *
 * Service for orchestrating the document anonymization pipeline:
 * text extraction with entity detection, and anonymization.
 * Uses OpenRegister services for text extraction and entity recognition.
 * Delegates entity detection logic to EntityDetectionService.
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
     * @param LoggerInterface        $logger          Logger for error reporting
     * @param ContainerInterface     $container       Container for dependency injection
     * @param IAppManager            $appManager      App manager interface
     * @param EntityDetectionService $entityDetection Entity detection and mapping service
     * @param OcrService             $ocrService      OCR text extraction service
     *
     * @return void
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ContainerInterface $container,
        private readonly IAppManager $appManager,
        private readonly EntityDetectionService $entityDetection,
        private readonly OcrService $ocrService
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
     * Extract text from a file and detect entities
     *
     * Runs OCR pre-processing for image-based documents before
     * delegating to OpenRegister's TextExtractionService for entity detection.
     *
     * @param int $fileId The Nextcloud file ID
     *
     * @return array<string, mixed> Extraction result with entities, entityCount, riskLevel     * @return array<string, mixed> Extraction result with entities, entityCount,
     *                              ocrProcessed, and optionally ocrConfidence     *
     * @throws Exception If extraction or detection fails
     */
    public function extractAndDetectEntities(int $fileId): array
    {
        try {
            // Run OCR pre-processing for scanned/image documents.
            $ocrResult = $this->ocrService->processFile($fileId);

            if ($ocrResult['ocrProcessed'] === true) {
                $this->logger->info(
                    'OCR text extracted before entity detection',
                    [
                        'fileId'     => $fileId,
                        'confidence' => $ocrResult['confidence'],
                        'textLength' => strlen($ocrResult['text']),
                    ]
                );
            }

            $textExtractor = $this->getOpenRegisterService(
                className: 'OCA\OpenRegister\Service\TextExtractionService'
            );
            $textExtractor->extractFile($fileId, true);

            $this->logger->debug('Text extracted from file', ['fileId' => $fileId]);

            $entityRelationMapper = $this->getOpenRegisterService(
                className: 'OCA\OpenRegister\Db\EntityRelationMapper'
            );
            $entities = $entityRelationMapper->findEntitiesForFile($fileId);

            $riskLevel = $this->getRiskLevelForFile(fileId: $fileId);

            return [
                'entities'    => $this->entityDetection->normalizeEntities($entities),
                'entityCount' => count($entities),
                'riskLevel'   => $riskLevel,            $result = [
                'entities'     => $this->entityDetection->normalizeEntities($entities),
                'entityCount'  => count($entities),
                'ocrProcessed' => $ocrResult['ocrProcessed'],            ];

            if ($ocrResult['ocrProcessed'] === true) {
                $result['ocrConfidence'] = $ocrResult['confidence'];
            }

            return $result;
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
     * Get risk level for a file, with graceful fallback
     *
     * @param int $fileId The Nextcloud file ID
     *
     * @return string The risk level or "unknown"
     */
    private function getRiskLevelForFile(int $fileId): string
    {
        try {
            $riskLevelService = $this->getOpenRegisterService(
                className: 'OCA\OpenRegister\Service\RiskLevelService'
            );
            return $riskLevelService->getRiskLevel($fileId);
        } catch (RuntimeException $e) {
            $this->logger->debug(
                'RiskLevelService unavailable, using default',
                ['fileId' => $fileId]
            );
            return 'unknown';
        }

    }//end getRiskLevelForFile()


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
            $fileService    = $this->getOpenRegisterService(className: 'OCA\OpenRegister\Service\FileService');
            $node           = $fileService->getFileById($fileId);
            $mappedEntities = $this->entityDetection->mapEntitiesForAnonymization($entities);
            $result         = $fileService->anonymizeDocument($node, $mappedEntities);

            $this->logger->info(
                'Document anonymized',
                ['fileId' => $fileId, 'entityCount' => count($mappedEntities)]
            );

            $resultInfo = $this->entityDetection->parseAnonymizationResult($result);
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


}//end class
