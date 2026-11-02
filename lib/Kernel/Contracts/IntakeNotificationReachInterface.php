<?php

/**
 * Contract of IntakeNotificationReach
 *
 * The public surface of the IntakeNotificationReach service, so callers in other
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
 * Contract of IntakeNotificationReach.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface IntakeNotificationReachInterface {
	/**
	 * The rule declared on `intakeDocument`.
	 *
	 * @var string
	 */
	public const RULE = 'readingFailed';

	/**
	 * The group the rule addresses.
	 *
	 * @var string
	 */
	public const GROUP = 'filinq-woo-officers';

	/**
	 * What the inbox shows about who will be told.
	 *
	 * @param int  $failureCount How many documents could not be read.
	 * @param int  $groupMembers How many people are in the notified group.
	 *
	 * @return array<string, mixed> What to show.
	 *
	 * @spec openspec/changes/archive/2026-10-09-intake-failure-reaches-someone/specs/filinq-notifications/spec.md
	 */
	public function describe(int $failureCount, int $groupMembers): array;
}//end interface
