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

use OCP\Files\IRootFolder;
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
 * @spec openspec/changes/woo-publicatie-pipeline/specs/woo-publicatie-pipeline/spec.md
 */
class PublicationAccess {

	/**
	 * Constructor
	 *
	 * @param IRootFolder   $rootFolder The caller's files
	 * @param IGroupManager $groups     Whether the caller is an admin
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IRootFolder $rootFolder,
		private readonly IGroupManager $groups,
	) {

	}//end __construct()

	/**
	 * Whether a user may act on a document's publication.
	 *
	 * @param string $uid    The user
	 * @param string $fileId The document's file id
	 *
	 * @return bool True for an admin, or when the user can open the document.
	 *
	 * @spec openspec/changes/woo-publicatie-pipeline/tasks.md#task-2.5
	 */
	public function mayAct(string $uid, string $fileId): bool {
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

	}//end mayAct()
}//end class
