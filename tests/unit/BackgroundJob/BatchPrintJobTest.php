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
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\Filinq\Tests\Unit\BackgroundJob;

use Exception;
use OCA\Filinq\BackgroundJob\BatchPrintJob;
use OCA\Filinq\Service\PdfService;
use OCA\Filinq\Service\PrintJobService;
use OCA\Filinq\Service\TemplateService;
use OCP\AppFramework\Utility\ITimeFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for BatchPrintJob
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\BackgroundJob
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class BatchPrintJobTest extends TestCase {

	/**
	 * The BatchPrintJob under test.
	 *
	 * @var BatchPrintJob
	 */
	private BatchPrintJob $job;

	/**
	 * Mock print job service.
	 *
	 * @var PrintJobService&MockObject
	 */
	private PrintJobService $mockPrintJobSvc;

	/**
	 * Mock PDF service.
	 *
	 * @var PdfService&MockObject
	 */
	private PdfService $mockPdfService;

	/**
	 * Mock template service.
	 *
	 * @var TemplateService&MockObject
	 */
	private TemplateService $mockTemplateSvc;

	/**
	 * Mock logger.
	 *
	 * @var LoggerInterface&MockObject
	 */
	private LoggerInterface $mockLogger;

	/**
	 * Set up test environment
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->mockPrintJobSvc = $this->createMock(originalClassName: PrintJobService::class);
		$this->mockPdfService = $this->createMock(originalClassName: PdfService::class);
		$this->mockTemplateSvc = $this->createMock(originalClassName: TemplateService::class);
		$this->mockLogger = $this->createMock(originalClassName: LoggerInterface::class);

		$timeFactory = $this->createMock(originalClassName: ITimeFactory::class);

		$this->job = new BatchPrintJob(
			time: $timeFactory,
			printJobSvc: $this->mockPrintJobSvc,
			pdfService: $this->mockPdfService,
			templateSvc: $this->mockTemplateSvc,
			logger: $this->mockLogger
		);

	}//end setUp()

	/**
	 * Test run logs error and returns early when jobId is missing
	 *
	 * @return void
	 */
	public function testRunLogsErrorWhenJobIdMissing(): void {
		$this->mockLogger->expects($this->once())->method('error');
		$this->mockTemplateSvc->expects($this->never())->method('getTemplate');

		$this->job->setArgument(['templateId' => 'tid', 'items' => []]);

		// Access protected run() via reflection.
		$reflection = new \ReflectionMethod($this->job, 'run');
		$reflection->setAccessible(true);
		$reflection->invoke($this->job, ['templateId' => 'tid', 'items' => []]);

	}//end testRunLogsErrorWhenJobIdMissing()

	/**
	 * Test run logs error and stores failed status when template not found
	 *
	 * @return void
	 */
	public function testRunStoresFailedStatusWhenTemplateNotFound(): void {
		$this->mockTemplateSvc->method('getTemplate')
			->willThrowException(new Exception('Template not found'));

		$this->mockPrintJobSvc->method('buildPrintConfig')->willReturn(
			['duplex' => false, 'color' => true, 'paperTray' => 'default', 'stapling' => false]
		);

		$this->mockPrintJobSvc->expects($this->atLeast(2))->method('storeJobStatus');
		$this->mockLogger->expects($this->atLeast(1))->method('error');

		$reflection = new \ReflectionMethod($this->job, 'run');
		$reflection->setAccessible(true);
		$reflection->invoke(
			$this->job,
			[
				'jobId' => 'job-123',
				'templateId' => 'template-uuid',
				'items' => [['data' => [], 'filename' => 'doc.pdf']],
				'options' => [],
				'userId' => 'user1',
			]
		);

	}//end testRunStoresFailedStatusWhenTemplateNotFound()

	/**
	 * Test run processes items and stores completed status
	 *
	 * @return void
	 */
	public function testRunProcessesItemsAndStoresCompletedStatus(): void {
		$this->mockTemplateSvc->method('getTemplate')
			->willReturn(
				[
					'content' => '<h1>Test</h1>',
					'name' => 'Test Template',
					'format' => 'A4',
					'orientation' => 'P',
				]
			);

		$this->mockPdfService->method('renderPdf')
			->willReturn('%PDF-1.4 fake content');

		$this->mockPrintJobSvc->method('buildPrintConfig')
			->willReturn(['duplex' => false, 'color' => true, 'paperTray' => 'default', 'stapling' => false]);

		$this->mockPrintJobSvc->method('buildManifest')
			->willReturn([['filename' => 'doc.pdf', 'status' => 'success']]);

		$this->mockPrintJobSvc->expects($this->atLeast(2))->method('storeJobStatus');
		$this->mockPrintJobSvc->expects($this->once())->method('storeJobPdf');

		$reflection = new \ReflectionMethod($this->job, 'run');
		$reflection->setAccessible(true);
		$reflection->invoke(
			$this->job,
			[
				'jobId' => 'job-123',
				'templateId' => 'template-uuid',
				'items' => [['data' => [], 'filename' => 'doc.pdf']],
				'options' => [],
				'userId' => 'user1',
			]
		);

	}//end testRunProcessesItemsAndStoresCompletedStatus()

	/**
	 * Test run handles individual item failures without aborting batch
	 *
	 * @return void
	 */
	public function testRunHandlesItemFailuresGracefully(): void {
		$this->mockTemplateSvc->method('getTemplate')
			->willReturn(
				[
					'content' => '<h1>Test</h1>',
					'name' => 'Test Template',
					'format' => 'A4',
					'orientation' => 'P',
				]
			);

		$this->mockPdfService->method('renderPdf')
			->willThrowException(new Exception('PDF generation failed'));

		$this->mockPrintJobSvc->method('buildPrintConfig')
			->willReturn(['duplex' => false, 'color' => true, 'paperTray' => 'default', 'stapling' => false]);

		$this->mockPrintJobSvc->method('buildManifest')
			->willReturn([['filename' => 'doc.pdf', 'status' => 'error']]);

		$this->mockLogger->expects($this->atLeast(1))->method('warning');

		$reflection = new \ReflectionMethod($this->job, 'run');
		$reflection->setAccessible(true);
		$reflection->invoke(
			$this->job,
			[
				'jobId' => 'job-456',
				'templateId' => 'template-uuid',
				'items' => [['data' => [], 'filename' => 'doc.pdf']],
				'options' => [],
				'userId' => 'user1',
			]
		);

	}//end testRunHandlesItemFailuresGracefully()
}//end class
