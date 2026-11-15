<?php

/**
 * Unit tests for OdsSpreadsheetCodec::writeCell on repeated-row runs and unqualified addresses.
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

use OCA\Filinq\Service\Editing\OdsSpreadsheetCodec;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Splitting a repeated-row run in its middle keeps both halves as runs.
 *
 * @covers \OCA\Filinq\Service\Editing\OdsSpreadsheetCodec
 */
class OdsSpreadsheetCodecRowRunTest extends TestCase {

	/**
	 * A sheet whose only row element stands for five identical rows.
	 *
	 * @return string The content.xml body.
	 */
	private function content(): string {
		return '<office:spreadsheet><table:table table:name="Plan">'
			. '<table:table-row table:style-name="ro1" table:number-rows-repeated="5">'
			. '<table:table-cell office:value-type="string"><text:p>x</text:p></table:table-cell>'
			. '</table:table-row></table:table></office:spreadsheet>';
	}//end content()

	/**
	 * Writing row 3 of five leaves a run of two before and a run of two after.
	 *
	 * @return void
	 */
	public function testWritingInsideARunKeepsRunsOnBothSides(): void {
		$xml = (new OdsSpreadsheetCodec())->writeCell(xml: $this->content(), cell: 'Plan!a3', value: 'new');

		$run = '<table:table-row table:style-name="ro1" table:number-rows-repeated="2">'
			. '<table:table-cell office:value-type="string"><text:p>x</text:p></table:table-cell></table:table-row>';
		$single = '<table:table-row table:style-name="ro1">'
			. '<table:table-cell office:value-type="string"><text:p>new</text:p></table:table-cell></table:table-row>';

		$this->assertSame(
			'<office:spreadsheet><table:table table:name="Plan">' . $run . $single . $run . '</table:table></office:spreadsheet>',
			$xml
		);
	}//end testWritingInsideARunKeepsRunsOnBothSides()

	/**
	 * An address without a sheet matches no sheet-qualified cell, so the write is refused.
	 *
	 * @return void
	 */
	public function testAnAddressWithoutASheetIsRefused(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Cell a1 does not exist in this sheet. Only populated cells can be written.');

		(new OdsSpreadsheetCodec())->writeCell(xml: $this->content(), cell: 'a1', value: 'new');
	}//end testAnAddressWithoutASheetIsRefused()
}//end class
