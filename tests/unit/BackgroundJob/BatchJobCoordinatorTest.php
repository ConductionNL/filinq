<?php

/**
 * Unit tests for BatchJobCoordinator: one work unit per run on the oldest background batch.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\BackgroundJob
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#requirement-chunked-background-processing-under-cron-req-ddras-002
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\BackgroundJob;

use OCA\Filinq\BackgroundJob\BatchJobCoordinator;
use OCA\Filinq\Service\BatchRouting;
use OCA\Filinq\Service\BatchStateService;
use OCA\Filinq\Service\BatchWorkUnit;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionMethod;
use RuntimeException;

/**
 * Which batch the coordinator picks, with which budget, and when it does nothing.
 *
 * @covers \OCA\Filinq\BackgroundJob\BatchJobCoordinator
 */
class BatchJobCoordinatorTest extends TestCase {

	/**
	 * Batch store double.
	 *
	 * @var BatchStateService&MockObject
	 */
	private BatchStateService&MockObject $state;

	/**
	 * Work unit double.
	 *
	 * @var BatchWorkUnit&MockObject
	 */
	private BatchWorkUnit&MockObject $unit;

	/**
	 * Routing double (the engine switch).
	 *
	 * @var BatchRouting&MockObject
	 */
	private BatchRouting&MockObject $routing;

	/**
	 * App config values.
	 *
	 * @var array<string, string>
	 */
	private array $config = [];

	/**
	 * Build the doubles.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->state = $this->createMock(BatchStateService::class);
		$this->unit = $this->createMock(BatchWorkUnit::class);
		$this->routing = $this->createMock(BatchRouting::class);
		$this->routing->method('backgroundEnabled')->willReturn(true);

	}//end setUp()

	/**
	 * The coordinator under test.
	 *
	 * @return BatchJobCoordinator
	 */
	private function job(): BatchJobCoordinator {
		$appConfig = $this->createMock(IAppConfig::class);
		$appConfig->method('getValueString')->willReturnCallback(
			fn (string $app, string $key, string $default): string => ($this->config[$key] ?? $default)
		);
		return new BatchJobCoordinator(
			time: $this->createMock(ITimeFactory::class),
			stateService: $this->state,
			workUnit: $this->unit,
			routing: $this->routing,
			appConfig: $appConfig,
			logger: $this->createMock(LoggerInterface::class)
		);

	}//end job()

	/**
	 * Run the job's protected run().
	 *
	 * @param BatchJobCoordinator $job The job.
	 *
	 * @return void
	 */
	private static function invokeRun(BatchJobCoordinator $job): void {
		(new ReflectionMethod($job, 'run'))->invoke($job, null);

	}//end invokeRun()

	/**
	 * The oldest batch with work left gets one unit with the default budgets.
	 *
	 * @return void
	 */
	public function testTheOldestBatchGetsOneUnitWithTheDefaultBudget(): void {
		$this->state->method('listActiveBackground')->willReturn([['batchId' => 'old'], ['batchId' => 'new']]);
		$this->unit->expects($this->once())->method('run')
			->with('old', 25, 120)
			->willReturn(['batchId' => 'old', 'batchStatus' => 'extracting', 'processed' => 25, 'throttled' => false]);

		$job = $this->job();
		$this->assertFalse($job->allowParallelRuns);
		self::invokeRun(job: $job);

	}//end testTheOldestBatchGetsOneUnitWithTheDefaultBudget()

	/**
	 * The tick budgets come from app config.
	 *
	 * @return void
	 */
	public function testTheBudgetsAreConfigurable(): void {
		$this->config = ['batch.files_per_tick' => '10', 'batch.seconds_per_tick' => '30'];
		$job = $this->job();

		$this->assertSame(10, $job->filesPerTick());
		$this->assertSame(30, $job->secondsPerTick());

		$this->config = ['batch.files_per_tick' => '0', 'batch.seconds_per_tick' => '-5'];
		$job = $this->job();
		$this->assertSame(1, $job->filesPerTick());
		$this->assertSame(1, $job->secondsPerTick());

	}//end testTheBudgetsAreConfigurable()

	/**
	 * Nothing to do: no unit runs.
	 *
	 * @return void
	 */
	public function testNoBatchMeansNoUnit(): void {
		$this->state->method('listActiveBackground')->willReturn([]);
		$this->unit->expects($this->never())->method('run');

		self::invokeRun(job: $this->job());

	}//end testNoBatchMeansNoUnit()

	/**
	 * With the legacy-sync engine the coordinator does not even list batches.
	 *
	 * @return void
	 */
	public function testTheLegacyEngineSwitchesTheCoordinatorOff(): void {
		$routing = $this->createMock(BatchRouting::class);
		$routing->method('backgroundEnabled')->willReturn(false);
		$this->routing = $routing;
		$this->state->expects($this->never())->method('listActiveBackground');
		$this->unit->expects($this->never())->method('run');

		self::invokeRun(job: $this->job());

	}//end testTheLegacyEngineSwitchesTheCoordinatorOff()

	/**
	 * A failing store or unit is logged, not thrown into cron.
	 *
	 * @return void
	 */
	public function testFailuresAreContained(): void {
		$this->state->method('listActiveBackground')->willReturn([['batchId' => 'b']]);
		$this->unit->method('run')->willThrowException(new RuntimeException('store down'));

		self::invokeRun(job: $this->job());

		$this->state = $this->createMock(BatchStateService::class);
		$this->state->method('listActiveBackground')->willThrowException(new RuntimeException('no OpenRegister'));
		self::invokeRun(job: $this->job());
		$this->addToAssertionCount(1);

	}//end testFailuresAreContained()
}//end class
