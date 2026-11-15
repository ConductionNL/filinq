<?php

/**
 * Unit tests for PhpWordBackend's refusals and reader selection.
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
use OCA\Filinq\Service\Conversion\PhpWordBackend;
use OCA\Filinq\Service\Conversion\PhpWordIo;
use OCA\Filinq\Service\PdfService;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\IAppConfig;
use OCP\ITempManager;
use PhpOffice\PhpWord\PhpWord;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * What PhpWordBackend refuses, and which PhpWord reader each extension gets.
 *
 * @covers \OCA\Filinq\Service\Conversion\PhpWordBackend
 *
 * @uses \OCA\Filinq\Exception\ConversionFailedException
 */
class PhpWordBackendFailuresTest extends TestCase {

	/**
	 * The temp manager double.
	 *
	 * @var ITempManager&MockObject
	 */
	private ITempManager $tempManager;

	/**
	 * The PDF service double.
	 *
	 * @var PdfService&MockObject
	 */
	private PdfService $pdfService;

	/**
	 * The reader/writer double.
	 *
	 * @var PhpWordIo&MockObject
	 */
	private PhpWordIo $io;

	/**
	 * Temp paths handed out, removed after each test.
	 *
	 * @var array<int, string>
	 */
	private array $temps = [];

	/**
	 * Set up.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->tempManager = $this->createMock(ITempManager::class);
		$this->pdfService = $this->createMock(PdfService::class);
		$this->io = $this->createMock(PhpWordIo::class);
	}//end setUp()

	/**
	 * Remove the temp files.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		foreach ($this->temps as $path) {
			@unlink($path);
		}

		parent::tearDown();
	}//end tearDown()

	/**
	 * The backend under test.
	 *
	 * @return PhpWordBackend
	 */
	private function backend(): PhpWordBackend {
		return new PhpWordBackend(
			appConfig: $this->createMock(IAppConfig::class),
			tempManager: $this->tempManager,
			pdfService: $this->pdfService,
			logger: $this->createMock(LoggerInterface::class),
			phpWordIo: $this->io,
		);
	}//end backend()

	/**
	 * Hand out a real temp path for the given suffix.
	 *
	 * @return void
	 */
	private function writableTemp(): void {
		$this->tempManager->method('getTemporaryFile')->willReturnCallback(
			function (string $suffix): string {
				$path = (string) tempnam(sys_get_temp_dir(), 'pwfail_') . $suffix;
				$this->temps[] = $path;

				return $path;
			}
		);
	}//end writableTemp()

	/**
	 * A source file double.
	 *
	 * @param string      $name    The file name.
	 * @param mixed       $content What getContent answers.
	 * @param Folder|null $parent  The parent folder.
	 *
	 * @return File
	 */
	private function source(string $name, mixed $content = 'bytes', ?Folder $parent = null): File {
		$source = $this->createMock(File::class);
		$source->method('getName')->willReturn($name);
		$source->method('getContent')->willReturn($content);
		$source->method('getParent')->willReturn($parent ?? $this->createMock(Folder::class));

		return $source;
	}//end source()

	/**
	 * Assert that converting the source fails with the message and reason given.
	 *
	 * @param File   $source   The source.
	 * @param string $message  The expected message.
	 * @param string $reason   The expected attempt reason.
	 * @param bool   $supports The expected supports flag.
	 *
	 * @return ConversionFailedException The failure, for further checks.
	 */
	private function assertConversionFails(File $source, string $message, string $reason, bool $supports = true): ConversionFailedException {
		try {
			$this->backend()->convert($source);
		} catch (ConversionFailedException $e) {
			self::assertSame($message, $e->getMessage());
			self::assertSame([['name' => 'phpword', 'available' => true, 'supports' => $supports, 'reason' => $reason]], $e->getAttempts());

			return $e;
		}

		self::fail('Expected the conversion to fail: ' . $message);
	}//end assertConversionFails()

	/**
	 * An extension without a PhpWord reader is refused before anything is written.
	 *
	 * @return void
	 */
	public function testAnUnmappedExtensionIsRefused(): void {
		$this->tempManager->expects(self::never())->method('getTemporaryFile');
		$this->io->expects(self::never())->method('load');

		$this->assertConversionFails(
			$this->source(name: 'sheet.xlsx'),
			'PhpWord backend reached convert() for unsupported extension xlsx',
			'No PhpWord reader mapped for .xlsx',
			false
		);
	}//end testAnUnmappedExtensionIsRefused()

	/**
	 * No temp file is a refusal, and the source is never read.
	 *
	 * @return void
	 */
	public function testNoTempFileIsRefused(): void {
		$this->tempManager->expects(self::once())->method('getTemporaryFile')->with('.odt')->willReturn(false);
		$source = $this->createMock(File::class);
		$source->method('getName')->willReturn('letter.odt');
		$source->expects(self::never())->method('getContent');

		$this->assertConversionFails($source, 'PhpWord backend could not allocate a temporary file.', 'getTemporaryFile returned false');
	}//end testNoTempFileIsRefused()

	/**
	 * A failing PDF renderer is refused with its message and kept as the cause.
	 *
	 * @return void
	 */
	public function testAFailingRendererIsRefused(): void {
		$this->writableTemp();
		$this->io->method('load')->willReturn(new PhpWord());
		$this->io->method('toHtml')->willReturn('<p>x</p>');
		$cause = new RuntimeException('mpdf exploded');
		$this->pdfService->method('generatePdfFromHtml')->willThrowException($cause);

		$failure = $this->assertConversionFails(
			$this->source(name: 'letter.docx'),
			'PdfService failed to render PhpWord-HTML: mpdf exploded',
			'PdfService::generatePdfFromHtml failed: mpdf exploded'
		);
		self::assertSame($cause, $failure->getPrevious());
	}//end testAFailingRendererIsRefused()

	/**
	 * An empty PDF is refused and nothing is written.
	 *
	 * @return void
	 */
	public function testAnEmptyPdfIsRefused(): void {
		$this->writableTemp();
		$this->io->method('load')->willReturn(new PhpWord());
		$this->io->method('toHtml')->willReturn('<p>x</p>');
		$this->pdfService->method('generatePdfFromHtml')->willReturn('');
		$parent = $this->createMock(Folder::class);
		$parent->expects(self::never())->method('newFile');

		$this->assertConversionFails(
			$this->source(name: 'letter.docx', parent: $parent),
			'PdfService returned empty PDF bytes.',
			'generatePdfFromHtml returned empty string'
		);
	}//end testAnEmptyPdfIsRefused()

	/**
	 * Each extension is read by its own reader from a temp file holding the source bytes,
	 * and @page rules are stripped from the HTML handed to the renderer.
	 *
	 * @return void
	 */
	public function testEachExtensionUsesItsReader(): void {
		$expected = ['doc' => 'MsDoc', 'docx' => 'Word2007', 'odt' => 'ODText', 'rtf' => 'RTF', 'html' => 'HTML', 'htm' => 'HTML'];
		$this->writableTemp();
		$readers = [];
		$this->io->method('load')->willReturnCallback(
			function (string $path, string $readerName) use (&$readers): PhpWord {
				$readers[pathinfo($path, PATHINFO_EXTENSION)] = $readerName;
				self::assertSame('source-bytes', file_get_contents($path));

				return new PhpWord();
			}
		);
		$this->io->method('toHtml')->willReturn('<style>@page page1 { size: A4 portrait; margin: 1in; } p { color: red; }</style><p>x</p>');
		$this->pdfService->expects(self::exactly(count($expected)))->method('generatePdfFromHtml')
			->with('<style> p { color: red; }</style><p>x</p>', self::anything())->willReturn('%PDF');
		$parent = $this->createMock(Folder::class);
		$parent->method('nodeExists')->willReturn(false);
		$parent->method('newFile')->willReturn($this->createMock(File::class));

		foreach (array_keys($expected) as $extension) {
			$this->backend()->convert($this->source(name: 'Report.Final.' . strtoupper($extension), content: 'source-bytes', parent: $parent));
		}

		self::assertSame($expected, $readers);
	}//end testEachExtensionUsesItsReader()
}//end class
