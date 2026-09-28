<?php

/**
 * Batch Print Job
 *
 * Background job for rendering large print batches. Dispatched by
 * PrintJobService when a job holds more letters than it renders during the
 * request; the rendering itself is PrintJobService::renderJob(), the same
 * code a small job runs.
 *
 * @category BackgroundJob
 * @package  OCA\Filinq\BackgroundJob
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/print-jobs-in-the-app/specs/print-preview/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\BackgroundJob;

use Exception;
use OCA\Filinq\Service\PrintJobService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
use Psr\Log\LoggerInterface;

/**
 * Renders the letters of one queued print job.
 *
 * @category BackgroundJob
 * @package  OCA\Filinq\BackgroundJob
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/print-jobs-in-the-app/specs/print-preview/spec.md
 */
class BatchPrintJob extends QueuedJob {

	/**
	 * Constructor.
	 *
	 * @param ITimeFactory    $time        Time factory
	 * @param PrintJobService $printJobSvc The print job service
	 * @param LoggerInterface $logger      Logger
	 *
	 * @return void
	 */
	public function __construct(
		ITimeFactory $time,
		private readonly PrintJobService $printJobSvc,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct(time: $time);

	}//end __construct()

	/**
	 * Render the job's letters.
	 *
	 * @param mixed $argument {jobId, templateId, items, options}
	 *
	 * @return void
	 *
	 * @spec openspec/changes/print-jobs-in-the-app/tasks.md#task-1.2
	 */
	protected function run(mixed $argument): void {
		$jobId = (string) ($argument['jobId'] ?? '');
		$templateId = (string) ($argument['templateId'] ?? '');

		if ($jobId === '' || $templateId === '') {
			$this->logger->error(
				message: 'BatchPrintJob: missing required arguments',
				context: ['argument' => $argument]
			);
			return;
		}

		try {
			$job = $this->printJobSvc->renderJob(
				jobId: $jobId,
				templateId: $templateId,
				items: (array) ($argument['items'] ?? []),
				options: (array) ($argument['options'] ?? [])
			);
			$this->logger->info(
				message: 'BatchPrintJob rendered ' . (int) ($job['rendered'] ?? 0) . ' of ' . (int) ($job['total'] ?? 0) . ' letters',
				context: ['jobId' => $jobId, 'status' => $job['status'] ?? '']
			);
		} catch (Exception $e) {
			$this->logger->error(
				message: 'BatchPrintJob failed: ' . $e->getMessage(),
				context: ['jobId' => $jobId, 'exception' => $e]
			);
		}

	}//end run()
}//end class
