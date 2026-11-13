<?php

/**
 * Unit tests for UnownEvidenceRecords: an unreadable database, the
 * configured system owner, and what the step reports.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Repair
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Repair;

use OCA\Filinq\Repair\UnownEvidenceRecords;
use OCP\DB\IResult;
use OCP\IAppConfig;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Who the rows go to, and what the upgrade log says.
 *
 * @covers \OCA\Filinq\Repair\UnownEvidenceRecords
 */
class UnownEvidenceRecordsEdgeTest extends TestCase {

	/**
	 * A database with Filinq's register (id 4) and one evidence table.
	 *
	 * @param array<int, array<int, mixed>> $updates Captured statements and params.
	 *
	 * @return IDBConnection
	 */
	private function db(array &$updates): IDBConnection {
		$register = $this->createMock(originalClassName: IResult::class);
		$register->method('fetch')->willReturn(['id' => 4]);
		$schemas = $this->createMock(originalClassName: IResult::class);
		$schemas->method('fetchAll')->willReturn([['id' => 10, 'slug' => 'erasureCertificate']]);

		$db = $this->createMock(originalClassName: IDBConnection::class);
		$db->method('tableExists')->willReturn(true);
		$db->method('executeQuery')->willReturnOnConsecutiveCalls($register, $schemas);
		$db->method('executeStatement')->willReturnCallback(
			function (string $sql, array $params) use (&$updates): int {
				$updates[] = [$sql, $params];
				return 3;
			}
		);

		return $db;
	}//end db()

	/**
	 * App config whose OpenRegister system user is $owner.
	 *
	 * @param string $owner The configured value.
	 *
	 * @return IAppConfig
	 */
	private function config(string $owner): IAppConfig {
		$config = $this->createMock(originalClassName: IAppConfig::class);
		$config->expects($this->once())
			->method('getValueString')
			->with('openregister', 'systemUserId', '__system__')
			->willReturn($owner);

		return $config;
	}//end config()

	/**
	 * The configured system user receives the rows, and the log names the
	 * schema, the count and the owner.
	 *
	 * @return void
	 */
	public function testTheConfiguredSystemUserReceivesTheRows(): void {
		$updates = [];
		$output  = $this->createMock(originalClassName: IOutput::class);
		$output->expects($this->once())->method('info')->with('erasureCertificate: 3 row(s) handed to svc-or');

		(new UnownEvidenceRecords(db: $this->db(updates: $updates), appConfig: $this->config(owner: 'svc-or')))->run(output: $output);

		$this->assertSame(expected: ['svc-or', 'svc-or'], actual: $updates[0][1]);
	}//end testTheConfiguredSystemUserReceivesTheRows()

	/**
	 * An empty configured system user falls back to the default identity.
	 *
	 * @return void
	 */
	public function testAnEmptyConfiguredUserFallsBackToTheDefault(): void {
		$updates = [];

		(new UnownEvidenceRecords(db: $this->db(updates: $updates), appConfig: $this->config(owner: '')))
			->run(output: $this->createMock(originalClassName: IOutput::class));

		$this->assertSame(expected: ['__system__', '__system__'], actual: $updates[0][1]);
	}//end testAnEmptyConfiguredUserFallsBackToTheDefault()

	/**
	 * When OpenRegister's tables cannot be read the step reports it, changes
	 * nothing, reads no owner, and lets the upgrade go on.
	 *
	 * @return void
	 */
	public function testAnUnreadableDatabaseChangesNothing(): void {
		$db = $this->createMock(originalClassName: IDBConnection::class);
		$db->method('tableExists')->willThrowException(new RuntimeException(message: 'no such table'));
		$db->expects($this->never())->method('executeStatement');

		$config = $this->createMock(originalClassName: IAppConfig::class);
		$config->expects($this->never())->method('getValueString');

		$output = $this->createMock(originalClassName: IOutput::class);
		$output->expects($this->once())
			->method('info')
			->with('OpenRegister tables are not readable, nothing was changed: no such table');

		(new UnownEvidenceRecords(db: $db, appConfig: $config))->run(output: $output);
	}//end testAnUnreadableDatabaseChangesNothing()

	/**
	 * Without Filinq's register no schema is looked up and nothing is written.
	 *
	 * @return void
	 */
	public function testWithoutTheRegisterNothingIsWritten(): void {
		$none = $this->createMock(originalClassName: IResult::class);
		$none->method('fetch')->willReturn(false);

		$db = $this->createMock(originalClassName: IDBConnection::class);
		$db->method('tableExists')->willReturn(true);
		$db->expects($this->once())->method('executeQuery')->willReturn($none);
		$db->expects($this->never())->method('executeStatement');

		$config = $this->createMock(originalClassName: IAppConfig::class);
		$config->method('getValueString')->willReturn('__system__');

		$output = $this->createMock(originalClassName: IOutput::class);
		$output->expects($this->never())->method('info');

		(new UnownEvidenceRecords(db: $db, appConfig: $config))->run(output: $output);
	}//end testWithoutTheRegisterNothingIsWritten()
}//end class
