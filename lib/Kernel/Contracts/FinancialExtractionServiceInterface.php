<?php

/**
 * Contract of FinancialExtractionService
 *
 * The public surface of the FinancialExtractionService service, so callers in other
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

use RuntimeException;

/**
 * Contract of FinancialExtractionService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface FinancialExtractionServiceInterface {
	/**
	 * Run the full extraction pipeline for a request and persist the result.
	 *
	 * @param array<string, mixed> $data Request body: `fileId|documentUri`, `docType`, `callbackEvent`.
	 * @param string $requestedBy Nextcloud user id that initiated the extraction.
	 *
	 * @return array<string, mixed> The persisted `financialExtraction` object.
	 *
	 * @throws RuntimeException (code 400) On missing file reference or invalid docType.
	 *
	 * @spec openspec/specs/financial-document-field-extraction/spec.md
	 */
	public function extractFinancial(array $data, string $requestedBy): array;

	/**
	 * Store human-corrected field values against an existing extraction.
	 *
	 * Additive/non-destructive: the original `fields` object is never
	 * mutated, corrections are appended to `corrections[]` (REQ-FIN-07).
	 *
	 * @param string $id The `financialExtraction` object id.
	 * @param array<string, mixed> $correctedFields Map of field name to corrected value.
	 * @param string $correctedBy Nextcloud user id submitting the corrections.
	 *
	 * @return array<string, mixed> The updated `financialExtraction` object.
	 *
	 * @throws RuntimeException (code 404) When no extraction exists for the given id.
	 *
	 * @spec openspec/specs/financial-document-field-extraction/spec.md
	 */
	public function addCorrection(string $id, array $correctedFields, string $correctedBy): array;

	/**
	 * Run the deterministic heuristic pipeline against extracted text.
	 *
	 * Pure given its input (no I/O): shapes the full REQ-FIN-03 field set
	 * (missing fields null, never omitted), assigns per-field confidence,
	 * and reconciles totals.
	 *
	 * @param string $text The extracted document text.
	 * @param string $docType `receipt` or `supplier-invoice`; part of the
	 *                        REQ-FIN-01 pipeline signature and reserved for
	 *                        a future docType-specific heuristic tuning pass
	 *                        — the current heuristics apply uniformly.
	 *
	 * @return array{fields: array<string, mixed>, fieldConfidence: array<string, float>, overallConfidence: float, reconciled: bool}
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter) $docType reserved for future per-type heuristic tuning
	 *
	 * @spec openspec/specs/financial-document-field-extraction/spec.md
	 */
	public function runExtraction(string $text, string $docType): array;

	/**
	 * Apply an optional AI-backend enhancement pass to fill null/low-confidence
	 * fields (REQ-FIN-06). Absent-safe: returns the input unchanged when no
	 * provider is available or the AI call fails for any reason.
	 *
	 * @param string $text The extracted document text.
	 * @param array<string, mixed> $result The heuristic-only pipeline result
	 *                                     (`{fields, fieldConfidence,
	 *                                     overallConfidence, reconciled}`,
	 *                                     see {@see runExtraction()}).
	 * @param string $requestedBy Nextcloud user id (task quota attribution).
	 *
	 * @return array<string, mixed> The (possibly AI-enhanced) pipeline result, same shape as the input.
	 *
	 * @spec openspec/specs/financial-document-field-extraction/spec.md
	 */
	public function applyAiEnhancement(string $text, array $result, string $requestedBy): array;
}//end interface
