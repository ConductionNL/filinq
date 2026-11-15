<?php

/**
 * Scan setup services
 *
 * The separator sheets and scan profiles a scan intake is prepared with.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller\Support
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

namespace OCA\Filinq\Controller\Support;

use OCA\Filinq\Kernel\Contracts\ScanProfileServiceInterface;
use OCA\Filinq\Kernel\Contracts\SeparatorSheetServiceInterface;

/**
 * The separator sheets and scan profiles a scan intake is prepared with.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller\Support
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
 */
final class ScanSetupServices {

	/**
	 * Constructor.
	 *
	 * @param SeparatorSheetServiceInterface $sheets   Separator sheet generation
	 * @param ScanProfileServiceInterface    $profiles Scan profiles
	 *
	 * @return void
	 *
	 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
	 */
	public function __construct(
		public readonly SeparatorSheetServiceInterface $sheets,
		public readonly ScanProfileServiceInterface $profiles,
	) {

	}//end __construct()
}//end class
