<?php

/**
 * Session user file
 *
 * Finds a file by id in the signed-in user's own folder.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/financial-document-field-extraction/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\IUserSession;

/**
 * A file by id from the signed-in user's own folder, or nothing.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/financial-document-field-extraction/spec.md
 */
final class SessionUserFile {

	/**
	 * Constructor.
	 *
	 * @param IRootFolder  $rootFolder  Root folder, for the user's own folder
	 * @param IUserSession $userSession User session, for the signed-in user
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IRootFolder $rootFolder,
		private readonly IUserSession $userSession,
	) {

	}//end __construct()

	/**
	 * The first node with this id in the signed-in user's own folder, when it
	 * is a file; null without a session, for an unknown id or for a folder.
	 *
	 * @param int $fileId The file id.
	 *
	 * @return File|null The file, or null.
	 *
	 * @spec openspec/specs/financial-document-field-extraction/spec.md
	 */
	public function find(int $fileId): ?File {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return null;
		}

		$userFolder = $this->rootFolder->getUserFolder($user->getUID());
		$nodes = $userFolder->getById($fileId);
		if (empty($nodes) === true) {
			return null;
		}

		$node = $nodes[0];
		if ($node instanceof File === false) {
			return null;
		}

		return $node;

	}//end find()
}//end class
