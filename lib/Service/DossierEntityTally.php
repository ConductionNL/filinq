<?php

/**
 * Dossier Entity Tally
 *
 * The running totals of one dossier's legal-bases summary.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
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

namespace OCA\Filinq\Service;

/**
 * One row per distinct entity (entityType:entityId) with its summed count,
 * the files it appears in and the union of its base labels, plus the total
 * occurrences and the distinct base references across the dossier.
 *
 * @spec exclude Running totals of LegalBasesSummaryService's dossier summary; no requirement of its own.
 */
final class DossierEntityTally {

	/**
	 * Rows by `entityType:entityId`.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private array $grouped = [];

	/**
	 * Every occurrence counted.
	 *
	 * @var int
	 */
	private int $occurrences = 0;

	/**
	 * The distinct base references seen.
	 *
	 * @var array<string, true>
	 */
	private array $basisRefs = [];

	/**
	 * Count one file's entity.
	 *
	 * The dossier number is consistent across files, so the same person or
	 * date appears once: its occurrence count is summed, the files it appears
	 * in are collected and its base labels are unioned, instead of repeating
	 * the same placeholder once per file.
	 *
	 * @param array<string, mixed> $entity   The pre-aggregated entity of one file.
	 * @param string               $filename The file's name, or ''.
	 *
	 * @return void
	 *
	 * @spec exclude Running totals of LegalBasesSummaryService's dossier summary.
	 */
	public function add(array $entity, string $filename): void {
		$count = (int)($entity['count'] ?? 0);
		$baseLabels = ($entity['baseLabels'] ?? []);
		if (is_array($baseLabels) === false) {
			$baseLabels = [];
		}

		$this->occurrences += $count;
		foreach (($entity['bases'] ?? []) as $ref) {
			$this->basisRefs[(string)$ref] = true;
		}

		$key = $this->rowFor(entity: $entity);
		$this->grouped[$key]['count'] += $count;
		if ($filename !== '') {
			$this->grouped[$key]['filenames'][$filename] = true;
		}

		foreach ($baseLabels as $label) {
			$this->grouped[$key]['baseLabels'][(string)$label] = true;
		}

	}//end add()

	/**
	 * The rows by `entityType:entityId`.
	 *
	 * @return array<string, array<string, mixed>> The grouped rows.
	 *
	 * @spec exclude Running totals of LegalBasesSummaryService's dossier summary.
	 */
	public function grouped(): array {
		return $this->grouped;
	}//end grouped()

	/**
	 * Every occurrence counted.
	 *
	 * @return int The total.
	 *
	 * @spec exclude Running totals of LegalBasesSummaryService's dossier summary.
	 */
	public function occurrences(): int {
		return $this->occurrences;
	}//end occurrences()

	/**
	 * How many distinct base references were seen.
	 *
	 * @return int The count.
	 *
	 * @spec exclude Running totals of LegalBasesSummaryService's dossier summary.
	 */
	public function distinctBases(): int {
		return count($this->basisRefs);
	}//end distinctBases()

	/**
	 * The key of the entity's row, starting the row on its first occurrence.
	 *
	 * @param array<string, mixed> $entity The entity.
	 *
	 * @return string The `entityType:entityId` key.
	 */
	private function rowFor(array $entity): string {
		$key = (string)($entity['entityType'] ?? '') . ':' . (string)($entity['entityId'] ?? '');
		if (isset($this->grouped[$key]) === false) {
			$this->grouped[$key] = [
				'placeholder' => (string)($entity['placeholder'] ?? ''),
				'entityType' => (string)($entity['entityType'] ?? ''),
				'entityId' => (int)($entity['entityId'] ?? 0),
				'count' => 0,
				'filenames' => [],
				'baseLabels' => [],
			];
		}

		return $key;

	}//end rowFor()
}//end class
