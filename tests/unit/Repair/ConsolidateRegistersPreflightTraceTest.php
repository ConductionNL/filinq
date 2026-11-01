<?php

/**
 * Characterization tests for ConsolidateRegisters' preflight
 *
 * Pins every early exit's output line, the ambiguity warning for the target
 * register, and the plan the run continues with.
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
 * Characterization of the preflight checks.
 *
 * @covers \OCA\Filinq\Repair\ConsolidateRegisters
 */
class ConsolidateRegistersPreflightTraceTest extends TestCase {

	private ConsolidateRegistersGateway&MockObject $gateway;

	private ConsolidateRegistersDecisions&MockObject $decisions;

	private LoggerInterface&MockObject $logger;

	/**
	 * Fresh doubles.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->gateway = $this->createMock(ConsolidateRegistersGateway::class);
		$this->decisions = $this->createMock(ConsolidateRegistersDecisions::class);
		$this->logger = $this->createMock(LoggerInterface::class);

	}//end setUp()

	/**
	 * An unreadable register table stops before listing tables.
	 *
	 * @return void
	 */
	public function testUnreadableRegisterTable(): void {
		$this->gateway->method('registerIds')->willReturn(null);
		$this->gateway->expects($this->never())->method('tableNames');

		$this->assertSame(['ConsolidateRegisters: could not read the register table; nothing moved.'], $this->runStep());

	}//end testUnreadableRegisterTable()

	/**
	 * A missing target register stops before listing tables.
	 *
	 * @return void
	 */
	public function testMissingTargetRegister(): void {
		$this->gateway->method('registerIds')->willReturn(['consent' => 3]);
		$this->gateway->expects($this->never())->method('tableNames');

		$this->assertSame(['ConsolidateRegisters: target register `filinq` does not exist yet; nothing moved.'], $this->runStep());

	}//end testMissingTargetRegister()

	/**
	 * Unlistable tables stop before the shard lookup.
	 *
	 * @return void
	 */
	public function testUnlistableTables(): void {
		$this->gateway->method('registerIds')->willReturn(['filinq' => 9]);
		$this->gateway->method('tableNames')->willReturn(null);
		$this->decisions->expects($this->never())->method('shardTablesFor');

		$this->assertSame(['ConsolidateRegisters: could not list tables; nothing moved.'], $this->runStep());

	}//end testUnlistableTables()

	/**
	 * Without target shard tables nothing moves; ambiguous pairs are still reported for the target.
	 *
	 * @return void
	 */
	public function testNoTargetTablesAndAmbiguityWarning(): void {
		$this->gateway->method('registerIds')->willReturn(['filinq' => 9]);
		$this->gateway->method('tableNames')->willReturn(['t1', 't2']);
		$this->decisions->expects($this->once())->method('shardTablesFor')
			->with(['t1', 't2'], 9)
			->willReturn(['tables' => [], 'ambiguous' => [4, 5]]);
		$this->logger->expects($this->once())->method('warning')
			->with($this->stringContains('more than one table name matched'), ['registerId' => 9, 'schemaIds' => [4, 5]]);

		$this->assertSame(['ConsolidateRegisters: target register `filinq` has no shard tables yet; nothing moved.'], $this->runStep());

	}//end testNoTargetTablesAndAmbiguityWarning()

	/**
	 * With target tables the run continues: only sources present and not the target are visited.
	 *
	 * @return void
	 */
	public function testPlanCarriesIntoTheRun(): void {
		$this->gateway->method('registerIds')->willReturn(['filinq' => 9, 'docudesk' => 9]);
		$this->gateway->method('tableNames')->willReturn(['t']);
		$this->decisions->method('shardTablesFor')->willReturn(['tables' => [1 => 't'], 'ambiguous' => []]);
		$this->logger->expects($this->never())->method('warning');

		$this->assertSame(
			[
				'ConsolidateRegisters: 0 object(s) moved into filinq, 0 conflict(s) left in place, '
				. '0 pair(s) skipped (no target table yet), 0 pair(s) refused.',
			],
			$this->runStep()
		);

	}//end testPlanCarriesIntoTheRun()

	/**
	 * Run the step and collect its output lines.
	 *
	 * @return array<int, string> The info lines.
	 */
	private function runStep(): array {
		$lines = [];
		$output = $this->createMock(IOutput::class);
		$output->method('info')->willReturnCallback(
			function (string $line) use (&$lines): void {
				$lines[] = $line;
			}
		);

		(new ConsolidateRegisters(logger: $this->logger, gateway: $this->gateway, decisions: $this->decisions))->run($output);

		return $lines;
	}//end runStep()
}//end class
