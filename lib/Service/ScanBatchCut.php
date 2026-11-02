<?php

/**
 * Scan Batch Cut
 *
 * A delivered batch file and the profile it is cut with.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCP\Files\File;

/**
 * The batch file the caller may read, and the scan profile that says how to cut it.
 *
 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
 */
final class ScanBatchCut {

	/**
	 * @param File                 $file    The batch the scanner wrote.
	 * @param array<string, mixed> $profile The scan profile to cut it with.
	 */
	public function __construct(
		public readonly File $file,
		public readonly array $profile,
	) {
	}//end __construct()
}//end class
