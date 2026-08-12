<?php

/**
 * Chart SVG Renderer
 *
 * Pure-PHP, local, deterministic renderer that turns a small chart data shape
 * into self-contained SVG markup for bar, line, and pie charts. No JavaScript,
 * no network access, no external chart service, and no GD/Imagick dependency
 * (config rule: all processing local). The emitted SVG is a conservative
 * subset (rect/line/path/circle/polyline/text with inline presentation
 * attributes only, no CSS classes, no gradients/filters/foreignObject) so it
 * renders reliably through mPDF's inline-SVG support as well as browsers.
 *
 * This class is the entry point and placeholder/fallback owner; the actual
 * drawing lives in the per-type renderers ({@see BarChartRenderer},
 * {@see LineChartRenderer}, {@see PieChartRenderer}) and their shared
 * collaborators.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service\Charts
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/changes/template-charts/specs/template-charts/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service\Charts;

/**
 * Renders bar, line, and pie charts as self-contained, deterministic SVG.
 *
 * Data shape: `{labels: string[], series: [{name: string, values: (int|float|null)[]}]}`.
 * Pie charts use only the first series. Horizontal bar orientation and donut
 * rendering are options on the `bar` and `pie` types respectively (not
 * separate chart types), keeping the supported type set at exactly
 * bar/line/pie per the template-charts spec (REQ-DDTCH-001).
 *
 * @category Service
 * @package  OCA\DocuDesk\Service\Charts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/changes/template-charts/tasks.md#task-1.1
 */
class ChartSvgRenderer {

	/**
	 * Chart types supported by this renderer.
	 *
	 * @var string[]
	 */
	public const SUPPORTED_TYPES = ['bar', 'line', 'pie'];

	/**
	 * Default canvas width in SVG user units (roughly px).
	 *
	 * @var int
	 */
	private const DEFAULT_WIDTH = 600;

	/**
	 * Default canvas height in SVG user units (roughly px).
	 *
	 * @var int
	 */
	private const DEFAULT_HEIGHT = 300;

	/**
	 * Default cap on the number of data points (labels) per chart.
	 * Overridable via the `docudesk.charts.max_points` app config value.
	 *
	 * @var int
	 */
	private const DEFAULT_MAX_POINTS = 1000;

	/**
	 * The failure reason recorded by the most recent {@see render()} call
	 * that fell back to a placeholder, or null when the last call rendered
	 * a real chart. Callers (the Twig `chart()` function) read this
	 * immediately after `render()` to surface a generation warning.
	 *
	 * @var string|null
	 */
	private ?string $lastWarning = null;

	/**
	 * Chart data validator/normalizer.
	 *
	 * @var ChartDataNormalizer
	 */
	private readonly ChartDataNormalizer $normalizer;

	/**
	 * Color palette resolver.
	 *
	 * @var ChartPalette
	 */
	private readonly ChartPalette $palette;

	/**
	 * SVG element builders (used for the placeholder box).
	 *
	 * @var SvgPrimitives
	 */
	private readonly SvgPrimitives $svg;

	/**
	 * Bar chart renderer.
	 *
	 * @var BarChartRenderer
	 */
	private readonly BarChartRenderer $bar;

	/**
	 * Line chart renderer.
	 *
	 * @var LineChartRenderer
	 */
	private readonly LineChartRenderer $line;

	/**
	 * Pie/donut chart renderer.
	 *
	 * @var PieChartRenderer
	 */
	private readonly PieChartRenderer $pie;

	/**
	 * Constructor.
	 *
	 * Collaborators are pure, stateless helpers with no I/O, so they are
	 * composed here rather than injected — this keeps the public constructor
	 * argument-free for both the DI container and direct instantiation.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->normalizer = new ChartDataNormalizer();
		$this->palette = new ChartPalette();
		$this->svg = new SvgPrimitives();
		$this->bar = new BarChartRenderer();
		$this->line = new LineChartRenderer();
		$this->pie = new PieChartRenderer();

	}//end __construct()

	/**
	 * Render a chart as self-contained SVG.
	 *
	 * Never throws: invalid type/shape or empty data degrade to a visible
	 * placeholder box inside the returned SVG rather than an exception, so
	 * callers (the Twig `chart()` function) can always embed the result.
	 * Check {@see getLastWarning()} immediately afterwards to detect a
	 * placeholder fallback.
	 *
	 * @param string $type Chart type: 'bar', 'line', or 'pie'.
	 * @param array $data `{labels: string[], series: [{name, values}]}`.
	 * @param array $options Rendering options: title, width, height, palette
	 *                       (hex[] override), showLegend (bool), valueFormat
	 *                       ('integer'|'decimal:N'|'currency'|'percent'),
	 *                       orientation ('vertical'|'horizontal', bar only),
	 *                       donut (bool, pie only), maxPoints (int).
	 *
	 * @return string SVG markup (starts with `<svg`).
	 *
	 * @spec openspec/changes/template-charts/specs/template-charts/spec.md#REQ-DDTCH-001
	 */
	public function render(string $type, array $data, array $options = []): string {
		$this->lastWarning = null;

		$width = $this->intOption(options: $options, key: 'width', default: self::DEFAULT_WIDTH);
		$height = $this->intOption(options: $options, key: 'height', default: self::DEFAULT_HEIGHT);

		if (in_array(needle: $type, haystack: self::SUPPORTED_TYPES, strict: true) === false) {
			return $this->renderPlaceholder(
				width: $width,
				height: $height,
				message: 'chart error: unsupported chart type "' . $type . '"'
			);
		}

		$normalized = $this->normalizer->normalize(
			data: $data,
			maxPoints: $this->intOption(options: $options, key: 'maxPoints', default: self::DEFAULT_MAX_POINTS)
		);

		if ($normalized instanceof ChartRenderError) {
			return $this->renderPlaceholder(width: $width, height: $height, message: $normalized->message);
		}

		$svg = $this->delegate(
			type: $type,
			normalized: $normalized,
			palette: $this->palette->resolve(options: $options),
			width: $width,
			height: $height,
			options: $options
		);

		if ($svg instanceof ChartRenderError) {
			return $this->renderPlaceholder(width: $width, height: $height, message: $svg->message);
		}

		return $svg;
	}//end render()

	/**
	 * The failure reason from the most recent {@see render()} call, or null
	 * when it rendered a real chart (no placeholder fallback).
	 *
	 * @return string|null
	 *
	 * @spec openspec/changes/template-charts/specs/template-charts/spec.md#REQ-DDTCH-002
	 */
	public function getLastWarning(): ?string {
		return $this->lastWarning;
	}//end getLastWarning()

	/**
	 * Hand a normalized payload to the renderer for its chart type.
	 *
	 * @param string $type Chart type (already validated as supported).
	 * @param array $normalized Normalized `{labels, series}` data.
	 * @param array $palette Ordered hex colors.
	 * @param int $width Canvas width.
	 * @param int $height Canvas height.
	 * @param array $options Rendering options.
	 *
	 * @return string|ChartRenderError SVG markup, or the reason no chart could
	 *                                 be drawn.
	 */
	private function delegate(
		string $type,
		array $normalized,
		array $palette,
		int $width,
		int $height,
		array $options,
	): string|ChartRenderError {
		if ($type === 'bar') {
			return $this->bar->render(
				normalized: $normalized,
				palette: $palette,
				width: $width,
				height: $height,
				options: $options
			);
		}

		if ($type === 'line') {
			return $this->line->render(
				normalized: $normalized,
				palette: $palette,
				width: $width,
				height: $height,
				options: $options
			);
		}

		return $this->pie->render(
			normalized: $normalized,
			palette: $palette,
			width: $width,
			height: $height,
			options: $options
		);

	}//end delegate()

	/**
	 * Render an accessible placeholder box with a centered message — used
	 * for chart errors and empty-data states. Always valid SVG so callers
	 * can embed the result unconditionally.
	 *
	 * @param int $width Canvas width.
	 * @param int $height Canvas height.
	 * @param string $message Message to display (escaped).
	 *
	 * @return string SVG markup.
	 */
	private function renderPlaceholder(int $width, int $height, string $message): string {
		$this->lastWarning = $message;

		$parts = [];
		$parts[] = $this->svg->svgOpenTag(width: $width, height: $height);
		$parts[] = $this->svg->rectEl(x: 1, y: 1, width: $width - 2, height: $height - 2, color: '#F5F5F5');
		$parts[] = $this->svg->rectOutlineEl(x: 1, y: 1, width: $width - 2, height: $height - 2, color: '#CCCCCC');
		$parts[] = $this->svg->textEl(x: $width / 2, y: $height / 2, text: $message, anchor: 'middle', size: 11, color: '#888888');
		$parts[] = '</svg>';

		return implode('', $parts);
	}//end renderPlaceholder()

	/**
	 * Read an integer option with a default and a sane positive floor.
	 *
	 * @param array $options Options array.
	 * @param string $key Option key.
	 * @param int $default Default value.
	 *
	 * @return int Resolved value (always >= 1).
	 */
	private function intOption(array $options, string $key, int $default): int {
		$value = $options[$key] ?? $default;
		if (is_numeric($value) === false) {
			return $default;
		}

		return max(1, (int)$value);
	}//end intOption()
}//end class
