<?php

/**
 * Separator Rule
 *
 * How a scanned page is recognised as a separator.
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

/**
 * The separator mode and, for blank-page mode, the ink weight a blank page stays under.
 *
 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
 */
final class SeparatorRule {

	/**
	 * @param string $mode The separator mode (`qr` or `blank`).
	 * @param int $inkThreshold The weight above which a page is not blank.
	 */
	public function __construct(
		public readonly string $mode,
		public readonly int $inkThreshold,
	) {
	}//end __construct()
}//end class
