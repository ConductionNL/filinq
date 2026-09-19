<?php

/**
 * Unit tests for DocumentMergeService
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
 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
 */

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Exception\MergeRefusedException;
use OCA\Filinq\Service\DocumentMergeService;
use OCA\Filinq\Service\MergeCoverRenderer;
use OCA\Filinq\Service\MergeJobRepository;
use OCA\Filinq\Service\PdfConversionService;
use OCA\Filinq\Service\Pdfa3ConversionService;
use OCA\Filinq\Service\PdfDocumentFactory;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;
use setasign\Fpdi\Fpdi;

/**
 * Asserts what comes out of a merge and what does not.
 *
 * The PDFs here are REAL: they are produced by FPDF and merged by the real
 * FPDI-backed factory, so the page order and the outline are read out of the
 * bytes rather than out of a double that was told what to say. Only the
 * conversion cascade, the archival step and the register are doubled, because
 * those reach outside the process.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class DocumentMergeServiceTest extends TestCase {

	/**
	 * Jobs the fake register was asked to store, in order.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $stored = [];

	/**
	 * The bytes written to the target folder, keyed by name.
	 *
	 * @var array<string, string>
	 */
	private array $written = [];

	/**
	 * A real PDF of the given page count, each page carrying its label.
	 *
	 * @param string $label What the pages say.
	 * @param int $pages How many pages.
	 *
	 * @return string The PDF bytes.
	 */
	private function pdf(string $label, int $pages): string {
		$pdf = new Fpdi();
		for ($page = 1; $page <= $pages; $page++) {
			$pdf->AddPage();
			$pdf->SetFont('Helvetica', '', 16);
			$pdf->Cell(0, 10, $label . ' page ' . $page);
		}

		// @phpstan-ignore-next-line method.notFound (FPDF stubs are not loaded)
		return (string)$pdf->Output('S');

	}//end pdf()

	/**
	 * A file double whose content is the given bytes.
	 *
	 * @param int $fileId The file id.
	 * @param string $name The file name.
	 * @param string $content Its bytes.
	 * @param Folder|null $parent The folder it sits in.
	 *
	 * @return File The double.
	 */
	private function file(int $fileId, string $name, string $content, ?Folder $parent = null): File {
		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn($fileId);
		$file->method('getName')->willReturn($name);
		$file->method('getContent')->willReturn($content);
		$file->method('getSize')->willReturn(strlen($content));
		if ($parent !== null) {
			$file->method('getParent')->willReturn($parent);
		}

		return $file;

	}//end file()

	/**
	 * Build the service over the given inputs.
	 *
	 * @param array<int, array<string, mixed>> $files File id to `{name, content}`, the files the user can read.
	 * @param bool $writable Whether the target folder accepts a new file.
	 * @param bool $conversionFails Whether the conversion cascade refuses the second input.
	 * @param bool $archivalWorks Whether the instance can produce PDF/A-3b.
	 *
	 * @return DocumentMergeService The service under test.
	 */
	private function service(
		array $files,
		bool $writable = true,
		bool $conversionFails = false,
		bool $archivalWorks = false,
	): DocumentMergeService {
		$folder = $this->createMock(Folder::class);
		$folder->method('isCreatable')->willReturn($writable);
		$folder->method('getNonExistingName')->willReturnArgument(0);
		$folder->method('newFile')->willReturnCallback(
			function (string $name, string $content): File {
				$this->written[$name] = $content;

				return $this->file(fileId: 9001, name: $name, content: $content);
			}
		);

		$nodes = [];
		foreach ($files as $fileId => $spec) {
			$nodes[$fileId] = $this->file(
				fileId: $fileId,
				name: $spec['name'],
				content: $spec['content'],
				parent: $folder
			);
		}

		$userFolder = $this->createMock(Folder::class);
		$userFolder->method('getById')->willReturnCallback(
			static fn (int $fileId): array => (isset($nodes[$fileId]) === true ? [$nodes[$fileId]] : [])
		);

		$rootFolder = $this->createMock(IRootFolder::class);
		$rootFolder->method('getUserFolder')->willReturn($userFolder);

		$conversion = $this->createMock(PdfConversionService::class);
		$conversion->method('convertToPdfReporting')->willReturnCallback(
			function (File $source) use ($conversionFails): array {
				if ($conversionFails === true && $source->getName() === 'plattegrond.png') {
					throw new RuntimeException('no backend handles image/png; tried office, mpdf');
				}

				// Every input is already a PDF in these tests, so the cascade
				// hands back what it was given: what is under test here is the
				// ORDER and the outline, not the conversion.
				return ['file' => $source, 'backend' => 'test'];
			}
		);

		$archival = $this->createMock(Pdfa3ConversionService::class);
		if ($archivalWorks === true) {
			$archival->method('convertExistingPdf')->willReturnCallback(
				static fn (File $source): array => [
					'content' => $source->getContent(),
					'checksumSha256' => '',
					'pages' => 0,
					'conformance' => 'PDF/A-3b',
				]
			);
		} else {
			$archival->method('convertExistingPdf')->willThrowException(
				new RuntimeException('ghostscript is not available on this instance')
			);
		}

		$jobs = $this->createMock(MergeJobRepository::class);
		$jobs->method('create')->willReturnCallback(
			function (array $job): array {
				$job['uuid'] = 'merge-1';
				$this->stored[] = $job;

				return $job;
			}
		);
		$jobs->method('save')->willReturnCallback(
			function (array $job, string $uuid): array {
				$job['uuid'] = $uuid;
				$this->stored[] = $job;

				return $job;
			}
		);

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('anna');
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);

		return new DocumentMergeService(
			$jobs,
			$conversion,
			$archival,
			new PdfDocumentFactory(),
			$this->createMock(MergeCoverRenderer::class),
			$rootFolder,
			$session,
			$this->createMock(LoggerInterface::class)
		);

	}//end service()

	/**
	 * The three inputs every ordering test uses.
	 *
	 * @return array<int, array<string, mixed>> The files.
	 */
	private function threeDocuments(): array {
		return [
			11 => ['name' => 'aanvraag.pdf', 'content' => $this->pdf(label: 'Aanvraag', pages: 2)],
			12 => ['name' => 'plattegrond.png', 'content' => $this->pdf(label: 'Plattegrond', pages: 1)],
			13 => ['name' => 'besluit.pdf', 'content' => $this->pdf(label: 'Besluit', pages: 3)],
		];

	}//end threeDocuments()

	/**
	 * Three files become one PDF in the chosen order, with three bookmarks.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
	 */
	public function testThreeFilesBecomeOnePdfInTheChosenOrder(): void {
		$service = $this->service(files: $this->threeDocuments());

		$job = $service->merge(
			inputs: [
				['fileId' => 11, 'label' => 'Aanvraag'],
				['fileId' => 12, 'label' => 'Plattegrond'],
				['fileId' => 13, 'label' => 'Besluit'],
			],
			options: ['bookmarks' => true, 'name' => 'bundel']
		);

		$this->assertSame('done', $job['status']);
		$this->assertSame(6, $job['pageCount'], 'The page count is the sum of the inputs.');
		$this->assertSame(9001, $job['resultFileId']);
		$this->assertSame(100, $job['progress']);

		$merged = $this->written['bundel.pdf'];
		$this->assertStringStartsWith('%PDF', $merged);
		$this->assertStringContainsString('/Outlines', $merged, 'A merged bundle carries an outline.');
		$this->assertStringContainsString('/UseOutlines', $merged, 'And the catalog points at it, or no reader shows it.');
		foreach (['Aanvraag', 'Plattegrond', 'Besluit'] as $label) {
			$this->assertStringContainsString($label, $merged, $label . ' must be a bookmark in the result.');
		}

	}//end testThreeFilesBecomeOnePdfInTheChosenOrder()

	/**
	 * Bookmarks off means no outline at all, not an empty one.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
	 */
	public function testBookmarksOffProducesNoOutline(): void {
		$service = $this->service(files: $this->threeDocuments());

		$service->merge(
			inputs: [['fileId' => 11, 'label' => 'Aanvraag'], ['fileId' => 13, 'label' => 'Besluit']],
			options: ['bookmarks' => false, 'name' => 'kaal']
		);

		$this->assertStringNotContainsString('/Outlines', $this->written['kaal.pdf']);

	}//end testBookmarksOffProducesNoOutline()

	/**
	 * An input nobody can convert fails the job and writes no file.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
	 */
	public function testAnInputNobodyCanConvertFailsTheJobCleanly(): void {
		$service = $this->service(files: $this->threeDocuments(), conversionFails: true);

		$job = $service->merge(
			inputs: [
				['fileId' => 11, 'label' => 'Aanvraag'],
				['fileId' => 12, 'label' => 'Plattegrond'],
				['fileId' => 13, 'label' => 'Besluit'],
			],
			options: ['name' => 'mislukt']
		);

		$this->assertSame('failed', $job['status']);
		$this->assertStringContainsString('Plattegrond', $job['lastError'], 'The failure names the file.');
		$this->assertStringContainsString('mpdf', $job['lastError'], 'And the backends that were tried.');
		$this->assertSame([], $this->written, 'A failed merge writes no partial file.');

	}//end testAnInputNobodyCanConvertFailsTheJobCleanly()

	/**
	 * A file the user may not read is refused, and no job is created.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
	 */
	public function testAFileTheUserMayNotReadIsRefusedAndCreatesNoJob(): void {
		$service = $this->service(files: $this->threeDocuments());

		try {
			$service->merge(
				inputs: [['fileId' => 11, 'label' => 'Aanvraag'], ['fileId' => 99, 'label' => 'Geheim']],
				options: []
			);
			$this->fail('An unreadable input must be refused.');
		} catch (MergeRefusedException $refusal) {
			$this->assertSame(403, $refusal->getStatus());
		}

		$this->assertSame([], $this->stored, 'A refusal creates no job.');
		$this->assertSame([], $this->written);

	}//end testAFileTheUserMayNotReadIsRefusedAndCreatesNoJob()

	/**
	 * A target folder the user may not write is refused, and creates no job.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
	 */
	public function testATargetTheUserMayNotWriteIsRefused(): void {
		$service = $this->service(files: $this->threeDocuments(), writable: false);

		try {
			$service->merge(inputs: [['fileId' => 11, 'label' => 'Aanvraag']], options: []);
			$this->fail('An unwritable target must be refused.');
		} catch (MergeRefusedException $refusal) {
			$this->assertSame(403, $refusal->getStatus());
		}

		$this->assertSame([], $this->stored);

	}//end testATargetTheUserMayNotWriteIsRefused()

	/**
	 * A merge that names no documents is refused as a bad request.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
	 */
	public function testAMergeWithNoInputsIsRefused(): void {
		$service = $this->service(files: []);

		try {
			$service->merge(inputs: [], options: []);
			$this->fail('A merge must name its documents.');
		} catch (MergeRefusedException $refusal) {
			$this->assertSame(400, $refusal->getStatus());
		}

	}//end testAMergeWithNoInputsIsRefused()

	/**
	 * The result says what it is: a plain PDF where the toolchain is absent.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
	 */
	public function testTheResultSaysWhetherItIsReallyPdfA(): void {
		$without = $this->service(files: $this->threeDocuments())->merge(
			inputs: [['fileId' => 11, 'label' => 'Aanvraag']],
			options: ['name' => 'zonder']
		);
		$this->assertSame('pdf', $without['conformance']);

		$this->written = [];
		$this->stored = [];

		$with = $this->service(files: $this->threeDocuments(), archivalWorks: true)->merge(
			inputs: [['fileId' => 11, 'label' => 'Aanvraag']],
			options: ['name' => 'met']
		);
		$this->assertSame('pdfa-3b', $with['conformance']);

	}//end testTheResultSaysWhetherItIsReallyPdfA()

	/**
	 * Progress moves while the merge runs, so a queued one can be watched.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
	 */
	public function testProgressMovesWhileTheMergeRuns(): void {
		$service = $this->service(files: $this->threeDocuments());

		$service->merge(
			inputs: [
				['fileId' => 11, 'label' => 'Aanvraag'],
				['fileId' => 13, 'label' => 'Besluit'],
			],
			options: ['name' => 'voortgang']
		);

		$progress = [];
		foreach ($this->stored as $job) {
			$progress[] = (int)($job['progress'] ?? 0);
		}

		$this->assertContains(50, $progress, 'Halfway through two inputs, progress reads 50.');
		$this->assertContains(100, $progress);

	}//end testProgressMovesWhileTheMergeRuns()

	/**
	 * A big selection is queued rather than waited for; a small one is not.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
	 */
	public function testALargeSelectionIsQueued(): void {
		$service = $this->service(files: $this->threeDocuments());

		$this->assertTrue(
			$service->shouldQueue(inputs: [['fileId' => 11, 'pages' => 200]], threshold: 50)
		);
		$this->assertFalse(
			$service->shouldQueue(inputs: [['fileId' => 11, 'pages' => 10]], threshold: 50)
		);

		// A selection that says nothing about its pages is estimated from its
		// size rather than assumed to be one page.
		$this->assertTrue(
			$service->shouldQueue(inputs: [['fileId' => 11, 'size' => (60 * 51200)]], threshold: 50)
		);

	}//end testALargeSelectionIsQueued()

	/**
	 * A queued merge is stored with the checks already passed, and runs later.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
	 */
	public function testAQueuedMergeIsStoredAndRunsLater(): void {
		$service = $this->service(files: $this->threeDocuments());

		$queued = $service->queue(
			inputs: [['fileId' => 11, 'label' => 'Aanvraag'], ['fileId' => 13, 'label' => 'Besluit']],
			options: ['name' => 'later']
		);

		$this->assertSame('queued', $queued['status']);
		$this->assertSame('anna', $queued['requestedBy']);
		$this->assertSame([], $this->written, 'Queuing writes no file.');

		$finished = $service->resume(job: $queued);

		$this->assertSame('done', $finished['status']);
		$this->assertSame(5, $finished['pageCount']);
		$this->assertArrayHasKey('later.pdf', $this->written);

	}//end testAQueuedMergeIsStoredAndRunsLater()
}//end class
