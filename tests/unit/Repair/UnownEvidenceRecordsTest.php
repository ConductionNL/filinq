<?php

/**
 * Unit tests for UnownEvidenceRecords.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Repair
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/evidence-rows-owned-by-the-system/specs/consumer-schema-authorization-audit/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Repair;

use OCA\Filinq\Repair\UnownEvidenceRecords;
use OCP\DB\IResult;
use OCP\IAppConfig;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use PHPUnit\Framework\TestCase;

/**
 * Existing evidence rows go to the system identity; nothing else changes.
 */
class UnownEvidenceRecordsTest extends TestCase {

	/**
	 * The step over a database with the given tables and schema rows.
	 *
	 * @param list<string>                     $tables  Tables that exist, without prefix.
	 * @param list<array{id: int, slug: string}> $schemas Schema rows the slug query returns.
	 * @param array<int, array<int, mixed>>    $updates Captured UPDATE statements and params.
	 *
	 * @return UnownEvidenceRecords
	 */
	private function step(array $tables, array $schemas, array &$updates): UnownEvidenceRecords {
		$register = $this->createMock(originalClassName: IResult::class);
		$register->method('fetch')->willReturn(['id' => 4]);
		$schemaRows = $this->createMock(originalClassName: IResult::class);
		$schemaRows->method('fetchAll')->willReturn($schemas);

		$db = $this->createMock(originalClassName: IDBConnection::class);
		$db->method('tableExists')->willReturnCallback(static fn (string $table): bool => in_array(needle: $table, haystack: $tables, strict: true));
		$db->method('executeQuery')->willReturnOnConsecutiveCalls($register, $schemaRows);
		$db->method('executeStatement')->willReturnCallback(
			function (string $sql, array $params) use (&$updates): int {
				$updates[] = [$sql, $params];
				return 2;
			}
		);

		$config = $this->createMock(originalClassName: IAppConfig::class);
		$config->method('getValueString')->willReturnCallback(static fn (string $app, string $key, string $default = ''): string => $default);

		return new UnownEvidenceRecords(db: $db, appConfig: $config);

	}//end step()

	/**
	 * Every existing server-only table is handed to the system identity.
	 *
	 * @return void
	 */
	public function testEvidenceTablesAreHandedToTheSystem(): void {
		$updates = [];
		$step = $this->step(
			tables: ['openregister_registers', 'openregister_schemas', 'openregister_table_4_10', 'openregister_table_4_11'],
			schemas: [['id' => 10, 'slug' => 'erasureCertificate'], ['id' => 11, 'slug' => 'signerRecord'], ['id' => 12, 'slug' => 'legalHoldCase']],
			updates: $updates
		);

		$step->run(output: $this->createMock(originalClassName: IOutput::class));

		$this->assertCount(expectedCount: 2, haystack: $updates);
		$this->assertStringContainsString(needle: '*PREFIX*openregister_table_4_10', haystack: $updates[0][0]);
		$this->assertStringContainsString(needle: 'WHERE _owner IS NULL OR _owner <> ?', haystack: $updates[0][0]);
		$this->assertSame(expected: ['__system__', '__system__'], actual: $updates[0][1]);
		$this->assertStringContainsString(needle: '*PREFIX*openregister_table_4_11', haystack: $updates[1][0]);

	}//end testEvidenceTablesAreHandedToTheSystem()

	/**
	 * Only server-only schemas are named in the lookup, so a user-written schema keeps its owners.
	 *
	 * @return void
	 */
	public function testOnlyServerOnlySchemasAreTouched(): void {
		$this->assertNotContains(needle: 'template', haystack: UnownEvidenceRecords::SCHEMAS);
		$this->assertNotContains(needle: 'publicationConsent', haystack: UnownEvidenceRecords::SCHEMAS);
		$this->assertContains(needle: 'erasureCertificate', haystack: UnownEvidenceRecords::SCHEMAS);

	}//end testOnlyServerOnlySchemasAreTouched()

	/**
	 * Without OpenRegister's tables nothing is written and the upgrade goes on.
	 *
	 * @return void
	 */
	public function testWithoutOpenRegisterNothingChanges(): void {
		$updates = [];
		$step = $this->step(tables: [], schemas: [], updates: $updates);

		$step->run(output: $this->createMock(originalClassName: IOutput::class));

		$this->assertSame(expected: [], actual: $updates);

	}//end testWithoutOpenRegisterNothingChanges()
}//end class
