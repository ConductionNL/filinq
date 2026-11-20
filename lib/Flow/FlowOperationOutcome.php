<?php

/**
 * Filinq Flow Operation Outcome
 *
 * What one operation run came to: the terminal status, its reason, the
 * counts-only summary and the file it produced, if any.
 *
 * @category Flow
 * @package  OCA\Filinq\Flow
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/flow-operations/tasks.md#task-2.3
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Flow;

/**
 * The outcome of one run.
 *
 * @category Flow
 * @package  OCA\Filinq\Flow
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/flow-operations/tasks.md#task-2.3
 */
final class FlowOperationOutcome {

	/**
	 * Constructor.
	 *
	 * @param string               $status         The terminal status (succeeded, failed or skipped).
	 * @param string|null          $reason         The machine-readable reason, null on plain success.
	 * @param array<string, mixed> $resultSummary  The counts-only summary; never document content.
	 * @param int|null             $producedFileId The file the operation wrote, if any.
	 * @param string|null          $errorMessage   The human-readable failure, if any.
	 *
	 * @return void
	 */
	public function __construct(
		public readonly string $status,
		public readonly ?string $reason=null,
		public readonly array $resultSummary=[],
		public readonly ?int $producedFileId=null,
		public readonly ?string $errorMessage=null,
	) {

	}//end __construct()

	/**
	 * A successful run.
	 *
	 * @param array<string, mixed> $summary        The summary.
	 * @param int|null             $producedFileId The file written, if any.
	 *
	 * @return self The outcome.
	 *
	 * @spec openspec/changes/flow-operations/tasks.md#task-2.3
	 */
	public static function succeeded(array $summary=[], ?int $producedFileId=null): self {
		return new self(status: FlowRunStatus::SUCCEEDED, resultSummary: $summary, producedFileId: $producedFileId);

	}//end succeeded()

	/**
	 * A failed run.
	 *
	 * @param string               $reason  The reason.
	 * @param string               $message The human-readable failure.
	 * @param array<string, mixed> $summary The summary.
	 *
	 * @return self The outcome.
	 *
	 * @spec openspec/changes/flow-operations/tasks.md#task-2.3
	 */
	public static function failed(string $reason, string $message, array $summary=[]): self {
		return new self(status: FlowRunStatus::FAILED, reason: $reason, resultSummary: $summary, errorMessage: $message);

	}//end failed()

	/**
	 * A run that had nothing to do.
	 *
	 * @param string $reason The reason.
	 *
	 * @return self The outcome.
	 *
	 * @spec openspec/changes/flow-operations/tasks.md#task-2.3
	 */
	public static function skipped(string $reason): self {
		return new self(status: FlowRunStatus::SKIPPED, reason: $reason);

	}//end skipped()
}//end class
