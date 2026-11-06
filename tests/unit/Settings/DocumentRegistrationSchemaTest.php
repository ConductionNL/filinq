<?php

/**
 * The post register's entry, and the number it is given.
 *
 * 🔴 THE NUMBER MUST NOT COME FROM A COUNTER IN FILINQ. Two registrations made
 * in the same second would race for it, and one would silently reuse a number
 * already issued — in a numbered series that is meant to be legible end to end,
 * a duplicate is worse than a gap. So it is declared through the platform's
 * `x-openregister-generated`, which reserves atomically.
 *
 * 🔑 AND THE SERIES IS INSTANCE-WIDE, NOT PER UNIT, WHICH IS NOT WHAT THE TASK
 * ASKED FOR. OpenRegister builds the sequence scope as the literal sequence name
 * plus the period, with no placeholder substitution, so `filinq-post-{unit}`
 * would have created ONE shared series literally named that. Shipping it would
 * have read as per-unit and behaved as shared. Asserted here so the difference
 * cannot be forgotten.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Settings
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Settings;

use PHPUnit\Framework\TestCase;

/**
 * `documentRegistration` in the shipped register.
 *
 * @coversNothing
 */
class DocumentRegistrationSchemaTest extends TestCase {

	/**
	 * The schema as shipped.
	 *
	 * @return array<string, mixed> The schema.
	 */
	private function schema(): array {
		$path = dirname(__DIR__, 3) . '/lib/Settings/filinq_register.json';
		$this->assertFileExists($path);

		$register = json_decode((string)file_get_contents($path), true);
		$this->assertIsArray($register, 'the register must be valid JSON');

		return ($register['components']['schemas']['documentRegistration'] ?? []);
	}//end schema()

	/**
	 * The entry carries what a post register entry is.
	 *
	 * @return void
	 */
	public function testTheRegistrationCarriesItsParts(): void {
		$properties = ($this->schema()['properties'] ?? []);

		foreach (
			['direction', 'registeredAt', 'unit', 'document', 'registrationNumber', 'answers', 'withdrawnReason']
			as $property
		) {
			$this->assertArrayHasKey($property, $properties);
		}
	}//end testTheRegistrationCarriesItsParts()

	/**
	 * A document either came in or went out. There is no third value.
	 *
	 * @return void
	 */
	public function testDirectionIsInboundOrOutboundAndNothingElse(): void {
		$this->assertSame(
			['inbound', 'outbound'],
			($this->schema()['properties']['direction']['enum'] ?? [])
		);
	}//end testDirectionIsInboundOrOutboundAndNothingElse()

	/**
	 * 🔴 THE NUMBER COMES FROM THE PLATFORM SEQUENCE, NOT FROM FILINQ.
	 *
	 * Verified against OpenRegister's own `GeneratedIdentifierDeclaration`
	 * while this was written: the declaration is accepted and renders
	 * `2026-00042` for sequence value 42.
	 *
	 * @return void
	 */
	public function testTheNumberIsDrawnFromAPlatformSequence(): void {
		$generated = ($this->schema()['properties']['registrationNumber']['x-openregister-generated'] ?? []);

		$this->assertSame('{year}-{seq:5}', ($generated['format'] ?? null));
		$this->assertSame(
			'year',
			($generated['resetOn'] ?? null),
			'A post register restarts each year, or last year\'s numbers repeat.'
		);
	}//end testTheNumberIsDrawnFromAPlatformSequence()

	/**
	 * 🔑 THE SEQUENCE NAME IS LITERAL, WITH NO PLACEHOLDER.
	 *
	 * OpenRegister builds the scope key as `'gen:' . sequence() . '|' . period`,
	 * substituting nothing. A name like `filinq-post-{unit}` would create one
	 * series literally called that, shared by every unit, while reading in the
	 * schema as though each unit had its own. This test is what stops that being
	 * added back as an apparent improvement.
	 *
	 * @return void
	 */
	public function testTheSequenceNameCarriesNoPlaceholder(): void {
		$sequence = (string)($this->schema()['properties']['registrationNumber']['x-openregister-generated']['sequence'] ?? '');

		$this->assertNotSame('', $sequence);
		$this->assertStringNotContainsString(
			'{',
			$sequence,
			'OpenRegister substitutes nothing in a sequence name, so a placeholder makes one shared series that reads as many.'
		);
	}//end testTheSequenceNameCarriesNoPlaceholder()

	/**
	 * The discharge is a link, never a status on the inbound entry.
	 *
	 * An inbound entry carrying its own "answered" flag can be set without an
	 * answer existing, which is the failure REQ-DIO-02 names.
	 *
	 * @return void
	 */
	public function testTheDischargeIsALinkAndNotAStatus(): void {
		$properties = ($this->schema()['properties'] ?? []);

		$this->assertArrayHasKey('answers', $properties);
		$this->assertArrayNotHasKey('answered', $properties);
		$this->assertArrayNotHasKey('dischargedAt', $properties);
	}//end testTheDischargeIsALinkAndNotAStatus()
}//end class
