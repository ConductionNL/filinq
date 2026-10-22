<?php

/**
 * Tells owners and the custodian that their records were frozen or unfrozen.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category  Service
 * @package   OCA\Filinq\Service\LegalHold
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-2.3
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\LegalHold;

use OCA\Filinq\AppInfo\Application;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IUserManager;
use OCP\Notification\IManager;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Sends the place and release notifications.
 *
 * Imperative on purpose: the recipients are the owners of the records in
 * scope, a list the x-openregister-notifications `field` recipient cannot
 * express (it takes one user id).
 */
class LegalHoldNotifications {

	/**
	 * Subject of the notification on placement.
	 *
	 * @var string
	 */
	public const SUBJECT_PLACED = 'legal_hold_placed';

	/**
	 * Subject of the notification on release.
	 *
	 * @var string
	 */
	public const SUBJECT_RELEASED = 'legal_hold_released';

	/**
	 * Constructor.
	 *
	 * @param IManager        $notifications The notification manager.
	 * @param IUserManager    $users         To skip ids that name nobody.
	 * @param ITimeFactory    $time          The clock.
	 * @param LoggerInterface $logger        For a notification that could not be sent.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IManager $notifications,
		private readonly IUserManager $users,
		private readonly ITimeFactory $time,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Notify each user once.
	 *
	 * @param array<int, string>   $userIds The owners and the custodian.
	 * @param array<string, mixed> $case    The case, with `uuid`.
	 * @param string               $subject SUBJECT_PLACED or SUBJECT_RELEASED.
	 *
	 * @return array<int, string> The users who were notified.
	 *
	 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-2.3
	 */
	public function notify(array $userIds, array $case, string $subject): array {
		$notified = [];
		foreach (array_unique(array_filter($userIds, static fn ($uid): bool => is_string($uid) && $uid !== '')) as $userId) {
			if ($this->users->userExists($userId) === false) {
				continue;
			}

			try {
				$notification = $this->notifications->createNotification();
				$notification->setApp(Application::APP_ID)
					->setUser($userId)
					->setDateTime($this->time->getDateTime())
					->setObject('legalHoldCase', (string) $case['uuid'])
					->setSubject(
						$subject,
						[
							'name' => (string) ($case['name'] ?? ''),
							'caseReference' => (string) ($case['caseReference'] ?? ''),
						]
					);
				$this->notifications->notify($notification);
				$notified[] = $userId;
			} catch (Throwable $e) {
				$this->logger->warning(
					message: '[LegalHoldNotifications] a hold notification could not be sent',
					context: ['case' => $case['uuid'], 'user' => $userId, 'error' => $e->getMessage()]
				);
			}
		}//end foreach

		return $notified;

	}//end notify()
}//end class
