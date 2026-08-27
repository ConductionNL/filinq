<?php

/**
 * SVG Primitives
 *
 * Builders for the conservative SVG element subset the chart renderers emit
 * (rect/line/text/polyline/path/circle with inline presentation attributes
 * only), plus the deterministic number formatting and text escaping every
 * emitted attribute depends on.
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
 * Builds the individual SVG elements used by the chart renderers.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Charts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/template-charts/tasks.md#task-1.1
 */
class SvgPrimitives {
	/**
	 * Build the opening `<svg>` tag with a white background rect.
	 *
	 * @param int $width Canvas width.
	 * @param int $height Canvas height.
	 *
	 * @return string SVG open tag + background rect.
	 *
	 * @spec openspec/changes/template-charts/specs/template-charts/spec.md#REQ-DDTCH-001
	 */
	public function svgOpenTag(int $width, int $height): string {
		return '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . '" height="' . $height . '" '
			. 'viewBox="0 0 ' . $width . ' ' . $height . '">'
			. '<rect x="0" y="0" width="' . $width . '" height="' . $height . '" fill="#FFFFFF"/>';

	}//end svgOpenTag()

	/**
	 * Build an escaped `<text>` element.
	 *
	 * @param float $x X position.
	 * @param float $y Y position (baseline).
	 * @param string $text Text content (escaped).
	 * @param string $anchor 'start'|'middle'|'end'.
	 * @param int $size Font size in user units.
	 * @param string $color Fill color.
	 * @param string $weight Font weight ('normal'|'bold').
	 *
	 * @return string SVG markup.
	 *
	 * @spec openspec/changes/template-charts/specs/template-charts/spec.md#REQ-DDTCH-001
	 */
	public function textEl(
		float $x,
		float $y,
		string $text,
		string $anchor = 'start',
		int $size = 10,
		string $color = '#000000',
		string $weight = 'normal',
	): string {
		return '<text x="' . $this->num(value: $x) . '" y="' . $this->num(value: $y) . '" '
			. 'font-family="sans-serif" font-size="' . $size . '" font-weight="' . $weight . '" '
			. 'fill="' . $color . '" text-anchor="' . $anchor . '">'
			. $this->escapeText(text: $text)
			. '</text>';

	}//end textEl()

	/**
	 * Build a `<rect>` element.
	 *
	 * @param float $x X position.
	 * @param float $y Y position.
	 * @param float $width Rectangle width.
	 * @param float $height Rectangle height.
	 * @param string $color Fill color.
	 *
	 * @return string SVG markup.
	 *
	 * @spec openspec/changes/template-charts/specs/template-charts/spec.md#REQ-DDTCH-001
	 */
	public function rectEl(float $x, float $y, float $width, float $height, string $color): string {
		return '<rect x="' . $this->num(value: $x) . '" y="' . $this->num(value: $y) . '" '
			. 'width="' . $this->num(value: $width) . '" height="' . $this->num(value: $height) . '" '
			. 'fill="' . $color . '"/>';

	}//end rectEl()

	/**
	 * Build an unfilled, stroked `<rect>` outline element.
	 *
	 * @param float $x X position.
	 * @param float $y Y position.
	 * @param float $width Rectangle width.
	 * @param float $height Rectangle height.
	 * @param string $color Stroke color.
	 *
	 * @return string SVG markup.
	 *
	 * @spec openspec/changes/template-charts/specs/template-charts/spec.md#REQ-DDTCH-001
	 */
	public function rectOutlineEl(float $x, float $y, float $width, float $height, string $color): string {
		return '<rect x="' . $this->num(value: $x) . '" y="' . $this->num(value: $y) . '" '
			. 'width="' . $this->num(value: $width) . '" height="' . $this->num(value: $height) . '" '
			. 'fill="none" stroke="' . $color . '" stroke-width="1"/>';

	}//end rectOutlineEl()

	/**
	 * Build a `<line>` element.
	 *
	 * @param float $x1 Start x.
	 * @param float $y1 Start y.
	 * @param float $x2 End x.
	 * @param float $y2 End y.
	 * @param string $color Stroke color.
	 * @param float $width Stroke width.
	 *
	 * @return string SVG markup.
	 *
	 * @spec openspec/changes/template-charts/specs/template-charts/spec.md#REQ-DDTCH-001
	 */
	public function lineEl(float $x1, float $y1, float $x2, float $y2, string $color, float $width): string {
		return '<line x1="' . $this->num(value: $x1) . '" y1="' . $this->num(value: $y1) . '" '
			. 'x2="' . $this->num(value: $x2) . '" y2="' . $this->num(value: $y2) . '" '
			. 'stroke="' . $color . '" stroke-width="' . $this->num(value: $width) . '"/>';

	}//end lineEl()

	/**
	 * Build a filled `<circle>` element.
	 *
	 * @param float $cx Center x.
	 * @param float $cy Center y.
	 * @param float $radius Radius.
	 * @param string $color Fill color.
	 *
	 * @return string SVG markup.
	 *
	 * @spec openspec/changes/template-charts/specs/template-charts/spec.md#REQ-DDTCH-001
	 */
	public function circleEl(float $cx, float $cy, float $radius, string $color): string {
		return '<circle cx="' . $this->num(value: $cx) . '" cy="' . $this->num(value: $cy) . '" '
			. 'r="' . $this->num(value: $radius) . '" fill="' . $color . '"/>';

	}//end circleEl()

	/**
	 * Build a `<polyline>` element from an ordered list of `[x, y]` points.
	 *
	 * @param array $points Ordered `[[x, y], ...]` points.
	 * @param string $color Stroke color.
	 *
	 * @return string SVG markup.
	 *
	 * @spec openspec/changes/template-charts/specs/template-charts/spec.md#REQ-DDTCH-001
	 */
	public function polylineEl(array $points, string $color): string {
		$pairs = [];
		foreach ($points as [$x, $y]) {
			$pairs[] = $this->num(value: $x) . ',' . $this->num(value: $y);
		}

		return '<polyline points="' . implode(' ', $pairs) . '" fill="none" stroke="' . $color . '" stroke-width="2"/>';
	}//end polylineEl()

	/**
	 * Build a pie slice as an SVG `<path>` arc sector.
	 *
	 * @param float $cx Center x.
	 * @param float $cy Center y.
	 * @param float $radius Slice radius.
	 * @param float $startAngle Start angle in degrees (0 = 3 o'clock, -90 = 12 o'clock).
	 * @param float $endAngle End angle in degrees.
	 * @param string $color Fill color.
	 *
	 * @return string SVG markup.
	 *
	 * @spec openspec/changes/template-charts/specs/template-charts/spec.md#REQ-DDTCH-001
	 */
	public function pieSliceEl(float $cx, float $cy, float $radius, float $startAngle, float $endAngle, string $color): string {
		$startRad = deg2rad($startAngle);
		$endRad = deg2rad($endAngle);

		$x1 = $cx + ($radius * cos($startRad));
		$y1 = $cy + ($radius * sin($startRad));
		$x2 = $cx + ($radius * cos($endRad));
		$y2 = $cy + ($radius * sin($endRad));

		$largeArc = 0;
		if (($endAngle - $startAngle) > 180.0) {
			$largeArc = 1;
		}

		$path = 'M ' . $this->num(value: $cx) . ' ' . $this->num(value: $cy)
			. ' L ' . $this->num(value: $x1) . ' ' . $this->num(value: $y1)
			. ' A ' . $this->num(value: $radius) . ' ' . $this->num(value: $radius) . ' 0 ' . $largeArc . ' 1 '
			. $this->num(value: $x2) . ' ' . $this->num(value: $y2) . ' Z';

		return '<path d="' . $path . '" fill="' . $color . '"/>';
	}//end pieSliceEl()

	/**
	 * Format a coordinate/dimension number deterministically (2 decimal
	 * places, no locale dependency, trailing zeros trimmed).
	 *
	 * @param float $value The number to format.
	 *
	 * @return string Formatted number.
	 *
	 * @spec openspec/changes/template-charts/specs/template-charts/spec.md#REQ-DDTCH-001
	 */
	public function num(float $value): string {
		$formatted = sprintf('%.2f', $value);
		$formatted = rtrim($formatted, '0');
		$formatted = rtrim($formatted, '.');

		if ($formatted === '' || $formatted === '-') {
			return '0';
		}

		return $formatted;
	}//end num()

	/**
	 * Escape a data-derived text value for safe embedding inside an SVG
	 * `<text>` element (blocks markup/script injection via labels).
	 *
	 * @param string $text Raw text.
	 *
	 * @return string Escaped text.
	 *
	 * @spec openspec/changes/template-charts/specs/template-charts/spec.md#REQ-DDTCH-001
	 */
	public function escapeText(string $text): string {
		return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
	}//end escapeText()
}//end class
