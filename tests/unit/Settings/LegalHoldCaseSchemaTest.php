<?php

/**
 * What the register declares about hold cases.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Settings
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-1.1
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Settings;

use OCA\Filinq\Tests\Unit\Service\LegalHold\LegalHoldDoubles;
use PHPUnit\Framework\TestCase;

/**
 * The legalHoldCase schema: lifecycle, bewaren, no archival annotation, demo rows.
 */
class LegalHoldCaseSchemaTest extends TestCase {
	use LegalHoldDoubles;

	/**
	 * A parsed settings file.
	 *
	 * @param string $file The file under lib/Settings.
	 *
	 * @return array<string, mixed> The descriptor.
	 */
	private function descriptor(string $file = 'filinq_register.json'): array {
		$parsed = json_decode((string) file_get_contents(__DIR__ . '/../../../lib/Settings/' . $file), true);
		$this->assertIsArray($parsed);

		return $parsed;

	}//end descriptor()

	/**
	 * The register lists the schema and bumps its version.
	 *
	 * @return void
	 */
	public function testTheRegisterCarriesTheSchema(): void {
		$register = $this->descriptor();
		$schema = $register['components']['schemas']['legalHoldCase'];

		$this->assertContains('legalHoldCase', $register['components']['registers']['filinq']['schemas']);
		$this->assertTrue(version_compare($register['info']['version'], '8.28.0', '>='));
		$this->assertTrue($schema['hardValidation']);
		$this->assertSame(['name', 'holdType', 'reason', 'status', 'placedBy', 'placedAt'], $schema['required']);
		$this->assertSame(['litigation', 'audit', 'woo-appeal', 'other'], $schema['properties']['holdType']['enum']);
		$this->assertSame(['admin'], $schema['authorization']['read']);

	}//end testTheRegisterCarriesTheSchema()

	/**
	 * Active to released is the only transition, and released is terminal.
	 *
	 * @return void
	 */
	public function testReleasedIsTerminal(): void {
		$lifecycle = $this->descriptor()['components']['schemas']['legalHoldCase']['x-openregister-lifecycle'];

		$this->assertSame('status', $lifecycle['field']);
		$this->assertSame('active', $lifecycle['initial']);
		$this->assertSame(['active', 'released'], array_keys($lifecycle['states']));
		$this->assertTrue($lifecycle['states']['released']['terminal']);
		$this->assertCount(1, $lifecycle['transitions']);
		$this->assertSame(['from' => 'active', 'to' => 'released'], array_intersect_key($lifecycle['transitions']['release'], ['from' => 1, 'to' => 1]));

	}//end testReleasedIsTerminal()

	/**
	 * A hold register is a record: bewaren, and never an archival annotation that could destroy it.
	 *
	 * @return void
	 */
	public function testHoldCasesAreKept(): void {
		$schema = $this->descriptor()['components']['schemas']['legalHoldCase'];

		$this->assertSame('bewaren', $schema['archive']['defaultNominatie']);
		$this->assertArrayNotHasKey('x-openregister-archival', $schema);

	}//end testHoldCasesAreKept()

	/**
	 * The demo cases validate against the schema.
	 *
	 * @return void
	 */
	public function testTheDemoCasesValidate(): void {
		$demo = array_values(
			array_filter(
				$this->descriptor(file: 'filinq_mock_register.json')['components']['objects'],
				static fn (array $row): bool => ($row['@self']['schema'] ?? '') === 'legalHoldCase'
			)
		);

		$this->assertCount(3, $demo);
		foreach ($demo as $row) {
			$this->assertValidCase(payload: $row);
		}

	}//end testTheDemoCasesValidate()
}//end class
