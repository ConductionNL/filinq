<?php

/**
 * Unit tests for XmlBlockScanner on a truncated opening tag.
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

use OCA\Filinq\Service\Editing\XmlBlockScanner;
use PHPUnit\Framework\TestCase;

/**
 * A truncated part yields the complete blocks before the truncation and nothing past it.
 *
 * @covers \OCA\Filinq\Service\Editing\XmlBlockScanner
 */
class XmlBlockScannerTruncationTest extends TestCase {

	/**
	 * An opening tag that never closes stops the scan.
	 *
	 * @return void
	 */
	public function testAnUnterminatedOpeningTagStopsTheScan(): void {
		$xml = '<w:p>one</w:p><w:p w:rsid="1"';

		$this->assertSame([[0, 14]], (new XmlBlockScanner())->spans(xml: $xml, tag: 'w:p'));
	}//end testAnUnterminatedOpeningTagStopsTheScan()
}//end class
