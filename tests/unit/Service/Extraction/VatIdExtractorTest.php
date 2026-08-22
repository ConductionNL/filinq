<?php

/**
 * Unit tests for VatIdExtractor
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

use OCA\Filinq\Service\Extraction\VatIdExtractor;
use PHPUnit\Framework\TestCase;

/**
 * Tests for VatIdExtractor.
 */
class VatIdExtractorTest extends TestCase {

	private VatIdExtractor $extractor;

	/**
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->extractor = new VatIdExtractor();

	}//end setUp()

	/**
	 * "NL001234567B01" is recognised as the BTW-nummer.
	 *
	 * @return void
	 */
	public function testBtwNummerRecognised(): void {
		$result = $this->extractor->extract('BTW-nummer: NL001234567B01');

		$this->assertSame('NL001234567B01', $result['value']);
		$this->assertGreaterThan(0.0, $result['confidence']);

	}//end testBtwNummerRecognised()

	/**
	 * Text without a BTW-nummer-shaped token yields a null value.
	 *
	 * @return void
	 */
	public function testNoMatchYieldsNull(): void {
		$result = $this->extractor->extract('Geen BTW gegevens hier.');

		$this->assertNull($result['value']);
		$this->assertSame(0.0, $result['confidence']);

	}//end testNoMatchYieldsNull()
}//end class
