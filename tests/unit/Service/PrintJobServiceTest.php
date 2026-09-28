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
 * @spec openspec/changes/print-jobs-in-the-app/specs/print-preview/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\Filinq\Tests\Unit\Service;

require_once __DIR__ . '/PrintJobDoubles.php';

use InvalidArgumentException;
use OCA\Filinq\BackgroundJob\BatchPrintJob;
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
	 * Build the service over in-memory rows and files.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$pdf = $this->createMock(PdfService::class);
		$pdf->method('renderPdf')->willReturnCallback(
			static function (string $templateContent, array $data, array $options): string {
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
			$this->jobList,
			$this->createMock(LoggerInterface::class)
		);

	}//end setUp()

	/**
	 * Three letters go to print as one job, queued, with three PDFs in app data.
	 *
	 * @return void
	 */
	public function testThreeLettersGoToPrintAsOneJob(): void {
		$result = $this->service->createBatchJob(
			templateId: 'tmpl-1',
			items: [['data' => ['naam' => 'An']], ['data' => ['naam' => 'Bo']], ['data' => ['naam' => 'Cy']]],
			userId: 'handler-a'
		);

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
		$this->service->createJob(templateId: 'tmpl-1', data: ['naam' => 'An'], userId: 'handler-a');

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

		$this->service->createBatchJob(templateId: 'tmpl-1', items: [['dataRefs' => $refs, 'data' => ['extra' => 1]]], userId: 'u');

		$this->assertSame(['%PDF-Resolved'], array_values($this->stored));

	}//end testDataRefsAreResolved()

	/**
	 * A failed letter is in the manifest; the rest still go to print.
	 *
	 * @return void
	 */
	public function testAFailedLetterLeavesTheRest(): void {
		$result = $this->service->createBatchJob(
			templateId: 'tmpl-1',
			items: [['data' => ['naam' => 'An']], ['data' => ['naam' => 'kapot'], 'filename' => 'b.pdf']],
			userId: 'u'
		);

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

		$result = $this->service->createBatchJob(templateId: 'tmpl-1', items: $items, userId: 'u');

		$this->assertSame('rendering', $result['status']);
		$this->assertSame([], $this->stored);

	}//end testALargeBatchIsRenderedInTheBackground()

	/**
	 * The print service reports back and the job shows it with the time.
	 *
	 * @return void
	 */
	public function testThePrintServiceReportsBack(): void {
		$result = $this->service->createJob(templateId: 'tmpl-1', data: ['naam' => 'An'], userId: 'u');
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
		$single = $this->service->createJob(templateId: 'tmpl-1', data: ['naam' => 'An'], userId: 'u', filename: 'brief.pdf');
		$download = $this->service->download(job: $this->service->getJob($single['jobId']));
		$this->assertSame(['content' => '%PDF-An', 'filename' => 'brief.pdf', 'contentType' => 'application/pdf'], $download);

		$batch = $this->service->createBatchJob(templateId: 'tmpl-1', items: [['data' => ['naam' => 'Bo']], ['data' => ['naam' => 'Cy']]], userId: 'u', filename: 'post.pdf');
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
		$this->service->createJob(templateId: 'tmpl-1', data: ['naam' => 'An'], userId: 'handler-a');
		$this->service->createJob(templateId: 'tmpl-1', data: ['naam' => 'Bo'], userId: 'handler-b');

		$mine = $this->service->listJobs(userId: 'handler-a');

		$this->assertCount(1, $mine);
		$this->assertSame('handler-a', $mine[0]['requestedBy']);

	}//end testTheListHoldsOnlyTheCallersJobs()
}//end class
