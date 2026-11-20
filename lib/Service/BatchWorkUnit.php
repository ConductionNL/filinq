<?php

/**
 * Batch Work Unit: one bounded slice of a background batch.
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

use Exception;
use OCP\AppFramework\Utility\ITimeFactory;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Processes the files of one batch sequentially until the batch's phase is
 * done or the unit's budget (files, then seconds) is spent, whichever comes
 * first, and writes the batch after every file, so the file boundary is also
 * the crash-recovery boundary.
 *
 * Phases: `extracting` takes `uploaded` files to `extracted` (or `error`) and
 * ends in `review`; `anonymizing` takes `extracted` files to `anonymized` (or
 * `error`) with the request stored on the batch and ends in `completed` with
 * the QA sample drawn. A requested cancel is honoured between files, never
 * inside one.
 *
 * @spec openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#requirement-chunked-background-processing-under-cron-req-ddras-002
 */
class BatchWorkUnit {

	/**
	 * A unit that spent its time budget on fewer than this share of its file budget is throttled.
	 */
	private const THROTTLE_SHARE = 0.2;

	/**
	 * Decides which failures are retried in a later unit.
	 *
	 * @var BatchRetryPolicy
	 */
	private readonly BatchRetryPolicy $retryPolicy;

	/**
	 * Constructor.
	 *
	 * @param BatchStateService     $stateService The batch store.
	 * @param AnonymizationService  $anonService  Extracts and detects one file.
	 * @param BatchAnonymizeService $anonymizer   Anonymizes one file and closes the phase.
	 * @param BatchBackgroundPath   $background   Reads the stored request and the resume rule.
	 * @param ITimeFactory          $clock        The clock the time budget is measured on.
	 * @param LoggerInterface       $logger       Logger.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly BatchStateService $stateService,
		private readonly AnonymizationService $anonService,
		private readonly BatchAnonymizeService $anonymizer,
		private readonly BatchBackgroundPath $background,
		private readonly ITimeFactory $clock,
		private readonly LoggerInterface $logger,
	) {
		$this->retryPolicy = new BatchRetryPolicy();

	}//end __construct()

	/**
	 * Run one work unit of a batch.
	 *
	 * @param string $batchId    Identifier of the batch.
	 * @param int    $maxFiles   The file budget (at least 1).
	 * @param int    $maxSeconds The time budget in seconds; 0 or less means no time budget.
	 *
	 * @return array{batchId: string, batchStatus: string, processed: int, throttled: bool}
	 *
	 * @spec openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#requirement-chunked-background-processing-under-cron-req-ddras-002
	 * @spec openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#requirement-cooperative-cancellation-req-ddras-004
	 */
	public function run(string $batchId, int $maxFiles, int $maxSeconds): array {
		$batch = $this->stateService->getBatch($batchId);
		if ($batch === null) {
			return ['batchId' => $batchId, 'batchStatus' => 'missing', 'processed' => 0, 'throttled' => false];
		}

		$status = (string)($batch['status'] ?? '');
		if ($status === BatchLifecycleService::CANCELLING) {
			return $this->finishCancel(batchId: $batchId, batch: $batch, processed: 0);
		}

		if ($status !== 'extracting' && $status !== 'anonymizing') {
			return ['batchId' => $batchId, 'batchStatus' => $status, 'processed' => 0, 'throttled' => false];
		}

		$request = null;
		if ($status === 'anonymizing') {
			$request = $this->background->storedRequest(batch: $batch);
			if ($request === null) {
				return $this->failWithoutRequest(batchId: $batchId, batch: $batch);
			}
		}

		if (isset($batch['startedAt']) === false) {
			$batch['startedAt'] = $this->clock->getTime();
		}

		return $this->processQueue(
			batchId: $batchId,
			batch: $batch,
			request: $request,
			budget: ['files' => max(1, $maxFiles), 'seconds' => $maxSeconds]
		);

	}//end run()

	/**
	 * Process the queued files of the batch's phase within the budget, then close the phase when done.
	 *
	 * @param string                     $batchId Identifier of the batch.
	 * @param array<string, mixed>       $batch   The batch.
	 * @param BatchAnonymizeRequest|null $request The stored request in the anonymize phase.
	 * @param array{files: int, seconds: int} $budget The file and time budget.
	 *
	 * @return array{batchId: string, batchStatus: string, processed: int, throttled: bool}
	 *
	 * @spec openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#requirement-chunked-background-processing-under-cron-req-ddras-002
	 */
	private function processQueue(string $batchId, array $batch, ?BatchAnonymizeRequest $request, array $budget): array {
		$phase = (string)$batch['status'];
		$started = $this->clock->getTime();
		$processed = 0;
		foreach ($this->queuedIndexes(batch: $batch, phase: $phase) as $index) {
			if ($processed >= $budget['files'] || $this->outOfTime(started: $started, seconds: $budget['seconds']) === true) {
				break;
			}

			$batch = $this->processOne(batch: $batch, index: $index, request: $request);
			$processed++;
			// Checked after the file and before its write: a cancel that came
			// in while the file ran must not be overwritten by this unit's
			// copy, and no further file is started (REQ-DDRAS-004).
			if ($this->cancelRequested(batchId: $batchId) === true) {
				return $this->finishCancel(batchId: $batchId, batch: $batch, processed: $processed);
			}

			$this->stateService->updateBatch($batchId, $batch);
		}//end foreach

		$timedOut = $this->outOfTime(started: $started, seconds: $budget['seconds'])
			&& $this->queuedIndexes(batch: $batch, phase: $phase) !== [];
		$batch['throttled'] = ($timedOut === true && $processed < ($budget['files'] * self::THROTTLE_SHARE));
		$batch = $this->closePhaseWhenDone(batch: $batch, phase: $phase);
		$this->stateService->updateBatch($batchId, $batch);
		return [
			'batchId' => $batchId,
			'batchStatus' => (string)$batch['status'],
			'processed' => $processed,
			'throttled' => $batch['throttled'],
		];

	}//end processQueue()

	/**
	 * Whether a unit that started at this time has spent its time budget.
	 *
	 * @param int $started When the unit started.
	 * @param int $seconds The time budget; 0 or less means none.
	 *
	 * @return bool
	 */
	private function outOfTime(int $started, int $seconds): bool {
		return $seconds > 0 && ($this->clock->getTime() - $started) >= $seconds;

	}//end outOfTime()

	/**
	 * Fail an anonymizing batch that carries no request to anonymize with.
	 *
	 * @param string               $batchId Identifier of the batch.
	 * @param array<string, mixed> $batch   The batch.
	 *
	 * @return array{batchId: string, batchStatus: string, processed: int, throttled: bool}
	 */
	private function failWithoutRequest(string $batchId, array $batch): array {
		$batch['status'] = 'error';
		$batch['error'] = 'The batch has no stored anonymization request.';
		$this->stateService->updateBatch($batchId, $batch);
		return ['batchId' => $batchId, 'batchStatus' => 'error', 'processed' => 0, 'throttled' => false];

	}//end failWithoutRequest()

	/**
	 * The indexes of the files still queued for a phase, in batch order.
	 *
	 * @param array<string, mixed> $batch The batch.
	 * @param string               $phase The phase.
	 *
	 * @return array<int, int|string>
	 */
	private function queuedIndexes(array $batch, string $phase): array {
		$queued = 'uploaded';
		if ($phase === 'anonymizing') {
			$queued = 'extracted';
		}

		$indexes = [];
		foreach ((array)($batch['files'] ?? []) as $index => $file) {
			if (($file['status'] ?? '') === $queued) {
				$indexes[] = $index;
			}
		}

		return $indexes;

	}//end queuedIndexes()

	/**
	 * Process one file in its phase and record how long it took.
	 *
	 * @param array<string, mixed>       $batch   The batch.
	 * @param int|string                 $index   The file's index.
	 * @param BatchAnonymizeRequest|null $request The stored request in the anonymize phase.
	 *
	 * @return array<string, mixed> The batch with that file's entry updated.
	 */
	private function processOne(array $batch, int|string $index, ?BatchAnonymizeRequest $request): array {
		$start = hrtime(true);
		if ($request !== null) {
			$batch = $this->anonymizer->anonymizeAt(batch: $batch, index: $index, request: $request);
			$entry = $batch['files'][$index];
			if ($this->retryPolicy->entryIsLockMiss(entry: $entry) === true) {
				$entry = $this->retryPolicy->retryOrFail(
					entry: $entry,
					queueStatus: 'extracted',
					message: (string)($entry['error'] ?? 'Conversion lock not available')
				);
			}

			$entry['anonymizeMs'] = $this->elapsedMs(start: $start);
			$batch['files'][$index] = $entry;
			return $batch;
		}

		$batch['files'][$index] = $this->extractOne(entry: $batch['files'][$index]);
		$batch['files'][$index]['extractMs'] = $this->elapsedMs(start: $start);
		return $batch;

	}//end processOne()

	/**
	 * Extract and detect one file; a failure is that file's error, never the batch's.
	 *
	 * @param array<string, mixed> $entry The file entry.
	 *
	 * @return array<string, mixed> The entry, `extracted`, `error`, or queued again after a lock miss.
	 *
	 * @spec openspec/changes/redaction-at-scale/specs/folder-batch-analysis/spec.md#requirement-background-extraction-via-queuedjob
	 */
	private function extractOne(array $entry): array {
		try {
			$result = $this->anonService->extractAndDetectEntities((int)$entry['fileId']);
			$entry['status'] = 'extracted';
			$entry['entityCount'] = (int)($result['entityCount'] ?? 0);
			$entry['error'] = null;
			return $entry;
		} catch (Throwable $e) {
			if ($this->retryPolicy->isLockMiss(error: $e) === true) {
				return $this->retryPolicy->retryOrFail(entry: $entry, queueStatus: 'uploaded', message: $e->getMessage());
			}

			$this->logger->warning(
				'Batch work unit: extraction failed for file',
				['fileId' => $entry['fileId'] ?? null, 'error' => $e->getMessage()]
			);
			$entry['status'] = 'error';
			$entry['error'] = $e->getMessage();
			if ($e instanceof Exception === false) {
				$entry['errorCategory'] = get_class($e);
			}

			return $entry;
		}//end try

	}//end extractOne()

	/**
	 * Move the batch to its next status once no file of its phase is queued.
	 *
	 * @param array<string, mixed> $batch The batch.
	 * @param string               $phase The phase.
	 *
	 * @return array<string, mixed> The batch, `review` / `completed` when the phase is done.
	 */
	private function closePhaseWhenDone(array $batch, string $phase): array {
		if ($this->queuedIndexes(batch: $batch, phase: $phase) !== []) {
			return $batch;
		}

		if ($phase === 'extracting') {
			$batch['status'] = 'review';
			if ($this->background->continuesAfterExtraction(batch: $batch) === true) {
				$batch['status'] = 'anonymizing';
			}

			return $batch;
		}

		return $this->anonymizer->complete(batch: $batch);

	}//end closePhaseWhenDone()

	/**
	 * Whether the stored batch has been asked to stop.
	 *
	 * Read from the store, not the copy in hand: the cancel arrives from
	 * another request while this unit runs.
	 *
	 * @param string $batchId Identifier of the batch.
	 *
	 * @return bool
	 */
	private function cancelRequested(string $batchId): bool {
		$stored = $this->stateService->getBatch($batchId);
		$status = (string)($stored['status'] ?? '');
		return in_array($status, [BatchLifecycleService::CANCELLING, BatchLifecycleService::CANCELLED], true);

	}//end cancelRequested()

	/**
	 * End the batch as cancelled, keeping every output already produced.
	 *
	 * @param string               $batchId   Identifier of the batch.
	 * @param array<string, mixed> $batch     The batch as this unit left it.
	 * @param int                  $processed Files this unit processed.
	 *
	 * @return array{batchId: string, batchStatus: string, processed: int, throttled: bool}
	 *
	 * @spec openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#requirement-cooperative-cancellation-req-ddras-004
	 */
	private function finishCancel(string $batchId, array $batch, int $processed): array {
		$batch['status'] = BatchLifecycleService::CANCELLED;
		$batch['throttled'] = false;
		$this->stateService->updateBatch($batchId, $batch);
		return ['batchId' => $batchId, 'batchStatus' => BatchLifecycleService::CANCELLED, 'processed' => $processed, 'throttled' => false];

	}//end finishCancel()

	/**
	 * Milliseconds since a hrtime() mark.
	 *
	 * @param int|float $start The mark.
	 *
	 * @return int
	 */
	private function elapsedMs(int|float $start): int {
		return (int)round((hrtime(true) - $start) / 1000000);

	}//end elapsedMs()
}//end class
