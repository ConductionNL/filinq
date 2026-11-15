<?php

/**
 * Unit tests for PdfConversionCascade.
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
use OCA\Filinq\Service\Conversion\ConversionBackendInterface;
use OCA\Filinq\Service\Conversion\PdfConversionCascade;
use OCP\Files\File;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * The backend cascade: skip, fall through, succeed or fail with every attempt.
 *
 * @covers \OCA\Filinq\Service\Conversion\PdfConversionCascade
 * @covers \OCA\Filinq\Service\Conversion\BackendWalk
 * @uses   \OCA\Filinq\Exception\ConversionFailedException
 */
class PdfConversionCascadeTest extends TestCase {

	/**
	 * Build a source file mock.
	 *
	 * @param string $name     File name.
	 * @param string $mimeType MIME type.
	 *
	 * @return File&MockObject
	 */
	private function source(string $name, string $mimeType): File&MockObject {
		$file = $this->createMock(File::class);
		$file->method('getName')->willReturn($name);
		$file->method('getMimeType')->willReturn($mimeType);
		$file->method('getPath')->willReturn('/admin/files/' . $name);
		return $file;
	}//end source()

	/**
	 * Build a backend mock.
	 *
	 * @param string $name      Backend name.
	 * @param bool   $available Whether it is available.
	 * @param bool   $handles   Whether it handles the source.
	 *
	 * @return ConversionBackendInterface&MockObject
	 */
	private function backend(string $name, bool $available, bool $handles): ConversionBackendInterface&MockObject {
		$backend = $this->createMock(ConversionBackendInterface::class);
		$backend->method('name')->willReturn($name);
		$backend->method('isAvailable')->willReturn($available);
		$backend->method('canHandle')->willReturn($handles);
		return $backend;
	}//end backend()

	/**
	 * The first backend that converts wins; later ones are never asked.
	 *
	 * @return void
	 */
	public function testFirstSuccessfulBackendWins(): void {
		$source = $this->source(name: 'Brief.DOCX', mimeType: 'application/msword');
		$output = $this->createMock(File::class);
		$output->method('getPath')->willReturn('/admin/files/Brief.pdf');

		$disabled = $this->backend(name: 'office', available: false, handles: true);
		$disabled->expects($this->never())->method('convert');
		$winner = $this->createMock(ConversionBackendInterface::class);
		$winner->method('name')->willReturn('phpword');
		$winner->method('isAvailable')->willReturn(true);
		$winner->expects($this->once())->method('canHandle')->with('application/msword', 'docx')->willReturn(true);
		$winner->expects($this->once())->method('convert')->with($source)->willReturn($output);
		$later = $this->backend(name: 'mpdf', available: true, handles: true);
		$later->expects($this->never())->method('isAvailable');

		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->once())->method('info')->with(
			'[PdfConversionService] Conversion succeeded',
			['backend' => 'phpword', 'source' => '/admin/files/Brief.DOCX', 'output' => '/admin/files/Brief.pdf']
		);

		$result = (new PdfConversionCascade(backends: [$disabled, $winner, $later], logger: $logger))->run(source: $source);

		$this->assertSame(['file' => $output, 'backend' => 'phpword'], $result);
	}//end testFirstSuccessfulBackendWins()

	/**
	 * A throwing backend is logged and the next one is tried.
	 *
	 * @return void
	 */
	public function testThrowingBackendFallsThrough(): void {
		$source = $this->source(name: 'a.odt', mimeType: 'application/vnd.oasis.opendocument.text');
		$output = $this->createMock(File::class);

		$broken = $this->backend(name: 'soffice', available: true, handles: true);
		$broken->method('convert')->willThrowException(new RuntimeException('timeout'));
		$good = $this->backend(name: 'phpword', available: true, handles: true);
		$good->method('convert')->willReturn($output);

		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->once())->method('warning')->with(
			'[PdfConversionService] Backend failed; falling through',
			['backend' => 'soffice', 'source' => '/admin/files/a.odt', 'exception' => RuntimeException::class, 'message' => 'timeout']
		);

		$result = (new PdfConversionCascade(backends: [$broken, $good], logger: $logger))->run(source: $source);

		$this->assertSame('phpword', $result['backend']);
		$this->assertSame($output, $result['file']);
	}//end testThrowingBackendFallsThrough()

	/**
	 * When nothing converts, the exception lists every attempt in order and skips non-backends.
	 *
	 * @return void
	 */
	public function testAllFailingRecordsEveryAttempt(): void {
		$source = $this->source(name: 'README', mimeType: 'text/plain');

		$disabled = $this->backend(name: 'office', available: false, handles: true);
		$unsupported = $this->backend(name: 'eml', available: true, handles: false);
		$broken = $this->backend(name: 'mpdf', available: true, handles: true);
		$broken->method('convert')->willThrowException(new RuntimeException('bad html'));

		$backends = [$disabled, new \stdClass(), $unsupported, $broken];
		$cascade = new PdfConversionCascade(backends: $backends, logger: $this->createMock(LoggerInterface::class));

		try {
			$cascade->run(source: $source);
			$this->fail('Expected ConversionFailedException');
		} catch (ConversionFailedException $e) {
			$this->assertSame('Conversion to PDF failed; no backend in the cascade succeeded.', $e->getMessage());
			$this->assertSame(422, $e->getCode());
			$this->assertSame(
				[
					['name' => 'office', 'available' => false, 'supports' => false, 'reason' => 'backend disabled or prerequisites not present'],
					['name' => 'eml', 'available' => true, 'supports' => false, 'reason' => 'backend does not support MIME text/plain / extension (none)'],
					['name' => 'mpdf', 'available' => true, 'supports' => true, 'reason' => 'bad html'],
				],
				$e->getAttempts()
			);
		}
	}//end testAllFailingRecordsEveryAttempt()

	/**
	 * An empty cascade fails with no attempts.
	 *
	 * @return void
	 */
	public function testEmptyCascadeFails(): void {
		$cascade = new PdfConversionCascade(backends: [], logger: $this->createMock(LoggerInterface::class));

		$this->expectException(ConversionFailedException::class);
		$this->expectExceptionMessage('no backend in the cascade succeeded');

		$cascade->run(source: $this->source(name: 'x.txt', mimeType: 'text/plain'));
	}//end testEmptyCascadeFails()
}//end class
