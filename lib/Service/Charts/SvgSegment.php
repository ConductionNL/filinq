<?php

/**
 * Svg Segment
 *
 * A straight line from one point to another.
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
 * A straight line from one point to another.
 *
 * @spec openspec/specs/template-charts/spec.md#REQ-DDTCH-001
 */
final class SvgSegment {

	/**
	 * @param SvgPoint $from Start point
	 * @param SvgPoint $to End point
	 *
	 * @spec openspec/specs/template-charts/spec.md#REQ-DDTCH-001
	 */
	public function __construct(
		public readonly SvgPoint $from,
		public readonly SvgPoint $to,
	) {
	}//end __construct()
}//end class
