<?php

/**
 * After OCR, a PDF gets its text layer as a new version and an image a searchable PDF beside it.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Ocr
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/finish-half-built-capabilities/tasks.md#task-1.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Ocr;

use OCA\Filinq\Service\Editing\DocumentGuard;
use OCA\Filinq\Service\Ocr\OcrRunService;
use OCA\Filinq\Service\Ocr\OcrTextLayerService;
use OCA\Filinq\Service\Ocr\SearchablePdfWriter;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\NotPermittedException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;

/**
 * OcrTextLayerService, and OcrRunService handing it the run's settings.
 */
class OcrTextLayerServiceTest extends TestCase {

	use OcrDoubles;

	/**
	 * A file node.
	 *
	 * @param string $mimeType  Its MIME type.
	 * @param string $name      Its name.
	 * @param bool   $writable  Whether it may be changed, and its folder take a new file.
	 * @param Folder|null $folder Its folder.
	 *
	 * @return File&MockObject
	 */
	private function file(string $mimeType, string $name, bool $writable=true, ?Folder $folder=null): File {
		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn(812004);
		$file->method('getMimeType')->willReturn($mimeType);
		$file->method('getName')->willReturn($name);
		$file->method('getContent')->willReturn('%PDF-1.4 scanned');
		$file->method('isUpdateable')->willReturn($writable);
		$folder ??= $this->createMock(Folder::class);
		$folder->method('isCreatable')->willReturn($writable);
		$file->method('getParent')->willReturn($folder);

		return $file;

	}//end file()

	/**
	 * A writer that answers the given result for a PDF and for an image.
	 *
	 * @param array{bytes: string, words: int}|RuntimeException $result What it builds, or throws.
	 * @param array|null $copied Filled with the local copy's content and the arguments of each call.
	 *
	 * @return SearchablePdfWriter&MockObject
	 */
	private function writer(array|RuntimeException $result, ?array &$copied=null): SearchablePdfWriter {
		$copied = [];
		$writer = $this->createMock(SearchablePdfWriter::class);
		$answer = static function (string $path, ...$rest) use ($result, &$copied): array {
			$copied[] = array_merge([(string) file_get_contents($path)], $rest);
			if ($result instanceof RuntimeException) {
				throw $result;
			}

			return $result;
		};
		$writer->method('fromPdf')->willReturnCallback(fn (string $pdfPath, string $languages, int $dpi): array => $answer($pdfPath, $languages, $dpi));
		$writer->method('fromImage')->willReturnCallback(
			fn (string $imagePath, string $mimeType, string $languages, int $dpi): array => $answer($imagePath, $mimeType, $languages, $dpi)
		);

		return $writer;

	}//end writer()

	/**
	 * A guard that answers the given refusal from one of its checks, or none.
	 *
	 * @param string|null $check   finalRefusal, signatureRefusal or anonymisationRefusal; null refuses nothing.
	 * @param bool        $asked   Whether the guard may be asked at all.
	 *
	 * @return DocumentGuard&MockObject
	 */
	private function guard(?string $check=null, bool $asked=true): DocumentGuard {
		$guard = $this->createMock(DocumentGuard::class);
		foreach (['finalRefusal', 'signatureRefusal', 'anonymisationRefusal'] as $method) {
			if ($asked === false) {
				$guard->expects($this->never())->method($method);
				continue;
			}

			$guard->method($method)->willReturn($method === $check ? 'This document is protected.' : null);
		}

		return $guard;

	}//end guard()

	/**
	 * A scanned PDF gets the searchable PDF as its new content, which keeps the old one as a version.
	 *
	 * @return void
	 */
	public function testAScannedPdfIsWrittenAsANewVersion(): void {
		$file = $this->file(mimeType: 'application/pdf', name: 'bezwaar.pdf');
		$file->expects($this->once())->method('putContent')->with('%PDF-1.3 searchable');

		$service = new OcrTextLayerService(writer: $this->writer(result: ['bytes' => '%PDF-1.3 searchable', 'words' => 3], copied: $copied), guard: $this->guard(), logger: new NullLogger());

		$this->assertSame(OcrTextLayerService::WRITTEN, $service->apply(file: $file, languages: 'nld+eng', dpi: 300));
		$this->assertSame([['%PDF-1.4 scanned', 'nld+eng', 300]], $copied);

	}//end testAScannedPdfIsWrittenAsANewVersion()

	/**
	 * A photographed page gets a searchable PDF beside it under a name not yet taken.
	 *
	 * @return void
	 */
	public function testAPhotographedPageGetsACopyBesideIt(): void {
		$folder = $this->createMock(Folder::class);
		$folder->expects($this->once())->method('getNonExistingName')->with('brief.pdf')->willReturn('brief (2).pdf');
		$folder->expects($this->once())->method('newFile')->with('brief (2).pdf', '%PDF-1.3 photo');
		$file = $this->file(mimeType: 'image/jpeg', name: 'brief.jpg', folder: $folder);
		$file->expects($this->never())->method('putContent');

		$service = new OcrTextLayerService(writer: $this->writer(result: ['bytes' => '%PDF-1.3 photo', 'words' => 1], copied: $copied), guard: $this->guard(), logger: new NullLogger());

		$this->assertSame(OcrTextLayerService::COPY_CREATED, $service->apply(file: $file, languages: 'nld', dpi: 200));
		$this->assertSame('image/jpeg', $copied[0][1]);
		$this->assertSame(200, $copied[0][3]);

	}//end testAPhotographedPageGetsACopyBesideIt()

	/**
	 * A final, signed or anonymised PDF keeps its bytes: rewriting it would break its frozen checksum or signature.
	 *
	 * @return void
	 */
	public function testAProtectedPdfIsNotRewritten(): void {
		foreach (['finalRefusal', 'signatureRefusal', 'anonymisationRefusal'] as $check) {
			$file = $this->file(mimeType: 'application/pdf', name: 'besluit.pdf');
			$file->expects($this->never())->method('putContent');
			$service = new OcrTextLayerService(writer: $this->writer(result: ['bytes' => '%PDF-1.3 searchable', 'words' => 3], copied: $copied), guard: $this->guard(check: $check), logger: new NullLogger());

			$this->assertSame(OcrTextLayerService::SKIP_PROTECTED, $service->apply(file: $file, languages: 'nld', dpi: 300), $check);
			$this->assertSame([], $copied, $check . ': nothing is built for a PDF that is not rewritten');
		}

	}//end testAProtectedPdfIsNotRewritten()

	/**
	 * An image is never rewritten, only given a new file beside it, so the guard is not asked.
	 *
	 * @return void
	 */
	public function testAnImageIsNotGuarded(): void {
		$folder = $this->createMock(Folder::class);
		$folder->method('getNonExistingName')->willReturn('scan.pdf');
		$folder->expects($this->once())->method('newFile')->with('scan.pdf', '%PDF-1.3 photo');
		$file = $this->file(mimeType: 'image/png', name: 'scan.png', folder: $folder);

		$service = new OcrTextLayerService(writer: $this->writer(result: ['bytes' => '%PDF-1.3 photo', 'words' => 1]), guard: $this->guard(asked: false), logger: new NullLogger());

		$this->assertSame(OcrTextLayerService::COPY_CREATED, $service->apply(file: $file, languages: 'nld', dpi: 300));

	}//end testAnImageIsNotGuarded()

	/**
	 * Nothing is written to a file the person may not change, of a type no page is built from, or without words.
	 *
	 * @return void
	 */
	public function testNothingIsWrittenWhenItCannotOrNeedNot(): void {
		$service = new OcrTextLayerService(writer: $this->writer(result: ['bytes' => '%PDF', 'words' => 0], copied: $copied), guard: $this->guard(), logger: new NullLogger());

		$readOnly = $this->file(mimeType: 'application/pdf', name: 'a.pdf', writable: false);
		$readOnly->expects($this->never())->method('putContent');
		$this->assertSame(OcrTextLayerService::SKIP_NOT_WRITABLE, $service->apply(file: $readOnly, languages: 'nld', dpi: 300));

		$tiff = $this->file(mimeType: 'image/tiff', name: 'a.tif');
		$this->assertSame(OcrTextLayerService::SKIP_UNSUPPORTED_TYPE, $service->apply(file: $tiff, languages: 'nld', dpi: 300));
		$this->assertSame([], $copied);

		$blank = $this->file(mimeType: 'application/pdf', name: 'b.pdf');
		$blank->expects($this->never())->method('putContent');
		$this->assertSame(OcrTextLayerService::SKIP_NO_WORDS, $service->apply(file: $blank, languages: 'nld', dpi: 300));

	}//end testNothingIsWrittenWhenItCannotOrNeedNot()

	/**
	 * A writer or storage failure is reported as failed, not thrown, and the local copy is removed.
	 *
	 * @return void
	 */
	public function testAFailureIsReportedNotThrown(): void {
		$before = glob(sys_get_temp_dir() . '/filinq_textlayer_*');

		$broken = new OcrTextLayerService(writer: $this->writer(result: new RuntimeException('xref stream')), guard: $this->guard(), logger: new NullLogger());
		$this->assertSame(OcrTextLayerService::SKIP_FAILED, $broken->apply(file: $this->file(mimeType: 'application/pdf', name: 'a.pdf'), languages: 'nld', dpi: 300));

		$locked = $this->file(mimeType: 'application/pdf', name: 'a.pdf');
		$locked->method('putContent')->willThrowException(new NotPermittedException('locked'));
		$service = new OcrTextLayerService(writer: $this->writer(result: ['bytes' => '%PDF', 'words' => 2]), guard: $this->guard(), logger: new NullLogger());
		$this->assertSame(OcrTextLayerService::SKIP_FAILED, $service->apply(file: $locked, languages: 'nld', dpi: 300));

		$this->assertSame($before, glob(sys_get_temp_dir() . '/filinq_textlayer_*'));

	}//end testAFailureIsReportedNotThrown()

	/**
	 * An OCR run hands the file and its settings to the text layer, and reports the outcome; a skipped run does not.
	 *
	 * @return void
	 */
	public function testAnOcrRunWritesTheTextLayerWithItsSettings(): void {
		$time = $this->createMock(ITimeFactory::class);
		$time->method('getDateTime')->willReturn(new \DateTime('2026-11-14T10:00:00+00:00'));
		$file = $this->ocrFile();

		$layer = $this->createMock(OcrTextLayerService::class);
		$layer->expects($this->once())->method('apply')->with($file, 'nld+eng', 300)->willReturn(OcrTextLayerService::WRITTEN);
		$run = new OcrRunService($this->ocrService(), $this->ocrResultRepository(), $time, new NullLogger(), $layer);

		$outcome = $run->run(file: $file, trigger: 'manual');
		$this->assertTrue($outcome['processed']);
		$this->assertSame(OcrTextLayerService::WRITTEN, $outcome['textLayer']);

		$untouched = $this->createMock(OcrTextLayerService::class);
		$untouched->expects($this->never())->method('apply');
		$silent = new OcrRunService($this->ocrService(text: '   '), $this->ocrResultRepository(), $time, new NullLogger(), $untouched);
		$this->assertFalse($silent->run(file: $file, trigger: 'manual')['processed']);

		$this->assertNull((new OcrRunService($this->ocrService(), $this->ocrResultRepository(), $time, new NullLogger()))->run(file: $file, trigger: 'manual')['textLayer']);

	}//end testAnOcrRunWritesTheTextLayerWithItsSettings()
}//end class
