<?php

/**
 * Unit tests for XlsxSpreadsheetCodec's value part and reference-less cells.
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
use OCA\Filinq\Service\Editing\XlsxSpreadsheetCodec;
use PHPUnit\Framework\TestCase;
use ZipArchive;

/**
 * Where xlsx values live, and that a cell without an address is not invented one.
 *
 * @covers \OCA\Filinq\Service\Editing\XlsxSpreadsheetCodec
 * @uses   \OCA\Filinq\Service\Editing\PackagePartIo
 */
class XlsxSpreadsheetCodecReferenceTest extends TestCase {

	/**
	 * Values are read from the first worksheet part.
	 *
	 * @return void
	 */
	public function testValuesLiveInTheFirstWorksheet(): void {
		$this->assertSame('xl/worksheets/sheet1.xml', (new XlsxSpreadsheetCodec(new PackagePartIo()))->valuePart());
	}//end testValuesLiveInTheFirstWorksheet()

	/**
	 * A cell element without an r attribute is skipped; its neighbours still read.
	 *
	 * @return void
	 */
	public function testACellWithoutAReferenceIsSkipped(): void {
		$path = tempnam(sys_get_temp_dir(), 'xlsxref');
		$zip = new ZipArchive();
		$zip->open($path, ZipArchive::OVERWRITE);
		$zip->addFromString('[Content_Types].xml', '<Types/>');
		$zip->close();
		$package = (string)file_get_contents($path);
		unlink($path);

		$xml = '<sheetData><row r="1"><c><v>1</v></c><c r="B1"><v>2</v></c></row></sheetData>';
		$cells = (new XlsxSpreadsheetCodec(new PackagePartIo()))->readCells(xml: $xml, packageBytes: $package);

		$this->assertSame(['Sheet1!B1'], array_column($cells, 'cell'));
		$this->assertSame('2', $cells[0]['value']);
	}//end testACellWithoutAReferenceIsSkipped()
}//end class
