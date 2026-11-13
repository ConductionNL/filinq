<?php

/**
 * Batch Report Aggregator: throughput, phase durations, failure groups and QA samples of a batch.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/redaction-at-scale
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

/**
 * Computes the batch report's summary at read time from the per-file entries,
 * so no stored counter can drift (redaction-at-scale REQ-DDRAS-006). Entity
 * values are never read. A figure whose inputs were not recorded is null
 * rather than guessed.
 *
 * @spec openspec/changes/redaction-at-scale
 */
class BatchReportAggregator {

	/**
	 * The failure group of a conversion that exhausted its cascade.
	 */
	public const REASON_CONVERSION = 'conversion-failed';

	/**
	 * The summary of one batch.
	 *
	 * @param array<string, mixed> $batch The batch, with its `files` and optional `startedAt` / `completedAt` (unix seconds).
	 *
	 * @return array<string, mixed> files, succeeded, failed, documentsPerHour (float|null), phaseMs (extract, anonymize:
	 *                              int|null), failures (reason => count) and qaSamples (fileId => outcome).
	 *
	 * @spec openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#requirement-throughput-and-failure-reporting-req-ddras-006
	 */
	public function summarise(array $batch): array {
		$files = array_values(array_filter((array)($batch['files'] ?? []), 'is_array'));
		$failures = [];
		$qaSamples = [];
		$succeeded = 0;
		foreach ($files as $file) {
			if (($file['status'] ?? '') === 'error') {
				$reason = self::reasonOf(file: $file);
				$failures[$reason] = (($failures[$reason] ?? 0) + 1);
				continue;
			}

			$succeeded++;
			if (($file['qaSampled'] ?? false) === true) {
				$qaSamples[(string)($file['fileId'] ?? '')] = (string)($file['qaOutcome'] ?? BatchQaSampler::OUTCOME_PENDING);
			}
		}

		arsort($failures);

		return [
			'files' => count($files),
			'succeeded' => $succeeded,
			'failed' => (count($files) - $succeeded),
			'documentsPerHour' => self::perHour(batch: $batch, done: $succeeded),
			'phaseMs' => [
				'extract' => self::phaseTotal(files: $files, key: 'extractMs'),
				'anonymize' => self::phaseTotal(files: $files, key: 'anonymizeMs'),
			],
			'failures' => $failures,
			'qaSamples' => $qaSamples,
		];
	}//end summarise()

	/**
	 * The failure group of a failed file: its recorded category, a conversion
	 * failure, or else its message.
	 *
	 * @param array<string, mixed> $file The per-file entry.
	 *
	 * @return string The group.
	 *
	 * @spec openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#requirement-throughput-and-failure-reporting-req-ddras-006
	 */
	public static function reasonOf(array $file): string {
		$category = trim((string)($file['errorCategory'] ?? ''));
		if ($category !== '') {
			return $category;
		}

		if (isset($file['conversionAttempts']) === true) {
			return self::REASON_CONVERSION;
		}

		$message = trim((string)($file['error'] ?? ''));
		if ($message === '') {
			return 'unknown';
		}

		return $message;
	}//end reasonOf()

	/**
	 * Documents per hour over the batch's run, or null without both timestamps.
	 *
	 * @param array<string, mixed> $batch The batch.
	 * @param int                  $done  Files that succeeded.
	 *
	 * @return float|null
	 */
	private static function perHour(array $batch, int $done): ?float {
		$started = ($batch['startedAt'] ?? null);
		$completed = ($batch['completedAt'] ?? null);
		if (is_numeric($started) === false || is_numeric($completed) === false || (int)$completed <= (int)$started) {
			return null;
		}

		return round(($done * 3600) / ((int)$completed - (int)$started), 1);
	}//end perHour()

	/**
	 * The summed duration of one phase, or null when no file recorded it.
	 *
	 * @param array<int, array<string, mixed>> $files The per-file entries.
	 * @param string                           $key   extractMs or anonymizeMs.
	 *
	 * @return int|null
	 */
	private static function phaseTotal(array $files, string $key): ?int {
		$total = null;
		foreach ($files as $file) {
			if (is_numeric($file[$key] ?? null) === true) {
				$total = (($total ?? 0) + (int)$file[$key]);
			}
		}

		return $total;
	}//end phaseTotal()
}//end class
