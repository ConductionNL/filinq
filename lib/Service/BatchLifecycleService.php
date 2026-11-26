<?php

/**
 * Batch Lifecycle Service: cancels and resumes an anonymization batch.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#requirement-cooperative-cancellation-req-ddras-004
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCA\Filinq\Kernel\Contracts\BatchStateServiceInterface;

/**
 * Moves a batch to `cancelling`/`cancelled`, or re-queues the files of an
 * errored or cancelled batch that did not succeed.
 *
 * Ownership is enforced by {@see BatchStateServiceInterface::getBatch()},
 * which returns null for a batch the session user may not see, so a foreign
 * batch answers the same as a missing one.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#requirement-cooperative-cancellation-req-ddras-004
 */
class BatchLifecycleService {

	/**
	 * Batch status while a worker is asked to stop after its current file.
	 *
	 * @var string
	 */
	public const CANCELLING = 'cancelling';

	/**
	 * Batch status once no further file will be started.
	 *
	 * @var string
	 */
	public const CANCELLED = 'cancelled';

	/**
	 * Per-file status of a file that produced its anonymized output.
	 *
	 * @var string
	 */
	private const FILE_SUCCEEDED = 'anonymized';

	/**
	 * Batch statuses a worker is busy in; cancelling one waits for that worker.
	 *
	 * @var array<int, string>
	 */
	private const RUNNING = ['extracting', 'anonymizing'];

	/**
	 * Batch statuses that cannot be cancelled.
	 *
	 * @var array<int, string>
	 */
	private const NOT_CANCELLABLE = ['completed', 'error', self::CANCELLED];

	/**
	 * Batch statuses that can be resumed.
	 *
	 * @var array<int, string>
	 */
	private const RESUMABLE = ['error', self::CANCELLED];

	/**
	 * Batch status of a finished run; resumable only while it has failed files.
	 *
	 * @var string
	 */
	private const COMPLETED = 'completed';

	/**
	 * Per-file status of a file that failed.
	 *
	 * @var string
	 */
	private const FILE_FAILED = 'error';

	/**
	 * Constructor.
	 *
	 * @param BatchStateServiceInterface $stateService Batch state, ownership-checked on read.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly BatchStateServiceInterface $stateService,
	) {

	}//end __construct()

	/**
	 * Ask a batch to stop.
	 *
	 * A batch a worker is busy in becomes `cancelling`; the worker finishes its
	 * current file and then sets `cancelled`. Any other open batch has no
	 * worker to wait for and becomes `cancelled` at once. Outputs already
	 * produced are kept.
	 *
	 * @param string $batchId The batch id.
	 *
	 * @return array{status: int, batchId?: string, batchStatus?: string, reason?: string}
	 *         404 for an unknown or foreign batch, 409 for a finished one, else 202.
	 *
	 * @spec openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#requirement-cooperative-cancellation-req-ddras-004
	 */
	public function cancel(string $batchId): array {
		$batch = $this->stateService->getBatch($batchId);
		if ($batch === null) {
			return ['status' => 404, 'reason' => 'not-found'];
		}

		$current = (string)($batch['status'] ?? '');
		if (in_array($current, self::NOT_CANCELLABLE, true) === true) {
			return ['status' => 409, 'reason' => 'not-cancellable', 'batchStatus' => $current];
		}

		if ($current !== self::CANCELLING) {
			$batch['status'] = self::CANCELLED;
			if (in_array($current, self::RUNNING, true) === true) {
				$batch['status'] = self::CANCELLING;
			}

			$this->stateService->updateBatch($batchId, $batch);
		}

		return ['status' => 202, 'batchId' => $batchId, 'batchStatus' => $batch['status']];

	}//end cancel()

	/**
	 * Re-queue the files of an errored or cancelled batch that did not succeed,
	 * or the failed files of a completed batch.
	 *
	 * A failed file goes back to `uploaded` (extracted again) with its retry
	 * count raised; an extracted file waits for anonymization again; a file
	 * that already produced its output is left alone, so a resumed run never
	 * produces it twice. A completed batch whose files failed is resumable
	 * too: per-file failures do not set the batch to `error`, so only its
	 * failed files are queued again and every other file keeps its outcome.
	 * A completed batch without failed files answers 409.
	 *
	 * @param string $batchId The batch id.
	 *
	 * @return array{status: int, batchId?: string, batchStatus?: string, requeued?: int, reason?: string}
	 *         404 for an unknown or foreign batch, 409 for one that cannot be resumed, else 202.
	 *
	 * @spec openspec/changes/redaction-at-scale/tasks.md#5.6
	 * @spec openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#requirement-idempotent-resume-req-ddras-005
	 */
	public function resume(string $batchId): array {
		$batch = $this->stateService->getBatch($batchId);
		if ($batch === null) {
			return ['status' => 404, 'reason' => 'not-found'];
		}

		$current = (string)($batch['status'] ?? '');
		$onlyFailed = ($current === self::COMPLETED && $this->hasFailedFiles(batch: $batch) === true);
		if ($onlyFailed === false && in_array($current, self::RESUMABLE, true) === false) {
			return ['status' => 409, 'reason' => 'not-resumable', 'batchStatus' => $current];
		}

		$requeue = $this->requeueUnfinished(batch: $batch, onlyFailed: $onlyFailed);
		$batch = $requeue['batch'];
		// Marks the run as resumed: a file whose redacted copy was made after
		// the batch started is then taken from its anonymizationLink instead
		// of being anonymized again (REQ-DDRAS-005).
		$batch['resumedAt'] = time();
		if ($batch['status'] === 'review' && $this->continuesInBackground(batch: $batch) === true) {
			$batch['status'] = 'anonymizing';
		}

		$this->stateService->updateBatch($batchId, $batch);

		return ['status' => 202, 'batchId' => $batchId, 'batchStatus' => $batch['status'], 'requeued' => $requeue['requeued']];

	}//end resume()

	/**
	 * Whether a resumed batch goes straight back to the background anonymize phase.
	 *
	 * True for a background batch that carries its reviewed request: nothing
	 * is left for a person to review, so the coordinator carries on.
	 *
	 * @param array<string, mixed> $batch The batch.
	 *
	 * @return bool
	 *
	 * @spec openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#requirement-idempotent-resume-req-ddras-005
	 */
	private function continuesInBackground(array $batch): bool {
		return ($batch['background'] ?? false) === true && is_array($batch['anonymizeRequest'] ?? null) === true;

	}//end continuesInBackground()

	/**
	 * Whether any file of the batch failed.
	 *
	 * @param array<string, mixed> $batch The stored batch.
	 *
	 * @return bool
	 *
	 * @spec openspec/changes/redaction-at-scale/tasks.md#5.6
	 */
	private function hasFailedFiles(array $batch): bool {
		foreach ((array)($batch['files'] ?? []) as $file) {
			if ((string)($file['status'] ?? '') === self::FILE_FAILED) {
				return true;
			}
		}

		return false;

	}//end hasFailedFiles()

	/**
	 * The batch with every unfinished file queued again: a file that failed
	 * before extraction goes back to `uploaded` with one more retry, an
	 * extracted one waits for review as it is. The batch extracts again when
	 * any file went back, else it returns to review. With `$onlyFailed` only
	 * the files in `error` are queued again (a completed batch).
	 *
	 * @param array<string, mixed> $batch      The stored batch.
	 * @param bool                 $onlyFailed Whether to queue only the failed files.
	 *
	 * @return array{batch: array<string, mixed>, requeued: int}
	 *
	 * @spec openspec/changes/redaction-at-scale/tasks.md#5.6
	 * @spec openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#requirement-idempotent-resume-req-ddras-005
	 */
	private function requeueUnfinished(array $batch, bool $onlyFailed=false): array {
		$requeued = 0;
		$batch['status'] = 'review';
		foreach ((array)($batch['files'] ?? []) as $index => $file) {
			$status = (string)($file['status'] ?? '');
			if ($status === self::FILE_SUCCEEDED || ($onlyFailed === true && $status !== self::FILE_FAILED)) {
				continue;
			}

			$requeued++;
			if ($status === 'extracted') {
				continue;
			}

			$file['status'] = 'uploaded';
			$file['retryCount'] = ((int)($file['retryCount'] ?? 0) + 1);
			unset($file['error']);
			$batch['files'][$index] = $file;
			$batch['status'] = 'extracting';
		}

		return ['batch' => $batch, 'requeued' => $requeued];

	}//end requeueUnfinished()
}//end class
