<?php

/**
 * Golden cases of what the register consolidation moves, skips and says.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Repair
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Repair;

use OCA\Filinq\Repair\ConsolidateRegisters;
use OCA\Filinq\Repair\ConsolidateRegistersGateway;
use OCP\DB\IResult;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

require_once __DIR__.'/ConsolidateRegistersTest.php';

/**
 * The output, the warnings and every table afterwards, compared with the
 * recorded JSON.
 *
 * @covers \OCA\Filinq\Repair\ConsolidateRegisters
 * @uses   \OCA\Filinq\Repair\ConsolidateRegistersGateway
 * @uses   \OCA\Filinq\Repair\ConsolidateRegistersDecisions
 *
 * @spec exclude No canonical spec covers the consolidation of the five registers into one.
 */
class ConsolidateRegistersGoldenTest extends TestCase {

	private const GOLDEN = __DIR__.'/golden/consolidate-registers.json';

	private const IDS = ['consent' => 3, 'signing' => 4, 'templates' => 5, 'document' => 6, 'dossier' => 66, 'docudesk' => 7, 'filinq' => 517];

	private const COLUMNS = ['_id', '_uuid', '_register', '_schema', 'name'];

	/**
	 * The cases: source tables (register => schema => uuids) and target tables (schema => uuids).
	 *
	 * @return array<string, array{0: array<int, array<int, array<int, string>>>, 1: array<int, array<int, string>>}>
	 */
	public static function cases(): array {
		return [
			'two registers, three pairs'              => [[66 => [227 => ['a', 'b']], 4 => [15 => ['c'], 16 => ['d']]], [227 => [], 15 => [], 16 => []]],
			'a conflict stays in place'               => [[66 => [227 => ['a', 'b']]], [227 => ['b']]],
			'a pair without a target table'           => [[66 => [227 => ['a'], 228 => ['o']], 3 => [11 => ['x']]], [227 => [], 11 => []]],
			'every source register'                   => [[3 => [11 => ['r3']], 4 => [15 => ['r4']], 5 => [12 => ['r5']], 6 => [50 => ['r6']], 66 => [227 => ['r66']], 7 => [90 => ['r7']]], [11 => [], 15 => [], 12 => [], 50 => [], 227 => [], 90 => []]],
			'nothing to move'                         => [[66 => [227 => []]], [227 => ['a']]],
			'the target has no shards yet'            => [[66 => [227 => ['a']]], []],
			'the target register is a source too'     => [[517 => [227 => ['t']], 66 => [227 => ['a']]], [227 => ['t']]],
		];

	}//end cases()

	/**
	 * The run matches the recorded one.
	 *
	 * @param array<int, array<int, array<int, string>>> $sources The source tables.
	 * @param array<int, array<int, string>>             $targets The target tables.
	 *
	 * @dataProvider cases
	 *
	 * @return void
	 */
	public function testRun(array $sources, array $targets): void {
		$tables = [];
		foreach ($sources as $register => $schemas) {
			foreach ($schemas as $schema => $uuids) {
				$tables['oc_openregister_table_'.$register.'_'.$schema] = ['columns' => self::COLUMNS, 'rows' => self::rows(uuids: $uuids, register: (string) $register, schema: (string) $schema)];
			}
		}

		foreach ($targets as $schema => $uuids) {
			$tables['oc_openregister_table_517_'.$schema] = ['columns' => self::COLUMNS, 'rows' => self::rows(uuids: $uuids, register: '517', schema: (string) $schema)];
		}

		$database = new ShardDatabase(registers: self::IDS, tables: $tables);
		$trace    = $this->runStep(database: $database);
		$names    = array_keys($tables);
		sort($names);
		foreach ($names as $name) {
			$trace['tables'][$name] = $database->rowsIn(table: $name);
		}

		$golden = [];
		if (is_file(self::GOLDEN) === true) {
			$golden = json_decode((string) file_get_contents(self::GOLDEN), true);
		}

		if (getenv('GOLDEN_RECORD') === '1') {
			$golden[$this->dataName()] = $trace;
			ksort($golden);
			file_put_contents(self::GOLDEN, json_encode($golden, (JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))."\n");
		}

		self::assertSame(($golden[$this->dataName()] ?? null), $trace);

	}//end testRun()

	/**
	 * Shard rows for $uuids.
	 *
	 * @param array<int, string> $uuids    The uuids.
	 * @param string             $register The register id.
	 * @param string             $schema   The schema id.
	 *
	 * @return array<int, array<string, mixed>> The rows.
	 */
	private static function rows(array $uuids, string $register, string $schema): array {
		$rows = [];
		foreach ($uuids as $i => $uuid) {
			$rows[] = ['_id' => ($i + 1), '_uuid' => $uuid, '_register' => $register, '_schema' => $schema, 'name' => 'object '.$uuid];
		}

		return $rows;

	}//end rows()

	/**
	 * Run the step against $database.
	 *
	 * @param ShardDatabase $database The fake.
	 *
	 * @return array{output: array<int, string>, warnings: array<int, string>} What it said.
	 */
	private function runStep(ShardDatabase $database): array {
		$db = $this->createMock(IDBConnection::class);
		$db->method('executeQuery')->willReturnCallback(
			function (string $sql, array $params=[]) use ($database): IResult {
				$result = $this->createMock(IResult::class);
				$result->method('fetchAll')->willReturn($database->query(sql: $sql, params: $params));
				return $result;
			}
		);
		$db->method('executeStatement')->willReturnCallback(
			static fn (string $sql, array $params=[]): int => $database->statement(sql: $sql, params: $params)
		);

		$said   = ['output' => [], 'warnings' => []];
		$output = $this->createMock(IOutput::class);
		$output->method('info')->willReturnCallback(
			static function (string $message) use (&$said): void {
				$said['output'][] = $message;
			}
		);
		$logger = $this->createMock(LoggerInterface::class);
		foreach (['warning', 'error'] as $level) {
			$logger->method($level)->willReturnCallback(
				static function (string|\Stringable $message, array $context=[]) use (&$said, $level): void {
					$said['warnings'][] = [$level, (string) $message, $context];
				}
			);
		}

		(new ConsolidateRegisters(logger: $logger, gateway: new ConsolidateRegistersGateway(db: $db, logger: $logger)))->run($output);

		return $said;

	}//end runStep()
}//end class
