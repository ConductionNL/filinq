<?php

/**
 * Unit tests for XlsxSpreadsheetCodec — reading and writing OOXML cells.
 *
 * 🔴 The property these tests exist for: `t="s"` means the cell's value is an
 * INDEX into the shared string table, not the text. A reader that returns the
 * index hands the caller a number where the sheet plainly shows a word — and an
 * agent asked "what is in B2" then answers `3`. It does not error, and the
 * number is real, which is what makes it convincing.
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

use OCA\Filinq\Service\Editing\PackagePartIo;
use OCA\Filinq\Service\Editing\XlsxSpreadsheetCodec;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Tests for the OOXML spreadsheet codec.
 *
 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.2
 */
class XlsxSpreadsheetCodecTest extends TestCase {
	/**
	 * A package reader that serves one fixed shared-string table.
	 *
	 * @param string|null $sharedXml The sharedStrings.xml, or null to make the
	 *                               part absent (a sheet with no strings at all).
	 *
	 * @return PackagePartIo The double.
	 */
	private function io(?string $sharedXml): PackagePartIo {
		return new class($sharedXml) extends PackagePartIo {
			/**
			 * @param string|null $sharedXml The part's content, or null when absent.
			 */
			public function __construct(private readonly ?string $sharedXml) {
			}

			/**
			 * Serve the shared string table, or refuse like a missing part.
			 *
			 * @param string $packageBytes Ignored.
			 * @param string $part         The requested part.
			 *
			 * @return string The part's content.
			 *
			 * @throws RuntimeException When the part is absent.
			 */
			public function readPart(string $packageBytes, string $part): string {
				if ($this->sharedXml === null) {
					throw new RuntimeException('no such part: ' . $part);
				}

				return $this->sharedXml;
			}
		};
	}//end io()

	/**
	 * A shared string table holding two entries.
	 *
	 * @return string The sharedStrings.xml.
	 */
	private function sharedStrings(): string {
		return '<sst><si><t>Name</t></si><si><t>Dev &amp; Ops</t></si></sst>';
	}//end sharedStrings()

	/**
	 * 🔴 A `t="s"` cell reports the STRING, never the shared-table index.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.2
	 */
	public function testASharedStringCellReportsTheTextNotTheIndex(): void {
		$codec = new XlsxSpreadsheetCodec($this->io($this->sharedStrings()));

		$cells = $codec->readCells(
			xml: '<row><c r="A1" t="s"><v>0</v></c></row>',
			packageBytes: 'ignored'
		);

		$this->assertCount(1, $cells);
		$this->assertSame('Name', $cells[0]['value'], 'reporting "0" would be the index, not the value');
		$this->assertSame('Sheet1!A1', $cells[0]['cell']);
	}//end testASharedStringCellReportsTheTextNotTheIndex()

	/**
	 * Entities in a shared string are decoded, so the caller sees the text.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.2
	 */
	public function testSharedStringEntitiesAreDecoded(): void {
		$codec = new XlsxSpreadsheetCodec($this->io($this->sharedStrings()));

		$cells = $codec->readCells(
			xml: '<row><c r="B1" t="s"><v>1</v></c></row>',
			packageBytes: 'ignored'
		);

		$this->assertSame('Dev & Ops', $cells[0]['value']);
	}//end testSharedStringEntitiesAreDecoded()

	/**
	 * An inline number is read as itself, with no shared table involved.
	 *
	 * The CONTROL for the two tests above: if every cell resolved through the
	 * string table, they would pass without proving the `t="s"` branch runs.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.2
	 */
	public function testANumericCellIsReadAsItself(): void {
		$codec = new XlsxSpreadsheetCodec($this->io($this->sharedStrings()));

		$cells = $codec->readCells(
			xml: '<row><c r="C1"><v>95</v></c></row>',
			packageBytes: 'ignored'
		);

		$this->assertSame('95', $cells[0]['value']);
	}//end testANumericCellIsReadAsItself()

	/**
	 * A missing shared-string part degrades to no strings, not to a crash.
	 *
	 * A sheet can legitimately have no `sharedStrings.xml` at all, and reading
	 * one must not be the difference between working and throwing.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.2
	 */
	public function testAnAbsentSharedStringTableIsNotAnError(): void {
		$codec = new XlsxSpreadsheetCodec($this->io(null));

		$cells = $codec->readCells(
			xml: '<row><c r="C1"><v>95</v></c></row>',
			packageBytes: 'ignored'
		);

		$this->assertSame('95', $cells[0]['value']);
	}//end testAnAbsentSharedStringTableIsNotAnError()

	/**
	 * A formula cell reports its formula alongside the cached value.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.3
	 */
	public function testAFormulaCellReportsItsFormula(): void {
		$codec = new XlsxSpreadsheetCodec($this->io($this->sharedStrings()));

		$cells = $codec->readCells(
			xml: '<row><c r="D1"><f>SUM(A1:A2)</f><v>3</v></c></row>',
			packageBytes: 'ignored'
		);

		$this->assertSame('SUM(A1:A2)', $cells[0]['formula']);
		$this->assertSame('3', $cells[0]['value'], 'the cached value is still reported');
	}//end testAFormulaCellReportsItsFormula()

	/**
	 * An empty cell with no formula is not reported as a cell at all.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.2
	 */
	public function testEmptyCellsAreNotReported(): void {
		$codec = new XlsxSpreadsheetCodec($this->io($this->sharedStrings()));

		$cells = $codec->readCells(
			xml: '<row><c r="A1"/><c r="B1"><v>7</v></c></row>',
			packageBytes: 'ignored'
		);

		$this->assertCount(1, $cells);
		$this->assertSame('Sheet1!B1', $cells[0]['cell']);
	}//end testEmptyCellsAreNotReported()

	/**
	 * Writing a cell replaces that cell and leaves its neighbour intact.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.2
	 */
	public function testWritingACellLeavesItsNeighbourAlone(): void {
		$codec = new XlsxSpreadsheetCodec($this->io($this->sharedStrings()));
		$xml = '<row><c r="A1" t="s"><v>0</v></c><c r="B1"><v>7</v></c></row>';

		$written = $codec->writeCell(xml: $xml, cell: 'Sheet1!A1', value: 'Service');
		$cells = array_column($codec->readCells(xml: $written, packageBytes: 'ignored'), 'value', 'cell');

		$this->assertSame('Service', $cells['Sheet1!A1']);
		$this->assertSame('7', $cells['Sheet1!B1'], 'the neighbouring cell must be untouched');
	}//end testWritingACellLeavesItsNeighbourAlone()

	/**
	 * Writing a cell that is not in the sheet is refused.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.2
	 */
	public function testWritingAnAbsentCellIsRefused(): void {
		$codec = new XlsxSpreadsheetCodec($this->io($this->sharedStrings()));

		$this->expectException(RuntimeException::class);

		$codec->writeCell(xml: '<row><c r="A1"><v>1</v></c></row>', cell: 'Sheet1!Z99', value: 'nope');
	}//end testWritingAnAbsentCellIsRefused()

	/**
	 * The codec claims the OOXML spreadsheet extension and not the ODF one.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.1
	 */
	public function testItSupportsOnlyItsOwnFormat(): void {
		$codec = new XlsxSpreadsheetCodec($this->io($this->sharedStrings()));

		$this->assertTrue($codec->supports(extension: 'xlsx'));
		$this->assertFalse($codec->supports(extension: 'ods'));
		$this->assertFalse($codec->supports(extension: 'docx'));
	}//end testItSupportsOnlyItsOwnFormat()
}//end class
