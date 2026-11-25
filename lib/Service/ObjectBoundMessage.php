<?php

/**
 * Object Bound Message
 *
 * What a mail bound to an OpenRegister object says, and the object it is about.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/signer-consent-notifications-to-email-leaf/tasks.md#d-2-consent-notification-via-the-email-leaf-drive-notificationstatus-m
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

/**
 * The subject, the plain-text body and the object of one mail.
 *
 * @spec openspec/changes/signer-consent-notifications-to-email-leaf/tasks.md#d-2-consent-notification-via-the-email-leaf-drive-notificationstatus-m
 */
final class ObjectBoundMessage {

	/**
	 * Constructor.
	 *
	 * @param string                $subject The subject line.
	 * @param string                $body    The plain-text body.
	 * @param ObjectBoundMailTarget $object  The object the mail is about.
	 */
	public function __construct(
		public readonly string $subject,
		public readonly string $body,
		public readonly ObjectBoundMailTarget $object,
	) {
	}//end __construct()
}//end class
