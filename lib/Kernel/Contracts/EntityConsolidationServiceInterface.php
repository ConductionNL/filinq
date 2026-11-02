<?php

/**
 * Contract of EntityConsolidationService
 *
 * The public surface of the EntityConsolidationService service, so callers in other
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

/**
 * Contract of EntityConsolidationService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface EntityConsolidationServiceInterface {
	/**
	 * Consolidate entity detections across every extracted file in a batch.
	 *
	 * Entities below the supplied confidence threshold are kept in the
	 * result but flagged as `included => false` so the UI can still show
	 * them for manual review.
	 *
	 * Each returned entity also carries:
	 *   - `prohibitionMatch` — `null` or `{ruleId, ruleName, highConfidence}`.
	 *   - `suggestedBases`   — array of dossier-derived Woo Art. 5 grondslag
	 *                          UUIDs/slugs (empty when no dossier is found).
	 *
	 * Both additions are non-breaking (strict superset of the pre-change shape).
	 *
	 * @param array<string, mixed> $batch Batch record whose file list should be consolidated.
	 * @param float $minConfidence Minimum confidence required for an entity to be included by default.
	 *
	 * @return array<int, array<string, mixed>> Consolidated, confidence-sorted list of entities.
	 *
	 * @spec openspec/specs/anonymization-entity-review/spec.md#requirement-consolidated-entity-list-endpoint
	 * @spec openspec/changes/anonymisation-entity-review-prohibition-hints/tasks.md#task-1
	 * @spec openspec/changes/anonymisation-entity-review-prohibition-hints/tasks.md#task-2
	 * @spec openspec/changes/anonymisation-entity-review-prohibition-hints/tasks.md#task-3
	 */
	public function consolidateEntities(array $batch, float $minConfidence = 0.0): array;
}//end interface
