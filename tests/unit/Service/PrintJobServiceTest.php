<?php

/**
 * Unit tests for PrintJobService
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/specs/print-preview/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\Filinq\Tests\Unit\Service;

require_once __DIR__ . '/PrintJobDoubles.php';

use InvalidArgumentException;
use OCA\Filinq\BackgroundJob\BatchPrintJob;
use OCA\Filinq\BackgroundJob\JobQueue;
use OCA\Filinq\Kernel\PrintJobRequest;
use OCA\Filinq\Service\DataResolverService;
use OCA\Filinq\Service\PdfService;
use OCA\Filinq\Service\PrintJobService;
use OCA\Filinq\Service\TemplateService;
use OCP\BackgroundJob\IJobList;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ZipArchive;

/**
 * A handler sends letters to print and follows the job (REQ-PJA-001).
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction Development Team <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class PrintJobServiceTest extends TestCase {
	use PrintJobDoubles;

	/**
	 * The job list.
	 *
	 * @var IJobList&MockObject
	 */
	private IJobList $jobList;

	/**
	 * The resolver for dataRefs.
	 *
	 * @var DataResolverService&MockObject
	 */
	private DataResolverService $resolver;

	/**
	 * The service under test.
	 *
	 * @var PrintJobService
	 */
	private PrintJobService $service;

	/**
	 * The PDF options of every render, in order.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $pdfOptions = [];

	/**
	 * Build the service over in-memory rows and files.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$pdf = $this->createMock(PdfService::class);
		$pdf->method('renderPdf')->willReturnCallback(
			function (string $templateContent, array $data, array $options): string {
				$this->pdfOptions[] = $options;
				if (($data['naam'] ?? '') === 'kapot') {
					throw new \RuntimeException('render failed');
				}

				return '%PDF-' . ($data['naam'] ?? '?');
			}
		);
		$templates = $this->createMock(TemplateService::class);
		$templates->method('getTemplate')->willReturn(['id' => 'tmpl-1', 'name' => 'Brief', 'content' => '<p>{{ naam }}</p>']);
		$this->jobList = $this->createMock(IJobList::class);
		$this->resolver = $this->createMock(DataResolverService::class);

		$this->service = new PrintJobService(
			$pdf,
			$templates,
			$this->resolver,
			$this->printJobRepository(),
			$this->printJobFileStore(),
			new JobQueue(jobList: $this->jobList),
			$this->createMock(LoggerInterface::class)
		);

	}//end setUp()

	/**
	 * Three letters go to print as one job, queued, with three PDFs in app data.
	 *
	 * @return void
	 */
	public function testThreeLettersGoToPrintAsOneJob(): void {
		$result = $this->service->createBatchJob(request: new PrintJobRequest(templateId: 'tmpl-1', userId: 'handler-a'), items: [['data' => ['naam' => 'An']], ['data' => ['naam' => 'Bo']], ['data' => ['naam' => 'Cy']]]);

		$this->assertSame('queued', $result['status']);
		$this->assertCount(1, $this->rows);
		$job = $this->rows[$result['jobId']];
		$this->assertSame(3, $job['total']);
		$this->assertSame(3, $job['rendered']);
		$this->assertSame('handler-a', $job['requestedBy']);
		$this->assertCount(3, $job['files']);
		$this->assertSame(['%PDF-An', '%PDF-Bo', '%PDF-Cy'], array_values($this->stored));

	}//end testThreeLettersGoToPrintAsOneJob()

	/**
	 * No PDF and no job ever lands in app configuration: every write is a
	 * printJob row without file bytes, and the bytes are in app data.
	 *
	 * @return void
	 */
	public function testNoPdfInTheAppConfiguration(): void {
		$this->service->createJob(request: new PrintJobRequest(templateId: 'tmpl-1', userId: 'handler-a'), data: ['naam' => 'An']);

		foreach ($this->written as $payload) {
			$this->assertStringNotContainsString('%PDF', (string) json_encode($payload));
			$this->assertStringNotContainsString(base64_encode('%PDF-An'), (string) json_encode($payload));
		}

		$this->assertSame(['%PDF-An'], array_values($this->stored));

	}//end testNoPdfInTheAppConfiguration()

	/**
	 * A letter's dataRefs are resolved like a generation, its data on top.
	 *
	 * @return void
	 */
	public function testDataRefsAreResolved(): void {
		$refs = [['register' => 'brp', 'schema' => 'persoon', 'id' => 'p-1']];
		$this->resolver->expects($this->once())->method('resolve')
			->with($refs, [], ['extra' => 1])
			->willReturn(['data' => ['naam' => 'Resolved'], 'errors' => [], 'warnings' => []]);

		$this->service->createBatchJob(request: new PrintJobRequest(templateId: 'tmpl-1', userId: 'u'), items: [['dataRefs' => $refs, 'data' => ['extra' => 1]]]);

		$this->assertSame(['%PDF-Resolved'], array_values($this->stored));

	}//end testDataRefsAreResolved()

	/**
	 * A failed letter is in the manifest; the rest still go to print.
	 *
	 * @return void
	 */
	public function testAFailedLetterLeavesTheRest(): void {
		$result = $this->service->createBatchJob(request: new PrintJobRequest(templateId: 'tmpl-1', userId: 'u'), items: [['data' => ['naam' => 'An']], ['data' => ['naam' => 'kapot'], 'filename' => 'b.pdf']]);

		$job = $this->rows[$result['jobId']];
		$this->assertSame('queued', $job['status']);
		$this->assertSame(1, $job['errors']);
		$this->assertSame('error', $job['manifest'][1]['status']);
		$this->assertSame('b.pdf', $job['manifest'][1]['filename']);

	}//end testAFailedLetterLeavesTheRest()

	/**
	 * A large batch is stored as rendering and handed to the background job.
	 *
	 * @return void
	 */
	public function testALargeBatchIsRenderedInTheBackground(): void {
		$items = array_fill(0, 11, ['data' => ['naam' => 'X']]);
		$this->jobList->expects($this->once())->method('add')
			->with(BatchPrintJob::class, $this->callback(static fn (array $a): bool => $a['jobId'] === 'job-1' && count($a['items']) === 11));

		$result = $this->service->createBatchJob(request: new PrintJobRequest(templateId: 'tmpl-1', userId: 'u'), items: $items);

		$this->assertSame('rendering', $result['status']);
		$this->assertSame([], $this->stored);

	}//end testALargeBatchIsRenderedInTheBackground()

	/**
	 * The print service reports back and the job shows it with the time.
	 *
	 * @return void
	 */
	public function testThePrintServiceReportsBack(): void {
		$result = $this->service->createJob(request: new PrintJobRequest(templateId: 'tmpl-1', userId: 'u'), data: ['naam' => 'An']);
		$job = $this->service->getJob($result['jobId']);
		$job['statusChangedAt'] = '2000-01-01T00:00:00+00:00';

		$after = $this->service->recordExternalStatus(job: $job, externalStatus: 'printed', details: 'tray 2');

		$this->assertSame('printed', $after['status']);
		$this->assertSame('tray 2', $after['statusDetails']);
		$this->assertNotSame('2000-01-01T00:00:00+00:00', $after['statusChangedAt']);
		$this->assertSame('sent', $this->service->recordExternalStatus(job: $after, externalStatus: 'printing', details: null)['status']);

	}//end testThePrintServiceReportsBack()

	/**
	 * An unknown status is refused.
	 *
	 * @return void
	 */
	public function testAnUnknownStatusIsRefused(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->service->recordExternalStatus(job: ['uuid' => 'x'], externalStatus: 'lost', details: null);

	}//end testAnUnknownStatusIsRefused()

	/**
	 * One letter downloads as its PDF, several as a ZIP with the manifest.
	 *
	 * @return void
	 */
	public function testTheDownload(): void {
		$single = $this->service->createJob(request: new PrintJobRequest(templateId: 'tmpl-1', userId: 'u', filename: 'brief.pdf'), data: ['naam' => 'An']);
		$download = $this->service->download(job: $this->service->getJob($single['jobId']));
		$this->assertSame(['content' => '%PDF-An', 'filename' => 'brief.pdf', 'contentType' => 'application/pdf'], $download);

		$batch = $this->service->createBatchJob(request: new PrintJobRequest(templateId: 'tmpl-1', userId: 'u', filename: 'post.pdf'), items: [['data' => ['naam' => 'Bo']], ['data' => ['naam' => 'Cy']]]);
		$job = $this->service->getJob($batch['jobId']);
		$zip = $this->service->download(job: $job);
		$this->assertSame('post.zip', $zip['filename']);
		$path = tempnam(sys_get_temp_dir(), 'zip');
		file_put_contents($path, $zip['content']);
		$archive = new ZipArchive();
		$this->assertTrue($archive->open($path));
		$this->assertSame(3, $archive->numFiles);
		$this->assertNotFalse($archive->locateName('manifest.json'));
		$archive->close();
		unlink($path);

		$this->assertSame('%PDF-Cy', $this->service->download(job: $job, item: 1)['content']);

	}//end testTheDownload()

	/**
	 * The list holds only the caller's own jobs, newest first.
	 *
	 * @return void
	 */
	public function testTheListHoldsOnlyTheCallersJobs(): void {
		$this->service->createJob(request: new PrintJobRequest(templateId: 'tmpl-1', userId: 'handler-a'), data: ['naam' => 'An']);
		$this->service->createJob(request: new PrintJobRequest(templateId: 'tmpl-1', userId: 'handler-b'), data: ['naam' => 'Bo']);

		$mine = $this->service->listJobs(userId: 'handler-a');

		$this->assertCount(1, $mine);
		$this->assertSame('handler-a', $mine[0]['requestedBy']);

	}//end testTheListHoldsOnlyTheCallersJobs()
	/**
	 * The stored job row, the queued background work and the answer all
	 * carry the template, the options, the user and the download name.
	 *
	 * @return void
	 */
	public function testALargeBatchStoresAndQueuesTheWholeRequest(): void {
		$items = array_fill(0, 11, ['data' => ['naam' => 'X']]);
		$options = ['duplex' => true, 'paperTray' => 'tray-2'];
		$this->jobList->expects($this->once())->method('add')
			->with(BatchPrintJob::class, ['jobId' => 'job-1', 'templateId' => 'tmpl-1', 'items' => $items, 'options' => $options]);

		$result = $this->service->createBatchJob(request: new PrintJobRequest(templateId: 'tmpl-1', options: $options, userId: 'handler-a', filename: 'post.pdf'), items: $items);

		$printConfig = ['duplex' => true, 'color' => true, 'paperTray' => 'tray-2', 'stapling' => false];
		$this->assertSame(['jobId' => 'job-1', 'status' => 'rendering', 'total' => 11, 'printConfig' => $printConfig], $result);
		$this->assertCount(1, $this->written);
		$row = $this->written[0];
		$this->assertIsString($row['requestedAt']);
		$this->assertSame($row['requestedAt'], $row['statusChangedAt']);
		unset($row['requestedAt'], $row['statusChangedAt']);
		$this->assertSame(
			[
				'status' => 'rendering',
				'requestedBy' => 'handler-a',
				'templateId' => 'tmpl-1',
				'filename' => 'post.pdf',
				'total' => 11,
				'rendered' => 0,
				'errors' => 0,
				'files' => [],
				'printConfig' => $printConfig,
				'manifest' => [],
			],
			$row
		);

	}//end testALargeBatchStoresAndQueuesTheWholeRequest()

	/**
	 * A batch of exactly the sync limit is rendered now, with the request's
	 * options reaching the PDF renderer.
	 *
	 * @return void
	 */
	public function testABatchAtTheLimitIsRenderedNowWithItsOptions(): void {
		$this->jobList->expects($this->never())->method('add');

		$result = $this->service->createBatchJob(request: new PrintJobRequest(templateId: 'tmpl-1', options: ['pdfa' => true, 'stapling' => true], userId: 'u'), items: ['a' => ['data' => ['naam' => 'An']]] + array_fill(0, 9, ['data' => ['naam' => 'Bo']]));

		$this->assertSame('queued', $result['status']);
		$this->assertSame(10, $result['total']);
		$this->assertCount(10, $this->pdfOptions);
		$this->assertTrue($this->pdfOptions[0]['pdfa']);
		$this->assertTrue($this->pdfOptions[0]['stapling']);
		$this->assertSame('%PDF-An', $this->stored[array_key_first($this->stored)]);

	}//end testABatchAtTheLimitIsRenderedNowWithItsOptions()

	/**
	 * Without a download name a single letter is document.pdf, both as the
	 * job's name and as the letter's, and a batch is print-job.pdf.
	 *
	 * @return void
	 */
	public function testTheDefaultDownloadNames(): void {
		$single = $this->service->createJob(request: new PrintJobRequest(templateId: 'tmpl-1', options: ['color' => false], userId: 'u'), data: ['naam' => 'An']);
		$job = $this->rows[$single['jobId']];
		$this->assertSame('document.pdf', $job['filename']);
		$this->assertSame('document.pdf', $job['manifest'][0]['filename']);
		$this->assertFalse($job['printConfig']['color']);
		$this->assertSame('u', $job['requestedBy']);

		$batch = $this->service->createBatchJob(request: new PrintJobRequest(templateId: 'tmpl-1'), items: [['data' => ['naam' => 'Bo']]]);
		$this->assertSame('print-job.pdf', $this->rows[$batch['jobId']]['filename']);
		$this->assertSame('', $this->rows[$batch['jobId']]['requestedBy']);

	}//end testTheDefaultDownloadNames()
}//end class
