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
     *
     * @return void
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ContainerInterface $container,
        private readonly IAppManager $appManager,
        private readonly EntityDetectionService $entityDetection
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
     * @param int $fileId The Nextcloud file ID
     *
     * @return array<string, mixed> Extraction result with entities, entityCount
     *
     * @throws Exception If extraction or detection fails
     */
    public function extractAndDetectEntities(int $fileId): array
    {
        try {
            $textExtractor = $this->getOpenRegisterService(
                className: 'OCA\OpenRegister\Service\TextExtractionService'
            );
            $textExtractor->extractFile($fileId, true);

            $this->logger->debug('Text extracted from file', ['fileId' => $fileId]);

            $entityRelationMapper = $this->getOpenRegisterService(
                className: 'OCA\OpenRegister\Db\EntityRelationMapper'
            );
            $entities = $entityRelationMapper->findEntitiesForFile($fileId);

            return [
                'entities'    => $this->entityDetection->normalizeEntities($entities),
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
     * Anonymize entities in a document
     *
     * When appendBasisSummary is true, invokes GrondslagenSummaryService after
     * the anonymised file has been written. For PDF output the summary is
     * appended as an extra page; for preserve mode a separate
     * `<base>_anonymized_grondslagen.pdf` is written alongside. Summary failure
     * is non-fatal: the anonymised file is always preserved and a `warning`
     * field is added to the response instead (HTTP 200).
     *
     * @param int                         $fileId             The Nextcloud file ID
     * @param array<array<string, mixed>> $entities           The entities to anonymize
     * @param bool                        $appendBasisSummary Whether to append a grondslagen summary (default false)
     * @param string                      $outputFormat       Output format: 'pdf' (default) or 'preserve'
     *
     * @return array<string, mixed> Anonymization result with optional warning/summaryFileId fields
     *
     * @throws Exception If anonymization fails
     *
     * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-2
     */
    public function anonymizeDocument(
        int $fileId,
        array $entities,
        bool $appendBasisSummary=false,
        string $outputFormat='pdf'
    ): array {
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

            if ($appendBasisSummary === true) {
                $resultInfo = $this->tryAppendBasisSummary(
                    resultInfo: $resultInfo,
                    node: $node,
                    outputFormat: $outputFormat
                );
            }

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
     * Attempt to append a grondslagen basis summary to the anonymized document.
     *
     * Soft-depends on GrondslagenSummaryService from the
     * anonymisation-grondslagen-summary-rendering change. When the service is
     * unavailable or throws, the failure is logged and a structured `warning`
     * field is added to the result. The anonymised file is always preserved.
     *
     * For PDF output: summary is appended as an extra page (in-place).
     * For preserve output: a separate _grondslagen.pdf is written alongside;
     * the result gains `summaryFileId` and `summaryFilePath` fields.
     *
     * @param array<string, mixed> $resultInfo   Current anonymization result
     * @param mixed                $node         Nextcloud file node of the anonymised file
     * @param string               $outputFormat 'pdf' or 'preserve'
     *
     * @return array<string, mixed> Result enriched with summary fields or a warning entry
     *
     * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-4
     * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-5
     * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-6
     */
    private function tryAppendBasisSummary(array $resultInfo, mixed $node, string $outputFormat): array
    {
        try {
            $summaryService = $this->container->get('OCA\DocuDesk\Service\GrondslagenSummaryService');

            if ($outputFormat === 'preserve') {
                $summaryResult = $summaryService->appendSummaryAsSeparatePdf(node: $node);
                $resultInfo['summaryFileId']   = $summaryResult['fileId'] ?? null;
                $resultInfo['summaryFilePath'] = $summaryResult['filePath'] ?? null;
            } else {
                $summaryService->appendSummaryToPdf(node: $node);
            }

            $this->logger->info(
                'Grondslagen basis summary appended',
                ['outputFormat' => $outputFormat]
            );
        } catch (\Throwable $e) {
            $this->logger->warning(
                'Failed to append grondslagen summary; anonymised file preserved: '.$e->getMessage(),
                ['exception' => $e]
            );
            $resultInfo['warning'] = [
                'code'    => 'SUMMARY_APPEND_FAILED',
                'message' => 'Basis summary could not be appended. The anonymised file is preserved.',
            ];
        }//end try

        return $resultInfo;

    }//end tryAppendBasisSummary()


}//end class
