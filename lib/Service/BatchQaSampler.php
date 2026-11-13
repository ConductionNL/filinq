<?php

/**
 * Batch QA Sampler: picks the reproducible QA sample of an anonymization batch.
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
 * Selects `rate` percent of a batch's successfully anonymized files for human
 * review (redaction-at-scale D5, REQ-DDRAS-007). The selection is a uniform
 * sample ranked by a hash of the stored seed and the file id, so the same seed
 * always selects the same files and an audit can recompute it. Rate 0 selects
 * nothing; any other rate selects at least one file when one succeeded.
 *
 * @spec openspec/changes/redaction-at-scale
 */
class BatchQaSampler {

	/**
	 * The per-file status of a successfully anonymized file.
	 */
	public const SUCCEEDED = 'anonymized';

	/**
	 * The outcome a sample starts with.
	 */
	public const OUTCOME_PENDING = 'pending';

	/**
	 * The file ids to sample, in a stable order.
	 *
	 * @param array<int, array<string, mixed>> $files The batch's per-file entries.
	 * @param int                              $rate  Percent of the succeeded files, 0 to 100.
	 * @param string                           $seed  The seed stored on the batch.
	 *
	 * @return array<int, string> The sampled file ids, as strings.
	 *
	 * @spec openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#requirement-sampling-qa-routed-to-human-review-req-ddras-007
	 */
	public function select(array $files, int $rate, string $seed): array {
		$rate = max(0, min(100, $rate));
		$succeeded = [];
		foreach ($files as $file) {
			$id = (string)($file['fileId'] ?? '');
			if ($id !== '' && ($file['status'] ?? '') === self::SUCCEEDED) {
				$succeeded[$id] = hash('sha256', $seed . '|' . $id);
			}
		}

		if ($rate === 0 || $succeeded === []) {
			return [];
		}

		asort($succeeded, SORT_STRING);
		$size = max(1, (int)round((count($succeeded) * $rate) / 100));

		return array_map('strval', array_slice(array_keys($succeeded), 0, $size));
	}//end select()

	/**
	 * The batch with its sample marked: `qaSampleRate` and `qaSampleSeed` on
	 * the batch, `qaSampled` and a pending `qaOutcome` on each sampled file.
	 * A file that already has an outcome keeps it.
	 *
	 * @param array<string, mixed> $batch The batch, with its `files`.
	 * @param int                  $rate  Percent of the succeeded files.
	 * @param string               $seed  The seed to store and sample with.
	 *
	 * @return array<string, mixed> The marked batch.
	 *
	 * @spec openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#requirement-sampling-qa-routed-to-human-review-req-ddras-007
	 */
	public function mark(array $batch, int $rate, string $seed): array {
		$files = (array)($batch['files'] ?? []);
		$sampled = array_flip($this->select(files: $files, rate: $rate, seed: $seed));

		foreach ($files as $index => $file) {
			if (isset($sampled[(string)($file['fileId'] ?? '')]) === false) {
				continue;
			}

			$files[$index]['qaSampled'] = true;
			$files[$index]['qaOutcome'] = ($file['qaOutcome'] ?? self::OUTCOME_PENDING);
		}

		$batch['files'] = $files;
		$batch['qaSampleRate'] = max(0, min(100, $rate));
		$batch['qaSampleSeed'] = $seed;

		return $batch;
	}//end mark()
}//end class
