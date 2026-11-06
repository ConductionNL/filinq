<?php

/**
 * Batch Anonymize Run
 *
 * The state of one batch while its files are anonymised.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/batch-anonymization/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

/**
 * One batch being anonymised: its stored state and what it has skipped and processed so far.
 *
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-anonymization
 */
final class BatchAnonymizeRun {

	/**
	 * Files skipped so far, each `{fileId, reason}` plus `httpStatus` for a prohibition.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	public array $skipped = [];

	/**
	 * Files anonymised so far.
	 *
	 * @var integer
	 */
	public int $processed = 0;

	/**
	 * Files skipped for a prohibition violation so far.
	 *
	 * @var integer
	 */
	public int $prohibitionSkipped = 0;

	/**
	 * @param array<string, mixed> $batch The stored batch, updated per file.
	 * @param BatchAnonymizeRequest $request What every file is anonymised with.
	 */
	public function __construct(
		public array $batch,
		public readonly BatchAnonymizeRequest $request,
	) {
	}//end __construct()

	/**
	 * Mark one file as refused by the prohibition gate and skip it.
	 *
	 * @param int|string $index The file's position in the batch.
	 * @param array<int, array<string, mixed>> $violations The violation records.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-5
	 */
	public function skipProhibited(int|string $index, array $violations): void {
		$this->batch['files'][$index]['status'] = 'prohibitionViolation';
		$this->batch['files'][$index]['prohibitedEntries'] = $violations;
		$this->skipped[] = [
			'fileId' => $this->batch['files'][$index]['fileId'],
			'reason' => 'Prohibition violation on unredacted entities',
			'httpStatus' => 422,
		];
		$this->prohibitionSkipped++;
	}//end skipProhibited()

	/**
	 * The run summary.
	 *
	 * @param string $batchId The batch.
	 *
	 * @return array<string, mixed> `{batchId, batchStatus, processedFiles, skippedFiles, prohibitionSkippedFiles, totalFiles}`.
	 *
	 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-anonymization
	 */
	public function summary(string $batchId): array {
		return [
			'batchId' => $batchId,
			'batchStatus' => 'completed',
			'processedFiles' => $this->processed,
			'skippedFiles' => $this->skipped,
			'prohibitionSkippedFiles' => $this->prohibitionSkipped,
			'totalFiles' => count($this->batch['files']),
		];
	}//end summary()
}//end class
