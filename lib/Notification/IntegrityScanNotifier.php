<?php

/**
 * Filinq Integrity Scan Notifier
 *
 * Renders the notification an administrator gets when the nightly integrity
 * scan found missing, changed or orphaned files
 * (whole-collection-integrity-scan REQ-DIS-02).
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
 * @spec openspec/changes/archive/2026-11-20-whole-collection-integrity-scan/tasks.md#task-2-nightly-job-occ-command-and-notification
 */

declare(strict_types=1);

namespace OCA\Filinq\Notification;

use OCA\Filinq\Kernel\AppIdentity;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;
use OCP\Notification\UnknownNotificationException;

/**
 * Renders an integrity scan's findings notification.
 *
 * @spec openspec/changes/archive/2026-11-20-whole-collection-integrity-scan/specs/document-versions/spec.md#requirement-the-scan-runs-nightly-and-on-demand-and-administrators-see-the-result-req-dis-02
 */
class IntegrityScanNotifier implements INotifier {

	/**
	 * The subject of a run with findings.
	 */
	public const SUBJECT = 'integrity_scan_findings';

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
	 *
	 * @spec openspec/changes/archive/2026-11-20-whole-collection-integrity-scan/specs/document-versions/spec.md#requirement-the-scan-runs-nightly-and-on-demand-and-administrators-see-the-result-req-dis-02
	 */
	public function getID(): string {
		return AppIdentity::APP_ID . '-integrity-scan';

	}//end getID()

	/**
	 * The notifier name.
	 *
	 * @return string The name.
	 *
	 * @spec openspec/changes/archive/2026-11-20-whole-collection-integrity-scan/specs/document-versions/spec.md#requirement-the-scan-runs-nightly-and-on-demand-and-administrators-see-the-result-req-dis-02
	 */
	public function getName(): string {
		return $this->l10nFactory->get(AppIdentity::APP_ID)->t('Integrity check');

	}//end getName()

	/**
	 * Render the findings notification: the count per kind and a link to the
	 * administration section that lists them.
	 *
	 * @param INotification $notification The notification.
	 * @param string        $languageCode The recipient's language.
	 *
	 * @return INotification The rendered notification.
	 *
	 * @throws UnknownNotificationException When it is not ours.
	 *
	 * @spec openspec/changes/archive/2026-11-20-whole-collection-integrity-scan/specs/document-versions/spec.md#requirement-the-scan-runs-nightly-and-on-demand-and-administrators-see-the-result-req-dis-02
	 */
	public function prepare(INotification $notification, string $languageCode): INotification {
		if ($notification->getApp() !== AppIdentity::APP_ID || $notification->getSubject() !== self::SUBJECT) {
			throw new UnknownNotificationException();
		}

		$l10n   = $this->l10nFactory->get(AppIdentity::APP_ID, $languageCode);
		$counts = $notification->getSubjectParameters();
		$notification->setParsedSubject(
			$l10n->t(
				'The integrity check found %1$s missing, %2$s changed and %3$s orphaned files.',
				[(int) ($counts['missing'] ?? 0), (int) ($counts['changed'] ?? 0), (int) ($counts['orphaned'] ?? 0)]
			)
		)
			->setIcon($this->urls->getAbsoluteURL($this->urls->imagePath(AppIdentity::APP_ID, 'app-dark.svg')))
			->setLink($this->urls->getAbsoluteURL($this->urls->linkToRoute('settings.AdminSettings.index', ['section' => AppIdentity::APP_ID])));

		return $notification;

	}//end prepare()
}//end class
