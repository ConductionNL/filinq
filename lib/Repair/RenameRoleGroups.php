<?php

/**
 * Rename the role groups from docudesk-* to filinq-*
 *
 * The schemas, the services and the app settings name the `filinq-*` groups.
 * A Nextcloud group id cannot be renamed, so this step creates each `filinq-*`
 * group, copies every member of the matching `docudesk-*` group into it, and
 * rewrites the group-list settings that still name an old group. It deletes no
 * group and removes nobody: the old groups stay as they are.
 *
 * It runs once. A second run would add back a member an administrator has since
 * taken out of a new group.
 *
 * @category Repair
 * @package  OCA\Filinq\Repair
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version GIT: <git-id>
 * @link https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec exclude One-off data migration for the role group rename.
 */

declare(strict_types=1);

namespace OCA\Filinq\Repair;

use OCA\Filinq\Kernel\AppIdentity;
use OCP\IAppConfig;
use OCP\IGroupManager;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

/**
 * Creates the filinq-* role groups and copies the docudesk-* members into them.
 *
 * @category Repair
 * @package  OCA\Filinq\Repair
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class RenameRoleGroups implements IRepairStep {

	/**
	 * The app setting that records the step has run.
	 *
	 * @var string
	 */
	public const DONE_KEY = 'role_groups_renamed';

	/**
	 * The role groups, old id => new id.
	 *
	 * @var array<string, string>
	 */
	public const RENAMES = [
		'docudesk-policy-admins' => 'filinq-policy-admins',
		'docudesk-template-editors' => 'filinq-template-editors',
		'docudesk-woo-officers' => 'filinq-woo-officers',
		'docudesk-signing-admins' => 'filinq-signing-admins',
		'docudesk-standing-consent-admins' => 'filinq-standing-consent-admins',
		'docudesk-privacy-officer' => 'filinq-privacy-officer',
		'docudesk-financial-admins' => 'filinq-financial-admins',
		'docudesk-final-document-admins' => 'filinq-final-document-admins',
		'docudesk-prohibition-admins' => 'filinq-prohibition-admins',
	];

	/**
	 * The app settings that hold a JSON list of group ids.
	 *
	 * @var list<string>
	 */
	public const GROUP_LIST_SETTINGS = [
		'subject_erasure_groups',
		'legal_hold_authority_groups',
		'prohibition_override_groups',
		'publication_publisher_groups',
		'pseudonymisation_restore_allowed_groups',
		'entity_search.allowed_groups',
	];

	/**
	 * Constructor.
	 *
	 * @param IGroupManager $groupManager The Nextcloud groups.
	 * @param IAppConfig    $appConfig    The app settings.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IGroupManager $groupManager,
		private readonly IAppConfig $appConfig,
	) {

	}//end __construct()

	/**
	 * The step's name in the upgrade log.
	 *
	 * @return string
	 */
	public function getName(): string {
		return 'Rename the Filinq role groups from docudesk-* to filinq-*';

	}//end getName()

	/**
	 * Create the new groups, copy the members, rewrite the settings; once.
	 *
	 * @param IOutput $output The upgrade output.
	 *
	 * @return void
	 */
	public function run(IOutput $output): void {
		if ($this->appConfig->getValueString(AppIdentity::APP_ID, self::DONE_KEY, '') === '1') {
			return;
		}

		foreach (self::RENAMES as $old => $new) {
			$copied = $this->copyMembers(old: $old, new: $new);
			$output->info(sprintf('%s: %d member(s) copied from %s', $new, $copied, $old));
		}

		foreach (self::GROUP_LIST_SETTINGS as $key) {
			$this->rewriteSetting(key: $key);
		}

		$this->appConfig->setValueString(AppIdentity::APP_ID, self::DONE_KEY, '1');

	}//end run()

	/**
	 * Create the new group if needed and add every member of the old one.
	 *
	 * @param string $old The docudesk-* group id.
	 * @param string $new The filinq-* group id.
	 *
	 * @return int How many members were added.
	 */
	private function copyMembers(string $old, string $new): int {
		$target = $this->groupManager->get($new);
		if ($target === null) {
			$target = $this->groupManager->createGroup($new);
		}

		$source = $this->groupManager->get($old);
		if ($source === null || $target === null) {
			return 0;
		}

		$copied = 0;
		foreach ($source->getUsers() as $user) {
			if ($target->inGroup($user) === false) {
				$target->addUser($user);
				$copied++;
			}
		}

		return $copied;

	}//end copyMembers()

	/**
	 * Replace old group ids in one JSON-list setting; anything else is left alone.
	 *
	 * @param string $key The setting.
	 *
	 * @return void
	 */
	private function rewriteSetting(string $key): void {
		if ($this->appConfig->hasKey(AppIdentity::APP_ID, $key) === false) {
			return;
		}

		$raw = $this->appConfig->getValueString(AppIdentity::APP_ID, $key, '');
		$groups = json_decode($raw, true);
		if (is_array($groups) === false || array_is_list($groups) === false) {
			return;
		}

		$renamed = [];
		foreach ($groups as $group) {
			$renamed[] = $group;
			if (is_string($group) === true && isset(self::RENAMES[$group]) === true) {
				$renamed[array_key_last($renamed)] = self::RENAMES[$group];
			}
		}

		if ($renamed !== $groups) {
			$this->appConfig->setValueString(AppIdentity::APP_ID, $key, (string) json_encode(array_values(array_unique($renamed, SORT_REGULAR))));
		}

	}//end rewriteSetting()
}//end class
