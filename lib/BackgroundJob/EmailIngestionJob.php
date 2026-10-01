<?php

/**
 * Email ingestion job
 *
 * Every five minutes, files up to `filinq.email_ingestion.files_per_tick`
 * emails (default 25) from the watched inbox folders into their dossiers.
 * A bulk drop drains over successive ticks; overlapping runs are harmless
 * because filing is idempotent.
 *
 * @category  BackgroundJob
 * @package   OCA\Filinq\BackgroundJob
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-10-01-email-ingestion/tasks.md#2-4
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\BackgroundJob;

use OCA\Filinq\Service\EmailIngestion\EmailIngestionService;
use OCA\Filinq\Service\EmailIngestion\EmailIngestionSettings;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use Psr\Log\LoggerInterface;

/**
 * Bounded watched-folder scan.
 */
class EmailIngestionJob extends TimedJob {

	/**
	 * Constructor.
	 *
	 * @param ITimeFactory           $clock     The clock.
	 * @param EmailIngestionService  $ingestion The ingestion.
	 * @param EmailIngestionSettings $settings  The per-tick budget.
	 * @param LoggerInterface        $logger    The logger.
	 *
	 * @return void
	 */
	public function __construct(
		ITimeFactory $clock,
		private readonly EmailIngestionService $ingestion,
		private readonly EmailIngestionSettings $settings,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct(time: $clock);
		$this->setInterval(seconds: 300);

	}//end __construct()

	/**
	 * File one tick's worth of email.
	 *
	 * @param mixed $argument Unused.
	 *
	 * @return void
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter) `$argument` is Nextcloud's
	 * TimedJob signature; this job takes its work from the inbox mapping.
	 *
	 * @spec openspec/changes/archive/2026-10-01-email-ingestion/tasks.md#2-4
	 */
	protected function run(mixed $argument): void {
		$summary = $this->ingestion->scan(limit: $this->settings->filesPerTick());
		if ($summary['processed'] > 0) {
			$this->logger->info('[EmailIngestionJob] {filed} filed, {failed} failed, {duplicates} duplicates', $summary);
		}

	}//end run()
}//end class
