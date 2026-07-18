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
 * @spec openspec/specs/anonymization/spec.md
 * @spec openspec/specs/anonymization/spec.md
 * @spec openspec/specs/anonymization/spec.md
 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-3
 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-4
 * @spec openspec/specs/anonymization/spec.md
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
use OCA\DocuDesk\Service\EmlPdfAssemblyService;
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
 * @spec openspec/specs/anonymization/spec.md
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
     * @param EmlPdfAssemblyService     $emlAssembly        Assembles OR's redacted anonymise-EML
     *                                                      result into a PDF/A-3b. EML inputs are
     *                                                      routed here directly because OR's
     *                                                      `anonymizeDocument()` throws on
     *                                                      `message/rfc822`.
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
        private readonly PdfConversionService $pdfConversion,
        private readonly EmlPdfAssemblyService $emlAssembly
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
     * @spec openspec/specs/anonymization/spec.md
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
     * By default this is a resume-friendly, DB-cached lookup: when the file is
     * unchanged, OpenRegister's `isSourceUpToDate` short-circuit returns the
     * existing chunks and `EntityRelation` rows (with their skip/bases
     * decisions) instead of re-detecting — so re-opening a concept picks up
     * where the operator left off and does not append duplicate relations.
     * Pass `$force = true` for an explicit re-analysis (e.g. after changing the
     * enabled entity types); the file's mtime already triggers a re-extract
     * when the source itself changed.
     *
     * @param int  $fileId The Nextcloud file ID
     * @param bool $force  Force a fresh extraction + detection even when the
     *                     file is unchanged (default false = resume/cached).
     *
     * @return array<string, mixed> Extraction result with entities, entityCount, riskLevel
     *
     * @throws Exception If extraction or detection fails
     *
     * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-5
     * @spec openspec/specs/anonymization/spec.md
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag) Force flag mirrors the OR API.
     */
    public function extractAndDetectEntities(int $fileId, bool $force=false): array
    {
        try {
            $textExtractor = $this->getOpenRegisterService(
                className: 'OCA\OpenRegister\Service\TextExtractionService'
            );

            // Resolve DocuDesk's grondslag service up front (via the container,
            // string class name, to keep this class's coupling in check). It
            // also owns the operator's enabled-entity-type selection, used to
            // scope automatic detection just below.
            $grondslagProposal = $this->container->get('OCA\DocuDesk\Service\GrondslagProposalService');

            // Scope automatic detection to the enabled entity types (null = all
            // types). Manual entities are added through a separate path, so a
            // manually-added type is still anonymised even when its automatic
            // detection is disabled here.
            $entityTypes = $grondslagProposal->getEntityTypeWhitelist();
            $textExtractor->extractFile($fileId, $force, $entityTypes);

            $this->logger->debug(
                'Text extracted from file',
                ['fileId' => $fileId, 'entityTypes' => $entityTypes]
            );

            $entityRelationMapper = $this->getOpenRegisterService(
                className: 'OCA\OpenRegister\Db\EntityRelationMapper'
            );
            $entities = $entityRelationMapper->findEntitiesForFile($fileId);

            // Pre-fill a proposed grondslag per entity type onto the
            // freshly-detected relations (fill-only-when-empty), then enrich
            // the returned rows with their current bases so the review UI can
            // show the proposal. $grondslagProposal was resolved above. Both
            // calls are internally best-effort and never block detection.
            $grondslagProposal->applyProposals(fileId: $fileId);
            $entities = $grondslagProposal->enrichEntitiesWithBases(entities: $entities, fileId: $fileId);

            $normalized = $this->entityDetection->normalizeEntities(entities: $entities);

            // Apply publication policy (Robert's policy pass, supersedes the
            // narrower attachProhibitionMatches): standing-consent winners are
            // auto-skipped on their relation; prohibition winners get a
            // read-only `prohibitionMatch` hint for the review UI. Best-effort —
            // a policy failure never blocks detection.
            $normalized = $this->applyPolicyDecisions(
                entities: $normalized,
                entityRelationMapper: $entityRelationMapper
            );

            // Development riskLevel feature (RiskLevelService via
            // FileEntityStatsService) — required by the return payload below.
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
     * Apply publication policy to freshly-detected, normalized entities.
     *
     * Runs `PolicyMatchService::match()` (prohibition precedence) per entity:
     *  - a standing-consent winner is auto-skipped (`skip_anonymization = true`
     *    on the relation, via OpenRegister) unless it is already skipped;
     *  - a prohibition winner gets a read-only `prohibitionMatch`
     *    (`{ruleId, ruleName, highConfidence}`) for the review UI and is never
     *    auto-skipped.
     *
     * Every returned entity gains a `prohibitionMatch` key (null when none).
     * Best-effort: policy failures are logged and never block detection.
     *
     * @param array<int, array<string, mixed>> $entities             Normalized entities.
     * @param mixed                            $entityRelationMapper OpenRegister EntityRelationMapper (DI).
     *
     * @return array<int, array<string, mixed>> Entities with `prohibitionMatch` attached.
     */
    private function applyPolicyDecisions(array $entities, mixed $entityRelationMapper): array
    {
        try {
            $matcher   = $this->container->get('OCA\DocuDesk\Service\PolicyMatchService');
            $threshold = (float) $matcher->highConfidenceThreshold();
        } catch (Exception $e) {
            $this->logger->warning(
                'Policy matcher unavailable; skipping publication-policy pass',
                ['exception' => $e->getMessage()]
            );
            foreach ($entities as &$plain) {
                $plain['prohibitionMatch'] = ($plain['prohibitionMatch'] ?? null);
            }

            unset($plain);
            return $entities;
        }//end try

        foreach ($entities as &$entity) {
            $entity['prohibitionMatch'] = null;
            $value = (string) ($entity['value'] ?? '');
            $type  = (string) ($entity['type'] ?? 'OTHER');
            if ($value === '') {
                continue;
            }

            try {
                $match = $matcher->match(entityText: $value, entityType: $type);
            } catch (Exception $e) {
                $this->logger->warning('Policy match failed for entity', ['exception' => $e->getMessage()]);
                continue;
            }

            if ($match === null) {
                continue;
            }

            if ($match['kind'] === PolicyMatchService::KIND_PROHIBITION) {
                $entity['prohibitionMatch'] = [
                    'ruleId'         => $match['uuid'],
                    'ruleName'       => $match['primaryName'],
                    'highConfidence' => (((float) ($entity['confidence'] ?? 0.0)) >= $threshold),
                ];
                continue;
            }

            // Standing consent → auto-skip this occurrence unless already skipped.
            if ($match['kind'] === PolicyMatchService::KIND_STANDING_CONSENT
                && ((bool) ($entity['skipAnonymization'] ?? false)) === false
                && ($entity['relationId'] ?? null) !== null
            ) {
                try {
                    $relation = $entityRelationMapper->find((int) $entity['relationId']);
                    $entityRelationMapper->updateDecisionMetadata($relation, ['skipAnonymization' => true]);
                    $entity['skipAnonymization'] = true;
                } catch (Exception $e) {
                    $this->logger->warning(
                        'Failed to auto-skip standing-consent entity',
                        ['relationId' => $entity['relationId'], 'exception' => $e->getMessage()]
                    );
                }
            }
        }//end foreach

        unset($entity);

        return $entities;

    }//end applyPolicyDecisions()

    /**
     * Classify a skip attempt on a prohibition-matched entity.
     *
     * Pure tier logic (callers have already established it is a skip AND a
     * prohibition match): at or above the threshold the match is absolute and
     * cannot be released; below the threshold it is releasable only with force.
     *
     * @param float $confidence Detection confidence for the occurrence.
     * @param float $threshold  High-confidence threshold in effect.
     * @param bool  $force      Whether the request set force.
     *
     * @return string One of 'block_absolute', 'block_releasable', 'allow'.
     */
    public static function classifyProhibitionSkip(float $confidence, float $threshold, bool $force): string
    {
        if ($confidence >= $threshold) {
            return 'block_absolute';
        }

        if ($force === false) {
            return 'block_releasable';
        }

        return 'allow';

    }//end classifyProhibitionSkip()

    /**
     * Guard + apply a per-relation skip/include decision from the review UI.
     *
     * Setting `skipAnonymization = true` on a prohibition-matched relation is
     * guarded per {@see classifyProhibitionSkip}. Include / non-skip decisions
     * are always allowed. Allowed decisions are forwarded to OpenRegister via
     * `updateDecisionMetadata` (so OR's audit-trail records the flip). A blocked
     * decision performs no OpenRegister write.
     *
     * @param int        $relationId The EntityRelation id.
     * @param bool       $skip       The requested skipAnonymization value.
     * @param array|null $bases      Optional bases to set alongside the decision.
     * @param bool       $force      Release a sub-threshold prohibition match.
     *
     * @return array{status: 200|404|422, body: array<string, mixed>} HTTP status + response body.
     */
    public function applyRelationSkipDecision(int $relationId, bool $skip, ?array $bases, bool $force): array
    {
        $mapper = $this->getOpenRegisterService(className: 'OCA\OpenRegister\Db\EntityRelationMapper');

        try {
            $relation = $mapper->find($relationId);
        } catch (Exception $e) {
            return ['status' => 404, 'body' => ['error' => 'Entity relation not found']];
        }

        if ($skip === true) {
            $block = $this->evaluateProhibitionSkip(
                mapper: $mapper,
                relation: $relation,
                relationId: $relationId,
                force: $force
            );
            if ($block !== null) {
                return ['status' => 422, 'body' => $block];
            }
        }

        $fields = ['skipAnonymization' => $skip];
        if ($bases !== null) {
            $fields['bases'] = $bases;
        }

        $mapper->updateDecisionMetadata($relation, $fields);

        return ['status' => 200, 'body' => ['status' => 'ok', 'skipAnonymization' => $skip]];

    }//end applyRelationSkipDecision()

    /**
     * Evaluate the prohibition guard for a skip on one relation.
     *
     * Resolves the occurrence's entity value/type/confidence via the file join,
     * matches it against the prohibition cache, and classifies the skip. Returns
     * the 422 body when the skip is blocked, or null when it is allowed (not a
     * prohibition match, released by force, or the match cannot be resolved).
     *
     * @param mixed $mapper     OpenRegister EntityRelationMapper (DI).
     * @param mixed $relation   The EntityRelation being decided.
     * @param int   $relationId The relation id (for the file-join lookup + logs).
     * @param bool  $force      Whether the request set force.
     *
     * @return array<string, mixed>|null The 422 body when blocked, else null.
     */
    private function evaluateProhibitionSkip(mixed $mapper, mixed $relation, int $relationId, bool $force): ?array
    {
        $row = null;
        foreach ($mapper->findEntitiesForFile((int) $relation->getFileId()) as $candidate) {
            if ((int) ($candidate['relation_id'] ?? 0) === $relationId) {
                $row = $candidate;
                break;
            }
        }

        if ($row === null) {
            return null;
        }

        $value = (string) ($row['entity_value'] ?? '');
        $type  = (string) ($row['entity_type'] ?? 'OTHER');
        if ($value === '') {
            return null;
        }

        try {
            $matcher = $this->container->get('OCA\DocuDesk\Service\PolicyMatchService');
        } catch (Exception $e) {
            $this->logger->warning('Policy matcher unavailable; skip guard is a no-op', ['exception' => $e->getMessage()]);
            return null;
        }

        $match = $matcher->matchProhibition(entityText: $value, entityType: $type);
        if ($match === null) {
            return null;
        }

        $confidence = (float) ($row['confidence'] ?? 0.0);
        $threshold  = (float) $matcher->highConfidenceThreshold();
        $decision   = self::classifyProhibitionSkip(confidence: $confidence, threshold: $threshold, force: $force);
        if ($decision === 'allow') {
            return null;
        }

        $this->logger->warning(
            'Prohibition guard blocked a skip decision',
            [
                'ruleId'     => $match['uuid'],
                'entityId'   => (int) ($row['entity_id'] ?? 0),
                'relationId' => $relationId,
            ]
        );

        return [
            'error'            => 'Entity is on the publication prohibition list; skipping is not allowed.',
            'threshold'        => $threshold,
            'prohibitionMatch' => [
                'entityId'   => (int) ($row['entity_id'] ?? 0),
                'entityName' => $value,
                'ruleId'     => $match['uuid'],
                'ruleName'   => $match['primaryName'],
                'confidence' => $confidence,
                'absolute'   => ($decision === 'block_absolute'),
            ],
        ];

    }//end evaluateProhibitionSkip()

    /**
     * Defence-in-depth backstop: absolute prohibition matches left un-redacted.
     *
     * OpenRegister's generic relation PATCH stays open, so a caller could skip a
     * prohibited relation directly, bypassing the DocuDesk skip endpoint. Before
     * redaction, this returns any prohibition-matched occurrence at confidence
     * >= threshold that is being left un-redacted (skipped). Only the absolute
     * tier is enforced here — the primary decision-time guard covers the rest.
     *
     * "Skipped" = detected for the file but absent from the anonymise set
     * (`findEntitiesForAnonymization`, which already excludes skipAnonymization).
     *
     * @param int $fileId The Nextcloud file id.
     *
     * @return array<int, array<string, mixed>> Absolute-tier violations (may be empty).
     */
    public function absoluteProhibitionViolations(int $fileId): array
    {
        try {
            $matcher = $this->container->get('OCA\DocuDesk\Service\PolicyMatchService');
        } catch (Exception $e) {
            $this->logger->warning(
                'Policy matcher unavailable; prohibition backstop is a no-op',
                ['exception' => $e->getMessage()]
            );
            return [];
        }

        $mapper    = $this->getOpenRegisterService(className: 'OCA\OpenRegister\Db\EntityRelationMapper');
        $threshold = (float) $matcher->highConfidenceThreshold();

        $redactIds = [];
        foreach ($mapper->findEntitiesForAnonymization($fileId) as $row) {
            $redactIds[(int) ($row['relation_id'] ?? 0)] = true;
        }

        $violations = [];
        foreach ($mapper->findEntitiesForFile($fileId) as $row) {
            $relationId = (int) ($row['relation_id'] ?? 0);
            if (isset($redactIds[$relationId]) === true) {
                // Being redacted — fine.
                continue;
            }

            $value = (string) ($row['entity_value'] ?? '');
            $type  = (string) ($row['entity_type'] ?? 'OTHER');
            if ($value === '') {
                continue;
            }

            $match = $matcher->matchProhibition(entityText: $value, entityType: $type);
            if ($match === null || ((float) ($row['confidence'] ?? 0.0)) < $threshold) {
                continue;
            }

            $this->logger->warning(
                'Prohibition backstop caught an un-redacted absolute match',
                ['ruleId' => $match['uuid'], 'entityId' => (int) ($row['entity_id'] ?? 0), 'fileId' => $fileId]
            );

            $violations[] = [
                'entityId'   => (int) ($row['entity_id'] ?? 0),
                'entityName' => $value,
                'ruleId'     => $match['uuid'],
                'ruleName'   => $match['primaryName'],
                'confidence' => (float) ($row['confidence'] ?? 0.0),
                'absolute'   => true,
            ];
        }//end foreach

        return $violations;

    }//end absoluteProhibitionViolations()

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
     * When outputFormat is "pdf-only" (default) or "pdf", the anonymised
     * intermediate is run through the PdfConversionService cascade and replaced
     * with the PDF; on cascade failure the intermediate is rolled back
     * (best-effort) and a ConversionFailedException is thrown for the controller
     * to surface as HTTP 422. "pdf-only" additionally best-effort deletes the
     * native anonymised intermediate after a successful conversion so only the
     * PDF remains; "pdf" keeps the native intermediate too; "preserve" skips
     * conversion and keeps the native format.
     *
     * EML inputs are routed to OpenRegister's dedicated anonymise-EML API and
     * assembled into a PDF/A-3b by EmlPdfAssemblyService (OR's anonymizeDocument
     * throws on message/rfc822); "preserve" is overridden to PDF for EML.
     *
     * When unredactedEntities is non-empty, a publicationConsent record is
     * created for each entry AFTER the anonymise pipeline succeeds. The
     * createdConsents[] field in the response aggregates the resulting records.
     *
     * @param int                              $fileId                The Nextcloud file ID
     * @param array<array<string, mixed>>      $entities              The entities to anonymize
     * @param bool                             $appendBasisSummary    Whether to append a grondslagen summary (default false)
     * @param string                           $outputFormat          Output format: 'pdf-only' (default), 'pdf' or 'preserve'
     * @param array<int, array<string, mixed>> $unredactedEntities    Entities to publish unredacted with consent creation
     * @param array<int, array<string, mixed>> $acknowledgedOverrides Override entries {ruleId, entityId, reason?} that
     *                                                                release low-confidence prohibition matches.
     * @param string                           $userId                UID of the acting user (for override audit entries).
     * @param string                           $scope                 Placeholder-numbering scope forwarded to
     *                                                                OpenRegister: 'document' (default) or 'dossier'
     *                                                                (consistent numbering across the dossier folder).
     * @param string|null                      $dossierKey            Stable folder id for the dossier when
     *                                                                $scope='dossier'; null lets OpenRegister fall
     *                                                                back to the file's parent folder.
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
     * @spec openspec/specs/anonymization/spec.md
     * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-3
     * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-4
     * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-3
     * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-4
     */
    public function anonymizeDocument(
        int $fileId,
        array $entities,
        bool $appendBasisSummary=false,
        string $outputFormat='pdf-only',
        array $unredactedEntities=[],
        array $acknowledgedOverrides=[],
        string $userId='',
        string $scope='document',
        ?string $dossierKey=null
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

            // EML branch (eml-pdf-assembly, Robert): OR's anonymizeDocument()
            // THROWS on message/rfc822 (it no longer leaks a raw-text body). EML
            // inputs are therefore routed to OR's dedicated anonymise-EML API
            // (anonymizeEmlStructured) and assembled into a PDF/A-3b here,
            // BEFORE the standard anonymizeDocument + convertToPdf path. This
            // REPLACES that path for EML. `outputFormat: "preserve"` is
            // silently overridden to PDF for EML (design D8) — handled inside
            // anonymizeEmlToPdf because EML has no reliably-redacted native
            // form to preserve.
            if ($this->isEmlInput(node: $node) === true) {
                return $this->anonymizeEmlToPdf(
                    fileId: $fileId,
                    node: $node,
                    fileService: $fileService,
                    mappedEntities: $mappedEntities,
                    appendBasisSummary: $appendBasisSummary,
                    scope: $scope,
                    dossierKey: $dossierKey
                );
            }

            // Capture a textual projection of the ORIGINAL document BEFORE
            // anonymization so we can compute which mapped entity values
            // were actually present (and therefore eligible to be
            // replaced by str_ireplace inside OpenRegister's
            // DocumentProcessingHandler). Closes #286.
            $originalText = $this->readNodeTextSafely(node: $node);

            // Placeholder-numbering scope (anonymisation-placeholder-id-scope):
            // 'document' (default) numbers entities locally to this file;
            // 'dossier' makes the number consistent across the dossier folder's
            // files. OpenRegister derives the dossier from $dossierKey (a stable
            // folder id) or falls back to the file's parent folder when null —
            // so a folder anonymise only needs to signal scope=dossier. Passed
            // positionally for compatibility with the reflectively-resolved
            // OpenRegister FileService.
            $result = $fileService->anonymizeDocument($node, $mappedEntities, $scope, $dossierKey);

            // Best-effort policy: OpenRegister now produces the anonymised file
            // even when some entity text could not be removed (e.g. the ExApp
            // NER over-captured a span across table cells, so the value is not
            // contiguous in the document). Pull the residual list so the
            // operator can be warned and iterate (manual entities, skip
            // unselected occurrences). Defensive method_exists() guard for
            // older OpenRegister versions without the best-effort API.
            $residualEntities = [];
            if (method_exists($fileService, 'getLastResidualEntities') === true) {
                $residualEntities = $fileService->getLastResidualEntities();
            }

            // Per-entity placeholder map (anonymisation-placeholder-id-scope):
            // the EXACT placeholder OpenRegister emitted per global entity id
            // (e.g. `"7" => "[PERSOON: 1]"`), so the grondslagen-summary renders
            // the same scope-local number + localized label the document carries
            // instead of re-deriving `[<TYPE>: <entity_id>]`. Defensive
            // method_exists() for older OpenRegister versions (summary then
            // falls back to the scope-local map or omits the entity).
            $placeholderMap = [];
            if (method_exists($fileService, 'getLastPlaceholderMap') === true) {
                $placeholderMap = $fileService->getLastPlaceholderMap();
            }

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
                    // PII-free: count only, never the residual text.
                    'residualCount'         => count($residualEntities),
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

            // PDF conversion gate: when outputFormat requests a PDF
            // ('pdf-only' or 'pdf') AND the anonymised result is not
            // already a PDF, run the cascade.
            // On failure: delete the un-converted intermediate (the
            // operator must NOT see a half-finished native-format
            // output when they asked for PDF) and re-throw the typed
            // exception so the controller maps it to 422.
            // On success in 'pdf-only' mode: best-effort delete the native
            // anonymised intermediate so only the PDF remains.
            if (in_array($outputFormat, ['pdf-only', 'pdf'], true) === true && $result instanceof File === true) {
                $resultMime = (string) $result->getMimeType();
                if ($resultMime !== 'application/pdf') {
                    // Capture the native anonymised node BEFORE $result is
                    // reassigned to the converted PDF — 'pdf-only' deletes it
                    // after a successful conversion. When the result is
                    // already a PDF the cascade is skipped, so there is no
                    // native intermediate to delete and 'pdf-only' behaves
                    // identically to 'pdf'.
                    $nativeIntermediate = $result;
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
                        // that they CAN inspect (they sent a PDF
                        // outputFormat and got 422, so the expectation
                        // is "no file written"). $result still points at
                        // the un-converted native intermediate here, as
                        // the reassignment above only runs on success.
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

                    // 'pdf-only': the conversion succeeded and the PDF is the
                    // referenced output, so the native intermediate is now
                    // un-redactable leftover. Best-effort delete it; a failure
                    // here MUST NOT fail an otherwise-successful run (mirrors
                    // the rollback above). PII-free log (file id + exception
                    // metadata only).
                    if ($outputFormat === 'pdf-only') {
                        try {
                            $nativeIntermediate->delete();
                        } catch (Throwable $deleteError) {
                            $this->logger->warning(
                                'pdf-only: failed to delete native anonymised intermediate; orphaned file remains.',
                                [
                                    'fileId'    => $fileId,
                                    'exception' => get_class($deleteError),
                                    'message'   => $deleteError->getMessage(),
                                ]
                            );
                        }
                    }//end if
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

            // Surface best-effort residuals so the UI can warn that the file was
            // produced but some entities could not be fully removed, and let the
            // operator refine them. `complete` drives the warning banner.
            $resultInfo['complete']         = (count($residualEntities) === 0);
            $resultInfo['residualCount']    = count($residualEntities);
            $resultInfo['residualEntities'] = $residualEntities;

            if ($appendBasisSummary === true) {
                $resultInfo = $this->attachGrondslagenSummary(
                    anonymisedNode: $result,
                    sourceFileId: $fileId,
                    resultInfo: $resultInfo,
                    placeholderMap: $placeholderMap
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
        }//end if

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
        }//end try

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
            }//end try

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
            }//end try

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
     * Whether a file node is an EML (email) input.
     *
     * Detected by MIME `message/rfc822` or a `.eml` extension. EML inputs are
     * routed to the dedicated anonymise-EML + assembly path.
     *
     * @param mixed $node The source file node.
     *
     * @return bool True when the node is an EML message.
     */
    private function isEmlInput(mixed $node): bool
    {
        if (is_object($node) === false) {
            return false;
        }

        if (method_exists($node, 'getMimeType') === true
            && (string) $node->getMimeType() === 'message/rfc822'
        ) {
            return true;
        }

        if (method_exists($node, 'getName') === true) {
            $name = (string) $node->getName();
            $dot  = strrpos($name, '.');
            if ($dot !== false && strtolower(substr($name, ($dot + 1))) === 'eml') {
                return true;
            }
        }

        return false;

    }//end isEmlInput()

    /**
     * Anonymise an EML input via OR's anonymise-EML API and assemble the
     * redacted result into a PDF/A-3b written beside the source.
     *
     * This is the EML replacement for the standard anonymizeDocument +
     * convertToPdf path. EML always produces a PDF — `outputFormat:
     * "preserve"` is silently overridden here (the caller is never told;
     * design D8), because OR redacts components, not a re-serialised native
     * `.eml`, so there is no native intermediate to keep. On OR API failure a
     * `ConversionFailedException` is raised with NO raw-parse fallback (design
     * D9), so the controller maps it to HTTP 422 and no un-redacted content is
     * ever written.
     *
     * @param int                             $fileId             Source Nextcloud file ID.
     * @param mixed                           $node               Source EML file node.
     * @param mixed                           $fileService        OR FileService (resolved reflectively).
     * @param array<int, array<string,mixed>> $mappedEntities     Entities to redact.
     * @param bool                            $appendBasisSummary Append the grondslagen summary to the PDF.
     * @param string                          $scope              Placeholder-numbering scope.
     * @param string|null                     $dossierKey         Stable dossier folder id, or null.
     *
     * @return array<string, mixed> The anonymisation result info (same shape the
     *                              controller expects: anonymizedFileId/Name/Path,
     *                              replacementCount, complete, residualEntities).
     *
     * @throws ConversionFailedException On OR API failure or assembly failure.
     */
    private function anonymizeEmlToPdf(
        int $fileId,
        mixed $node,
        mixed $fileService,
        array $mappedEntities,
        bool $appendBasisSummary,
        string $scope,
        ?string $dossierKey
    ): array {
        if (method_exists($fileService, 'anonymizeEmlStructured') === false) {
            throw new ConversionFailedException(
                message: 'OpenRegister does not expose the anonymise-EML API; cannot anonymise EML input.',
                attempts: [
                    [
                        'name'      => 'eml',
                        'available' => false,
                        'supports'  => true,
                        'reason'    => 'anonymizeEmlStructured not present on OpenRegister FileService',
                    ],
                ]
            );
        }

        try {
            $structure = $fileService->anonymizeEmlStructured($node, $mappedEntities, $scope, $dossierKey);
        } catch (ConversionFailedException $e) {
            throw $e;
        } catch (Throwable $e) {
            // NO raw-parse fallback — leaking un-redacted EML is the worse
            // failure. Surface as a typed conversion failure (HTTP 422).
            $this->logger->warning(
                'EML anonymise-API failed; no raw-parse fallback.',
                ['fileId' => $fileId, 'exception' => get_class($e), 'message' => $e->getMessage()]
            );
            throw new ConversionFailedException(
                message: 'OpenRegister anonymise-EML API failed: '.$e->getMessage(),
                attempts: [
                    [
                        'name'      => 'eml',
                        'available' => true,
                        'supports'  => true,
                        'reason'    => 'anonymizeEmlStructured threw: '.$e->getMessage(),
                    ],
                ],
                previous: $e
            );
        }//end try

        if (is_object($structure) === false) {
            throw new ConversionFailedException(
                message: 'OpenRegister anonymise-EML API returned no structure.',
                attempts: [
                    [
                        'name'      => 'eml',
                        'available' => true,
                        'supports'  => true,
                        'reason'    => 'anonymizeEmlStructured returned non-object',
                    ],
                ]
            );
        }

        $sourceName = '';
        if (method_exists($node, 'getName') === true) {
            $sourceName = (string) $node->getName();
        }

        // The assemble() call throws ConversionFailedException on unrecoverable
        // failure; let it propagate so the controller surfaces 422.
        $pdfBytes = $this->emlAssembly->assemble(result: $structure, sourceFilename: $sourceName);

        $parent = $node->getParent();
        // Fall back to a generic base name when the source node has no name.
        $safeName = $sourceName;
        if ($safeName === '') {
            $safeName = 'email';
        }

        $baseName   = $this->stripExtension(name: $safeName);
        $outputName = $baseName.'_anonymized.pdf';
        if ($parent->nodeExists($outputName) === true) {
            $parent->get($outputName)->delete();
        }

        $pdfNode = $parent->newFile($outputName, $pdfBytes);

        $this->logger->info(
            'EML anonymised and assembled to PDF',
            [
                'fileId'      => $fileId,
                'entityCount' => count($mappedEntities),
            ]
        );

        $resultInfo = $this->entityDetection->parseAnonymizationResult($pdfNode);

        // #286: do not fabricate replacementCount from count($mappedEntities).
        // The EML output is an assembled binary PDF, so the replacement layer
        // cannot verify how many mapped entities actually appeared in — and were
        // therefore removed from — the source text (same limitation as any
        // binary format; see readNodeTextSafely()/verifyReplacements()). Surface
        // the truth: how many were attempted, that none could be verified, and
        // let the legacy replacementCount fall back to the attempted count with
        // replacementsVerified=false telling callers it is unconfirmed. This
        // preserves the #286 anti-fabrication fix on the EML path (issue #312).
        $attemptedCount = count($mappedEntities);
        $resultInfo['replacementsAttempted'] = $attemptedCount;
        $resultInfo['replacementsApplied']   = null;
        $resultInfo['replacementsVerified']  = false;
        $resultInfo['unmatchedEntities']     = [];
        // The replacementsApplied value is null on the EML path (the assembled
        // binary PDF cannot confirm applied replacements), so the legacy
        // replacementCount deliberately falls back to the attempted count with
        // replacementsVerified=false marking it unconfirmed (#286/#312).
        $resultInfo['replacementCount'] = $resultInfo['replacementsAttempted'];
        // OR's anonymise-EML path does not surface a residual list; the
        // assembled PDF is the authoritative redacted output.
        $resultInfo['complete']         = true;
        $resultInfo['residualCount']    = 0;
        $resultInfo['residualEntities'] = [];

        if ($appendBasisSummary === true) {
            $placeholderMap = [];
            if (method_exists($fileService, 'getLastPlaceholderMap') === true) {
                $placeholderMap = $fileService->getLastPlaceholderMap();
            }

            $resultInfo = $this->attachGrondslagenSummary(
                anonymisedNode: $pdfNode,
                sourceFileId: $fileId,
                resultInfo: $resultInfo,
                placeholderMap: $placeholderMap
            );
        }

        if (empty($resultInfo['anonymizedFileId']) === false) {
            $resultInfo = $this->recordAnonymizationLink(
                fileId: $fileId,
                sourceNode: $node,
                resultInfo: $resultInfo
            );
        }

        return $resultInfo;

    }//end anonymizeEmlToPdf()

    /**
     * Return $name without its trailing `.ext`.
     *
     * @param string $name File name with extension.
     *
     * @return string Name without extension.
     */
    private function stripExtension(string $name): string
    {
        $dotPos = strrpos($name, '.');
        if ($dotPos === false) {
            return $name;
        }

        return substr($name, 0, $dotPos);

    }//end stripExtension()

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
     * @param mixed                 $anonymisedNode The Node/File returned by OR's anonymizeDocument.
     * @param int                   $sourceFileId   The pre-anonymisation source file id (used to look
     *                                              up the EntityRelation rows that carry the bases).
     * @param array<string, mixed>  $resultInfo     The current result info — extended with the
     *                                              summary's `summaryFileId` / `warning` fields and
     *                                              returned.
     * @param array<string, string> $placeholderMap OpenRegister's per-entity placeholder map
     *                                              (global entity id → emitted placeholder, e.g.
     *                                              `"7" => "[PERSOON: 1]"`) so the summary renders
     *                                              the SAME placeholder the document carries. Empty
     *                                              → summary uses its own scope-local map or omits.
     *
     * @return array<string, mixed> The (possibly-extended) result info.
     */
    private function attachGrondslagenSummary(mixed $anonymisedNode, int $sourceFileId, array $resultInfo, array $placeholderMap=[]): array
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
                    sourceFileId: $sourceFileId,
                    placeholderMap: $placeholderMap
                );
                $resultInfo['summaryAppended'] = true;
            } else {
                $summaryFile = $this->grondslagenSummary->renderSummaryBesideFile(
                    anonymisedFile: $anonymisedNode,
                    sourceFileId: $sourceFileId,
                    placeholderMap: $placeholderMap
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
