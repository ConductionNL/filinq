<?php

/**
 * Characterization tests for MigrateSchemaApplicationId::run()
 *
 * Pins the exact summary line, the counts when an update fails, and the
 * warning logged for a refused duplicate.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Repair
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec exclude No canonical spec covers the `docudesk` -> `filinq` schema
 *  application-id migration. Pointing this at an existing spec would report
 *  conformance to a requirement that says nothing about it.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Repair;

use OCA\Filinq\Repair\MigrateSchemaApplicationId;
use OCP\DB\Exception as DbException;
use OCP\DB\IResult;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Characterization of the repair step's run.
 *
 * @covers \OCA\Filinq\Repair\MigrateSchemaApplicationId
 */
final class MigrateSchemaApplicationIdRunTraceTest extends TestCase {

	private LoggerInterface&MockObject $logger;

	/**
	 * Fresh logger double.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->logger = $this->createMock(LoggerInterface::class);

	}//end setUp()

	/**
	 * Moved, refused and failed slugs give the exact summary line; failures count as neither.
	 *
	 * @return void
	 */
	public function testSummaryCountsMovedAndRefused(): void {
		$written = [];
		$db = $this->db(underNew: ['Case', 'note'], underOld: ['case', 'task', 'broken', 'memo', '', 'Note', 'plan'], written: $written, failOn: 'broken');
		$warnings = [];
		$this->logger->method('warning')->willReturnCallback(
			function (string $message, array $context) use (&$warnings): void {
				$warnings[] = [$message, $context];
			}
		);

		$this->assertSame(
			['MigrateSchemaApplicationId: 3 schema(s) moved to filinq, 2 refused as duplicates.'],
			$this->runStep(db: $db)
		);
		$this->assertSame([['filinq', 'docudesk', 'task'], ['filinq', 'docudesk', 'broken'], ['filinq', 'docudesk', 'memo'], ['filinq', 'docudesk', 'plan']], $written);
		$this->assertSame(
			[
				'MigrateSchemaApplicationId: schema slug already exists under the new application id; '
				. 'refusing to create a duplicate (application, slug) pair. Merge them by hand.',
				['slug' => 'case', 'old' => 'docudesk', 'new' => 'filinq'],
			],
			$warnings[0]
		);
		$this->assertSame('MigrateSchemaApplicationId: could not move schema.', $warnings[1][0]);
		$this->assertSame('broken', $warnings[1][1]['slug']);
		$this->assertSame('Note', $warnings[2][1]['slug']);
		$this->assertCount(3, $warnings);

	}//end testSummaryCountsMovedAndRefused()

	/**
	 * Nothing on the old id answers the no-op line and writes nothing.
	 *
	 * @return void
	 */
	public function testNoCandidatesLine(): void {
		$written = [];
		$db = $this->db(underNew: ['a'], underOld: [''], written: $written);

		$this->assertSame(['MigrateSchemaApplicationId: no schemas on the old application id; nothing to do.'], $this->runStep(db: $db));
		$this->assertSame([], $written);

	}//end testNoCandidatesLine()

	/**
	 * A failed read of the new id answers the read-failure line and reads nothing more.
	 *
	 * @return void
	 */
	public function testFailedReadLine(): void {
		$db = $this->createMock(IDBConnection::class);
		$db->expects($this->once())->method('executeQuery')->willThrowException(new DbException('down'));
		$db->expects($this->never())->method('executeStatement');

		$this->assertSame(['MigrateSchemaApplicationId: could not read schemas; nothing done.'], $this->runStep(db: $db));

	}//end testFailedReadLine()

	/**
	 * Run the step and collect its output lines.
	 *
	 * @param IDBConnection $db The connection double.
	 *
	 * @return array<int, string> The info lines.
	 */
	private function runStep(IDBConnection $db): array {
		$lines = [];
		$output = $this->createMock(IOutput::class);
		$output->method('info')->willReturnCallback(
			function (string $line) use (&$lines): void {
				$lines[] = $line;
			}
		);

		(new MigrateSchemaApplicationId(db: $db, logger: $this->logger))->run($output);

		return $lines;
	}//end runStep()

	/**
	 * A connection double answering the two reads and recording each update.
	 *
	 * @param array<int, string> $underNew Slugs under the new id.
	 * @param array<int, string> $underOld Slugs under the old id.
	 * @param array<int, mixed> $written Receives each update's parameters.
	 * @param string|null $failOn A slug whose update throws.
	 *
	 * @return IDBConnection The double.
	 */
	private function db(array $underNew, array $underOld, array &$written, ?string $failOn = null): IDBConnection {
		$result = function (array $slugs): IResult {
			$r = $this->createMock(IResult::class);
			$r->method('fetchAll')->willReturn(array_map(static fn (string $s): array => ['slug' => $s], $slugs));
			return $r;
		};

		$db = $this->createMock(IDBConnection::class);
		$db->method('executeQuery')->willReturnOnConsecutiveCalls($result($underNew), $result($underOld));
		$db->method('executeStatement')->willReturnCallback(
			function (string $sql, array $params) use (&$written, $failOn): int {
				$written[] = $params;
				if ($params[2] === $failOn) {
					throw new DbException('locked');
				}

				return 1;
			}
		);

		return $db;
	}//end db()
}//end class
