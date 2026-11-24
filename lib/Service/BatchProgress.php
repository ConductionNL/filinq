<?php

/**
 * Batch Progress: the progress figures of a batch, computed from its own files.
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
 * Succeeded, failed and pending files, the percentage done in the current
 * phase, and documents per hour since the batch started. Computed from the
 * batch's own file entries on every write, in the same object, so the
 * figures a list shows can never drift from the files they count.
 *
 * @spec openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#requirement-per-batch-progress-reporting-req-ddras-003
 */
final class BatchProgress {

	/**
	 * File statuses that count as failed.
	 */
	private const FAILED = ['error', 'prohibitionViolation'];

	/**
	 * The progress figures of a batch.
	 *
	 * @param array<string, mixed> $batch The batch.
	 * @param int                  $now   The current time (unix seconds).
	 *
	 * @return array{filesSucceeded: int, filesFailed: int, filesPending: int, progressPercent: float, documentsPerHour: float|null}
	 *
	 * @spec openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#requirement-per-batch-progress-reporting-req-ddras-003
	 */
	public function figuresFor(array $batch, int $now): array {
		$files = (array)($batch['files'] ?? []);
		$succeeded = 0;
		$failed = 0;
		$extracted = 0;
		foreach ($files as $file) {
			$status = (string)($file['status'] ?? '');
			$succeeded += (int)($status === 'anonymized');
			$failed += (int)in_array($status, self::FAILED, true);
			$extracted += (int)($status === 'extracted');
		}

		$total = count($files);
		$done = ($succeeded + $failed);
		if (($batch['status'] ?? '') === 'extracting') {
			$done += $extracted;
		}

		$percent = 0.0;
		if ($total > 0) {
			$percent = round((((float)$done / (float)$total) * 100.0), 1);
		}

		return [
			'filesSucceeded' => $succeeded,
			'filesFailed' => $failed,
			'filesPending' => ($total - $succeeded - $failed),
			'progressPercent' => $percent,
			'documentsPerHour' => $this->perHour(batch: $batch, succeeded: $succeeded, now: $now),
		];

	}//end figuresFor()

	/**
	 * Documents per hour since the batch started, or null before a minute has passed.
	 *
	 * @param array<string, mixed> $batch     The batch.
	 * @param int                  $succeeded Files that succeeded.
	 * @param int                  $now       The current time.
	 *
	 * @return float|null
	 */
	private function perHour(array $batch, int $succeeded, int $now): ?float {
		if (isset($batch['startedAt']) === false) {
			return null;
		}

		$end = (int)($batch['completedAt'] ?? $now);
		$seconds = ($end - (int)$batch['startedAt']);
		if ($seconds < 60) {
			return null;
		}

		return round(($succeeded * 3600) / $seconds, 1);

	}//end perHour()
}//end class
