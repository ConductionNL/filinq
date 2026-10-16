<?php

/**
 * Renders the legal hold notifications.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category  Notification
 * @package   OCA\Filinq\Notification
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-2.3
 */

declare(strict_types=1);

namespace OCA\Filinq\Notification;

use OCA\Filinq\AppInfo\Application;
use OCA\Filinq\Service\LegalHold\LegalHoldNotifications;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;
use OCP\Notification\UnknownNotificationException;

/**
 * Turns a hold notification into text in the recipient's language.
 */
class LegalHoldNotifier implements INotifier {

	/**
	 * Constructor.
	 *
	 * @param IFactory      $l10nFactory Translations per language.
	 * @param IURLGenerator $urls        For the absolute icon and link.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IFactory $l10nFactory,
		private readonly IURLGenerator $urls,
	) {

	}//end __construct()

	/**
	 * The notifier id.
	 *
	 * @return string The id.
	 */
	public function getID(): string {
		return Application::APP_ID . '-legal-hold';

	}//end getID()

	/**
	 * The notifier name.
	 *
	 * @return string The name.
	 */
	public function getName(): string {
		return $this->l10nFactory->get(Application::APP_ID)->t('Legal holds');

	}//end getName()

	/**
	 * Render a hold notification.
	 *
	 * @param INotification $notification The notification.
	 * @param string        $languageCode The recipient's language.
	 *
	 * @return INotification The rendered notification.
	 *
	 * @throws UnknownNotificationException When it is not ours.
	 *
	 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-2.3
	 */
	public function prepare(INotification $notification, string $languageCode): INotification {
		$subject = $notification->getSubject();
		if ($notification->getApp() !== Application::APP_ID
			|| in_array($subject, [LegalHoldNotifications::SUBJECT_PLACED, LegalHoldNotifications::SUBJECT_RELEASED], true) === false
		) {
			throw new UnknownNotificationException();
		}

		$l10n = $this->l10nFactory->get(Application::APP_ID, $languageCode);
		$name = (string) ($notification->getSubjectParameters()['name'] ?? '');
		$text = $l10n->t('Legal hold "%s" is released: records in it may be destroyed or deleted again when their term allows.', [$name]);
		if ($subject === LegalHoldNotifications::SUBJECT_PLACED) {
			$text = $l10n->t('Legal hold "%s" freezes records of yours: they cannot be destroyed or deleted until the hold is released.', [$name]);
		}

		$notification->setParsedSubject($text)
			->setIcon($this->urls->getAbsoluteURL($this->urls->imagePath(Application::APP_ID, 'app-dark.svg')))
			->setLink($this->urls->getAbsoluteURL($this->urls->linkToRoute(Application::APP_ID . '.dashboard.page') . 'legal-holds'));

		return $notification;

	}//end prepare()
}//end class
