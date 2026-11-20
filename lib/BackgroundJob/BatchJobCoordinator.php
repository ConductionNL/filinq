<?php

/**
 * Batch Job Coordinator: the cron job that runs background anonymization batches in work units.
 *
 * @category  BackgroundJob
 * @package   OCA\Filinq\BackgroundJob
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

namespace OCA\Filinq\BackgroundJob;

use OCA\Filinq\Service\BatchRouting;
use OCA\Filinq\Service\BatchStateService;
use OCA\Filinq\Service\BatchWorkUnit;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\IAppConfig;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Every five minutes, claims the oldest background batch with work left and
 * runs one work unit on it: at most `batch.files_per_tick` files (default
 * 25) and at most `batch.seconds_per_tick` seconds (default 120), whichever
 * ends first. Parallel runs are switched off, so one instance never runs
 * two work units at once. With `batch.engine = legacy-sync` it does nothing.
 *
 * @spec openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#requirement-chunked-background-processing-under-cron-req-ddras-002
 */
class BatchJobCoordinator extends TimedJob {

	/**
	 * Default file budget of one work unit.
	 */
	public const DEFAULT_FILES_PER_TICK = 25;

	/**
	 * Default time budget of one work unit, in seconds.
	 */
	public const DEFAULT_SECONDS_PER_TICK = 120;

	/**
	 * How often the coordinator runs, in seconds.
	 */
	public const INTERVAL = 300;

	/**
	 * Constructor.
	 *
	 * @param ITimeFactory      $time         The clock.
	 * @param BatchStateService $stateService Lists the batches with work left.
	 * @param BatchWorkUnit     $workUnit     Runs one unit of a batch.
	 * @param BatchRouting      $routing      The engine switch.
	 * @param IAppConfig        $appConfig    The tick budgets.
	 * @param LoggerInterface   $logger       Logger.
	 *
	 * @return void
	 */
	public function __construct(
		ITimeFactory $time,
		private readonly BatchStateService $stateService,
		private readonly BatchWorkUnit $workUnit,
		private readonly BatchRouting $routing,
		private readonly IAppConfig $appConfig,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct(time: $time);
		$this->setInterval(seconds: self::INTERVAL);
		$this->setAllowParallelRuns(allow: false);

	}//end __construct()

	/**
	 * The file budget of one work unit.
	 *
	 * @return int At least 1.
	 *
	 * @spec openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#requirement-chunked-background-processing-under-cron-req-ddras-002
	 */
	public function filesPerTick(): int {
		return max(
			1,
			(int)$this->appConfig->getValueString('filinq', 'batch.files_per_tick', (string)self::DEFAULT_FILES_PER_TICK)
		);

	}//end filesPerTick()

	/**
	 * The time budget of one work unit.
	 *
	 * @return int Seconds, at least 1.
	 *
	 * @spec openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#requirement-chunked-background-processing-under-cron-req-ddras-002
	 */
	public function secondsPerTick(): int {
		return max(
			1,
			(int)$this->appConfig->getValueString('filinq', 'batch.seconds_per_tick', (string)self::DEFAULT_SECONDS_PER_TICK)
		);

	}//end secondsPerTick()

	/**
	 * Run one work unit on the oldest background batch with work left.
	 *
	 * @param mixed $argument Unused.
	 *
	 * @return void
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter) `$argument` is Nextcloud's job argument; the base
	 * class dictates the signature and this job takes none.
	 *
	 * @spec openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#requirement-chunked-background-processing-under-cron-req-ddras-002
	 */
	protected function run(mixed $argument): void {
		if ($this->routing->backgroundEnabled() === false) {
			return;
		}

		try {
			$batches = $this->stateService->listActiveBackground();
		} catch (Throwable $e) {
			$this->logger->warning('BatchJobCoordinator: could not list background batches', ['error' => $e->getMessage()]);
			return;
		}

		if ($batches === []) {
			return;
		}

		$batchId = (string)$batches[0]['batchId'];
		try {
			$outcome = $this->workUnit->run(
				batchId: $batchId,
				maxFiles: $this->filesPerTick(),
				maxSeconds: $this->secondsPerTick()
			);
		} catch (Throwable $e) {
			$this->logger->error(
				'BatchJobCoordinator: work unit failed',
				['batchId' => $batchId, 'error' => $e->getMessage()]
			);
			return;
		}

		$this->logger->info('BatchJobCoordinator: work unit done', $outcome);

	}//end run()
}//end class
