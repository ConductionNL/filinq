<?php

/**
 * Who may search the entity catalogue.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category  Service
 * @package   OCA\Filinq\Service\EntitySearch
 *
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/entity-search/tasks.md#task-2.3
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\EntitySearch;

use OCA\Filinq\AppInfo\Application;
use OCA\Filinq\Exception\EntitySearchRefusedException;
use OCP\IAppConfig;
use OCP\IGroupManager;
use Throwable;

/**
 * Admins, plus the groups in `entity_search.allowed_groups` (a JSON list,
 * empty by default, so admins only). Searching the catalogue is looking up
 * where a person appears across every document, which is exactly the lookup
 * the AVG wants accounted for; this check runs before anything is read.
 *
 * A setting that is not a JSON list of group names, or cannot be read,
 * refuses everyone, admins included: a broken setting never widens access.
 */
class EntitySearchGate {

	/**
	 * The app setting that names the entity search groups.
	 *
	 * @var string
	 */
	public const CONFIG_KEY = 'entity_search.allowed_groups';

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
	 * Refuse unless the user may search the entity catalogue.
	 *
	 * @param string $userId The signed-in user, '' when nobody is.
	 *
	 * @return void
	 *
	 * @throws EntitySearchRefusedException When the user may not.
	 *
	 * @spec openspec/changes/entity-search/tasks.md#task-2.3
	 */
	public function assertMaySearch(string $userId): void {
		$groups = $this->groups();
		if ($groups === null) {
			throw new EntitySearchRefusedException(
				reason: EntitySearchRefusedException::REASON_CONFIG_UNREADABLE,
				message: 'The entity search groups could not be read, so nobody may search.'
			);
		}

		if ($userId === '') {
			throw new EntitySearchRefusedException(reason: EntitySearchRefusedException::REASON_NOT_ALLOWED, message: 'Only a signed-in user may search.');
		}

		if ($this->groupManager->isAdmin($userId) === true) {
			return;
		}

		foreach ($groups as $group) {
			if ($this->groupManager->isInGroup($userId, $group) === true) {
				return;
			}
		}

		throw new EntitySearchRefusedException(
			reason: EntitySearchRefusedException::REASON_NOT_ALLOWED,
			message: 'This user is neither an admin nor in an entity search group.'
		);

	}//end assertMaySearch()

	/**
	 * Whether the user is an admin, which lifts the organisation scoping.
	 *
	 * @param string $userId The user.
	 *
	 * @return bool True for an admin.
	 *
	 * @spec openspec/changes/entity-search/tasks.md#task-2.1
	 */
	public function isAdmin(string $userId): bool {
		return ($userId !== '' && $this->groupManager->isAdmin($userId) === true);

	}//end isAdmin()

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
