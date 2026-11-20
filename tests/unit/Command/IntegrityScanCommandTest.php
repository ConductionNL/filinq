<?php

/**
 * Unit tests for `occ filinq:integrity:scan`.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/whole-collection-integrity-scan/tasks.md#task-2-nightly-job-occ-command-and-notification
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Command;

use OCA\Filinq\Command\IntegrityScanCommand;
use OCA\Filinq\Service\IntegrityScanService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \OCA\Filinq\Command\IntegrityScanCommand
 */
class IntegrityScanCommandTest extends TestCase {

	/**
	 * The command over a scan that answers this run.
	 *
	 * @param array<string, mixed> $run The run.
	 *
	 * @return CommandTester
	 */
	private function tester(array $run): CommandTester {
		$scan = $this->createMock(IntegrityScanService::class);
		$scan->expects($this->once())->method('run')->with('command')->willReturn($run);

		return new CommandTester(new IntegrityScanCommand($scan));
	}

	public function testACleanRunExitsZero(): void {
		$tester = $this->tester(['checked' => 12, 'missing' => 0, 'changed' => 0, 'orphaned' => 0, 'findings' => []]);

		$this->assertSame(0, $tester->execute([]));
		$this->assertSame("Checked 12 records: 0 missing, 0 changed, 0 orphaned.\n", $tester->getDisplay());
	}

	public function testFindingsArePrintedAndExitOne(): void {
		$tester = $this->tester(
			[
				'checked' => 3, 'missing' => 1, 'changed' => 0, 'orphaned' => 0,
				'findings' => [['kind' => 'missing', 'schema' => 'dossier', 'record' => 'd-1', 'fileId' => 12, 'name' => 'Lindelaan 12']],
			]
		);

		$this->assertSame(1, $tester->execute([]));
		$this->assertStringContainsString('1 missing', $tester->getDisplay());
		$this->assertStringContainsString('missing  dossier d-1  file 12  Lindelaan 12', $tester->getDisplay());
	}
}
