<?php

/**
 * Unit tests for PdfBesideSource and BackendFailure.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Conversion
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Conversion;

use OCA\Filinq\Exception\ConversionFailedException;
use OCA\Filinq\Service\Conversion\BackendFailure;
use OCA\Filinq\Service\Conversion\PdfBesideSource;
use OCP\Files\File;
use OCP\Files\Folder;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Writing the PDF next to its source, and the single-backend failure value.
 *
 * @covers \OCA\Filinq\Service\Conversion\PdfBesideSource
 * @covers \OCA\Filinq\Service\Conversion\BackendFailure
 * @uses   \OCA\Filinq\Exception\ConversionFailedException
 */
class PdfBesideSourceTest extends TestCase {

	/**
	 * An existing PDF of the same stem is deleted before the new one is written.
	 *
	 * @return void
	 */
	public function testReplacesExistingPdf(): void {
		$existing = $this->createMock(File::class);
		$existing->expects($this->once())->method('delete');
		$written = $this->createMock(File::class);

		$parent = $this->createMock(Folder::class);
		$parent->method('nodeExists')->with('report.final.pdf')->willReturn(true);
		$parent->method('get')->with('report.final.pdf')->willReturn($existing);
		$parent->expects($this->once())->method('newFile')->with('report.final.pdf', '%PDF')->willReturn($written);

		$source = $this->createMock(File::class);
		$source->method('getName')->willReturn('report.final.docx');
		$source->method('getParent')->willReturn($parent);

		$this->assertSame($written, (new PdfBesideSource())->write(source: $source, pdfBytes: '%PDF'));
	}//end testReplacesExistingPdf()

	/**
	 * A source without extension gets ".pdf" appended; nothing is deleted.
	 *
	 * @return void
	 */
	public function testNameWithoutExtension(): void {
		$written = $this->createMock(File::class);
		$parent = $this->createMock(Folder::class);
		$parent->method('nodeExists')->with('Makefile.pdf')->willReturn(false);
		$parent->expects($this->never())->method('get');
		$parent->expects($this->once())->method('newFile')->with('Makefile.pdf', 'b')->willReturn($written);

		$source = $this->createMock(File::class);
		$source->method('getName')->willReturn('Makefile');
		$source->method('getParent')->willReturn($parent);

		$this->assertSame($written, (new PdfBesideSource())->write(source: $source, pdfBytes: 'b'));
	}//end testNameWithoutExtension()

	/**
	 * The failure builds a 422 exception with one supported, available attempt.
	 *
	 * @return void
	 */
	public function testBackendFailureException(): void {
		$previous = new RuntimeException('cause');
		$exception = (new BackendFailure(message: 'Could not render', reason: 'font missing'))->exception(backendName: 'mpdf', previous: $previous);

		$this->assertInstanceOf(ConversionFailedException::class, $exception);
		$this->assertSame('Could not render', $exception->getMessage());
		$this->assertSame(422, $exception->getCode());
		$this->assertSame($previous, $exception->getPrevious());
		$this->assertSame(
			[['name' => 'mpdf', 'available' => true, 'supports' => true, 'reason' => 'font missing']],
			$exception->getAttempts()
		);
	}//end testBackendFailureException()
}//end class
