<?php

/**
 * AdmitsConfiguredGroups — the group check shared by Filinq's authority gates.
 *
 * A gate reads a JSON list of group ids from the app config and admits an
 * admin or a member of any of those groups. The gate keeps its own order of
 * checks and its own refusals; this trait only reads the list and answers
 * the membership question.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
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

namespace OCA\Filinq\Service;

use OCA\Filinq\Kernel\AppIdentity;
use Throwable;

/**
 * Reads a configured group list and checks admin or group membership.
 *
 * The using class provides `$appConfig` (IAppConfig) and `$groupManager`
 * (IGroupManager).
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude shared access-check plumbing; each gate is specified with its own service
 */
trait AdmitsConfiguredGroups {
	/**
	 * The configured group ids, trimmed and without blanks, or null when the
	 * setting is not a JSON list of strings.
	 *
	 * @param string $key The app config key.
	 * @param string $default The value used when the key is not set.
	 *
	 * @return array<int, string>|null The groups; null means nobody may pass.
	 */
	private function configuredGroups(string $key, string $default): ?array {
		try {
			$raw = $this->appConfig->getValueString(AppIdentity::APP_ID, $key, $default);
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
	}//end configuredGroups()

	/**
	 * Whether the user is an admin or a member of one of the groups.
	 *
	 * @param string $userId A non-empty user id.
	 * @param array<int, string> $groups The configured groups.
	 *
	 * @return bool True when the user may pass.
	 */
	private function isAdminOrMember(string $userId, array $groups): bool {
		if ($this->groupManager->isAdmin($userId) === true) {
			return true;
		}

		foreach ($groups as $group) {
			if ($this->groupManager->isInGroup($userId, $group) === true) {
				return true;
			}
		}

		return false;
	}//end isAdminOrMember()
}//end trait
