<?php

/**
 * Filinq job queue over Nextcloud's job list
 *
 * Maps each job name in JobQueueInterface to the job class that runs it.
 *
 * @category  BackgroundJob
 * @package   OCA\Filinq\BackgroundJob
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\BackgroundJob;

use InvalidArgumentException;
use OCA\Filinq\Kernel\JobQueueInterface;
use OCP\BackgroundJob\IJobList;

/**
 * Queues Filinq background jobs on Nextcloud's job list.
 *
 * @category BackgroundJob
 * @package  OCA\Filinq\BackgroundJob
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural adapter, no functional requirement.
 */
class JobQueue implements JobQueueInterface {
	/**
	 * Job name to job class.
	 *
	 * @var array<string, class-string>
	 */
	private const CLASSES = [
		self::BATCH_CORRESPONDENCE => BatchCorrespondenceJob::class,
		self::BATCH_DOCUMENT       => BatchDocumentJob::class,
		self::BATCH_PRINT          => BatchPrintJob::class,
		self::BULK_SIGNING         => BulkSigningJob::class,
		self::FOLDER_EXTRACTION    => FolderExtractionJob::class,
		self::INTAKE_OCR           => IntakeOcrJob::class,
	];

	/**
	 * Constructor.
	 *
	 * @param IJobList $jobList Nextcloud's job list.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IJobList $jobList,
	) {

	}//end __construct()

	/**
	 * Queue a job.
	 *
	 * @param string $job One of the job name constants.
	 * @param array<string, mixed> $argument The job argument.
	 *
	 * @return void
	 *
	 * @spec exclude Structural adapter, no functional requirement.
	 */
	public function add(string $job, array $argument): void {
		$this->jobList->add(job: $this->classOf(job: $job), argument: $argument);

	}//end add()

	/**
	 * Remove a queued job.
	 *
	 * @param string $job One of the job name constants.
	 * @param array<string, mixed> $argument The argument the job was queued with.
	 *
	 * @return void
	 *
	 * @spec exclude Structural adapter, no functional requirement.
	 */
	public function remove(string $job, array $argument): void {
		$this->jobList->remove(job: $this->classOf(job: $job), argument: $argument);

	}//end remove()

	/**
	 * The job class for a job name.
	 *
	 * @param string $job The job name.
	 *
	 * @return class-string The job class.
	 *
	 * @throws InvalidArgumentException When the job name is unknown.
	 */
	private function classOf(string $job): string {
		if (isset(self::CLASSES[$job]) === false) {
			throw new InvalidArgumentException(message: 'Unknown job: ' . $job);
		}

		return self::CLASSES[$job];

	}//end classOf()
}//end class
