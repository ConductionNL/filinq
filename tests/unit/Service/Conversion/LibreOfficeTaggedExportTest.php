<?php

/**
 * LibreOffice headless: the PDF it emits is read back under the name
 * soffice gives it, and the tagged (PDF/UA) export asks for tags and
 * PDF/UA, keeps PDF/A when asked, and fails closed without soffice.
 *
 * The process runner is replaced by one that behaves like soffice on
 * disk: it writes `<input stem>.pdf` into the output directory.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service\Conversion
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/pdfua-accessible-output/tasks.md#task-1.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Conversion;

use OCA\Filinq\Exception\ConversionFailedException;
use OCA\Filinq\Service\Conversion\LibreOfficeHeadlessBackend;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\IAppConfig;
use OCP\Lock\ILockingProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class LibreOfficeTaggedExportTest extends TestCase {

	/**
	 * A backend on a runner, with soffice "installed" as /bin/sh.
	 *
	 * @param DiskLikeSofficeRunner $runner  The runner.
	 * @param bool                  $enabled Whether the backend is switched on.
	 *
	 * @return LibreOfficeHeadlessBackend
	 */
	private function backend(DiskLikeSofficeRunner $runner, bool $enabled = true): LibreOfficeHeadlessBackend {
		$appConfig = $this->createMock(IAppConfig::class);
		$appConfig->method('getValueString')->willReturnCallback(
			static fn (string $app, string $key, string $default = '') => match ($key) {
				'filinq.conversion.libreoffice_binary_path' => '/bin/sh',
				'filinq.conversion.backends.libreoffice_enabled' => ($enabled === true ? 'true' : 'false'),
				default => $default,
			}
		);

		return new LibreOfficeHeadlessBackend(
			appConfig: $appConfig,
			lockingProvider: $this->createMock(ILockingProvider::class),
			logger: $this->createMock(LoggerInterface::class),
			processRunner: $runner,
		);
	}//end backend()

	/**
	 * The filter argument of a run.
	 *
	 * @param array<int, string> $argv The argv.
	 *
	 * @return string
	 */
	private function filterOf(array $argv): string {
		return $argv[(int) array_search('--convert-to', $argv, true) + 1];
	}//end filterOf()

	public function testConvertReadsThePdfSofficeNamesAfterTheInput(): void {
		$runner = new DiskLikeSofficeRunner('%PDF-1.7 from soffice');
		$written = [];
		$parent = $this->createMock(Folder::class);
		$parent->method('nodeExists')->willReturn(false);
		$parent->method('newFile')->willReturnCallback(function (string $name, string $bytes) use (&$written) {
			$written[$name] = $bytes;
			return $this->createMock(File::class);
		});
		$source = $this->createMock(File::class);
		$source->method('getName')->willReturn('brief aan inwoner.docx');
		$source->method('getContent')->willReturn('docx bytes');
		$source->method('getParent')->willReturn($parent);

		$this->backend($runner)->convert($source);

		$this->assertSame(['brief aan inwoner.pdf' => '%PDF-1.7 from soffice'], $written);
	}//end testConvertReadsThePdfSofficeNamesAfterTheInput()

	public function testTheTaggedExportAsksForTagsAndPdfUa(): void {
		$runner = new DiskLikeSofficeRunner('%PDF-1.7 tagged');

		$bytes = $this->backend($runner)->convertTagged(bytes: '<html lang="nl"></html>', extension: 'html', pdfa: false);

		$this->assertSame('%PDF-1.7 tagged', $bytes);
		$argv = $runner->runs[0];
		$filter = json_decode(substr($this->filterOf($argv), strlen('pdf:writer_pdf_Export:')), true);
		$this->assertSame('true', $filter['PDFUACompliance']['value']);
		$this->assertSame('true', $filter['UseTaggedPDF']['value']);
		$this->assertSame('0', $filter['SelectPdfVersion']['value']);
		$this->assertContains('--infilter=HTML (StarWriter)', $argv, 'HTML opens in Writer, whose PDF export tags');
	}//end testTheTaggedExportAsksForTagsAndPdfUa()

	public function testTheTaggedExportKeepsPdfAWhenAskedFor(): void {
		$runner = new DiskLikeSofficeRunner();

		$this->backend($runner)->convertTagged(bytes: 'docx bytes', extension: 'docx', pdfa: true);

		$filter = json_decode(substr($this->filterOf($runner->runs[0]), strlen('pdf:writer_pdf_Export:')), true);
		$this->assertSame('3', $filter['SelectPdfVersion']['value']);
		$this->assertSame('true', $filter['PDFUACompliance']['value']);
		$this->assertNotContains('--infilter=HTML (StarWriter)', $runner->runs[0]);
	}//end testTheTaggedExportKeepsPdfAWhenAskedFor()

	public function testTheTaggedExportFailsClosedWithoutSoffice(): void {
		$runner = new DiskLikeSofficeRunner();

		try {
			$this->backend($runner, enabled: false)->convertTagged(bytes: '<p>x</p>', extension: 'html', pdfa: false);
			$this->fail('a tagged export without soffice must throw');
		} catch (ConversionFailedException $e) {
			$this->assertSame('libreoffice_headless', $e->getAttempts()[0]['name']);
			$this->assertFalse($e->getAttempts()[0]['available']);
		}

		$this->assertSame([], $runner->runs);
	}//end testTheTaggedExportFailsClosedWithoutSoffice()
}//end class
