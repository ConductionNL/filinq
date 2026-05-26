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
 *
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-3
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-4
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-35
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use Exception;
use RuntimeException;
use OCP\App\IAppManager;
use OCP\IAppConfig;
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
     * Default prohibition high-confidence threshold (inclusive)
     *
     * @var float
     */
    private const DEFAULT_HIGH_CONFIDENCE_THRESHOLD = 0.85;

    /**
     * App config key for the high-confidence threshold
     *
     * @var string
     */
    private const HIGH_CONFIDENCE_THRESHOLD_KEY = 'prohibition.high_confidence_threshold';

    /**
     * Constructor for AnonymizationService
     *
     * @param LoggerInterface        $logger          Logger for error reporting
     * @param ContainerInterface     $container       Container for dependency injection
     * @param IAppManager            $appManager      App manager interface
     * @param EntityDetectionService $entityDetection Entity detection and mapping service
     * @param IAppConfig             $appConfig       App configuration for threshold settings
     *
     * @return void
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ContainerInterface $container,
        private readonly IAppManager $appManager,
        private readonly EntityDetectionService $entityDetection,
        private readonly IAppConfig $appConfig
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
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-35
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
     * Each entity in the response includes a `prohibitionMatch` field: null when
     * no publication-prohibition rule matches, or an object with ruleId, ruleName,
     * and highConfidence (score >= configured threshold, inclusive).
     *
     * @param int $fileId The Nextcloud file ID
     *
     * @return array<string, mixed> Extraction result with entities, entityCount
     *
     * @throws Exception If extraction or detection fails
     *
     * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-5
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-3
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
            $entities   = $entityRelationMapper->findEntitiesForFile($fileId);
            $normalized = $this->entityDetection->normalizeEntities(entities: $entities);
            $normalized = $this->attachProhibitionMatches(entities: $normalized);

            return [
                'entities'    => $normalized,
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
     * Attach a `prohibitionMatch` field to each normalized entity
     *
     * Calls PolicyMatchService when available; returns null for every entity when
     * the service is not yet installed (before anonymisation-prohibition-gate lands).
     *
     * @param array<int, array<string, mixed>> $entities Normalized entity list
     *
     * @return array<int, array<string, mixed>> Entities with prohibitionMatch added
     *
     * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-5
     */
    private function attachProhibitionMatches(array $entities): array
    {
        $policyService = $this->tryGetPolicyMatchService();
        $threshold     = $this->getHighConfidenceThreshold();

        foreach ($entities as &$entity) {
            $entity['prohibitionMatch'] = $this->computeProhibitionMatch(
                entity: $entity,
                policyService: $policyService,
                threshold: $threshold
            );
        }

        return $entities;

    }//end attachProhibitionMatches()

    /**
     * Compute the prohibitionMatch value for a single entity
     *
     * @param array<string, mixed> $entity        Normalized entity
     * @param mixed                $policyService PolicyMatchService instance or null
     * @param float                $threshold     High-confidence threshold (inclusive)
     *
     * @return array<string, mixed>|null Match object or null
     *
     * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-6
     */
    private function computeProhibitionMatch(array $entity, mixed $policyService, float $threshold): ?array
    {
        if ($policyService === null) {
            return null;
        }

        try {
            $match = $policyService->matchProhibition(
                entityType: (string) ($entity['type'] ?? ''),
                entityValue: (string) ($entity['value'] ?? '')
            );
        } catch (\Throwable $e) {
            $this->logger->debug(
                'PolicyMatchService::matchProhibition threw; returning null',
                ['exception' => $e->getMessage()]
            );
            return null;
        }

        if ($match === null) {
            return null;
        }

        $confidence = (float) ($entity['confidence'] ?? 0.0);

        return [
            'ruleId'         => $match['ruleId'] ?? null,
            'ruleName'       => $match['ruleName'] ?? null,
            'highConfidence' => $confidence >= $threshold,
        ];

    }//end computeProhibitionMatch()

    /**
     * Try to get PolicyMatchService from the container without throwing
     *
     * Returns null when the service is not registered (before anonymisation-prohibition-gate lands).
     *
     * @return mixed PolicyMatchService instance or null
     */
    private function tryGetPolicyMatchService(): mixed
    {
        try {
            return $this->container->get('OCA\DocuDesk\Service\PolicyMatchService');
        } catch (\Throwable) {
            return null;
        }

    }//end tryGetPolicyMatchService()

    /**
     * Read the high-confidence threshold from app config
     *
     * Default 0.85; configurable via docudesk.prohibition.high_confidence_threshold.
     *
     * @return float Threshold value (inclusive boundary)
     *
     * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-7
     */
    private function getHighConfidenceThreshold(): float
    {
        return $this->appConfig->getValueFloat(
            app: 'docudesk',
            key: self::HIGH_CONFIDENCE_THRESHOLD_KEY,
            default: self::DEFAULT_HIGH_CONFIDENCE_THRESHOLD
        );

    }//end getHighConfidenceThreshold()

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
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-4
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
