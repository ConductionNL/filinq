<?php

/**
 * Unit tests for OrganisationSettingsInput
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use InvalidArgumentException;
use OCA\Filinq\Service\OrganisationSettingsInput;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Only the schema's fields reach the store, each inside the schema's range.
 *
 * @covers \OCA\Filinq\Service\OrganisationSettingsInput
 *
 * @spec openspec/changes/multi-tenant-hardening/specs/admin-settings/spec.md#requirement-per-organisation-settings-are-stored-as-organisationsettings-objects-req-ddmth-007
 */
class OrganisationSettingsInputTest extends TestCase {

	private const MAX = ['name' => 10, 'notes' => 20];

	/**
	 * Empty input inherits everything and takes the organisation's name.
	 *
	 * @return void
	 */
	public function testEmptyInputInheritsEverything(): void {
		$this->assertSame(
			['name' => 'Demostad', 'consentPeriodDays' => null, 'anonymizationProfile' => null, 'defaultHuisstijl' => null, 'notes' => null],
			OrganisationSettingsInput::fields(input: [], defaultName: ' Demostad ', maxLength: self::MAX)
		);
	}//end testEmptyInputInheritsEverything()

	/**
	 * Without a name or an organisation name, a neutral name is stored (the schema requires one).
	 *
	 * @return void
	 */
	public function testANameIsAlwaysStored(): void {
		$this->assertSame('Organisation', OrganisationSettingsInput::fields(input: ['name' => '  '], defaultName: '', maxLength: self::MAX)['name']);
	}//end testANameIsAlwaysStored()

	/**
	 * Unknown fields, an organisation stamp among them, never reach the store.
	 *
	 * @return void
	 */
	public function testUnknownFieldsAreDropped(): void {
		$fields = OrganisationSettingsInput::fields(
			input: ['organisation' => 'x', '@self' => ['organisation' => 'x'], 'owner' => 'bob', 'consentPeriodDays' => 42],
			defaultName: 'A',
			maxLength: self::MAX
		);

		$this->assertSame(OrganisationSettingsInput::FIELDS, array_keys($fields));
		$this->assertSame(42, $fields['consentPeriodDays']);
	}//end testUnknownFieldsAreDropped()

	/**
	 * Periods at the edges and as digit strings are accepted.
	 *
	 * @return void
	 */
	public function testPeriodBoundsAreAccepted(): void {
		$this->assertSame(1, OrganisationSettingsInput::fields(input: ['consentPeriodDays' => '1'], defaultName: 'A', maxLength: self::MAX)['consentPeriodDays']);
		$this->assertSame(365, OrganisationSettingsInput::fields(input: ['consentPeriodDays' => 365], defaultName: 'A', maxLength: self::MAX)['consentPeriodDays']);
	}//end testPeriodBoundsAreAccepted()

	/**
	 * Values a field must refuse.
	 *
	 * @return array<string, array{0: array<string, mixed>, 1: string}>
	 */
	public static function refusedInputs(): array {
		return [
			'period zero'             => [['consentPeriodDays' => 0], 'between 1 and 365'],
			'period over a year'      => [['consentPeriodDays' => '366'], 'between 1 and 365'],
			'period fraction'         => [['consentPeriodDays' => '4.5'], 'whole number'],
			'period negative text'    => [['consentPeriodDays' => '-3'], 'whole number'],
			'period float'            => [['consentPeriodDays' => 30.0], 'whole number'],
			'profile not a list'      => [['anonymizationProfile' => 'PERSON'], 'lists the categories'],
			'profile list is a map'   => [['anonymizationProfile' => ['anonymize' => ['a' => 'PERSON']]], 'lists the categories'],
			'profile lower category'  => [['anonymizationProfile' => ['anonymize' => ['person']]], 'entity category'],
			'profile in both lists'   => [['anonymizationProfile' => ['anonymize' => ['BSN'], 'keepVisible' => ['BSN']]], 'both anonymised'],
			'huisstijl not a uuid'    => [['defaultHuisstijl' => 'demostad'], 'huisstijl reference'],
			'huisstijl not text'      => [['defaultHuisstijl' => 12], 'huisstijl reference'],
			'name too long'           => [['name' => str_repeat('x', 11)], 'at most 10'],
			'notes not text'          => [['notes' => ['x']], 'notes is text'],
			'notes too long'          => [['notes' => str_repeat('é', 21)], 'at most 20'],
		];
	}//end refusedInputs()

	/**
	 * Out-of-range values are refused with a message naming the rule.
	 *
	 * @param array<string, mixed> $input   The input.
	 * @param string               $message Part of the expected message.
	 *
	 * @return void
	 */
	#[DataProvider('refusedInputs')]
	public function testOutOfRangeValuesAreRefused(array $input, string $message): void {
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage($message);
		OrganisationSettingsInput::fields(input: $input, defaultName: 'A', maxLength: self::MAX);
	}//end testOutOfRangeValuesAreRefused()

	/**
	 * A profile is stored with duplicates folded and the keep list named keepVisible.
	 *
	 * @return void
	 */
	public function testAProfileIsNormalised(): void {
		$fields = OrganisationSettingsInput::fields(
			input: ['anonymizationProfile' => ['anonymize' => ['PERSON', 'BSN', 'PERSON'], 'keepVisible' => ['ORGANIZATION']]],
			defaultName: 'A',
			maxLength: self::MAX
		);

		$this->assertSame(['anonymize' => ['PERSON', 'BSN'], 'keepVisible' => ['ORGANIZATION']], $fields['anonymizationProfile']);
	}//end testAProfileIsNormalised()

	/**
	 * A profile with two empty lists inherits the instance profile.
	 *
	 * @return void
	 */
	public function testAnEmptyProfileInherits(): void {
		$fields = OrganisationSettingsInput::fields(
			input: ['anonymizationProfile' => ['anonymize' => [], 'keepVisible' => []]],
			defaultName: 'A',
			maxLength: self::MAX
		);

		$this->assertNull($fields['anonymizationProfile']);
	}//end testAnEmptyProfileInherits()

	/**
	 * A huisstijl reference is stored lower case and trimmed.
	 *
	 * @return void
	 */
	public function testAHuisstijlReferenceIsNormalised(): void {
		$fields = OrganisationSettingsInput::fields(
			input: ['defaultHuisstijl' => ' AAAAAAAA-AAAA-4AAA-8AAA-AAAAAAAAAAAA '],
			defaultName: 'A',
			maxLength: self::MAX
		);

		$this->assertSame('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', $fields['defaultHuisstijl']);
	}//end testAHuisstijlReferenceIsNormalised()
}//end class
