<?php

/**
 * Who may place and release legal holds.
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
 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-2.4
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\LegalHold;

use OCA\Filinq\AppInfo\Application;
use OCA\Filinq\Exception\LegalHoldRefusedException;
use OCP\IAppConfig;
use OCP\IGroupManager;
use Throwable;

/**
 * Admins, plus the groups in `legal_hold_authority_groups`. A setting that is
 * not a JSON list of group names refuses everyone, admins included: a broken
 * setting must never widen who may freeze or unfreeze evidence.
 */
class LegalHoldAuthority {

	/**
	 * The app setting that names the authority groups.
	 *
	 * @var string
	 */
	public const CONFIG_KEY = 'legal_hold_authority_groups';

	/**
	 * Constructor.
	 *
	 * @param IAppConfig    $appConfig    The app settings.
	 * @param IGroupManager $groupManager Group membership.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IAppConfig $appConfig,
		private readonly IGroupManager $groupManager,
	) {

	}//end __construct()

	/**
	 * Refuse unless the user may place and release holds.
	 *
	 * @param string $userId The signed-in user, '' when nobody is.
	 *
	 * @return void
	 *
	 * @throws LegalHoldRefusedException When the user may not.
	 *
	 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-2.4
	 */
	public function assertAuthority(string $userId): void {
		$groups = $this->groups();
		if ($groups === null) {
			throw new LegalHoldRefusedException(
				reason: LegalHoldRefusedException::REASON_CONFIG_UNREADABLE,
				message: 'The legal hold authority groups could not be read, so nobody may act.'
			);
		}

		if ($userId === '') {
			throw new LegalHoldRefusedException(reason: LegalHoldRefusedException::REASON_NOT_ALLOWED, message: 'Only a signed-in user may act.');
		}

		if ($this->groupManager->isAdmin($userId) === true) {
			return;
		}

		foreach ($groups as $group) {
			if ($this->groupManager->isInGroup($userId, $group) === true) {
				return;
			}
		}

		throw new LegalHoldRefusedException(
			reason: LegalHoldRefusedException::REASON_NOT_ALLOWED,
			message: 'This user is neither an admin nor in a legal hold authority group.'
		);

	}//end assertAuthority()

	/**
	 * Whether the user may place and release holds.
	 *
	 * @param string $userId The signed-in user.
	 *
	 * @return bool True when allowed.
	 *
	 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-2.4
	 */
	public function hasAuthority(string $userId): bool {
		try {
			$this->assertAuthority(userId: $userId);
		} catch (LegalHoldRefusedException) {
			return false;
		}

		return true;

	}//end hasAuthority()

	/**
	 * The configured groups, or null when the setting does not parse.
	 *
	 * @return array<int, string>|null The groups.
	 */
	private function groups(): ?array {
		try {
			$raw = $this->appConfig->getValueString(Application::APP_ID, self::CONFIG_KEY, '[]');
			$decoded = json_decode(json: $raw, associative: true, flags: JSON_THROW_ON_ERROR);
		} catch (Throwable) {
			return null;
		}

		if (is_array($decoded) === false || array_is_list($decoded) === false) {
			return null;
		}

		$groups = [];
		foreach ($decoded as $group) {
			if (is_string($group) === false) {
				return null;
			}

			if (trim($group) !== '') {
				$groups[] = trim($group);
			}
		}

		return $groups;

	}//end groups()
}//end class
