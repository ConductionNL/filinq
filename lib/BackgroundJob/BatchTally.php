<?php

/**
 * Batch tally
 *
 * The running count of a batch job: how many items it has, how many
 * succeeded or failed, and the result row of each.
 *
 * @category  BackgroundJob
 * @package   OCA\Filinq\BackgroundJob
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-batch-correspondence-generation
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\BackgroundJob;

/**
 * Counts the results of a batch job and states its progress.
 *
 * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-batch-correspondence-generation
 */
final class BatchTally {

	/**
	 * Items that succeeded.
	 *
	 * @var integer
	 */
	private int $completed = 0;

	/**
	 * Items that failed.
	 *
	 * @var integer
	 */
	private int $errors = 0;

	/**
	 * The result row of each item so far.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $results = [];

	/**
	 * A tally for a batch of this many items.
	 *
	 * @param int $total The number of items in the batch.
	 *
	 * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-batch-correspondence-generation
	 */
	public function __construct(private readonly int $total) {
	}//end __construct()

	/**
	 * Count one item's result row; a row with status `success` succeeded.
	 *
	 * @param array<string, mixed> $result The result row.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-batch-correspondence-generation
	 */
	public function record(array $result): void {
		$this->results[] = $result;
		if ($result['status'] === 'success') {
			$this->completed++;
			return;
		}

		$this->errors++;
	}//end record()

	/**
	 * Whether any item failed.
	 *
	 * @return bool True when at least one item failed.
	 *
	 * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-batch-correspondence-generation
	 */
	public function hasErrors(): bool {
		return $this->errors > 0;
	}//end hasErrors()

	/**
	 * The progress in a given state: status, total, completed, errors, results.
	 *
	 * @param string $status The job state.
	 *
	 * @return array{status: string, total: int, completed: int, errors: int, results: array<int, array<string, mixed>>} The progress.
	 *
	 * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-batch-correspondence-generation
	 */
	public function progress(string $status): array {
		return [
			'status'    => $status,
			'total'     => $this->total,
			'completed' => $this->completed,
			'errors'    => $this->errors,
			'results'   => $this->results,
		];
	}//end progress()
}//end class
