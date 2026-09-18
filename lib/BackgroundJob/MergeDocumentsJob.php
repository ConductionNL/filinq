<?php

/**
 * Merge Documents Job
 *
 * Runs the merges that were too large to do while somebody waited. The
 * threshold is `mergeSyncThresholdPages`: under it the endpoint merges inline
 * and answers with the finished job, over it the endpoint answers `queued` and
 * this job picks it up.
 *
 * The checks are NOT repeated here, and that is deliberate: they ran before the
 * job existed, and a queued job is the record that they passed. Re-running them
 * as the background user would ask a different question with a different
 * answer.
 *
 * @category  BackgroundJob
 * @package   OCA\Filinq\BackgroundJob
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\BackgroundJob;

use OCA\Filinq\Service\DocumentMergeService;
use OCA\Filinq\Service\MergeJobRepository;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Picks up queued merges and runs them.
 *
 * @category BackgroundJob
 * @package  OCA\Filinq\BackgroundJob
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
 */
class MergeDocumentsJob extends TimedJob {

	/**
	 * How often the queue is looked at, in seconds.
	 *
	 * @var int
	 */
	private const INTERVAL = 300;

	/**
	 * Constructor.
	 *
	 * @param ITimeFactory $time The clock the job runner uses.
	 * @param MergeJobRepository $jobs The job store.
	 * @param DocumentMergeService $merges The merge itself.
	 * @param IUserSession $userSession The session the merge runs as.
	 * @param LoggerInterface $logger Logger for diagnostics.
	 *
	 * @return void
	 */
	public function __construct(
		ITimeFactory $time,
		private readonly MergeJobRepository $jobs,
		private readonly DocumentMergeService $merges,
		private readonly IUserSession $userSession,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct($time);
		$this->setInterval(self::INTERVAL);

	}//end __construct()

	/**
	 * Run every queued merge.
	 *
	 * One failing job does not stop the rest of the queue: a batch that stopped
	 * at the first bad input would leave every later merge waiting with nothing
	 * saying why.
	 *
	 * @param mixed $argument The job argument, unused.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
	 */
	protected function run($argument): void {
		foreach ($this->jobs->findQueued() as $job) {
			$uuid = (string)($job['uuid'] ?? '');
			if ($uuid === '') {
				continue;
			}

			try {
				$this->merges->resume(job: $job);
			} catch (Throwable $e) {
				$this->logger->error(
					message: '[MergeDocumentsJob] a queued merge could not be run',
					context: ['file' => __FILE__, 'line' => __LINE__, 'uuid' => $uuid, 'error' => $e->getMessage()]
				);
			}
		}

	}//end run()
}//end class
