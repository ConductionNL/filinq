<?php

/**
 * Unit tests for FieldValueCheck and TextPassage
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service\FieldFill
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\FieldFill;

use OCA\Filinq\Service\FieldFill\FieldValueCheck;
use OCA\Filinq\Service\FieldFill\TextPassage;
use PHPUnit\Framework\TestCase;

/**
 * Whether a value fits its property, and the passage it came from.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\FieldFill
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class FieldValueCheckAndPassageTest extends TestCase {

	/**
	 * Type, format, allowed values and range decide; OpenRegister's own keys do not.
	 *
	 * @return void
	 */
	public function testValuesAreCheckedAgainstTheProperty(): void {
		$check = new FieldValueCheck();

		$this->assertTrue($check->fits(value: '2027-03-01', property: ['type' => 'string', 'format' => 'date', 'visible' => true, 'required' => true]));
		$this->assertFalse($check->fits(value: 'next spring', property: ['type' => 'string', 'format' => 'date']));
		$this->assertFalse($check->fits(value: '5', property: ['type' => 'number']));
		$this->assertFalse($check->fits(value: 'closed', property: ['enum' => ['open']]));
		$this->assertFalse($check->fits(value: -1, property: ['type' => 'number', 'minimum' => 0]));
		$this->assertFalse($check->fits(value: null, property: []));
		$this->assertTrue($check->fits(value: ['a'], property: ['type' => 'array', 'items' => ['type' => 'string']]));
		$this->assertEquals((object)[], $check->valueSchema(property: ['title' => 'Naam']));

	}//end testValuesAreCheckedAgainstTheProperty()

	/**
	 * A value is found ignoring case and line breaks, with a passage of at most 200 characters.
	 *
	 * @return void
	 */
	public function testThePassageAroundAValue(): void {
		$passage = new TextPassage();
		$text    = str_repeat('a ', 200) . "Aanvrager: Stichting\nGroen Dorp, Utrecht. " . str_repeat('b ', 200);

		$found = $passage->around(text: $text, value: 'stichting groen dorp');
		$this->assertNotNull($found);
		$this->assertStringContainsString('Stichting Groen Dorp', $found);
		$this->assertLessThanOrEqual(TextPassage::MAX_LENGTH, mb_strlen($found));

		$this->assertSame('Bedrag 12500', $passage->around(text: 'Bedrag 12500', value: 12500));
		$this->assertNull($passage->around(text: $text, value: 'Amsterdam'));
		$this->assertNull($passage->around(text: $text, value: 'a'));
		$this->assertNull($passage->around(text: $text, value: true));
		$this->assertSame(TextPassage::MAX_LENGTH, mb_strlen((string)$passage->around(text: str_repeat('x', 300), value: str_repeat('x', 250))));

	}//end testThePassageAroundAValue()
}//end class
