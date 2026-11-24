<?php

/**
 * The Convert to PDF/A operation: a new uniquely named copy beside the source
 * or in the target folder, the source untouched, a person's file never
 * replaced, and conversion failures recorded (REQ-DDFLO-006).
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Flow
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/flow-operations/tasks.md#task-4.1
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Flow;

use OCA\Filinq\Exception\Pdfa3ConversionException;
use OCA\Filinq\Flow\FlowOperationOutcome;
use OCA\Filinq\Flow\Operation\ConvertToPdfaOperation;
use OCA\Filinq\Kernel\Contracts\Pdfa3ConversionServiceInterface;
use OCA\Filinq\Service\FileUploadService;
use OCA\Filinq\Service\PdfConversionService;
use OCP\Files\File;
use OCP\Files\Folder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests ConvertToPdfaOperation::execute().
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Flow
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @covers \OCA\Filinq\Flow\Operation\ConvertToPdfaOperation
 * @uses   \OCA\Filinq\Flow\Operation\AbstractFileOperation
 * @uses   \OCA\Filinq\Flow\FlowOperationOutcome
 * @uses   \OCA\Filinq\Exception\Pdfa3ConversionException
 */
class ConvertToPdfaOperationTest extends TestCase {
	use FlowOperationDoubles;

	/**
	 * @var Pdfa3ConversionServiceInterface&MockObject
	 */
	private Pdfa3ConversionServiceInterface $pdfa;

	/**
	 * @var PdfConversionService&MockObject
	 */
	private PdfConversionService $pdf;

	/**
	 * @var FileUploadService&MockObject
	 */
	private FileUploadService $uploads;

	/**
	 * @var Folder&MockObject
	 */
	private Folder $parent;

	/**
	 * @var Folder&MockObject
	 */
	private Folder $home;

	/**
	 * Set up.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->pdfa = $this->createMock(Pdfa3ConversionServiceInterface::class);
		$this->pdf = $this->createMock(PdfConversionService::class);
		$this->uploads = $this->createMock(FileUploadService::class);
		$this->uploads->method('resolveUniqueFileName')->willReturnCallback(static fn (Folder $folder, string $fileName): string => $fileName);
		$this->parent = $this->createMock(Folder::class);
		$this->home = $this->createMock(Folder::class);

	}//end setUp()

	/**
	 * A source that must never be written, moved or deleted.
	 *
	 * @param string $name The name.
	 * @param string $mime The MIME type.
	 *
	 * @return File&MockObject The source.
	 */
	private function source(string $name, string $mime): File {
		$source = $this->createMock(File::class);
		$source->method('getName')->willReturn($name);
		$source->method('getMimeType')->willReturn($mime);
		$source->method('getParent')->willReturn($this->parent);
		$source->expects($this->never())->method('delete');
		$source->expects($this->never())->method('putContent');
		$source->expects($this->never())->method('move');

		return $source;

	}//end source()

	/**
	 * A written file with an id.
	 *
	 * @param int $id The id.
	 *
	 * @return File&MockObject The file.
	 */
	private function written(int $id): File {
		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn($id);

		return $file;

	}//end written()

	/**
	 * Run the operation.
	 *
	 * @param File                 $source The source.
	 * @param array<string, mixed> $config The options.
	 *
	 * @return FlowOperationOutcome The outcome.
	 */
	private function execute(File $source, array $config=[]): FlowOperationOutcome {
		return (new ConvertToPdfaOperation($this->l10n(), $this->urls(), $this->idleTrigger(), $this->pdfa, $this->pdf, $this->uploads))
			->execute($source, $config, $this->home);

	}//end execute()

	/**
	 * A PDF is converted directly and the copy written beside it as <name>.pdfa.pdf.
	 *
	 * @return void
	 */
	public function testAPdfGetsASiblingPdfaCopy(): void {
		$source = $this->source('besluit-2026-114.pdf', 'application/pdf');
		$this->pdf->expects($this->never())->method('convertToPdf');
		$this->pdfa->expects($this->once())->method('convertExistingPdf')
			->with($source, ['title' => 'besluit-2026-114'])
			->willReturn(['content' => '%PDF-A', 'checksumSha256' => 'x', 'pages' => 2, 'conformance' => 'PDF/A-3b', 'verified' => 'skipped']);
		$this->parent->expects($this->once())->method('newFile')->with('besluit-2026-114.pdfa.pdf', '%PDF-A')->willReturn($this->written(812100));

		$outcome = $this->execute($source);

		$this->assertSame('succeeded', $outcome->status);
		$this->assertSame(812100, $outcome->producedFileId);
		$this->assertSame(['producedFileId' => 812100, 'conformance' => 'PDF/A-3b', 'pages' => 2, 'verified' => 'skipped'], $outcome->resultSummary);

	}//end testAPdfGetsASiblingPdfaCopy()

	/**
	 * A DOCX goes through PDF first; the intermediate is removed and the copy lands in the target folder, created when absent.
	 *
	 * @return void
	 */
	public function testADocxIsArchivedInTheTargetFolder(): void {
		$source = $this->source('besluit.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
		$this->parent->method('nodeExists')->with('besluit.pdf')->willReturn(false);
		$intermediate = $this->createMock(File::class);
		$intermediate->expects($this->once())->method('delete');
		$this->pdf->expects($this->once())->method('convertToPdf')->with($source)->willReturn($intermediate);
		$this->pdfa->expects($this->once())->method('convertExistingPdf')->with($intermediate)
			->willReturn(['content' => '%PDF-A', 'checksumSha256' => 'x', 'pages' => 1, 'conformance' => 'PDF/A-3b', 'verified' => 'true']);
		$archief = $this->createMock(Folder::class);
		$this->home->method('nodeExists')->with('Archief')->willReturn(false);
		$this->home->expects($this->once())->method('newFolder')->with('Archief')->willReturn($archief);
		$archief->expects($this->once())->method('newFile')->with('besluit.pdfa.pdf', '%PDF-A')->willReturn($this->written(812101));
		$this->parent->expects($this->never())->method('newFile');

		$outcome = $this->execute($source, ['targetFolder' => 'Archief']);

		$this->assertSame('succeeded', $outcome->status);
		$this->assertSame(812101, $outcome->producedFileId);

	}//end testADocxIsArchivedInTheTargetFolder()

	/**
	 * A person's PDF of the same name beside a DOCX is never replaced: the run fails and nothing converts.
	 *
	 * @return void
	 */
	public function testAPersonsSiblingPdfIsNeverReplaced(): void {
		$source = $this->source('besluit.docx', 'application/msword');
		$this->parent->method('nodeExists')->with('besluit.pdf')->willReturn(true);
		$this->pdf->expects($this->never())->method('convertToPdf');
		$this->pdfa->expects($this->never())->method('convertExistingPdf');

		$outcome = $this->execute($source);

		$this->assertSame('failed', $outcome->status);
		$this->assertSame('conversion_error', $outcome->reason);
		$this->assertStringContainsString('besluit.pdf already exists', (string) $outcome->errorMessage);

	}//end testAPersonsSiblingPdfIsNeverReplaced()

	/**
	 * An encrypted PDF fails flagged with the refusal, and the source is untouched.
	 *
	 * @return void
	 */
	public function testAnEncryptedPdfFailsFlagged(): void {
		$source = $this->source('besluit-2026-114.pdf', 'application/pdf');
		$this->pdfa->method('convertExistingPdf')->willThrowException(new Pdfa3ConversionException(Pdfa3ConversionException::REASON_SOURCE_UNREADABLE, 'Source PDF is encrypted; PDF/A conversion refused', 'Decrypt the source first'));
		$this->parent->expects($this->never())->method('newFile');

		$outcome = $this->execute($source);

		$this->assertSame('failed', $outcome->status);
		$this->assertSame('conversion_error', $outcome->reason);
		$this->assertSame('Source PDF is encrypted; PDF/A conversion refused', $outcome->errorMessage);
		$this->assertNull($outcome->producedFileId);

	}//end testAnEncryptedPdfFailsFlagged()

	/**
	 * A target path that is a file fails the run before anything converts.
	 *
	 * @return void
	 */
	public function testATargetThatIsAFileFailsTheRun(): void {
		$source = $this->source('besluit.pdf', 'application/pdf');
		$this->home->method('nodeExists')->with('Archief')->willReturn(true);
		$this->home->method('get')->with('Archief')->willReturn($this->createMock(File::class));
		$this->pdfa->expects($this->never())->method('convertExistingPdf');

		$outcome = $this->execute($source, ['targetFolder' => 'Archief']);

		$this->assertSame('target_folder_unavailable', $outcome->reason);
		$this->assertStringContainsString('Archief is a file, not a folder', (string) $outcome->errorMessage);

	}//end testATargetThatIsAFileFailsTheRun()

	/**
	 * A target path climbing out of the owner's tree is refused at run time too.
	 *
	 * @return void
	 */
	public function testAClimbingTargetIsRefusedAtRunTime(): void {
		$source = $this->source('besluit.pdf', 'application/pdf');
		$this->home->expects($this->never())->method('newFolder');

		$this->assertSame('target_folder_unavailable', $this->execute($source, ['targetFolder' => '../../other'])->reason);

	}//end testAClimbingTargetIsRefusedAtRunTime()
}//end class
