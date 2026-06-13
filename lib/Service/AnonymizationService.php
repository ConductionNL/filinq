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
 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-3
 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-4
 * @spec openspec/changes/enhanced-anonymization/specs/anonymization/spec.md
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-3
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-4
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-5
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-6
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-7
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use Exception;
use OCA\DocuDesk\Exception\ConversionFailedException;
use OCA\DocuDesk\Exception\ProhibitionGateException;
use RuntimeException;
use Throwable;
use OCP\App\IAppManager;
use OCP\Files\File;
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
 *
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-4
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-3
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
     * Register slug for the prohibition override audit schema.
     *
     * @var string
     */
    private const OVERRIDE_AUDIT_REGISTER = 'consent';

    /**
     * Schema slug for the prohibition override audit entries.
     *
     * @var string
     */
    private const OVERRIDE_AUDIT_SCHEMA = 'prohibitionOverrideAudit';

    /**
     * App config key controlling the gate's fail-mode for backend errors.
     *
     * When `true` (default) any backend error inside the prohibition gate
     * (PolicyMatchService unavailable, EntityRelationMapper lookup throws,
     * per-entity matchProhibition throws) is treated as gate-firing: the
     * call is rejected via ProhibitionGateException. This is the safety-
     * critical default for a gate protecting witness/undercover-officer
     * identities — silent fail-open would let any service outage disable
     * the gate.
     *
     * Set to `false` to opt into the legacy fail-open behaviour for non-
     * production environments.
     *
     * @var string
     */
    private const FAIL_CLOSED_KEY = 'prohibition.fail_closed';

    /**
     * Default for the fail-closed flag.
     *
     * @var bool
     */
    private const DEFAULT_FAIL_CLOSED = true;

    /**
     * Constructor for AnonymizationService
     *
     * @param LoggerInterface           $logger             Logger for error reporting
     * @param ContainerInterface        $container          Container for dependency injection
     * @param IAppManager               $appManager         App manager interface
     * @param EntityDetectionService    $entityDetection    Entity detection and mapping service
     * @param IAppConfig                $appConfig          App configuration for threshold settings
     * @param ConsentCrudService        $consentCrud        Consent CRUD service for register/schema config
     * @param ConsentService            $consentService     Consent service for creating publication consents
     * @param GrondslagenSummaryService $grondslagenSummary Renderer for the per-document grondslagen
     *                                                      summary page (Wave 4a — opt-in via
     *                                                      `appendBasisSummary: true` on the request).
     * @param FileEntityStatsService    $fileEntityStats    Service for entity statistics and risk levels.
     * @param PdfConversionService      $pdfConversion      Cascade orchestrator that converts the
     *                                                      anonymised intermediate to PDF when
     *                                                      `outputFormat: "pdf"` is in effect.
     *
     * @return void
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ContainerInterface $container,
        private readonly IAppManager $appManager,
        private readonly EntityDetectionService $entityDetection,
        private readonly IAppConfig $appConfig,
        private readonly ConsentCrudService $consentCrud,
        private readonly ConsentService $consentService,
        private readonly GrondslagenSummaryService $grondslagenSummary,
        private readonly FileEntityStatsService $fileEntityStats,
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
     * The response also includes a `riskLevel` field derived from OpenRegister's
     * RiskLevelService, or 'none' when that service is unavailable.
     *
     * @param int $fileId The Nextcloud file ID
     *
     * @return array<string, mixed> Extraction result with entities, entityCount, riskLevel
     *
     * @throws Exception If extraction or detection fails
     *
     * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-5
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-3
     * @spec openspec/changes/enhanced-anonymization/specs/anonymization/spec.md
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

            $riskLevelService = $this->fileEntityStats->tryGetRiskLevelService();
            $riskLevel        = $this->fileEntityStats->getFileRiskLevel(
                fileId: $fileId,
                riskLevelService: $riskLevelService
            );

            return [
                'entities'    => $normalized,
                'entityCount' => count($entities),
                'riskLevel'   => $riskLevel,
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
     * appended as an extra page; for non-PDF output a separate
     * `<base>_grondslagen.pdf` is written alongside. Summary failure
     * is non-fatal: the anonymised file is always preserved and a `warning`
     * field is added to the response instead (HTTP 200).
     *
     * When outputFormat is "pdf" (default), the anonymised intermediate is run
     * through the PdfConversionService cascade and replaced with the PDF; on
     * cascade failure the intermediate is rolled back (best-effort) and a
     * ConversionFailedException is thrown for the controller to surface as
     * HTTP 422. "preserve" skips conversion and keeps the native format.
     *
     * When unredactedEntities is non-empty, a publicationConsent record is
     * created for each entry AFTER the anonymise pipeline succeeds. The
     * createdConsents[] field in the response aggregates the resulting records.
     *
     * @param int                              $fileId                The Nextcloud file ID
     * @param array<array<string, mixed>>      $entities              The entities to anonymize
     * @param bool                             $appendBasisSummary    Whether to append a grondslagen summary (default false)
     * @param string                           $outputFormat          Output format: 'pdf' (default) or 'preserve'
     * @param array<int, array<string, mixed>> $unredactedEntities    Entities to publish unredacted with consent creation
     * @param array<int, array<string, mixed>> $acknowledgedOverrides Override entries {ruleId, entityId, reason?} that
     *                                                                release low-confidence prohibition matches.
     * @param string                           $userId                UID of the acting user (for override audit entries).
     *
     * @return array<string, mixed> Anonymization result with optional warning/summaryFileId/createdConsents fields
     *
     * @throws Exception                  If anonymization fails.
     * @throws ConversionFailedException  When `$outputFormat === "pdf"` and the cascade could not
     *                                    convert the anonymised intermediate. The intermediate
     *                                    is deleted (best-effort) before the exception propagates.
     * @throws ProhibitionGateException   When the prohibition gate fires (high-confidence matches
     *                                    missing or invalid overrides for high-confidence matches).
     *
     * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-2
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-4
     * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-3
     * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-4
     * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-3
     * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-4
     */
    public function anonymizeDocument(
        int $fileId,
        array $entities,
        bool $appendBasisSummary=false,
        string $outputFormat='pdf',
        array $unredactedEntities=[],
        array $acknowledgedOverrides=[],
        string $userId=''
    ): array {
        // Prohibition gate — runs BEFORE any OR interaction.
        // Throws ProhibitionGateException when gate fires; passes through otherwise.
        $this->runProhibitionGate(
            fileId: $fileId,
            requestEntities: $entities,
            acknowledgedOverrides: $acknowledgedOverrides,
            userId: $userId
        );

        try {
            $fileService    = $this->getOpenRegisterService(className: 'OCA\OpenRegister\Service\FileService');
            $node           = $fileService->getFileById($fileId);
            $mappedEntities = $this->entityDetection->mapEntitiesForAnonymization($entities);

            // Capture a textual projection of the ORIGINAL document BEFORE
            // anonymization so we can compute which mapped entity values
            // were actually present (and therefore eligible to be
            // replaced by str_ireplace inside OpenRegister's
            // DocumentProcessingHandler). Closes #286.
            $originalText = $this->readNodeTextSafely(node: $node);

            $result = $fileService->anonymizeDocument($node, $mappedEntities);

            // Derive the REAL replacement-stats from the original text:
            // an entity counts as "applied" iff its literal value
            // (case-insensitive, matching str_ireplace semantics in
            // OR's DocumentProcessingHandler) was present in the
            // source. Entities that weren't present cannot have been
            // replaced and are surfaced as `unmatchedEntities` so a
            // reviewer sees the truth instead of a fabricated count.
            $verification = $this->verifyReplacements(
                mappedEntities: $mappedEntities,
                originalText: $originalText
            );

            $this->logger->info(
                'Document anonymized',
                [
                    'fileId'                => $fileId,
                    'replacementsAttempted' => $verification['replacementsAttempted'],
                    'replacementsApplied'   => $verification['replacementsApplied'],
                    'replacementsVerified'  => $verification['replacementsVerified'],
                    'unmatchedCount'        => count($verification['unmatchedEntities']),
                ]
            );

            if ($verification['replacementsVerified'] === true
                && $verification['replacementsAttempted'] !== $verification['replacementsApplied']
            ) {
                $this->logger->warning(
                    'Anonymization replacement-count discrepancy: not all sent entities were '
                    .'found literally in the source text',
                    [
                        'fileId'                => $fileId,
                        'replacementsAttempted' => $verification['replacementsAttempted'],
                        'replacementsApplied'   => $verification['replacementsApplied'],
                        'unmatchedEntities'     => $verification['unmatchedEntities'],
                    ]
                );
            }

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

            // Surface the truth: `replacementsAttempted` is how many
            // entities we forwarded to OR; `replacementsApplied` is
            // how many of those actually appeared (and were therefore
            // replaced) in the source text. `replacementsVerified`
            // signals whether we could read the source as text at all
            // (binary formats like PDF/DOCX cannot be verified at
            // this layer — see readNodeTextSafely()). When verified
            // is false, `replacementsApplied` is null and callers
            // MUST NOT treat the older `replacementCount` field as
            // ground truth.
            $resultInfo['replacementsAttempted'] = $verification['replacementsAttempted'];
            $resultInfo['replacementsApplied']   = $verification['replacementsApplied'];
            $resultInfo['replacementsVerified']  = $verification['replacementsVerified'];
            $resultInfo['unmatchedEntities']     = $verification['unmatchedEntities'];

            // Legacy field for backwards compatibility. When we
            // could verify, this now reflects what was ACTUALLY
            // replaced (no longer the fabricated `count($mappedEntities)`
            // that #286 flagged). When we couldn't verify (binary
            // format), we fall back to the attempted count and the
            // `replacementsVerified=false` flag tells callers it's
            // unconfirmed.
            $resultInfo['replacementCount'] = $verification['replacementsApplied'] ?? $verification['replacementsAttempted'];

            if (empty($unredactedEntities) === false) {
                $resultInfo = $this->createConsentsForUnredactedEntities(
                    resultInfo: $resultInfo,
                    unredactedEntities: $unredactedEntities
                );
            }

            if ($appendBasisSummary === true) {
                $resultInfo = $this->attachGrondslagenSummary(
                    anonymisedNode: $result,
                    sourceFileId: $fileId,
                    resultInfo: $resultInfo
                );
            }

            // Persist / update the source↔anonymised file mapping so the
            // relationship is queryable via OR's search API in both
            // directions and re-anonymisation overwrites a single record.
            // Success path only: guard on a known anonymised file id.
            if (empty($resultInfo['anonymizedFileId']) === false) {
                $resultInfo = $this->recordAnonymizationLink(
                    fileId: $fileId,
                    sourceNode: $node,
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
     * Run the prohibition gate before forwarding to OpenRegister.
     *
     * Resolves detected entities for the file, matches each against active
     * prohibition rules, validates acknowledgedOverrides, checks that
     * high-confidence matches are present in the to-be-anonymised set, and
     * commits validated overrides (DocuDesk audit entry + OR PATCH). Throws
     * ProhibitionGateException when the gate blocks the call.
     *
     * @param int                              $fileId                Nextcloud file ID.
     * @param array<int, array<string, mixed>> $requestEntities       User-submitted entities[] to anonymize.
     * @param array<int, array<string, mixed>> $acknowledgedOverrides Override entries {ruleId, entityId, reason?}.
     * @param string                           $userId                UID of the acting user.
     *
     * @return void
     *
     * @throws ProhibitionGateException When the gate blocks the call.
     *
     * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-3
     * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-4
     * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-6
     * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-7
     */
    public function runProhibitionGate(
        int $fileId,
        array $requestEntities,
        array $acknowledgedOverrides=[],
        string $userId=''
    ): void {
        $failClosed = $this->getFailClosed();

        $policyService = $this->tryGetPolicyMatchService();
        if ($policyService === null) {
            // PolicyMatchService not available — fail-CLOSED by default for
            // a privacy-critical safety gate. Silent fail-open would let
            // any service outage disable witness/undercover-officer
            // protection. Operators can opt into legacy fail-open via
            // docudesk.prohibition.fail_closed=false in non-production
            // environments.
            if ($failClosed === true) {
                $this->logger->warning(
                    'ProhibitionGate: PolicyMatchService unavailable — failing closed',
                    ['fileId' => $fileId]
                );
                throw new ProhibitionGateException(
                    missingProhibitionMatches: [],
                    rejectedOverrides: [],
                    backendUnavailable: 'PolicyMatchService unavailable'
                );
            }

            $this->logger->warning(
                'ProhibitionGate: PolicyMatchService unavailable — fail-open (legacy mode)',
                ['fileId' => $fileId]
            );
            return;
        }

        $threshold = $this->getHighConfidenceThreshold();

        // Load detected entities from the file.
        try {
            $entityRelationMapper = $this->getOpenRegisterService(
                className: 'OCA\OpenRegister\Db\EntityRelationMapper'
            );
            $rawEntities          = $entityRelationMapper->findEntitiesForFile($fileId);
        } catch (\Throwable $e) {
            if ($failClosed === true) {
                $this->logger->warning(
                    'ProhibitionGate: failed to load entities for file — failing closed',
                    ['fileId' => $fileId, 'error' => $e->getMessage()]
                );
                throw new ProhibitionGateException(
                    missingProhibitionMatches: [],
                    rejectedOverrides: [],
                    backendUnavailable: 'EntityRelationMapper unavailable: '.$e->getMessage()
                );
            }

            $this->logger->warning(
                'ProhibitionGate: failed to load entities for file — fail-open (legacy mode)',
                ['fileId' => $fileId, 'error' => $e->getMessage()]
            );
            return;
        }

        // Build prohibition matches: [ruleId, ruleName, entityId, entityRelationId, confidence, entityValue].
        $matches = $this->buildProhibitionMatches(
            rawEntities: $rawEntities,
            policyService: $policyService
        );

        if (empty($matches) === true) {
            return;
        }

        // Validate acknowledgedOverrides and split into released / rejected.
        $released = [];
        $rejected = [];

        foreach ($acknowledgedOverrides as $override) {
            $overrideRuleId   = (string) ($override['ruleId'] ?? '');
            $overrideEntityId = (int) ($override['entityId'] ?? 0);

            // Find matching prohibition match by (ruleId, entityId).
            $foundMatch = null;
            foreach ($matches as $match) {
                if ($match['ruleId'] === $overrideRuleId
                    && (int) $match['entityId'] === $overrideEntityId
                ) {
                    $foundMatch = $match;
                    break;
                }
            }

            // Non-matching combination: silently ignore.
            if ($foundMatch === null) {
                continue;
            }

            // High-confidence match: override is rejected.
            if ((float) $foundMatch['confidence'] >= $threshold) {
                $rejected[] = [
                    'ruleId'   => $overrideRuleId,
                    'entityId' => $overrideEntityId,
                    'reason'   => 'override not allowed for high-confidence matches',
                ];
                continue;
            }

            // Low-confidence match: override is valid — mark as released.
            $released[$overrideRuleId.'|'.$overrideEntityId] = [
                'match'  => $foundMatch,
                'reason' => (string) ($override['reason'] ?? ''),
            ];
        }//end foreach

        // Build the request entity value set for fast lookup.
        $requestValues = [];
        foreach ($requestEntities as $ent) {
            $val = (string) ($ent['value'] ?? $ent['text'] ?? '');
            if ($val !== '') {
                $requestValues[mb_strtolower($val)] = true;
            }
        }

        // Identify high-confidence matches that are missing from entities[].
        $missing = [];
        foreach ($matches as $match) {
            $key = $match['ruleId'].'|'.(int) $match['entityId'];
            if (isset($released[$key]) === true) {
                // Released by a valid override — skip.
                continue;
            }

            if ((float) $match['confidence'] < $threshold) {
                // Low-confidence, no override, not required.
                continue;
            }

            // High-confidence match — must be in entities[].
            $entityValueLower = mb_strtolower((string) ($match['entityValue'] ?? ''));
            if (isset($requestValues[$entityValueLower]) === true) {
                // Present — gate passes for this match.
                continue;
            }

            $entityName = $this->tryGetEntityCanonicalName(entityId: (int) $match['entityId']);

            $fallbackName = (string) ($match['entityValue'] ?? '');
            if ($entityName !== '') {
                $resolvedEntityName = $entityName;
            } else {
                $resolvedEntityName = $fallbackName;
            }

            $missing[] = [
                'entityId'   => (int) $match['entityId'],
                'entityName' => $resolvedEntityName,
                'ruleId'     => $match['ruleId'],
                'ruleName'   => $match['ruleName'],
                'confidence' => (float) $match['confidence'],
            ];
        }//end foreach

        if (empty($missing) === false || empty($rejected) === false) {
            $this->logger->warning(
                'ProhibitionGate: 422 — prohibition gate fired',
                [
                    'fileId'                => $fileId,
                    'missingCount'          => count($missing),
                    'rejectedOverrideCount' => count($rejected),
                    'ruleIds'               => array_column($missing, 'ruleId'),
                    'entityIds'             => array_column($missing, 'entityId'),
                ]
            );

            throw new ProhibitionGateException(
                missingProhibitionMatches: $missing,
                rejectedOverrides: $rejected
            );
        }

        // Gate passes — commit validated overrides.
        if (empty($released) === false) {
            $this->commitOverrides(
                released: $released,
                fileId: $fileId,
                userId: $userId
            );
        }

    }//end runProhibitionGate()

    /**
     * Build prohibition matches from raw EntityRelation data.
     *
     * For each raw entity, calls PolicyMatchService::matchProhibition and
     * collects matches into a structured list.
     *
     * @param array<int, mixed> $rawEntities   Raw EntityRelation rows from findEntitiesForFile.
     * @param mixed             $policyService PolicyMatchService instance.
     *
     * @return array<int, array<string, mixed>> Match entries with ruleId, ruleName, entityId,
     *                                          entityRelationId, confidence, entityValue.
     *
     * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-3
     */
    private function buildProhibitionMatches(array $rawEntities, mixed $policyService): array
    {
        $matches    = [];
        $failClosed = $this->getFailClosed();

        foreach ($rawEntities as $raw) {
            if (is_object($raw) === true && method_exists($raw, 'jsonSerialize') === true) {
                $entityData = $raw->jsonSerialize();
            } else {
                $entityData = (array) $raw;
            }

            $entityType  = (string) ($entityData['entity_type'] ?? $entityData['entityType'] ?? 'UNKNOWN');
            $entityValue = (string) ($entityData['entity_value'] ?? $entityData['entityValue'] ?? '');
            $confidence  = (float) ($entityData['confidence'] ?? 0.0);
            $entityId    = (int) ($entityData['entity_id'] ?? $entityData['entityId'] ?? 0);
            $relationId  = (int) ($entityData['relation_id'] ?? $entityData['relationId'] ?? 0);

            if ($entityValue === '') {
                continue;
            }

            try {
                $match = $policyService->matchProhibition(
                    entityType: $entityType,
                    entityValue: $entityValue
                );
            } catch (\Throwable $e) {
                // Per-entity match failure: when fail-closed, escalate so
                // runProhibitionGate can surface a 422/503 rather than
                // silently skipping the entity (which would allow the
                // anonymise call to proceed without a check).
                if ($failClosed === true) {
                    $this->logger->warning(
                        'ProhibitionGate: matchProhibition threw — failing closed',
                        [
                            'entityId'   => $entityId,
                            'entityType' => $entityType,
                            'exception'  => $e->getMessage(),
                        ]
                    );
                    throw new ProhibitionGateException(
                        missingProhibitionMatches: [],
                        rejectedOverrides: [],
                        backendUnavailable: 'PolicyMatchService::matchProhibition threw: '.$e->getMessage()
                    );
                }

                $this->logger->debug(
                    'ProhibitionGate: matchProhibition threw; skipping entity (legacy fail-open)',
                    ['exception' => $e->getMessage()]
                );
                continue;
            }

            if ($match === null) {
                continue;
            }

            $matches[] = [
                'ruleId'           => (string) ($match['ruleId'] ?? ''),
                'ruleName'         => (string) ($match['ruleName'] ?? ''),
                'entityId'         => $entityId,
                'entityRelationId' => $relationId,
                'confidence'       => $confidence,
                'entityValue'      => $entityValue,
            ];
        }//end foreach

        return $matches;

    }//end buildProhibitionMatches()

    /**
     * Read the fail-closed flag from app config.
     *
     * Defaults to TRUE — the gate fails closed by default for any backend
     * outage path. Operators can flip to false for non-production via
     * docudesk.prohibition.fail_closed.
     *
     * @return bool
     */
    private function getFailClosed(): bool
    {
        return $this->appConfig->getValueBool(
            app: 'docudesk',
            key: self::FAIL_CLOSED_KEY,
            default: self::DEFAULT_FAIL_CLOSED
        );

    }//end getFailClosed()

    /**
     * Commit validated override entries: write audit + PATCH OR skip flag.
     *
     * Processes overrides sequentially. Writes the DocuDesk audit entry BEFORE
     * the OR PATCH for each override. On OR PATCH failure, stops processing
     * further overrides and throws RuntimeException (HTTP 500).
     *
     * @param array<string, array<string, mixed>> $released Validated overrides keyed by 'ruleId|entityId'.
     * @param int                                 $fileId   Nextcloud file ID (for audit entry).
     * @param string                              $userId   UID of the acting user.
     *
     * @return void
     *
     * @throws RuntimeException When an OR PATCH fails.
     *
     * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-6
     */
    private function commitOverrides(array $released, int $fileId, string $userId): void
    {
        $now = (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM);

        try {
            $objectService        = $this->getOpenRegisterService(
                className: 'OCA\OpenRegister\Service\ObjectService'
            );
            $entityRelationMapper = $this->getOpenRegisterService(
                className: 'OCA\OpenRegister\Db\EntityRelationMapper'
            );
        } catch (\Throwable $e) {
            $this->logger->warning(
                'ProhibitionGate: OR services unavailable — skipping override commit',
                ['error' => $e->getMessage()]
            );
            return;
        }

        foreach ($released as $override) {
            $match      = $override['match'];
            $reason     = $override['reason'];
            $relationId = (int) ($match['entityRelationId'] ?? 0);

            // Step 1: Write DocuDesk audit entry BEFORE OR PATCH.
            $auditEntry = [
                'ruleId'           => $match['ruleId'],
                'entityRelationId' => $relationId,
                'fileId'           => $fileId,
                'reason'           => $reason,
                'acknowledgedBy'   => $userId,
                'acknowledgedAt'   => $now,
            ];

            try {
                $objectService->saveObject(
                    object: $auditEntry,
                    register: self::OVERRIDE_AUDIT_REGISTER,
                    schema: self::OVERRIDE_AUDIT_SCHEMA
                );
            } catch (\Throwable $e) {
                $this->logger->error(
                    'ProhibitionGate: failed to write audit entry — aborting override commit',
                    [
                        'ruleId'     => $match['ruleId'],
                        'relationId' => $relationId,
                        'fileId'     => $fileId,
                        'error'      => $e->getMessage(),
                    ]
                );
                // AVG Art. 30 / 10-year archival: an override permanently
                // released into OpenRegister with no DocuDesk audit record
                // is a compliance violation. If the audit write fails we
                // MUST NOT proceed to the OR PATCH — fail-closed.
                throw new RuntimeException(
                    'ProhibitionGate: refusing to commit override without audit entry: '.$e->getMessage(),
                    500,
                    $e
                );
            }

            // Step 2: PATCH OR EntityRelation with skipAnonymization=true.
            if ($relationId > 0) {
                try {
                    $entityRelationMapper->updateDecisionMetadata(
                        $relationId,
                        ['skipAnonymization' => true]
                    );
                } catch (\Throwable $e) {
                    $this->logger->error(
                        'ProhibitionGate: OR PATCH failed — stopping override processing',
                        [
                            'ruleId'     => $match['ruleId'],
                            'relationId' => $relationId,
                            'fileId'     => $fileId,
                            'error'      => $e->getMessage(),
                        ]
                    );
                    throw new RuntimeException(
                        'ProhibitionGate: failed to update EntityRelation skip flag: '.$e->getMessage(),
                        500,
                        $e
                    );
                }//end try
            }//end if
        }//end foreach

    }//end commitOverrides()

    /**
     * Try to get the canonical name of an OR Entity record.
     *
     * Best-effort: returns empty string when OR is unavailable or the entity
     * has no canonical name field. The gate falls back to the detected text
     * when this returns empty.
     *
     * @param int $entityId OR Entity record ID.
     *
     * @return string Canonical name, or empty string on failure.
     *
     * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-4
     */
    private function tryGetEntityCanonicalName(int $entityId): string
    {
        if ($entityId <= 0) {
            return '';
        }

        try {
            $objectService = $this->getOpenRegisterService(
                className: 'OCA\OpenRegister\Service\ObjectService'
            );
            $entity        = $objectService->find(
                id: (string) $entityId,
                register: 'entities',
                schema: 'entity'
            );

            if (is_array($entity) === false) {
                return '';
            }

            return (string) (
                $entity['canonicalName'] ?? $entity['canonical_name'] ?? $entity['name'] ?? $entity['displayName'] ?? $entity['primaryName'] ?? ''
            );
        } catch (\Throwable) {
            return '';
        }//end try

    }//end tryGetEntityCanonicalName()

    /**
     * Check unredacted entities against publication-prohibition rules.
     *
     * Returns an array of violation records (one per matching entity).
     * An empty array means no violations — all entries may proceed to consent creation.
     * Uses PolicyMatchService at any confidence (operator made an explicit decision;
     * the 0.85-threshold logic of the regular gate does NOT apply here — D2).
     *
     * @param array<int, array<string, mixed>> $unredactedEntities Entries from the unredactedEntities[] payload field
     *
     * @return array<int, array<string, mixed>> Violation records: [{entityId, entityText, ruleId, ruleName}]
     *
     * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-2
     */
    public function checkUnredactedProhibitions(array $unredactedEntities): array
    {
        $policyService = $this->tryGetPolicyMatchService();
        if ($policyService === null) {
            return [];
        }

        $violations = [];
        foreach ($unredactedEntities as $entry) {
            $entityType = (string) ($entry['entityType'] ?? '');
            $entityText = (string) ($entry['entityText'] ?? '');

            try {
                $match = $policyService->matchProhibition(
                    entityType: $entityType,
                    entityValue: $entityText
                );
            } catch (\Throwable $e) {
                $this->logger->debug(
                    'PolicyMatchService::matchProhibition threw during unredacted check; skipping',
                    ['exception' => $e->getMessage()]
                );
                continue;
            }

            if ($match !== null) {
                $violations[] = [
                    'entityId'   => $entry['entityId'] ?? null,
                    'entityText' => $entityText,
                    'ruleId'     => $match['ruleId'] ?? null,
                    'ruleName'   => $match['ruleName'] ?? null,
                ];
            }
        }//end foreach

        return $violations;

    }//end checkUnredactedProhibitions()

    /**
     * Create publicationConsent records for each unredacted entity after a successful anonymise run.
     *
     * Calls ConsentService::createConsentRequest() once per entry. Any consent-creation
     * failure is logged but does NOT abort the response — the consent failure is surfaced as
     * a structured error entry in createdConsents[].
     *
     * @param array<string, mixed>             $resultInfo         Current anonymization result
     * @param array<int, array<string, mixed>> $unredactedEntities Validated unredacted-entity entries
     *
     * @return array<string, mixed> Result enriched with createdConsents[] field
     *
     * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-3
     * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-4
     */
    private function createConsentsForUnredactedEntities(
        array $resultInfo,
        array $unredactedEntities
    ): array {
        $config = $this->consentCrud->getConsentConfig();
        if ($config === null) {
            $this->logger->warning(
                'Publication consent register/schema not configured; skipping consent creation for unredacted entities.'
            );
            $resultInfo['createdConsents'] = [];
            return $resultInfo;
        }

        $documentId      = (string) ($resultInfo['anonymizedFileId'] ?? '');
        $createdConsents = [];

        foreach ($unredactedEntities as $entry) {
            $entityText = (string) ($entry['entityText'] ?? '');
            $entityType = (string) ($entry['entityType'] ?? '');
            $extra      = [
                'publicationBases' => $entry['publicationBases'] ?? [],
            ];

            if (empty($entry['contactEmail']) === false) {
                $extra['contactEmail'] = (string) $entry['contactEmail'];
            }

            if (empty($entry['contactAddress']) === false) {
                $extra['contactAddress'] = (string) $entry['contactAddress'];
            }

            try {
                $consent = $this->consentService->createConsentRequest(
                    documentId: $documentId,
                    entityType: $entityType,
                    entityText: $entityText,
                    register: $config['register'],
                    schema: $config['schema'],
                    extra: $extra
                );

                $createdConsents[] = [
                    'entityId'      => $entry['entityId'] ?? null,
                    'entityText'    => $entityText,
                    'consentId'     => $consent['id'] ?? $consent['uuid'] ?? null,
                    'consentStatus' => $consent['consentStatus'] ?? 'pending',
                    'action'        => 'created',
                ];
            } catch (Exception $e) {
                $this->logger->error(
                    'Failed to create consent for unredacted entity: '.$e->getMessage(),
                    ['entityText' => $entityText, 'exception' => $e]
                );
                $createdConsents[] = [
                    'entityId'   => $entry['entityId'] ?? null,
                    'entityText' => $entityText,
                    'action'     => 'failed',
                    'error'      => 'Consent creation failed.',
                ];
            }//end try
        }//end foreach

        $resultInfo['createdConsents'] = $createdConsents;
        return $resultInfo;

    }//end createConsentsForUnredactedEntities()

    /**
     * Read a Nextcloud file node's content as text, safely.
     *
     * Returns the raw content for text-like MIME types (text/*,
     * application/json, application/xml, text/csv, …). Returns null for
     * binary formats (PDF, DOCX, XLSX, …) where the file content is a
     * compressed/encoded container and entity values are NOT findable
     * literally with str_ipos. Callers MUST treat a null return as
     * "verification not possible" and surface a `replacementsVerified=false`
     * flag rather than silently reporting zero or all replacements.
     *
     * @param mixed $node Nextcloud file node (OCP\Files\File or compatible)
     *
     * @return string|null Text content, or null when the node is binary /
     *                     unreadable / not a file.
     *
     * @spec issue #286 — derive replacementCount from real result
     */
    private function readNodeTextSafely(mixed $node): ?string
    {
        if (is_object($node) === false) {
            return null;
        }

        try {
            // We need both a content reader AND a MIME-type oracle to
            // know whether the bytes will be findable as literal text.
            if (method_exists($node, 'getMimeType') === false
                || method_exists($node, 'getContent') === false
            ) {
                return null;
            }

            $mimeType = (string) $node->getMimeType();

            $textLike = (str_starts_with($mimeType, 'text/') === true
                || $mimeType === 'application/json'
                || $mimeType === 'application/xml'
                || $mimeType === 'application/x-yaml'
                || $mimeType === 'application/x-ndjson'
            );

            if ($textLike === false) {
                return null;
            }

            $content = $node->getContent();
            if (is_string($content) === false || $content === '') {
                return null;
            }

            return $content;
        } catch (\Throwable $e) {
            $this->logger->debug(
                'Could not read node content for replacement verification; falling back to '
                .'unverified count: '.$e->getMessage(),
                ['exception' => $e]
            );
            return null;
        }//end try

    }//end readNodeTextSafely()

    /**
     * Compute real replacement statistics for an anonymization run.
     *
     * For each mapped entity, check whether its literal text is present
     * (case-insensitive, mirroring OR's str_ireplace semantics) in the
     * original source text. Entities that are not present cannot have
     * been replaced — they are surfaced as `unmatchedEntities`. When the
     * source text could not be read at all (binary format such as PDF /
     * DOCX — see readNodeTextSafely()), verification is reported as
     * impossible (`replacementsVerified=false`, `replacementsApplied=null`).
     *
     * @param array<int, array<string, mixed>> $mappedEntities Entities sent to OR
     * @param string|null                      $originalText   Textual projection of the
     *                                                         original source, or null
     *                                                         when the file is binary.
     *
     * @return array{
     *     replacementsAttempted: int,
     *     replacementsApplied: int|null,
     *     replacementsVerified: bool,
     *     unmatchedEntities: array<int, array{text: string, entityType: string}>
     * }
     *
     * @spec issue #286 — surface attempted vs applied + unmatched list
     */
    private function verifyReplacements(array $mappedEntities, ?string $originalText): array
    {
        $attempted = count($mappedEntities);

        if ($originalText === null) {
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
            if ($text === '') {
                continue;
            }

            // Case-insensitive search via mb_stripos with explicit UTF-8
            // encoding mirrors the str_ireplace semantics used in OR's
            // DocumentProcessingHandler::replaceWordsInTextDocument while
            // being safe for multibyte content.
            $found = mb_stripos($originalText, $text, 0, 'UTF-8');

            if ($found !== false) {
                $applied++;
                continue;
            }

            $unmatched[] = [
                'text'       => $text,
                'entityType' => (string) ($entity['entityType'] ?? 'UNKNOWN'),
            ];
        }//end foreach

        return [
            'replacementsAttempted' => $attempted,
            'replacementsApplied'   => $applied,
            'replacementsVerified'  => true,
            'unmatchedEntities'     => $unmatched,
        ];

    }//end verifyReplacements()

    /**
     * Persist or update the mapping between a source file and its anonymised counterpart.
     *
     * Idempotent UPSERT keyed on `sourceFileId`: the first successful
     * anonymisation of a file creates an `anonymizationLink` object in the
     * `document` register; every subsequent re-anonymisation of the same
     * source file updates that same record (preserving its `@self`, which
     * triggers OpenRegister's update path) and increments `runCount`. Both
     * `sourceFileId` and `anonymizedFileId` are facetable on the schema so
     * OR's search API resolves the link in both directions.
     *
     * Best-effort: the anonymised file already exists and the run has
     * succeeded, so a persistence failure here MUST NOT abort or alter the
     * response. Failures are caught, logged at warning level, and the
     * unmodified `$resultInfo` is returned (without an `anonymizationLinkId`
     * key). This mirrors attachGrondslagenSummary().
     *
     * @param int                  $fileId     The source (unanonymised) Nextcloud file ID.
     * @param mixed                $sourceNode The source file node (used for name/path/owner metadata).
     * @param array<string, mixed> $resultInfo Current result; carries anonymizedFileId/Name/Path + replacementCount.
     *
     * @return array<string, mixed> The `$resultInfo`, enriched with `anonymizationLinkId` on success.
     */
    private function recordAnonymizationLink(int $fileId, mixed $sourceNode, array $resultInfo): array
    {
        try {
            $objectService = $this->getOpenRegisterService(
                className: 'OCA\OpenRegister\Service\ObjectService'
            );

            $results = $objectService->searchObjects(
                query: [
                    '@self'        => [
                        'register' => 'document',
                        'schema'   => 'anonymizationLink',
                    ],
                    'sourceFileId' => $fileId,
                ]
            );

            $existing = [];
            if (is_array($results) === true && empty($results) === false) {
                $existing = $this->extractLinkObjectData(candidate: $results[0]);
            }

            if (empty($existing) === false) {
                $object = $existing;
                $object['runCount'] = ((int) ($existing['runCount'] ?? 0) + 1);
            } else {
                $object = [
                    '@self'        => [
                        'register' => 'document',
                        'schema'   => 'anonymizationLink',
                    ],
                    'sourceFileId' => $fileId,
                    'runCount'     => 1,
                ];
            }

            $object = $this->applySourceNodeMetadata(object: $object, sourceNode: $sourceNode);

            // Anonymised-side metadata + run stats. Only successful runs
            // reach this method, so status is always 'anonymized'.
            $anonymizedName = (string) ($resultInfo['anonymizedFileName'] ?? '');
            $object['anonymizedFileId']   = (int) $resultInfo['anonymizedFileId'];
            $object['anonymizedFileName'] = $anonymizedName;
            $object['anonymizedFilePath'] = (string) ($resultInfo['anonymizedFilePath'] ?? '');
            $object['status']           = 'anonymized';
            $object['replacementCount'] = (int) ($resultInfo['replacementCount'] ?? 0);
            $object['anonymizedAt']     = date(format: 'c');

            $extension = strtolower(pathinfo($anonymizedName, PATHINFO_EXTENSION));
            if (in_array($extension, ['pdf', 'docx', 'odt', 'txt', 'html'], true) === true) {
                $object['outputFormat'] = $extension;
            }

            $saved  = $objectService->saveObject(
                object: $object,
                register: 'document',
                schema: 'anonymizationLink'
            );
            $linkId = $this->extractSavedObjectId(saved: $saved);
            if ($linkId !== null) {
                $resultInfo['anonymizationLinkId'] = $linkId;
            }

            $this->logger->info(
                'Anonymisation link recorded',
                [
                    'sourceFileId'     => $fileId,
                    'anonymizedFileId' => $object['anonymizedFileId'],
                    'runCount'         => $object['runCount'],
                ]
            );
        } catch (Throwable $e) {
            $this->logger->warning(
                'recordAnonymizationLink failed; anonymisation result is unaffected: '.$e->getMessage(),
                ['fileId' => $fileId, 'exception' => $e]
            );
        }//end try

        return $resultInfo;

    }//end recordAnonymizationLink()

    /**
     * Apply best-effort source-node metadata (name, path, owner) to a link object.
     *
     * Each accessor is guarded with method_exists so the method tolerates any
     * file-node-like object (and mocks in unit tests) without fataling.
     *
     * @param array<string, mixed> $object     The link object being built.
     * @param mixed                $sourceNode The source file node.
     *
     * @return array<string, mixed> The object with any resolvable source metadata applied.
     */
    private function applySourceNodeMetadata(array $object, mixed $sourceNode): array
    {
        if (is_object($sourceNode) === false) {
            return $object;
        }

        if (method_exists(object_or_class: $sourceNode, method: 'getName') === true) {
            $object['sourceFileName'] = (string) $sourceNode->getName();
        }

        if (method_exists(object_or_class: $sourceNode, method: 'getPath') === true) {
            $object['sourceFilePath'] = (string) $sourceNode->getPath();
        }

        $owner = null;
        if (method_exists(object_or_class: $sourceNode, method: 'getOwner') === true) {
            $owner = $sourceNode->getOwner();
        }

        if ($owner !== null && method_exists(object_or_class: $owner, method: 'getUID') === true) {
            $object['anonymizedBy'] = (string) $owner->getUID();
        }

        return $object;

    }//end applySourceNodeMetadata()

    /**
     * Normalise a searchObjects() candidate to a plain array including its `@self`.
     *
     * @param mixed $candidate A search result entry (array, or an OR entity object).
     *
     * @return array<string, mixed> The object data, or an empty array if it could not be read.
     */
    private function extractLinkObjectData(mixed $candidate): array
    {
        if (is_array($candidate) === true) {
            return $candidate;
        }

        if (is_object($candidate) === true) {
            if (method_exists(object_or_class: $candidate, method: 'getObject') === true) {
                $payload = $candidate->getObject();
                if (is_array($payload) === true) {
                    if (isset($payload['@self']) === false
                        && method_exists(object_or_class: $candidate, method: 'getUuid') === true
                    ) {
                        $uuid = $candidate->getUuid();
                        if ($uuid !== null) {
                            $payload['@self'] = ['id' => $uuid];
                        }
                    }

                    return $payload;
                }
            }

            if (method_exists(object_or_class: $candidate, method: 'jsonSerialize') === true) {
                $payload = $candidate->jsonSerialize();
                if (is_array($payload) === true) {
                    return $payload;
                }
            }
        }//end if

        return [];

    }//end extractLinkObjectData()

    /**
     * Extract the persisted object's identifier from a saveObject() return value.
     *
     * @param mixed $saved The value returned by ObjectService::saveObject.
     *
     * @return string|null The object id/uuid, or null when it cannot be determined.
     */
    private function extractSavedObjectId(mixed $saved): ?string
    {
        if (is_object($saved) === true) {
            if (method_exists(object_or_class: $saved, method: 'getUuid') === true) {
                $uuid = $saved->getUuid();
                if (empty($uuid) === false) {
                    return (string) $uuid;
                }
            }

            if (method_exists(object_or_class: $saved, method: 'getId') === true) {
                $id = $saved->getId();
                if (empty($id) === false) {
                    return (string) $id;
                }
            }

            if (method_exists(object_or_class: $saved, method: 'jsonSerialize') === true) {
                $saved = $saved->jsonSerialize();
            }
        }

        if (is_array($saved) === true) {
            $self = ($saved['@self'] ?? []);
            $id   = ($self['id'] ?? ($self['uuid'] ?? ($saved['id'] ?? null)));
            if (empty($id) === false) {
                return (string) $id;
            }
        }

        return null;

    }//end extractSavedObjectId()

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
