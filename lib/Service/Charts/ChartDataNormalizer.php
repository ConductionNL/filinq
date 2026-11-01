<?php

/**
 * Chart Data Normalizer
 *
 * Validates and normalizes the incoming `{labels, series}` chart data shape
 * into the fixed structure the renderers consume, or returns a
 * {@see ChartRenderError} describing why the payload is unusable.
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
 * Normalizes raw chart data payloads.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Charts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-28-template-charts/tasks.md#task-1.1
 */
class ChartDataNormalizer {
	/**
	 * Validate and normalize the incoming chart data shape.
	 *
	 * Missing/null/non-numeric values are skipped (kept as `null` markers so
	 * bar/line renderers can leave a gap) rather than causing an error;
	 * only a structurally invalid or fully empty payload is an error.
	 *
	 * @param array $data Raw chart data.
	 * @param int $maxPoints Maximum allowed labels/points.
	 *
	 * @return array{labels: string[], series: array<int, array{name: string, values: array<int, float|null>}>}|ChartRenderError
	 *
	 * @spec openspec/specs/template-charts/spec.md#REQ-DDTCH-001
	 */
	public function normalize(array $data, int $maxPoints): array|ChartRenderError {
		$shapeError = $this->validateShape(data: $data, maxPoints: $maxPoints);
		if ($shapeError !== null) {
			return $shapeError;
		}

		$normalizedSeries = $this->normalizeSeriesList(seriesList: $data['series'], pointCount: count($data['labels']));
		if ($normalizedSeries === null) {
			return new ChartRenderError(message: 'chart error: no data');
		}

		$normalizedLabels = [];
		foreach ($data['labels'] as $label) {
			$normalizedLabels[] = (string)$label;
		}

		return [
			'labels' => $normalizedLabels,
			'series' => $normalizedSeries,
		];

	}//end normalize()

	/**
	 * Normalize every array entry of the series list.
	 *
	 * @param array $seriesList The raw series entries; entries that are not arrays are skipped.
	 * @param int $pointCount Number of label positions to fill.
	 *
	 * @return array<int, array{name: string, values: array<int, float|null>}>|null The series, or null when none holds a value.
	 */
	private function normalizeSeriesList(array $seriesList, int $pointCount): ?array {
		$normalizedSeries = [];
		foreach (array_filter($seriesList, 'is_array') as $oneSeries) {
			$normalizedSeries[] = $this->normalizeSeries(oneSeries: $oneSeries, pointCount: $pointCount);
		}

		$allValues = array_merge(...array_column($normalizedSeries, 'values'));
		if ($this->containsValue(values: $allValues) === false) {
			return null;
		}

		return $normalizedSeries;
	}//end normalizeSeriesList()

	/**
	 * Normalize one series: its name as a string, its values as floats or gaps.
	 *
	 * @param array $oneSeries The raw series.
	 * @param int $pointCount Number of label positions to fill.
	 *
	 * @return array{name: string, values: array<int, float|null>} The series.
	 */
	private function normalizeSeries(array $oneSeries, int $pointCount): array {
		$values = $oneSeries['values'] ?? [];
		if (is_array($values) === false) {
			$values = [];
		}

		return [
			'name' => (string)($oneSeries['name'] ?? ''),
			'values' => $this->normalizeValues(values: $values, pointCount: $pointCount),
		];
	}//end normalizeSeries()

	/**
	 * Check the top-level payload shape and point budget.
	 *
	 * @param array $data Raw chart data.
	 * @param int $maxPoints Maximum allowed labels/points.
	 *
	 * @return ChartRenderError|null The error, or null when the shape is usable.
	 */
	private function validateShape(array $data, int $maxPoints): ?ChartRenderError {
		$labels = $data['labels'] ?? null;
		$series = $data['series'] ?? null;

		if (is_array($labels) === false || is_array($series) === false) {
			return new ChartRenderError(message: 'chart error: data must include "labels" and "series"');
		}

		if (count($labels) === 0 || $series === []) {
			return new ChartRenderError(message: 'chart error: no data');
		}

		if (count($labels) > $maxPoints) {
			return new ChartRenderError(
				message: 'chart error: too many data points (max %s)',
				parameters: [$maxPoints]
			);
		}

		return null;
	}//end validateShape()

	/**
	 * Coerce one series' raw values into `$pointCount` floats or null gaps.
	 *
	 * @param array $values Raw values, indexed by point position.
	 * @param int $pointCount Number of label positions to fill.
	 *
	 * @return array<int, float|null> The coerced values.
	 */
	private function normalizeValues(array $values, int $pointCount): array {
		$normalized = [];
		for ($i = 0; $i < $pointCount; $i++) {
			$raw = $values[$i] ?? null;
			if (is_int($raw) === true || is_float($raw) === true) {
				$normalized[] = (float)$raw;
				continue;
			}

			if (is_string($raw) === true && is_numeric($raw) === true) {
				$normalized[] = (float)$raw;
				continue;
			}

			// Missing, null, or non-numeric: skipped (gap), never an error.
			$normalized[] = null;
		}

		return $normalized;
	}//end normalizeValues()

	/**
	 * Whether a coerced value list holds at least one numeric point.
	 *
	 * @param array<int, float|null> $values The coerced values.
	 *
	 * @return bool True when any point is numeric.
	 */
	private function containsValue(array $values): bool {
		foreach ($values as $value) {
			if ($value !== null) {
				return true;
			}
		}

		return false;
	}//end containsValue()
}//end class
