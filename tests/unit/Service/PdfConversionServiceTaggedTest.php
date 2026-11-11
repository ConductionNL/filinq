<?php

/**
 * PdfConversionService with `accessible: true`: only LibreOffice's tagged
 * export may answer, the other backends are never asked, and without it
 * the conversion fails with every backend's attempt record.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-pdfua-accessible-output/tasks.md#task-1.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Exception\ConversionFailedException;
use OCA\Filinq\Service\Conversion\ConversionBackendInterface;
use OCA\Filinq\Service\Conversion\LibreOfficeHeadlessBackend;
use OCA\Filinq\Service\PdfConversionService;
use OCA\Filinq\Tests\Unit\Service\Conversion\DiskLikeSofficeRunner;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\IAppConfig;
use OCP\Lock\ILockingProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class PdfConversionServiceTaggedTest extends TestCase {

	/**
	 * The LibreOffice backend on a disk-like runner.
	 *
	 * @param DiskLikeSofficeRunner $runner  The runner.
	 * @param bool                  $enabled Whether it is switched on.
	 *
	 * @return LibreOfficeHeadlessBackend
	 */
	private function libreOffice(DiskLikeSofficeRunner $runner, bool $enabled = true): LibreOfficeHeadlessBackend {
		$appConfig = $this->createMock(IAppConfig::class);
		$appConfig->method('getValueString')->willReturnCallback(
			static fn (string $app, string $key, string $default = '') => match ($key) {
				'filinq.conversion.libreoffice_binary_path' => '/bin/sh',
				'filinq.conversion.backends.libreoffice_enabled' => ($enabled === true ? 'true' : 'false'),
				default => $default,
			}
		);

		return new LibreOfficeHeadlessBackend($appConfig, $this->createMock(ILockingProvider::class), $this->createMock(LoggerInterface::class), $runner);
	}//end libreOffice()

	/**
	 * A backend that must never be asked.
	 *
	 * @return ConversionBackendInterface
	 */
	private function untaggedBackend(): ConversionBackendInterface {
		$backend = $this->createMock(ConversionBackendInterface::class);
		$backend->method('name')->willReturn('mpdf');
		$backend->method('isAvailable')->willReturn(true);
		$backend->method('canHandle')->willReturn(true);
		$backend->expects($this->never())->method('convert');
		return $backend;
	}//end untaggedBackend()

	/**
	 * A source DOCX beside which the PDF lands.
	 *
	 * @param array<string, string> $written Receives the files written.
	 *
	 * @return File
	 */
	private function source(array &$written): File {
		$parent = $this->createMock(Folder::class);
		$parent->method('nodeExists')->willReturn(false);
		$parent->method('newFile')->willReturnCallback(function (string $name, string $bytes) use (&$written) {
			$written[$name] = $bytes;
			$file = $this->createMock(File::class);
			$file->method('getPath')->willReturn('/admin/files/' . $name);
			return $file;
		});
		$source = $this->createMock(File::class);
		$source->method('getName')->willReturn('besluit.docx');
		$source->method('getMimeType')->willReturn('application/vnd.openxmlformats-officedocument.wordprocessingml.document');
		$source->method('getContent')->willReturn('docx bytes');
		$source->method('getParent')->willReturn($parent);
		$source->method('getPath')->willReturn('/admin/files/besluit.docx');
		return $source;
	}//end source()

	public function testAnAccessibleConversionUsesTheTaggedExportOnly(): void {
		$runner = new DiskLikeSofficeRunner('%PDF-1.7 tagged');
		$service = new PdfConversionService([$this->untaggedBackend(), $this->libreOffice($runner)], $this->createMock(LoggerInterface::class));
		$written = [];

		$result = $service->convertToPdfReporting($this->source($written), ['accessible' => true, 'pdfa' => true]);

		$this->assertSame('libreoffice_headless', $result['backend']);
		$this->assertSame(['besluit.pdf' => '%PDF-1.7 tagged'], $written);
		$this->assertStringContainsString('PDFUACompliance', implode(' ', $runner->runs[0]));
		$this->assertStringContainsString('"SelectPdfVersion":{"type":"long","value":"3"}', implode(' ', $runner->runs[0]));
	}//end testAnAccessibleConversionUsesTheTaggedExportOnly()

	public function testWithoutLibreOfficeTheAccessibleConversionFailsWithEveryAttempt(): void {
		$service = new PdfConversionService([$this->untaggedBackend(), $this->libreOffice(new DiskLikeSofficeRunner(), enabled: false)], $this->createMock(LoggerInterface::class));
		$written = [];

		try {
			$service->convertToPdf($this->source($written), ['accessible' => true]);
			$this->fail('must fail closed');
		} catch (ConversionFailedException $e) {
			$names = array_column($e->getAttempts(), 'name');
			$this->assertSame(['mpdf', 'libreoffice_headless'], $names);
			$this->assertFalse($e->getAttempts()[0]['supports']);
		}

		$this->assertSame([], $written);
	}//end testWithoutLibreOfficeTheAccessibleConversionFailsWithEveryAttempt()

	/**
	 * A LibreOffice backend double whose tagged export answers or fails.
	 *
	 * @param string                         $name   Its name.
	 * @param string|ConversionFailedException $answer The bytes, or the failure to throw.
	 *
	 * @return LibreOfficeHeadlessBackend
	 */
	private function taggedDouble(string $name, string|ConversionFailedException $answer): LibreOfficeHeadlessBackend {
		$backend = $this->createMock(LibreOfficeHeadlessBackend::class);
		$backend->method('name')->willReturn($name);
		if ($answer instanceof ConversionFailedException) {
			$backend->method('convertTagged')->willThrowException($answer);
			return $backend;
		}

		$backend->method('convertTagged')->willReturn($answer);
		return $backend;
	}//end taggedDouble()

	/**
	 * A source file with the given name whose folder may already hold the PDF.
	 *
	 * @param string $name   The source name.
	 * @param Folder $parent The folder.
	 *
	 * @return File
	 */
	private function namedSource(string $name, Folder $parent): File {
		$source = $this->createMock(File::class);
		$source->method('getName')->willReturn($name);
		$source->method('getContent')->willReturn('source bytes');
		$source->method('getParent')->willReturn($parent);
		return $source;
	}//end namedSource()

	/**
	 * A failing tagged export falls through to the next LibreOffice backend, foreign backends are skipped, an existing PDF is replaced.
	 *
	 * @return void
	 */
	public function testAFailingTaggedExportFallsThroughAndAnExistingPdfIsReplaced(): void {
		$attempt = ['name' => 'lo1', 'available' => true, 'supports' => true, 'reason' => 'crash'];
		$failure = new ConversionFailedException(message: 'x', attempts: [$attempt]);
		$first = $this->taggedDouble('lo1', $failure);
		$second = $this->taggedDouble('lo2', '%PDF tagged');
		$second->expects($this->once())->method('convertTagged')->with('source bytes', '', false);

		$existing = $this->createMock(File::class);
		$existing->expects($this->once())->method('delete');
		$written = $this->createMock(File::class);
		$parent = $this->createMock(Folder::class);
		$parent->method('nodeExists')->with('README.pdf')->willReturn(true);
		$parent->method('get')->with('README.pdf')->willReturn($existing);
		$parent->expects($this->once())->method('newFile')->with('README.pdf', '%PDF tagged')->willReturn($written);

		$service = new PdfConversionService([$first, new \stdClass(), $second], $this->createMock(LoggerInterface::class));
		$result = $service->convertToPdfReporting($this->namedSource('README', $parent), ['accessible' => true]);

		$this->assertSame(['file' => $written, 'backend' => 'lo2'], $result);
	}//end testAFailingTaggedExportFallsThroughAndAnExistingPdfIsReplaced()

	/**
	 * The PDF is named after the stem up to the last dot; the extension is lowercased.
	 *
	 * @return void
	 */
	public function testTheStemKeepsInnerDotsAndTheExtensionIsLowercased(): void {
		$backend = $this->taggedDouble('lo', '%PDF');
		$backend->expects($this->once())->method('convertTagged')->with('source bytes', 'docx', true);
		$parent = $this->createMock(Folder::class);
		$parent->method('nodeExists')->willReturn(false);
		$parent->expects($this->never())->method('get');
		$parent->expects($this->once())->method('newFile')->with('a.b.pdf', '%PDF')->willReturn($this->createMock(File::class));

		$service = new PdfConversionService([$backend], $this->createMock(LoggerInterface::class));
		$result = $service->convertToPdfReporting($this->namedSource('a.b.DOCX', $parent), ['accessible' => true, 'pdfa' => true]);
		$this->assertSame('lo', $result['backend']);
	}//end testTheStemKeepsInnerDotsAndTheExtensionIsLowercased()

	/**
	 * Without a working tagged export the failure carries every attempt, in order, and code 503.
	 *
	 * @return void
	 */
	public function testEveryFailedTaggedExportIsReportedWithCode503(): void {
		$offline = $this->createMock(ConversionBackendInterface::class);
		$offline->method('name')->willReturn('office_app');
		$offline->method('isAvailable')->willReturn(false);
		$first = $this->taggedDouble('lo1', new ConversionFailedException(message: 'a', attempts: [['name' => 'lo1', 'reason' => 'one']]));
		$second = $this->taggedDouble('lo2', new ConversionFailedException(message: 'b', attempts: [['name' => 'lo2', 'reason' => 'two']]));
		$parent = $this->createMock(Folder::class);
		$parent->expects($this->never())->method('newFile');

		$service = new PdfConversionService([$first, $offline, $second], $this->createMock(LoggerInterface::class));
		try {
			$service->convertToPdfReporting($this->namedSource('x.odt', $parent), ['accessible' => true]);
			$this->fail('must fail closed');
		} catch (ConversionFailedException $e) {
			$this->assertSame('Accessible PDF output needs LibreOffice, which is not available; no untagged PDF is made instead.', $e->getMessage());
			$this->assertSame(503, $e->getCode());
			$expected = [
				['name' => 'lo1', 'reason' => 'one'],
				['name' => 'office_app', 'available' => false, 'supports' => false, 'reason' => 'cannot write tagged PDF'],
				['name' => 'lo2', 'reason' => 'two'],
			];
			$this->assertSame($expected, $e->getAttempts());
		}
	}//end testEveryFailedTaggedExportIsReportedWithCode503()
}//end class
