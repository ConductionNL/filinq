<?php

/**
 * Unit tests for BatchPrintJob
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\BackgroundJob
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/print-jobs-in-the-app/specs/print-preview/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\Filinq\Tests\Unit\BackgroundJob;

require_once __DIR__ . '/../Service/PrintJobDoubles.php';

use OCA\Filinq\BackgroundJob\BatchPrintJob;
use OCA\Filinq\Service\DataResolverService;
use OCA\Filinq\Service\PdfService;
use OCA\Filinq\Service\PrintJobService;
use OCA\Filinq\Service\TemplateService;
use OCA\Filinq\Tests\Unit\Service\PrintJobDoubles;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * A large batch is rendered by the background job into the same job.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\BackgroundJob
 * @author   Conduction Development Team <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class BatchPrintJobTest extends TestCase {
	use PrintJobDoubles;

	/**
	 * The dispatched argument of a large batch renders into its job.
	 *
	 * @return void
	 */
	public function testTheQueuedBatchIsRenderedIntoItsJob(): void {
		$pdf = $this->createMock(PdfService::class);
		$pdf->method('renderPdf')->willReturn('%PDF');
		$templates = $this->createMock(TemplateService::class);
		$templates->method('getTemplate')->willReturn(['id' => 't', 'content' => '']);
		$jobList = $this->createMock(IJobList::class);
		$dispatched = null;
		$jobList->method('add')->willReturnCallback(
			static function (string $class, mixed $argument) use (&$dispatched): void {
				$dispatched = $argument;
			}
		);
		$service = new PrintJobService(
			$pdf,
			$templates,
			$this->createMock(DataResolverService::class),
			$this->printJobRepository(),
			$this->printJobFileStore(),
			$jobList,
			$this->createMock(LoggerInterface::class)
		);

		$created = $service->createBatchJob(templateId: 't', items: array_fill(0, 12, ['data' => []]), userId: 'u');
		$this->assertSame('rendering', $created['status']);

		$job = new BatchPrintJob($this->createMock(ITimeFactory::class), $service, $this->createMock(LoggerInterface::class));
		// The QueuedJob stub's execute() does nothing, so run() is called the
		// way the real QueuedJob::start() calls it.
		(new \ReflectionMethod($job, 'run'))->invoke($job, $dispatched);

		$stored = $this->rows[$created['jobId']];
		$this->assertSame('queued', $stored['status']);
		$this->assertSame(12, $stored['rendered']);
		$this->assertCount(12, $this->stored);

	}//end testTheQueuedBatchIsRenderedIntoItsJob()

	/**
	 * A job with a template nobody can load is failed, not left rendering.
	 *
	 * @return void
	 */
	public function testAMissingTemplateFailsTheJob(): void {
		$templates = $this->createMock(TemplateService::class);
		$templates->method('getTemplate')->willThrowException(new \Exception('gone'));
		$service = new PrintJobService(
			$this->createMock(PdfService::class),
			$templates,
			$this->createMock(DataResolverService::class),
			$this->printJobRepository(),
			$this->printJobFileStore(),
			$this->createMock(IJobList::class),
			$this->createMock(LoggerInterface::class)
		);
		$this->rows['j'] = ['uuid' => 'j', 'status' => 'rendering', 'requestedBy' => 'u', 'total' => 1];

		$job = new BatchPrintJob($this->createMock(ITimeFactory::class), $service, $this->createMock(LoggerInterface::class));
		(new \ReflectionMethod($job, 'run'))->invoke($job, ['jobId' => 'j', 'templateId' => 't', 'items' => [['data' => []]], 'options' => []]);

		$this->assertSame('failed', $this->rows['j']['status']);
		$this->assertSame('Template not found', $this->rows['j']['statusDetails']);

	}//end testAMissingTemplateFailsTheJob()
}//end class
