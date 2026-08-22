<?php

/**
 * Filinq OdsSpreadsheetCodec
 *
 * Cell access for ODF spreadsheets (`.ods`).
 *
 * ⚠️ ODS compresses runs of identical columns with
 * `table:number-columns-repeated`, so ONE element can stand for many columns.
 * Ignoring the repeat count puts every cell after a gap at the wrong address —
 * silently, and for the whole row — and writing into such a run must SPLIT it
 * rather than rewrite every column it stood for.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Editing
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 *
 * @link https://filinq.app
 *
 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Editing;

use RuntimeException;

/**
 * Cell access for ODF spreadsheets.
 */
class OdsSpreadsheetCodec implements SpreadsheetFamilyCodec {

	/**
	 * Whether this codec handles an extension.
	 *
	 * @param string $extension The lower-case file extension.
	 *
	 * @return bool True when handled.
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.2
	 */
	public function supports(string $extension): bool {
		return (strtolower($extension) === 'ods');
	}//end supports()

	/**
	 * The package part carrying cell values.
	 *
	 * @return string The part path.
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.2
	 */
	public function valuePart(): string {
		return 'content.xml';
	}//end valuePart()

	/**
	 * Read ODS cells.
	 *
	 * @param string $xml The content.xml.
	 * @param string $packageBytes The whole package. Unused here — ODF keeps its
	 *                             cell text inline, so nothing else has to be
	 *                             opened. Accepted so the codec matches the
	 *                             interface OOXML needs, where the text lives in
	 *                             a separate shared-string part.
	 *
	 * @return array<int, array{cell: string, value: string, formula: string|null}> The cells.
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.2
	 */
	public function readCells(string $xml, string $packageBytes): array {
		$cells = [];

		preg_match_all('/<table:table\b[^>]*table:name="([^"]*)"(.*?)<\/table:table>/s', $xml, $tables, PREG_SET_ORDER);
		foreach ($tables as $table) {
			$sheet = $table[1];
			preg_match_all('/<table:table-row\b[^>]*>(.*?)<\/table:table-row>/s', $table[2], $rows, PREG_SET_ORDER);

			$rowNumber = 0;
			foreach ($rows as $row) {
				$rowNumber++;
				foreach ($this->rowCells(sheet: $sheet, rowNumber: $rowNumber, row: $row[1]) as $cell) {
					$cells[] = $cell;
				}
			}
		}//end foreach

		return $cells;
	}//end readCells()

	/**
	 * The populated cells of one row.
	 *
	 * ⚠️ One element can stand for MANY columns via
	 * `table:number-columns-repeated`. Ignoring the repeat count puts every
	 * cell after the first gap at the wrong address — silently, and for the
	 * whole row.
	 *
	 * @param string $sheet     The sheet name.
	 * @param int    $rowNumber The 1-based row number.
	 * @param string $row       The row's inner markup.
	 *
	 * @return array<int, array{cell: string, value: string, formula: string|null}> The cells.
	 */
	private function rowCells(string $sheet, int $rowNumber, string $row): array {
		$cells = [];
		$column = 0;
		preg_match_all('/<table:table-cell\\b([^>]*?)(?:\\/>|>(.*?)<\\/table:table-cell>)/s', $row, $found, PREG_SET_ORDER);

		foreach ($found as $match) {
			$attributes = $match[1];
			$repeat = $this->repeatCount(attributes: $attributes);
			$text = $this->cellText(body: ($match[2] ?? ''));
			$formula = $this->cellFormula(attributes: $attributes);

			for ($i = 0; $i < $repeat; $i++) {
				$column++;
				if ($text === '' && $formula === null) {
					continue;
				}

				$cells[] = [
					'cell' => $sheet . '!' . $this->columnName(index: $column) . $rowNumber,
					'value' => html_entity_decode($text, ENT_QUOTES | ENT_HTML5),
					'formula' => $formula,
				];
			}
		}//end foreach

		return $cells;
	}//end rowCells()

	/**
	 * How many columns one cell element stands for.
	 *
	 * @param string $attributes The cell's attributes.
	 *
	 * @return int The repeat count, at least 1.
	 */
	private function repeatCount(string $attributes): int {
		if (preg_match('/table:number-columns-repeated="(\\d+)"/', $attributes, $match) === 1) {
			return max(1, (int)$match[1]);
		}

		return 1;
	}//end repeatCount()

	/**
	 * The visible text of a cell.
	 *
	 * @param string $body The cell's inner markup.
	 *
	 * @return string The text.
	 */
	private function cellText(string $body): string {
		if (preg_match_all('/<text:p[^>]*>(.*?)<\\/text:p>/s', $body, $paragraphs) > 0) {
			return strip_tags(implode(' ', $paragraphs[1]));
		}

		return '';
	}//end cellText()

	/**
	 * The formula a cell carries, if any.
	 *
	 * @param string $attributes The cell's attributes.
	 *
	 * @return string|null The formula, or null.
	 */
	private function cellFormula(string $attributes): ?string {
		if (preg_match('/table:formula="([^"]*)"/', $attributes, $match) === 1) {
			return htmlspecialchars_decode($match[1], ENT_QUOTES);
		}

		return null;
	}//end cellFormula()

	/**
	 * Write a literal into an ODS cell.
	 *
	 * @param string $xml   The content.xml.
	 * @param string $cell  The address.
	 * @param string $value The literal.
	 *
	 * @return string The rewritten XML.
	 *
	 * @throws RuntimeException When the cell is absent.
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.2
	 */
	public function writeCell(string $xml, string $cell, string $value): string {
		$target = $this->normaliseAddress(address: $cell);
		$escaped = htmlspecialchars($value, ENT_QUOTES | ENT_XML1);
		$rewritten = null;

		$xml = preg_replace_callback(
			'/<table:table\b[^>]*table:name="([^"]*)"(.*?)<\/table:table>/s',
			function (array $table) use ($target, $escaped, &$rewritten): string {
				$sheet = $table[1];
				$rowNumber = 0;

				$body = preg_replace_callback(
					'/<table:table-row\b[^>]*>(.*?)<\/table:table-row>/s',
					function (array $row) use ($sheet, $target, $escaped, &$rowNumber, &$rewritten): string {
						$rowNumber++;
						$column = 0;

						$cells = preg_replace_callback(
							'/<table:table-cell\b([^>]*?)(?:\/>|>(.*?)<\/table:table-cell>)/s',
							function (array $match) use ($sheet, $target, $escaped, $rowNumber, &$column, &$rewritten): string {
								$attributes = $match[1];
								$repeat = 1;
								if (preg_match('/table:number-columns-repeated="(\d+)"/', $attributes, $r) === 1) {
									$repeat = max(1, (int)$r[1]);
								}

								$start = ($column + 1);
								$column += $repeat;

								for ($i = 0; $i < $repeat; $i++) {
									$address = $sheet . '!' . $this->columnName(index: ($start + $i)) . $rowNumber;
									if ($this->normaliseAddress(address: $address) !== $target) {
										continue;
									}

									// A repeated cell covering the target must be
									// SPLIT, or writing one column silently rewrites
									// every column it stood for.
									$rewritten = true;

									return $this->splitOdsRepeat(
										attributes: $attributes,
										repeat: $repeat,
										offset: $i,
										value: $escaped
									);
								}

								return $match[0];
							},
							$row[1]
						);

						return '<table:table-row>' . $cells . '</table:table-row>';
					},
					$table[2]
				);

				return '<table:table table:name="' . $sheet . '">' . $body . '</table:table>';
			},
			$xml
		);

		if ($rewritten === null) {
			throw new RuntimeException(
				sprintf('Cell %s does not exist in this sheet. Only populated cells can be written.', $cell)
			);
		}

		return $xml;
	}//end writeCell()

	/**
	 * Replace one column of a repeated ODS cell, preserving the others.
	 *
	 * @param string $attributes The original cell attributes.
	 * @param int    $repeat     How many columns the element stood for.
	 * @param int    $offset     Which of them is being written.
	 * @param string $value      The escaped literal.
	 *
	 * @return string The replacement markup.
	 */
	private function splitOdsRepeat(string $attributes, int $repeat, int $offset, string $value): string {
		$blank = preg_replace('/\s*table:formula="[^"]*"/', '', $attributes);
		$blank = preg_replace('/\s*table:number-columns-repeated="\d+"/', '', $blank);

		$written = sprintf(
			'<table:table-cell office:value-type="string"><text:p>%s</text:p></table:table-cell>',
			$value
		);

		$before = '';
		if ($offset > 0) {
			$before = $this->repeatedBlank(attributes: $blank, count: $offset);
		}

		$after = '';
		if (($repeat - $offset - 1) > 0) {
			$after = $this->repeatedBlank(attributes: $blank, count: ($repeat - $offset - 1));
		}

		return $before . $written . $after;
	}//end splitOdsRepeat()

	/**
	 * A run of untouched cells preserving the original attributes.
	 *
	 * @param string $attributes The cell attributes.
	 * @param int    $count      How many columns.
	 *
	 * @return string The markup.
	 */
	private function repeatedBlank(string $attributes, int $count): string {
		$repeat = '';
		if ($count > 1) {
			$repeat = sprintf(' table:number-columns-repeated="%d"', $count);
		}

		return sprintf('<table:table-cell%s%s/>', rtrim($attributes), $repeat);
	}//end repeatedBlank()

	/**
	 * The spreadsheet column name for a 1-based index.
	 *
	 * @param int $index The 1-based column index.
	 *
	 * @return string The column name (A, B, ..., AA).
	 */
	private function columnName(int $index): string {
		$name = '';
		while ($index > 0) {
			$index--;
			$name = chr(65 + ($index % 26)) . $name;
			$index = intdiv($index, 26);
		}

		return $name;
	}//end columnName()

	/**
	 * Normalise an address for comparison.
	 *
	 * ⚠️ Only the CELL REFERENCE is upper-cased. A sheet name is case-sensitive
	 * in every spreadsheet application, so upper-casing the whole address turns
	 * `Sales!b2` into `SALES!B2` and it matches no sheet at all — the write then
	 * fails on a document where the sheet plainly exists.
	 *
	 * @param string $address The `Sheet!Cell` address.
	 *
	 * @return string The normalised address.
	 */
	private function normaliseAddress(string $address): string {
		$position = strrpos($address, '!');
		if ($position === false) {
			return strtoupper($address);
		}

		return substr($address, 0, $position) . '!' . strtoupper(substr($address, ($position + 1)));
	}//end normaliseAddress()
}//end class
