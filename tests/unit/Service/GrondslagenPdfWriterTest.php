<?php

/**
 * Unit tests for GrondslagenPdfWriter
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use Exception;
use FPDF;
use OCA\Filinq\Exception\DocumentFinalException;
use OCA\Filinq\Service\Conversion\OutputLayoutResolver;
use OCA\Filinq\Service\FinalDocumentService;
use OCA\Filinq\Service\GrondslagenPdfWriter;
use OCA\Filinq\Service\PdfService;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;
use setasign\Fpdi\Fpdi;

/**
 * Rendering through PdfService with the bundled templates, merging a summary
 * into an anonymised PDF with FPDI, and the final-document guard on every
 * write path.
 *
 * @covers \OCA\Filinq\Service\GrondslagenPdfWriter
 * @uses   \OCA\Filinq\Service\Conversion\OutputLayoutResolver
 * @uses   \OCA\Filinq\Exception\DocumentFinalException
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class GrondslagenPdfWriterTest extends TestCase {

	/**
	 * PDF renderer.
	 *
	 * @var PdfService&MockObject
	 */
	private PdfService&MockObject $pdfService;

	/**
	 * Final-document guard.
	 *
	 * @var FinalDocumentService&MockObject
	 */
	private FinalDocumentService&MockObject $finalDocuments;

	/**
	 * Build the mocks.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->pdfService = $this->createMock(PdfService::class);
		$this->finalDocuments = $this->createMock(FinalDocumentService::class);

	}//end setUp()

	/**
	 * Build the writer over the real layout resolver (subfolder `anonymised`).
	 *
	 * @return GrondslagenPdfWriter
	 */
	private function writer(): GrondslagenPdfWriter {
		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturn('anonymised');

		return new GrondslagenPdfWriter(
			$this->pdfService,
			$this->finalDocuments,
			new OutputLayoutResolver($config, $this->createMock(LoggerInterface::class))
		);

	}//end writer()

	/**
	 * A real PDF with the given number of pages.
	 *
	 * @param int $pages Page count.
	 *
	 * @return string The PDF bytes.
	 */
	private function pdfWithPages(int $pages): string {
		$pdf = new FPDF();
		$pdf->SetFont('Arial', '', 12);
		for ($i = 1; $i <= $pages; $i++) {
			$pdf->AddPage();
			$pdf->Cell(40, 10, 'Page ' . $i);
		}

		return (string)$pdf->Output('S');

	}//end pdfWithPages()

	/**
	 * Count the pages of PDF bytes.
	 *
	 * @param string $bytes The PDF bytes.
	 *
	 * @return int The page count.
	 */
	private function pageCount(string $bytes): int {
		$path = tempnam(sys_get_temp_dir(), 'grondslagen-test-');
		file_put_contents($path, $bytes);
		try {
			return (new Fpdi())->setSourceFile($path);
		} finally {
			unlink($path);
		}

	}//end pageCount()

	/**
	 * The template source shipped under lib/Resources.
	 *
	 * @param string $name The template file name.
	 *
	 * @return string
	 */
	private function template(string $name): string {
		return (string)file_get_contents(__DIR__ . '/../../../lib/Resources/templates/grondslagen/' . $name);

	}//end template()

	/**
	 * The per-document summary renders the per-doc template as PDF/A.
	 *
	 * @return void
	 */
	public function testRenderPerDocumentPdfUsesThePerDocTemplate(): void {
		$this->pdfService->expects($this->once())
			->method('renderPdf')
			->with(
				$this->template('summary_per_doc.twig'),
				['entities' => 3],
				['pdfa' => true, 'title' => 'Anonimisatie-samenvatting']
			)
			->willReturn('%PDF-doc');

		$this->assertSame('%PDF-doc', $this->writer()->renderPerDocumentPdf(['entities' => 3], 42));

	}//end testRenderPerDocumentPdfUsesThePerDocTemplate()

	/**
	 * A per-document render failure is wrapped with the file id.
	 *
	 * @return void
	 */
	public function testRenderPerDocumentPdfWrapsFailure(): void {
		$cause = new Exception('twig syntax');
		$this->pdfService->method('renderPdf')->willThrowException($cause);

		try {
			$this->writer()->renderPerDocumentPdf([], 42);
			$this->fail('Expected a RuntimeException.');
		} catch (RuntimeException $e) {
			$this->assertSame('Grondslagen summary: per-doc render failed for fileId 42: twig syntax', $e->getMessage());
			$this->assertSame($cause, $e->getPrevious());
		}

	}//end testRenderPerDocumentPdfWrapsFailure()

	/**
	 * The per-dossier summary renders the per-dossier template as PDF/A.
	 *
	 * @return void
	 */
	public function testRenderDossierPdfUsesThePerDossierTemplate(): void {
		$this->pdfService->expects($this->once())
			->method('renderPdf')
			->with(
				$this->template('summary_per_dossier.twig'),
				['documents' => []],
				['pdfa' => true, 'title' => 'Grondslagen-rapportage']
			)
			->willReturn('%PDF-dossier');

		$this->assertSame('%PDF-dossier', $this->writer()->renderDossierPdf(['documents' => []], 'd-1'));

	}//end testRenderDossierPdfUsesThePerDossierTemplate()

	/**
	 * A per-dossier render failure is wrapped with the dossier uuid.
	 *
	 * @return void
	 */
	public function testRenderDossierPdfWrapsFailure(): void {
		$this->pdfService->method('renderPdf')->willThrowException(new Exception('mpdf'));

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Grondslagen summary: per-dossier render failed for d-7: mpdf');

		$this->writer()->renderDossierPdf([], 'd-7');

	}//end testRenderDossierPdfWrapsFailure()

	/**
	 * Appending merges every page of both PDFs and writes the result in place,
	 * after the final-document guard.
	 *
	 * @return void
	 */
	public function testAppendToPdfMergesAllPagesAndWritesInPlace(): void {
		$written = null;
		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn(17);
		$file->method('getContent')->willReturn($this->pdfWithPages(2));
		$file->expects($this->once())->method('putContent')->willReturnCallback(
			function (string $bytes) use (&$written): void {
				$written = $bytes;
			}
		);

		$this->finalDocuments->expects($this->once())
			->method('assertWritable')
			->with(17, 'append the grondslagen summary to this document');

		$this->writer()->appendToPdf($file, $this->pdfWithPages(1));

		$this->assertIsString($written);
		$this->assertStringStartsWith('%PDF', $written);
		$this->assertSame(3, $this->pageCount($written));

	}//end testAppendToPdfMergesAllPagesAndWritesInPlace()

	/**
	 * A final document refuses the append before it is read or written.
	 *
	 * @return void
	 */
	public function testAppendToPdfRefusesAFinalDocument(): void {
		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn(17);
		$file->expects($this->never())->method('getContent');
		$file->expects($this->never())->method('putContent');

		$this->finalDocuments->method('assertWritable')
			->willThrowException(new DocumentFinalException('Version 3 is final.'));

		$this->expectException(DocumentFinalException::class);
		$this->expectExceptionMessage('Version 3 is final.');

		$this->writer()->appendToPdf($file, '%PDF');

	}//end testAppendToPdfRefusesAFinalDocument()

	/**
	 * Unparseable PDF input fails the merge and leaves the file untouched.
	 *
	 * @return void
	 */
	public function testAppendToPdfMergeFailureLeavesFileUntouched(): void {
		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn(17);
		$file->method('getContent')->willReturn('this is not a pdf');
		$file->expects($this->never())->method('putContent');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Grondslagen summary: FPDI merge failed: ');

		$this->writer()->appendToPdf($file, $this->pdfWithPages(1));

	}//end testAppendToPdfMergeFailureLeavesFileUntouched()

	/**
	 * A failing write is wrapped with the file path.
	 *
	 * @return void
	 */
	public function testAppendToPdfWrapsWriteFailure(): void {
		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn(17);
		$file->method('getPath')->willReturn('/alice/files/doc_anonymized.pdf');
		$file->method('getContent')->willReturn($this->pdfWithPages(1));
		$file->method('putContent')->willThrowException(new Exception('quota exceeded'));

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage(
			'Grondslagen summary: failed to write combined PDF to /alice/files/doc_anonymized.pdf: quota exceeded'
		);

		$this->writer()->appendToPdf($file, $this->pdfWithPages(1));

	}//end testAppendToPdfWrapsWriteFailure()

	/**
	 * An existing summary beside the file is refreshed after the guard.
	 *
	 * @return void
	 */
	public function testWriteBesideFileRefreshesExistingSummary(): void {
		$existing = $this->createMock(File::class);
		$existing->method('getId')->willReturn(55);
		$existing->expects($this->once())->method('putContent')->with('%PDF-new');

		$parent = $this->createMock(Folder::class);
		$parent->method('nodeExists')->with('doc_summary.pdf')->willReturn(true);
		$parent->method('get')->with('doc_summary.pdf')->willReturn($existing);
		$parent->expects($this->never())->method('newFile');

		$file = $this->createMock(File::class);
		$file->method('getParent')->willReturn($parent);

		$this->finalDocuments->expects($this->once())
			->method('assertWritable')
			->with(55, 'refresh this grondslagen summary');

		$this->assertSame(
			['file' => $existing, 'refreshed' => true],
			$this->writer()->writeBesideFile($file, 'doc_summary.pdf', '%PDF-new')
		);

	}//end testWriteBesideFileRefreshesExistingSummary()

	/**
	 * Without an existing summary file (absent, or the name is a folder) a new
	 * file is created.
	 *
	 * @return void
	 */
	public function testWriteBesideFileCreatesNewSummary(): void {
		$created = $this->createMock(File::class);

		$parent = $this->createMock(Folder::class);
		$parent->method('nodeExists')->willReturnMap([['absent.pdf', false], ['folder.pdf', true]]);
		$parent->method('get')->with('folder.pdf')->willReturn($this->createMock(Folder::class));
		$parent->expects($this->exactly(2))->method('newFile')->willReturn($created);

		$file = $this->createMock(File::class);
		$file->method('getParent')->willReturn($parent);

		$this->finalDocuments->expects($this->never())->method('assertWritable');

		$writer = $this->writer();
		$this->assertSame(
			['file' => $created, 'refreshed' => false],
			$writer->writeBesideFile($file, 'absent.pdf', '%PDF')
		);
		$this->assertSame(
			['file' => $created, 'refreshed' => false],
			$writer->writeBesideFile($file, 'folder.pdf', '%PDF')
		);

	}//end testWriteBesideFileCreatesNewSummary()

	/**
	 * A final summary keeps its own exception type and message.
	 *
	 * @return void
	 */
	public function testWriteBesideFileKeepsFinalRefusal(): void {
		$existing = $this->createMock(File::class);
		$existing->method('getId')->willReturn(55);
		$existing->expects($this->never())->method('putContent');

		$parent = $this->createMock(Folder::class);
		$parent->method('nodeExists')->willReturn(true);
		$parent->method('get')->willReturn($existing);

		$file = $this->createMock(File::class);
		$file->method('getParent')->willReturn($parent);

		$this->finalDocuments->method('assertWritable')
			->willThrowException(new DocumentFinalException('Summary is final.'));

		$this->expectException(DocumentFinalException::class);
		$this->expectExceptionMessage('Summary is final.');

		$this->writer()->writeBesideFile($file, 'doc_summary.pdf', '%PDF');

	}//end testWriteBesideFileKeepsFinalRefusal()

	/**
	 * Any other write failure is wrapped with the summary name.
	 *
	 * @return void
	 */
	public function testWriteBesideFileWrapsWriteFailure(): void {
		$parent = $this->createMock(Folder::class);
		$parent->method('nodeExists')->willReturn(false);
		$parent->method('newFile')->willThrowException(new Exception('read-only'));

		$file = $this->createMock(File::class);
		$file->method('getParent')->willReturn($parent);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Grondslagen summary write failed: doc_summary.pdf — read-only');

		$this->writer()->writeBesideFile($file, 'doc_summary.pdf', '%PDF');

	}//end testWriteBesideFileWrapsWriteFailure()

	/**
	 * An existing dossier report is refreshed after the final-document guard.
	 *
	 * @return void
	 */
	public function testSaveDossierSummaryGuardsTheRefresh(): void {
		$existing = $this->createMock(File::class);
		$existing->method('getId')->willReturn(88);
		$existing->expects($this->once())->method('putContent')->with('%PDF-d');

		$sub = $this->createMock(Folder::class);
		$sub->method('nodeExists')->with('grondslagen.pdf')->willReturn(true);
		$sub->method('get')->with('grondslagen.pdf')->willReturn($existing);

		$dossier = $this->createMock(Folder::class);
		$dossier->method('nodeExists')->with('anonymised')->willReturn(true);
		$dossier->method('get')->with('anonymised')->willReturn($sub);

		$this->finalDocuments->expects($this->once())
			->method('assertWritable')
			->with(88, 'refresh this grondslagen report');

		$this->assertSame($existing, $this->writer()->saveDossierSummary($dossier, '%PDF-d'));

	}//end testSaveDossierSummaryGuardsTheRefresh()

	/**
	 * A folder named grondslagen.pdf is not refreshed; a new file is written.
	 *
	 * @return void
	 */
	public function testSaveDossierSummaryWritesNewFileWhenNameIsAFolder(): void {
		$created = $this->createMock(File::class);
		$sub = $this->createMock(Folder::class);
		$sub->method('nodeExists')->willReturn(true);
		$sub->method('get')->willReturn($this->createMock(Folder::class));
		$sub->expects($this->once())->method('newFile')->with('grondslagen.pdf', '%PDF')->willReturn($created);

		$dossier = $this->createMock(Folder::class);
		$dossier->method('nodeExists')->willReturn(true);
		$dossier->method('get')->willReturn($sub);

		$this->finalDocuments->expects($this->never())->method('assertWritable');

		$this->assertSame($created, $this->writer()->saveDossierSummary($dossier, '%PDF'));

	}//end testSaveDossierSummaryWritesNewFileWhenNameIsAFolder()

	/**
	 * A final dossier report keeps its own exception type.
	 *
	 * @return void
	 */
	public function testSaveDossierSummaryKeepsFinalRefusal(): void {
		$existing = $this->createMock(File::class);
		$existing->method('getId')->willReturn(88);
		$existing->expects($this->never())->method('putContent');

		$sub = $this->createMock(Folder::class);
		$sub->method('nodeExists')->willReturn(true);
		$sub->method('get')->willReturn($existing);

		$dossier = $this->createMock(Folder::class);
		$dossier->method('nodeExists')->willReturn(true);
		$dossier->method('get')->willReturn($sub);

		$this->finalDocuments->method('assertWritable')
			->willThrowException(new DocumentFinalException('Report is final.'));

		$this->expectException(DocumentFinalException::class);
		$this->expectExceptionMessage('Report is final.');

		$this->writer()->saveDossierSummary($dossier, '%PDF');

	}//end testSaveDossierSummaryKeepsFinalRefusal()

	/**
	 * A file occupying the subfolder name fails the save with a wrapped error.
	 *
	 * @return void
	 */
	public function testSaveDossierSummaryFailsWhenSubfolderNameIsAFile(): void {
		$dossier = $this->createMock(Folder::class);
		$dossier->method('nodeExists')->with('anonymised')->willReturn(true);
		$dossier->method('get')->with('anonymised')->willReturn($this->createMock(File::class));
		$dossier->expects($this->never())->method('newFolder');
		$dossier->expects($this->never())->method('newFile');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage(
			'Grondslagen summary: failed to write grondslagen.pdf to dossier folder: '
			. 'The output subfolder name "anonymised" is taken by a file in this dossier.'
		);

		$this->writer()->saveDossierSummary($dossier, '%PDF');

	}//end testSaveDossierSummaryFailsWhenSubfolderNameIsAFile()
}//end class
