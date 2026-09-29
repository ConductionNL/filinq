<?php

/**
 * The entity search log schema: declared as its own processing activity, append-only, never the query.
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
 * @spec openspec/changes/entity-search/tasks.md#task-1.1
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Settings;

use PHPUnit\Framework\TestCase;

/**
 * Reads lib/Settings/filinq_register.json and filinq_mock_register.json.
 */
class EntitySearchLogSchemaTest extends TestCase {

	/**
	 * A descriptor file.
	 *
	 * @param string $file The file name.
	 *
	 * @return array<string, mixed> The descriptor.
	 */
	private function descriptor(string $file): array {
		return json_decode((string) file_get_contents(__DIR__ . '/../../../lib/Settings/' . $file), true, 512, JSON_THROW_ON_ERROR);

	}//end descriptor()

	/**
	 * The schema is on the register, and the register version moved.
	 *
	 * @return void
	 */
	public function testTheSchemaIsDeclaredAndTheRegisterMoved(): void {
		$register = $this->descriptor(file: 'filinq_register.json');

		$this->assertArrayHasKey('entitySearchLog', $register['components']['schemas']);
		$this->assertContains('entitySearchLog', $register['components']['registers']['filinq']['schemas']);
		$this->assertTrue(version_compare($register['info']['version'], '8.30.0', '>='));

	}//end testTheSchemaIsDeclaredAndTheRegisterMoved()

	/**
	 * The search is its own processing activity on the public-task ground, with reads logged.
	 *
	 * @return void
	 */
	public function testTheActivityIsDeclaredInThePlatformRegister(): void {
		$processing = $this->descriptor(file: 'filinq_register.json')['components']['schemas']['entitySearchLog']['x-openregister-processing'];

		$this->assertSame('filinq-entity-search', $processing['activity']);
		$this->assertSame('public-task', $processing['legalBasis']);
		$this->assertTrue($processing['logReads']);

	}//end testTheActivityIsDeclaredInThePlatformRegister()

	/**
	 * Nobody may change or delete a row, and only admins may read the log.
	 *
	 * @return void
	 */
	public function testTheLogIsAppendOnlyAndAdminRead(): void {
		$schema = $this->descriptor(file: 'filinq_register.json')['components']['schemas']['entitySearchLog'];

		$this->assertSame([], $schema['authorization']['update']);
		$this->assertSame([], $schema['authorization']['delete']);
		$this->assertSame(['admin'], $schema['authorization']['read']);
		$this->assertFalse($schema['searchable']);

	}//end testTheLogIsAppendOnlyAndAdminRead()

	/**
	 * No field can hold a query: the digest takes only a sha256, and there is no query field.
	 *
	 * @return void
	 */
	public function testNoFieldCanHoldTheQuery(): void {
		$properties = $this->descriptor(file: 'filinq_register.json')['components']['schemas']['entitySearchLog']['properties'];

		$this->assertArrayNotHasKey('query', $properties);
		$this->assertSame('^([0-9a-f]{64})?$', $properties['queryDigest']['pattern']);
		$this->assertSame(1, preg_match('/' . $properties['queryDigest']['pattern'] . '/', hash('sha256', 'jan de vries')));
		$this->assertSame(0, preg_match('/' . $properties['queryDigest']['pattern'] . '/', '123456782'));

	}//end testNoFieldCanHoldTheQuery()

	/**
	 * Three demo rows, none of which carries a raw value.
	 *
	 * @return void
	 */
	public function testThreeDemoRowsCarryDigestsOnly(): void {
		$rows = array_values(
			array_filter(
				$this->descriptor(file: 'filinq_mock_register.json')['components']['objects'],
				static fn (array $row): bool => ($row['@self']['schema'] ?? '') === 'entitySearchLog'
			)
		);

		$this->assertCount(3, $rows);
		foreach ($rows as $row) {
			$this->assertSame(1, preg_match('/^([0-9a-f]{64})?$/', $row['queryDigest']));
			$this->assertStringNotContainsString('vries', strtolower((string) json_encode($row)));
		}

	}//end testThreeDemoRowsCarryDigestsOnly()
}//end class
