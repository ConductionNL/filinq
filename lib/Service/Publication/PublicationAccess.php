<?php

/**
 * Publication access
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Publication
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

namespace OCA\Filinq\Service\Publication;

use OCA\Filinq\Kernel\AppIdentity;
use OCA\Filinq\Kernel\Contracts\PublicationAccessInterface;
use OCP\Files\IRootFolder;
use OCP\IAppConfig;
use OCP\IGroupManager;
use Throwable;

/**
 * Who may see and move a publication: whoever can open its document, and
 * an admin.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Publication
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/woo-publicatie-pipeline/spec.md
 */
class PublicationAccess implements PublicationAccessInterface {

	/**
	 * Constructor
	 *
	 * @param IRootFolder   $rootFolder The caller's files
	 * @param IGroupManager $groups     Whether the caller is an admin or a publisher
	 * @param IAppConfig    $appConfig  The publisher groups setting
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IRootFolder $rootFolder,
		private readonly IGroupManager $groups,
		private readonly IAppConfig $appConfig,
	) {

	}//end __construct()

	/**
	 * Whether a user may see a document's publication.
	 *
	 * @param string $uid    The user
	 * @param string $fileId The document's file id
	 *
	 * @return bool True for an admin, or when the user can open the document.
	 *
	 * @spec openspec/changes/archive/2026-09-29-woo-publicatie-pipeline/tasks.md#task-2.5
	 */
	public function maySee(string $uid, string $fileId): bool {
		if ($uid === '') {
			return false;
		}

		if ($this->groups->isAdmin($uid) === true) {
			return true;
		}

		try {
			return $this->rootFolder->getUserFolder($uid)->getById((int) $fileId) !== [];
		} catch (Throwable) {
			return false;
		}

	}//end maySee()

	/**
	 * Whether a user may start, hand off, withdraw or change a document's publication.
	 *
	 * File access alone is not enough: a read-only share recipient can open the
	 * document. The user must also be in a publisher group.
	 *
	 * @param string $uid    The user
	 * @param string $fileId The document's file id
	 *
	 * @return bool True for an admin, or a publisher who can open the document.
	 *
	 * @spec openspec/changes/archive/2026-09-29-woo-publicatie-pipeline/tasks.md#task-2.5
	 */
	public function mayAct(string $uid, string $fileId): bool {
		if ($uid === '') {
			return false;
		}

		if ($this->groups->isAdmin($uid) === true) {
			return true;
		}

		return $this->isPublisher(uid: $uid) === true && $this->maySee(uid: $uid, fileId: $fileId) === true;

	}//end mayAct()

	/**
	 * Whether the user is in one of the configured publisher groups.
	 *
	 * A setting that is not a JSON list of group ids makes nobody a publisher.
	 *
	 * @param string $uid The user
	 *
	 * @return bool True when the user is in a publisher group.
	 */
	private function isPublisher(string $uid): bool {
		$raw = $this->appConfig->getValueString(AppIdentity::APP_ID, self::PUBLISHER_GROUPS_KEY, self::DEFAULT_PUBLISHER_GROUPS);
		$groups = json_decode($raw, true);
		if (is_array($groups) === false) {
			return false;
		}

		foreach ($groups as $group) {
			if (is_string($group) === true && $group !== '' && $this->groups->isInGroup($uid, $group) === true) {
				return true;
			}
		}

		return false;

	}//end isPublisher()
}//end class
