<?php

/**
 * Where an asynchronous bulk generation job lives
 *
 * A bulk generation above the synchronous limit is queued as a
 * BatchDocumentJob, and its progress is kept in app config under
 * `document_job_<jobId>` so the status endpoint can answer while the job
 * runs. This class owns both halves: the status record and the queueing.
 * It was split out of DocumentService, which reached the app config through
 * the container on every read and write.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/document-creatie-sjablonen/tasks.md#task-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use Exception;
use OCA\Filinq\BackgroundJob\BatchDocumentJob;
use OCP\BackgroundJob\IJobList;
use OCP\IAppConfig;
use Psr\Log\LoggerInterface;

/**
 * Status records and queueing of bulk document jobs.
 */
class DocumentJobStore {

	/**
	 * Constructor.
	 *
	 * @param IAppConfig      $appConfig Holds the status record per job.
	 * @param IJobList        $jobList   Nextcloud job list for async processing.
	 * @param LoggerInterface $logger    Logger for a status that cannot be read or written.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IAppConfig $appConfig,
		private readonly IJobList $jobList,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * The status of a job.
	 *
	 * @param string $jobId The job UUID.
	 *
	 * @return array|null The job status, or null if not found.
	 *
	 * @spec openspec/changes/document-creatie-sjablonen/tasks.md#task-1
	 */
	public function get(string $jobId): ?array {
		try {
			$value = $this->appConfig->getValueString('filinq', 'document_job_' . $jobId, '');
			if ($value === '') {
				return null;
			}

			return json_decode($value, true);
		} catch (Exception $e) {
			$this->logger->error(
				message: 'Failed to load document job status: ' . $e->getMessage(),
				context: ['jobId' => $jobId]
			);
			return null;
		}

	}//end get()

	/**
	 * Store the status of a job.
	 *
	 * @param string $jobId  The job UUID.
	 * @param array  $status The status data to store.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/document-creatie-sjablonen/tasks.md#task-1
	 */
	public function put(string $jobId, array $status): void {
		try {
			$this->appConfig->setValueString('filinq', 'document_job_' . $jobId, json_encode($status));
		} catch (Exception $e) {
			$this->logger->error(
				message: 'Failed to store document job status: ' . $e->getMessage(),
				context: ['jobId' => $jobId]
			);
		}

	}//end put()

	/**
	 * Record a job's first status and queue it.
	 *
	 * @param string $jobId    The job UUID.
	 * @param array  $status   The initial status.
	 * @param array  $argument The BatchDocumentJob argument.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/document-output-destinations-and-bulk-retention/specs/document-creatie-sjablonen/spec.md#req-ddob-006
	 */
	public function enqueue(string $jobId, array $status, array $argument): void {
		$this->put(jobId: $jobId, status: $status);
		$this->jobList->add(job: BatchDocumentJob::class, argument: $argument);

	}//end enqueue()

	/**
	 * A cryptographically secure job UUID.
	 *
	 * @return string A RFC-4122 v4 UUID job identifier.
	 *
	 * @spec openspec/changes/document-creatie-sjablonen/tasks.md#task-1
	 */
	public function newId(): string {
		$data = random_bytes(16);
		$data[6] = chr(ord($data[6]) & 0x0f | 0x40);
		$data[8] = chr(ord($data[8]) & 0x3f | 0x80);
		return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));

	}//end newId()
}//end class
