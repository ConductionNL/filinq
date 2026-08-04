<?php
/**
 * Chart Legend Renderer
 *
 * Renders the horizontal color-swatch + series-name legend shared by the bar
 * and line chart renderers.
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
 * Renders the series legend of a cartesian chart.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service\Charts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/changes/template-charts/tasks.md#task-1.1
 */
class ChartLegendRenderer
{

    /**
     * SVG element builders.
     *
     * @var SvgPrimitives
     */
    private readonly SvgPrimitives $svg;

    /**
     * Label text formatter.
     *
     * @var ChartLabelFormatter
     */
    private readonly ChartLabelFormatter $labels;

    /**
     * Constructor.
     *
     * Collaborators are pure, stateless helpers with no I/O, so they are
     * composed here rather than injected — this keeps the public constructor
     * argument-free for both the DI container and direct instantiation.
     *
     * @return void
     */
    public function __construct()
    {
        $this->svg    = new SvgPrimitives();
        $this->labels = new ChartLabelFormatter();

    }//end __construct()

    /**
     * Render a legend row of color-swatch + series name entries.
     *
     * @param array $series     Normalized series list.
     * @param array $palette    Ordered hex colors.
     * @param float $x          Left x position.
     * @param float $y          Baseline y position.
     * @param int   $maxEntries Maximum number of entries rendered.
     *
     * @return string SVG markup fragment.
     *
     * @spec openspec/changes/template-charts/specs/template-charts/spec.md#REQ-DDTCH-001
     */
    public function render(array $series, array $palette, float $x, float $y, int $maxEntries): string
    {
        $parts   = [];
        $cursorX = $x;
        foreach ($series as $index => $oneSeries) {
            if ($index >= $maxEntries) {
                break;
            }

            $color = $palette[(int) $index % count($palette)];
            $name  = $this->labels->truncate(text: $oneSeries['name'], max: 18);

            $parts[]  = $this->svg->rectEl(x: $cursorX, y: $y - 9, width: 9, height: 9, color: $color);
            $parts[]  = $this->svg->textEl(x: $cursorX + 13, y: $y, text: $name, anchor: 'start', size: 9, color: '#333333');
            $cursorX += 13 + (strlen($name) * 5.5) + 14;
        }

        return implode('', $parts);

    }//end render()
}//end class
