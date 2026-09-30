<?php

/**
 * Unit tests for reading a recipient list
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\BulkSigning
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\Filinq\Tests\Unit\Service\BulkSigning;

use InvalidArgumentException;
use OCA\Filinq\Service\BulkSigning\BulkSigningRecipientParser;
use PHPUnit\Framework\TestCase;
use ZipArchive;

/**
 * A CSV or XLSX list becomes numbered recipient rows, and nothing in a cell runs.
 */
class BulkSigningRecipientParserTest extends TestCase {

	/**
	 * The 50-row fixture reads as 50 rows numbered as the spreadsheet numbers them.
	 *
	 * @return void
	 */
	public function testTheFixtureReadsAsFiftyNumberedRows(): void {
		$rows = (new BulkSigningRecipientParser())->parse(
			content: (string) file_get_contents(__DIR__ . '/../../../fixtures/bulk-signing/recipients-50.csv'),
			filename: 'recipients-50.csv'
		);

		$this->assertCount(50, $rows);
		$this->assertSame(['row' => 2, 'userId' => '', 'email' => 'burger1@example.invalid', 'displayName' => 'Burger 1'], $rows[0]);
		$this->assertSame(51, $rows[49]['row']);

	}//end testTheFixtureReadsAsFiftyNumberedRows()

	/**
	 * Comma lists, a byte-order mark, header case and blank lines are all read.
	 *
	 * @return void
	 */
	public function testACommaListWithABomAndBlankLinesReads(): void {
		$csv = "\u{FEFF}E-mail,User ID,Name\r\nan@example.invalid,an,An\r\n\r\n,bo,\r\n";

		$rows = (new BulkSigningRecipientParser())->parse(content: $csv, filename: 'list.csv');

		$this->assertSame(
			[
				['row' => 2, 'userId' => 'an', 'email' => 'an@example.invalid', 'displayName' => 'An'],
				['row' => 4, 'userId' => 'bo', 'email' => '', 'displayName' => ''],
			],
			$rows
		);

	}//end testACommaListWithABomAndBlankLinesReads()

	/**
	 * A formula in a cell is kept as the text it is, never evaluated.
	 *
	 * @return void
	 */
	public function testAFormulaStaysText(): void {
		$rows = (new BulkSigningRecipientParser())->parse(content: "email\n=HYPERLINK(\"x\")\n", filename: 'f.csv');

		$this->assertSame('=HYPERLINK("x")', $rows[0]['email']);

	}//end testAFormulaStaysText()

	/**
	 * A list without an e-mail or user column is refused as a whole.
	 *
	 * @return void
	 */
	public function testAListWithoutARecipientColumnIsRefused(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionCode(400);

		(new BulkSigningRecipientParser())->parse(content: "name;phone\nAn;06\n", filename: 'x.csv');

	}//end testAListWithoutARecipientColumnIsRefused()

	/**
	 * More rows than the cap refuses the list before anything is stored.
	 *
	 * @return void
	 */
	public function testMoreRowsThanTheCapIsRefused(): void {
		$csv = "email\n" . str_repeat("a@example.invalid\n", 4);

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionCode(400);

		(new BulkSigningRecipientParser(maxRows: 3))->parse(content: $csv, filename: 'x.csv');

	}//end testMoreRowsThanTheCapIsRefused()

	/**
	 * A file type that is not a list is refused.
	 *
	 * @return void
	 */
	public function testAnotherFileTypeIsRefused(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionCode(400);

		(new BulkSigningRecipientParser())->parse(content: '%PDF-1.7', filename: 'x.pdf');

	}//end testAnotherFileTypeIsRefused()

	/**
	 * An XLSX sheet reads with shared strings, inline strings and a formula's cached text.
	 *
	 * @return void
	 */
	public function testAnXlsxSheetReads(): void {
		$rows = (new BulkSigningRecipientParser())->parse(content: $this->xlsx(), filename: 'list.xlsx');

		$this->assertSame(
			[
				['row' => 2, 'userId' => '', 'email' => 'an@example.invalid', 'displayName' => 'An'],
				['row' => 3, 'userId' => 'bo', 'email' => 'bo@example.invalid', 'displayName' => ''],
			],
			$rows
		);
		$this->assertSame('xlsx', BulkSigningRecipientParser::sourceOf(filename: 'list.xlsx'));
		$this->assertSame('csv', BulkSigningRecipientParser::sourceOf(filename: 'LIST.CSV'));

	}//end testAnXlsxSheetReads()

	/**
	 * A broken XLSX is refused, not read as empty.
	 *
	 * @return void
	 */
	public function testABrokenXlsxIsRefused(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionCode(400);

		(new BulkSigningRecipientParser())->parse(content: 'not a zip', filename: 'list.xlsx');

	}//end testABrokenXlsxIsRefused()

	/**
	 * Build a minimal XLSX the way a spreadsheet program writes one.
	 *
	 * @return string The file bytes
	 */
	private function xlsx(): string {
		$path = tempnam(sys_get_temp_dir(), 'xlsx');
		$zip = new ZipArchive();
		$zip->open($path, ZipArchive::OVERWRITE);
		$zip->addFromString(
			'xl/sharedStrings.xml',
			'<?xml version="1.0"?><sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
			. '<si><t>email</t></si><si><t>userId</t></si><si><t>displayName</t></si><si><t>an@example.invalid</t></si><si><r><t>A</t></r><r><t>n</t></r></si></sst>'
		);
		$zip->addFromString(
			'xl/worksheets/sheet1.xml',
			'<?xml version="1.0"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>'
			. '<row r="1"><c r="A1" t="s"><v>0</v></c><c r="B1" t="s"><v>1</v></c><c r="C1" t="s"><v>2</v></c></row>'
			. '<row r="2"><c r="A2" t="s"><v>3</v></c><c r="C2" t="s"><v>4</v></c></row>'
			. '<row r="3"><c r="A3" t="str"><f>LOWER("BO@example.invalid")</f><v>bo@example.invalid</v></c><c r="B3" t="inlineStr"><is><t>bo</t></is></c></row>'
			. '</sheetData></worksheet>'
		);
		$zip->close();
		$bytes = (string) file_get_contents($path);
		unlink($path);

		return $bytes;

	}//end xlsx()
}//end class
