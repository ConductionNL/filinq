<?php

/**
 * Unit tests for the mdtoSupplement schema (tmlo-mdto-metadata task 1.1, 1.2).
 *
 * @category Test
 * @package  OCA\Filinq\Tests\Unit\Settings
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/archive/2026-11-25-tmlo-mdto-metadata/specs/document-register/spec.md#requirement-mdtosupplement-schema-in-document-register-req-ddtmm-020
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Settings;

use PHPUnit\Framework\TestCase;

/**
 * The mdtoSupplement schema carries the MDTO attributes OpenRegister does not
 * model, never a retention-owned one, and lives as long as its record.
 */
class MdtoSupplementSchemaTest extends TestCase {

	/**
	 * The retention-owned core attributes no Filinq schema may copy.
	 */
	private const RETENTION_OWNED = ['waardering', 'archiefnominatie', 'bewaartermijn', 'archiefactiedatum', 'archiefstatus', 'classificatie'];

	/**
	 * The register file, decoded.
	 *
	 * @return array<string, mixed>
	 */
	private function descriptor(): array {
		return json_decode((string) file_get_contents(__DIR__ . '/../../../lib/Settings/filinq_register.json'), true);
	}//end descriptor()

	/**
	 * The mdtoSupplement schema.
	 *
	 * @return array<string, mixed>
	 */
	private function schema(): array {
		return $this->descriptor()['components']['schemas']['mdtoSupplement'];
	}//end schema()

	/**
	 * The schema is in the register, validated hard, with the fields and enums of design D1.
	 *
	 * @return void
	 */
	public function testTheSchemaIsInTheRegisterWithItsFields(): void {
		$this->assertContains('mdtoSupplement', $this->descriptor()['components']['registers']['filinq']['schemas']);
		$this->assertTrue($this->schema()['hardValidation']);
		$this->assertSame(
			[
				'objectRef', 'objectType', 'aggregatieniveau', 'omschrijving', 'taal', 'dekkingInTijdBegin', 'dekkingInTijdEind',
				'beperkingGebruik', 'betrokkene', 'archiefvormerOverride', 'proposals',
			],
			array_keys($this->schema()['properties'])
		);

		$properties = $this->schema()['properties'];
		$this->assertSame(['document', 'dossier'], $properties['objectType']['enum']);
		$this->assertSame(['archiefstuk', 'dossier'], $properties['aggregatieniveau']['enum']);
		$this->assertSame('nl', $properties['taal']['default']);
		$this->assertSame(
			['avg-persoonsgegevens', 'woo-uitzondering', 'auteursrecht', 'vertrouwelijk', 'overig'],
			$properties['beperkingGebruik']['items']['properties']['type']['enum']
		);
		$this->assertSame(['rol', 'naamRef'], array_keys($properties['betrokkene']['items']['properties']));
	}//end testTheSchemaIsInTheRegisterWithItsFields()

	/**
	 * No property, nested or not, copies a retention-owned core attribute, and there is no archival sweep.
	 *
	 * @return void
	 */
	public function testTheSchemaCopiesNoRetentionOwnedAttribute(): void {
		$names = [];
		array_walk_recursive(
			$this->schema()['properties'],
			static function (mixed $value, string|int $key) use (&$names): void {
				$names[] = (string) $key;
			}
		);
		$names = array_merge($names, array_keys($this->schema()['properties']));

		foreach (self::RETENTION_OWNED as $owned) {
			$this->assertNotContains($owned, $names, $owned . ' belongs to the OpenRegister retention block');
		}

		$this->assertArrayNotHasKey('x-openregister-archival', $this->schema());
	}//end testTheSchemaCopiesNoRetentionOwnedAttribute()

	/**
	 * A party is a reference: the pattern refuses a name written in clear.
	 *
	 * @return void
	 */
	public function testAPartyIsAReferenceNeverANameInClear(): void {
		$pattern = '#' . $this->schema()['properties']['betrokkene']['items']['properties']['naamRef']['pattern'] . '#';

		$this->assertSame(1, preg_match($pattern, '6f1c2d3e-4b5a-4c6d-8e7f-0a1b2c3d4e5f'));
		$this->assertSame(1, preg_match($pattern, 'https://contacts.example.nl/contact/42'));
		$this->assertSame(0, preg_match($pattern, 'Jan Jansen'));
	}//end testAPartyIsAReferenceNeverANameInClear()

	/**
	 * Nobody deletes a supplement on its own: it goes with its record.
	 *
	 * @return void
	 */
	public function testNobodyDeletesASupplementOnItsOwn(): void {
		$this->assertSame([], $this->schema()['authorization']['delete']);
		$this->assertSame(['filinq-policy-admins'], $this->schema()['authorization']['update']);
	}//end testNobodyDeletesASupplementOnItsOwn()

	/**
	 * The demo supplement validates against the schema: required fields, enums, date shapes.
	 *
	 * @return void
	 */
	public function testTheDemoSupplementValidates(): void {
		$seeds = array_values(
			array_filter(
				$this->descriptor()['components']['objects'],
				static fn (array $row): bool => ($row['@self']['schema'] ?? '') === 'mdtoSupplement'
			)
		);
		$this->assertCount(1, $seeds);
		$seed       = $seeds[0];
		$properties = $this->schema()['properties'];

		$this->assertSame('00000000-0000-0000-0000-000000000001', $seed['objectRef']);
		foreach ($this->schema()['required'] as $required) {
			$this->assertArrayHasKey($required, $seed);
		}

		foreach (array_keys($seed) as $field) {
			if ($field !== '@self') {
				$this->assertArrayHasKey($field, $properties, $field . ' is not a schema property');
			}
		}

		$this->assertContains($seed['aggregatieniveau'], $properties['aggregatieniveau']['enum']);
		$this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $seed['dekkingInTijdBegin']);
		$this->assertMatchesRegularExpression('/' . $properties['taal']['pattern'] . '/', $seed['taal']);
		$this->assertSame('avg-persoonsgegevens', $seed['beperkingGebruik'][0]['type']);
		$this->assertFalse($seed['beperkingGebruik'][0]['proposed']);
	}//end testTheDemoSupplementValidates()

	/**
	 * The register version is bumped so the boot import picks the schema up.
	 *
	 * @return void
	 */
	public function testTheRegisterVersionIsBumped(): void {
		$this->assertTrue(version_compare($this->descriptor()['info']['version'], '8.54.0', '>'));
	}//end testTheRegisterVersionIsBumped()
}//end class
