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
class ChartSvgRenderer
{

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
     * Fixed accessible fallback palette (used when no huisstijl seed color is
     * available, or the seed color does not yield sufficient contrast).
     * Chosen for pairwise distinguishability on a white background.
     *
     * @var string[]
     */
    private const FALLBACK_PALETTE = [
        '#21468B',
        '#DE6E4B',
        '#3FA796',
        '#F2A93B',
        '#7A5FB5',
        '#5B8DEF',
        '#C0562F',
        '#4B8F29',
    ];

    /**
     * Maximum number of legend/slice-label entries rendered before the
     * renderer stops emitting individual text labels (chart shapes are still
     * drawn; this only bounds label text volume for very wide series).
     *
     * @var int
     */
    private const MAX_LABELLED_ENTRIES = 20;

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
     * Render a chart as self-contained SVG.
     *
     * Never throws: invalid type/shape or empty data degrade to a visible
     * placeholder box inside the returned SVG rather than an exception, so
     * callers (the Twig `chart()` function) can always embed the result.
     * Check {@see getLastWarning()} immediately afterwards to detect a
     * placeholder fallback.
     *
     * @param string $type    Chart type: 'bar', 'line', or 'pie'.
     * @param array  $data    `{labels: string[], series: [{name, values}]}`.
     * @param array  $options Rendering options: title, width, height, palette
     *                        (hex[] override), showLegend (bool), valueFormat
     *                        ('integer'|'decimal:N'|'currency'|'percent'),
     *                        orientation ('vertical'|'horizontal', bar only),
     *                        donut (bool, pie only), maxPoints (int).
     *
     * @return string SVG markup (starts with `<svg`).
     *
     * @spec openspec/changes/template-charts/specs/template-charts/spec.md#REQ-DDTCH-001
     */
    public function render(string $type, array $data, array $options=[]): string
    {
        $this->lastWarning = null;

        $width  = $this->intOption(options: $options, key: 'width', default: self::DEFAULT_WIDTH);
        $height = $this->intOption(options: $options, key: 'height', default: self::DEFAULT_HEIGHT);

        if (in_array(needle: $type, haystack: self::SUPPORTED_TYPES, strict: true) === false) {
            return $this->renderPlaceholder(
                width: $width,
                height: $height,
                message: 'chart error: unsupported chart type "'.$type.'"'
            );
        }

        $maxPoints  = $this->intOption(options: $options, key: 'maxPoints', default: self::DEFAULT_MAX_POINTS);
        $normalized = $this->normalizeData(data: $data, maxPoints: $maxPoints);

        if ($normalized instanceof ChartRenderError) {
            return $this->renderPlaceholder(width: $width, height: $height, message: $normalized->message);
        }

        $palette = $this->resolvePalette(options: $options);

        switch ($type) {
            case 'bar':
                return $this->renderBar(
                    normalized: $normalized,
                    palette: $palette,
                    width: $width,
                    height: $height,
                    options: $options
                );
            case 'line':
                return $this->renderLine(
                    normalized: $normalized,
                    palette: $palette,
                    width: $width,
                    height: $height,
                    options: $options
                );
            case 'pie':
            default:
                return $this->renderPie(
                    normalized: $normalized,
                    palette: $palette,
                    width: $width,
                    height: $height,
                    options: $options
                );
        }//end switch

    }//end render()

    /**
     * The failure reason from the most recent {@see render()} call, or null
     * when it rendered a real chart (no placeholder fallback).
     *
     * @return string|null
     *
     * @spec openspec/changes/template-charts/specs/template-charts/spec.md#REQ-DDTCH-002
     */
    public function getLastWarning(): ?string
    {
        return $this->lastWarning;

    }//end getLastWarning()

    /**
     * Validate and normalize the incoming chart data shape.
     *
     * Missing/null/non-numeric values are skipped (kept as `null` markers so
     * bar/line renderers can leave a gap) rather than causing an error;
     * only a structurally invalid or fully empty payload is an error.
     *
     * @param array $data      Raw chart data.
     * @param int   $maxPoints Maximum allowed labels/points.
     *
     * @return array{labels: string[], series: array<int, array{name: string, values: array<int, float|null>}>}|ChartRenderError
     */
    private function normalizeData(array $data, int $maxPoints): array|ChartRenderError
    {
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
                message: 'chart error: too many data points (max '.$maxPoints.')'
            );
        }

        $pointCount       = count($labels);
        $normalizedSeries = [];
        $hasAnyValue      = false;

        foreach ($series as $oneSeries) {
            if (is_array($oneSeries) === false) {
                continue;
            }

            $name   = (string) ($oneSeries['name'] ?? '');
            $values = $oneSeries['values'] ?? [];
            if (is_array($values) === false) {
                $values = [];
            }

            $normalizedValues = [];
            for ($i = 0; $i < $pointCount; $i++) {
                $raw = $values[$i] ?? null;
                if (is_int($raw) === true || is_float($raw) === true) {
                    $normalizedValues[] = (float) $raw;
                    $hasAnyValue        = true;
                    continue;
                }

                if (is_string($raw) === true && is_numeric($raw) === true) {
                    $normalizedValues[] = (float) $raw;
                    $hasAnyValue        = true;
                    continue;
                }

                // Missing, null, or non-numeric: skipped (gap), never an error.
                $normalizedValues[] = null;
            }

            $normalizedSeries[] = [
                'name'   => $name,
                'values' => $normalizedValues,
            ];
        }//end foreach

        if ($normalizedSeries === [] || $hasAnyValue === false) {
            return new ChartRenderError(message: 'chart error: no data');
        }

        $normalizedLabels = [];
        foreach ($labels as $label) {
            $normalizedLabels[] = (string) $label;
        }

        return [
            'labels' => $normalizedLabels,
            'series' => $normalizedSeries,
        ];

    }//end normalizeData()

    /**
     * Resolve the color palette for a chart, honouring an explicit override.
     *
     * @param array $options Rendering options (may include 'palette' and/or
     *                       'huisstijlPrimaryColor').
     *
     * @return string[] Ordered list of hex colors.
     */
    private function resolvePalette(array $options): array
    {
        if (isset($options['palette']) === true && is_array($options['palette']) === true && $options['palette'] !== []) {
            $override = [];
            foreach ($options['palette'] as $color) {
                if (is_string($color) === true && $this->isValidHexColor(color: $color) === true) {
                    $override[] = $color;
                }
            }

            if ($override !== []) {
                return $override;
            }
        }

        $seed = $options['huisstijlPrimaryColor'] ?? null;
        if (is_string($seed) === true && $this->isValidHexColor(color: $seed) === true) {
            $ramp = $this->buildRampFromSeed(seed: $seed);
            if ($ramp !== null) {
                return $ramp;
            }
        }

        return self::FALLBACK_PALETTE;

    }//end resolvePalette()

    /**
     * Build a deterministic multi-color ramp seeded from a huisstijl primary
     * color by rotating hue in fixed steps. Rejects seed colors that would
     * not provide sufficient contrast against a white background.
     *
     * @param string $seed Hex color, e.g. '#21468B'.
     *
     * @return string[]|null Ramp of 8 hex colors, or null if the seed is unusable.
     */
    private function buildRampFromSeed(string $seed): ?array
    {
        [$r, $g, $b] = $this->hexToRgb(hex: $seed);

        // Relative luminance (WCAG); reject seed colors too close to white
        // (poor contrast/near-invisible bars/lines/slices).
        $luminance = ((0.2126 * $r) + (0.7152 * $g) + (0.0722 * $b)) / 255;
        if ($luminance > 0.85) {
            return null;
        }

        [$h, $s, $l] = $this->rgbToHsl(r: $r, g: $g, b: $b);

        // Keep saturation/lightness in a legible band regardless of seed.
        $s = max(0.35, min($s, 0.85));
        $l = max(0.30, min($l, 0.55));

        $ramp = [];
        for ($i = 0; $i < 8; $i++) {
            $hue    = fmod(($h + ($i * 45)), 360);
            $ramp[] = $this->hslToHex(h: $hue, s: $s, l: $l);
        }

        return $ramp;

    }//end buildRampFromSeed()

    /**
     * Render a bar chart (vertical, grouped by series; or horizontal, first
     * series only — the ranked-list orientation).
     *
     * @param array $normalized Normalized `{labels, series}` data.
     * @param array $palette    Ordered hex colors.
     * @param int   $width      Canvas width.
     * @param int   $height     Canvas height.
     * @param array $options    Rendering options.
     *
     * @return string SVG markup.
     */
    private function renderBar(array $normalized, array $palette, int $width, int $height, array $options): string
    {
        $orientation = ($options['orientation'] ?? 'vertical');
        if ($orientation === 'horizontal') {
            return $this->renderHorizontalBar(normalized: $normalized, palette: $palette, width: $width, height: $height, options: $options);
        }

        $labels     = $normalized['labels'];
        $series     = $normalized['series'];
        $title      = (string) ($options['title'] ?? '');
        $valueFmt   = (string) ($options['valueFormat'] ?? 'integer');
        $numLabels  = count($labels);
        $numSeries  = count($series);
        $showLegend = (bool) ($options['showLegend'] ?? ($numSeries > 1));

        $marginTop = 14;
        if ($title !== '') {
            $marginTop = 34;
        }

        $marginBottom = 40;
        if ($showLegend === true) {
            $marginBottom += 22;
        }

        $marginLeft  = 46;
        $marginRight = 16;

        $plotWidth  = max(1, $width - $marginLeft - $marginRight);
        $plotHeight = max(1, $height - $marginTop - $marginBottom);

        $max     = $this->seriesMax(series: $series);
        $niceMax = $this->niceCeiling(value: $max);
        if ($niceMax <= 0.0) {
            $niceMax = 1.0;
        }

        $parts   = [];
        $parts[] = $this->svgOpenTag(width: $width, height: $height);

        if ($title !== '') {
            $parts[] = $this->textEl(x: $width / 2, y: 18, text: $title, anchor: 'middle', size: 13, weight: 'bold');
        }

        // Gridlines + y-axis ticks (5 lines: 0..max).
        $tickCount = 4;
        for ($t = 0; $t <= $tickCount; $t++) {
            $value      = ($niceMax / $tickCount) * $t;
            $y          = $marginTop + $plotHeight - (($value / $niceMax) * $plotHeight);
            $valueLabel = $this->formatValue(value: $value, format: $valueFmt);
            $parts[]    = $this->lineEl(x1: $marginLeft, y1: $y, x2: $marginLeft + $plotWidth, y2: $y, color: '#E2E2E2', width: 1);
            $parts[]    = $this->textEl(x: $marginLeft - 6, y: $y + 3, text: $valueLabel, anchor: 'end', size: 9, color: '#666666');
        }//end for

        // Axis baseline.
        $baselineY = $marginTop + $plotHeight;
        $parts[]   = $this->lineEl(x1: $marginLeft, y1: $baselineY, x2: $marginLeft + $plotWidth, y2: $baselineY, color: '#999999', width: 1);

        $groupWidth = $plotWidth / max(1, $numLabels);
        $barGap     = $groupWidth * 0.18;
        $barsWidth  = $groupWidth - (2 * $barGap);
        $barWidth   = $barsWidth / max(1, $numSeries);

        for ($i = 0; $i < $numLabels; $i++) {
            $groupX = $marginLeft + ($i * $groupWidth);

            for ($s = 0; $s < $numSeries; $s++) {
                $value = $series[$s]['values'][$i] ?? null;
                if ($value === null) {
                    continue;
                }

                $barHeight = ($value / $niceMax) * $plotHeight;
                $barHeight = max(0.0, $barHeight);
                $x         = $groupX + $barGap + ($s * $barWidth);
                $y         = $baselineY - $barHeight;
                $color     = $palette[$s % count($palette)];

                $parts[] = $this->rectEl(x: $x, y: $y, width: $barWidth * 0.86, height: $barHeight, color: $color);

                if ($numLabels <= self::MAX_LABELLED_ENTRIES && $barHeight > 12) {
                    $parts[] = $this->textEl(
                        x: $x + (($barWidth * 0.86) / 2),
                        y: $y - 4,
                        text: $this->formatValue(value: $value, format: $valueFmt),
                        anchor: 'middle',
                        size: 9,
                        color: '#333333'
                    );
                }
            }//end for

            if ($numLabels <= self::MAX_LABELLED_ENTRIES) {
                $label   = $this->truncate(text: $labels[$i], max: 14);
                $parts[] = $this->textEl(
                    x: $groupX + ($groupWidth / 2),
                    y: $baselineY + 16,
                    text: $label,
                    anchor: 'middle',
                    size: 9,
                    color: '#333333'
                );
            }
        }//end for

        if ($showLegend === true) {
            $parts[] = $this->renderLegend(series: $series, palette: $palette, x: $marginLeft, y: $height - 12);
        }

        $parts[] = '</svg>';

        return implode('', $parts);

    }//end renderBar()

    /**
     * Render a horizontal bar chart (ranked-list orientation, first series only).
     *
     * @param array $normalized Normalized `{labels, series}` data.
     * @param array $palette    Ordered hex colors.
     * @param int   $width      Canvas width.
     * @param int   $height     Canvas height.
     * @param array $options    Rendering options.
     *
     * @return string SVG markup.
     */
    private function renderHorizontalBar(array $normalized, array $palette, int $width, int $height, array $options): string
    {
        $labels    = $normalized['labels'];
        $values    = $normalized['series'][0]['values'];
        $title     = (string) ($options['title'] ?? '');
        $valueFmt  = (string) ($options['valueFormat'] ?? 'integer');
        $numLabels = count($labels);
        $color     = $palette[0];

        $marginTop = 14;
        if ($title !== '') {
            $marginTop = 34;
        }

        $marginBottom = 12;
        $marginLeft   = 130;
        $marginRight  = 50;

        $plotWidth  = max(1, $width - $marginLeft - $marginRight);
        $plotHeight = max(1, $height - $marginTop - $marginBottom);

        $numericValues = array_filter($values, static fn ($v) => $v !== null);
        if ($numericValues === []) {
            $numericValues = [0];
        }

        $max     = max($numericValues);
        $niceMax = $this->niceCeiling(value: (float) $max);
        if ($niceMax <= 0.0) {
            $niceMax = 1.0;
        }

        $parts   = [];
        $parts[] = $this->svgOpenTag(width: $width, height: $height);

        if ($title !== '') {
            $parts[] = $this->textEl(x: $width / 2, y: 18, text: $title, anchor: 'middle', size: 13, weight: 'bold');
        }

        $rowHeight = $plotHeight / max(1, $numLabels);
        $barHeight = $rowHeight * 0.6;

        for ($i = 0; $i < $numLabels; $i++) {
            $value = $values[$i] ?? null;
            $rowY  = $marginTop + ($i * $rowHeight);
            $label = $this->truncate(text: $labels[$i], max: 22);

            $parts[] = $this->textEl(
                x: $marginLeft - 8,
                y: $rowY + ($rowHeight / 2) + 3,
                text: $label,
                anchor: 'end',
                size: 9,
                color: '#333333'
            );

            if ($value === null) {
                continue;
            }

            $barWidth = ($value / $niceMax) * $plotWidth;
            $barWidth = max(0.0, $barWidth);
            $barY     = $rowY + (($rowHeight - $barHeight) / 2);

            $parts[] = $this->rectEl(x: $marginLeft, y: $barY, width: $barWidth, height: $barHeight, color: $color);
            $parts[] = $this->textEl(
                x: $marginLeft + $barWidth + 6,
                y: $barY + ($barHeight / 2) + 3,
                text: $this->formatValue(value: $value, format: $valueFmt),
                anchor: 'start',
                size: 9,
                color: '#333333'
            );
        }//end for

        $parts[] = '</svg>';

        return implode('', $parts);

    }//end renderHorizontalBar()

    /**
     * Render a line chart (one polyline per series, gaps for skipped values).
     *
     * @param array $normalized Normalized `{labels, series}` data.
     * @param array $palette    Ordered hex colors.
     * @param int   $width      Canvas width.
     * @param int   $height     Canvas height.
     * @param array $options    Rendering options.
     *
     * @return string SVG markup.
     */
    private function renderLine(array $normalized, array $palette, int $width, int $height, array $options): string
    {
        $labels     = $normalized['labels'];
        $series     = $normalized['series'];
        $title      = (string) ($options['title'] ?? '');
        $valueFmt   = (string) ($options['valueFormat'] ?? 'integer');
        $numLabels  = count($labels);
        $numSeries  = count($series);
        $showLegend = (bool) ($options['showLegend'] ?? ($numSeries > 1));

        $marginTop = 14;
        if ($title !== '') {
            $marginTop = 34;
        }

        $marginBottom = 40;
        if ($showLegend === true) {
            $marginBottom += 22;
        }

        $marginLeft  = 46;
        $marginRight = 16;

        $plotWidth  = max(1, $width - $marginLeft - $marginRight);
        $plotHeight = max(1, $height - $marginTop - $marginBottom);

        $max     = $this->seriesMax(series: $series);
        $niceMax = $this->niceCeiling(value: $max);
        if ($niceMax <= 0.0) {
            $niceMax = 1.0;
        }

        $parts   = [];
        $parts[] = $this->svgOpenTag(width: $width, height: $height);

        if ($title !== '') {
            $parts[] = $this->textEl(x: $width / 2, y: 18, text: $title, anchor: 'middle', size: 13, weight: 'bold');
        }

        $tickCount = 4;
        for ($t = 0; $t <= $tickCount; $t++) {
            $value      = ($niceMax / $tickCount) * $t;
            $y          = $marginTop + $plotHeight - (($value / $niceMax) * $plotHeight);
            $valueLabel = $this->formatValue(value: $value, format: $valueFmt);
            $parts[]    = $this->lineEl(x1: $marginLeft, y1: $y, x2: $marginLeft + $plotWidth, y2: $y, color: '#E2E2E2', width: 1);
            $parts[]    = $this->textEl(x: $marginLeft - 6, y: $y + 3, text: $valueLabel, anchor: 'end', size: 9, color: '#666666');
        }//end for

        $baselineY = $marginTop + $plotHeight;
        $parts[]   = $this->lineEl(x1: $marginLeft, y1: $baselineY, x2: $marginLeft + $plotWidth, y2: $baselineY, color: '#999999', width: 1);

        $lineSteps = 1;
        if (($numLabels - 1) > 0) {
            $lineSteps = $numLabels - 1;
        }

        $stepX = $plotWidth / max(1, $lineSteps);

        for ($s = 0; $s < $numSeries; $s++) {
            $color   = $palette[$s % count($palette)];
            $points  = [];
            $segment = [];

            for ($i = 0; $i < $numLabels; $i++) {
                $value = $series[$s]['values'][$i] ?? null;
                if ($value === null) {
                    if ($segment !== []) {
                        $parts[] = $this->polylineEl(points: $segment, color: $color);
                        $segment = [];
                    }

                    continue;
                }

                $x         = $marginLeft + ($i * $stepX);
                $y         = $baselineY - (($value / $niceMax) * $plotHeight);
                $segment[] = [$x, $y];
                $points[]  = [$x, $y, $value];
            }

            if ($segment !== []) {
                $parts[] = $this->polylineEl(points: $segment, color: $color);
            }

            foreach ($points as [$px, $py]) {
                $parts[] = '<circle cx="'.$this->num(value: $px).'" cy="'.$this->num(value: $py).'" r="2.5" fill="'.$color.'"/>';
            }
        }//end for

        if ($numLabels <= self::MAX_LABELLED_ENTRIES) {
            for ($i = 0; $i < $numLabels; $i++) {
                $x       = $marginLeft + ($i * $stepX);
                $parts[] = $this->textEl(
                    x: $x,
                    y: $baselineY + 16,
                    text: $this->truncate(text: $labels[$i], max: 14),
                    anchor: 'middle',
                    size: 9,
                    color: '#333333'
                );
            }
        }

        if ($showLegend === true) {
            $parts[] = $this->renderLegend(series: $series, palette: $palette, x: $marginLeft, y: $height - 12);
        }

        $parts[] = '</svg>';

        return implode('', $parts);

    }//end renderLine()

    /**
     * Render a pie (or donut) chart from the first series.
     *
     * @param array $normalized Normalized `{labels, series}` data.
     * @param array $palette    Ordered hex colors.
     * @param int   $width      Canvas width.
     * @param int   $height     Canvas height.
     * @param array $options    Rendering options.
     *
     * @return string SVG markup.
     */
    private function renderPie(array $normalized, array $palette, int $width, int $height, array $options): string
    {
        $labels     = $normalized['labels'];
        $values     = $normalized['series'][0]['values'];
        $title      = (string) ($options['title'] ?? '');
        $donut      = (bool) ($options['donut'] ?? false);
        $showLegend = (bool) ($options['showLegend'] ?? true);

        $slices = [];
        $total  = 0.0;
        foreach ($values as $i => $value) {
            if ($value === null || $value <= 0.0) {
                continue;
            }

            $slices[] = ['label' => $labels[$i], 'value' => $value];
            $total   += $value;
        }

        if ($total <= 0.0 || $slices === []) {
            return $this->renderPlaceholder(width: $width, height: $height, message: 'chart error: no data');
        }

        $marginTop = 14;
        if ($title !== '') {
            $marginTop = 34;
        }

        $legendWidth = 0;
        if ($showLegend === true) {
            $legendWidth = min(220, (int) ($width * 0.4));
        }

        $plotWidth  = $width - $legendWidth;
        $plotHeight = $height - $marginTop - 14;

        $radius = max(10.0, (min($plotWidth, $plotHeight) / 2) - 8);
        $cx     = $plotWidth / 2;
        $cy     = $marginTop + ($plotHeight / 2);

        $innerRadius = 0.0;
        if ($donut === true) {
            $innerRadius = $radius * 0.55;
        }

        $parts   = [];
        $parts[] = $this->svgOpenTag(width: $width, height: $height);

        if ($title !== '') {
            $parts[] = $this->textEl(x: $width / 2, y: 18, text: $title, anchor: 'middle', size: 13, weight: 'bold');
        }

        $parts[] = $this->renderPieSlices(slices: $slices, total: $total, palette: $palette, cx: $cx, cy: $cy, radius: $radius);

        if ($innerRadius > 0.0) {
            $innerCx = $this->num(value: $cx);
            $innerCy = $this->num(value: $cy);
            $innerR  = $this->num(value: $innerRadius);
            $parts[] = '<circle cx="'.$innerCx.'" cy="'.$innerCy.'" r="'.$innerR.'" fill="#FFFFFF"/>';
        }

        if ($showLegend === true) {
            $legendX = $plotWidth + 12;
            $legendY = $marginTop + 6;
            $shown   = 0;
            foreach ($slices as $index => $slice) {
                if ($shown >= self::MAX_LABELLED_ENTRIES) {
                    break;
                }

                $color   = $palette[(int) $index % count($palette)];
                $percent = round(($slice['value'] / $total) * 100);
                $rowY    = $legendY + ($shown * 16);

                $parts[] = $this->rectEl(x: $legendX, y: $rowY - 8, width: 10, height: 10, color: $color);
                $parts[] = $this->textEl(
                    x: $legendX + 14,
                    y: $rowY + 1,
                    text: $this->truncate(text: $slice['label'], max: 20).' ('.$percent.'%)',
                    anchor: 'start',
                    size: 9,
                    color: '#333333'
                );
                $shown++;
            }
        }//end if

        $parts[] = '</svg>';

        return implode('', $parts);

    }//end renderPie()

    /**
     * Render the pie/donut slice shapes: a single non-zero slice as a full
     * `<circle>` (the 360° sweep arc-math edge case), otherwise one `<path>`
     * arc sector per slice.
     *
     * @param array $slices  `[{label, value}, ...]` — already filtered to
     *                       non-zero, positive values.
     * @param float $total   Sum of all slice values.
     * @param array $palette Ordered hex colors.
     * @param float $cx      Center x.
     * @param float $cy      Center y.
     * @param float $radius  Slice radius.
     *
     * @return string SVG markup fragment.
     */
    private function renderPieSlices(array $slices, float $total, array $palette, float $cx, float $cy, float $radius): string
    {
        if (count($slices) === 1) {
            $circleRadius = $this->num(value: $radius);
            $circleCx     = $this->num(value: $cx);
            $circleCy     = $this->num(value: $cy);

            return '<circle cx="'.$circleCx.'" cy="'.$circleCy.'" r="'.$circleRadius.'" fill="'.$palette[0].'"/>';
        }

        $parts = [];
        $angle = -90.0;
        foreach ($slices as $index => $slice) {
            $sweep    = ($slice['value'] / $total) * 360.0;
            $endAngle = $angle + $sweep;
            $color    = $palette[(int) $index % count($palette)];
            $parts[]  = $this->pieSliceEl(cx: $cx, cy: $cy, radius: $radius, startAngle: $angle, endAngle: $endAngle, color: $color);
            $angle    = $endAngle;
        }

        return implode('', $parts);

    }//end renderPieSlices()

    /**
     * Render a legend row of color-swatch + series name entries.
     *
     * @param array $series  Normalized series list.
     * @param array $palette Ordered hex colors.
     * @param float $x       Left x position.
     * @param float $y       Baseline y position.
     *
     * @return string SVG markup fragment.
     */
    private function renderLegend(array $series, array $palette, float $x, float $y): string
    {
        $parts   = [];
        $cursorX = $x;
        foreach ($series as $index => $oneSeries) {
            if ($index >= self::MAX_LABELLED_ENTRIES) {
                break;
            }

            $color = $palette[(int) $index % count($palette)];
            $name  = $this->truncate(text: $oneSeries['name'], max: 18);

            $parts[]  = $this->rectEl(x: $cursorX, y: $y - 9, width: 9, height: 9, color: $color);
            $parts[]  = $this->textEl(x: $cursorX + 13, y: $y, text: $name, anchor: 'start', size: 9, color: '#333333');
            $cursorX += 13 + (strlen($name) * 5.5) + 14;
        }

        return implode('', $parts);

    }//end renderLegend()

    /**
     * Render an accessible placeholder box with a centered message — used
     * for chart errors and empty-data states. Always valid SVG so callers
     * can embed the result unconditionally.
     *
     * @param int    $width   Canvas width.
     * @param int    $height  Canvas height.
     * @param string $message Message to display (escaped).
     *
     * @return string SVG markup.
     */
    private function renderPlaceholder(int $width, int $height, string $message): string
    {
        $this->lastWarning = $message;

        $parts   = [];
        $parts[] = $this->svgOpenTag(width: $width, height: $height);
        $parts[] = $this->rectEl(x: 1, y: 1, width: $width - 2, height: $height - 2, color: '#F5F5F5');
        $parts[] = '<rect x="1" y="1" width="'.($width - 2).'" height="'.($height - 2).'" fill="none" stroke="#CCCCCC" stroke-width="1"/>';
        $parts[] = $this->textEl(x: $width / 2, y: $height / 2, text: $message, anchor: 'middle', size: 11, color: '#888888');
        $parts[] = '</svg>';

        return implode('', $parts);

    }//end renderPlaceholder()

    /**
     * Compute the maximum value across all series (ignoring skipped points).
     *
     * @param array $series Normalized series list.
     *
     * @return float Maximum value, or 0.0 when no numeric value is present.
     */
    private function seriesMax(array $series): float
    {
        $max = 0.0;
        foreach ($series as $oneSeries) {
            foreach ($oneSeries['values'] as $value) {
                if ($value !== null && $value > $max) {
                    $max = $value;
                }
            }
        }

        return $max;

    }//end seriesMax()

    /**
     * Round a value up to a "nice" axis maximum (1/2/5/10 × 10^n).
     *
     * @param float $value Raw maximum value.
     *
     * @return float Nice ceiling value.
     */
    private function niceCeiling(float $value): float
    {
        if ($value <= 0.0) {
            return 0.0;
        }

        $magnitude  = 10 ** floor(log10($value));
        $normalized = $value / $magnitude;

        if ($normalized <= 1.0) {
            return 1.0 * $magnitude;
        }

        if ($normalized <= 2.0) {
            return 2.0 * $magnitude;
        }

        if ($normalized <= 5.0) {
            return 5.0 * $magnitude;
        }

        return 10.0 * $magnitude;

    }//end niceCeiling()

    /**
     * Format a numeric value for on-chart labels.
     *
     * @param float  $value  The value to format.
     * @param string $format 'integer' (default), 'decimal:N', 'currency', or 'percent'.
     *
     * @return string Formatted value.
     */
    private function formatValue(float $value, string $format): string
    {
        if ($format === 'percent') {
            return sprintf('%.0f%%', $value * 100);
        }

        if (str_starts_with($format, 'decimal:') === true) {
            $precision = (int) substr($format, 8);
            $precision = max(0, min($precision, 6));
            return sprintf('%.'.$precision.'f', $value);
        }

        if ($format === 'currency') {
            return '€ '.$this->formatThousands(value: $value, decimals: 2);
        }

        // Default: integer.
        return $this->formatThousands(value: round($value), decimals: 0);

    }//end formatValue()

    /**
     * Format a number with NL-style thousands separators, independent of
     * environment locale (deterministic across containers).
     *
     * @param float $value    Value to format.
     * @param int   $decimals Number of decimal places.
     *
     * @return string Formatted number, e.g. '1.234,56'.
     */
    private function formatThousands(float $value, int $decimals): string
    {
        $fixed    = sprintf('%.'.$decimals.'f', $value);
        $negative = str_starts_with($fixed, '-');
        if ($negative === true) {
            $fixed = substr($fixed, 1);
        }

        $parts       = explode('.', $fixed);
        $wholePart   = $parts[0];
        $decimalPart = $parts[1] ?? '';

        $grouped = '';
        $len     = strlen($wholePart);
        for ($i = 0; $i < $len; $i++) {
            if ($i > 0 && ($len - $i) % 3 === 0) {
                $grouped .= '.';
            }

            $grouped .= $wholePart[$i];
        }

        $result = $grouped;
        if ($decimals > 0) {
            $result .= ','.$decimalPart;
        }

        $sign = '';
        if ($negative === true) {
            $sign = '-';
        }

        return $sign.$result;

    }//end formatThousands()

    /**
     * Truncate text to a maximum character length with an ellipsis marker.
     *
     * @param string $text Source text.
     * @param int    $max  Maximum character length.
     *
     * @return string Truncated text.
     */
    private function truncate(string $text, int $max): string
    {
        if (strlen($text) <= $max) {
            return $text;
        }

        return substr($text, 0, max(1, $max - 1)).'…';

    }//end truncate()

    /**
     * Read an integer option with a default and a sane positive floor.
     *
     * @param array  $options Options array.
     * @param string $key     Option key.
     * @param int    $default Default value.
     *
     * @return int Resolved value (always >= 1).
     */
    private function intOption(array $options, string $key, int $default): int
    {
        $value = $options[$key] ?? $default;
        if (is_numeric($value) === false) {
            return $default;
        }

        return max(1, (int) $value);

    }//end intOption()

    /**
     * Build the opening `<svg>` tag with a white background rect.
     *
     * @param int $width  Canvas width.
     * @param int $height Canvas height.
     *
     * @return string SVG open tag + background rect.
     */
    private function svgOpenTag(int $width, int $height): string
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="'.$width.'" height="'.$height.'" '
            .'viewBox="0 0 '.$width.' '.$height.'">'
            .'<rect x="0" y="0" width="'.$width.'" height="'.$height.'" fill="#FFFFFF"/>';

    }//end svgOpenTag()

    /**
     * Build an escaped `<text>` element.
     *
     * @param float  $x      X position.
     * @param float  $y      Y position (baseline).
     * @param string $text   Text content (escaped).
     * @param string $anchor 'start'|'middle'|'end'.
     * @param int    $size   Font size in user units.
     * @param string $color  Fill color.
     * @param string $weight Font weight ('normal'|'bold').
     *
     * @return string SVG markup.
     */
    private function textEl(
        float $x,
        float $y,
        string $text,
        string $anchor='start',
        int $size=10,
        string $color='#000000',
        string $weight='normal'
    ): string {
        return '<text x="'.$this->num(value: $x).'" y="'.$this->num(value: $y).'" '
            .'font-family="sans-serif" font-size="'.$size.'" font-weight="'.$weight.'" '
            .'fill="'.$color.'" text-anchor="'.$anchor.'">'
            .$this->escapeText(text: $text)
            .'</text>';

    }//end textEl()

    /**
     * Build a `<rect>` element.
     *
     * @param float  $x      X position.
     * @param float  $y      Y position.
     * @param float  $width  Rectangle width.
     * @param float  $height Rectangle height.
     * @param string $color  Fill color.
     *
     * @return string SVG markup.
     */
    private function rectEl(float $x, float $y, float $width, float $height, string $color): string
    {
        return '<rect x="'.$this->num(value: $x).'" y="'.$this->num(value: $y).'" '
            .'width="'.$this->num(value: $width).'" height="'.$this->num(value: $height).'" '
            .'fill="'.$color.'"/>';

    }//end rectEl()

    /**
     * Build a `<line>` element.
     *
     * @param float  $x1    Start x.
     * @param float  $y1    Start y.
     * @param float  $x2    End x.
     * @param float  $y2    End y.
     * @param string $color Stroke color.
     * @param float  $width Stroke width.
     *
     * @return string SVG markup.
     */
    private function lineEl(float $x1, float $y1, float $x2, float $y2, string $color, float $width): string
    {
        return '<line x1="'.$this->num(value: $x1).'" y1="'.$this->num(value: $y1).'" '
            .'x2="'.$this->num(value: $x2).'" y2="'.$this->num(value: $y2).'" '
            .'stroke="'.$color.'" stroke-width="'.$this->num(value: $width).'"/>';

    }//end lineEl()

    /**
     * Build a `<polyline>` element from an ordered list of `[x, y]` points.
     *
     * @param array  $points Ordered `[[x, y], ...]` points.
     * @param string $color  Stroke color.
     *
     * @return string SVG markup.
     */
    private function polylineEl(array $points, string $color): string
    {
        $pairs = [];
        foreach ($points as [$x, $y]) {
            $pairs[] = $this->num(value: $x).','.$this->num(value: $y);
        }

        return '<polyline points="'.implode(' ', $pairs).'" fill="none" stroke="'.$color.'" stroke-width="2"/>';

    }//end polylineEl()

    /**
     * Build a pie slice as an SVG `<path>` arc sector.
     *
     * @param float  $cx         Center x.
     * @param float  $cy         Center y.
     * @param float  $radius     Slice radius.
     * @param float  $startAngle Start angle in degrees (0 = 3 o'clock, -90 = 12 o'clock).
     * @param float  $endAngle   End angle in degrees.
     * @param string $color      Fill color.
     *
     * @return string SVG markup.
     */
    private function pieSliceEl(float $cx, float $cy, float $radius, float $startAngle, float $endAngle, string $color): string
    {
        $startRad = deg2rad($startAngle);
        $endRad   = deg2rad($endAngle);

        $x1 = $cx + ($radius * cos($startRad));
        $y1 = $cy + ($radius * sin($startRad));
        $x2 = $cx + ($radius * cos($endRad));
        $y2 = $cy + ($radius * sin($endRad));

        $largeArc = 0;
        if (($endAngle - $startAngle) > 180.0) {
            $largeArc = 1;
        }

        $path = 'M '.$this->num(value: $cx).' '.$this->num(value: $cy)
            .' L '.$this->num(value: $x1).' '.$this->num(value: $y1)
            .' A '.$this->num(value: $radius).' '.$this->num(value: $radius).' 0 '.$largeArc.' 1 '
            .$this->num(value: $x2).' '.$this->num(value: $y2).' Z';

        return '<path d="'.$path.'" fill="'.$color.'"/>';

    }//end pieSliceEl()

    /**
     * Format a coordinate/dimension number deterministically (2 decimal
     * places, no locale dependency, trailing zeros trimmed).
     *
     * @param float $value The number to format.
     *
     * @return string Formatted number.
     */
    private function num(float $value): string
    {
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
     */
    private function escapeText(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    }//end escapeText()

    /**
     * Validate a string is a `#RRGGBB` hex color.
     *
     * @param string $color Candidate color string.
     *
     * @return bool True when valid.
     */
    private function isValidHexColor(string $color): bool
    {
        return preg_match('/^#[0-9A-Fa-f]{6}$/', $color) === 1;

    }//end isValidHexColor()

    /**
     * Convert a `#RRGGBB` hex color to an `[r, g, b]` (0-255) triple.
     *
     * @param string $hex Hex color, validated by the caller.
     *
     * @return array{0: int, 1: int, 2: int}
     */
    private function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];

    }//end hexToRgb()

    /**
     * Convert an RGB (0-255) triple to `[hue(0-360), saturation(0-1), lightness(0-1)]`.
     *
     * @param int $r Red channel.
     * @param int $g Green channel.
     * @param int $b Blue channel.
     *
     * @return array{0: float, 1: float, 2: float}
     */
    private function rgbToHsl(int $r, int $g, int $b): array
    {
        $rf = $r / 255;
        $gf = $g / 255;
        $bf = $b / 255;

        $max = max($rf, $gf, $bf);
        $min = min($rf, $gf, $bf);
        $l   = ($max + $min) / 2;

        if ($max === $min) {
            return [0.0, 0.0, $l];
        }

        $d = $max - $min;
        $s = $d / ($max + $min);
        if ($l > 0.5) {
            $s = $d / (2 - $max - $min);
        }

        switch ($max) {
            case $rf:
                $h = fmod((($gf - $bf) / $d), 6);
                break;
            case $gf:
                $h = (($bf - $rf) / $d) + 2;
                break;
            default:
                $h = (($rf - $gf) / $d) + 4;
                break;
        }

        $h *= 60;
        if ($h < 0) {
            $h += 360;
        }

        return [$h, $s, $l];

    }//end rgbToHsl()

    /**
     * Convert `[hue(0-360), saturation(0-1), lightness(0-1)]` to a `#RRGGBB` hex color.
     *
     * @param float $h Hue in degrees.
     * @param float $s Saturation (0-1).
     * @param float $l Lightness (0-1).
     *
     * @return string Hex color.
     */
    private function hslToHex(float $h, float $s, float $l): string
    {
        $c = (1 - abs((2 * $l) - 1)) * $s;
        $x = $c * (1 - abs(fmod(($h / 60), 2) - 1));
        $m = $l - ($c / 2);

        [$r, $g, $b] = $this->hueSextant(h: $h, c: $c, x: $x);

        $rHex = str_pad(dechex((int) round(($r + $m) * 255)), 2, '0', STR_PAD_LEFT);
        $gHex = str_pad(dechex((int) round(($g + $m) * 255)), 2, '0', STR_PAD_LEFT);
        $bHex = str_pad(dechex((int) round(($b + $m) * 255)), 2, '0', STR_PAD_LEFT);

        return '#'.strtoupper($rHex.$gHex.$bHex);

    }//end hslToHex()

    /**
     * Map a hue (0-360°) to its `[r, g, b]` sextant using the chroma/second
     * largest component (`c`/`x`) already computed by {@see hslToHex()}.
     *
     * @param float $h Hue in degrees.
     * @param float $c Chroma.
     * @param float $x Second-largest color component.
     *
     * @return array{0: float, 1: float, 2: float}
     */
    private function hueSextant(float $h, float $c, float $x): array
    {
        if ($h < 60) {
            return [$c, $x, 0];
        }

        if ($h < 120) {
            return [$x, $c, 0];
        }

        if ($h < 180) {
            return [0, $c, $x];
        }

        if ($h < 240) {
            return [0, $x, $c];
        }

        if ($h < 300) {
            return [$x, 0, $c];
        }

        return [$c, 0, $x];

    }//end hueSextant()
}//end class
