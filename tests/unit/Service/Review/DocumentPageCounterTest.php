<?php

/**
 * A PDF counts its pages, an image is one page, anything else is unknown.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Review
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/image-redaction/specs/image-redaction/spec.md#requirement-a-page-a-page-range-or-a-whole-document-is-redacted-in-one-action-req-ddimr-009
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Review;

use OCA\Filinq\Service\Review\DocumentPageCounter;
use OCP\Files\File;
use PHPUnit\Framework\TestCase;
use setasign\Fpdi\Fpdi;

/**
 * @covers \OCA\Filinq\Service\Review\DocumentPageCounter
 * @uses   \OCA\Filinq\Service\PdfStreamReaderFactory
 */
class DocumentPageCounterTest extends TestCase {

	/**
	 * A file of a type and content.
	 *
	 * @param string $mime    The MIME type.
	 * @param string $content The bytes.
	 *
	 * @return File The file.
	 */
	private function file(string $mime, string $content = ''): File {
		$file = $this->createMock(File::class);
		$file->method('getMimeType')->willReturn($mime);
		$file->method('getContent')->willReturn($content);

		return $file;

	}//end file()

	public function testAPdfCountsItsPages(): void {
		$pdf = new Fpdi();
		for ($page = 1; $page <= 7; $page++) {
			$pdf->AddPage();
		}

		$this->assertSame(7, (new DocumentPageCounter())->count(file: $this->file(mime: 'application/pdf', content: $pdf->Output('S'))));

	}//end testAPdfCountsItsPages()

	public function testAnImageIsOnePage(): void {
		$this->assertSame(1, (new DocumentPageCounter())->count(file: $this->file(mime: 'image/jpeg')));

	}//end testAnImageIsOnePage()

	public function testAnOfficeFileHasNoKnownPages(): void {
		$this->assertSame(0, (new DocumentPageCounter())->count(file: $this->file(mime: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')));

	}//end testAnOfficeFileHasNoKnownPages()

	public function testAnUnreadablePdfHasNoKnownPages(): void {
		$this->assertSame(0, (new DocumentPageCounter())->count(file: $this->file(mime: 'application/pdf', content: 'not a pdf')));

	}//end testAnUnreadablePdfHasNoKnownPages()
}//end class
