<?php

/**
 * Svg Circle
 *
 * A circle by its center and radius.
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
 * A circle by its center and radius.
 *
 * @spec openspec/specs/template-charts/spec.md#REQ-DDTCH-001
 */
final class SvgCircle {

	/**
	 * @param SvgPoint $center Center
	 * @param float $radius Radius
	 *
	 * @spec openspec/specs/template-charts/spec.md#REQ-DDTCH-001
	 */
	public function __construct(
		public readonly SvgPoint $center,
		public readonly float $radius,
	) {
	}//end __construct()
}//end class
