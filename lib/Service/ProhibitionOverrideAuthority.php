<?php

/**
 * Prohibition Override Authority
 *
 * Says who may release a publish-never match that sits below the
 * high-confidence threshold. File access is not enough: the rules are written
 * by policy admins, so overriding one is theirs too.
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
 *
 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-6
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCA\Filinq\Kernel\AppIdentity;
use OCP\IAppConfig;
use OCP\IGroupManager;

/**
 * Admins and the configured override groups may release a prohibition match.
 */
class ProhibitionOverrideAuthority {

	/**
	 * The app setting naming the override groups: a JSON list of group ids.
	 *
	 * @var string
	 */
	public const CONFIG_KEY = 'prohibition_override_groups';

	/**
	 * The override groups when nothing is configured.
	 *
	 * @var string
	 */
	public const DEFAULT_GROUPS = '["filinq-policy-admins"]';

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
	 * Whether the user may release a prohibition match.
	 *
	 * A setting that is not a JSON list of group ids leaves admins only.
	 *
	 * @param string $userId The acting user, '' when nobody is signed in.
	 *
	 * @return bool True for an admin or a member of an override group.
	 *
	 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-6
	 */
	public function mayOverride(string $userId): bool {
		if ($userId === '') {
			return false;
		}

		if ($this->groupManager->isAdmin($userId) === true) {
			return true;
		}

		$groups = json_decode($this->appConfig->getValueString(AppIdentity::APP_ID, self::CONFIG_KEY, self::DEFAULT_GROUPS), true);
		if (is_array($groups) === false) {
			return false;
		}

		foreach ($groups as $group) {
			if (is_string($group) === true && $group !== '' && $this->groupManager->isInGroup($userId, $group) === true) {
				return true;
			}
		}

		return false;

	}//end mayOverride()
}//end class
