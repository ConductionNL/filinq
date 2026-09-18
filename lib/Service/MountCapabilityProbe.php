<?php

/**
 * What a mount can actually do, asked rather than assumed.
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
 * One narrow question per capability filinq depends on.
 *
 * 🔑 WHY THIS IS A SEAM RATHER THAN A DIRECT READ OF THE STORAGE. Answering
 * these questions means `OCP\Files\Mount\IMountPoint` and
 * `OCP\Files\Storage\IStorage`, and NEITHER EXISTS in this repository's unit
 * test environment, measured with `interface_exists()` under
 * `tests/bootstrap-unit.php`. A validator written straight against them could
 * be typed but neither run nor tested here, and an untestable guard is the one
 * that quietly stops guarding. So the validator reasons about an answer and the
 * adapter that gets the answer from a real mount is named as still open.
 *
 * 🔴 EVERY ANSWER IS THREE-VALUED, NEVER TWO. `null` means the probe could not
 * find out, and that is not the same as "no". A false collapses into "the mount
 * cannot do this, name it in the report"; a null must read as "nobody knows
 * whether this mount can do this", which is a WORSE answer than no and has to
 * reach the person setting the mount up as its own line.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction Development Team <dev@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
 */
interface MountCapabilityProbe {

	/**
	 * Whether the mount can hold a permission per group, which reconciliation needs.
	 *
	 * @param string $path The folder the domain would live in.
	 *
	 * @return bool|null True, false, or null when it could not be found out.
	 */
	public function supportsPerGroupPermissions(string $path): ?bool;

	/**
	 * Whether every write to this mount passes through filinq, which the upload policy needs.
	 *
	 * @param string $path The folder the domain would live in.
	 *
	 * @return bool|null True, false, or null when it could not be found out.
	 */
	public function writesPassThroughFilinq(string $path): ?bool;

	/**
	 * Whether the mount keeps a version history, which a corrected document needs.
	 *
	 * @param string $path The folder the domain would live in.
	 *
	 * @return bool|null True, false, or null when it could not be found out.
	 */
	public function supportsVersions(string $path): ?bool;

	/**
	 * Whether the mount is writable at all.
	 *
	 * @param string $path The folder the domain would live in.
	 *
	 * @return bool|null True, false, or null when it could not be found out.
	 */
	public function isWritable(string $path): ?bool;
}//end interface
