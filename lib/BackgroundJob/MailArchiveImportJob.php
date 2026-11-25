<?php

/**
 * Mail archive import job
 *
 * Every five minutes, the oldest importing mail archive import takes one
 * bounded unit of messages from its saved cursor, so a large archive drains
 * over many runs and a crash resumes where the last unit stopped (design D3).
 *
 * @category  BackgroundJob
 * @package   OCA\Filinq\BackgroundJob
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-11-25-intake-mailbox-archive-import/tasks.md#3-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\BackgroundJob;

use OCA\Filinq\Service\MailArchive\MailArchiveImportService;
use OCA\Filinq\Service\Support\ActingUser;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * One work unit of a mail archive import per run.
 *
 * @spec openspec/changes/archive/2026-11-25-intake-mailbox-archive-import/specs/mailbox-archive-import/spec.md#requirement-a-large-archive-drains-in-resumable-units-req-mai-004
 */
class MailArchiveImportJob extends TimedJob {

	/**
	 * Constructor.
	 *
	 * @param ITimeFactory             $clock   The clock.
	 * @param MailArchiveImportService $imports The imports.
	 * @param LoggerInterface          $logger  The logger.
	 * @param ActingUser               $acting  Runs the unit as the user who started the import.
	 *
	 * @return void
	 */
	public function __construct(
		ITimeFactory $clock,
		private readonly MailArchiveImportService $imports,
		private readonly LoggerInterface $logger,
		private readonly ActingUser $acting,
	) {
		parent::__construct(time: $clock);
		$this->setInterval(seconds: 300);

	}//end __construct()

	/**
	 * Take one unit.
	 *
	 * @param mixed $argument Unused.
	 *
	 * @return void
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter) `$argument` is Nextcloud's
	 * TimedJob signature; this job takes its work from the importing imports.
	 *
	 * @spec openspec/changes/archive/2026-11-25-intake-mailbox-archive-import/tasks.md#3-1
	 */
	protected function run(mixed $argument): void {
		try {
			// With the rights of the user who started it: a job has no user, and OpenRegister refuses an anonymous write.
			$import = $this->acting->run(userId: $this->imports->nextRequester(), operation: fn (): ?array => $this->imports->runUnit());
		} catch (Throwable $e) {
			// The cursor was not saved, so the next run takes the same unit again.
			$this->logger->error('[MailArchiveImportJob] a work unit failed', ['exception' => $e->getMessage()]);
			return;
		}

		if ($import !== null) {
			$this->logger->info(
				'[MailArchiveImportJob] import {uuid} is {status}',
				['uuid' => ($import['uuid'] ?? ''), 'status' => ($import['status'] ?? '')]
			);
		}

	}//end run()
}//end class
