<?php

/**
 * Cell-addressed spreadsheet editing, and the refusals that make it safe.
 *
 * Every guard here is tested as a CONTROL PAIR — the same write refused
 * without intent and accepted with it. A guard nobody has watched refuse is
 * untested: it may be rejecting everything, or nothing.
 *
 * @category Test
 * @package  OCA\Filinq\Tests\Unit\Service\Editing
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://filinq.app
 *
 * @spec openspec/changes/multi-format-editing-tools/tasks.md#1-codec-interface--spreadsheet
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Editing;

use OCA\Filinq\Service\Editing\PackagePartIo;
use OCA\Filinq\Service\Editing\SpreadsheetCodec;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Tests for SpreadsheetCodec.
 */
class SpreadsheetCodecTest extends TestCase {

	private SpreadsheetCodec $codec;

	private string $ods;

	/**
	 * Build a minimal ODS package in memory.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->codec = new SpreadsheetCodec(new PackagePartIo());
		$this->ods = $this->buildOds($this->content());
	}//end setUp()

	/**
	 * The content.xml under test: a header row, a data row, and a formula.
	 *
	 * @return string The XML.
	 */
	private function content(): string {
		return '<?xml version="1.0"?><office:document-content>'
			. '<office:body><office:spreadsheet>'
			. '<table:table table:name="Sales">'
			. '<table:table-row>'
			. '<table:table-cell office:value-type="string"><text:p>Post</text:p></table:table-cell>'
			. '<table:table-cell office:value-type="string"><text:p>Aantal</text:p></table:table-cell>'
			. '<table:table-cell office:value-type="string"><text:p>Prijs</text:p></table:table-cell>'
			. '<table:table-cell office:value-type="string"><text:p>Totaal</text:p></table:table-cell>'
			. '</table:table-row>'
			. '<table:table-row>'
			. '<table:table-cell office:value-type="string"><text:p>Licentie</text:p></table:table-cell>'
			. '<table:table-cell office:value-type="float" office:value="10"><text:p>10</text:p></table:table-cell>'
			. '<table:table-cell office:value-type="float" office:value="29"><text:p>29</text:p></table:table-cell>'
			. '<table:table-cell table:formula="of:=[.B2]*[.C2]" office:value-type="float" office:value="290">'
			. '<text:p>290</text:p></table:table-cell>'
			. '</table:table-row>'
			. '</table:table>'
			. '</office:spreadsheet></office:body></office:document-content>';
	}//end content()

	/**
	 * Zip one content.xml into an ODS package.
	 *
	 * @param string $content The content.xml.
	 *
	 * @return string The package bytes.
	 */
	private function buildOds(string $content): string {
		$path = tempnam(sys_get_temp_dir(), 'ods');
		$zip = new \ZipArchive();
		$zip->open($path, \ZipArchive::OVERWRITE);
		$zip->addFromString('content.xml', $content);
		$zip->close();
		$bytes = file_get_contents($path);
		unlink($path);

		return (string)$bytes;
	}//end buildOds()

	/**
	 * Cells are addressed as `Sheet!Cell`, and a formula is reported as one.
	 *
	 * @return void
	 */
	public function testCellsAreReadWithSheetAddressesAndFormulas(): void {
		$cells = $this->codec->readCells($this->ods, 'ods');
		$byAddress = array_column($cells, null, 'cell');

		$this->assertArrayHasKey('Sales!A1', $byAddress);
		$this->assertSame('Post', $byAddress['Sales!A1']['value']);
		$this->assertSame('of:=[.B2]*[.C2]', $byAddress['Sales!D2']['formula']);
		$this->assertNull($byAddress['Sales!B2']['formula']);
	}//end testCellsAreReadWithSheetAddressesAndFormulas()

	/**
	 * CONTROL A: an ordinary cell writes without ceremony.
	 *
	 * @return void
	 */
	public function testAPlainCellIsWritten(): void {
		$result = $this->codec->applyCellEdits($this->ods, 'ods', [['cell' => 'Sales!B2', 'value' => '25']]);

		$this->assertSame(['Sales!B2'], $result['applied']);

		$after = array_column($this->codec->readCells($result['bytes'], 'ods'), null, 'cell');
		$this->assertSame('25', $after['Sales!B2']['value']);
	}//end testAPlainCellIsWritten()

	/**
	 * 🔴 CONTROL B: writing a literal over a formula is REFUSED.
	 *
	 * @return void
	 */
	public function testWritingOverAFormulaIsRefused(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessageMatches('/holds the formula.*replaceFormula/s');

		$this->codec->applyCellEdits($this->ods, 'ods', [['cell' => 'Sales!D2', 'value' => '999']]);
	}//end testWritingOverAFormulaIsRefused()

	/**
	 * CONTROL B, other half: the SAME write succeeds with per-cell intent. Run
	 * both halves — a guard nobody has watched accept may be refusing
	 * everything.
	 *
	 * @return void
	 */
	public function testTheSameWriteSucceedsWithPerCellIntent(): void {
		$result = $this->codec->applyCellEdits(
			$this->ods,
			'ods',
			[['cell' => 'Sales!D2', 'value' => '999', 'replaceFormula' => true]]
		);

		$this->assertSame(['Sales!D2'], $result['applied']);
	}//end testTheSameWriteSucceedsWithPerCellIntent()

	/**
	 * 🔴 Intent is PER CELL and must not be carried along by a bulk write. One
	 * cell's permission travelling across a call would destroy the formula the
	 * caller never looked at.
	 *
	 * @return void
	 */
	public function testIntentDoesNotCarryAcrossABulkWrite(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessageMatches('/Edit 2:.*holds the formula/s');

		$this->codec->applyCellEdits(
			$this->ods,
			'ods',
			[
				['cell' => 'Sales!B2', 'value' => '1', 'replaceFormula' => true],
				['cell' => 'Sales!D2', 'value' => '2'],
			]
		);
	}//end testIntentDoesNotCarryAcrossABulkWrite()

	/**
	 * A cell whose formula references a written cell is reported STALE.
	 *
	 * Reported, not recalculated: this app has no formula engine, and a cached
	 * value left silently in place is a number that looks current and is not.
	 *
	 * @return void
	 */
	public function testDependentsOfAWrittenCellAreReportedStale(): void {
		$result = $this->codec->applyCellEdits($this->ods, 'ods', [['cell' => 'Sales!B2', 'value' => '25']]);

		$this->assertSame(['Sales!D2'], $result['staleDependents']);
	}//end testDependentsOfAWrittenCellAreReportedStale()

	/**
	 * A cell nothing depends on reports no stale dependents — otherwise the
	 * previous assertion passes on a method that flags everything.
	 *
	 * @return void
	 */
	public function testAnIndependentCellReportsNoStaleDependents(): void {
		$result = $this->codec->applyCellEdits($this->ods, 'ods', [['cell' => 'Sales!A1', 'value' => 'Kop']]);

		$this->assertSame([], $result['staleDependents']);
	}//end testAnIndependentCellReportsNoStaleDependents()

	/**
	 * ⚠️ Over the cell bound the call is REFUSED, never truncated. A truncated
	 * bulk write reports success for edits it never made.
	 *
	 * @return void
	 */
	public function testAnOversizedCallIsRefusedRatherThanTruncated(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessageMatches('/refused rather than truncated/');

		$this->codec->applyCellEdits(
			$this->ods,
			'ods',
			array_fill(0, (SpreadsheetCodec::MAX_CELLS_PER_CALL + 1), ['cell' => 'Sales!B2', 'value' => 'x'])
		);
	}//end testAnOversizedCallIsRefusedRatherThanTruncated()

	/**
	 * ⚠️ A sheet name is CASE-SENSITIVE. Upper-casing the whole address turns
	 * `Sales!b2` into `SALES!B2`, which matches no sheet — the write then fails
	 * on a document where the sheet plainly exists.
	 *
	 * @return void
	 */
	public function testSheetNameCaseIsPreservedWhileCellReferenceIsNot(): void {
		$result = $this->codec->applyCellEdits($this->ods, 'ods', [['cell' => 'Sales!b2', 'value' => '7']]);

		$this->assertSame(['Sales!B2'], $result['applied']);
	}//end testSheetNameCaseIsPreservedWhileCellReferenceIsNot()

	/**
	 * 🔴 One ODS element can stand for MANY columns via
	 * `table:number-columns-repeated`. Ignoring the repeat puts every cell
	 * after a gap at the wrong address, silently, for the whole row.
	 *
	 * @return void
	 */
	public function testRepeatedColumnsAdvanceTheAddress(): void {
		$xml = '<?xml version="1.0"?><office:document-content><office:body><office:spreadsheet>'
			. '<table:table table:name="Gap">'
			. '<table:table-row>'
			. '<table:table-cell table:number-columns-repeated="3"/>'
			. '<table:table-cell office:value-type="string"><text:p>Vierde</text:p></table:table-cell>'
			. '</table:table-row>'
			. '</table:table></office:spreadsheet></office:body></office:document-content>';

		$cells = $this->codec->readCells($this->buildOds($xml), 'ods');

		$this->assertSame('Gap!D1', $cells[0]['cell'], 'three repeated columns must push the value to D, not B');
	}//end testRepeatedColumnsAdvanceTheAddress()

	/**
	 * A cell that does not exist is refused by name rather than silently
	 * created somewhere plausible.
	 *
	 * @return void
	 */
	public function testAnAbsentCellIsRefusedByName(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessageMatches('/does not exist in this sheet/');

		$this->codec->applyCellEdits($this->ods, 'ods', [['cell' => 'Sales!Z99', 'value' => 'x']]);
	}//end testAnAbsentCellIsRefusedByName()

	/**
	 * A non-spreadsheet extension is refused, naming what IS supported.
	 *
	 * @return void
	 */
	public function testANonSpreadsheetExtensionIsRefused(): void {
		$this->assertFalse($this->codec->supports('docx'));

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessageMatches('/not a spreadsheet this codec edits.*ods, xlsx/s');

		$this->codec->readCells($this->ods, 'docx');
	}//end testANonSpreadsheetExtensionIsRefused()
}//end class
