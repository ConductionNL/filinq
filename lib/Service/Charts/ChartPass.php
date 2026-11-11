<?php

/**
 * Chart Pass
 *
 * One pass of {@see SvgRasterizer::rasterizeInlineSvg()} over a rendered
 * HTML document: whether the converter lock was taken, the target format the
 * warnings name, and the warnings collected so far, in chart order. Built per
 * call by the rasterizer and never handed outside it.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Charts
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/template-charts/spec.md#REQ-DDTCH-007
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Charts;

/**
 * The lock state, format and warnings of one rasterizing pass.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Charts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/template-charts/spec.md#REQ-DDTCH-007
 */
final class ChartPass {

	/**
	 * The warnings for the charts that were not converted, in chart order.
	 *
	 * @var string[]
	 */
	public array $warnings = [];

	/**
	 * Constructor.
	 *
	 * @param bool   $locked Whether this pass holds the converter lock.
	 * @param string $format The target format, named in the warnings (odf, docx).
	 *
	 * @return void
	 */
	public function __construct(
		public readonly bool $locked,
		public readonly string $format,
	) {

	}//end __construct()
}//end class
