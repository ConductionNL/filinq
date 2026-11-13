<?php

/**
 * Contract of AnonymizationService
 *
 * The public surface of the AnonymizationService service, so callers in other
 * components depend on this contract instead of the implementation.
 *
 * @category  Kernel
 * @package   OCA\Filinq\Kernel\Contracts
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Kernel\Contracts;

use OCA\Filinq\Exception\ConversionFailedException;
use OCA\Filinq\Exception\DetectionUnavailableException;
use Exception;
use OCA\Filinq\Exception\ProhibitionGateException;
use OCA\Filinq\Exception\RedactionNotReviewedException;

/**
 * Contract of AnonymizationService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface AnonymizationServiceInterface {
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
	public function extractAndDetectEntities(int $fileId): array;

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
	public function reExtractAndDetectEntities(int $fileId): array;

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
	public function applyRelationSkipDecision(int $relationId, bool $skip, ?array $bases, bool $force): array;

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
	public function absoluteProhibitionViolations(int $fileId): array;

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
	public function checkUnredactedProhibitions(array $unredactedEntities): array;

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
	public function runProhibitionGate(int $fileId, array $requestEntities, array $overrides = [], string $userId = ''): void;

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
	 * This contract runs with the default options. Callers inside the Service
	 * component pass AnonymizeOptions to AnonymizationService::anonymizeDocument(),
	 * or ask for the summary through anonymizeDocumentWithBasisSummary().
	 *
	 * @param int $fileId The Nextcloud file ID
	 * @param array<array<string, mixed>> $entities The entities to anonymize
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
	public function anonymizeDocument(int $fileId, array $entities): array;

	/**
	 * Anonymize a document on behalf of a named user.
	 *
	 * The same run as {@see anonymizeDocument()}, with `$userId` as the acting
	 * user, so a background job that runs for whoever started it is held to the
	 * four-eyes rule like a request from that person.
	 *
	 * @param int                         $fileId   The Nextcloud file ID
	 * @param array<array<string, mixed>> $entities The entities to anonymize
	 * @param string                      $userId   The acting user.
	 *
	 * @return array<string, mixed> Anonymization result
	 *
	 * @throws Exception If anonymization fails.
	 * @throws RedactionNotReviewedException When the review gate refuses the run.
	 *
	 * @spec openspec/changes/redaction-four-eyes-setting/tasks.md#task-3
	 */
	public function anonymizeDocumentAs(int $fileId, array $entities, string $userId): array;
}//end interface
