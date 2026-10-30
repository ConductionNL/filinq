<?php

/**
 * A cell write into a Calc-shaped ODS package keeps content.xml well-formed.
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
use DOMXPath;
use OCA\Filinq\Service\Editing\PackagePartIo;
use OCA\Filinq\Service\Editing\SpreadsheetCodec;
use PHPUnit\Framework\TestCase;

/**
 * Writes into tests/fixtures/ods/budget.ods through the spreadsheet codec and parses the result.
 *
 * @covers \OCA\Filinq\Service\Editing\OdsSpreadsheetCodec
 * @uses   \OCA\Filinq\Service\Editing\SpreadsheetCodec
 * @uses   \OCA\Filinq\Service\Editing\PackagePartIo
 * @uses   \OCA\Filinq\Service\Editing\XlsxSpreadsheetCodec
 */
class OdsWriteCellPackageTest extends TestCase {

	/**
	 * The written content.xml, parsed.
	 *
	 * @param array<int, array{cell: string, value: string}> $edits The edits.
	 *
	 * @return DOMXPath The parsed content.xml.
	 */
	private function write(array $edits): DOMXPath {
		$io = new PackagePartIo();
		$package = (string)file_get_contents(dirname(__DIR__, 3) . '/fixtures/ods/budget.ods');

		$written = (new SpreadsheetCodec($io))->applyCellEdits(packageBytes: $package, extension: 'ods', edits: $edits);

		$document = new DOMDocument();
		$this->assertTrue(
			@$document->loadXML($io->readPart(packageBytes: $written['bytes'], part: 'content.xml')),
			'the written content.xml must parse as XML'
		);

		return new DOMXPath($document);

	}//end write()

	/**
	 * The fixture parses before any write, so a failure below is the write's.
	 *
	 * @return void
	 */
	public function testTheTemplateIsWellFormedBeforeAWrite(): void {
		$document = new DOMDocument();
		$io = new PackagePartIo();

		$this->assertTrue($document->loadXML($io->readPart(
			packageBytes: (string)file_get_contents(dirname(__DIR__, 3) . '/fixtures/ods/budget.ods'),
			part: 'content.xml'
		)));

	}//end testTheTemplateIsWellFormedBeforeAWrite()

	/**
	 * A written cell leaves well-formed XML with the sheet's, rows' and group's markup intact.
	 *
	 * @return void
	 */
	public function testAWriteKeepsTheSheetAndRowMarkup(): void {
		$xpath = $this->write(edits: [['cell' => 'Begroting!B2', 'value' => '100'], ['cell' => 'Begroting!A1', 'value' => 'Rol & naam']]);

		$table = $xpath->query('//table:table')->item(0);
		$this->assertSame('Begroting', $table->getAttribute('table:name'));
		$this->assertSame('ta1', $table->getAttribute('table:style-name'));
		$this->assertSame('', trim($xpath->evaluate('string(//table:table/text())')), 'no markup may leak into the sheet as text');

		$rows = $xpath->query('//table:table-row');
		$this->assertSame(3, $rows->length);
		foreach ($rows as $row) {
			$this->assertSame('ro1', $row->getAttribute('table:style-name'));
		}

		$this->assertSame('2', $rows->item(2)->getAttribute('table:number-rows-repeated'));
		$this->assertSame(2, $xpath->query('//table:table-row-group/table:table-row')->length);
		$this->assertSame(1, $xpath->query('//table:table-column[@table:style-name="co1"]')->length);
		$this->assertSame('100', $xpath->evaluate('string(//table:table-row-group/table:table-row[1]/table:table-cell[2])'));
		$this->assertSame('Rol & naam', $xpath->evaluate('string(//table:table-row[1]/table:table-cell[1])'));
		$this->assertSame('of:=[.B2]*40', $xpath->evaluate('string(//table:table-row-group/table:table-row[1]/table:table-cell[3]/@table:formula)'));

	}//end testAWriteKeepsTheSheetAndRowMarkup()
}//end class
