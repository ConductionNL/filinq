<?php

/**
 * Unit tests for OdsSpreadsheetCodec — reading and writing ODF cells.
 *
 * 🔴 The property these tests exist for: a cell's ADDRESS survives the read. In
 * ODF one element can stand for many columns via
 * `table:number-columns-repeated`, so ignoring the repeat count puts every cell
 * after the first gap at the wrong address — silently, for the whole row. An
 * agent then reports the right value against the wrong cell, which is worse
 * than failing to read it at all.
 *
 * @category Test
 * @package  OCA\Filinq\Tests\Unit\Service\Editing
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Editing;

use OCA\Filinq\Service\Editing\OdsSpreadsheetCodec;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Tests for the ODF spreadsheet codec.
 *
 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.2
 */
class OdsSpreadsheetCodecTest extends TestCase {
	/**
	 * The codec under test.
	 *
	 * @var OdsSpreadsheetCodec
	 */
	private OdsSpreadsheetCodec $codec;

	/**
	 * Build the codec.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->codec = new OdsSpreadsheetCodec();
	}//end setUp()

	/**
	 * One sheet with three populated cells, the third behind a repeat gap.
	 *
	 * @return string The content.xml.
	 */
	private function sheetXml(): string {
		return '<office:document-content>'
			. '<table:table table:name="Sheet1">'
			. '<table:table-row>'
			. '<table:table-cell office:value-type="string"><text:p>Name</text:p></table:table-cell>'
			. '<table:table-cell office:value-type="string"><text:p>Rate</text:p></table:table-cell>'
			. '</table:table-row>'
			. '<table:table-row>'
			. '<table:table-cell office:value-type="string"><text:p>Dev</text:p></table:table-cell>'
			. '<table:table-cell table:number-columns-repeated="2"/>'
			. '<table:table-cell office:value-type="float" office:value="95"><text:p>95</text:p></table:table-cell>'
			. '</table:table-row>'
			. '</table:table>'
			. '</office:document-content>';
	}//end sheetXml()

	/**
	 * Cells come back addressed by `Sheet!Cell`, not by ordinal position.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.2
	 */
	public function testCellsAreAddressedBySheetAndCell(): void {
		$cells = $this->codec->readCells(xml: $this->sheetXml(), packageBytes: '');

		$addresses = array_column($cells, 'cell');

		$this->assertContains('Sheet1!A1', $addresses);
		$this->assertContains('Sheet1!B1', $addresses);
		$this->assertContains('Sheet1!A2', $addresses);
	}//end testCellsAreAddressedBySheetAndCell()

	/**
	 * 🔴 A repeated blank shifts every later cell in the row, and must be counted.
	 *
	 * The value 95 sits after a `table:number-columns-repeated="2"` blank, so it
	 * is in column D. A reader that treats the repeat as one column reports it
	 * as C2 — the right number against the wrong cell, with nothing to show for
	 * it downstream.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.2
	 */
	public function testARepeatedBlankShiftsTheAddressesAfterIt(): void {
		$cells = $this->codec->readCells(xml: $this->sheetXml(), packageBytes: '');

		$byAddress = array_column($cells, 'value', 'cell');

		$this->assertArrayHasKey('Sheet1!D2', $byAddress, 'the repeat count must move the value to column D');
		$this->assertSame('95', $byAddress['Sheet1!D2']);
		$this->assertArrayNotHasKey('Sheet1!C2', $byAddress, 'C2 is inside the repeated blank and holds nothing');
	}//end testARepeatedBlankShiftsTheAddressesAfterIt()

	/**
	 * A formula cell reports its formula, not only its cached value.
	 *
	 * The distinction is what the write guard is built on: a literal write into
	 * a cell that holds a formula destroys the formula, so the reader has to say
	 * which cells those are.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.3
	 */
	public function testAFormulaCellReportsItsFormula(): void {
		$xml = '<table:table table:name="S">'
			. '<table:table-row>'
			. '<table:table-cell table:formula="of:=SUM([.A1:.A2])" office:value-type="float" office:value="3">'
			. '<text:p>3</text:p></table:table-cell>'
			. '</table:table-row></table:table>';

		$cells = $this->codec->readCells(xml: $xml, packageBytes: '');

		$this->assertCount(1, $cells);
		$this->assertNotNull($cells[0]['formula'], 'a formula cell must not read as a plain value');
		$this->assertStringContainsString('SUM', (string)$cells[0]['formula']);
	}//end testAFormulaCellReportsItsFormula()

	/**
	 * A plain value cell reports no formula.
	 *
	 * Asserted as the CONTROL for the test above: if every cell reported a
	 * formula, that test would pass while telling us nothing.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.3
	 */
	public function testAPlainCellReportsNoFormula(): void {
		$cells = $this->codec->readCells(xml: $this->sheetXml(), packageBytes: '');

		foreach ($cells as $cell) {
			$this->assertNull($cell['formula'], $cell['cell'] . ' holds no formula and must not claim one');
		}
	}//end testAPlainCellReportsNoFormula()

	/**
	 * Writing a cell changes that cell and leaves the rest of the sheet alone.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.2
	 */
	public function testWritingACellLeavesItsNeighboursAlone(): void {
		$written = $this->codec->writeCell(xml: $this->sheetXml(), cell: 'Sheet1!A1', value: 'Service');

		$cells = array_column($this->codec->readCells(xml: $written, packageBytes: ''), 'value', 'cell');

		$this->assertSame('Service', $cells['Sheet1!A1']);
		$this->assertSame('Rate', $cells['Sheet1!B1'], 'the neighbouring cell must be untouched');
		$this->assertSame('Dev', $cells['Sheet1!A2']);
		$this->assertSame('95', $cells['Sheet1!D2'], 'the repeat-shifted cell must survive a write elsewhere');
	}//end testWritingACellLeavesItsNeighboursAlone()

	/**
	 * Writing a cell that does not exist is refused, not silently ignored.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.2
	 */
	public function testWritingAnAbsentCellIsRefused(): void {
		$this->expectException(RuntimeException::class);

		$this->codec->writeCell(xml: $this->sheetXml(), cell: 'Sheet1!Z99', value: 'nope');
	}//end testWritingAnAbsentCellIsRefused()

	/**
	 * The codec claims the ODF spreadsheet extension and not the OOXML one.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.1
	 */
	public function testItSupportsOnlyItsOwnFormat(): void {
		$this->assertTrue($this->codec->supports(extension: 'ods'));
		$this->assertFalse($this->codec->supports(extension: 'xlsx'));
		$this->assertFalse($this->codec->supports(extension: 'odt'));
	}//end testItSupportsOnlyItsOwnFormat()
}//end class
