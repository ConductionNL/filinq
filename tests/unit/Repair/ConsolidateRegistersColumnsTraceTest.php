<?php

/**
 * Characterization tests for the column agreement step of ConsolidateRegisters
 *
 * Pins every refusal of a source/target pair on its columns (an unreadable
 * table, a table that is not a shard table, a source wider than its target),
 * that a refused pair never reads or writes a row, and that an agreed pair
 * moves its rows with exactly the columns the decisions name.
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

use OCA\Filinq\Repair\ConsolidateRegisters;
use OCA\Filinq\Repair\ConsolidateRegistersDecisions;
use OCA\Filinq\Repair\ConsolidateRegistersGateway;
use OCP\Migration\IOutput;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Characterization of the column agreement of one pair.
 *
 * @covers \OCA\Filinq\Repair\ConsolidateRegisters
 */
class ConsolidateRegistersColumnsTraceTest extends TestCase {

	private const UNREADABLE = 'ConsolidateRegisters: could not read the columns of one of the tables; refusing the pair. '
		. 'An unreadable schema is not an empty one.';

	private const NOT_SHARD = 'ConsolidateRegisters: a table matching the shard-table name pattern is missing `_uuid` or '
		. '`_register`; it is not one of ours whatever its name says. Refusing the pair.';

	private const WIDER = 'ConsolidateRegisters: the source table carries column(s) the target does not; refusing the '
		. 'pair. Copying anyway would drop those properties from every migrated object while every '
		. 'row count still reconciled — a data loss no count can catch. Sync the schema first.';

	private const PAIR = ['sourceTable' => 'src_3_1', 'targetTable' => 'dst_9_1'];

	private const SHARD = ['_uuid', '_register', 'title'];

	/**
	 * @var array<string, array<int, string>|null> The columns each table answers.
	 */
	private array $columns = ['src_3_1' => self::SHARD, 'dst_9_1' => self::SHARD];

	/**
	 * @var array<int, string> The columns the source carries and the target does not.
	 */
	private array $missing = [];

	/**
	 * @var array<int, array{0: string, 1: string, 2: array<string, mixed>}> Every log line.
	 */
	private array $logs = [];

	private ConsolidateRegistersGateway&MockObject $gateway;

	/**
	 * Fresh doubles.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->gateway = $this->createMock(ConsolidateRegistersGateway::class);

	}//end setUp()

	/**
	 * An unreadable source or target refuses the pair before any row is read.
	 *
	 * @return void
	 */
	public function testUnreadableColumnsRefuseThePair(): void {
		foreach (['src_3_1', 'dst_9_1'] as $table) {
			$this->setUp();
			$this->logs = [];
			$this->columns = ['src_3_1' => self::SHARD, 'dst_9_1' => self::SHARD, $table => null];
			$this->expectNoRows();

			$this->assertSame([$this->refusedLine()], $this->runStep());
			$this->assertSame([['warning', self::UNREADABLE, self::PAIR]], $this->logs);
		}

	}//end testUnreadableColumnsRefuseThePair()

	/**
	 * A source or target without the shard columns refuses the pair.
	 *
	 * @return void
	 */
	public function testANonShardTableRefusesThePair(): void {
		foreach (['src_3_1', 'dst_9_1'] as $table) {
			$this->setUp();
			$this->logs = [];
			$this->columns = ['src_3_1' => self::SHARD, 'dst_9_1' => self::SHARD, $table => ['_uuid', 'title']];
			$this->expectNoRows();

			$this->assertSame([$this->refusedLine()], $this->runStep());
			$this->assertSame([['warning', self::NOT_SHARD, self::PAIR]], $this->logs);
		}

	}//end testANonShardTableRefusesThePair()

	/**
	 * A source wider than its target refuses the pair and names the columns.
	 *
	 * @return void
	 */
	public function testAWiderSourceRefusesThePair(): void {
		$this->missing = ['summary'];
		$this->expectNoRows();

		$this->assertSame([$this->refusedLine()], $this->runStep());
		$this->assertSame([['warning', self::WIDER, self::PAIR + ['columns' => ['summary']]]], $this->logs);

	}//end testAWiderSourceRefusesThePair()

	/**
	 * An agreed pair moves its rows with the insert columns of the source.
	 *
	 * @return void
	 */
	public function testAnAgreedPairMovesWithTheInsertColumns(): void {
		$this->columns = ['src_3_1' => ['_uuid', '_register', 'title', 'body'], 'dst_9_1' => ['_uuid', '_register', 'title', 'body', 'extra']];
		$this->gateway->method('uuidsIn')->willReturn(['u1']);
		$this->gateway->method('uuidsPresent')->willReturnOnConsecutiveCalls([], ['u1']);
		$this->gateway->expects($this->once())->method('insertBatch')->with(
			$this->identicalTo('src_3_1'),
			$this->identicalTo('dst_9_1'),
			$this->identicalTo(9),
			$this->identicalTo(['insert:_uuid,_register,title,body']),
			$this->identicalTo(['u1'])
		)->willReturn(true);
		$this->gateway->method('deleteBatch')->willReturn(true);

		$lines = $this->runStep();

		$this->assertSame([], $this->logs);
		$this->assertStringStartsWith('ConsolidateRegisters: 1 object(s) moved', $lines[0]);

	}//end testAnAgreedPairMovesWithTheInsertColumns()

	/**
	 * A refused pair reads no uuid and writes no row.
	 *
	 * @return void
	 */
	private function expectNoRows(): void {
		$this->gateway->expects($this->never())->method('uuidsIn');
		$this->gateway->expects($this->never())->method('insertBatch');
		$this->gateway->expects($this->never())->method('deleteBatch');

	}//end expectNoRows()

	/**
	 * The summary line of a run that refused one pair.
	 *
	 * @return string The line.
	 */
	private function refusedLine(): string {
		return 'ConsolidateRegisters: 0 object(s) moved into filinq, 0 conflict(s) left in place, '
			. '0 pair(s) skipped (no target table yet), 1 pair(s) refused.';

	}//end refusedLine()

	/**
	 * Run the step over one source register (3) with one shard table for schema 1, and the target register 9.
	 *
	 * @return array<int, string> The output lines.
	 */
	private function runStep(): array {
		$this->gateway->method('registerIds')->willReturn(['filinq' => 9, 'consent' => 3]);
		$this->gateway->method('tableNames')->willReturn(['src_3_1', 'dst_9_1']);
		$this->gateway->method('columnsOf')->willReturnCallback(fn (string $table): ?array => $this->columns[$table]);

		$decisions = $this->createMock(ConsolidateRegistersDecisions::class);
		$decisions->method('shardTablesFor')->willReturnCallback(
			fn (array $names, int $registerId): array => ['tables' => ($registerId === 9 ? [1 => 'dst_9_1'] : ($registerId === 3 ? [1 => 'src_3_1'] : [])), 'ambiguous' => []]
		);
		$decisions->method('isSafeTableName')->willReturn(true);
		$decisions->method('looksLikeShardTable')->willReturnCallback(
			fn (array $columns): bool => (in_array('_uuid', $columns, true) === true && in_array('_register', $columns, true) === true)
		);
		$decisions->method('missingColumns')->willReturnCallback(
			fn (array $sourceColumns, array $targetColumns): array => ($sourceColumns === $this->columns['src_3_1'] && $targetColumns === $this->columns['dst_9_1'] ? $this->missing : ['wrong arguments'])
		);
		$decisions->method('insertColumns')->willReturnCallback(fn (array $sourceColumns): array => ['insert:' . implode(',', $sourceColumns)]);
		$decisions->method('partitionUuids')->willReturnCallback(fn (array $sourceUuids, array $targetUuids): array => ['movable' => $sourceUuids, 'conflicts' => []]);
		$decisions->method('chunk')->willReturnCallback(fn (array $items): array => [$items]);
		$decisions->method('reconciled')->willReturnCallback(fn (int $expected, ?int $observed): bool => $expected === $observed);

		$logger = $this->createMock(LoggerInterface::class);
		foreach (['info', 'warning', 'error'] as $level) {
			$logger->method($level)->willReturnCallback(
				function (string $message, array $context = []) use ($level): void {
					$this->logs[] = [$level, $message, $context];
				}
			);
		}

		$lines = [];
		$output = $this->createMock(IOutput::class);
		$output->method('info')->willReturnCallback(
			function (string $line) use (&$lines): void {
				$lines[] = $line;
			}
		);

		(new ConsolidateRegisters(logger: $logger, gateway: $this->gateway, decisions: $decisions))->run($output);

		return $lines;

	}//end runStep()
}//end class
