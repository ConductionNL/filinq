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
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
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
     * Result key used when the grondslagen-summary step fails but anonymisation succeeded.
     */
    private const SUMMARY_APPEND_FAILED = 'grondslagen_summary_failed';

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
        private readonly GrondslagenSummaryService $grondslagenSummary
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
     * @param string                      $outputFormat       Requested output format: 'pdf' (default)
     *                                                        appends the summary to the output PDF;
     *                                                        'preserve' saves the summary as a
     *                                                        separate file regardless of MIME type.
     *
     * @return array<string, mixed> Anonymization result. Adds the optional `warning` field when
     *                              the grondslagen-summary step failed but the anonymise itself
     *                              succeeded; adds `summaryFileId` when a separate summary PDF
     *                              was written (preserve-mode fallback).
     *
     * @throws Exception If anonymization fails
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

            // Derive replacement stats from the actual result, not from count($mappedEntities).
            $sourceText   = $this->readNodeTextSafely(node: $node);
            $verifyResult = $this->verifyReplacements(
                mappedEntities: $mappedEntities,
                originalText: $sourceText
            );
            $resultInfo['replacementsAttempted'] = $verifyResult['replacementsAttempted'];
            $resultInfo['replacementsApplied']   = $verifyResult['replacementsApplied'];
            $resultInfo['replacementsVerified']  = $verifyResult['replacementsVerified'];
            $resultInfo['unmatchedEntities']     = $verifyResult['unmatchedEntities'];

            if ($appendBasisSummary === true) {
                $resultInfo = $this->tryAppendBasisSummary(
                    anonymisedNode: $result,
                    sourceFileId: $fileId,
                    outputFormat: $outputFormat,
                    resultInfo: $resultInfo
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

    /**
     * Verify that anonymised entities are actually present in the source text.
     *
     * Uses case-insensitive matching to mirror OpenRegister's str_ireplace semantics.
     *
     * @param array<int, array<string, mixed>> $mappedEntities Entities submitted for anonymisation.
     * @param string                           $originalText   Full source-document text (empty when unreadable).
     *
     * @return array<string, mixed> Stats: replacementsAttempted, replacementsApplied, replacementsVerified, unmatchedEntities.
     */
    private function verifyReplacements(array $mappedEntities, ?string $originalText): array
    {
        $attempted = count($mappedEntities);

        if ($originalText === null) {
            // Binary source — verification not possible; callers see the truth.
            return [
                'replacementsAttempted' => $attempted,
                'replacementsApplied'   => null,
                'replacementsVerified'  => false,
                'unmatchedEntities'     => [],
            ];
        }

        $applied   = 0;
        $unmatched = [];

        foreach ($mappedEntities as $entity) {
            $text = (string) ($entity['text'] ?? '');
            if ($text !== '' && stripos($originalText, $text) !== false) {
                $applied++;
            } else {
                $unmatched[] = $entity;
            }
        }

        return [
            'replacementsAttempted' => $attempted,
            'replacementsApplied'   => $applied,
            'replacementsVerified'  => ($applied > 0),
            'unmatchedEntities'     => $unmatched,
        ];

    }//end verifyReplacements()

    /**
     * Read a file node's text content without throwing on binary formats.
     *
     * Returns null for non-File nodes and for binary MIME types (anything that
     * is not text/*). Callers should treat null as "text unavailable" and skip
     * verification rather than failing.
     *
     * @param mixed $node The file node returned by OpenRegister's FileService.
     *
     * @return string|null The raw text content, or null when the file is binary/unreadable.
     */
    private function readNodeTextSafely(mixed $node): ?string
    {
        if (is_object($node) === false) {
            return null;
        }

        if (method_exists($node, 'getMimeType') === false || method_exists($node, 'getContent') === false) {
            return null;
        }

        $mime = $node->getMimeType();
        if (str_starts_with($mime, 'text/') === false) {
            return null;
        }

        try {
            return $node->getContent();
        } catch (Exception $e) {
            $this->logger->debug(
                'AnonymizationService: could not read node text for verification',
                ['error' => $e->getMessage()]
            );
            return null;
        }

    }//end readNodeTextSafely()

    /**
     * Render and attach the grondslagen summary to a freshly-anonymised file.
     *
     * When `$outputFormat === 'preserve'` the caller explicitly opted out of
     * PDF conversion; the summary is always saved as a separate file in that
     * case regardless of the anonymised file's MIME type.
     *
     * Summary-step failure is non-fatal: the anonymise call still returns
     * success; the result gets a structured `warning` field.
     *
     * @param mixed                $anonymisedNode The Node/File returned by OR's anonymizeDocument.
     * @param int                  $sourceFileId   Pre-anonymisation source file id.
     * @param string               $outputFormat   Caller's requested output format ('pdf' or 'preserve').
     * @param array<string, mixed> $resultInfo     Current result info — extended and returned.
     *
     * @return array<string, mixed> The (possibly-extended) result info.
     */
    private function tryAppendBasisSummary(
        mixed $anonymisedNode,
        int $sourceFileId,
        string $outputFormat,
        array $resultInfo
    ): array {
        if (($anonymisedNode instanceof \OCP\Files\File) === false) {
            $resultInfo['warning'] = self::SUMMARY_APPEND_FAILED.': anonymised result is not a File node';
            return $resultInfo;
        }

        $mime     = $anonymisedNode->getMimeType();
        $isPdf    = ($mime === 'application/pdf');
        $preserve = ($outputFormat === 'preserve');

        try {
            if ($isPdf === true && $preserve === false) {
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
                    'outputFormat' => $outputFormat,
                    'error'        => $e->getMessage(),
                ]
            );
            $resultInfo['warning'] = self::SUMMARY_APPEND_FAILED.': '.$e->getMessage();
        }//end try

        return $resultInfo;

    }//end tryAppendBasisSummary()
}//end class
