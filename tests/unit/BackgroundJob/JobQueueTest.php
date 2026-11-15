<?php

/**
 * The job queue puts each job name on Nextcloud's job list as the class that
 * runs it, and refuses a name it does not know.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\BackgroundJob
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural adapter, no functional requirement.
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\BackgroundJob;

use InvalidArgumentException;
use OCA\Filinq\BackgroundJob\BatchCorrespondenceJob;
use OCA\Filinq\BackgroundJob\BatchDocumentJob;
use OCA\Filinq\BackgroundJob\BatchPrintJob;
use OCA\Filinq\BackgroundJob\BulkSigningJob;
use OCA\Filinq\BackgroundJob\FolderExtractionJob;
use OCA\Filinq\BackgroundJob\IntakeOcrJob;
use OCA\Filinq\BackgroundJob\JobQueue;
use OCA\Filinq\Kernel\JobQueueInterface;
use OCP\BackgroundJob\IJobList;
use PHPUnit\Framework\TestCase;

class JobQueueTest extends TestCase {
	/**
	 * Every job name and the class that runs it.
	 *
	 * @return array<string, array{0: string, 1: string}>
	 */
	public static function jobProvider(): array {
		return [
			'batch correspondence' => [JobQueueInterface::BATCH_CORRESPONDENCE, BatchCorrespondenceJob::class],
			'batch document'       => [JobQueueInterface::BATCH_DOCUMENT, BatchDocumentJob::class],
			'batch print'          => [JobQueueInterface::BATCH_PRINT, BatchPrintJob::class],
			'bulk signing'         => [JobQueueInterface::BULK_SIGNING, BulkSigningJob::class],
			'folder extraction'    => [JobQueueInterface::FOLDER_EXTRACTION, FolderExtractionJob::class],
			'intake ocr'           => [JobQueueInterface::INTAKE_OCR, IntakeOcrJob::class],
		];

	}//end jobProvider()

	/**
	 * A queued name lands on the job list as its class, with its argument.
	 *
	 * @param string $name The job name.
	 * @param string $class The job class.
	 *
	 * @return void
	 *
	 * @dataProvider jobProvider
	 */
	public function testAJobIsQueuedAsItsClass(string $name, string $class): void {
		$jobList = $this->createMock(originalClassName: IJobList::class);
		$jobList->expects($this->once())->method('add')->with($class, ['id' => 7]);

		(new JobQueue(jobList: $jobList))->add(job: $name, argument: ['id' => 7]);

	}//end testAJobIsQueuedAsItsClass()

	/**
	 * Removing a name removes its class from the job list.
	 *
	 * @return void
	 */
	public function testAJobIsRemovedAsItsClass(): void {
		$jobList = $this->createMock(originalClassName: IJobList::class);
		$jobList->expects($this->once())->method('remove')->with(FolderExtractionJob::class, ['batchId' => 'b1']);

		(new JobQueue(jobList: $jobList))->remove(job: JobQueueInterface::FOLDER_EXTRACTION, argument: ['batchId' => 'b1']);

	}//end testAJobIsRemovedAsItsClass()

	/**
	 * An unknown name is refused and nothing is queued.
	 *
	 * @return void
	 */
	public function testAnUnknownJobIsRefused(): void {
		$jobList = $this->createMock(originalClassName: IJobList::class);
		$jobList->expects($this->never())->method('add');

		$this->expectException(exception: InvalidArgumentException::class);

		(new JobQueue(jobList: $jobList))->add(job: 'no-such-job', argument: []);

	}//end testAnUnknownJobIsRefused()
}//end class
