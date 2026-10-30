<?php

/**
 * Svg Arc
 *
 * The start and end angle of an arc in degrees (0 = 3 o'clock, -90 = 12 o'clock).
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Charts
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/template-charts/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Charts;

/**
 * The start and end angle of an arc in degrees (0 = 3 o'clock, -90 = 12 o'clock).
 *
 * @spec openspec/specs/template-charts/spec.md#REQ-DDTCH-001
 */
final class SvgArc {

	/**
	 * @param float $startAngle Start angle in degrees
	 * @param float $endAngle End angle in degrees
	 *
	 * @spec openspec/specs/template-charts/spec.md#REQ-DDTCH-001
	 */
	public function __construct(
		public readonly float $startAngle,
		public readonly float $endAngle,
	) {
	}//end __construct()
}//end class
