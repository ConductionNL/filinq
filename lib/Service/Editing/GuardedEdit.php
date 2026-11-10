<?php

/**
 * Filinq GuardedEdit
 *
 * One read-modify-write an agent asks GuardedWriter to run: who acts, on
 * which file, the codec pass that rewrites the package, the version the
 * caller read and the resolved output mode.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Editing
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 *
 * @link https://filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Editing;

use Closure;
use OCP\Files\File;

/**
 * The acting user, the file, the transform, the expected version and the output mode of one guarded edit.
 *
 * @spec openspec/specs/document-editing/spec.md#requirement-an-in-place-write-is-guarded-by-the-lock-and-a-version-precondition
 */
final class GuardedEdit {

	/**
	 * Constructor.
	 *
	 * @param string  $uid       The acting user id.
	 * @param File    $file      The source file.
	 * @param Closure $transform Given (bytes, extension), returns the rewritten package.
	 * @param string  $version   The expected version.
	 * @param string  $mode      The resolved output mode.
	 */
	public function __construct(
		public readonly string $uid,
		public readonly File $file,
		public readonly Closure $transform,
		public readonly string $version,
		public readonly string $mode,
	) {
	}//end __construct()
}//end class
