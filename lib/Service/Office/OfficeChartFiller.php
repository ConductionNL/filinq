<?php

/**
 * Office Chart Filler
 *
 * Fills the `${chart:key}` placeholders of an office (DOCX) template with
 * native WordprocessingML charts, built from `charts.key = {type, data,
 * options}` in the generation context. It runs in the office fill
 * pipeline's pre-pass, before plain values are set, so the chart part is
 * written while the placeholder is still in the document.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Office
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-11-24-office-charts-and-images/tasks.md#task-1.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Office;

use OCA\Filinq\Service\Charts\ChartDataNormalizer;
use OCA\Filinq\Service\Charts\ChartRenderError;
use OCP\IL10N;
use PhpOffice\PhpWord\Element\Chart;
use PhpOffice\PhpWord\TemplateProcessor;
use Throwable;

/**
 * Turns `${chart:key}` placeholders into native Word charts.
 *
 * A placeholder whose context is missing, malformed or of an unsupported type
 * is replaced by the visible marker text (`[chart error: …]`, in the user's
 * language) and reported as a generation warning, the same honest failure as
 * the Twig `chart()` function.
 *
 * @spec openspec/changes/archive/2026-11-24-office-charts-and-images/specs/template-charts/spec.md#REQ-DDTCH-003
 */
class OfficeChartFiller {

	/**
	 * The placeholder family prefix, as it appears inside `${…}`.
	 */
	public const PREFIX = 'chart:';

	/**
	 * Maximum charts filled in one document, the same cap as the Twig path.
	 */
	public const MAX_CHARTS_PER_DOCUMENT = 20;

	/**
	 * Maximum labels (points) per chart, the same default as the Twig path.
	 */
	public const MAX_POINTS = 1000;

	/**
	 * Supported context types mapped to PhpWord chart types. A vertical bar
	 * chart is a Word "column" chart; a horizontal one is a Word "bar" chart.
	 */
	private const TYPES = [
		'bar'  => 'column',
		'line' => 'line',
		'pie'  => 'pie',
	];

	/**
	 * Constructor for OfficeChartFiller.
	 *
	 * @param ChartDataNormalizer $normalizer The shared chart data validator.
	 * @param IL10N|null          $l10n       Translator for the marker text.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly ChartDataNormalizer $normalizer,
		private readonly ?IL10N $l10n = null,
	) {

	}//end __construct()

	/**
	 * Fill every `${chart:key}` placeholder of the template.
	 *
	 * @param TemplateProcessor $processor The office template being filled.
	 * @param array             $charts    The `charts` context entry, keyed by placeholder key.
	 *
	 * @return string[] The generation warnings, one per placeholder that became a marker.
	 *
	 * @spec openspec/changes/archive/2026-11-24-office-charts-and-images/specs/template-charts/spec.md#REQ-DDTCH-003
	 */
	public function fill(TemplateProcessor $processor, array $charts): array {
		$warnings = [];
		$count    = 0;
		foreach ($this->chartVariables(processor: $processor) as $variable) {
			$count++;
			$key = substr($variable, strlen(self::PREFIX));

			$chart = $this->buildForKey(charts: $charts, key: $key, count: $count);
			if ($chart instanceof ChartRenderError === false) {
				try {
					$processor->setChart($variable, $chart);
					continue;
				} catch (Throwable) {
					$chart = new ChartRenderError(message: 'chart error: the chart could not be placed');
				}
			}

			$message = $this->translate(text: $chart->message, parameters: $chart->parameters);
			$processor->setValue($variable, '[' . $message . ']');
			$warnings[] = $message;
		}

		return $warnings;

	}//end fill()

	/**
	 * Build the PhpWord chart element for one context entry.
	 *
	 * @param string $type    The context chart type: bar, line or pie.
	 * @param array  $data    The `{labels, series}` data.
	 * @param array  $options The options: `title`, `orientation`, `legend`, `width`, `height` (EMU).
	 *
	 * @return Chart|ChartRenderError The element, or the reason it cannot be built.
	 *
	 * @spec openspec/changes/archive/2026-11-24-office-charts-and-images/specs/template-charts/spec.md#REQ-DDTCH-003
	 */
	public function build(string $type, array $data, array $options=[]): Chart|ChartRenderError {
		if (isset(self::TYPES[$type]) === false) {
			return new ChartRenderError(message: 'chart error: unsupported chart type "%s"', parameters: [$type]);
		}

		$normalized = $this->normalizer->normalize(data: $data, maxPoints: self::MAX_POINTS);
		if ($normalized instanceof ChartRenderError) {
			return $normalized;
		}

		$wordType = self::TYPES[$type];
		if ($type === 'bar' && ($options['orientation'] ?? 'vertical') === 'horizontal') {
			$wordType = 'bar';
		}

		$series = $normalized['series'];
		if ($type === 'pie') {
			// A pie shows one series; further series have no place in it.
			$series = [$series[0]];
		}

		$first = array_shift($series);
		$chart = new Chart($wordType, $normalized['labels'], $this->values(values: $first['values']), null, $first['name']);
		foreach ($series as $oneSeries) {
			$chart->addSeries($normalized['labels'], $this->values(values: $oneSeries['values']), $oneSeries['name']);
		}

		$this->applyStyle(chart: $chart, options: $options, seriesCount: count($series) + 1, type: $type);

		return $chart;

	}//end build()

	/**
	 * The `${chart:…}` variables of the template, in document order.
	 *
	 * @param TemplateProcessor $processor The office template.
	 *
	 * @return string[] The variable names without the macro characters.
	 */
	private function chartVariables(TemplateProcessor $processor): array {
		$variables = [];
		foreach ($processor->getVariables() as $variable) {
			if (str_starts_with($variable, self::PREFIX) === true && strlen($variable) > strlen(self::PREFIX)) {
				$variables[] = $variable;
			}
		}

		return $variables;

	}//end chartVariables()

	/**
	 * Build the chart for one placeholder key, or the reason it cannot be.
	 *
	 * @param array  $charts The `charts` context entry.
	 * @param string $key    The placeholder key.
	 * @param int    $count  The 1-based position of this chart in the document.
	 *
	 * @return Chart|ChartRenderError The element or the error.
	 */
	private function buildForKey(array $charts, string $key, int $count): Chart|ChartRenderError {
		if ($count > self::MAX_CHARTS_PER_DOCUMENT) {
			return new ChartRenderError(
				message: 'chart error: document exceeds the maximum of %s charts',
				parameters: [self::MAX_CHARTS_PER_DOCUMENT]
			);
		}

		$entry = $charts[$key] ?? null;
		if (is_array($entry) === false) {
			return new ChartRenderError(message: 'chart error: no data');
		}

		$data = $entry['data'] ?? [];
		if (is_array($data) === false) {
			$data = [];
		}

		$options = $entry['options'] ?? [];
		if (is_array($options) === false) {
			$options = [];
		}

		return $this->build(type: (string)($entry['type'] ?? ''), data: $data, options: $options);

	}//end buildForKey()

	/**
	 * Values as PhpWord writes them: a gap becomes zero, Word has no null point.
	 *
	 * @param array<int, float|null> $values The normalized values.
	 *
	 * @return float[] The values.
	 */
	private function values(array $values): array {
		return array_map(static fn (?float $value): float => $value ?? 0.0, $values);

	}//end values()

	/**
	 * Apply the title, legend and size options to the chart style.
	 *
	 * @param Chart  $chart       The chart element.
	 * @param array  $options     The chart options.
	 * @param int    $seriesCount Number of series in the chart.
	 * @param string $type        The context chart type.
	 *
	 * @return void
	 */
	private function applyStyle(Chart $chart, array $options, int $seriesCount, string $type): void {
		$style = $chart->getStyle();

		$title = $options['title'] ?? null;
		if (is_string($title) === true && $title !== '') {
			$style->setTitle($title);
		}

		$legend = $options['legend'] ?? ($seriesCount > 1 || $type === 'pie');
		$style->setShowLegend((bool)$legend);

		foreach (['width' => 'setWidth', 'height' => 'setHeight'] as $option => $setter) {
			$size = $options[$option] ?? null;
			if (is_int($size) === true && $size > 0) {
				$style->$setter($size);
			}
		}

	}//end applyStyle()

	/**
	 * Translate an English source string with `%s` parameters.
	 *
	 * @param string $text       The source string.
	 * @param array  $parameters The `%s` values.
	 *
	 * @return string The translated text.
	 */
	private function translate(string $text, array $parameters=[]): string {
		if ($this->l10n !== null) {
			return $this->l10n->t($text, $parameters);
		}

		if ($parameters === []) {
			return $text;
		}

		return vsprintf($text, $parameters);

	}//end translate()
}//end class
