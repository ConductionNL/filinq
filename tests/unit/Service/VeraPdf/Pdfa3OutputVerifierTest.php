<?php

/**
 * A PDF/A-3 conversion checked by veraPDF: the verdict it carries, the
 * report kept on the source file, and strict mode refusing to hand back
 * output that fails. Runs the real converter (mPDF) and the real verifier.
 *
 * A conversion's bytes differ per run, so the recorded veraPDF answers them
 * with the report veraPDF 1.30.2 printed for the same kind of output
 * (FAKE_VERAPDF_REPORT).
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\VeraPdf
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-verapdf-validation/tasks.md#task-4.1
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\VeraPdf;

use OCA\Filinq\Exception\Pdfa3ConversionException;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\Pdfa3ConversionService;
use OCA\Filinq\Service\PdfService;
use OCA\Filinq\Service\VeraPdf\ConformanceGuidance;
use OCA\Filinq\Service\VeraPdf\ConformanceReportRepository;
use OCA\Filinq\Service\VeraPdf\ConformanceService;
use OCA\Filinq\Service\VeraPdf\Pdfa3OutputVerifier;
use OCA\Filinq\Service\VeraPdf\VeraPdfService;
use OCA\Filinq\Tests\Unit\Service\SubjectErasure\SubjectErasureDoubles;
use OCP\Files\File;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class Pdfa3OutputVerifierTest extends TestCase {
	use SubjectErasureDoubles;
	use VeraPdfDoubles;

	protected function tearDown(): void {
		putenv('FAKE_VERAPDF_REPORT');
		putenv('FAKE_VERAPDF_SLEEP');
		parent::tearDown();

	}//end tearDown()

	/**
	 * The real converter, with or without the verifier.
	 *
	 * @param boolean $verified Whether to wire the verifier.
	 *
	 * @return Pdfa3ConversionService The converter.
	 */
	private function converter(bool $verified = true): Pdfa3ConversionService {
		$pdf = $this->createMock(PdfService::class);
		$pdf->method('getFontDirectory')->willReturn(null);
		$verifier = null;
		if ($verified === true) {
			$conformance = new ConformanceService(
				$this->veraPdf(),
				new ConformanceGuidance(),
				new ConformanceReportRepository(new DocumentObjectServiceResolver($this->container(), $this->apps())),
				$this->clock()
			);
			$verifier = new Pdfa3OutputVerifier($conformance, $this->appConfig(), new NullLogger());
		}

		return new Pdfa3ConversionService($pdf, $this->appConfig(), new NullLogger(), null, null, $verifier);

	}//end converter()

	/**
	 * The uploaded letter with a font it never embedded, as file 42.
	 *
	 * @return File The source.
	 */
	private function letter(): File {
		$fpdf = new \FPDF();
		$fpdf->AddPage();
		$fpdf->SetFont('Helvetica', '', 12);
		$fpdf->Cell(0, 10, 'Voorbeeldbrief met een niet-ingesloten lettertype.');
		$bytes = $fpdf->Output('S');
		$source = $this->createMock(File::class);
		$source->method('getId')->willReturn(42);
		$source->method('getSize')->willReturn(strlen($bytes));
		$source->method('getContent')->willReturn($bytes);
		$source->method('getName')->willReturn('voorbeeldbrief.pdf');

		return $source;

	}//end letter()

	/**
	 * Report mode: failing output still comes back, marked false, and the
	 * source file carries the report with the imported-pages advice.
	 *
	 * @return void
	 */
	public function testReportModeShipsTheBytesAndRecordsTheFailure(): void {
		putenv('FAKE_VERAPDF_REPORT=pdfa3b-imported-unembedded-font.json');

		$result = $this->converter()->convertExistingPdf(source: $this->letter());

		$this->assertStringStartsWith('%PDF-', $result['content']);
		$this->assertSame(Pdfa3OutputVerifier::NOT_VERIFIED, $result['verified']);
		$report = array_values($this->rows[ConformanceReportRepository::SCHEMA])[0];
		$this->assertSame(42, $report['fileId']);
		$this->assertSame('conversionOutput', $report['subject']);
		$this->assertSame('conversion', $report['trigger']);
		$this->assertSame(['Helvetica'], $report['fontsNotEmbedded']);
		$this->assertSame(ConformanceGuidance::RECONVERT_FROM_SOURCE, $report['guidance']);
		$this->assertSame(hash('sha256', $result['content']), $report['checksumSha256']);
		$this->assertValidAgainstSchema(schema: 'conformanceReport', payload: $report);

	}//end testReportModeShipsTheBytesAndRecordsTheFailure()

	/**
	 * Strict mode: the same failing output is refused and no bytes return.
	 *
	 * @return void
	 */
	public function testStrictModeRefusesFailingOutput(): void {
		putenv('FAKE_VERAPDF_REPORT=pdfa3b-imported-unembedded-font.json');
		$this->config[Pdfa3OutputVerifier::CFG_STRICT] = 'true';

		try {
			$this->converter()->convertExistingPdf(source: $this->letter());
			$this->fail('Strict mode returned output that fails PDF/A.');
		} catch (Pdfa3ConversionException $e) {
			$this->assertSame(Pdfa3ConversionException::REASON_OUTPUT_VALIDATION_FAILED, $e->getReason());
			$this->assertSame(422, $e->getCode());
		}

	}//end testStrictModeRefusesFailingOutput()

	/**
	 * Rendered HTML that passes is marked true, strict or not.
	 *
	 * @return void
	 */
	public function testPassingOutputIsVerified(): void {
		putenv('FAKE_VERAPDF_REPORT=pdfa3b-rendered.json');
		$this->config[Pdfa3OutputVerifier::CFG_STRICT] = 'true';

		$result = $this->converter()->convertHtml(html: '<p>Besluit</p>', metadata: ['title' => 'Besluit']);

		$this->assertSame(Pdfa3OutputVerifier::VERIFIED, $result['verified']);
		$this->assertSame([], $this->rows[ConformanceReportRepository::SCHEMA] ?? [], 'Rendered output has no source file to keep a report on.');

	}//end testPassingOutputIsVerified()

	/**
	 * No validator: skipped, and the conversion is what it was.
	 *
	 * @return void
	 */
	public function testNoValidatorKeepsTodaysEnvelope(): void {
		$this->config[VeraPdfService::CFG_BINARY_PATH] = '/nonexistent/verapdf';
		$this->config[Pdfa3OutputVerifier::CFG_STRICT] = 'true';

		$result = $this->converter()->convertExistingPdf(source: $this->letter());

		$this->assertSame(Pdfa3OutputVerifier::SKIPPED, $result['verified']);
		$this->assertSame('3-B', $result['conformance']);
		$this->assertSame(Pdfa3OutputVerifier::SKIPPED, $this->converter(verified: false)->convertExistingPdf(source: $this->letter())['verified']);

	}//end testNoValidatorKeepsTodaysEnvelope()

	/**
	 * A validator that cannot answer: skipped in report mode, refused in
	 * strict mode, since strict promises checked output.
	 *
	 * @return void
	 */
	public function testAValidatorErrorIsSkippedOrRefused(): void {
		$this->config[VeraPdfService::CFG_MAX_SECONDS] = '1';
		$converter = $this->converter();
		putenv('FAKE_VERAPDF_SLEEP=3');

		$this->assertSame(Pdfa3OutputVerifier::SKIPPED, $converter->convertExistingPdf(source: $this->letter())['verified']);

		$this->config[Pdfa3OutputVerifier::CFG_STRICT] = 'true';
		$this->expectException(Pdfa3ConversionException::class);
		$this->converter()->convertExistingPdf(source: $this->letter());

	}//end testAValidatorErrorIsSkippedOrRefused()
}//end class
