<?php

/**
 * Chart Request
 *
 * The normalized data, palette, canvas size and options one chart is drawn from.
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
 * Everything a per-type renderer draws one chart from.
 *
 * @spec openspec/specs/template-charts/spec.md#REQ-DDTCH-001
 */
class ChartRequest {

	/**
	 * @param array $normalized Normalized `{labels, series}` data
	 * @param array $palette    Ordered hex colors
	 * @param int   $width      Canvas width
	 * @param int   $height     Canvas height
	 * @param array $options    Rendering options
	 */
	public function __construct(
		public readonly array $normalized,
		public readonly array $palette,
		public readonly int $width,
		public readonly int $height,
		public readonly array $options,
	) {
	}//end __construct()

	/**
	 * The chart title, or '' for none.
	 *
	 * @return string The title.
	 *
	 * @spec openspec/specs/template-charts/spec.md#REQ-DDTCH-001
	 */
	public function title(): string {
		return (string)($this->options['title'] ?? '');
	}//end title()

	/**
	 * The value label format, 'integer' unless an option sets one.
	 *
	 * @return string The format.
	 *
	 * @spec openspec/specs/template-charts/spec.md#REQ-DDTCH-001
	 */
	public function valueFormat(): string {
		return (string)($this->options['valueFormat'] ?? 'integer');
	}//end valueFormat()

	/**
	 * The palette color for a series or slice index, cycling through the palette.
	 *
	 * @param int $index The series or slice index.
	 *
	 * @return string The hex color.
	 *
	 * @spec openspec/specs/template-charts/spec.md#REQ-DDTCH-001
	 */
	public function color(int $index): string {
		return $this->palette[$index % count($this->palette)];
	}//end color()
}//end class
