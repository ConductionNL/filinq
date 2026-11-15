<?php

/**
 * Chart Input
 *
 * What a caller asks {@see ChartSvgRenderer} to draw: the raw chart data and
 * the rendering options, before either is normalised or validated.
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
 * The raw data and options of one chart render.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Charts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/template-charts/spec.md#REQ-DDTCH-001
 */
final class ChartInput {
	/**
	 * Constructor.
	 *
	 * @param array $data    `{labels: string[], series: [{name, values}]}`.
	 * @param array $options Rendering options (title, width, height, palette, ...).
	 */
	public function __construct(
		public readonly array $data,
		public readonly array $options,
	) {

	}//end __construct()
}//end class
