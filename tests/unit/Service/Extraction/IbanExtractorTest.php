<?php

/**
 * Unit tests for IbanExtractor
 *
 * @category  Tests
 * @package   OCA\DocuDesk\Tests\Unit\Service\Extraction
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/financial-document-field-extraction/specs/financial-document-field-extraction/spec.md
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Tests\Unit\Service\Extraction;

use OCA\DocuDesk\Service\Extraction\IbanExtractor;
use PHPUnit\Framework\TestCase;

/**
 * Tests for IbanExtractor.
 */
class IbanExtractorTest extends TestCase {

	private IbanExtractor $extractor;

	/**
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->extractor = new IbanExtractor();

	}//end setUp()

	/**
	 * A checksum-valid IBAN is accepted with high confidence.
	 *
	 * @return void
	 */
	public function testValidIbanAcceptedHighConfidence(): void {
		$result = $this->extractor->extract('Betaal aan NL91ABNA0417164300 binnen 14 dagen.');

		$this->assertSame('NL91ABNA0417164300', $result['value']);
		$this->assertGreaterThanOrEqual(0.9, $result['confidence']);

	}//end testValidIbanAcceptedHighConfidence()

	/**
	 * A mod-97-invalid IBAN-shaped string is rejected — no value returned.
	 *
	 * @return void
	 */
	public function testInvalidIbanRejected(): void {
		// Same shape as a valid IBAN but with the checksum digits tampered.
		$result = $this->extractor->extract('Betaal aan NL00ABNA0417164300 binnen 14 dagen.');

		$this->assertNull($result['value']);
		$this->assertSame(0.0, $result['confidence']);

	}//end testInvalidIbanRejected()

	/**
	 * No IBAN-shaped text at all yields a null value.
	 *
	 * @return void
	 */
	public function testNoCandidateYieldsNull(): void {
		$result = $this->extractor->extract('Geen bankgegevens in deze tekst.');

		$this->assertNull($result['value']);
		$this->assertSame(0.0, $result['confidence']);

	}//end testNoCandidateYieldsNull()

	/**
	 * When an invalid candidate precedes a valid one, the valid one still wins.
	 *
	 * @return void
	 */
	public function testSkipsInvalidCandidateAndFindsValidOne(): void {
		$text = 'Oud: NL00ABNA0417164300. Nieuw: NL91ABNA0417164300.';
		$result = $this->extractor->extract($text);

		$this->assertSame('NL91ABNA0417164300', $result['value']);

	}//end testSkipsInvalidCandidateAndFindsValidOne()
}//end class
