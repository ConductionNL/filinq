<?php

/**
 * Unit tests for TaggedPdfConversion.
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
use OCA\Filinq\Service\Conversion\LibreOfficeHeadlessBackend;
use OCA\Filinq\Service\Conversion\TaggedPdfConversion;
use OCP\Files\File;
use OCP\Files\Folder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tagged PDF output: only LibreOffice can write it, and nothing untagged is made instead.
 *
 * @covers \OCA\Filinq\Service\Conversion\TaggedPdfConversion
 * @covers \OCA\Filinq\Service\Conversion\BackendWalk
 * @uses   \OCA\Filinq\Exception\ConversionFailedException
 */
class TaggedPdfConversionTest extends TestCase {

	/**
	 * Build a source file inside a parent folder.
	 *
	 * @param Folder $parent The parent folder.
	 *
	 * @return File&MockObject
	 */
	private function source(Folder $parent): File&MockObject {
		$file = $this->createMock(File::class);
		$file->method('getName')->willReturn('Besluit.v2.ODT');
		$file->method('getContent')->willReturn('odt-bytes');
		$file->method('getParent')->willReturn($parent);
		return $file;
	}//end source()

	/**
	 * Build a non-LibreOffice backend.
	 *
	 * @param string $name      Backend name.
	 * @param bool   $available Availability.
	 *
	 * @return ConversionBackendInterface&MockObject
	 */
	private function otherBackend(string $name, bool $available): ConversionBackendInterface&MockObject {
		$backend = $this->createMock(ConversionBackendInterface::class);
		$backend->method('name')->willReturn($name);
		$backend->method('isAvailable')->willReturn($available);
		$backend->expects($this->never())->method('convert');
		return $backend;
	}//end otherBackend()

	/**
	 * LibreOffice's tagged bytes replace an existing PDF beside the source.
	 *
	 * @return void
	 */
	public function testLibreOfficeWritesTaggedPdfBesideSource(): void {
		$existing = $this->createMock(File::class);
		$existing->expects($this->once())->method('delete');
		$written = $this->createMock(File::class);

		$parent = $this->createMock(Folder::class);
		$parent->method('nodeExists')->with('Besluit.v2.pdf')->willReturn(true);
		$parent->method('get')->with('Besluit.v2.pdf')->willReturn($existing);
		$parent->expects($this->once())->method('newFile')->with('Besluit.v2.pdf', 'tagged-pdf')->willReturn($written);

		$libre = $this->createMock(LibreOfficeHeadlessBackend::class);
		$libre->method('name')->willReturn('libreoffice');
		$libre->expects($this->once())->method('convertTagged')->with('odt-bytes', 'odt', true)->willReturn('tagged-pdf');

		$conversion = new TaggedPdfConversion(backends: [$this->otherBackend(name: 'office', available: true), $libre], pdfa: true);

		$this->assertSame(['file' => $written, 'backend' => 'libreoffice'], $conversion->run(source: $this->source(parent: $parent)));
	}//end testLibreOfficeWritesTaggedPdfBesideSource()

	/**
	 * Without a usable LibreOffice the walk fails with 503 and the collected attempts.
	 *
	 * @return void
	 */
	public function testFailsWithAttemptsWhenNoBackendCanTag(): void {
		$parent = $this->createMock(Folder::class);
		$parent->expects($this->never())->method('newFile');

		$libreAttempt = ['name' => 'libreoffice', 'available' => false, 'supports' => true, 'reason' => 'not installed'];
		$libre = $this->createMock(LibreOfficeHeadlessBackend::class);
		$libre->method('convertTagged')->with('odt-bytes', 'odt', false)
			->willThrowException(new ConversionFailedException(message: 'x', attempts: [$libreAttempt]));

		$conversion = new TaggedPdfConversion(
			backends: [$this->otherBackend(name: 'mpdf', available: false), 'not a backend', $libre],
			pdfa: false
		);

		try {
			$conversion->run(source: $this->source(parent: $parent));
			$this->fail('Expected ConversionFailedException');
		} catch (ConversionFailedException $e) {
			$this->assertSame(503, $e->getCode());
			$this->assertSame('Accessible PDF output needs LibreOffice, which is not available; no untagged PDF is made instead.', $e->getMessage());
			$this->assertSame(
				[
					['name' => 'mpdf', 'available' => false, 'supports' => false, 'reason' => 'cannot write tagged PDF'],
					$libreAttempt,
				],
				$e->getAttempts()
			);
		}
	}//end testFailsWithAttemptsWhenNoBackendCanTag()

	/**
	 * A new PDF is created without deleting anything when none exists.
	 *
	 * @return void
	 */
	public function testCreatesPdfWhenNoneExists(): void {
		$written = $this->createMock(File::class);
		$parent = $this->createMock(Folder::class);
		$parent->method('nodeExists')->willReturn(false);
		$parent->expects($this->never())->method('get');
		$parent->expects($this->once())->method('newFile')->with('Besluit.v2.pdf', 'pdf')->willReturn($written);

		$libre = $this->createMock(LibreOfficeHeadlessBackend::class);
		$libre->method('name')->willReturn('libreoffice');
		$libre->method('convertTagged')->willReturn('pdf');

		$result = (new TaggedPdfConversion(backends: [$libre], pdfa: false))->run(source: $this->source(parent: $parent));

		$this->assertSame($written, $result['file']);
	}//end testCreatesPdfWhenNoneExists()
}//end class
