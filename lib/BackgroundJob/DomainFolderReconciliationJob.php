<?php

/**
 * The nightly reconciliation of every domain folder.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 *
 * @category BackgroundJob
 * @package  OCA\Filinq\BackgroundJob
 *
 * @author    Conduction Development Team <dev@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\BackgroundJob;

use OCA\Filinq\Service\DomainFolderReconciler;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Runs the reconciler once a night.
 *
 * 🔑 THE JOB IS A CLOCK, NOTHING MORE. Every decision about what a drifted
 * folder means lives in DomainFolderReconciler, where it can be tested without
 * a scheduler; the job exists to say "once a night" and to make sure a throw
 * inside one night does not take the scheduler's whole run down with it.
 *
 * @category BackgroundJob
 * @package  OCA\Filinq\BackgroundJob
 * @author   Conduction Development Team <dev@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
 */
class DomainFolderReconciliationJob extends TimedJob {

	/**
	 * Collaborators.
	 *
	 * @param ITimeFactory           $time       The clock the scheduler reads.
	 * @param DomainFolderReconciler $reconciler Does the night's work.
	 * @param LoggerInterface        $logger     Structured logger.
	 *
	 * @return void
	 */
	public function __construct(
		ITimeFactory $time,
		private readonly DomainFolderReconciler $reconciler,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct(time: $time);
		// Nightly, as REQ-CDF-02 asks.
		$this->setInterval(seconds: 86400);

	}//end __construct()

	/**
	 * Reconcile every domain folder once.
	 *
	 * @param mixed $argument Job arguments. The job is registered bare and takes none.
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter) `$argument` is Nextcloud's
	 * TimedJob signature, not a parameter this job chose. Dropping it changes the
	 * override into a different method and the job stops running.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	protected function run(mixed $argument): void {
		try {
			$this->reconciler->run();
		} catch (Throwable $e) {
			// The reconciler already turns a per-domain throw into a reported
			// refusal, so reaching here means the run itself could not start.
			// It is logged and not rethrown, so one app cannot stop the
			// instance's whole cron pass, but it is never silent: a night that
			// did not run must not read like a night that found nothing.
			$this->logger->error(
				'filinq.domain-reconcile.run-failed',
				['error' => $e->getMessage()]
			);
		}

	}//end run()
}//end class
