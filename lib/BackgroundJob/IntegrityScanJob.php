<?php

/**
 * Filinq Integrity Scan Job
 *
 * Runs the whole-collection integrity scan once a night and tells every
 * administrator when it found something (whole-collection-integrity-scan
 * REQ-DIS-02). A clean run is stored and tells nobody.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category  BackgroundJob
 * @package   OCA\Filinq\BackgroundJob
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/whole-collection-integrity-scan/tasks.md#task-2-nightly-job-occ-command-and-notification
 */

declare(strict_types=1);

namespace OCA\Filinq\BackgroundJob;

use DateTime;
use OCA\Filinq\Kernel\AppIdentity;
use OCA\Filinq\Notification\IntegrityScanNotifier;
use OCA\Filinq\Service\IntegrityScanService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\IGroupManager;
use OCP\Notification\IManager as INotificationManager;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * The nightly integrity scan.
 *
 * @spec openspec/changes/whole-collection-integrity-scan/specs/document-versions/spec.md#requirement-the-scan-runs-nightly-and-on-demand-and-administrators-see-the-result-req-dis-02
 */
class IntegrityScanJob extends TimedJob {

	/**
	 * Once a day.
	 */
	private const INTERVAL = 86400;

	/**
	 * The trigger a nightly run is stored with.
	 */
	public const TRIGGER = 'nightly';

	/**
	 * Constructor.
	 *
	 * @param ITimeFactory         $time          The clock the scheduler uses.
	 * @param IntegrityScanService $scan          Runs and stores the scan.
	 * @param IGroupManager        $groups        The administrators.
	 * @param INotificationManager $notifications Tells them.
	 * @param LoggerInterface      $logger        Records a failed run or notification.
	 *
	 * @return void
	 */
	public function __construct(
		ITimeFactory $time,
		private readonly IntegrityScanService $scan,
		private readonly IGroupManager $groups,
		private readonly INotificationManager $notifications,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct(time: $time);
		$this->setInterval(seconds: self::INTERVAL);

	}//end __construct()

	/**
	 * Run the scan and notify the administrators of its findings.
	 *
	 * @param mixed $argument Unused.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/whole-collection-integrity-scan/specs/document-versions/spec.md#requirement-the-scan-runs-nightly-and-on-demand-and-administrators-see-the-result-req-dis-02
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter) -- the base class dictates the signature.
	 */
	protected function run($argument): void {
		try {
			$run = $this->scan->run(trigger: self::TRIGGER);
		} catch (Throwable $e) {
			$this->logger->error('[IntegrityScanJob] the integrity scan failed', ['error' => $e->getMessage()]);
			return;
		}

		$this->notifyAdministrators(run: $run);

	}//end run()

	/**
	 * One notification per administrator for a run with findings; none for a clean run.
	 *
	 * @param array<string, mixed> $run The stored run.
	 *
	 * @return int How many administrators were notified.
	 *
	 * @spec openspec/changes/whole-collection-integrity-scan/specs/document-versions/spec.md#requirement-the-scan-runs-nightly-and-on-demand-and-administrators-see-the-result-req-dis-02
	 */
	public function notifyAdministrators(array $run): int {
		$counts = [];
		foreach (IntegrityScanService::KINDS as $kind) {
			$counts[$kind] = (int) ($run[$kind] ?? 0);
		}

		$admins = $this->groups->get('admin');
		// Only a missing or changed file calls for an administrator; an orphaned copy alone does not.
		if (($counts['missing'] + $counts['changed']) === 0 || $admins === null) {
			return 0;
		}

		$sent = 0;
		foreach ($admins->getUsers() as $admin) {
			try {
				$notification = $this->notifications->createNotification();
				$notification->setApp(AppIdentity::APP_ID)
					->setUser($admin->getUID())
					->setDateTime(new DateTime())
					->setObject('integrityScan', (string) ($run['startedAt'] ?? 'run'))
					->setSubject(IntegrityScanNotifier::SUBJECT, $counts);
				$this->notifications->notify($notification);
				$sent++;
			} catch (Throwable $e) {
				$this->logger->warning('[IntegrityScanJob] an administrator was not notified', ['error' => $e->getMessage()]);
			}
		}

		return $sent;

	}//end notifyAdministrators()
}//end class
