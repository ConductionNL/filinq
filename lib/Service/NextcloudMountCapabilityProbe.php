<?php

/**
 * Asking a real Nextcloud mount what it can do.
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
 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCP\Files\IRootFolder;
use Throwable;

/**
 * The one probe that talks to a real mount.
 *
 * 🔴 IT ANSWERS `null` FOR EVERYTHING NEXTCLOUD DOES NOT EXPOSE, AND THAT IS
 * THE HONEST ANSWER, NOT A STUB. There is no public API that asks a storage
 * whether it holds a permission per group, keeps a version history, or can be
 * written to behind the app's back. Answering `true` would tell an
 * administrator their external store keeps the promises filinq makes, on no
 * evidence at all; answering `false` would cry wolf about every mount whose
 * driver simply does not say. `ExternalMountValidator` reports a `null` as
 * `unknown`, apart from `cannot`, which is exactly what this situation is.
 *
 * 🔑 A HOME MOUNT IS THE ONE CASE THAT CAN BE ANSWERED. When the folder sits on
 * the user's own home storage, permissions, versions and the write path are
 * Nextcloud's own and filinq's promises hold. Everything else, files_external
 * included, is reported as unconfirmed rather than guessed at. That is the
 * difference the requirement's scenario turns on: setup must say what it cannot
 * meet before a document is stored, and "nobody has checked this store" is a
 * thing it must be able to say.
 *
 * 🔑 NOT COVERED BY UNIT TESTS, DELIBERATELY AND NARROWLY.
 * `OCP\Files\Mount\IMountPoint` does not exist in this repository's unit test
 * environment, measured with `interface_exists()` under
 * `tests/bootstrap-unit.php`, so a double of it cannot be built here. The
 * decisions therefore live in ExternalMountValidator, which is tested; this
 * class holds only the lookup and the mount-type reading, and is kept small
 * enough that reading it is the review.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
 */
class NextcloudMountCapabilityProbe implements MountCapabilityProbe {

	/**
	 * Collaborators.
	 *
	 * @param IRootFolder    $rootFolder The file tree.
	 * @param DomainDirectory $directory  Names the user whose storage holds the folders.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IRootFolder $rootFolder,
		private readonly DomainDirectory $directory,
	) {

	}//end __construct()

	/**
	 * Whether the mount accepts a write.
	 *
	 * @param string $path The folder the domain would live in.
	 *
	 * @return bool|null True, false, or null when it could not be found out.
	 *
	 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function isWritable(string $path): ?bool {
		$node = $this->node(path: $path);
		if ($node === null) {
			return null;
		}

		try {
			return (bool)$node->isUpdateable();
		} catch (Throwable $e) {
			unset($e);

			return null;
		}

	}//end isWritable()

	/**
	 * Whether the mount can hold a permission per group.
	 *
	 * @param string $path The folder the domain would live in.
	 *
	 * @return bool|null True on a home mount, null anywhere else.
	 *
	 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function supportsPerGroupPermissions(string $path): ?bool {
		return $this->homeMount(path: $path);

	}//end supportsPerGroupPermissions()

	/**
	 * Whether the mount keeps a version history.
	 *
	 * @param string $path The folder the domain would live in.
	 *
	 * @return bool|null True on a home mount, null anywhere else.
	 *
	 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function supportsVersions(string $path): ?bool {
		return $this->homeMount(path: $path);

	}//end supportsVersions()

	/**
	 * Whether every write to this mount passes through filinq.
	 *
	 * 🔴 THIS IS `null` EVEN ON A HOME MOUNT, and that is not an oversight. A
	 * home folder is reachable over WebDAV and the Files app by the person who
	 * owns it, so "every write passes filinq" is false there in the strictest
	 * reading and true in the reading the requirement means. Saying so either
	 * way would be filinq deciding what an administrator's threat model is.
	 *
	 * @param string $path The folder the domain would live in.
	 *
	 * @return bool|null Always null: nothing Nextcloud exposes answers this.
	 *
	 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function writesPassThroughFilinq(string $path): ?bool {
		unset($path);

		return null;

	}//end writesPassThroughFilinq()

	/**
	 * Whether the folder sits on the owner's own home storage.
	 *
	 * @param string $path The folder.
	 *
	 * @return bool|null True on a home mount, null when it is anything else or could not be read.
	 *
	 * @spec exclude Mount-type lookup; the decisions it feeds live in ExternalMountValidator.
	 */
	private function homeMount(string $path): ?bool {
		$node = $this->node(path: $path);
		if ($node === null) {
			return null;
		}

		try {
			$type = (string)$node->getMountPoint()->getMountType();
		} catch (Throwable $e) {
			unset($e);

			return null;
		}

		// Nextcloud reports the home storage with an empty mount type. Anything
		// else -- `external`, `shared`, `group`, a type this version has not
		// shipped yet -- is a store filinq has no way to question, so it is
		// reported as unconfirmed rather than assumed either way.
		if ($type === '') {
			return true;
		}

		return null;

	}//end homeMount()

	/**
	 * The node at a path, or null when it is not there or cannot be reached.
	 *
	 * @param string $path The path, relative to the folder owner's files.
	 *
	 * @return \OCP\Files\Node|null The node.
	 *
	 * @spec exclude Lookup helper.
	 */
	private function node(string $path): ?\OCP\Files\Node {
		$owner = $this->directory->owner();
		if ($owner === '') {
			return null;
		}

		try {
			$userFolder = $this->rootFolder->getUserFolder($owner);
			if ($userFolder->nodeExists($path) === false) {
				return null;
			}

			return $userFolder->get($path);
		} catch (Throwable $e) {
			unset($e);

			return null;
		}

	}//end node()
}//end class
