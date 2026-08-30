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
 * Renders a collection + column definition to a styled HTML table.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Charts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/template-charts/tasks.md#task-1.2
 */
class TableHtmlRenderer {

	/**
	 * Default cap on the number of rows rendered (guardrail against
	 * unbounded collections blowing up document size).
	 *
	 * @var int
	 */
	private const DEFAULT_MAX_ROWS = 500;

	/**
	 * Row and column shape normalizer.
	 *
	 * @var TableDataNormalizer
	 */
	private readonly TableDataNormalizer $normalizer;

	/**
	 * Cell value formatter.
	 *
	 * @var TableCellFormatter
	 */
	private readonly TableCellFormatter $cells;

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
		$this->normalizer = new TableDataNormalizer();
		$this->cells = new TableCellFormatter();

	}//end __construct()

	/**
	 * Render a collection as an HTML table.
	 *
	 * Never throws: malformed columns are skipped individually, and an
	 * empty/invalid collection renders a localised empty-state row.
	 *
	 * @param mixed $collection Iterable collection of associative rows
	 *                          (array of arrays/array-accessible objects).
	 * @param array $columns `[{key, label, align?, format?}, ...]`.
	 *                       When empty, columns are derived from the
	 *                       keys of the first row.
	 * @param array $options Rendering options: maxRows (int), emptyText
	 *                       (string override for the empty-state row).
	 *
	 * @return string HTML `<table>` markup.
	 *
	 * @spec openspec/changes/template-charts/specs/template-charts/spec.md#REQ-DDTCH-004
	 */
	public function render($collection, array $columns, array $options = []): string {
		$rows = $this->normalizer->normalizeCollection(collection: $collection);

		if ($columns === [] && $rows !== []) {
			$columns = $this->normalizer->deriveColumns(firstRow: $rows[0]);
		}

		$columns = $this->normalizer->normalizeColumns(columns: $columns);

		$maxRows = self::DEFAULT_MAX_ROWS;
		if (is_numeric($options['maxRows'] ?? null) === true) {
			$maxRows = max(1, (int)$options['maxRows']);
		}

		$truncated = count($rows) > $maxRows;
		if ($truncated === true) {
			$rows = array_slice($rows, 0, $maxRows);
		}

		$emptyText = (string)($options['emptyText'] ?? 'Geen gegevens beschikbaar');

		$html = '<table style="width:100%;border-collapse:collapse;font-family:sans-serif;font-size:10pt;">';
		$html .= $this->renderHead(columns: $columns);
		$html .= '<tbody>';
		$html .= $this->renderBody(rows: $rows, columns: $columns, emptyText: $emptyText);
		$html .= '</tbody></table>';

		if ($truncated === true) {
			$html .= '<p style="font-size:8pt;color:#888888;">'
				. $this->escape(value: 'Table truncated to ' . $maxRows . ' rows.')
				. '</p>';
		}

		return $html;
	}//end render()

	/**
	 * Render the `<tbody>` row content: the empty-state row, or one `<tr>`
	 * per data row.
	 *
	 * @param array $rows Normalized rows (already row-limited).
	 * @param array $columns Normalized columns.
	 * @param string $emptyText Empty-state message.
	 *
	 * @return string HTML markup fragment.
	 */
	private function renderBody(array $rows, array $columns, string $emptyText): string {
		if ($rows === [] || $columns === []) {
			$colspan = max(1, count($columns));

			return '<tr><td colspan="' . $colspan . '" style="padding:6px 8px;border:1px solid #DDDDDD;color:#666666;text-align:center;">'
				. $this->escape(value: $emptyText) . '</td></tr>';
		}

		$html = '';
		foreach ($rows as $row) {
			$html .= $this->renderRow(row: $row, columns: $columns);
		}

		return $html;
	}//end renderBody()

	/**
	 * Render the `<thead>` row.
	 *
	 * @param array $columns Normalized columns.
	 *
	 * @return string HTML markup.
	 */
	private function renderHead(array $columns): string {
		$html = '<thead><tr>';
		foreach ($columns as $column) {
			$html .= '<th style="padding:6px 8px;border:1px solid #DDDDDD;background:#F5F5F5;'
				. 'text-align:' . $column['align'] . ';font-weight:bold;">'
				. $this->escape(value: $column['label']) . '</th>';
		}

		$html .= '</tr></thead>';

		return $html;
	}//end renderHead()

	/**
	 * Render a single `<tr>` for a data row.
	 *
	 * @param array $row A single normalized row.
	 * @param array $columns Normalized columns.
	 *
	 * @return string HTML markup.
	 */
	private function renderRow(array $row, array $columns): string {
		$html = '<tr>';
		foreach ($columns as $column) {
			$value = $row[$column['key']] ?? null;
			$formatted = $this->cells->format(value: $value, format: $column['format']);
			$html .= '<td style="padding:6px 8px;border:1px solid #DDDDDD;text-align:' . $column['align'] . ';">'
				. $this->escape(value: $formatted) . '</td>';
		}

		$html .= '</tr>';

		return $html;
	}//end renderRow()

	/**
	 * Escape a data-derived value for safe embedding as HTML text content.
	 *
	 * @param string $value Raw text.
	 *
	 * @return string Escaped text.
	 */
	private function escape(string $value): string {
		return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
	}//end escape()
}//end class
