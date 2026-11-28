<?php

/**
 * Folder Extraction Background Job
 *
 * The fallback job of an in-request folder batch: extracts and detects every
 * queued file through the same work-unit mechanic the cron coordinator uses
 * for background batches, and leaves the batch in `review`. Anonymisation,
 * with its output-folder layout, is the batch anonymize step's job.
 *
 * @category  BackgroundJob
 * @package   OCA\Filinq\BackgroundJob
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/anonymisation-folder-output-folder-layout/tasks.md#task-2
 * @spec openspec/changes/redaction-at-scale/specs/folder-batch-analysis/spec.md#requirement-background-extraction-via-queuedjob
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\BackgroundJob;

use OCA\Filinq\Kernel\Contracts\BatchStateServiceInterface;
use OCA\Filinq\Service\BatchWorkUnit;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
use Psr\Log\LoggerInterface;

/**
 * Extracts a folder batch in work units, one file at a time with the batch
 * written after each file, until no file is queued; a requested cancel stops
 * it between files. A background batch is left alone: the cron coordinator
 * owns it.
 *
 * @category BackgroundJob
 * @package  OCA\Filinq\BackgroundJob
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/redaction-at-scale/specs/folder-batch-analysis/spec.md#requirement-background-extraction-via-queuedjob
 */
class FolderExtractionJob extends QueuedJob {

	/**
	 * Files per work unit of the fallback run.
	 */
	private const FILES_PER_UNIT = 25;

	/**
	 * Constructor
	 *
	 * @param ITimeFactory               $time         Time factory (parent requirement)
	 * @param BatchStateServiceInterface $stateService Batch state persistence
	 * @param BatchWorkUnit              $workUnit     Runs one bounded unit of the batch
	 * @param LoggerInterface            $logger       Logger
	 *
	 * @return void
	 */
	public function __construct(
		ITimeFactory $time,
		private readonly BatchStateServiceInterface $stateService,
		private readonly BatchWorkUnit $workUnit,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct(time: $time);

	}//end __construct()

	/**
	 * Extract every queued file of the batch.
	 *
	 * @param mixed $argument Job argument containing batchId
	 *
	 * @return void
	 *
	 * @spec openspec/changes/redaction-at-scale/specs/folder-batch-analysis/spec.md#requirement-background-extraction-via-queuedjob
	 */
	protected function run(mixed $argument): void {
		$batchId = (string)($argument['batchId'] ?? '');
		if ($batchId === '') {
			$this->logger->error('FolderExtractionJob: missing batchId');
			return;
		}

		$batch = $this->stateService->getBatch($batchId);
		if ($batch === null) {
			$this->logger->error('FolderExtractionJob: batch not found', ['batchId' => $batchId]);
			return;
		}

		if (($batch['background'] ?? false) === true) {
			return;
		}

		$status = (string)($batch['status'] ?? '');
		if (in_array($status, ['uploading', 'uploaded', 'extracting'], true) === false) {
			return;
		}

		if ($status !== 'extracting') {
			$batch['status'] = 'extracting';
			$this->stateService->updateBatch($batchId, $batch);
		}

		// Unit after unit, so the per-file writes and the cancel check of the
		// work unit apply; each unit processes at least one file, so the loop
		// ends once nothing is queued.
		do {
			$outcome = $this->workUnit->run(batchId: $batchId, maxFiles: self::FILES_PER_UNIT, maxSeconds: 0);
		} while ($outcome['batchStatus'] === 'extracting' && $outcome['processed'] > 0);

		$this->logger->info(
			'FolderExtractionJob completed',
			['batchId' => $batchId, 'batchStatus' => $outcome['batchStatus']]
		);

	}//end run()
}//end class
