<?php

/**
 * Characterization tests for the batch loop of ConsolidateRegisters
 *
 * With one row per batch, a failure in the second batch must keep the first
 * batch moved, leave the later ones untouched, and report only the confirmed
 * rows as moved.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Repair
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec exclude No canonical spec covers the consolidation of Filinq's five
 *  OpenRegister registers into one.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Repair;

require_once __DIR__ . '/ConsolidateRegistersTest.php';

use OCA\Filinq\Repair\ConsolidateRegisters;
use OCA\Filinq\Repair\ConsolidateRegistersDecisions;
use OCA\Filinq\Repair\ConsolidateRegistersGateway;
use OCP\DB\IResult;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Batch-by-batch behaviour of the row move.
 *
 * @covers \OCA\Filinq\Repair\ConsolidateRegisters
 * @uses   \OCA\Filinq\Repair\ConsolidateRegistersDecisions
 * @uses   \OCA\Filinq\Repair\ConsolidateRegistersGateway
 */
class ConsolidateRegistersBatchTraceTest extends TestCase {

	private const SOURCE = 'nc_openregister_table_66_227';

	private const TARGET = 'nc_openregister_table_517_227';

	/**
	 * Rows per batch for the next run.
	 *
	 * @var integer
	 */
	private int $batchSize = 1;

	/**
	 * Every batch succeeds: all three rows move and are counted.
	 *
	 * @return void
	 */
	public function testEveryBatchMovesAndIsCounted(): void {
		$database = $this->database();

		$result = $this->runStep(database: $database, fault: []);

		$this->assertSame([], $database->uuidsIn(table: self::SOURCE));
		$this->assertSame(['u1', 'u2', 'u3'], $database->uuidsIn(table: self::TARGET));
		$this->assertStringContainsString('3 object(s) moved into filinq', $result['output']);
		$this->assertSame([], $result['warnings']);
		$this->assertSame(['517', '517', '517'], array_column($database->rowsIn(table: self::TARGET), '_register'));

	}//end testEveryBatchMovesAndIsCounted()

	/**
	 * A failing second insert keeps batch one moved and stops before batch three.
	 *
	 * @return void
	 */
	public function testFailingSecondInsertStopsAfterTheFirstBatch(): void {
		$database = $this->database();

		$result = $this->runStep(database: $database, fault: ['INSERT INTO', 2, 'throw']);

		$this->assertSame(['u2', 'u3'], $database->uuidsIn(table: self::SOURCE));
		$this->assertSame(['u1'], $database->uuidsIn(table: self::TARGET));
		$this->assertStringContainsString('1 object(s) moved into filinq', $result['output']);
		$this->assertCount(1, array_filter($result['warnings'], fn (string $w): bool => str_contains($w, 'insert failed')));

	}//end testFailingSecondInsertStopsAfterTheFirstBatch()

	/**
	 * A second insert that silently goes nowhere does not authorise its delete.
	 *
	 * @return void
	 */
	public function testSwallowedSecondInsertKeepsItsSourceRow(): void {
		$database = $this->database();

		$result = $this->runStep(database: $database, fault: ['INSERT INTO', 2, 'swallow']);

		$this->assertSame(['u2', 'u3'], $database->uuidsIn(table: self::SOURCE));
		$this->assertSame(['u1'], $database->uuidsIn(table: self::TARGET));
		$this->assertStringContainsString('1 object(s) moved into filinq', $result['output']);
		$this->assertCount(1, array_filter($result['warnings'], fn (string $w): bool => str_contains($w, 'NOT deleting the source rows')));

	}//end testSwallowedSecondInsertKeepsItsSourceRow()

	/**
	 * A failing second delete leaves that row on both sides and is not counted.
	 *
	 * @return void
	 */
	public function testFailingSecondDeleteIsNotCounted(): void {
		$database = $this->database();

		$result = $this->runStep(database: $database, fault: ['DELETE FROM', 2, 'throw']);

		$this->assertSame(['u2', 'u3'], $database->uuidsIn(table: self::SOURCE));
		$this->assertSame(['u1', 'u2'], $database->uuidsIn(table: self::TARGET));
		$this->assertStringContainsString('1 object(s) moved into filinq', $result['output']);
		$this->assertCount(1, array_filter($result['warnings'], fn (string $w): bool => str_contains($w, 'exist in BOTH tables')));

	}//end testFailingSecondDeleteIsNotCounted()

	/**
	 * A batch counts all its rows: two rows in batch one, a failing delete in batch two.
	 *
	 * @return void
	 */
	public function testAConfirmedBatchCountsEveryRow(): void {
		$database = $this->database();
		$this->batchSize = 2;

		$result = $this->runStep(database: $database, fault: ['DELETE FROM', 2, 'throw']);

		$this->assertSame(['u3'], $database->uuidsIn(table: self::SOURCE));
		$this->assertSame(['u1', 'u2', 'u3'], $database->uuidsIn(table: self::TARGET));
		$this->assertStringContainsString('2 object(s) moved into filinq', $result['output']);

	}//end testAConfirmedBatchCountsEveryRow()

	/**
	 * Three source rows and an empty target.
	 *
	 * @return ShardDatabase The database.
	 */
	private function database(): ShardDatabase {
		$columns = ['_id', '_uuid', '_register', '_schema', 'name'];
		$rows = [];
		foreach (['u1', 'u2', 'u3'] as $i => $uuid) {
			$rows[] = ['_id' => ($i + 1), '_uuid' => $uuid, '_register' => '66', '_schema' => '227', 'name' => 'object ' . $uuid];
		}

		return new ShardDatabase(
			registers: ['consent' => 3, 'signing' => 4, 'templates' => 5, 'document' => 6, 'dossier' => 66, 'docudesk' => 7, 'filinq' => 517],
			tables: [
				self::SOURCE => ['columns' => $columns, 'rows' => $rows],
				self::TARGET => ['columns' => $columns, 'rows' => []],
				'nc_filecache' => ['columns' => ['fileid'], 'rows' => []],
			]
		);

	}//end database()

	/**
	 * Run the step with $batchSize rows per batch and an optional fault on the nth statement.
	 *
	 * @param ShardDatabase $database The fixture.
	 * @param array{0?: string, 1?: int, 2?: 'throw'|'swallow'} $fault Statement prefix, which occurrence, and how it fails.
	 *
	 * @return array{output: string, warnings: array<int, string>}
	 */
	private function runStep(ShardDatabase $database, array $fault): array {
		$seen = 0;
		$db = $this->createMock(IDBConnection::class);
		$db->method('executeQuery')->willReturnCallback(
			function (string $sql, array $params = []) use ($database): IResult {
				$result = $this->createMock(IResult::class);
				$result->method('fetchAll')->willReturn($database->query(sql: $sql, params: $params));
				return $result;
			}
		);
		$db->method('executeStatement')->willReturnCallback(
			static function (string $sql, array $params = []) use ($database, $fault, &$seen): int {
				if ($fault !== [] && str_starts_with($sql, $fault[0]) === true && ++$seen === $fault[1]) {
					if ($fault[2] === 'throw') {
						throw new RuntimeException('statement failed');
					}

					return count($params);
				}

				return $database->statement(sql: $sql, params: $params);
			}
		);

		$messages = [];
		$output = $this->createMock(IOutput::class);
		$output->method('info')->willReturnCallback(
			static function (string $message) use (&$messages): void {
				$messages[] = $message;
			}
		);

		$warnings = [];
		$logger = $this->createMock(LoggerInterface::class);
		$collect = static function (string|\Stringable $message) use (&$warnings): void {
			$warnings[] = (string)$message;
		};
		$logger->method('warning')->willReturnCallback($collect);
		$logger->method('error')->willReturnCallback($collect);

		$decisions = new class($this->batchSize) extends ConsolidateRegistersDecisions {
			/**
			 * Fixed batch size.
			 *
			 * @param int $batchSize Rows per batch.
			 */
			public function __construct(
				private readonly int $batchSize,
			) {
			}

			/**
			 * The configured number of items per batch.
			 *
			 * @param array<int, mixed> $items The items.
			 * @param int $size Ignored.
			 *
			 * @return array<int, array<int, mixed>>
			 */
			public function chunk(array $items, int $size = self::CHUNK_SIZE): array {
				return parent::chunk(items: $items, size: $this->batchSize);
			}
		};

		(new ConsolidateRegisters(
			logger: $logger,
			gateway: new ConsolidateRegistersGateway(db: $db, logger: $logger),
			decisions: $decisions
		))->run($output);

		return ['output' => implode("\n", $messages), 'warnings' => $warnings];
	}//end runStep()
}//end class
