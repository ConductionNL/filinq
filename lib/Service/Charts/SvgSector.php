<?php

/**
 * Svg Sector
 *
 * A pie slice: the part of a circle between the two angles of an arc.
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
 * A pie slice: the part of a circle between the two angles of an arc.
 *
 * @spec openspec/specs/template-charts/spec.md#REQ-DDTCH-001
 */
final class SvgSector {

	/**
	 * @param SvgCircle $circle The full circle
	 * @param SvgArc $arc The angles the slice spans
	 *
	 * @spec openspec/specs/template-charts/spec.md#REQ-DDTCH-001
	 */
	public function __construct(
		public readonly SvgCircle $circle,
		public readonly SvgArc $arc,
	) {
	}//end __construct()
}//end class
