<?php

/**
 * Unit tests for MrzParser
 *
 * @package   OCA\Filinq\Tests\Unit\Service\Extraction

 * @category  Tests
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/extraction-employee-document/specs/employee-document-extraction/spec.md#REQ-EDE-001
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Extraction;

use DateTimeImmutable;
use OCA\Filinq\Service\Extraction\MrzParser;
use PHPUnit\Framework\TestCase;

/**
 * Tests for MrzParser, on the ICAO 9303 specimens.
 */
class MrzParserTest extends TestCase {

	/**
	 * The ICAO 9303 passport specimen.
	 */
	private const PASSPORT = "PASSPORT\nP<UTOERIKSSON<<ANNA<MARIA<<<<<<<<<<<<<<<<<<<\nL898902C36UTO7408122F1204159ZE184226B<<<<<10\n";

	/**
	 * The ICAO 9303 identity card specimen.
	 */
	private const CARD = "I<UTOD231458907<<<<<<<<<<<<<<<\n7408122F1204159UTO<<<<<<<<<<<6\nERIKSSON<<ANNA<MARIA<<<<<<<<<<\n";

	/**
	 * Read a zone.
	 *
	 * @param string $text The text.
	 *
	 * @return array<string, array{value: string, confidence: float}>|null
	 */
	private function read(string $text): ?array {
		return (new MrzParser())->parse(text: $text, today: new DateTimeImmutable('2026-11-15'));
	}//end read()

	/**
	 * A passport zone with valid check digits.
	 *
	 * @return void
	 */
	public function testAPassportZoneIsRead(): void {
		$fields = $this->read(text: self::PASSPORT);

		$this->assertSame(expected: 'ERIKSSON', actual: $fields['lastName']['value']);
		$this->assertSame(expected: 'ANNA MARIA', actual: $fields['firstName']['value']);
		$this->assertSame(expected: '1974-08-12', actual: $fields['dateOfBirth']['value']);
		$this->assertSame(expected: 'L898902C3', actual: $fields['documentNumber']['value']);
		$this->assertSame(expected: '2012-04-15', actual: $fields['expiryDate']['value']);
		$this->assertSame(expected: 'UTO', actual: $fields['nationality']['value']);
		$this->assertSame(expected: 'ZE184226B', actual: $fields['personalNumber']['value']);
		$this->assertSame(expected: [MrzParser::CHECKED], actual: array_values(array_unique(array_column($fields, 'confidence'))));
	}//end testAPassportZoneIsRead()

	/**
	 * An identity card zone with valid check digits.
	 *
	 * @return void
	 */
	public function testAnIdentityCardZoneIsRead(): void {
		$fields = $this->read(text: self::CARD);

		$this->assertSame(expected: 'ERIKSSON', actual: $fields['lastName']['value']);
		$this->assertSame(expected: 'D23145890', actual: $fields['documentNumber']['value']);
		$this->assertSame(expected: '1974-08-12', actual: $fields['dateOfBirth']['value']);
		$this->assertSame(expected: MrzParser::CHECKED, actual: $fields['firstName']['confidence']);
		$this->assertArrayNotHasKey(key: 'personalNumber', array: $fields);
	}//end testAnIdentityCardZoneIsRead()

	/**
	 * A misread digit lowers the confidence of every value.
	 *
	 * @return void
	 */
	public function testAMisreadDigitLowersTheConfidence(): void {
		$fields = $this->read(text: str_replace('L898902C36', 'L898902C46', self::PASSPORT));

		$this->assertSame(expected: 'L898902C4', actual: $fields['documentNumber']['value']);
		$this->assertSame(expected: [MrzParser::UNCHECKED], actual: array_values(array_unique(array_column($fields, 'confidence'))));
	}//end testAMisreadDigitLowersTheConfidence()

	/**
	 * Text without a zone answers null.
	 *
	 * @return void
	 */
	public function testTextWithoutAZoneIsNull(): void {
		$this->assertNull(actual: $this->read(text: "Arbeidsovereenkomst\nWerknemer: Jan de Boer\n"));
	}//end testTextWithoutAZoneIsNull()

	/**
	 * The check digit of the specimen's document number.
	 *
	 * @return void
	 */
	public function testTheCheckDigit(): void {
		$this->assertSame(expected: 6, actual: MrzParser::checkDigit(field: 'L898902C3'));
		$this->assertSame(expected: 2, actual: MrzParser::checkDigit(field: '740812'));
	}//end testTheCheckDigit()
}//end class
