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
 * @spec openspec/specs/template-charts/spec.md
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
 * @spec openspec/changes/archive/2026-09-28-template-charts/tasks.md#task-1.1
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
	 * @spec openspec/specs/template-charts/spec.md#REQ-DDTCH-001
	 */
	public function svgOpenTag(int $width, int $height): string {
		return '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . '" height="' . $height . '" '
			. 'viewBox="0 0 ' . $width . ' ' . $height . '">'
			. '<rect x="0" y="0" width="' . $width . '" height="' . $height . '" fill="#FFFFFF"/>';

	}//end svgOpenTag()

	/**
	 * Build an escaped `<text>` element.
	 *
	 * @param SvgPoint $position Position (baseline).
	 * @param SvgText $text Text content (escaped) and style.
	 *
	 * @return string SVG markup.
	 *
	 * @spec openspec/specs/template-charts/spec.md#REQ-DDTCH-001
	 */
	public function textEl(SvgPoint $position, SvgText $text): string {
		return '<text x="' . $this->num(value: $position->x) . '" y="' . $this->num(value: $position->y) . '" '
			. 'font-family="sans-serif" ' . $text->style->attributes() . '>'
			. $this->escapeText(text: $text->content)
			. '</text>';

	}//end textEl()

	/**
	 * Build the body of a placeholder canvas: a light frame with a centered message.
	 *
	 * @param SvgSize $canvas Canvas width and height.
	 * @param string $message Message text (escaped).
	 *
	 * @return string SVG markup.
	 *
	 * @spec openspec/specs/template-charts/spec.md#REQ-DDTCH-002
	 */
	public function placeholderEl(SvgSize $canvas, string $message): string {
		$frame = new SvgBox(origin: new SvgPoint(x: 1, y: 1), size: new SvgSize(width: $canvas->width - 2, height: $canvas->height - 2));
		$center = new SvgPoint(x: $canvas->width / 2, y: $canvas->height / 2);

		return $this->rectEl(box: $frame, color: '#F5F5F5')
			. $this->rectOutlineEl(box: $frame, color: '#CCCCCC')
			. $this->textEl(position: $center, text: new SvgText(content: $message, style: SvgTextStyle::Placeholder));
	}//end placeholderEl()

	/**
	 * Build a `<rect>` element.
	 *
	 * @param SvgBox $box Position and size.
	 * @param string $color Fill color.
	 *
	 * @return string SVG markup.
	 *
	 * @spec openspec/specs/template-charts/spec.md#REQ-DDTCH-001
	 */
	public function rectEl(SvgBox $box, string $color): string {
		return '<rect ' . $this->boxAttributes(box: $box) . ' fill="' . $color . '"/>';
	}//end rectEl()

	/**
	 * Build an unfilled, stroked `<rect>` outline element.
	 *
	 * @param SvgBox $box Position and size.
	 * @param string $color Stroke color.
	 *
	 * @return string SVG markup.
	 *
	 * @spec openspec/specs/template-charts/spec.md#REQ-DDTCH-001
	 */
	public function rectOutlineEl(SvgBox $box, string $color): string {
		return '<rect ' . $this->boxAttributes(box: $box) . ' fill="none" stroke="' . $color . '" stroke-width="1"/>';
	}//end rectOutlineEl()

	/**
	 * Build a `<line>` element one unit wide.
	 *
	 * @param SvgSegment $segment Start and end point.
	 * @param string $color Stroke color.
	 *
	 * @return string SVG markup.
	 *
	 * @spec openspec/specs/template-charts/spec.md#REQ-DDTCH-001
	 */
	public function lineEl(SvgSegment $segment, string $color): string {
		return '<line x1="' . $this->num(value: $segment->from->x) . '" y1="' . $this->num(value: $segment->from->y) . '" '
			. 'x2="' . $this->num(value: $segment->to->x) . '" y2="' . $this->num(value: $segment->to->y) . '" '
			. 'stroke="' . $color . '" stroke-width="1"/>';

	}//end lineEl()

	/**
	 * Build a filled `<circle>` element.
	 *
	 * @param SvgCircle $circle Center and radius.
	 * @param string $color Fill color.
	 *
	 * @return string SVG markup.
	 *
	 * @spec openspec/specs/template-charts/spec.md#REQ-DDTCH-001
	 */
	public function circleEl(SvgCircle $circle, string $color): string {
		return '<circle cx="' . $this->num(value: $circle->center->x) . '" cy="' . $this->num(value: $circle->center->y) . '" '
			. 'r="' . $this->num(value: $circle->radius) . '" fill="' . $color . '"/>';

	}//end circleEl()

	/**
	 * Build a `<polyline>` element from an ordered list of `[x, y]` points.
	 *
	 * @param array $points Ordered `[[x, y], ...]` points.
	 * @param string $color Stroke color.
	 *
	 * @return string SVG markup.
	 *
	 * @spec openspec/specs/template-charts/spec.md#REQ-DDTCH-001
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
	 * @param SvgSector $sector The circle and the angles the slice spans.
	 * @param string $color Fill color.
	 *
	 * @return string SVG markup.
	 *
	 * @spec openspec/specs/template-charts/spec.md#REQ-DDTCH-001
	 */
	public function pieSliceEl(SvgSector $sector, string $color): string {
		$center = $sector->circle->center;
		$radius = $this->num(value: $sector->circle->radius);

		$largeArc = 0;
		if (($sector->arc->endAngle - $sector->arc->startAngle) > 180.0) {
			$largeArc = 1;
		}

		$path = 'M ' . $this->num(value: $center->x) . ' ' . $this->num(value: $center->y)
			. ' L ' . $this->pointOnCircle(circle: $sector->circle, angle: $sector->arc->startAngle)
			. ' A ' . $radius . ' ' . $radius . ' 0 ' . $largeArc . ' 1 '
			. $this->pointOnCircle(circle: $sector->circle, angle: $sector->arc->endAngle) . ' Z';

		return '<path d="' . $path . '" fill="' . $color . '"/>';
	}//end pieSliceEl()

	/**
	 * The `x y` path coordinates of the point on a circle at an angle.
	 *
	 * @param SvgCircle $circle The circle.
	 * @param float $angle Angle in degrees.
	 *
	 * @return string Formatted `x y` pair.
	 */
	private function pointOnCircle(SvgCircle $circle, float $angle): string {
		$rad = deg2rad($angle);

		return $this->num(value: $circle->center->x + ($circle->radius * cos($rad)))
			. ' ' . $this->num(value: $circle->center->y + ($circle->radius * sin($rad)));
	}//end pointOnCircle()

	/**
	 * The x, y, width and height attributes of a box.
	 *
	 * @param SvgBox $box Position and size.
	 *
	 * @return string The four attributes.
	 */
	private function boxAttributes(SvgBox $box): string {
		return 'x="' . $this->num(value: $box->origin->x) . '" y="' . $this->num(value: $box->origin->y) . '" '
			. 'width="' . $this->num(value: $box->size->width) . '" height="' . $this->num(value: $box->size->height) . '"';
	}//end boxAttributes()

	/**
	 * Format a coordinate/dimension number deterministically (2 decimal
	 * places, no locale dependency, trailing zeros trimmed).
	 *
	 * @param float $value The number to format.
	 *
	 * @return string Formatted number.
	 *
	 * @spec openspec/specs/template-charts/spec.md#REQ-DDTCH-001
	 */
	private function num(float $value): string {
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
	 * @spec openspec/specs/template-charts/spec.md#REQ-DDTCH-001
	 */
	private function escapeText(string $text): string {
		return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
	}//end escapeText()
}//end class
