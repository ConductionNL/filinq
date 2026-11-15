<?php

/**
 * The nightly reconciliation's clock.
 *
 * 🔑 THE JOB HOLDS NO DECISIONS, so there is little to assert here beyond the
 * two things a scheduler cares about: that the night actually runs, and that a
 * run which cannot start does not take the instance's whole cron pass with it.
 * Everything about what a drifted folder means is asserted in
 * DomainFolderReconcilerTest, where it needs no scheduler.
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

use OCA\Filinq\BackgroundJob\DomainFolderReconciliationJob;
use OCA\Filinq\Service\DomainFolderReconciler;
use OCP\AppFramework\Utility\ITimeFactory;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use ReflectionClass;
use RuntimeException;

/**
 * `DomainFolderReconciliationJob`.
 *
 * @covers \OCA\Filinq\BackgroundJob\DomainFolderReconciliationJob
 */
class DomainFolderReconciliationJobTest extends TestCase {

	/**
	 * A reconciler double.
	 *
	 * `onlyMethods` so it cannot answer a `run()` the real class lacks.
	 *
	 * @param bool $throws Whether the run refuses to start.
	 *
	 * @return DomainFolderReconciler The double.
	 */
	private function reconciler(bool $throws): DomainFolderReconciler {
		$reconciler = $this->getMockBuilder(DomainFolderReconciler::class)
			->disableOriginalConstructor()
			->onlyMethods(['run'])
			->getMock();

		if ($throws === true) {
			$reconciler->expects(self::once())->method('run')->willThrowException(new RuntimeException('no register'));
		} else {
			$reconciler->expects(self::once())->method('run')->willReturn(
				['skipped' => false, 'reason' => '', 'corrected' => [], 'correctedCount' => 0, 'refused' => [], 'refusedCount' => 0, 'pinned' => [], 'pinnedCount' => 0, 'inStepCount' => 0]
			);
		}

		return $reconciler;
	}//end reconciler()

	/**
	 * The night runs.
	 *
	 * @return void
	 */
	public function testRunsTheReconciler(): void {
		$job = new DomainFolderReconciliationJob(
			$this->createMock(ITimeFactory::class),
			$this->reconciler(false),
			new NullLogger()
		);

		$this->invokeRun(job: $job);
	}//end testRunsTheReconciler()

	/**
	 * A run that cannot start is logged, not rethrown into the cron pass.
	 *
	 * @return void
	 */
	public function testAFailedRunDoesNotEscape(): void {
		$job = new DomainFolderReconciliationJob(
			$this->createMock(ITimeFactory::class),
			$this->reconciler(true),
			new NullLogger()
		);

		$this->invokeRun(job: $job);

		self::assertTrue(true, 'the throw was contained');
	}//end testAFailedRunDoesNotEscape()

	/**
	 * Call the protected run(), which is what the scheduler calls.
	 *
	 * @param DomainFolderReconciliationJob $job The job.
	 *
	 * @return void
	 */
	private function invokeRun(DomainFolderReconciliationJob $job): void {
		$method = (new ReflectionClass($job))->getMethod('run');
		$method->setAccessible(true);
		$method->invoke($job, null);
	}//end invokeRun()
}//end class
