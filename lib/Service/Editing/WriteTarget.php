<?php

/**
 * Filinq WriteTarget
 *
 * Where a guarded edit writes: the resolved source file and the output mode
 * (in place, or a new file beside the source).
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

use OCP\Files\File;

/**
 * The source file and the resolved output mode of one guarded edit.
 *
 * @spec openspec/specs/document-editing/spec.md#requirement-an-in-place-write-is-guarded-by-the-lock-and-a-version-precondition
 */
final class WriteTarget {

	/**
	 * Constructor.
	 *
	 * @param File   $file The source file.
	 * @param string $mode The resolved output mode.
	 */
	public function __construct(
		public readonly File $file,
		public readonly string $mode,
	) {
	}//end __construct()
}//end class
