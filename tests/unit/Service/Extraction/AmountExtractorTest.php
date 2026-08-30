<?php

/**
 * Unit tests for AmountExtractor
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service\Extraction
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/financial-document-field-extraction/specs/financial-document-field-extraction/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Extraction;

use OCA\Filinq\Service\Extraction\AmountExtractor;
use PHPUnit\Framework\TestCase;

/**
 * Tests for AmountExtractor.
 */
class AmountExtractorTest extends TestCase {

	private AmountExtractor $extractor;

	/**
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->extractor = new AmountExtractor();

	}//end setUp()

	/**
	 * Dutch grouping "1.234,56" parses to 1234.56.
	 *
	 * @return void
	 */
	public function testDutchGroupingParsed(): void {
		$this->assertSame(1234.56, $this->extractor->parseAmount('1.234,56'));

	}//end testDutchGroupingParsed()

	/**
	 * Anglo grouping "1,234.56" parses to the same numeric value.
	 *
	 * @return void
	 */
	public function testAngloGroupingParsesToSameValue(): void {
		$this->assertSame(1234.56, $this->extractor->parseAmount('1,234.56'));

	}//end testAngloGroupingParsesToSameValue()

	/**
	 * A simple Dutch decimal-comma amount ("100,00") parses correctly.
	 *
	 * @return void
	 */
	public function testSimpleDecimalCommaParsed(): void {
		$this->assertSame(100.00, $this->extractor->parseAmount('100,00'));

	}//end testSimpleDecimalCommaParsed()

	/**
	 * A currency-marked labelled amount ("Totaal € 1.234,56") is extracted.
	 *
	 * @return void
	 */
	public function testLabelledEuroAmountExtracted(): void {
		$result = $this->extractor->extractLabelled('Totaal € 1.234,56', ['totaal']);

		$this->assertSame(1234.56, $result['value']);
		$this->assertGreaterThan(0.0, $result['confidence']);

	}//end testLabelledEuroAmountExtracted()

	/**
	 * A longer label ("Subtotaal") never falsely matches the shorter label
	 * ("totaal") as a substring.
	 *
	 * @return void
	 */
	public function testLabelBoundaryPreventsSubstringMatch(): void {
		$result = $this->extractor->extractLabelled('Subtotaal € 100,00', ['totaal']);

		$this->assertNull($result['value']);

	}//end testLabelBoundaryPreventsSubstringMatch()

	/**
	 * Unparseable text yields a null value.
	 *
	 * @return void
	 */
	public function testUnparseableAmountYieldsNull(): void {
		$this->assertNull($this->extractor->parseAmount('geen bedrag'));

	}//end testUnparseableAmountYieldsNull()
}//end class
