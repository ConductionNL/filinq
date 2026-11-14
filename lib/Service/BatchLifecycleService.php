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
	 * Re-queue the files of an errored or cancelled batch that did not succeed.
	 *
	 * A failed file goes back to `uploaded` (extracted again) with its retry
	 * count raised; an extracted file waits for anonymization again; a file
	 * that already produced its output is left alone, so a resumed run never
	 * produces it twice.
	 *
	 * @param string $batchId The batch id.
	 *
	 * @return array{status: int, batchId?: string, batchStatus?: string, requeued?: int, reason?: string}
	 *         404 for an unknown or foreign batch, 409 for one not errored or cancelled, else 202.
	 *
	 * @spec openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#requirement-idempotent-resume-req-ddras-005
	 */
	public function resume(string $batchId): array {
		$batch = $this->stateService->getBatch($batchId);
		if ($batch === null) {
			return ['status' => 404, 'reason' => 'not-found'];
		}

		$current = (string)($batch['status'] ?? '');
		if (in_array($current, self::RESUMABLE, true) === false) {
			return ['status' => 409, 'reason' => 'not-resumable', 'batchStatus' => $current];
		}

		$requeued = 0;
		$needsExtraction = false;
		foreach ((array)($batch['files'] ?? []) as $index => $file) {
			$status = (string)($file['status'] ?? '');
			if ($status === self::FILE_SUCCEEDED) {
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
			$needsExtraction = true;
		}

		$batch['status'] = 'review';
		if ($needsExtraction === true) {
			$batch['status'] = 'extracting';
		}

		$this->stateService->updateBatch($batchId, $batch);

		return ['status' => 202, 'batchId' => $batchId, 'batchStatus' => $batch['status'], 'requeued' => $requeued];

	}//end resume()
}//end class
