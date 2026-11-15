<?php

/**
 * Unit tests for SpreadsheetCodec's edit-set validation and address normalisation.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Editing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Editing;

use OCA\Filinq\Service\Editing\PackagePartIo;
use OCA\Filinq\Service\Editing\SpreadsheetCodec;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use ZipArchive;

/**
 * Empty edit sets, empty addresses and sheet-less addresses through the spreadsheet codec.
 *
 * @covers \OCA\Filinq\Service\Editing\SpreadsheetCodec
 * @uses   \OCA\Filinq\Service\Editing\PackagePartIo
 * @uses   \OCA\Filinq\Service\Editing\XlsxSpreadsheetCodec
 * @uses   \OCA\Filinq\Service\Editing\OdsSpreadsheetCodec
 */
class SpreadsheetCodecEditValidationTest extends TestCase {

	/**
	 * A one-sheet xlsx with A1 = 5 and B1 = 7.
	 *
	 * @return string The package bytes.
	 */
	private function xlsx(): string {
		$path = tempnam(sys_get_temp_dir(), 'sheetval');
		$zip = new ZipArchive();
		$zip->open($path, ZipArchive::OVERWRITE);
		$zip->addFromString('[Content_Types].xml', '<Types/>');
		$zip->addFromString(
			'xl/worksheets/sheet1.xml',
			'<worksheet xmlns="urn:x"><sheetData><row r="1"><c r="A1"><v>5</v></c><c r="B1"><v>7</v></c></row></sheetData></worksheet>'
		);
		$zip->close();
		$bytes = (string)file_get_contents($path);
		unlink($path);

		return $bytes;
	}//end xlsx()

	/**
	 * The codec under test.
	 *
	 * @return SpreadsheetCodec
	 */
	private function codec(): SpreadsheetCodec {
		return new SpreadsheetCodec(new PackagePartIo());
	}//end codec()

	/**
	 * An empty edit set is refused before the package is read.
	 *
	 * @return void
	 */
	public function testAnEmptyEditSetIsRefused(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('At least one cell edit is required.');

		$this->codec()->applyCellEdits(packageBytes: 'not a package', extension: 'xlsx', edits: []);
	}//end testAnEmptyEditSetIsRefused()

	/**
	 * An edit without a cell address names its position, counted from 1.
	 *
	 * @return void
	 */
	public function testAnEditWithoutACellNamesItsPosition(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Edit 2: a cell address is required.');

		$this->codec()->applyCellEdits(
			packageBytes: $this->xlsx(),
			extension: 'xlsx',
			edits: [['cell' => 'Sheet1!A1', 'value' => '1'], ['cell' => '', 'value' => '2']]
		);
	}//end testAnEditWithoutACellNamesItsPosition()

	/**
	 * A sheet-less, lower-case address is upper-cased and still reaches the cell.
	 *
	 * @return void
	 */
	public function testASheetlessLowerCaseAddressIsNormalised(): void {
		$result = $this->codec()->applyCellEdits(
			packageBytes: $this->xlsx(),
			extension: 'xlsx',
			edits: [['cell' => 'b1', 'value' => '9']]
		);

		$this->assertSame(['B1'], $result['applied']);
		$cells = array_column($this->codec()->readCells(packageBytes: $result['bytes'], extension: 'xlsx'), 'value', 'cell');
		$this->assertSame('9', $cells['Sheet1!B1']);
		$this->assertSame('5', $cells['Sheet1!A1']);
	}//end testASheetlessLowerCaseAddressIsNormalised()
}//end class
