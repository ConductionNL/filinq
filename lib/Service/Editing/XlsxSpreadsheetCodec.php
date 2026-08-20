<?php

/**
 * DocuDesk XlsxSpreadsheetCodec
 *
 * Cell access for OOXML spreadsheets (`.xlsx`).
 *
 * ⚠️ A string cell does not hold its text: `t="s"` means the value is an
 * INDEX into `xl/sharedStrings.xml`. Reporting the index would hand a caller a
 * number where the sheet shows a word, so reads resolve it. Writes use an
 * INLINE string instead of appending to the shared table, because that table is
 * indexed by every other sheet in the workbook.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service\Editing
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 *
 * @link https://docudesk.app
 *
 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service\Editing;

use RuntimeException;

/**
 * Cell access for OOXML spreadsheets.
 */
class XlsxSpreadsheetCodec implements SpreadsheetFamilyCodec {

	/**
	 * Constructor.
	 *
	 * @param PackagePartIo $io Package reader, for the shared string table.
	 */
	public function __construct(private readonly PackagePartIo $io) {
	}//end __construct()

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
		return (strtolower($extension) === 'xlsx');
	}//end supports()

	/**
	 * The package part carrying cell values.
	 *
	 * @return string The part path.
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.2
	 */
	public function valuePart(): string {
		return 'xl/worksheets/sheet1.xml';
	}//end valuePart()

	/**
	 * Read XLSX cells.
	 *
	 * @param string $xml          The worksheet XML.
	 * @param string $packageBytes The package, for the shared string table.
	 *
	 * @return array<int, array{cell: string, value: string, formula: string|null}> The cells.
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.2
	 */
	public function readCells(string $xml, string $packageBytes): array {
		$shared = $this->sharedStrings(packageBytes: $packageBytes);
		$cells = [];

		preg_match_all('/<c\b([^>]*?)(?:\/>|>(.*?)<\/c>)/s', $xml, $found, PREG_SET_ORDER);
		foreach ($found as $match) {
			$attributes = $match[1];
			$body = ($match[2] ?? '');

			if (preg_match('/\br="([A-Z]+\d+)"/i', $attributes, $ref) !== 1) {
				continue;
			}

			$formula = null;
			if (preg_match('/<f[^>]*>(.*?)<\/f>/s', $body, $f) === 1) {
				$formula = html_entity_decode($f[1], ENT_QUOTES | ENT_HTML5);
			}

			$value = $this->cellValue(attributes: $attributes, body: $body, shared: $shared);

			if ($value === '' && $formula === null) {
				continue;
			}

			$cells[] = [
				'cell' => 'Sheet1!' . strtoupper($ref[1]),
				'value' => $value,
				'formula' => $formula,
			];
		}//end foreach

		return $cells;
	}//end readCells()

	/**
	 * The visible value of a cell.
	 *
	 * ⚠️ `t="s"` means the value is an INDEX into the shared string table, not
	 * the text. Reporting the index would hand a caller a number where the
	 * sheet plainly shows a word.
	 *
	 * @param string             $attributes The cell's attributes.
	 * @param string             $body       The cell's inner markup.
	 * @param array<int, string> $shared     The shared string table.
	 *
	 * @return string The value.
	 */
	private function cellValue(string $attributes, string $body, array $shared): string {
		if (preg_match('/<is>.*?<t[^>]*>(.*?)<\\/t>.*?<\\/is>/s', $body, $inline) === 1) {
			return html_entity_decode($inline[1], ENT_QUOTES | ENT_HTML5);
		}

		if (preg_match('/<v[^>]*>(.*?)<\\/v>/s', $body, $match) !== 1) {
			return '';
		}

		$value = html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5);
		if (preg_match('/\\bt="s"/', $attributes) === 1 && isset($shared[(int)$value]) === true) {
			return $shared[(int)$value];
		}

		return $value;
	}//end cellValue()

	/**
	 * Write a literal into an XLSX cell, as an inline string.
	 *
	 * Inline rather than shared: appending to the shared table renumbers
	 * nothing but grows a structure every other sheet also indexes into, and an
	 * inline string is valid OOXML that no other cell can be affected by.
	 *
	 * @param string $xml   The worksheet XML.
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
		$ref = $this->bareRef(cell: $cell);
		$escaped = htmlspecialchars($value, ENT_QUOTES | ENT_XML1);
		$replacement = sprintf('<c r="%s" t="inlineStr"><is><t>%s</t></is></c>', $ref, $escaped);

		$pattern = '/<c\b[^>]*\br="' . preg_quote($ref, '/') . '"[^>]*(?:\/>|>.*?<\/c>)/si';
		if (preg_match($pattern, $xml) !== 1) {
			throw new RuntimeException(
				sprintf('Cell %s does not exist in this sheet. Only populated cells can be written.', $cell)
			);
		}

		return preg_replace($pattern, $replacement, $xml, 1);
	}//end writeCell()

	/**
	 * The shared string table, indexed as the worksheet references it.
	 *
	 * @param string $packageBytes The package.
	 *
	 * @return array<int, string> The strings.
	 */
	private function sharedStrings(string $packageBytes): array {
		try {
			$xml = $this->io->readPart(packageBytes: $packageBytes, part: 'xl/sharedStrings.xml');
		} catch (RuntimeException) {
			return [];
		}

		$strings = [];
		preg_match_all('/<si\b[^>]*>(.*?)<\/si>/s', $xml, $items, PREG_SET_ORDER);
		foreach ($items as $index => $item) {
			preg_match_all('/<t[^>]*>(.*?)<\/t>/s', $item[1], $texts);
			$strings[$index] = html_entity_decode(implode('', $texts[1]), ENT_QUOTES | ENT_HTML5);
		}

		return $strings;
	}//end sharedStrings()

	/**
	 * The cell part of a `Sheet!Cell` address.
	 *
	 * @param string $cell The address.
	 *
	 * @return string The bare cell reference.
	 */
	private function bareRef(string $cell): string {
		$parts = explode('!', $cell);

		return end($parts);
	}//end bareRef()
}//end class
