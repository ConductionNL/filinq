<?php
/**
 * Anonymization Service
 *
 * Service for orchestrating the document anonymization pipeline:
 * text extraction with entity detection, and anonymization.
 * Uses OpenRegister services for text extraction and entity recognition.
 * Delegates entity detection logic to EntityDetectionService, the
 * publication-prohibition gate and policy pass to ProhibitionPolicyService,
 * and the whole per-document anonymise pipeline (EML branch, PDF-output gate,
 * replacement statistics, grondslagen summary and post-run persistence) to
 * DocumentAnonymizeRunner.
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
 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-3
 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-4
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
 * @spec openspec/changes/files-confidential-labels/specs/files-confidential-labels/spec.md
 */
class AnonymizationService
{
    /**
     * Constructor for AnonymizationService
     *
     * @param LoggerInterface                 $logger               Logger for error reporting
     * @param ContainerInterface              $container            Container for dependency injection
     * @param OpenRegisterServiceLocator      $locator              Resolver for OpenRegister services and
     *                                                              mappers.
     * @param EntityDetectionService          $entityDetection      Entity detection and mapping service
     * @param CustomDictionaryDetectionRunner $dictionaryRunner     The custom-dictionary detection pass
     *                                                              (custom-dictionary-recognition
     *                                                              design.md §D3). Best-effort — it
     *                                                              returns a warning string rather than
     *                                                              throwing, so OpenRegister's own
     *                                                              detections always survive.
     * @param FileEntityStatsService          $fileEntityStats      Service for entity statistics and risk
     *                                                              levels.
     * @param ConfidentialityLabelService     $confidentialityLabel Reads a file's existing
     *                                                              files_confidential TSCP/BAILS
     *                                                              classification (availability-guarded;
     *                                                              null when absent) so it can be
     *                                                              surfaced alongside detected entities
     *                                                              and risk (files-confidential-labels).
     * @param ProhibitionPolicyService        $prohibitionPolicy    Publication-policy decisions on detected
     *                                                              entities and skip requests, plus the
     *                                                              pre-anonymise prohibition gate.
     * @param DocumentAnonymizeRunner         $anonymizeRunner      The per-document anonymise pipeline.
     *
     * @return void
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ContainerInterface $container,
        private readonly OpenRegisterServiceLocator $locator,
        private readonly EntityDetectionService $entityDetection,
        private readonly CustomDictionaryDetectionRunner $dictionaryRunner,
        private readonly FileEntityStatsService $fileEntityStats,
        private readonly ConfidentialityLabelService $confidentialityLabel,
        private readonly ProhibitionPolicyService $prohibitionPolicy,
        private readonly DocumentAnonymizeRunner $anonymizeRunner
    ) {

    }//end __construct()

    /**
     * Extract text from a file and detect entities, resuming from cache.
     *
     * This is the resume-friendly, DB-cached lookup: when the file is unchanged,
     * OpenRegister's `isSourceUpToDate` short-circuit returns the existing chunks
     * and `EntityRelation` rows (with their skip/bases decisions) instead of
     * re-detecting, so re-opening a concept picks up where the operator left off
     * and does not append duplicate relations. The file's mtime already triggers a
     * re-extract when the source itself changed; see
     * {@see reExtractAndDetectEntities()} for an explicit re-analysis.
     *
     * @param int $fileId The Nextcloud file ID
     *
     * @return array<string, mixed> Extraction result with entities, entityCount, riskLevel
     *
     * @throws Exception If extraction or detection fails
     *
     * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-5
     * @spec openspec/specs/anonymization/spec.md
     * @spec openspec/changes/files-confidential-labels/specs/files-confidential-labels/spec.md#requirement-surface-the-label-in-the-document-report-and-entity-review-context-req-ddfcl-002
     */
    public function extractAndDetectEntities(int $fileId): array
    {
        return $this->runExtraction(fileId: $fileId, options: ['force' => false]);

    }//end extractAndDetectEntities()

    /**
     * Force a fresh extraction + detection even when the file is unchanged.
     *
     * Identical to {@see extractAndDetectEntities()} except that OpenRegister's
     * `isSourceUpToDate` short-circuit is bypassed, so the document is re-chunked
     * and re-detected from scratch.
     *
     * @param int $fileId The Nextcloud file ID
     *
     * @return array<string, mixed> Extraction result with entities, entityCount, riskLevel
     *
     * @throws Exception If extraction or detection fails
     *
     * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-5
     * @spec openspec/specs/anonymization/spec.md
     */
    public function reExtractAndDetectEntities(int $fileId): array
    {
        return $this->runExtraction(fileId: $fileId, options: ['force' => true]);

    }//end reExtractAndDetectEntities()

    /**
     * Shared implementation behind both extraction entry points.
     *
     * Each entity in the response includes a `prohibitionMatch` field: null when
     * no publication-prohibition rule matches, or an object with ruleId, ruleName,
     * and highConfidence (score >= configured threshold, inclusive).
     *
     * The response also includes a `riskLevel` field derived from OpenRegister's
     * RiskLevelService, or 'none' when that service is unavailable.
     *
     * The response also includes a `customDictionaryWarning` field: null when the
     * custom-dictionary matching pass (organisation-managed term lists,
     * `CUSTOM_DICTIONARY` entities) ran without error, or a human-readable warning
     * string when it failed. OpenRegister's own detections are always returned
     * regardless — the pass is best-effort (custom-dictionary-recognition §D3).
     *
     * When the file carries a `files_confidential` TSCP/BAILS confidentiality label
     * (availability-guarded — see ConfidentialityLabelService), the response also
     * includes `confidentialityLabel` (display string) and `confidentialityLevel`
     * (normalised int). Both are omitted when no label resolves — a read-only
     * signal, never a block/gate/redaction (files-confidential-labels).
     *
     * @param int                  $fileId  The Nextcloud file ID
     * @param array<string, mixed> $options Run options; `force` (bool) bypasses
     *                                      OpenRegister's up-to-date short-circuit.
     *
     * @return array<string, mixed> Extraction result with entities, entityCount, riskLevel
     *
     * @throws Exception If extraction or detection fails
     *
     * @spec openspec/specs/anonymization/spec.md
     */
    private function runExtraction(int $fileId, array $options): array
    {
        $force = $options['force'];

        try {
            $textExtractor = $this->locator->get(
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

            // Custom-dictionary pass (custom-dictionary-recognition, design.md
            // §D3): runs after OR extraction and before the entities are read
            // back below, so freshly-written CUSTOM_DICTIONARY relations are
            // included in `$entities`. Best-effort — a failure here is logged
            // and surfaced as a warning but never blocks OR's own detection.
            $dictionaryWarning = $this->dictionaryRunner->run(
                fileId: $fileId,
                entityTypeWhitelist: $entityTypes
            );

            $entityRelationMapper = $this->locator->get(
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
            $normalized = $this->prohibitionPolicy->applyPolicyDecisions(
                entities: $normalized,
                entityRelationMapper: $entityRelationMapper
            );

            return $this->buildExtractionResult(
                fileId: $fileId,
                normalized: $normalized,
                entityCount: count($entities),
                dictionaryWarning: $dictionaryWarning
            );
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

    }//end runExtraction()

    /**
     * Assemble the extraction response payload.
     *
     * Adds the risk level derived from OpenRegister's RiskLevelService and, when
     * the file carries one, the read-only `files_confidential` TSCP/BAILS
     * confidentiality signal. Both are surfaced alongside entities, never
     * blocking detection (files-confidential-labels, design.md D2).
     *
     * @param int                              $fileId            The Nextcloud file ID.
     * @param array<int, array<string, mixed>> $normalized        Normalized, policy-decorated entities.
     * @param int                              $entityCount       Raw detected-entity count.
     * @param string|null                      $dictionaryWarning Warning from the best-effort
     *                                                            custom-dictionary pass, or null.
     *
     * @return array<string, mixed> The extraction result payload.
     *
     * @spec openspec/changes/files-confidential-labels/specs/files-confidential-labels/spec.md#requirement-surface-the-label-in-the-document-report-and-entity-review-context-req-ddfcl-002
     */
    private function buildExtractionResult(
        int $fileId,
        array $normalized,
        int $entityCount,
        ?string $dictionaryWarning
    ): array {
        // Development riskLevel feature (RiskLevelService via
        // FileEntityStatsService) — required by the return payload below.
        $riskLevelService = $this->fileEntityStats->tryGetRiskLevelService();
        $riskLevel        = $this->fileEntityStats->getFileRiskLevel(
            fileId: $fileId,
            riskLevelService: $riskLevelService
        );

        $result = [
            'entities'                => $normalized,
            'entityCount'             => $entityCount,
            'riskLevel'               => $riskLevel,
            'customDictionaryWarning' => $dictionaryWarning,
        ];

        // Read-only sensitivity signal from files_confidential (or null when the
        // app is absent / the file is unlabelled / no vocabulary match).
        $confidentialityLabel = $this->confidentialityLabel->getLabelForFile($fileId);
        if ($confidentialityLabel !== null) {
            $result['confidentialityLabel'] = $confidentialityLabel->getLabel();
            $result['confidentialityLevel'] = $confidentialityLabel->getLevel();
        }

        return $result;

    }//end buildExtractionResult()

    /**
     * Guard + apply a per-relation skip/include decision from the review UI.
     *
     * Setting `skipAnonymization = true` on a prohibition-matched relation is
     * guarded per {@see ProhibitionSkipTier::classify}; include / non-skip
     * decisions are always allowed. A blocked decision performs no OpenRegister
     * write.
     *
     * @param int        $relationId The EntityRelation id.
     * @param bool       $skip       The requested skipAnonymization value.
     * @param array|null $bases      Optional bases to set alongside the decision.
     * @param bool       $force      Release a sub-threshold prohibition match.
     *
     * @return array{status: 200|404|422, body: array<string, mixed>} HTTP status + response body.
     *
     * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-6
     */
    public function applyRelationSkipDecision(int $relationId, bool $skip, ?array $bases, bool $force): array
    {
        return $this->prohibitionPolicy->applyRelationSkipDecision(
            relationId: $relationId,
            skip: $skip,
            bases: $bases,
            force: $force
        );

    }//end applyRelationSkipDecision()

    /**
     * Defence-in-depth backstop: absolute prohibition matches left un-redacted.
     *
     * Returns any prohibition-matched occurrence at confidence >= threshold that
     * is being left un-redacted (skipped).
     *
     * @param int $fileId The Nextcloud file id.
     *
     * @return array<int, array<string, mixed>> Absolute-tier violations (may be empty).
     *
     * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-7
     */
    public function absoluteProhibitionViolations(int $fileId): array
    {
        return $this->prohibitionPolicy->absoluteProhibitionViolations($fileId);

    }//end absoluteProhibitionViolations()

    /**
     * Check unredacted entities against publication-prohibition rules.
     *
     * Returns an array of violation records (one per matching entity). An empty
     * array means no violations — all entries may proceed to consent creation.
     *
     * @param array<int, array<string, mixed>> $unredactedEntities Entries from the request's unredactedEntities field
     *
     * @return array<int, array<string, mixed>> Violation records: [{entityId, entityText, ruleId, ruleName}]
     *
     * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-2
     */
    public function checkUnredactedProhibitions(array $unredactedEntities): array
    {
        return $this->prohibitionPolicy->checkUnredactedProhibitions(
            unredactedEntities: $unredactedEntities
        );

    }//end checkUnredactedProhibitions()

    /**
     * Run the prohibition gate before forwarding to OpenRegister.
     *
     * Matches the file's detected entities against the active prohibition rules,
     * validates acknowledged overrides, requires every high-confidence match to be
     * in the to-be-anonymised set, and commits the validated overrides.
     *
     * @param int                              $fileId          Nextcloud file ID.
     * @param array<int, array<string, mixed>> $requestEntities User-submitted entities to anonymize.
     * @param array<int, array<string, mixed>> $overrides       Override entries {ruleId, entityId, reason?}.
     * @param string                           $userId          UID of the acting user.
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
        array $overrides=[],
        string $userId=''
    ): void {
        $this->prohibitionPolicy->runGate(
            fileId: $fileId,
            requestEntities: $requestEntities,
            overrides: $overrides,
            userId: $userId
        );

    }//end runProhibitionGate()

    /**
     * Anonymize entities in a document.
     *
     * No grondslagen summary is produced; see
     * {@see anonymizeDocumentWithBasisSummary()} for the summary-producing variant.
     *
     * When outputFormat is "pdf-only" (default) or "pdf", the anonymised
     * intermediate is run through the PdfConversionService cascade and replaced
     * with the PDF; on cascade failure the intermediate is rolled back
     * (best-effort) and a ConversionFailedException is thrown for the controller
     * to surface as HTTP 422. "pdf-only" additionally best-effort deletes the
     * native anonymised intermediate after a successful conversion so only the PDF
     * remains; "pdf" keeps it too; "preserve" skips conversion entirely.
     *
     * EML inputs are routed to OpenRegister's dedicated anonymise-EML API and
     * assembled into a PDF/A-3b by EmlPdfAssemblyService (OR's anonymizeDocument
     * throws on message/rfc822); "preserve" is overridden to PDF for EML.
     *
     * When unredactedEntities is non-empty, a publicationConsent record is
     * created for each entry AFTER the anonymise pipeline succeeds. The
     * `createdConsents` field in the response aggregates the resulting records.
     *
     * @param int                              $fileId             The Nextcloud file ID
     * @param array<array<string, mixed>>      $entities           The entities to anonymize
     * @param string                           $outputFormat       Output format: 'pdf-only' (default), 'pdf'
     *                                                             or 'preserve'
     * @param array<int, array<string, mixed>> $unredactedEntities Entities to publish unredacted with consent
     *                                                             creation
     * @param array<int, array<string, mixed>> $overrides          Acknowledged override entries {ruleId,
     *                                                             entityId, reason?} that release
     *                                                             low-confidence prohibition matches.
     * @param string                           $userId             UID of the acting user (for override audit
     *                                                             entries).
     * @param string                           $scope              Placeholder-numbering scope forwarded to
     *                                                             OpenRegister: 'document' (default) or
     *                                                             'dossier' (consistent numbering across the
     *                                                             dossier folder).
     * @param string|null                      $dossierKey         Stable folder id for the dossier when
     *                                                             $scope='dossier'; null lets OpenRegister
     *                                                             fall back to the file's parent folder.
     *
     * @return array<string, mixed> Anonymization result with optional warning/createdConsents fields
     *
     * @throws Exception                 If anonymization fails.
     * @throws ConversionFailedException When `$outputFormat` requests a PDF and the cascade could
     *                                   not convert the anonymised intermediate. The intermediate
     *                                   is deleted (best-effort) before the exception propagates.
     * @throws ProhibitionGateException  When the prohibition gate fires (high-confidence matches
     *                                   missing or invalid overrides for high-confidence matches).
     *
     * @spec openspec/specs/anonymization/spec.md
     * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-3
     * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-4
     * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-3
     * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-4
     */
    public function anonymizeDocument(
        int $fileId,
        array $entities,
        string $outputFormat='pdf-only',
        array $unredactedEntities=[],
        array $overrides=[],
        string $userId='',
        string $scope='document',
        ?string $dossierKey=null
    ): array {
        return $this->runAnonymize(
            fileId: $fileId,
            entities: $entities,
            userId: $userId,
            overrides: $overrides,
            options: [
                'appendBasisSummary' => false,
                'outputFormat'       => $outputFormat,
                'unredactedEntities' => $unredactedEntities,
                'scope'              => $scope,
                'dossierKey'         => $dossierKey,
            ]
        );

    }//end anonymizeDocument()

    /**
     * Anonymize entities in a document AND append the grondslagen summary.
     *
     * Identical to {@see anonymizeDocument()} except that LegalBasesSummaryService
     * is invoked after the anonymised file has been written. For PDF output the
     * summary is appended as an extra page; otherwise a separate
     * `<base>_grondslagen.pdf` is written alongside. Summary failure is non-fatal:
     * the anonymised file is always preserved and a `warning` field is added to the
     * response instead (HTTP 200).
     *
     * @param int                              $fileId             The Nextcloud file ID
     * @param array<array<string, mixed>>      $entities           The entities to anonymize
     * @param string                           $outputFormat       Output format: 'pdf-only' (default), 'pdf'
     *                                                             or 'preserve'
     * @param array<int, array<string, mixed>> $unredactedEntities Entities to publish unredacted with consent
     *                                                             creation
     * @param array<int, array<string, mixed>> $overrides          Acknowledged override entries {ruleId,
     *                                                             entityId, reason?}.
     * @param string                           $userId             UID of the acting user.
     * @param string                           $scope              Placeholder-numbering scope forwarded to
     *                                                             OpenRegister.
     * @param string|null                      $dossierKey         Stable folder id for the dossier.
     *
     * @return array<string, mixed> Anonymization result with optional
     *                              warning/summaryFileId/createdConsents fields
     *
     * @throws Exception                 If anonymization fails.
     * @throws ConversionFailedException When the PDF cascade is exhausted.
     * @throws ProhibitionGateException  When the prohibition gate fires.
     *
     * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-2
     * @spec openspec/specs/anonymization/spec.md
     */
    public function anonymizeDocumentWithBasisSummary(
        int $fileId,
        array $entities,
        string $outputFormat='pdf-only',
        array $unredactedEntities=[],
        array $overrides=[],
        string $userId='',
        string $scope='document',
        ?string $dossierKey=null
    ): array {
        return $this->runAnonymize(
            fileId: $fileId,
            entities: $entities,
            userId: $userId,
            overrides: $overrides,
            options: [
                'appendBasisSummary' => true,
                'outputFormat'       => $outputFormat,
                'unredactedEntities' => $unredactedEntities,
                'scope'              => $scope,
                'dossierKey'         => $dossierKey,
            ]
        );

    }//end anonymizeDocumentWithBasisSummary()

    /**
     * Shared implementation behind both anonymise entry points.
     *
     * The prohibition gate runs BEFORE any OpenRegister interaction; the pipeline
     * itself is owned by DocumentAnonymizeRunner.
     *
     * @param int                              $fileId    The Nextcloud file ID.
     * @param array<array<string, mixed>>      $entities  The entities to anonymize.
     * @param string                           $userId    UID of the acting user.
     * @param array<int, array<string, mixed>> $overrides Acknowledged override entries.
     * @param array<string, mixed>             $options   Run options forwarded to the runner
     *                                                    (appendBasisSummary, outputFormat,
     *                                                    unredactedEntities, scope, dossierKey).
     *
     * @return array<string, mixed> The anonymisation result.
     *
     * @throws Exception                 If anonymization fails.
     * @throws ConversionFailedException When the PDF cascade is exhausted.
     * @throws ProhibitionGateException  When the prohibition gate fires.
     *
     * @spec openspec/specs/anonymization/spec.md
     * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-3
     */
    private function runAnonymize(
        int $fileId,
        array $entities,
        string $userId,
        array $overrides,
        array $options
    ): array {
        // Prohibition gate — runs BEFORE any OR interaction.
        // Throws ProhibitionGateException when gate fires; passes through otherwise.
        $this->runProhibitionGate(
            fileId: $fileId,
            requestEntities: $entities,
            overrides: $overrides,
            userId: $userId
        );

        return $this->anonymizeRunner->run(
            fileId: $fileId,
            entities: $entities,
            options: $options
        );

    }//end runAnonymize()
}//end class
