<?php

/**
 * Chart Render Error
 *
 * Immutable value object carrying a human-readable reason for a chart data
 * validation failure, returned internally by {@see ChartSvgRenderer} instead
 * of throwing — rendering must never fail loudly (REQ-DDTCH-001/002).
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Charts
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/template-charts/specs/template-charts/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Charts;

/**
 * Value object for a chart data validation failure reason.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Charts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/template-charts/tasks.md#task-1.1
 */
final class ChartRenderError {
	/**
	 * Constructor for ChartRenderError.
	 *
	 * @param string $message Human-readable, already user-safe reason.
	 *
	 * @return void
	 */
	public function __construct(
		public readonly string $message,
	) {

	}//end __construct()
}//end class
