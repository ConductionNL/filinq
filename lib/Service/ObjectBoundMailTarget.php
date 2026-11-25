<?php

/**
 * Object Bound Mail Target
 *
 * The OpenRegister object a mail is about: its uuid, and its register and
 * schema by id or slug.
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
 * The object a mail is bound to.
 *
 * @spec openspec/changes/signer-consent-notifications-to-email-leaf/tasks.md#d-2-consent-notification-via-the-email-leaf-drive-notificationstatus-m
 */
final class ObjectBoundMailTarget {

	/**
	 * Constructor.
	 *
	 * @param string $uuid     The object's uuid.
	 * @param string $register Its register, by id or slug.
	 * @param string $schema   Its schema, by id or slug.
	 */
	public function __construct(
		public readonly string $uuid,
		public readonly string $register,
		public readonly string $schema,
	) {
	}//end __construct()
}//end class
