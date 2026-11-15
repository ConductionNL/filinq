<?php

/**
 * Unit tests for how MigratePrintJobsOutOfAppConfig maps one legacy job.
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
 * @spec openspec/specs/print-preview/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Repair;

use DateTimeImmutable;
use DateTimeInterface;
use OCA\Filinq\Kernel\Contracts\PrintJobFileStoreInterface;
use OCA\Filinq\Kernel\Contracts\PrintJobRepositoryInterface;
use OCA\Filinq\Repair\MigratePrintJobsOutOfAppConfig;
use OCP\IAppConfig;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * The status, defaults and details a legacy job becomes.
 *
 * @covers \OCA\Filinq\Repair\MigratePrintJobsOutOfAppConfig
 */
class MigratePrintJobsToPrintJobTest extends TestCase {

	/**
	 * The step over doubles it does not reach when mapping.
	 *
	 * @return MigratePrintJobsOutOfAppConfig
	 */
	private function step(): MigratePrintJobsOutOfAppConfig {
		return new MigratePrintJobsOutOfAppConfig(
			appConfig: $this->createMock(originalClassName: IAppConfig::class),
			jobs: $this->createMock(originalClassName: PrintJobRepositoryInterface::class),
			files: $this->createMock(originalClassName: PrintJobFileStoreInterface::class),
			logger: $this->createMock(originalClassName: LoggerInterface::class)
		);
	}//end step()

	/**
	 * Legacy states, whether PDFs were found, and the status they become.
	 *
	 * @return array<string, array{0: array<string, mixed>, 1: bool, 2: string}>
	 */
	public static function statuses(): array {
		return [
			'printing at the printer'      => [['status' => 'completed', 'externalStatus' => 'printing'], true, 'sent'],
			'sent to the printer'          => [['status' => 'completed', 'externalStatus' => 'sent'], true, 'sent'],
			'printed'                      => [['status' => 'completed', 'externalStatus' => 'printed'], true, 'printed'],
			'failed at the printer'        => [['status' => 'completed', 'externalStatus' => 'failed'], true, 'failed'],
			'unknown printer state, failed' => [['status' => 'failed', 'externalStatus' => 'lost'], true, 'failed'],
			'rendered with letters'        => [['status' => 'completed'], true, 'queued'],
			'rendered without letters'     => [['status' => 'completed'], false, 'failed'],
			'still queued'                 => [['status' => 'queued'], false, 'rendering'],
			'still processing'             => [['status' => 'processing'], true, 'rendering'],
			'nothing known'                => [[], false, 'rendering'],
		];
	}//end statuses()

	/**
	 * The printer's state wins; otherwise a finished job with letters waits in
	 * the queue, one without letters failed, and an unfinished one is rendering.
	 *
	 * @param array<string, mixed> $legacy   The legacy entry.
	 * @param bool                 $hasFiles Whether PDFs were found.
	 * @param string               $status   The expected status.
	 *
	 * @return void
	 */
	#[DataProvider('statuses')]
	public function testTheStatusOfALegacyJob(array $legacy, bool $hasFiles, string $status): void {
		$files = [];
		if ($hasFiles === true) {
			$files = ['print-job-x-0.pdf'];
		}

		$this->assertSame(expected: $status, actual: $this->step()->toPrintJob(legacy: $legacy, files: $files)['status']);
	}//end testTheStatusOfALegacyJob()

	/**
	 * An empty legacy entry gets every default, no details, and one timestamp
	 * for both the request and the status change.
	 *
	 * @return void
	 */
	public function testAnEmptyEntryGetsTheDefaults(): void {
		$job = $this->step()->toPrintJob(legacy: [], files: []);

		$this->assertSame(expected: $job['requestedAt'], actual: $job['statusChangedAt']);
		$this->assertNotFalse(condition: DateTimeImmutable::createFromFormat(format: DateTimeInterface::ATOM, datetime: $job['requestedAt']));
		unset($job['requestedAt'], $job['statusChangedAt']);

		$this->assertSame(
			expected: [
				'status' => 'rendering',
				'requestedBy' => '',
				'filename' => 'print-job.pdf',
				'total' => 0,
				'rendered' => 0,
				'errors' => 0,
				'files' => [],
				'printConfig' => [],
				'manifest' => [],
			],
			actual: $job
		);
	}//end testAnEmptyEntryGetsTheDefaults()

	/**
	 * The counts come from the entry where it has them, never below zero, and
	 * the total falls back to the letters found; the manifest becomes a list.
	 *
	 * @return void
	 */
	public function testCountsAndManifest(): void {
		$step  = $this->step();
		$files = ['a.pdf', 'b.pdf'];

		$given = $step->toPrintJob(
			legacy: [
				'total' => 5,
				'errors' => 3,
				'ownerUserId' => 'u1',
				'filename' => 'brief.pdf',
				'manifest' => ['x' => 'one', 'y' => 'two'],
				'printConfig' => ['duplex' => true],
			],
			files: $files
		);
		$this->assertSame(expected: [5, 2, 3], actual: [$given['total'], $given['rendered'], $given['errors']]);
		$this->assertSame(expected: ['u1', 'brief.pdf'], actual: [$given['requestedBy'], $given['filename']]);
		$this->assertSame(expected: ['one', 'two'], actual: $given['manifest']);
		$this->assertSame(expected: ['duplex' => true], actual: $given['printConfig']);
		$this->assertSame(expected: $files, actual: $given['files']);

		$negative = $step->toPrintJob(legacy: ['total' => -4, 'errors' => -1], files: $files);
		$this->assertSame(expected: [0, 0], actual: [$negative['total'], $negative['errors']]);

		$this->assertSame(expected: 2, actual: $step->toPrintJob(legacy: [], files: $files)['total']);
	}//end testCountsAndManifest()

	/**
	 * The printer's details win over an error; structured details are kept as
	 * JSON; and the text is cut at 4096 characters.
	 *
	 * @return void
	 */
	public function testStatusDetails(): void {
		$step = $this->step();

		$this->assertSame(
			expected: 'paper jam',
			actual: $step->toPrintJob(legacy: ['externalDetails' => 'paper jam', 'error' => 'ignored'], files: [])['statusDetails']
		);
		$this->assertSame(expected: 'render failed', actual: $step->toPrintJob(legacy: ['error' => 'render failed'], files: [])['statusDetails']);
		$this->assertSame(
			expected: '{"code":42,"tray":"A4"}',
			actual: $step->toPrintJob(legacy: ['externalDetails' => ['code' => 42, 'tray' => 'A4']], files: [])['statusDetails']
		);

		$long = $step->toPrintJob(legacy: ['error' => str_repeat(string: 'é', times: 5000)], files: [])['statusDetails'];
		$this->assertSame(expected: str_repeat(string: 'é', times: 4096), actual: $long);
	}//end testStatusDetails()
}//end class
