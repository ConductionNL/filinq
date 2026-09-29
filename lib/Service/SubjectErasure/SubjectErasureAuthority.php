<?php

/**
 * Who may place, preview and run a subject erasure.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category  Service
 * @package   OCA\Filinq\Service\SubjectErasure
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/erase-a-person-while-the-records-stay/tasks.md#task-4.1
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\SubjectErasure;

use OCA\Filinq\AppInfo\Application;
use OCA\Filinq\Exception\SubjectErasureRefusedException;
use OCP\IAppConfig;
use OCP\IGroupManager;
use Throwable;

/**
 * Admins, plus the groups in `subject_erasure_groups`, which defaults to the
 * two groups the request schema itself grants. An erasure rewrites files across
 * every user, so the file work happens past the file owners' own permissions:
 * this check is the control, and it runs before anything is read.
 *
 * A setting that is not a JSON list of group names refuses everyone, admins
 * included: a broken setting must never widen who may erase.
 */
class SubjectErasureAuthority {

	/**
	 * The app setting that names the erasure groups.
	 *
	 * @var string
	 */
	public const CONFIG_KEY = 'subject_erasure_groups';

	/**
	 * The groups the subjectErasureRequest schema grants, used when the setting is absent.
	 *
	 * @var string
	 */
	public const DEFAULT_GROUPS = '["docudesk-privacy-officer","docudesk-policy-admins"]';

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
	 * Refuse unless the user may act on subject erasures.
	 *
	 * @param string $userId The signed-in user, '' when nobody is.
	 *
	 * @return void
	 *
	 * @throws SubjectErasureRefusedException When the user may not.
	 *
	 * @spec openspec/changes/erase-a-person-while-the-records-stay/tasks.md#task-4.1
	 */
	public function assertMayErase(string $userId): void {
		$groups = $this->groups();
		if ($groups === null) {
			throw new SubjectErasureRefusedException(
				reason: SubjectErasureRefusedException::REASON_CONFIG_UNREADABLE,
				message: 'The subject erasure groups could not be read, so nobody may erase.'
			);
		}

		if ($userId === '') {
			throw new SubjectErasureRefusedException(reason: SubjectErasureRefusedException::REASON_NOT_ALLOWED, message: 'Only a signed-in user may erase.');
		}

		if ($this->groupManager->isAdmin($userId) === true) {
			return;
		}

		foreach ($groups as $group) {
			if ($this->groupManager->isInGroup($userId, $group) === true) {
				return;
			}
		}

		throw new SubjectErasureRefusedException(
			reason: SubjectErasureRefusedException::REASON_NOT_ALLOWED,
			message: 'This user is neither an admin nor in a subject erasure group.'
		);

	}//end assertMayErase()

	/**
	 * The configured groups, or null when the setting does not parse.
	 *
	 * @return array<int, string>|null The groups.
	 */
	private function groups(): ?array {
		try {
			$raw = $this->appConfig->getValueString(Application::APP_ID, self::CONFIG_KEY, self::DEFAULT_GROUPS);
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
