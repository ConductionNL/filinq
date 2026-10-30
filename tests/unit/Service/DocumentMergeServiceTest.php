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
 * @spec openspec/specs/document-merge/spec.md
 */

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Exception\MergeRefusedException;
use OCA\Filinq\Service\DocumentMergeService;
use OCA\Filinq\Service\MergeCoverRenderer;
use OCA\Filinq\Service\MergedPdfDocument;
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
	 * The document factory the next service() uses; null for a plain one.
	 *
	 * @var PdfDocumentFactory|null
	 */
	private ?PdfDocumentFactory $factory = null;

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
	 * @param MergeCoverRenderer|null $cover The cover renderer, a bare double when null.
	 * @param bool $writeFails Whether writing the result file throws.
	 *
	 * @return DocumentMergeService The service under test.
	 */
	private function service(
		array $files,
		bool $writable = true,
		bool $conversionFails = false,
		bool $archivalWorks = false,
		?MergeCoverRenderer $cover = null,
		bool $writeFails = false,
	): DocumentMergeService {
		$folder = $this->createMock(Folder::class);
		$folder->method('isCreatable')->willReturn($writable);
		$folder->method('getNonExistingName')->willReturnArgument(0);
		$folder->method('newFile')->willReturnCallback(
			function (string $name, string $content) use ($writeFails): File {
				if ($writeFails === true) {
					throw new RuntimeException('disk full');
				}

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
			($this->factory ?? new PdfDocumentFactory()),
			($cover ?? $this->createMock(originalClassName: MergeCoverRenderer::class)),
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
	 * @spec openspec/specs/document-merge/spec.md
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
	 * @spec openspec/specs/document-merge/spec.md
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
	 * @spec openspec/specs/document-merge/spec.md
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
	 * @spec openspec/specs/document-merge/spec.md
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
	 * @spec openspec/specs/document-merge/spec.md
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
	 * @spec openspec/specs/document-merge/spec.md
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
	 * @spec openspec/specs/document-merge/spec.md
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
	 * @spec openspec/specs/document-merge/spec.md
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
	 * @spec openspec/specs/document-merge/spec.md
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
	 * @spec openspec/specs/document-merge/spec.md
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
	/**
	 * A cover page goes in front of the inputs and counts as a page.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/document-merge/spec.md
	 */
	public function testACoverPageGoesInFrontOfTheInputs(): void {
		$cover = $this->createMock(originalClassName: MergeCoverRenderer::class);
		$cover->expects($this->once())->method('render')->willReturnCallback(
			function (string $templateRef, array $data): string {
				$this->assertSame(expected: 'voorblad', actual: $templateRef);
				$this->assertSame(expected: 'anna', actual: $data['requestedBy']);
				$this->assertSame(expected: 2, actual: $data['documentCount']);

				return $this->pdf(label: 'Voorblad', pages: 1);
			}
		);
		$service = $this->service(files: $this->threeDocuments(), cover: $cover);

		$job = $service->merge(
			inputs: [['fileId' => 11, 'label' => 'Aanvraag'], ['fileId' => 13, 'label' => 'Besluit']],
			options: ['name' => 'met-voorblad', 'coverTemplateRef' => ' voorblad ']
		);

		$this->assertSame(expected: 'done', actual: $job['status']);
		$this->assertSame(expected: 6, actual: $job['pageCount'], message: 'The cover page counts.');
		$this->assertStringContainsString(needle: 'Voorblad', haystack: $this->written['met-voorblad.pdf']);

	}//end testACoverPageGoesInFrontOfTheInputs()

	/**
	 * A cover that does not render fails the merge and writes nothing.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/document-merge/spec.md
	 */
	public function testACoverThatDoesNotRenderFailsTheMerge(): void {
		$cover = $this->createMock(originalClassName: MergeCoverRenderer::class);
		$cover->method('render')->willThrowException(exception: new RuntimeException(message: 'template missing'));
		$service = $this->service(files: $this->threeDocuments(), cover: $cover);

		$job = $service->merge(
			inputs: [['fileId' => 11, 'label' => 'Aanvraag']],
			options: ['name' => 'geen-voorblad', 'coverTemplateRef' => 'voorblad']
		);

		$this->assertSame(expected: 'failed', actual: $job['status']);
		$this->assertSame(expected: 'Could not render the cover page: template missing', actual: $job['lastError']);
		$this->assertSame(expected: [], actual: $this->written);

	}//end testACoverThatDoesNotRenderFailsTheMerge()

	/**
	 * An input whose bytes are no PDF fails the merge and names the input.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/document-merge/spec.md
	 */
	public function testAnInputThatCannotBeAddedFailsTheMerge(): void {
		$files = $this->threeDocuments();
		$files[12]['content'] = 'not a pdf';
		$service = $this->service(files: $files);

		$job = $service->merge(
			inputs: [['fileId' => 11, 'label' => 'Aanvraag'], ['fileId' => 12, 'label' => 'Plattegrond']],
			options: ['name' => 'kapot']
		);

		$this->assertSame(expected: 'failed', actual: $job['status']);
		$this->assertStringStartsWith(prefix: 'Could not add Plattegrond to the merge: ', string: $job['lastError']);
		$this->assertSame(expected: 50, actual: $job['progress'], message: 'The progress of the first input is kept.');
		$this->assertSame(expected: [], actual: $this->written);

	}//end testAnInputThatCannotBeAddedFailsTheMerge()

	/**
	 * A result that cannot be written fails the merge.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/document-merge/spec.md
	 */
	public function testAResultThatCannotBeWrittenFailsTheMerge(): void {
		$service = $this->service(files: $this->threeDocuments(), writeFails: true);

		$job = $service->merge(
			inputs: [['fileId' => 11, 'label' => 'Aanvraag']],
			options: ['name' => 'vol']
		);

		$this->assertSame(expected: 'failed', actual: $job['status']);
		$this->assertSame(expected: 'Could not write the merged PDF: disk full', actual: $job['lastError']);

	}//end testAResultThatCannotBeWrittenFailsTheMerge()


	/**
	 * A factory that keeps every document it creates, so a test can read its bookmarks.
	 *
	 * @return PdfDocumentFactory The recording factory.
	 */
	private function recordingFactory(): PdfDocumentFactory {
		return new class extends PdfDocumentFactory {
			/**
			 * Every document created so far.
			 *
			 * @var array<int, MergedPdfDocument>
			 */
			public array $created = [];

			/**
			 * Create a document and keep it.
			 *
			 * @return MergedPdfDocument The document.
			 */
			public function create(): MergedPdfDocument {
				$document = parent::create();
				$this->created[] = $document;

				return $document;
			}
		};

	}//end recordingFactory()

	/**
	 * A merge with a cover stores the job at every step in exactly this shape,
	 * and every bookmark points at the page its input starts on.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/document-merge/spec.md
	 */
	public function testAMergeStoresEveryStepAndBookmarksEveryStartPage(): void {
		$cover = $this->createMock(originalClassName: MergeCoverRenderer::class);
		$cover->method('render')->willReturn($this->pdf(label: 'Voorblad', pages: 1));
		$this->factory = $this->recordingFactory();
		$service = $this->service(files: $this->threeDocuments(), cover: $cover);

		$inputs = [['fileId' => 11, 'label' => 'Aanvraag'], ['fileId' => 13, 'label' => '']];
		$options = ['name' => 'stap', 'coverTemplateRef' => 'voorblad'];
		$service->merge(inputs: $inputs, options: $options, hostObject: ['id' => 'zaak-1']);

		$summary = [['fileId' => 11, 'label' => 'Aanvraag'], ['fileId' => 13, 'label' => 'besluit.pdf']];
		$running = ['inputs' => $summary, 'options' => $options, 'requestedBy' => 'anna', 'hostObject' => ['id' => 'zaak-1'], 'progress' => 0, 'status' => 'running', 'uuid' => 'merge-1'];
		$this->assertSame(
			[
				$running,
				array_replace($running, ['progress' => 50]),
				array_replace($running, ['progress' => 100]),
				array_replace($running, ['progress' => 100, 'status' => 'done']) + ['resultFileId' => 9001, 'pageCount' => 6, 'conformance' => 'pdf'],
			],
			$this->stored
		);
		$this->assertSame(['Aanvraag' => 2, 'besluit.pdf' => 4], array_column(array_slice($this->factory->created[0]->bookmarks(), -2), 'p', 't'));

	}//end testAMergeStoresEveryStepAndBookmarksEveryStartPage()

	/**
	 * A failing input stops the job at the progress it reached, as a failed job.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/document-merge/spec.md
	 */
	public function testAFailingInputStoresTheJobAtTheProgressItReached(): void {
		$service = $this->service(files: $this->threeDocuments(), conversionFails: true);

		$job = $service->merge(
			inputs: [['fileId' => 11, 'label' => 'Aanvraag'], ['fileId' => 12, 'label' => 'Plattegrond'], ['fileId' => 13, 'label' => 'Besluit']],
			options: ['name' => 'half']
		);

		$this->assertSame(33, $job['progress']);
		$this->assertSame('failed', $job['status']);
		$this->assertSame('merge-1', $job['uuid']);
		$this->assertCount(3, $this->stored);
		$this->assertSame($job, $this->stored[2]);

	}//end testAFailingInputStoresTheJobAtTheProgressItReached()
}//end class
