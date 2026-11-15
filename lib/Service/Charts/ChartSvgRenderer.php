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

use OCP\IL10N;

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
 * @package  OCA\Filinq\Service\Charts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-28-template-charts/tasks.md#task-1.1
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
	 * Overridable via the `filinq.charts.max_points` app config value.
	 *
	 * @var int
	 */
	private const DEFAULT_MAX_POINTS = 1000;

	/**
	 * The default of each integer option.
	 *
	 * @var array<string, int>
	 */
	private const INT_OPTION_DEFAULTS = [
		'width' => self::DEFAULT_WIDTH,
		'height' => self::DEFAULT_HEIGHT,
		'maxPoints' => self::DEFAULT_MAX_POINTS,
	];

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
	 * The one optional argument translates the error markers; without it
	 * they stay in English.
	 *
	 * @param IL10N|null $l10n Translates the error markers (REQ-DDTCH-002).
	 *
	 * @return void
	 */
	public function __construct(
		private readonly ?IL10N $l10n = null,
	) {
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
	 * @spec openspec/specs/template-charts/spec.md#REQ-DDTCH-001
	 */
	public function render(string $type, array $data, array $options = []): string {
		$this->lastWarning = null;

		$outcome = $this->chartOrError(type: $type, input: new ChartInput(data: $data, options: $options));
		if ($outcome instanceof ChartRenderError) {
			return $this->placeholderFor(error: $outcome, options: $options);
		}

		return $outcome;
	}//end render()

	/**
	 * The chart's SVG, or why it cannot be drawn: an unsupported type, data
	 * that does not normalise, or a renderer refusal.
	 *
	 * @param string     $type  Chart type: 'bar', 'line', or 'pie'.
	 * @param ChartInput $input The raw data and options.
	 *
	 * @return string|ChartRenderError The SVG, or the reason for a placeholder.
	 */
	private function chartOrError(string $type, ChartInput $input): string|ChartRenderError {
		if (in_array(needle: $type, haystack: self::SUPPORTED_TYPES, strict: true) === false) {
			return new ChartRenderError(message: 'chart error: unsupported chart type "%s"', parameters: [$type]);
		}

		$normalized = $this->normalizer->normalize(
			data: $input->data,
			maxPoints: $this->intOption(options: $input->options, key: 'maxPoints')
		);

		if ($normalized instanceof ChartRenderError) {
			return $normalized;
		}

		return $this->delegate(type: $type, chart: $this->chartRequest(normalized: $normalized, options: $input->options));
	}//end chartOrError()

	/**
	 * The request the per-type renderers draw from.
	 *
	 * @param array $normalized The normalised data.
	 * @param array $options    The rendering options.
	 *
	 * @return ChartRequest The data, palette, canvas size and options.
	 */
	private function chartRequest(array $normalized, array $options): ChartRequest {
		return new ChartRequest(
			normalized: $normalized,
			palette: $this->palette->resolve(options: $options),
			width: $this->intOption(options: $options, key: 'width'),
			height: $this->intOption(options: $options, key: 'height'),
			options: $options
		);
	}//end chartRequest()

	/**
	 * The failure reason from the most recent {@see render()} call, or null
	 * when it rendered a real chart (no placeholder fallback).
	 *
	 * @return string|null
	 *
	 * @spec openspec/specs/template-charts/spec.md#REQ-DDTCH-002
	 */
	public function getLastWarning(): ?string {
		return $this->lastWarning;
	}//end getLastWarning()

	/**
	 * Hand a normalized payload to the renderer for its chart type.
	 *
	 * @param string $type Chart type (already validated as supported).
	 * @param ChartRequest $chart The normalized data, palette, canvas size and options.
	 *
	 * @return string|ChartRenderError SVG markup, or the reason no chart could
	 *                                 be drawn.
	 */
	private function delegate(string $type, ChartRequest $chart): string|ChartRenderError {
		if ($type === 'bar') {
			return $this->bar->render(chart: $chart);
		}

		if ($type === 'line') {
			return $this->line->render(chart: $chart);
		}

		return $this->pie->render(chart: $chart);

	}//end delegate()

	/**
	 * A visible placeholder box carrying the (translated) reason; the reason
	 * is also kept for {@see getLastWarning()}.
	 *
	 * @param ChartRenderError $error   Why no chart is drawn.
	 * @param array            $options The rendering options (width, height).
	 *
	 * @return string SVG markup.
	 */
	private function placeholderFor(ChartRenderError $error, array $options): string {
		$message = $this->translated(error: $error);
		$this->lastWarning = $message;

		$width = $this->intOption(options: $options, key: 'width');
		$height = $this->intOption(options: $options, key: 'height');

		return $this->svg->svgOpenTag(width: $width, height: $height)
			. $this->svg->placeholderEl(canvas: new SvgSize(width: $width, height: $height), message: $message)
			. '</svg>';
	}//end placeholderFor()

	/**
	 * The reason as the reader sees it: translated when a translator is
	 * wired, its parameters filled in otherwise.
	 *
	 * @param ChartRenderError $error Why no chart is drawn.
	 *
	 * @return string The message.
	 */
	private function translated(ChartRenderError $error): string {
		if ($this->l10n !== null) {
			return $this->l10n->t($error->message, $error->parameters);
		}

		if ($error->parameters !== []) {
			return vsprintf($error->message, $error->parameters);
		}

		return $error->message;
	}//end translated()

	/**
	 * A positive integer option, or its default when absent or not numeric.
	 *
	 * @param array  $options The rendering options.
	 * @param string $key     'width', 'height' or 'maxPoints'.
	 *
	 * @return int The value, at least 1.
	 */
	private function intOption(array $options, string $key): int {
		$default = self::INT_OPTION_DEFAULTS[$key];
		$value = $options[$key] ?? $default;
		if (is_numeric($value) === false) {
			return $default;
		}

		return max(1, (int)$value);
	}//end intOption()
}//end class
