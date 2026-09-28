<?php

/**
 * Periodic Document Job
 *
 * Every hour, runs the periodic documents whose cadence has come round.
 *
 * @category  BackgroundJob
 * @package   OCA\Filinq\BackgroundJob
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/periodic-documents-on-a-schedule/specs/document-creatie-sjablonen/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\BackgroundJob;

use DateTimeImmutable;
use OCA\Filinq\Service\PeriodicDocumentService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use Psr\Log\LoggerInterface;

/**
 * The hourly sweep over the periodic documents.
 *
 * @category BackgroundJob
 * @package  OCA\Filinq\BackgroundJob
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/periodic-documents-on-a-schedule/specs/document-creatie-sjablonen/spec.md
 */
class PeriodicDocumentJob extends TimedJob {

	/**
	 * Constructor.
	 *
	 * @param ITimeFactory            $clock    The clock.
	 * @param PeriodicDocumentService $periodic Runs the due schedules.
	 * @param LoggerInterface         $logger   Logs what the sweep did.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly ITimeFactory $clock,
		private readonly PeriodicDocumentService $periodic,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct(time: $clock);
		// Hourly: a daily document is at most an hour late, and a sweep over
		// schedules that are not due writes nothing.
		$this->setInterval(seconds: 3600);

	}//end __construct()

	/**
	 * Run the due schedules.
	 *
	 * @param mixed $argument Unused.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/periodic-documents-on-a-schedule/specs/document-creatie-sjablonen/spec.md
	 */
	protected function run(mixed $argument): void {
		$now = (new DateTimeImmutable())->setTimestamp($this->clock->getTime());
		$summary = $this->periodic->runDue(now: $now);

		if ($summary['ran'] > 0 || $summary['failed'] > 0) {
			$this->logger->info(
				message: '[PeriodicDocumentJob] periodic documents: {ran} produced, {failed} failed',
				context: $summary
			);
		}

	}//end run()
}//end class
