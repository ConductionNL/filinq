<?php

/**
 * Contract of UploadFragmentReaper
 *
 * The public surface of the UploadFragmentReaper service, so callers in other
 * components depend on this contract instead of the implementation.
 *
 * @category  Kernel
 * @package   OCA\Filinq\Kernel\Contracts
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

namespace OCA\Filinq\Kernel\Contracts;

use OCP\Files\Folder;

/**
 * Contract of UploadFragmentReaper.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface UploadFragmentReaperInterface {
	/**
	 * The suffixes an in-flight upload carries.
	 *
	 * @var array<int, string>
	 */
	public const FRAGMENT_SUFFIXES = ['.part', '.filepart', '.ocTransferId'];

	/**
	 * Whether a name is an upload fragment's name.
	 *
	 * @param string $name The file name.
	 *
	 * @return bool True when the name carries a fragment suffix.
	 *
	 * @spec openspec/changes/archive/2026-10-09-case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function isFragmentName(string $name): bool;

	/**
	 * Reap one folder tree.
	 *
	 * @param Folder $folder        The tree to sweep, which is filinq's documents folder.
	 * @param int    $maxAgeSeconds How old a fragment must be before it is reaped.
	 * @param int    $now           The moment to measure age against, as a unix timestamp.
	 *
	 * @return array{removed: int, bytes: int, kept: int, refused: array<int, array{path: string, reason: string}>} What the sweep did.
	 *
	 * @spec openspec/changes/archive/2026-10-09-case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function reap(Folder $folder, int $maxAgeSeconds, int $now): array;
}//end interface
