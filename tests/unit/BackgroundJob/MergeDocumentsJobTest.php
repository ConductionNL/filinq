<?php

/**
 * Unit tests for MergeDocumentsJob, the sweep that runs queued merges.
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
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\BackgroundJob;

use OCA\Filinq\BackgroundJob\MergeDocumentsJob;
use OCA\Filinq\Kernel\Contracts\DocumentMergeServiceInterface;
use OCA\Filinq\Kernel\Contracts\MergeJobRepositoryInterface;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionMethod;
use ReflectionProperty;
use RuntimeException;

/**
 * Every queued merge is resumed; one failure does not stop the others.
 *
 * @covers \OCA\Filinq\BackgroundJob\MergeDocumentsJob
 */
class MergeDocumentsJobTest extends TestCase {

	/**
	 * Run the job's sweep once.
	 *
	 * @param MergeDocumentsJob $job The job.
	 *
	 * @return void
	 */
	private function sweep(MergeDocumentsJob $job): void {
		(new ReflectionMethod(objectOrMethod: $job, method: 'run'))->invoke($job, null);
	}//end sweep()

	/**
	 * The job runs every five minutes.
	 *
	 * @return void
	 */
	public function testTheSweepRunsEveryFiveMinutes(): void {
		$job = new MergeDocumentsJob(
			time: $this->createMock(originalClassName: ITimeFactory::class),
			jobs: $this->createMock(originalClassName: MergeJobRepositoryInterface::class),
			merges: $this->createMock(originalClassName: DocumentMergeServiceInterface::class),
			userSession: $this->createMock(originalClassName: IUserSession::class),
			logger: $this->createMock(originalClassName: LoggerInterface::class)
		);

		$this->assertSame(expected: 300, actual: (new ReflectionProperty(class: $job, property: 'interval'))->getValue($job));
	}//end testTheSweepRunsEveryFiveMinutes()

	/**
	 * Each queued job with a uuid is resumed in order; one without is skipped;
	 * a failing one is logged with its uuid and the rest still run.
	 *
	 * @return void
	 */
	public function testQueuedMergesAreResumedAndAFailureIsLogged(): void {
		$queued = [
			['uuid' => 'm-1', 'inputs' => [1]],
			['inputs' => [2]],
			['uuid' => '', 'inputs' => [3]],
			['uuid' => 'm-4', 'inputs' => [4]],
			['uuid' => 'm-5', 'inputs' => [5]],
		];
		$jobs = $this->createMock(originalClassName: MergeJobRepositoryInterface::class);
		$jobs->expects($this->once())->method('findQueued')->willReturn($queued);

		$resumed = [];
		$merges  = $this->createMock(originalClassName: DocumentMergeServiceInterface::class);
		$merges->method('resume')->willReturnCallback(
			static function (array $job) use (&$resumed): array {
				$resumed[] = $job['uuid'];
				if ($job['uuid'] === 'm-4') {
					throw new RuntimeException(message: 'source gone');
				}

				return $job;
			}
		);

		$logger = $this->createMock(originalClassName: LoggerInterface::class);
		$logger->expects($this->once())
			->method('error')
			->with(
				'[MergeDocumentsJob] a queued merge could not be run',
				$this->callback(callback: static fn (array $context): bool => $context['uuid'] === 'm-4' && $context['error'] === 'source gone')
			);

		$this->sweep(
			job: new MergeDocumentsJob(
				time: $this->createMock(originalClassName: ITimeFactory::class),
				jobs: $jobs,
				merges: $merges,
				userSession: $this->createMock(originalClassName: IUserSession::class),
				logger: $logger
			)
		);

		$this->assertSame(expected: ['m-1', 'm-4', 'm-5'], actual: $resumed);
	}//end testQueuedMergesAreResumedAndAFailureIsLogged()

	/**
	 * An empty queue resumes nothing and logs nothing.
	 *
	 * @return void
	 */
	public function testAnEmptyQueueDoesNothing(): void {
		$jobs = $this->createMock(originalClassName: MergeJobRepositoryInterface::class);
		$jobs->method('findQueued')->willReturn([]);
		$merges = $this->createMock(originalClassName: DocumentMergeServiceInterface::class);
		$merges->expects($this->never())->method('resume');
		$logger = $this->createMock(originalClassName: LoggerInterface::class);
		$logger->expects($this->never())->method('error');

		$this->sweep(
			job: new MergeDocumentsJob(
				time: $this->createMock(originalClassName: ITimeFactory::class),
				jobs: $jobs,
				merges: $merges,
				userSession: $this->createMock(originalClassName: IUserSession::class),
				logger: $logger
			)
		);
	}//end testAnEmptyQueueDoesNothing()
}//end class
