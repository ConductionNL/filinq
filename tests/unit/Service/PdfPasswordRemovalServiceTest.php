<?php

/**
 * Unit tests for PdfPasswordRemovalService: a copy beside the original, checked at once;
 * a wrong password, a final document and a missing tool refused; the password kept nowhere.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/intake-pdf-password-removal/tasks.md#1-service-and-route
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\DocumentValidationService;
use OCA\Filinq\Service\FinalDocumentService;
use OCA\Filinq\Service\PdfPasswordRemovalService;
use OCA\Filinq\Service\QpdfDecryptor;
use OCP\Files\File;
use OCP\Files\Folder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * @covers \OCA\Filinq\Service\PdfPasswordRemovalService
 */
class PdfPasswordRemovalServiceTest extends TestCase {

	private const PASSWORD = 'Geheim-2026!';

	/** @var FinalDocumentService&MockObject */
	private FinalDocumentService&MockObject $final;

	/** @var DocumentValidationService&MockObject */
	private DocumentValidationService&MockObject $validation;

	/** @var LoggerInterface&MockObject */
	private LoggerInterface&MockObject $logger;

	/** @var QpdfDecryptor&MockObject */
	private QpdfDecryptor&MockObject $qpdf;

	/** @var Folder&MockObject */
	private Folder&MockObject $folder;

	protected function setUp(): void {
		$this->final = $this->createMock(FinalDocumentService::class);
		$this->validation = $this->createMock(DocumentValidationService::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->qpdf = $this->createMock(QpdfDecryptor::class);
		$this->folder = $this->createMock(Folder::class);
	}

	private function service(): PdfPasswordRemovalService {
		return new PdfPasswordRemovalService($this->final, $this->validation, $this->logger, $this->qpdf);
	}

	/**
	 * A locked PDF in the folder.
	 *
	 * @param string $content Its bytes.
	 *
	 * @return File&MockObject
	 */
	private function locked(string $content = "%PDF-1.7\n/Encrypt"): File {
		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn(41);
		$file->method('getName')->willReturn('offerte.pdf');
		$file->method('getContent')->willReturn($content);
		$file->method('getParent')->willReturn($this->folder);
		$file->expects($this->never())->method('putContent');
		return $file;
	}

	public function testTheRightPasswordWritesACheckedCopyBesideTheOriginal(): void {
		$this->final->method('refusalFor')->willReturn(null);
		$this->qpdf->method('isAvailable')->willReturn(true);
		$this->qpdf->expects($this->once())->method('decrypt')->with("%PDF-1.7\n/Encrypt", self::PASSWORD)->willReturn('%PDF-1.7 unlocked');

		$copy = $this->createMock(File::class);
		$copy->method('getId')->willReturn(42);
		$copy->method('getName')->willReturn('offerte (unlocked).pdf');
		$this->folder->expects($this->once())->method('getNonExistingName')->with('offerte (unlocked).pdf')->willReturn('offerte (unlocked).pdf');
		$this->folder->expects($this->once())->method('newFile')->with('offerte (unlocked).pdf', '%PDF-1.7 unlocked')->willReturn($copy);
		$this->validation->expects($this->once())->method('validate')->with($copy)
			->willReturn(['validationStatus' => 'warning', 'validationFindings' => [['code' => 'missing-text-layer']]]);

		$this->assertSame(
			[
				'outcome' => 'unlocked',
				'fileId' => 42,
				'name' => 'offerte (unlocked).pdf',
				'validationStatus' => 'warning',
				'validationFindings' => [['code' => 'missing-text-layer']],
			],
			$this->service()->unlock($this->locked(), self::PASSWORD)
		);
	}

	public function testAWrongPasswordWritesNothing(): void {
		$this->final->method('refusalFor')->willReturn(null);
		$this->qpdf->method('isAvailable')->willReturn(true);
		$this->qpdf->method('decrypt')->willReturn(null);
		$this->folder->expects($this->never())->method('newFile');
		$this->validation->expects($this->never())->method('validate');

		$this->assertSame(['outcome' => 'wrong-password'], $this->service()->unlock($this->locked(), 'fout'));
	}

	public function testAFinalDocumentIsRefusedBeforeAnythingRuns(): void {
		$this->final->expects($this->once())->method('refusalFor')->with(41, 'remove the password of this document')->willReturn('This document is final.');
		$this->qpdf->expects($this->never())->method('isAvailable');
		$this->qpdf->expects($this->never())->method('decrypt');

		$this->assertSame(['outcome' => 'final', 'message' => 'This document is final.'], $this->service()->unlock($this->locked(), self::PASSWORD));
	}

	public function testWithoutQpdfTheToolIsNamedAndNothingRuns(): void {
		$this->final->method('refusalFor')->willReturn(null);
		$this->qpdf->method('isAvailable')->willReturn(false);
		$this->qpdf->expects($this->never())->method('decrypt');

		$this->assertSame(['outcome' => 'no-tool'], $this->service()->unlock($this->locked(), self::PASSWORD));
		$this->assertFalse($this->service()->isAvailable());
	}

	public function testAFileThatIsNotAPdfIsRefused(): void {
		$this->final->method('refusalFor')->willReturn(null);
		$this->qpdf->method('isAvailable')->willReturn(true);
		$this->qpdf->expects($this->never())->method('decrypt');

		$this->assertSame(['outcome' => 'failed', 'message' => 'This file is not a PDF.'], $this->service()->unlock($this->locked('PK zip'), self::PASSWORD));
	}

	public function testAFailureIsLoggedWithoutThePassword(): void {
		$this->final->method('refusalFor')->willReturn(null);
		$this->qpdf->method('isAvailable')->willReturn(true);
		$this->qpdf->method('decrypt')->willThrowException(new RuntimeException('qpdf could not decrypt the PDF (exit 2).'));
		$this->logger->expects($this->once())->method('warning')->with(
			$this->logicalNot($this->stringContains(self::PASSWORD)),
			$this->callback(static fn (array $context): bool => str_contains((string)json_encode($context), self::PASSWORD) === false && $context['fileId'] === 41)
		);

		$result = $this->service()->unlock($this->locked(), self::PASSWORD);
		$this->assertSame('failed', $result['outcome']);
		$this->assertStringNotContainsString(self::PASSWORD, (string)json_encode($result));
	}

	public function testTheCopyNameKeepsTheOriginalsName(): void {
		$this->assertSame('offerte (unlocked).pdf', PdfPasswordRemovalService::copyName('offerte.pdf'));
		$this->assertSame('Scan 12 (unlocked).pdf', PdfPasswordRemovalService::copyName('Scan 12.PDF'));
		$this->assertSame('document (unlocked).pdf', PdfPasswordRemovalService::copyName('.pdf'));
	}
}
