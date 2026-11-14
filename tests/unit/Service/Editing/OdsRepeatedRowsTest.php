<?php

/**
 * Repeated ODS rows count as the rows they stand for, in reading and in writing.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service\Editing
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.2
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Editing;

use DOMDocument;
use OCA\Filinq\Service\Editing\PackagePartIo;
use OCA\Filinq\Service\Editing\SpreadsheetCodec;
use PHPUnit\Framework\TestCase;

/**
 * Over tests/fixtures/ods/planning-runs.ods: a header row, a run of 3 empty rows,
 * a data row, a run of 2 identical filled rows, a last row and Calc's trailing empty run.
 *
 * @covers \OCA\Filinq\Service\Editing\OdsSpreadsheetCodec
 * @uses   \OCA\Filinq\Service\Editing\SpreadsheetCodec
 * @uses   \OCA\Filinq\Service\Editing\PackagePartIo
 * @uses   \OCA\Filinq\Service\Editing\XlsxSpreadsheetCodec
 */
class OdsRepeatedRowsTest extends TestCase {

	/**
	 * What every cell of the untouched fixture reads as.
	 */
	private const CELLS = [
		'Planning!A1' => 'Rol',
		'Planning!B1' => 'Uurtarief',
		'Planning!A5' => 'Adviseur',
		'Planning!B5' => '110',
		'Planning!E5' => 'E5',
		'Planning!A6' => 'Herhaald',
		'Planning!A7' => 'Herhaald',
		'Planning!A8' => 'Laatste',
	];

	/**
	 * The fixture package.
	 *
	 * @return string The package bytes.
	 */
	private function package(): string {
		return (string)file_get_contents(dirname(__DIR__, 3) . '/fixtures/ods/planning-runs.ods');

	}//end package()

	/**
	 * Cell values by address.
	 *
	 * @param string $package The package bytes.
	 *
	 * @return array<string, string> The values.
	 */
	private function read(string $package): array {
		return array_column((new SpreadsheetCodec(new PackagePartIo()))->readCells(packageBytes: $package, extension: 'ods'), 'value', 'cell');

	}//end read()

	/**
	 * Write one cell and return the package, after checking content.xml still parses.
	 *
	 * @param string $cell  The address.
	 * @param string $value The value.
	 *
	 * @return string The written package.
	 */
	private function write(string $cell, string $value): string {
		$io = new PackagePartIo();
		$written = (new SpreadsheetCodec($io))->applyCellEdits(
			packageBytes: $this->package(),
			extension: 'ods',
			edits: [['cell' => $cell, 'value' => $value]]
		);

		$this->assertSame([$cell], $written['applied']);
		$this->assertTrue((new DOMDocument())->loadXML($io->readPart(packageBytes: $written['bytes'], part: 'content.xml')));

		return $written['bytes'];

	}//end write()

	/**
	 * Rows after an empty-row run read at their own addresses, and a filled run reads once per row.
	 *
	 * @return void
	 */
	public function testRowsAfterARunReadAtTheirAddresses(): void {
		$this->assertSame(self::CELLS, $this->read(package: $this->package()));

	}//end testRowsAfterARunReadAtTheirAddresses()

	/**
	 * A write after the run lands on that row and nowhere else.
	 *
	 * @return void
	 */
	public function testAWriteAfterTheRunChangesThatCellOnly(): void {
		$cells = $this->read(package: $this->write(cell: 'Planning!B5', value: '120'));

		$this->assertSame(array_replace(self::CELLS, ['Planning!B5' => '120']), $cells);

	}//end testAWriteAfterTheRunChangesThatCellOnly()

	/**
	 * A write inside the empty run splits it around the written row.
	 *
	 * @return void
	 */
	public function testAWriteInsideTheEmptyRunSplitsIt(): void {
		$package = $this->write(cell: 'Planning!C3', value: 'nieuw');

		$cells = $this->read(package: $package);
		ksort($cells);
		$expected = self::CELLS + ['Planning!C3' => 'nieuw'];
		ksort($expected);
		$this->assertSame($expected, $cells);
		$xml = (new PackagePartIo())->readPart(packageBytes: $package, part: 'content.xml');
		$this->assertSame(0, substr_count($xml, 'table:number-rows-repeated="3"'));
		$this->assertSame(2, substr_count($xml, '<table:table-row table:style-name="ro1"><table:table-cell table:number-columns-repeated="3"/></table:table-row>'));

	}//end testAWriteInsideTheEmptyRunSplitsIt()

	/**
	 * A write into the second row of a filled run leaves the first as it was.
	 *
	 * @return void
	 */
	public function testAWriteIntoAFilledRunChangesOneRowOfIt(): void {
		$cells = $this->read(package: $this->write(cell: 'Planning!A7', value: 'Anders'));

		$this->assertSame(array_replace(self::CELLS, ['Planning!A7' => 'Anders']), $cells);

	}//end testAWriteIntoAFilledRunChangesOneRowOfIt()

	/**
	 * A repeated column after a run still lands in its own column.
	 *
	 * @return void
	 */
	public function testARepeatedColumnAfterARunLandsInItsColumn(): void {
		$cells = $this->read(package: $this->write(cell: 'Planning!D5', value: 'D'));

		$this->assertSame(['Planning!D5' => 'D', 'Planning!E5' => 'E5'], array_intersect_key($cells, ['Planning!D5' => 1, 'Planning!E5' => 1]));
		$this->assertSame('110', $cells['Planning!B5']);

	}//end testARepeatedColumnAfterARunLandsInItsColumn()
	/**
	 * A run on another sheet is left whole by a write that shares its row number.
	 *
	 * @return void
	 */
	public function testARunOnAnotherSheetStaysWhole(): void {
		$run = '<table:table-row table:number-rows-repeated="3"><table:table-cell table:number-columns-repeated="2"/></table:table-row>';
		$xml = '<table:table table:name="Een">' . $run . '</table:table>'
			. '<table:table table:name="Twee"><table:table-row><table:table-cell/></table:table-row>'
			. '<table:table-row><table:table-cell/></table:table-row></table:table>';

		$written = (new \OCA\Filinq\Service\Editing\OdsSpreadsheetCodec())->writeCell(xml: $xml, cell: 'Twee!A2', value: 'x');

		$this->assertStringContainsString('<table:table table:name="Een">' . $run . '</table:table>', $written);

	}//end testARunOnAnotherSheetStaysWhole()
}//end class
