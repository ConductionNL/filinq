<?php

/**
 * The queued bulk-signing job hands its batch to the runner.
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
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\BackgroundJob;

use OCA\Filinq\BackgroundJob\BulkSigningJob;
use OCA\Filinq\Kernel\Contracts\BulkSigningRunnerInterface;
use OCP\AppFramework\Utility\ITimeFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * `BulkSigningJob`.
 *
 * @covers \OCA\Filinq\BackgroundJob\BulkSigningJob
 */
class BulkSigningJobTest extends TestCase {

	/**
	 * Arguments and the batch / user the runner must receive for them.
	 *
	 * @return array<string, array{0: mixed, 1: string, 2: string}>
	 */
	public static function arguments(): array {
		return [
			'batch and initiator' => [['batchId' => 'batch-7', 'userId' => 'alice'], 'batch-7', 'alice'],
			'numeric values are passed as strings' => [['batchId' => 42, 'userId' => 7], '42', '7'],
			'missing user' => [['batchId' => 'batch-7'], 'batch-7', ''],
			'missing batch' => [['userId' => 'alice'], '', 'alice'],
			'no arguments' => [[], '', ''],
			'a null argument' => [null, '', ''],
		];
	}//end arguments()

	/**
	 * The runner is called once, with the batch and the initiator from the argument.
	 *
	 * @param mixed  $argument The queued argument.
	 * @param string $batchId  The batch the runner must get.
	 * @param string $userId   The user the runner must get.
	 *
	 * @return void
	 */
	#[DataProvider('arguments')]
	public function testTheBatchIsHandedToTheRunner(mixed $argument, string $batchId, string $userId): void {
		$runner = $this->createMock(BulkSigningRunnerInterface::class);
		$runner->expects($this->once())
			->method('run')
			->with($batchId, $userId)
			->willReturn([]);

		$job = new BulkSigningJob($this->createMock(ITimeFactory::class), $runner);
		(new ReflectionMethod($job, 'run'))->invoke($job, $argument);
	}//end testTheBatchIsHandedToTheRunner()

	/**
	 * A runner failure is not swallowed: the job queue must see it.
	 *
	 * @return void
	 */
	public function testARunnerFailurePropagates(): void {
		$runner = $this->createMock(BulkSigningRunnerInterface::class);
		$runner->method('run')->willThrowException(new \RuntimeException('signing service down'));

		$job = new BulkSigningJob($this->createMock(ITimeFactory::class), $runner);

		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('signing service down');
		(new ReflectionMethod($job, 'run'))->invoke($job, ['batchId' => 'b', 'userId' => 'u']);
	}//end testARunnerFailurePropagates()
}//end class
