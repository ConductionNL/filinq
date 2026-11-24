<?php

/**
 * Expired Share Closer Job
 *
 * Every hour, closes the share records whose link ran out or was removed in Files.
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

use OCA\Filinq\Service\Share\DocumentShareService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Moves share records past their expiry to `expired`, and records whose
 * share was deleted in Files to `revoked`, deleting a bundle's archive
 * either way (share-document-links D7, REQ-SDL-004).
 *
 * The lifecycle has no clock of its own, so a timed job does it. Nextcloud
 * already refuses an expired link on access; this job keeps the record
 * and the sharer's Files in step with that.
 *
 * @category BackgroundJob
 * @package  OCA\Filinq\BackgroundJob
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/share-document-links/tasks.md#task-2.5
 */
class ExpiredShareCloserJob extends TimedJob {

	/**
	 * Seconds between runs.
	 *
	 * @var int
	 */
	public const INTERVAL = 3600;

	/**
	 * Constructor.
	 *
	 * @param ITimeFactory         $clock  The clock.
	 * @param DocumentShareService $shares Closes the lapsed share records.
	 * @param LoggerInterface      $logger Logs what the run did.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/share-document-links/tasks.md#task-2.5
	 */
	public function __construct(
		ITimeFactory $clock,
		private readonly DocumentShareService $shares,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct(time: $clock);
		$this->setInterval(seconds: self::INTERVAL);

	}//end __construct()

	/**
	 * Close what ran out or was removed in Files.
	 *
	 * @param mixed $argument Unused; the job takes no arguments.
	 *
	 * @return void
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter) `$argument` is Nextcloud's job argument; the base
	 * class dictates the signature and this job takes none.
	 *
	 * @spec openspec/changes/share-document-links/tasks.md#task-2.5
	 */
	protected function run(mixed $argument): void {
		try {
			$counts = $this->shares->closeLapsed();
		} catch (Throwable $e) {
			$this->logger->warning('[ExpiredShareCloserJob] The share records could not be read', ['exception' => $e->getMessage()]);
			return;
		}

		if (($counts['expired'] + $counts['revoked'] + $counts['failed']) > 0) {
			$this->logger->info('[ExpiredShareCloserJob] Closed lapsed share records', $counts);
		}

	}//end run()
}//end class
