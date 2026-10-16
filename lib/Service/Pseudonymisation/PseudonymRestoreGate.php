<?php

/**
 * Pseudonym Restore Gate
 *
 * Decides who may turn a reversibly anonymised copy back into names: admins, and
 * members of the groups an admin listed. It fails closed: an empty list means
 * admins only, and a list that cannot be read means nobody.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Pseudonymisation
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-reversible-pseudonymization/tasks.md#task-3.2
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Pseudonymisation;

use OCA\Filinq\AppInfo\Application;
use OCA\Filinq\Exception\PseudonymRestoreRefusedException;
use OCP\IAppConfig;
use OCP\IGroupManager;
use Throwable;

/**
 * Who may restore the original of a reversibly anonymised copy.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Pseudonymisation
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class PseudonymRestoreGate {

	/**
	 * The app config key: a JSON list of Nextcloud group ids.
	 *
	 * @var string
	 */
	public const CONFIG_KEY = 'pseudonymisation_restore_allowed_groups';

	/**
	 * Constructor.
	 *
	 * @param IAppConfig $appConfig Holds the allowed groups.
	 * @param IGroupManager $groupManager Answers admin and group membership.
	 */
	public function __construct(
		private readonly IAppConfig $appConfig,
		private readonly IGroupManager $groupManager,
	) {

	}//end __construct()

	/**
	 * Refuse unless this user may restore.
	 *
	 * @param string $userId The Nextcloud user id.
	 *
	 * @return void
	 *
	 * @throws PseudonymRestoreRefusedException When the user may not, or the list cannot be read.
	 *
	 * @spec openspec/changes/archive/2026-09-29-reversible-pseudonymization/tasks.md#task-3.2
	 */
	public function assertMayRestore(string $userId): void {
		$groups = $this->allowedGroups();
		if ($groups === null) {
			throw new PseudonymRestoreRefusedException(
				reason: PseudonymRestoreRefusedException::REASON_CONFIG_UNREADABLE,
				message: 'The groups allowed to restore could not be read, so nobody may restore.'
			);
		}

		if ($userId === '') {
			throw new PseudonymRestoreRefusedException(
				reason: PseudonymRestoreRefusedException::REASON_NOT_ALLOWED,
				message: 'Only a signed-in user may restore.'
			);
		}

		if ($this->groupManager->isAdmin($userId) === true) {
			return;
		}

		foreach ($groups as $group) {
			if ($this->groupManager->isInGroup($userId, $group) === true) {
				return;
			}
		}

		throw new PseudonymRestoreRefusedException(
			reason: PseudonymRestoreRefusedException::REASON_NOT_ALLOWED,
			message: 'This user is neither an admin nor in a group allowed to restore.'
		);

	}//end assertMayRestore()

	/**
	 * Whether this user may restore, for showing or hiding the action.
	 *
	 * @param string $userId The Nextcloud user id.
	 *
	 * @return bool True when assertMayRestore() would let them through.
	 *
	 * @spec openspec/changes/archive/2026-09-29-reversible-pseudonymization/tasks.md#task-4.2
	 */
	public function mayRestore(string $userId): bool {
		try {
			$this->assertMayRestore(userId: $userId);
		} catch (PseudonymRestoreRefusedException) {
			return false;
		}

		return true;

	}//end mayRestore()

	/**
	 * The allowed group ids, or null when the setting cannot be read as a list.
	 *
	 * @return array<int, string>|null The groups; [] means admins only.
	 */
	private function allowedGroups(): ?array {
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

	}//end allowedGroups()
}//end class
