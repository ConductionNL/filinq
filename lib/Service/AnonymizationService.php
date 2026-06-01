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
use OCA\DocuDesk\Exception\ConversionFailedException;
use RuntimeException;
use OCP\App\IAppManager;
use OCP\Files\File;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

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
     * @param LoggerInterface           $logger             Logger for error reporting
     * @param ContainerInterface        $container          Container for dependency injection
     * @param IAppManager               $appManager         App manager interface
     * @param EntityDetectionService    $entityDetection    Entity detection and mapping service
     * @param GrondslagenSummaryService $grondslagenSummary Renderer for the per-document grondslagen
     *                                                      summary page (Wave 4a — opt-in via
     *                                                      `appendBasisSummary: true` on the request).
     *
     * @return void
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ContainerInterface $container,
        private readonly IAppManager $appManager,
        private readonly EntityDetectionService $entityDetection,
        private readonly GrondslagenSummaryService $grondslagenSummary,
        private readonly PdfConversionService $pdfConversion
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
     * @param int                         $fileId             The Nextcloud file ID
     * @param array<array<string, mixed>> $entities           The entities to anonymize
     * @param bool                        $appendBasisSummary When true, after the anonymise pipeline
     *                                                        completes the per-document grondslagen
     *                                                        summary is rendered and either appended
     *                                                        to the resulting PDF (when the output
     *                                                        is a PDF) or saved as a separate
     *                                                        `<base>_grondslagen.pdf` file beside
     *                                                        it. Summary-step failure does NOT fail
     *                                                        the anonymise — a structured
     *                                                        `warning` is attached to the result
     *                                                        instead.
     * @param string                      $outputFormat       Output format gate. `"pdf"` (default)
     *                                                        runs the post-anonymise PDF conversion
     *                                                        cascade and rolls back the intermediate
     *                                                        if conversion fails (re-throws
     *                                                        ConversionFailedException for the
     *                                                        controller to surface as HTTP 422).
     *                                                        `"preserve"` skips conversion and
     *                                                        returns the anonymised file in its
     *                                                        native format.
     *
     * @return array<string, mixed> Anonymization result. Adds the optional `warning` field when
     *                              the grondslagen-summary step failed but the anonymise itself
     *                              succeeded; adds `summaryFileId` when a separate summary PDF
     *                              was written (preserve-mode fallback).
     *
     * @throws Exception                  If anonymization fails.
     * @throws ConversionFailedException  When `$outputFormat === "pdf"` and the cascade could not
     *                                    convert the anonymised intermediate. The intermediate
     *                                    is deleted (best-effort) before the exception propagates.
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

            // PDF conversion gate: when outputFormat is 'pdf' AND the
            // anonymised result is not already a PDF, run the cascade.
            // On failure: delete the un-converted intermediate (the
            // operator must NOT see a half-finished native-format
            // output when they asked for PDF) and re-throw the typed
            // exception so the controller maps it to 422.
            if ($outputFormat === 'pdf' && $result instanceof File === true) {
                $resultMime = (string) $result->getMimeType();
                if ($resultMime !== 'application/pdf') {
                    try {
                        $result = $this->pdfConversion->convertToPdf($result);
                    } catch (ConversionFailedException $e) {
                        $this->logger->warning(
                            'PDF conversion failed; rolling back anonymised intermediate.',
                            [
                                'fileId'   => $fileId,
                                'attempts' => $e->getAttempts(),
                            ]
                        );
                        // Best-effort rollback. If delete fails, log
                        // and continue — re-throwing is more important
                        // than leaving the operator in a partial state
                        // that they CAN inspect (they sent
                        // outputFormat: "pdf" and got 422, so the
                        // expectation is "no file written").
                        try {
                            $result->delete();
                        } catch (Throwable $deleteError) {
                            $this->logger->warning(
                                'Rollback delete failed; orphaned anonymised file remains.',
                                [
                                    'fileId'    => $fileId,
                                    'exception' => get_class($deleteError),
                                    'message'   => $deleteError->getMessage(),
                                ]
                            );
                        }
                        throw $e;
                    }//end try
                }//end if
            }//end if

            $resultInfo = $this->entityDetection->parseAnonymizationResult($result);
            $resultInfo['replacementCount'] = count($mappedEntities);

            if ($appendBasisSummary === true) {
                $resultInfo = $this->attachGrondslagenSummary(
                    anonymisedNode: $result,
                    sourceFileId: $fileId,
                    resultInfo: $resultInfo
                );
            }

            return $resultInfo;
        } catch (ConversionFailedException $e) {
            // Surface unchanged so the controller can build the 422 body.
            throw $e;
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to anonymize document: '.$e->getMessage(),
                ['fileId' => $fileId, 'exception' => $e]
            );
            throw new Exception('Failed to anonymize document: '.$e->getMessage(), 0, $e);
        }//end try

    }//end anonymizeDocument()


    /**
     * Render and attach the grondslagen summary to a freshly-anonymised file.
     *
     * If the anonymised file is a PDF, the summary is appended to it in place
     * (one extra page). For other formats, the summary is saved as a separate
     * `<base>_grondslagen.pdf` file beside the anonymised file.
     *
     * Summary-step failure is **non-fatal**: the anonymise call still
     * returns success; the result gets a structured `warning` field so the
     * caller can surface the issue to the operator without rolling back the
     * anonymisation.
     *
     * @param mixed                $anonymisedNode The Node/File returned by OR's anonymizeDocument.
     * @param int                  $sourceFileId   The pre-anonymisation source file id (used to look
     *                                             up the EntityRelation rows that carry the bases).
     * @param array<string, mixed> $resultInfo     The current result info — extended with the
     *                                             summary's `summaryFileId` / `warning` fields and
     *                                             returned.
     *
     * @return array<string, mixed> The (possibly-extended) result info.
     */
    private function attachGrondslagenSummary(mixed $anonymisedNode, int $sourceFileId, array $resultInfo): array
    {
        if (($anonymisedNode instanceof \OCP\Files\File) === false) {
            $resultInfo['warning'] = 'grondslagen_summary_skipped: anonymised result is not a File node';
            return $resultInfo;
        }

        $mime  = $anonymisedNode->getMimeType();
        $isPdf = ($mime === 'application/pdf');

        try {
            if ($isPdf === true) {
                $this->grondslagenSummary->appendSummaryToPdf(
                    anonymisedFile: $anonymisedNode,
                    sourceFileId: $sourceFileId
                );
                $resultInfo['summaryAppended'] = true;
            } else {
                $summaryFile = $this->grondslagenSummary->renderSummaryBesideFile(
                    anonymisedFile: $anonymisedNode,
                    sourceFileId: $sourceFileId
                );
                $resultInfo['summaryAppended'] = false;
                $resultInfo['summaryFileId']   = $summaryFile->getId();
                $resultInfo['summaryFilePath'] = $summaryFile->getPath();
            }
        } catch (Exception $e) {
            $this->logger->warning(
                'Grondslagen summary attach failed',
                [
                    'fileId'       => $anonymisedNode->getId(),
                    'sourceFileId' => $sourceFileId,
                    'isPdf'        => $isPdf,
                    'error'        => $e->getMessage(),
                ]
            );
            $resultInfo['warning'] = 'grondslagen_summary_failed: '.$e->getMessage();
        }//end try

        return $resultInfo;

    }//end attachGrondslagenSummary()


}//end class
