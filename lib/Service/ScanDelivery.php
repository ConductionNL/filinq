<?php

/**
 * Scan Delivery
 *
 * Where the segments of one scanned batch go and whose name they carry.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-11-25-scan-intake-with-separator-sheets/specs/scan-intake/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCP\Files\Folder;

/**
 * The scanner a batch came from and the folder its segments are written to.
 *
 * @spec openspec/changes/archive/2026-11-25-scan-intake-with-separator-sheets/specs/scan-intake/spec.md
 */
final class ScanDelivery {

	/**
	 * @param string $scannerId The scanner, named in every file name and as the sender.
	 * @param Folder $target The folder the segments are written to.
	 * @param string $batchId The scan batch, recorded on a scanned paper original.
	 */
	public function __construct(
		public readonly string $scannerId,
		public readonly Folder $target,
		public readonly string $batchId = '',
	) {
	}//end __construct()
}//end class
