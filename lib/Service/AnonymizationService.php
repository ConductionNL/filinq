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
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
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

namespace OCA\Filinq\Service;

use Exception;
use OCA\Filinq\Exception\ConversionFailedException;
use OCA\Filinq\Exception\DetectionUnavailableException;
use OCA\Filinq\Exception\ProhibitionGateException;
use OCA\Filinq\Exception\RedactionNotReviewedException;
use OCA\Filinq\Kernel\Contracts\AnonymizationServiceInterface;
use OCA\Filinq\Service\Detection\DetectionProviderResolver;
use OCA\Filinq\Service\Detection\DetectionRequest;
use OCA\Filinq\Service\Ocr\OcrExtractionFallback;
use OCA\Filinq\Service\Redaction\RedactionOutputGuard;
use OCA\OpenRegister\Mcp\Attribute\McpTool;
use OCP\IUserSession;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Service for orchestrating the document anonymization pipeline
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/anonymization/spec.md
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-3
 * @spec openspec/changes/files-confidential-labels/specs/files-confidential-labels/spec.md
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects) The collaborator that took this
 * over the line is RedactionOutputGuard: nothing may be written until a person
 * has checked the detection run. The guard is asked here because this is the
 * class that writes; asking it anywhere else would leave a write path that does
 * not ask.
 *
 * @SuppressWarnings(PHPMD.ExcessiveParameterList) Same collaborator, same
 * constructor. Every parameter is an injected service, and grouping them behind
 * a bag would hide which of them a given instance actually needs.
 */
class AnonymizationService implements AnonymizationServiceInterface {

	/**
	 * The trigger an agent's intake run is attributed to, next to `manual` and `flow`.
	 *
	 * @var string
	 */
	public const MCP_TRIGGER = 'mcp';

	/**
	 * The reason an agent's intake is refused when a publication prohibition applies.
	 *
	 * @var string
	 */
	public const PROHIBITION_REASON = 'A publication prohibition applies to this document. '
		. 'The detection is in the review queue; a person decides there.';
	/**
	 * Constructor for AnonymizationService
	 *
	 * @param LoggerInterface $logger Logger for error reporting
	 * @param ContainerInterface $container Container for dependency injection
	 * @param OpenRegisterServiceLocator $locator Resolver for OpenRegister services and
	 *                                            mappers.
	 * @param EntityDetectionService $entityDetection Entity detection and mapping service
	 * @param CustomDictionaryDetectionRunner $dictionaryRunner The custom-dictionary detection pass
	 *                                                          (custom-dictionary-recognition
	 *                                                          design.md §D3). Best-effort — it
	 *                                                          returns a warning string rather than
	 *                                                          throwing, so OpenRegister's own
	 *                                                          detections always survive.
	 * @param FileEntityStatsService $fileEntityStats Service for entity statistics and risk
	 *                                                levels.
	 * @param ConfidentialityLabelService $confidentialityLabel Reads a file's existing
	 *                                                          files_confidential TSCP/BAILS
	 *                                                          classification (availability-guarded;
	 *                                                          null when absent) so it can be
	 *                                                          surfaced alongside detected entities
	 *                                                          and risk (files-confidential-labels).
	 * @param ProhibitionPolicyService $prohibitionPolicy Publication-policy decisions on detected
	 *                                                    entities and skip requests, plus the
	 *                                                    pre-anonymise prohibition gate.
	 * @param DocumentAnonymizeRunner $anonymizeRunner The per-document anonymise pipeline.
	 * @param RedactionOutputGuard $reviewGuard The human review gate. It sits in the service
	 *                                          rather than on the screen, so the API, the batch
	 *                                          path and the folder job all reach the same
	 *                                          refusal.
	 * @param AnonymiserBackendStateClient $backendState OpenRegister's detection state, read
	 *                                                   once per run: no live detector, no file.
	 * @param OcrExtractionFallback $ocrFallback Runs OCR on a scan OpenRegister extracted no
	 *                                           text from, and flags what detection missed.
	 * @param DetectionProviderResolver|null $detectionResolver The organisation's detection
	 *                                                          provider; null leaves OpenRegister's
	 *                                                          detection as the only one.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/archive/2026-09-29-anonymisation-fails-closed-without-a-detector/tasks.md#task-2
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
		private readonly DocumentAnonymizeRunner $anonymizeRunner,
		private readonly RedactionOutputGuard $reviewGuard,
		private readonly AnonymiserBackendStateClient $backendState,
		private readonly OcrExtractionFallback $ocrFallback,
		private readonly ?DetectionProviderResolver $detectionResolver=null,
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
	public function extractAndDetectEntities(int $fileId): array {
		return $this->runExtraction(fileId: $fileId, options: ['force' => false]);
	}//end extractAndDetectEntities()

	#[McpTool(
		name: 'anonymizeDocument',
		subject: 'document',
		action: 'anonymize',
		description: 'Start anonymisation of one document: detect the personal data in it and put it '
			. 'in the review queue, where a person decides what is redacted. Takes only the file id. '
			. 'It never produces the anonymised copy and never marks the document reviewed; it answers '
			. 'with the number of entities found per type, never their values. A publication '
			. 'prohibition on the document is answered as refused, with the reason.',
		readOnlyHint: false,
		destructiveHint: false,
		idempotentHint: false,
		scope: 'create'
	)]
	/**
	 * Anonymisation intake for an agent: detection and review-queue routing only.
	 *
	 * The file id is the whole input: no entity list, no override and no
	 * output choice, so nothing an agent sends can steer what is redacted or
	 * lift a gate. The run never writes an anonymised copy, never marks the
	 * review and never changes the source file. The answer holds counts and
	 * statuses only, never an entity value. A file the caller may not read
	 * answers not_found, like one that does not exist.
	 *
	 * @param int $fileId The Nextcloud file id.
	 *
	 * @return array<string, mixed> `status`, `entityCounts`, `reviewRequired`, and `refusedReason` when refused.
	 *
	 * @spec openspec/changes/mcp-generation-tools/tasks.md#task-1.3
	 */
	public function anonymizeViaAgent(int $fileId): array {
		$uid = $this->agentUid();
		if ($uid === '' || $this->container->get(ReadableFileResolver::class)->node(fileId: $fileId) === null) {
			return ['status' => 'not_found', 'entityCounts' => [], 'reviewRequired' => false];
		}

		$this->logger->info(
			'Filinq: anonymisation intake started',
			['fileId' => $fileId, 'triggeredBy' => self::MCP_TRIGGER, 'uid' => $uid]
		);

		try {
			$detected = $this->extractAndDetectEntities(fileId: $fileId);
		} catch (Throwable $e) {
			$this->logger->warning(
				'Filinq: anonymisation intake failed',
				['fileId' => $fileId, 'triggeredBy' => self::MCP_TRIGGER, 'uid' => $uid, 'exception' => $e->getMessage()]
			);
			return ['status' => 'failed', 'entityCounts' => [], 'reviewRequired' => false];
		}

		$counts = FileEntityStatsService::countByType(entities: (array)($detected['entities'] ?? []));

		if ($this->prohibited(fileId: $fileId) === true) {
			return [
				'status' => 'refused',
				'entityCounts' => $counts,
				'reviewRequired' => true,
				'refusedReason' => self::PROHIBITION_REASON,
			];
		}

		return ['status' => 'review_required', 'entityCounts' => $counts, 'reviewRequired' => true];

	}//end anonymizeViaAgent()

	/**
	 * Whether an absolute publication prohibition applies to the file. A gate
	 * that cannot be asked counts as applying: the agent path fails closed.
	 *
	 * @param int $fileId The file.
	 *
	 * @return bool True when the prohibition gate refuses the file.
	 *
	 * @spec openspec/changes/mcp-generation-tools/tasks.md#task-1.3
	 */
	private function prohibited(int $fileId): bool {
		try {
			return $this->absoluteProhibitionViolations(fileId: $fileId) !== [];
		} catch (Throwable $e) {
			$this->logger->warning(
				'Filinq: the prohibition gate could not be asked; the agent intake is refused',
				['fileId' => $fileId, 'exception' => $e->getMessage()]
			);
			return true;
		}

	}//end prohibited()

	/**
	 * The user the agent acts for, or an empty string without one.
	 *
	 * @return string The user id.
	 *
	 * @spec openspec/changes/mcp-generation-tools/tasks.md#task-1.3
	 */
	private function agentUid(): string {
		try {
			return (string)($this->container->get(IUserSession::class)->getUser()?->getUID() ?? '');
		} catch (Throwable $e) {
			return '';
		}

	}//end agentUid()

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
	public function reExtractAndDetectEntities(int $fileId): array {
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
	 * @param int $fileId The Nextcloud file ID
	 * @param array<string, mixed> $options Run options; `force` (bool) bypasses
	 *                                      OpenRegister's up-to-date short-circuit.
	 *
	 * @return array<string, mixed> Extraction result with entities, entityCount, riskLevel
	 *
	 * @throws Exception If extraction or detection fails
	 *
	 * @spec openspec/specs/anonymization/spec.md
	 * @spec openspec/changes/archive/2026-09-29-ocr-trigger-surface/tasks.md#task-2.3
	 */
	private function runExtraction(int $fileId, array $options): array {
		try {
			return $this->extractAndDetect(fileId: $fileId, force: $options['force']);
		} catch (Exception $e) {
			$this->logger->error(
				'Failed to extract and detect entities: ' . $e->getMessage(),
				['fileId' => $fileId, 'exception' => $e]
			);
			throw new Exception(
				'Failed to extract and detect entities: ' . $e->getMessage(),
				0,
				$e
			);
		}//end try
	}//end runExtraction()

	/**
	 * Extract the file's text, run the OCR fallback and the custom-dictionary
	 * pass, then read the detected entities back.
	 *
	 * @param int $fileId The file.
	 * @param mixed $force Whether to bypass OpenRegister's up-to-date check.
	 *
	 * @return array<string, mixed> The extraction result, with any OCR fields.
	 *
	 * @throws Exception When a step fails.
	 */
	private function extractAndDetect(int $fileId, mixed $force): array {
		$textExtractor = $this->locator->get(
			className: 'OCA\OpenRegister\Files\Service\TextExtractionService'
		);

		// Resolve Filinq's grondslag service up front (via the container,
		// string class name, to keep this class's coupling in check). It
		// also owns the operator's enabled-entity-type selection, used to
		// scope automatic detection just below.
		$legalBasisProposal = $this->container->get('OCA\Filinq\Service\LegalBasisProposalService');

		// Scope automatic detection to the enabled entity types (null = all
		// types). Manual entities are added through a separate path, so a
		// manually-added type is still anonymised even when its automatic
		// detection is disabled here.
		$entityTypes = $legalBasisProposal->getEntityTypeWhitelist();
		(new OpenRegisterTextExtractionCall())->extractFile(extractor: $textExtractor, fileId: $fileId, force: (bool) $force, entityTypes: $entityTypes);

		// OpenRegister does no OCR, so a scan comes back without text and
		// would read as "nothing to redact". The fallback runs OCR for it
		// and says on the result when detection could not see the scan.
		$ocrFields = $this->ocrFallback->afterExtraction(
			fileId: $fileId,
			textExtractor: $textExtractor,
			force: $force
		);

		$this->logger->debug(
			'Text extracted from file',
			['fileId' => $fileId, 'entityTypes' => $entityTypes]
		);

		// The organisation's detection provider (llm-entity-detection-provider):
		// OpenRegister's own detection ran during extraction; an organisation on
		// the LLM provider gets its entities added here, or an explicit fallback
		// warning when it could not run. Before the read-back below, so the LLM
		// relations are included.
		$providerFields = $this->runDetectionProvider(
			request: new DetectionRequest(fileId: $fileId, entityTypes: $entityTypes, force: (bool) $force)
		);

		// Custom-dictionary pass (custom-dictionary-recognition, design.md
		// §D3): runs after OR extraction and before the entities are read
		// back below, so freshly-written CUSTOM_DICTIONARY relations are
		// included. Best-effort — a failure here is logged and surfaced as a
		// warning but never blocks OR's own detection.
		$detected = ['customDictionaryWarning' => $this->dictionaryRunner->run(fileId: $fileId, entityTypeWhitelist: $entityTypes)];
		$detected += $this->detectedEntities(fileId: $fileId, legalBasisProposal: $legalBasisProposal);

		return array_merge(
			$this->buildExtractionResult(fileId: $fileId, detected: $detected),
			$ocrFields,
			$providerFields
		);
	}//end extractAndDetect()

	/**
	 * Run the organisation's detection provider and return the payload fields naming it.
	 *
	 * @param DetectionRequest $request The file, the enabled entity types and whether this is a re-analysis.
	 *
	 * @return array<string, mixed> `detectionProvider`, `detectionFellBack`, `detectionWarning`;
	 *                              empty when no resolver is wired.
	 *
	 * @spec openspec/changes/llm-entity-detection-provider/tasks.md#task-3.2
	 */
	private function runDetectionProvider(DetectionRequest $request): array {
		if ($this->detectionResolver === null) {
			return [];
		}

		return $this->detectionResolver->runForActiveOrganisation(request: $request)->toPayload();
	}//end runDetectionProvider()

	/**
	 * Read the file's detected entities back, with proposed bases and the
	 * publication policy applied.
	 *
	 * @param int $fileId The file.
	 * @param object $legalBasisProposal The grondslag service.
	 *
	 * @return array{entities: array<int, mixed>, entityCount: int} The normalised entities and the raw count.
	 *
	 * @throws Exception When a step fails.
	 */
	private function detectedEntities(int $fileId, object $legalBasisProposal): array {
		$entityRelationMapper = $this->locator->get(
			className: 'OCA\OpenRegister\Db\EntityRelationMapper'
		);
		$entities = $entityRelationMapper->findEntitiesForFile($fileId);

		// Pre-fill a proposed grondslag per entity type onto the
		// freshly-detected relations (fill-only-when-empty), then enrich
		// the returned rows with their current bases so the review UI can
		// show the proposal. Both calls are internally best-effort and never
		// block detection.
		$legalBasisProposal->applyProposals(fileId: $fileId);
		$entities = $legalBasisProposal->enrichEntitiesWithBases(entities: $entities, fileId: $fileId);

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

		return ['entities' => $normalized, 'entityCount' => count($entities)];
	}//end detectedEntities()

	/**
	 * Assemble the extraction response payload.
	 *
	 * Adds the risk level derived from OpenRegister's RiskLevelService and, when
	 * the file carries one, the read-only `files_confidential` TSCP/BAILS
	 * confidentiality signal. Both are surfaced alongside entities, never
	 * blocking detection (files-confidential-labels, design.md D2).
	 *
	 * @param int $fileId The Nextcloud file ID.
	 * @param array $detected The normalized, policy-decorated `entities`, the raw detected
	 *                        `entityCount` and the `customDictionaryWarning` from the
	 *                        best-effort custom-dictionary pass (or null).
	 *
	 * @return array<string, mixed> The extraction result payload.
	 *
	 * @spec openspec/changes/files-confidential-labels/specs/files-confidential-labels/spec.md#requirement-surface-the-label-in-the-document-report-and-entity-review-context-req-ddfcl-002
	 */
	private function buildExtractionResult(int $fileId, array $detected): array {
		// Development riskLevel feature (RiskLevelService via
		// FileEntityStatsService) — required by the return payload below.
		$riskLevelService = $this->fileEntityStats->tryGetRiskLevelService();
		$riskLevel = $this->fileEntityStats->getFileRiskLevel(
			fileId: $fileId,
			riskLevelService: $riskLevelService
		);

		$result = [
			'entities' => $detected['entities'],
			'entityCount' => $detected['entityCount'],
			'riskLevel' => $riskLevel,
			'customDictionaryWarning' => $detected['customDictionaryWarning'],
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
	 * @param int $relationId The EntityRelation id.
	 * @param bool $skip The requested skipAnonymization value.
	 * @param array|null $bases Optional bases to set alongside the decision.
	 * @param bool $force Release a sub-threshold prohibition match.
	 *
	 * @return array{status: 200|404|422, body: array<string, mixed>} HTTP status + response body.
	 *
	 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-6
	 */
	public function applyRelationSkipDecision(int $relationId, bool $skip, ?array $bases, bool $force): array {
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
	public function absoluteProhibitionViolations(int $fileId): array {
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
	public function checkUnredactedProhibitions(array $unredactedEntities): array {
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
	 * @param int $fileId Nextcloud file ID.
	 * @param array<int, array<string, mixed>> $requestEntities User-submitted entities to anonymize.
	 * @param array<int, array<string, mixed>> $overrides Override entries {ruleId, entityId, reason?}.
	 * @param string $userId UID of the acting user.
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
		array $overrides = [],
		string $userId = '',
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
	 * @param int $fileId The Nextcloud file ID
	 * @param array<array<string, mixed>> $entities The entities to anonymize
	 * @param AnonymizeOptions|null $options Output format, consents, overrides, acting user,
	 *                                       placeholder scope and reversibility; null for the defaults.
	 *
	 * @return array<string, mixed> Anonymization result with optional warning/createdConsents fields
	 *
	 * @throws Exception If anonymization fails.
	 * @throws ConversionFailedException When the output format requests a PDF and the cascade could
	 *                                   not convert the anonymised intermediate. The intermediate
	 *                                   is deleted (best-effort) before the exception propagates.
	 * @throws ProhibitionGateException When the prohibition gate fires (high-confidence matches
	 *                                  missing or invalid overrides for high-confidence matches).
	 * @throws RedactionNotReviewedException When nobody has checked this detection run yet.
	 * @throws DetectionUnavailableException When no live entity detector is behind the run.
	 *
	 * @spec openspec/specs/anonymization/spec.md
	 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-3
	 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-4
	 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-3
	 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-4
	 * @spec openspec/changes/archive/2026-09-29-reversible-pseudonymization/tasks.md#task-2.2
	 */
	public function anonymizeDocument(int $fileId, array $entities, ?AnonymizeOptions $options = null): array {
		return $this->runAnonymize(run: ($options ?? new AnonymizeOptions())->runFor(fileId: $fileId, entities: $entities));

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
	 * @param int $fileId The Nextcloud file ID
	 * @param array<array<string, mixed>> $entities The entities to anonymize
	 * @param AnonymizeOptions|null $options Output format, consents, overrides, acting user,
	 *                                       placeholder scope and reversibility; null for the defaults.
	 *
	 * @return array<string, mixed> Anonymization result with optional
	 *                              warning/summaryFileId/createdConsents fields
	 *
	 * @throws Exception If anonymization fails.
	 * @throws ConversionFailedException When the PDF cascade is exhausted.
	 * @throws ProhibitionGateException When the prohibition gate fires.
	 * @throws RedactionNotReviewedException When nobody has checked this detection run yet.
	 * @throws DetectionUnavailableException When no live entity detector is behind the run.
	 *
	 * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-2
	 * @spec openspec/specs/anonymization/spec.md
	 * @spec openspec/changes/archive/2026-09-29-reversible-pseudonymization/tasks.md#task-2.2
	 */
	public function anonymizeDocumentWithBasisSummary(int $fileId, array $entities, ?AnonymizeOptions $options = null): array {
		return $this->runAnonymize(run: ($options ?? new AnonymizeOptions())->summaryRunFor(fileId: $fileId, entities: $entities));

	}//end anonymizeDocumentWithBasisSummary()

	/**
	 * Shared implementation behind both anonymise entry points.
	 *
	 * The prohibition gate runs BEFORE any OpenRegister interaction; the pipeline
	 * itself is owned by DocumentAnonymizeRunner.
	 *
	 * @param AnonymizeRun $run The file, entities, overrides and run options (the acting user among them).
	 *
	 * @return array<string, mixed> The anonymisation result.
	 *
	 * @throws Exception If anonymization fails.
	 * @throws ConversionFailedException When the PDF cascade is exhausted.
	 * @throws ProhibitionGateException When the prohibition gate fires.
	 * @throws RedactionNotReviewedException When nobody has checked this detection run yet.
	 * @throws DetectionUnavailableException When no live entity detector is behind the run.
	 *
	 * @spec openspec/specs/anonymization/spec.md
	 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-3
	 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
	 * @spec openspec/changes/archive/2026-09-29-anonymisation-fails-closed-without-a-detector/tasks.md#task-2
	 */
	private function runAnonymize(AnonymizeRun $run): array {
		$fileId = $run->fileId;
		$entities = $run->entities;

		// Prohibition gate — runs BEFORE any OR interaction.
		// Throws ProhibitionGateException when gate fires; passes through otherwise.
		$this->runProhibitionGate(
			fileId: $fileId,
			requestEntities: $entities,
			overrides: $run->overrides,
			userId: $run->userId()
		);

		// 🔴 THE HUMAN GATE, IN THE SERVICE RATHER THAN ON THE SCREEN. Both
		// public entry points come through here, and so do the API, the batch
		// path and the folder job behind them, so a copy is written only after
		// somebody has looked at THIS detection run and said so. It throws
		// before the runner, which means a refused document produces no file
		// at all rather than one nobody checked.
		$this->reviewGuard->assertMayWrite(fileId: $fileId, entities: $entities);

		// THE DETECTOR GATE. Zero detections from no detector look exactly like
		// zero detections from a clean document, and the runner would file a
		// copy of the input as the anonymised document. So the state is read
		// once, here, and a run without a live detector writes nothing.
		$backend = $this->requireLiveDetector();

		$result = $this->anonymizeRunner->run(
			fileId: $fileId,
			entities: $entities,
			options: $run->options
		);

		$redacted = count($entities);
		$outcome = 'redacted';
		if ($redacted === 0) {
			$outcome = 'nothing_found';
		}

		$result['detection'] = [
			'ran' => true,
			'backend' => $backend,
			'entitiesRedacted' => $redacted,
			'outcome' => $outcome,
		];

		return $result;

	}//end runAnonymize()

	/**
	 * Read OpenRegister's detection state and refuse unless a detector is live.
	 *
	 * @return string The effective backend the run will report.
	 *
	 * @throws DetectionUnavailableException When recognition is off, the effective
	 *                                       backend is unavailable, or the state is unknown.
	 *
	 * @spec openspec/changes/archive/2026-09-29-anonymisation-fails-closed-without-a-detector/tasks.md#task-2
	 */
	private function requireLiveDetector(): string {
		$state = $this->backendState->getState();
		$reason = $this->backendState->refusalReason(state: $state);
		$backend = (string)($state['effectiveMethod'] ?? '');

		if ($reason === null) {
			return $backend;
		}

		$messages = [
			AnonymiserBackendStateClient::REFUSE_UNKNOWN => 'Anonymisation refused: filinq could not read which entity detector is live.',
			AnonymiserBackendStateClient::REFUSE_DISABLED => 'Anonymisation refused: entity detection is disabled on this instance.',
			AnonymiserBackendStateClient::REFUSE_UNAVAILABLE => 'Anonymisation refused: the entity detector "' . $backend . '" is unavailable.',
		];

		throw new DetectionUnavailableException(
			reason: $reason,
			message: $messages[$reason],
			backend: $backend
		);

	}//end requireLiveDetector()
}//end class
