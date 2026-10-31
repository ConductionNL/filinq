<?php

/**
 * Batch Correspondence Job
 *
 * Background job for processing large correspondence batches asynchronously.
 * Dispatched by CorrespondenceService when the batch size exceeds the
 * synchronous limit (>10 recipients).
 *
 * @category  BackgroundJob
 * @package   OCA\Filinq\BackgroundJob
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
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

use Exception;
use OCA\Filinq\Kernel\Contracts\CorrespondenceServiceInterface;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
use Psr\Log\LoggerInterface;

/**
 * Queued job for batch correspondence generation
 *
 * @category BackgroundJob
 * @package  OCA\Filinq\BackgroundJob
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/letter-correspondence-generation/tasks.md#task-2
 */
class BatchCorrespondenceJob extends QueuedJob {
	/**
	 * Constructor for BatchCorrespondenceJob
	 *
	 * @param ITimeFactory $time Time factory
	 * @param CorrespondenceServiceInterface $corrSvc Correspondence generation service
	 * @param LoggerInterface $logger Logger for error reporting
	 *
	 * @return void
	 */
	public function __construct(
		ITimeFactory $time,
		private readonly CorrespondenceServiceInterface $corrSvc,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct(time: $time);

	}//end __construct()

	/**
	 * Run the batch correspondence generation
	 *
	 * Processes each recipient individually. Updates job status after each
	 * recipient so progress can be tracked. Individual failures do not
	 * abort the batch.
	 *
	 * @param array $argument Job arguments containing jobId, templateId,
	 *                        recipientIds, and options
	 *
	 * @return void
	 *
	 * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-batch-correspondence-generation
	 */
	protected function run(mixed $argument): void {
		$jobId = $argument['jobId'] ?? '';
		$templateId = $argument['templateId'] ?? '';
		$recipientIds = $argument['recipientIds'] ?? [];
		$options = $argument['options'] ?? [];
		// SB1 fix: carry the ownerUserId stored at dispatch time so every
		// mid-job progress update retains it (otherwise ownership check reads null).
		$ownerUserId = (string)($options['userId'] ?? '');

		if (empty($jobId) === true || empty($templateId) === true) {
			$this->logger->error(
				message: 'BatchCorrespondenceJob: missing required arguments',
				context: ['argument' => $argument]
			);
			return;
		}

		$this->processRecipients(
			job: ['jobId' => $jobId, 'templateId' => $templateId, 'options' => $options, 'ownerUserId' => $ownerUserId],
			recipientIds: $recipientIds
		);

	}//end run()

	/**
	 * Generate a letter per recipient and report the progress after each one.
	 *
	 * @param array{jobId: string, templateId: string, options: array, ownerUserId: string} $job          The job.
	 * @param array                                                                         $recipientIds Array of recipient UUIDs
	 *
	 * @return void
	 *
	 * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-batch-correspondence-generation
	 */
	private function processRecipients(array $job, array $recipientIds): void {
		$tally = new BatchTally(total: count($recipientIds));
		$this->storeProgress(job: $job, progress: $tally->progress(status: 'processing'));

		foreach ($recipientIds as $recipientId) {
			$tally->record(result: $this->generateFor(job: $job, recipientId: $recipientId));
			$this->storeProgress(job: $job, progress: $tally->progress(status: 'processing'));
		}

		$final = $tally->progress(status: 'completed');
		$this->storeProgress(job: $job, progress: $final);

		$this->logger->info(
			message: "BatchCorrespondenceJob completed: {$final['completed']}/{$final['total']} successful, {$final['errors']} errors",
			context: ['jobId' => $job['jobId']]
		);

	}//end processRecipients()

	/**
	 * Generate the letter for one recipient.
	 *
	 * @param array{jobId: string, templateId: string, options: array, ownerUserId: string} $job         The job.
	 * @param mixed                                                                         $recipientId The recipient UUID.
	 *
	 * @return array<string, mixed> The recipient's result row.
	 *
	 * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-batch-correspondence-generation
	 */
	private function generateFor(array $job, mixed $recipientId): array {
		$dataRefs = [
			[
				'register' => $job['options']['register'] ?? '',
				'schema' => $job['options']['schema'] ?? '',
				'id' => $recipientId,
			],
		];

		try {
			$this->corrSvc->generate(
				templateId: $job['templateId'],
				dataRefs: $dataRefs,
				options: $job['options']
			);
		} catch (Exception $e) {
			$this->logger->warning(
				message: 'Batch correspondence failed for recipient: ' . $e->getMessage(),
				context: [
					'jobId' => $job['jobId'],
					'recipientId' => $recipientId,
				]
			);

			return [
				'recipientId' => $recipientId,
				'status' => 'error',
				'error' => $e->getMessage(),
			];
		}//end try

		return [
			'recipientId' => $recipientId,
			'status' => 'success',
		];

	}//end generateFor()

	/**
	 * Store the job's progress, keeping the owner so the ownership check holds.
	 *
	 * @param array{jobId: string, templateId: string, options: array, ownerUserId: string} $job      The job.
	 * @param array<string, mixed>                                                          $progress The tally's progress.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-batch-correspondence-generation
	 */
	private function storeProgress(array $job, array $progress): void {
		$this->corrSvc->storeJobStatus(
			jobId: $job['jobId'],
			data: $progress + ['ownerUserId' => $job['ownerUserId']]
		);

	}//end storeProgress()
}//end class
