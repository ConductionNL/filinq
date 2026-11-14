<?php

/**
 * Golden output of OdsSpreadsheetCodec::writeCell.
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

use OCA\Filinq\Service\Editing\OdsSpreadsheetCodec;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Every write in tests/fixtures/ods/write-cell-golden.json yields its recorded XML byte for byte.
 *
 * @covers \OCA\Filinq\Service\Editing\OdsSpreadsheetCodec
 */
class OdsWriteCellGoldenTest extends TestCase {

	/**
	 * The recorded writes.
	 *
	 * @return array<string, array{0: string, 1: string, 2: string, 3: string}> [xml, cell, value, expected] per case.
	 */
	public static function writes(): array {
		$golden = json_decode((string)file_get_contents(dirname(__DIR__, 3) . '/fixtures/ods/write-cell-golden.json'), true);
		$cases = [];
		foreach ($golden['cases'] as $case) {
			$cases[$case['cell']] = [$golden['xml'], $case['cell'], $case['value'], $case['expected']];
		}

		return $cases;

	}//end writes()

	/**
	 * A write produces the recorded XML.
	 *
	 * @param string $xml      The content.xml.
	 * @param string $cell     The address.
	 * @param string $value    The literal.
	 * @param string $expected The recorded result.
	 *
	 * @return void
	 */
	#[DataProvider('writes')]
	public function testAWriteMatchesTheRecordedXml(string $xml, string $cell, string $value, string $expected): void {
		$this->assertSame($expected, (new OdsSpreadsheetCodec())->writeCell(xml: $xml, cell: $cell, value: $value));

	}//end testAWriteMatchesTheRecordedXml()

	/**
	 * An absent cell is refused with a message naming it.
	 *
	 * @return void
	 */
	public function testAnAbsentCellIsRefusedByName(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Cell Two!C1 does not exist in this sheet. Only populated cells can be written.');

		(new OdsSpreadsheetCodec())->writeCell(xml: self::writes()['Two!A1'][0], cell: 'Two!C1', value: 'x');

	}//end testAnAbsentCellIsRefusedByName()
}//end class
