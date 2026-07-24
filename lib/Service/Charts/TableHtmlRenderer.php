<?php
/**
 * Table HTML Renderer
 *
 * Renders an OpenRegister object collection into a consistently formatted
 * HTML table for use inside document templates (Twig `data_table()`
 * function). Column selection/order/labels/alignment/formatting are all
 * explicit — no environment-locale dependence — every cell is escaped, and
 * an empty collection renders a localised empty-state row rather than
 * nothing (never a silent gap).
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
 * Renders a collection + column definition to a styled HTML table.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service\Charts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/changes/template-charts/tasks.md#task-1.2
 */
class TableHtmlRenderer
{

    /**
     * Default cap on the number of rows rendered (guardrail against
     * unbounded collections blowing up document size).
     *
     * @var int
     */
    private const DEFAULT_MAX_ROWS = 500;

    /**
     * Render a collection as an HTML table.
     *
     * Never throws: malformed columns are skipped individually, and an
     * empty/invalid collection renders a localised empty-state row.
     *
     * @param mixed $collection Iterable collection of associative rows
     *                          (array of arrays/array-accessible objects).
     * @param array $columns    `[{key, label, align?, format?}, ...]`.
     *                          When empty, columns are derived from the
     *                          keys of the first row.
     * @param array $options    Rendering options: maxRows (int), emptyText
     *                          (string override for the empty-state row).
     *
     * @return string HTML `<table>` markup.
     *
     * @spec openspec/changes/template-charts/specs/template-charts/spec.md#REQ-DDTCH-004
     */
    public function render($collection, array $columns, array $options=[]): string
    {
        $rows = $this->normalizeCollection(collection: $collection);

        if ($columns === [] && $rows !== []) {
            $columns = $this->deriveColumns(firstRow: $rows[0]);
        }

        $columns = $this->normalizeColumns(columns: $columns);

        $maxRows = self::DEFAULT_MAX_ROWS;
        if (is_numeric($options['maxRows'] ?? null) === true) {
            $maxRows = max(1, (int) $options['maxRows']);
        }

        $truncated = count($rows) > $maxRows;
        if ($truncated === true) {
            $rows = array_slice($rows, 0, $maxRows);
        }

        $emptyText = (string) ($options['emptyText'] ?? 'Geen gegevens beschikbaar');

        $html  = '<table style="width:100%;border-collapse:collapse;font-family:sans-serif;font-size:10pt;">';
        $html .= $this->renderHead(columns: $columns);
        $html .= '<tbody>';
        $html .= $this->renderBody(rows: $rows, columns: $columns, emptyText: $emptyText);
        $html .= '</tbody></table>';

        if ($truncated === true) {
            $html .= '<p style="font-size:8pt;color:#888888;">'
                .$this->escape(value: 'Table truncated to '.$maxRows.' rows.')
                .'</p>';
        }

        return $html;

    }//end render()

    /**
     * Render the `<tbody>` row content: the empty-state row, or one `<tr>`
     * per data row.
     *
     * @param array  $rows      Normalized rows (already row-limited).
     * @param array  $columns   Normalized columns.
     * @param string $emptyText Empty-state message.
     *
     * @return string HTML markup fragment.
     */
    private function renderBody(array $rows, array $columns, string $emptyText): string
    {
        if ($rows === [] || $columns === []) {
            $colspan = max(1, count($columns));

            return '<tr><td colspan="'.$colspan.'" style="padding:6px 8px;border:1px solid #DDDDDD;color:#666666;text-align:center;">'
                .$this->escape(value: $emptyText).'</td></tr>';
        }

        $html = '';
        foreach ($rows as $row) {
            $html .= $this->renderRow(row: $row, columns: $columns);
        }

        return $html;

    }//end renderBody()

    /**
     * Normalize the incoming collection into a plain array of associative
     * rows, tolerating iterables and array-accessible objects.
     *
     * @param mixed $collection Raw collection value.
     *
     * @return array<int, array<string, mixed>>
     */
    private function normalizeCollection($collection): array
    {
        if (is_array($collection) === false && $collection instanceof \Traversable) {
            $collection = iterator_to_array($collection);
        }

        if (is_array($collection) === false) {
            return [];
        }

        $rows = [];
        foreach ($collection as $item) {
            if (is_array($item) === true) {
                $rows[] = $item;
                continue;
            }

            if ($item instanceof \ArrayAccess || is_object($item) === true) {
                $rows[] = (array) $item;
                continue;
            }

            // Scalars in the collection are not row-shaped: skip silently
            // (forgiving; the row simply does not appear).
        }

        return $rows;

    }//end normalizeCollection()

    /**
     * Derive a default column list from the keys of the first row when the
     * caller supplies no explicit `columns` definition.
     *
     * @param array $firstRow The first normalized row.
     *
     * @return array<int, array{key: string, label: string}>
     */
    private function deriveColumns(array $firstRow): array
    {
        $columns = [];
        foreach (array_keys($firstRow) as $key) {
            if (is_string($key) === false) {
                continue;
            }

            $columns[] = ['key' => $key, 'label' => $key];
        }

        return $columns;

    }//end deriveColumns()

    /**
     * Normalize/validate the column definitions, skipping malformed entries.
     *
     * @param array $columns Raw column definitions.
     *
     * @return array<int, array{key: string, label: string, align: string, format: string}>
     */
    private function normalizeColumns(array $columns): array
    {
        $validFormats = ['text', 'number', 'date', 'currency'];
        $validAligns  = ['left', 'right', 'center'];

        $normalized = [];
        foreach ($columns as $column) {
            if (is_array($column) === false || isset($column['key']) === false || is_string($column['key']) === false) {
                continue;
            }

            $format = (string) ($column['format'] ?? 'text');
            if (in_array(needle: $format, haystack: $validFormats, strict: true) === false) {
                $format = 'text';
            }

            $defaultAlign = 'left';
            if ($format === 'number' || $format === 'currency') {
                $defaultAlign = 'right';
            }

            $align = (string) ($column['align'] ?? $defaultAlign);
            if (in_array(needle: $align, haystack: $validAligns, strict: true) === false) {
                $align = 'left';
            }

            $normalized[] = [
                'key'    => $column['key'],
                'label'  => (string) ($column['label'] ?? $column['key']),
                'align'  => $align,
                'format' => $format,
            ];
        }//end foreach

        return $normalized;

    }//end normalizeColumns()

    /**
     * Render the `<thead>` row.
     *
     * @param array $columns Normalized columns.
     *
     * @return string HTML markup.
     */
    private function renderHead(array $columns): string
    {
        $html = '<thead><tr>';
        foreach ($columns as $column) {
            $html .= '<th style="padding:6px 8px;border:1px solid #DDDDDD;background:#F5F5F5;'
                .'text-align:'.$column['align'].';font-weight:bold;">'
                .$this->escape(value: $column['label']).'</th>';
        }

        $html .= '</tr></thead>';

        return $html;

    }//end renderHead()

    /**
     * Render a single `<tr>` for a data row.
     *
     * @param array $row     A single normalized row.
     * @param array $columns Normalized columns.
     *
     * @return string HTML markup.
     */
    private function renderRow(array $row, array $columns): string
    {
        $html = '<tr>';
        foreach ($columns as $column) {
            $value     = $row[$column['key']] ?? null;
            $formatted = $this->formatCell(value: $value, format: $column['format']);
            $html     .= '<td style="padding:6px 8px;border:1px solid #DDDDDD;text-align:'.$column['align'].';">'
                .$this->escape(value: $formatted).'</td>';
        }

        $html .= '</tr>';

        return $html;

    }//end renderRow()

    /**
     * Format a single cell value according to its column format. Missing or
     * malformed values are skipped/rendered blank rather than erroring.
     *
     * @param mixed  $value  Raw cell value.
     * @param string $format 'text'|'number'|'date'|'currency'.
     *
     * @return string Formatted (unescaped) text — escaping happens by the caller.
     */
    private function formatCell($value, string $format): string
    {
        if ($value === null) {
            return '';
        }

        if ($format === 'number' || $format === 'currency') {
            if (is_numeric($value) === false) {
                return (string) $value;
            }

            $numeric = (float) $value;

            $decimals = 2;
            if (floor($numeric) === $numeric) {
                $decimals = 0;
            }

            $formatted = $this->formatThousands(value: $numeric, decimals: $decimals);

            if ($format === 'currency') {
                return '€ '.$formatted;
            }

            return $formatted;
        }

        if ($format === 'date') {
            if (is_string($value) === false && is_numeric($value) === false) {
                return (string) $value;
            }

            $timestamp = strtotime((string) $value);
            if (is_numeric($value) === true) {
                $timestamp = (int) $value;
            }

            if ($timestamp === false) {
                return (string) $value;
            }

            return date('d-m-Y', $timestamp);
        }

        return (string) $value;

    }//end formatCell()

    /**
     * Format a number with NL-style thousands separators, independent of
     * environment locale.
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
     * Escape a data-derived value for safe embedding as HTML text content.
     *
     * @param string $value Raw text.
     *
     * @return string Escaped text.
     */
    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    }//end escape()
}//end class
