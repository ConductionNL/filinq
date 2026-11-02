<?php

/**
 * Contract of LegalHoldNotifications
 *
 * The public surface of the LegalHoldNotifications service, so callers in other
 * components depend on this contract instead of the implementation.
 *
 * @category  Kernel
 * @package   OCA\Filinq\Kernel\Contracts
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

namespace OCA\Filinq\Kernel\Contracts;

/**
 * Contract of LegalHoldNotifications.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface LegalHoldNotificationsInterface {
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
	public function notify(array $userIds, array $case, string $subject): array;
}//end interface
