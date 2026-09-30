<?php

/**
 * Bulk Signing Recipient Parser
 *
 * Reads a CSV or XLSX recipient list into numbered rows. Cells are data: a
 * formula is kept as the text it is, never evaluated.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\BulkSigning
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md#requirement-bulk-send-validates-first-then-creates-isolated-ordinary-requests-req-ddbsf-002
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\BulkSigning;

use InvalidArgumentException;
use SimpleXMLElement;
use ZipArchive;

/**
 * Turns an uploaded recipient list into rows of userId, email and displayName.
 *
 * No spreadsheet library is vendored, so XLSX is read directly: the first
 * worksheet and the shared strings, through ZipArchive and SimpleXML. A
 * formula cell contributes its cached text; the formula itself is ignored.
 *
 * @category Service
 * @package  OCA\Filinq\Service\BulkSigning
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md#requirement-bulk-send-validates-first-then-creates-isolated-ordinary-requests-req-ddbsf-002
 */
class BulkSigningRecipientParser {

	/**
	 * Default row cap.
	 */
	public const MAX_ROWS = 1000;

	/**
	 * Largest file read, in bytes.
	 */
	public const MAX_BYTES = 2097152;

	/**
	 * Accepted header spellings per field, lower case without spaces, dashes or underscores.
	 *
	 * @var array<string, list<string>>
	 */
	private const HEADERS = [
		'userId' => ['userid', 'user', 'uid', 'username'],
		'email' => ['email', 'emailaddress', 'mail'],
		'displayName' => ['displayname', 'name', 'naam', 'fullname'],
	];

	/**
	 * Constructor.
	 *
	 * @param int $maxRows The most data rows a list may hold
	 *
	 * @spec openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md#requirement-bulk-send-validates-first-then-creates-isolated-ordinary-requests-req-ddbsf-002
	 */
	public function __construct(
		private readonly int $maxRows = self::MAX_ROWS,
	) {

	}//end __construct()

	/**
	 * The recipient source a file name stands for.
	 *
	 * @param string $filename The uploaded file name
	 *
	 * @return string `csv`, `xlsx`, or '' for anything else
	 *
	 * @spec openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md#requirement-bulk-send-validates-first-then-creates-isolated-ordinary-requests-req-ddbsf-002
	 */
	public function sourceOf(string $filename): string {
		$extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
		if (in_array($extension, ['csv', 'txt'], true) === true) {
			return 'csv';
		}

		if ($extension === 'xlsx') {
			return 'xlsx';
		}

		return '';

	}//end sourceOf()

	/**
	 * Parse a recipient list.
	 *
	 * @param string $content  The file bytes
	 * @param string $filename The uploaded file name, which decides the format
	 *
	 * @return list<array{row: int, userId: string, email: string, displayName: string}> Rows in file order;
	 *     `row` is the line number a spreadsheet shows, the header being row 1
	 *
	 * @throws InvalidArgumentException 400 when the list cannot be read as a whole
	 *
	 * @spec openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md#requirement-bulk-send-validates-first-then-creates-isolated-ordinary-requests-req-ddbsf-002
	 */
	public function parse(string $content, string $filename): array {
		if ($content === '') {
			throw new InvalidArgumentException(message: 'The recipient list is empty', code: 400);
		}

		if (strlen($content) > self::MAX_BYTES) {
			throw new InvalidArgumentException(message: 'The recipient list is larger than 2 MB', code: 400);
		}

		$grid = match ($this->sourceOf(filename: $filename)) {
			'csv' => $this->readCsv(content: $content),
			'xlsx' => $this->readXlsx(content: $content),
			default => throw new InvalidArgumentException(message: 'A recipient list is a .csv or .xlsx file', code: 400),
		};

		return $this->toRows(grid: $grid);

	}//end parse()

	/**
	 * Map a grid of cells, header first, to recipient rows.
	 *
	 * @param array<int, array<int, string>> $grid Cells by row number, then column index
	 *
	 * @return list<array{row: int, userId: string, email: string, displayName: string}>
	 *
	 * @throws InvalidArgumentException 400 without a recipient column or over the row cap
	 */
	private function toRows(array $grid): array {
		$headerRow = array_key_first($grid);
		$columns = $this->columns(header: ($grid[$headerRow] ?? []));
		if (isset($columns['email']) === false && isset($columns['userId']) === false) {
			throw new InvalidArgumentException(message: 'The recipient list needs an "email" or a "userId" column', code: 400);
		}

		unset($grid[$headerRow]);
		$rows = [];
		foreach ($grid as $number => $cells) {
			if (implode('', array_map('trim', $cells)) === '') {
				continue;
			}

			$row = ['row' => (int) $number];
			foreach (array_keys(self::HEADERS) as $field) {
				$row[$field] = trim((string) ($cells[$columns[$field] ?? -1] ?? ''));
			}

			$rows[] = $row;
		}

		if (count($rows) > $this->maxRows) {
			throw new InvalidArgumentException(message: 'The recipient list has more than ' . $this->maxRows . ' rows', code: 400);
		}

		return $rows;

	}//end toRows()

	/**
	 * Find the column index of each known field in the header.
	 *
	 * @param array<int, string> $header The header cells
	 *
	 * @return array<string, int> Field => column index
	 */
	private function columns(array $header): array {
		$columns = [];
		foreach ($header as $index => $cell) {
			$key = strtolower(str_replace([' ', '-', '_'], '', trim($cell)));
			foreach (self::HEADERS as $field => $spellings) {
				if (isset($columns[$field]) === false && in_array($key, $spellings, true) === true) {
					$columns[$field] = $index;
				}
			}
		}

		return $columns;

	}//end columns()

	/**
	 * Read CSV text, comma or semicolon separated, into a grid.
	 *
	 * @param string $content The file bytes
	 *
	 * @return array<int, array<int, string>>
	 */
	private function readCsv(string $content): array {
		$content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;
		$lines = preg_split('/\r\n|\n|\r/', $content);
		if ($lines === false) {
			$lines = [];
		}

		$delimiter = ',';
		if (substr_count($lines[0] ?? '', ';') > substr_count($lines[0] ?? '', ',')) {
			$delimiter = ';';
		}

		$grid = [];
		foreach ($lines as $index => $line) {
			$grid[$index + 1] = array_map(
				static fn (?string $cell): string => (string) $cell,
				str_getcsv($line, $delimiter, '"', '')
			);
		}

		return $grid;

	}//end readCsv()

	/**
	 * Read the first worksheet of an XLSX file into a grid.
	 *
	 * @param string $content The file bytes
	 *
	 * @return array<int, array<int, string>>
	 *
	 * @throws InvalidArgumentException 400 when the file is not a readable workbook
	 */
	private function readXlsx(string $content): array {
		$path = tempnam(sys_get_temp_dir(), 'filinq-bulk');
		file_put_contents($path, $content);
		$zip = new ZipArchive();
		$opened = $zip->open($path, ZipArchive::RDONLY);
		$sheet = false;
		$strings = false;
		if ($opened === true) {
			$sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
			$strings = $zip->getFromName('xl/sharedStrings.xml');
			$zip->close();
		}

		unlink($path);
		$xml = false;
		if (is_string($sheet) === true) {
			$xml = simplexml_load_string($sheet, SimpleXMLElement::class, LIBXML_NONET);
		}

		if ($xml === false) {
			throw new InvalidArgumentException(message: 'The .xlsx file could not be read', code: 400);
		}

		$shared = $this->sharedStrings(xml: $strings);
		$grid = [];
		foreach ($xml->sheetData->row as $row) {
			$cells = [];
			foreach ($row->c as $cell) {
				$cells[$this->columnIndex(reference: (string) $cell['r'])] = $this->cellText(cell: $cell, shared: $shared);
			}

			$grid[(int) $row['r']] = $cells;
		}

		return $grid;

	}//end readXlsx()

	/**
	 * The text of one cell: shared, inline, or the cached value of a formula.
	 *
	 * @param SimpleXMLElement   $cell   The <c> element
	 * @param array<int, string> $shared The shared strings table
	 *
	 * @return string
	 */
	private function cellText(SimpleXMLElement $cell, array $shared): string {
		$type = (string) $cell['t'];
		if ($type === 's') {
			return ($shared[(int) $cell->v] ?? '');
		}

		if ($type === 'inlineStr') {
			return $this->richText(node: $cell->is);
		}

		return (string) $cell->v;

	}//end cellText()

	/**
	 * Read the shared strings table.
	 *
	 * @param string|false $xml The sharedStrings.xml content, or false when absent
	 *
	 * @return array<int, string>
	 */
	private function sharedStrings(string|false $xml): array {
		if ($xml === false) {
			return [];
		}

		$parsed = simplexml_load_string($xml, SimpleXMLElement::class, LIBXML_NONET);
		if ($parsed === false) {
			return [];
		}

		$strings = [];
		foreach ($parsed->si as $item) {
			$strings[] = $this->richText(node: $item);
		}

		return $strings;

	}//end sharedStrings()

	/**
	 * Join a string item's plain or rich-text runs.
	 *
	 * @param SimpleXMLElement $node An <si> or <is> element
	 *
	 * @return string
	 */
	private function richText(SimpleXMLElement $node): string {
		if (isset($node->t) === true) {
			return (string) $node->t;
		}

		$text = '';
		foreach ($node->r as $run) {
			$text .= (string) $run->t;
		}

		return $text;

	}//end richText()

	/**
	 * Zero-based column index of a cell reference such as "C12".
	 *
	 * @param string $reference The cell reference
	 *
	 * @return int
	 */
	private function columnIndex(string $reference): int {
		$letters = preg_replace('/[^A-Z]/', '', strtoupper($reference)) ?? '';
		$index = 0;
		foreach (str_split($letters) as $letter) {
			$index = ($index * 26) + (ord($letter) - 64);
		}

		return ($index - 1);

	}//end columnIndex()
}//end class
