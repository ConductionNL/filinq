<?php

/**
 * Registry answers: the name and address are read out of a Handelsregister
 * row and a BRP person, in each shape the registries answer, and nothing
 * else leaves.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Intake
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Intake;

use OCA\Filinq\Service\Intake\RegistryAnswer;
use PHPUnit\Framework\TestCase;

/**
 * @covers \OCA\Filinq\Service\Intake\RegistryAnswer
 */
class RegistryAnswerTest extends TestCase {

	/**
	 * A KvK row with a domestic address block reads as name and visiting address.
	 *
	 * @return void
	 */
	public function testACompanyWithADomesticAddress(): void {
		$this->assertSame(
			['name' => 'Voorbeeld B.V.', 'address' => 'Voorbeeldstraat 1b, 1234AB Voorbeeldstad'],
			RegistryAnswer::company(
				row: [
					'naam' => ' Voorbeeld B.V. ',
					'adres' => ['binnenlandsAdres' => ['straatnaam' => 'Voorbeeldstraat', 'huisnummer' => 1, 'huisletter' => 'b', 'postcode' => '1234AB', 'plaats' => 'Voorbeeldstad']],
				]
			)
		);

	}//end testACompanyWithADomesticAddress()

	/**
	 * An older row carries the street and place on itself, and only a place is still a line.
	 *
	 * @return void
	 */
	public function testACompanyWithAFlatRow(): void {
		$this->assertSame(
			['name' => 'Handelsnaam', 'address' => 'Kerkweg 3, Dorp'],
			RegistryAnswer::company(row: ['handelsnaam' => 'Handelsnaam', 'straatnaam' => 'Kerkweg', 'huisnummer' => '3', 'plaats' => 'Dorp'])
		);
		$this->assertSame(['name' => '', 'address' => 'Dorp'], RegistryAnswer::company(row: ['naam' => ['not', 'text'], 'plaats' => 'Dorp']));

	}//end testACompanyWithAFlatRow()

	/**
	 * A BRP person keeps the full name and residential address, nothing else.
	 *
	 * @return void
	 */
	public function testAPersonKeepsNameAndAddressOnly(): void {
		$this->assertSame(
			['name' => 'Jan de Vries', 'address' => 'Dorpsstraat 12a, 9999ZZ Ergens'],
			RegistryAnswer::person(
				person: [
					'burgerservicenummer' => '111222333',
					'geboorte' => ['datum' => ['datum' => '1980-01-01']],
					'naam' => ['volledigeNaam' => 'Jan de Vries', 'voornamen' => 'Jan'],
					'verblijfplaats' => ['verblijfadres' => ['officieleStraatnaam' => 'Dorpsstraat', 'huisnummer' => 12, 'huisletter' => 'a', 'postcode' => '9999ZZ', 'woonplaats' => 'Ergens']],
				]
			)
		);

	}//end testAPersonKeepsNameAndAddressOnly()

	/**
	 * Without a full name the parts are joined; without an address block the address lines are used.
	 *
	 * @return void
	 */
	public function testAPersonFromNamePartsAndAddressLines(): void {
		$this->assertSame(
			['name' => 'Anna van Dijk', 'address' => 'Laan 4, 1000AA Stad'],
			RegistryAnswer::person(
				person: [
					'naam' => ['voornamen' => 'Anna', 'voorvoegsel' => 'van', 'geslachtsnaam' => 'Dijk'],
					'verblijfplaats' => ['adressering' => ['adresregel1' => 'Laan 4', 'adresregel2' => '1000AA Stad']],
				]
			)
		);
		$this->assertSame(['name' => '', 'address' => ''], RegistryAnswer::person(person: []));

	}//end testAPersonFromNamePartsAndAddressLines()
}//end class
