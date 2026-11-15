<?php

/**
 * Unit tests for ReviewRenditionService.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Review
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
 *
 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-5.1
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Review;

use OCA\Filinq\Kernel\Contracts\OcrResultRepositoryInterface;
use OCA\Filinq\Service\OcrService;
use OCA\Filinq\Service\OpenRegisterServiceLocator;
use OCA\Filinq\Service\PdfConversionService;
use OCA\Filinq\Service\Review\ReviewRenditionRepository;
use OCA\Filinq\Service\Review\ReviewRenditionService;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Which rendition each kind of source gets, how it is stored and reused, and detection on it.
 *
 * @covers \OCA\Filinq\Service\Review\ReviewRenditionService
 * @uses   \OCA\Filinq\Service\OpenRegisterTextExtractionCall
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class ReviewRenditionServiceTest extends TestCase {

	/**
	 * @var PdfConversionService&MockObject
	 */
	private PdfConversionService&MockObject $conversion;

	/**
	 * @var OcrService&MockObject
	 */
	private OcrService&MockObject $ocr;

	/**
	 * @var OcrResultRepositoryInterface&MockObject
	 */
	private OcrResultRepositoryInterface&MockObject $ocrResults;

	/**
	 * @var ReviewRenditionRepository&MockObject
	 */
	private ReviewRenditionRepository&MockObject $repository;

	/**
	 * @var IRootFolder&MockObject
	 */
	private IRootFolder&MockObject $rootFolder;

	/**
	 * The text OpenRegister extracted, per file id.
	 *
	 * @var array<int, string>
	 */
	private array $extracted = [];

	/**
	 * The service under test.
	 *
	 * @var ReviewRenditionService
	 */
	private ReviewRenditionService $service;

	/**
	 * Build the service over doubles of its boundaries.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->conversion = $this->createMock(PdfConversionService::class);
		$this->ocr = $this->createMock(OcrService::class);
		$this->ocrResults = $this->createMock(OcrResultRepositoryInterface::class);
		$this->repository = $this->createMock(ReviewRenditionRepository::class);
		$this->rootFolder = $this->createMock(IRootFolder::class);

		$extractor = new class ($this) {
			/**
			 * Constructor.
			 *
			 * @param ReviewRenditionServiceTest $test The test holding the texts.
			 */
			public function __construct(private ReviewRenditionServiceTest $test) {
			}

			/**
			 * The text extracted for a file.
			 *
			 * @param int $fileId The file.
			 *
			 * @return string The text.
			 */
			public function getExtractedText(int $fileId): string {
				return $this->test->extractedFor(fileId: $fileId);
			}
		};
		$locator = $this->createMock(OpenRegisterServiceLocator::class);
		$locator->method('get')->with('OCA\OpenRegister\Files\Service\TextExtractionService')->willReturn($extractor);

		$this->service = new ReviewRenditionService(
			conversion: $this->conversion,
			ocr: $this->ocr,
			ocrResults: $this->ocrResults,
			locator: $locator,
			repository: $this->repository,
			rootFolder: $this->rootFolder,
			logger: $this->createMock(LoggerInterface::class)
		);
	}//end setUp()

	/**
	 * The text extracted for a file, for the extractor double.
	 *
	 * @param int $fileId The file.
	 *
	 * @return string The text.
	 */
	public function extractedFor(int $fileId): string {
		return ($this->extracted[$fileId] ?? '');
	}//end extractedFor()

	/**
	 * A file double.
	 *
	 * @param int         $id      The file id.
	 * @param string      $mime    The MIME type.
	 * @param string      $content The bytes.
	 * @param string      $name    The name.
	 * @param Folder|null $parent  The parent folder.
	 * @param int         $mtime   The modification time.
	 *
	 * @return File&MockObject The file.
	 */
	private function file(int $id, string $mime, string $content, string $name = 'x', ?Folder $parent = null, int $mtime = 100): File&MockObject {
		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn($id);
		$file->method('getMimeType')->willReturn($mime);
		$file->method('getContent')->willReturn($content);
		$file->method('getName')->willReturn($name);
		$file->method('getMTime')->willReturn($mtime);
		if ($parent !== null) {
			$file->method('getParent')->willReturn($parent);
		}

		return $file;
	}//end file()

	/**
	 * A Word file is converted, and the rendition names the PDF and the backend that made it.
	 *
	 * @return void
	 */
	public function testAWordFileIsConverted(): void {
		$docx = $this->file(id: 10, mime: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', content: 'docx');
		$pdf = $this->file(id: 11, mime: 'application/pdf', content: '%PDF converted');
		$this->conversion->expects($this->once())->method('convertToPdfReporting')->with($docx)
			->willReturn(['file' => $pdf, 'backend' => 'libreoffice']);

		$rendition = $this->service->prepare(source: $docx);

		$this->assertSame(['fileId', 'sha256', 'kind', 'backend', 'textLayer', 'preparedAt'], array_keys($rendition));
		$this->assertSame(11, $rendition['fileId']);
		$this->assertSame(hash('sha256', '%PDF converted'), $rendition['sha256']);
		$this->assertSame('converted', $rendition['kind']);
		$this->assertSame('libreoffice', $rendition['backend']);
		$this->assertSame('native', $rendition['textLayer']);
		$this->assertNotFalse(\DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, $rendition['preparedAt']));
	}//end testAWordFileIsConverted()

	/**
	 * A scan without text gets its text layer from OCR.
	 *
	 * @return void
	 */
	public function testAScanGetsAnOcrTextLayer(): void {
		$scan = $this->file(id: 20, mime: 'application/pdf', content: '%PDF scan');
		$this->ocrResults->method('findForFile')->with(20)->willReturn(null);
		$this->ocr->method('isOcrEnabled')->willReturn(true);
		$this->ocr->method('isTesseractAvailable')->willReturn(true);
		$this->ocr->expects($this->once())->method('processNode')->with($scan)
			->willReturn(['text' => 'Jan de Vries', 'confidence' => 88.0, 'languages' => 'nld', 'dpi' => 300]);
		$this->conversion->expects($this->never())->method('convertToPdfReporting');

		$rendition = $this->service->prepare(source: $scan);

		$this->assertSame(20, $rendition['fileId']);
		$this->assertSame(hash('sha256', '%PDF scan'), $rendition['sha256']);
		$this->assertSame('ocr-text-layer', $rendition['kind']);
		$this->assertSame('tesseract', $rendition['backend']);
		$this->assertSame('ocr', $rendition['textLayer']);
	}//end testAScanGetsAnOcrTextLayer()

	/**
	 * A scan whose OCR text was already handed to OpenRegister is not read as a native PDF, nor OCR'd again.
	 *
	 * @return void
	 */
	public function testAStoredOcrResultIsUsedAsTheTextLayer(): void {
		$scan = $this->file(id: 21, mime: 'image/png', content: 'png');
		$this->extracted[21] = 'Jan de Vries';
		$this->ocrResults->method('findForFile')->with(21)->willReturn(['fileId' => 21, 'textLength' => 12]);
		$this->ocr->expects($this->never())->method('processNode');

		$rendition = $this->service->prepare(source: $scan);

		$this->assertSame('ocr-text-layer', $rendition['kind']);
		$this->assertSame('ocr', $rendition['textLayer']);

		$pdfScan = $this->file(id: 21, mime: 'application/pdf', content: '%PDF');
		$this->assertSame('ocr', $this->service->prepare(source: $pdfScan)['textLayer']);
	}//end testAStoredOcrResultIsUsedAsTheTextLayer()

	/**
	 * Without OCR, or when OCR reads nothing or fails, the scan has no text layer.
	 *
	 * @return void
	 */
	public function testNoOcrMeansNoTextLayer(): void {
		$scan = $this->file(id: 30, mime: 'image/jpeg', content: 'jpg');
		$this->ocrResults->method('findForFile')->willReturn(null);
		$this->ocr->method('isOcrEnabled')->willReturn(true);
		$this->ocr->method('isTesseractAvailable')->willReturnOnConsecutiveCalls(false, true, true);
		$this->ocr->method('processNode')->willReturnOnConsecutiveCalls(
			['text' => '  ', 'confidence' => 0.0, 'languages' => 'nld', 'dpi' => 300],
			$this->throwException(new RuntimeException('tesseract crashed'))
		);

		foreach (['not installed', 'read nothing', 'failed'] as $case) {
			$rendition = $this->service->prepare(source: $scan);
			$this->assertSame('ocr-text-layer', $rendition['kind'], $case);
			$this->assertSame('', $rendition['backend'], $case);
			$this->assertSame('none', $rendition['textLayer'], $case);
			$this->assertSame(30, $rendition['fileId'], $case);
		}
	}//end testNoOcrMeansNoTextLayer()

	/**
	 * A PDF with its own text is used as it is: no conversion, no OCR.
	 *
	 * @return void
	 */
	public function testANativePdfIsUsedAsItIs(): void {
		$pdf = $this->file(id: 40, mime: 'application/pdf', content: '%PDF text');
		$this->extracted[40] = 'Besluit op het Woo-verzoek';
		$this->ocrResults->method('findForFile')->willReturn(null);
		$this->conversion->expects($this->never())->method('convertToPdfReporting');
		$this->ocr->expects($this->never())->method('processNode');

		$rendition = $this->service->prepare(source: $pdf);

		$this->assertSame(40, $rendition['fileId']);
		$this->assertSame(hash('sha256', '%PDF text'), $rendition['sha256']);
		$this->assertSame('native-pdf', $rendition['kind']);
		$this->assertSame('', $rendition['backend']);
		$this->assertSame('native', $rendition['textLayer']);
	}//end testANativePdfIsUsedAsItIs()

	/**
	 * Record stores the prepared rendition for its source and answers the stored row.
	 *
	 * @return void
	 */
	public function testRecordStoresTheRendition(): void {
		$pdf = $this->file(id: 40, mime: 'application/pdf', content: '%PDF text');
		$this->extracted[40] = 'text';
		$this->repository->method('findFor')->with(40)->willReturn(null);
		$this->repository->expects($this->once())->method('saveFor')
			->with(40, $this->callback(static fn (array $r): bool => $r['kind'] === 'native-pdf' && $r['fileId'] === 40))
			->willReturn(['uuid' => 'r-1', 'sourceFileId' => 40, 'kind' => 'native-pdf']);
		$this->rootFolder->method('getFirstNodeById')->with(40)->willReturn($pdf);

		$this->assertSame(['uuid' => 'r-1', 'sourceFileId' => 40, 'kind' => 'native-pdf'], $this->service->recordFor(fileId: 40));
	}//end testRecordStoresTheRendition()

	/**
	 * A converted rendition that still exists, is newer than its source and unchanged is reused.
	 *
	 * @return void
	 */
	public function testACurrentConvertedRenditionIsReused(): void {
		$docx = $this->file(id: 10, mime: 'application/msword', content: 'doc', name: 'besluit.doc', mtime: 100);
		$pdf = $this->file(id: 11, mime: 'application/pdf', content: '%PDF', mtime: 200);
		$previous = ['uuid' => 'r-1', 'sourceFileId' => 10, 'fileId' => 11, 'sha256' => hash('sha256', '%PDF'), 'kind' => 'converted'];
		$this->repository->method('findFor')->willReturn($previous);
		$this->rootFolder->method('getFirstNodeById')->with(11)->willReturn($pdf);
		$this->conversion->expects($this->never())->method('convertToPdfReporting');
		$this->repository->expects($this->never())->method('saveFor');

		$this->assertSame($previous, $this->service->record(source: $docx));
	}//end testACurrentConvertedRenditionIsReused()

	/**
	 * A source changed after its rendition is converted again, replacing the old rendition file.
	 *
	 * @return void
	 */
	public function testAChangedSourceIsConvertedAgain(): void {
		$parent = $this->createMock(Folder::class);
		$docx = $this->file(id: 10, mime: 'application/msword', content: 'doc', name: 'besluit.doc', parent: $parent, mtime: 300);
		$old = $this->file(id: 11, mime: 'application/pdf', content: '%PDF', mtime: 200);
		$new = $this->file(id: 12, mime: 'application/pdf', content: '%PDF new', mtime: 301);
		$this->repository->method('findFor')->willReturn(['sourceFileId' => 10, 'fileId' => 11, 'sha256' => hash('sha256', '%PDF'), 'kind' => 'converted']);
		$this->rootFolder->method('getFirstNodeById')->with(11)->willReturn($old);
		$parent->method('nodeExists')->with('besluit.pdf')->willReturn(true);
		$parent->method('get')->with('besluit.pdf')->willReturn($old);
		$this->conversion->expects($this->once())->method('convertToPdfReporting')->willReturn(['file' => $new, 'backend' => 'phpword']);
		$this->repository->expects($this->once())->method('saveFor')
			->with(10, $this->callback(static fn (array $r): bool => $r['fileId'] === 12 && $r['backend'] === 'phpword'))
			->willReturnArgument(1);

		$this->assertSame(12, $this->service->record(source: $docx)['fileId']);
	}//end testAChangedSourceIsConvertedAgain()

	/**
	 * A PDF of the same name next to the source that is not the rendition is never overwritten.
	 *
	 * @return void
	 */
	public function testAnotherPdfOfTheSameNameIsNotOverwritten(): void {
		$parent = $this->createMock(Folder::class);
		$docx = $this->file(id: 10, mime: 'application/msword', content: 'doc', name: 'besluit.doc', parent: $parent);
		$theirs = $this->file(id: 77, mime: 'application/pdf', content: '%PDF theirs');
		$this->repository->method('findFor')->willReturn(null);
		$parent->method('nodeExists')->with('besluit.pdf')->willReturn(true);
		$parent->method('get')->with('besluit.pdf')->willReturn($theirs);
		$this->conversion->expects($this->never())->method('convertToPdfReporting');
		$this->repository->expects($this->never())->method('saveFor');

		$this->assertNull($this->service->record(source: $docx));
	}//end testAnotherPdfOfTheSameNameIsNotOverwritten()

	/**
	 * A failed conversion leaves no rendition, and an unknown file id is answered null.
	 *
	 * @return void
	 */
	public function testAFailureLeavesNoRendition(): void {
		$parent = $this->createMock(Folder::class);
		$parent->method('nodeExists')->willReturn(false);
		$docx = $this->file(id: 10, mime: 'text/html', content: '<p>', name: 'brief.html', parent: $parent);
		$this->repository->method('findFor')->willReturn(null);
		$this->conversion->method('convertToPdfReporting')->willThrowException(new RuntimeException('no backend'));
		$this->repository->expects($this->never())->method('saveFor');
		$this->rootFolder->method('getFirstNodeById')->willReturn(null);

		$this->assertNull($this->service->record(source: $docx));
		$this->assertNull($this->service->recordFor(fileId: 5));
	}//end testAFailureLeavesNoRendition()

	/**
	 * Detection on a converted rendition hands its text to OpenRegister as the source's.
	 *
	 * @return void
	 */
	public function testDetectionRunsOnAConvertedRenditionsText(): void {
		$extractor = new class () {
			/**
			 * The calls made, in order.
			 *
			 * @var list<string>
			 */
			public array $calls = [];

			/**
			 * Extract a file.
			 *
			 * @param int        $fileId      The file.
			 * @param bool       $force       Re-extract.
			 * @param array|null $entityTypes The types.
			 *
			 * @return void
			 */
			public function extractFile(int $fileId, bool $force, ?array $entityTypes): void {
				$this->calls[] = 'extract ' . $fileId . ' ' . json_encode($entityTypes);
			}

			/**
			 * The text of a file.
			 *
			 * @param int $fileId The file.
			 *
			 * @return string The text.
			 */
			public function getExtractedText(int $fileId): string {
				return ($fileId === 11 ? 'Jan de Vries' : '');
			}

			/**
			 * Take text for a file.
			 *
			 * @param int    $fileId The file.
			 * @param string $text   The text.
			 *
			 * @return void
			 */
			public function extractFromProvidedText(int $fileId, string $text): void {
				$this->calls[] = 'provide ' . $fileId . ' ' . $text;
			}
		};

		$ran = $this->service->detectOnRendition(
			sourceFileId: 10,
			rendition: ['fileId' => 11, 'kind' => 'converted'],
			textExtractor: $extractor,
			entityTypes: ['PERSON']
		);

		$this->assertTrue($ran);
		$this->assertSame(['extract 11 ["PERSON"]', 'provide 10 Jan de Vries'], $extractor->calls);

		$this->assertFalse($this->service->detectOnRendition(sourceFileId: 10, rendition: ['fileId' => 10, 'kind' => 'native-pdf'], textExtractor: $extractor, entityTypes: null));
		$this->assertFalse($this->service->detectOnRendition(sourceFileId: 10, rendition: null, textExtractor: $extractor, entityTypes: null));
		$this->assertFalse($this->service->detectOnRendition(sourceFileId: 10, rendition: ['fileId' => 12, 'kind' => 'converted'], textExtractor: $extractor, entityTypes: null));
		$this->assertFalse($this->service->detectOnRendition(sourceFileId: 10, rendition: ['fileId' => 11, 'kind' => 'converted'], textExtractor: new \stdClass(), entityTypes: null));
		$this->assertCount(3, $extractor->calls);
	}//end testDetectionRunsOnAConvertedRenditionsText()
}//end class
