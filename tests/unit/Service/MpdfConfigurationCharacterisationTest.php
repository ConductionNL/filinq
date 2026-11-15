<?php

/**
 * Unit tests for the mPDF configuration PdfService and Pdfa3ConversionService build
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\Pdfa3ConversionService;
use OCA\Filinq\Service\PdfService;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Pins the exact mPDF configuration PdfService builds (keys, values and
 * order), its temp-directory preparation, and the mPDF instance the PDF/A-3
 * conversion configures.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class MpdfConfigurationCharacterisationTest extends TestCase {

	/**
	 * The page options every case renders with.
	 *
	 * @var array<string,mixed>
	 */
	private const OPTIONS = [
		'format' => 'A3',
		'orientation' => 'L',
		'margin' => ['top' => 5, 'left' => 7],
	];

	/**
	 * Without options PdfService falls back to A4 portrait and 15mm margins.
	 *
	 * @return void
	 */
	public function testPdfServiceDefaultsWithoutPdfa(): void {
		$this->assertSame(
			[
				'tempDir' => '/tmp/x',
				'format' => 'A4',
				'orientation' => 'P',
				'margin_top' => 15,
				'margin_right' => 15,
				'margin_bottom' => 15,
				'margin_left' => 15,
			],
			$this->call(new ReflectionClass(PdfService::class), ['buildMpdfConfig', '/tmp/x', []])
		);

	}//end testPdfServiceDefaultsWithoutPdfa()

	/**
	 * With pdfa PdfService adds the PDF/A-3b keys and the embedded font.
	 *
	 * @return void
	 */
	public function testPdfServicePdfaConfig(): void {
		$options = self::OPTIONS + ['pdfa' => true];
		$this->assertSame(
			$this->pageSetup() + [
				'PDFA' => true,
				'PDFAauto' => true,
				'PDFAversion' => '3-B',
			] + $this->font(),
			$this->call(new ReflectionClass(PdfService::class), ['buildMpdfConfig', '/tmp/x', $options])
		);

	}//end testPdfServicePdfaConfig()

	/**
	 * Pdfa3ConversionService configures mPDF with the same page setup, the
	 * PDF/A-3b keys and the embedded DejaVu font, in its own temp directory.
	 *
	 * @return void
	 */
	public function testPdfa3ConversionConfiguresMpdf(): void {
		$mpdf = $this->call(new ReflectionClass(Pdfa3ConversionService::class), ['instantiateMpdf', self::OPTIONS]);
		$this->assertSame(
			[true, true, '3-B', 'L', 5, 7, 15, 15, 'dejavusans', sys_get_temp_dir() . '/filinq-pdfa3'],
			[
				$mpdf->PDFA,
				$mpdf->PDFAauto,
				$mpdf->PDFAversion,
				$mpdf->DefOrientation,
				$mpdf->tMargin,
				$mpdf->lMargin,
				$mpdf->rMargin,
				$mpdf->bMargin,
				$mpdf->default_font,
				$mpdf->tempDir,
			]
		);
		$this->assertEqualsWithDelta(297.0, $mpdf->fw, 0.01);
		$this->assertArrayHasKey('dejavusans', $mpdf->fontdata);

	}//end testPdfa3ConversionConfiguresMpdf()

	/**
	 * Without the bundled font directory the PDF/A-3 configuration keeps
	 * mPDF's own default font.
	 *
	 * @return void
	 */
	public function testPdfa3ConversionWithoutFontDirectory(): void {
		$pdf = $this->createPartialMock(PdfService::class, ['getFontDirectory']);
		$pdf->method('getFontDirectory')->willReturn(null);
		$service = (new ReflectionClass(Pdfa3ConversionService::class))->newInstanceWithoutConstructor();
		(function (PdfService $pdf): void {
			$this->pdfService = $pdf;
		})->call($service, $pdf);

		$mpdf = (new ReflectionClass(Pdfa3ConversionService::class))->getMethod('instantiateMpdf')->invoke($service, []);
		$this->assertSame([true, '3-B', 'P', 15], [$mpdf->PDFA, $mpdf->PDFAversion, $mpdf->DefOrientation, $mpdf->tMargin]);
		$this->assertNotSame('dejavusans', $mpdf->default_font);

	}//end testPdfa3ConversionWithoutFontDirectory()

	/**
	 * PdfService creates a missing temp directory and leaves it writable.
	 *
	 * @return void
	 */
	public function testEnsureTempDirectoryCreatesIt(): void {
		$dir = sys_get_temp_dir() . '/filinq-mpdf-' . bin2hex(random_bytes(4)) . '/nested';
		$this->call(new ReflectionClass(PdfService::class), ['ensureTempDirectory', $dir]);
		$this->assertDirectoryExists($dir);
		$this->assertSame('0777', substr(sprintf('%o', fileperms($dir)), -4));
		rmdir($dir);
		rmdir(dirname($dir));

	}//end testEnsureTempDirectoryCreatesIt()

	/**
	 * The page setup for OPTIONS in /tmp/x.
	 *
	 * @return array<string,mixed>
	 */
	private function pageSetup(): array {
		return [
			'tempDir' => '/tmp/x',
			'format' => 'A3',
			'orientation' => 'L',
			'margin_top' => 5,
			'margin_right' => 15,
			'margin_bottom' => 15,
			'margin_left' => 7,
		];

	}//end pageSetup()

	/**
	 * The embedded DejaVu font keys.
	 *
	 * @return array<string,mixed>
	 */
	private function font(): array {
		return [
			'fontDir' => [dirname(__DIR__, 3) . '/lib/Fonts'],
			'fontdata' => [
				'dejavusans' => [
					'R' => 'DejaVuSans.ttf',
					'B' => 'DejaVuSans-Bold.ttf',
					'I' => 'DejaVuSans-Oblique.ttf',
					'BI' => 'DejaVuSans-BoldOblique.ttf',
				],
			],
			'default_font' => 'dejavusans',
		];

	}//end font()

	/**
	 * Call a private method on an instance built without its constructor;
	 * the PDF/A-3 service gets a real PdfService for the font directory.
	 *
	 * @param ReflectionClass<object> $class The service.
	 * @param array<int,mixed>        $call  The method name followed by its arguments.
	 *
	 * @return mixed What the method returns.
	 */
	private function call(ReflectionClass $class, array $call): mixed {
		$service = $class->newInstanceWithoutConstructor();
		if ($service instanceof Pdfa3ConversionService) {
			$pdf = (new ReflectionClass(PdfService::class))->newInstanceWithoutConstructor();
			(function (PdfService $pdf): void {
				$this->pdfService = $pdf;
			})->call($service, $pdf);
		}

		$method = array_shift($call);
		return $class->getMethod($method)->invoke($service, ...$call);

	}//end call()

}//end class
