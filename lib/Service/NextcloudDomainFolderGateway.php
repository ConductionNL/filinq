<?php

/**
 * A domain folder's group access, held as Nextcloud group shares.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-10-09-case-documents-and-the-flat-list/specs/document-register/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCP\Constants;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\Share\IManager;
use OCP\Share\IShare;

/**
 * Reads and writes which groups may reach a folder through group shares.
 *
 * Every failure (a missing folder, a share the storage refuses) is thrown, so
 * DomainFolderService reports it as a refusal with its reason.
 *
 * @spec openspec/changes/archive/2026-10-09-case-documents-and-the-flat-list/specs/document-register/spec.md
 */
class NextcloudDomainFolderGateway implements DomainFolderGateway {

	/**
	 * Collaborators.
	 *
	 * @param IRootFolder $rootFolder   Resolves the folder in the owner's storage.
	 * @param IManager    $shareManager Reads, creates and deletes the group shares.
	 */
	public function __construct(
		private readonly IRootFolder $rootFolder,
		private readonly IManager $shareManager,
	) {
	}//end __construct()

	/**
	 * Which groups can currently reach the folder.
	 *
	 * @param string $path  The folder.
	 * @param string $owner The user whose storage holds it.
	 *
	 * @return array<int, string> The group ids.
	 *
	 * @spec openspec/changes/archive/2026-10-09-case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function groupsWithAccess(string $path, string $owner): array {
		$groups = [];
		foreach ($this->groupShares(path: $path, owner: $owner) as $share) {
			$groups[] = (string)$share->getSharedWith();
		}

		return array_values(array_unique($groups));
	}//end groupsWithAccess()

	/**
	 * Give a group access to the folder.
	 *
	 * @param string $path  The folder.
	 * @param string $owner The user whose storage holds it.
	 * @param string $group The group.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/archive/2026-10-09-case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function grant(string $path, string $owner, string $group): void {
		$share = $this->shareManager->newShare();
		$share->setNode($this->folder(path: $path, owner: $owner))
			->setShareType(IShare::TYPE_GROUP)
			->setSharedWith($group)
			->setSharedBy($owner)
			->setShareOwner($owner)
			->setPermissions(Constants::PERMISSION_ALL);

		$this->shareManager->createShare($share);
	}//end grant()

	/**
	 * Take a group's access to the folder away.
	 *
	 * @param string $path  The folder.
	 * @param string $owner The user whose storage holds it.
	 * @param string $group The group.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/archive/2026-10-09-case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function revoke(string $path, string $owner, string $group): void {
		foreach ($this->groupShares(path: $path, owner: $owner) as $share) {
			if ((string)$share->getSharedWith() === $group) {
				$this->shareManager->deleteShare($share);
			}
		}
	}//end revoke()

	/**
	 * Every group share on the folder, including reshares by other users.
	 *
	 * @param string $path  The folder.
	 * @param string $owner The user whose storage holds it.
	 *
	 * @return array<int, IShare> The shares.
	 */
	private function groupShares(string $path, string $owner): array {
		return $this->shareManager->getSharesBy(
			$owner,
			IShare::TYPE_GROUP,
			$this->folder(path: $path, owner: $owner),
			true,
			-1
		);
	}//end groupShares()

	/**
	 * The folder in the owner's storage.
	 *
	 * @param string $path  The folder.
	 * @param string $owner The user whose storage holds it.
	 *
	 * @return Node The folder.
	 */
	private function folder(string $path, string $owner): Node {
		return $this->rootFolder->getUserFolder($owner)->get($path);
	}//end folder()
}//end class
