<?php

/**
 * The boundary between reconciling and the file storage that grants access.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 *
 * @author    Conduction Development Team <dev@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

/**
 * Reads and writes which groups may reach a folder.
 *
 * 🔑 THIS IS A SEAM BECAUSE THE STORAGE IS THE PART THAT REFUSES, and refusing
 * is the behaviour REQ-CDF-02 cares most about. An external mount can decline a
 * permission change, and the reconciler has to report that rather than claim
 * success. Putting the storage behind one narrow interface is what lets the
 * refusal be exercised at all: a test can make `revoke()` throw, which is the
 * scenario the requirement names and which no amount of real-mount testing on a
 * build host would reach.
 *
 * 🔴 THE NEXTCLOUD-BACKED IMPLEMENTATION IS NOT IN THIS CHANGE, AND THAT IS
 * MEASURED RATHER THAN CHOSEN. `OCP\Share\IManager` does not exist in this
 * repository's test environment: `interface_exists()` returns false under
 * `tests/bootstrap-unit.php`, while `OCP\Files\IRootFolder` returns true. So an
 * adapter written here could be neither run nor tested, only typed, and a
 * caller-less adapter that nobody can execute is exactly the shape this lane
 * keeps finding. It is named as what waits instead.
 *
 * Every method MAY throw. The service treats a throw as a refusal with a reason
 * and reports it; it never treats one as success.
 *
 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
 */
interface DomainFolderGateway {

	/**
	 * Which groups can currently reach the folder.
	 *
	 * @param string $path  The folder.
	 * @param string $owner The user whose storage holds it.
	 *
	 * @return array<int, string> The group ids.
	 *
	 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function groupsWithAccess(string $path, string $owner): array;

	/**
	 * Give a group access to the folder.
	 *
	 * @param string $path  The folder.
	 * @param string $owner The user whose storage holds it.
	 * @param string $group The group.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function grant(string $path, string $owner, string $group): void;

	/**
	 * Take a group's access to the folder away.
	 *
	 * @param string $path  The folder.
	 * @param string $owner The user whose storage holds it.
	 * @param string $group The group.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function revoke(string $path, string $owner, string $group): void;
}//end interface
