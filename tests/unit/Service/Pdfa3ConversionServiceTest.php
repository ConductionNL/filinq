<?php

/**
 * Unit tests for Pdfa3ConversionService
 *
 * Exercises the real mPDF + FPDI conversion pipeline (mirroring
 * PdfServiceTest's convention of running mPDF for real rather than
 * mocking it) — only the Nextcloud-framework boundary (OCP\Files\File,
 * IAppConfig, LoggerInterface) is mocked.
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

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Exception\Pdfa3ConversionException;
use OCA\Filinq\Service\Charts\ChartSvgRenderer;
use OCA\Filinq\Service\Charts\TableHtmlRenderer;
use OCA\Filinq\Service\Pdfa3ConversionService;
use OCA\Filinq\Service\PdfService;
use OCA\Filinq\Service\TemplateRenderer;
use OCP\Files\File;
use OCP\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionClass;

/**
 * Unit tests for Pdfa3ConversionService
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class Pdfa3ConversionServiceTest extends TestCase {

	/**
	 * @var Pdfa3ConversionService
	 */
	private Pdfa3ConversionService $service;

	/**
	 * @var PdfService
	 */
	private PdfService $pdfService;

	/**
	 * @var IAppConfig|MockObject
	 */
	private IAppConfig|MockObject $mockAppConfig;

	/**
	 * @var LoggerInterface|MockObject
	 */
	private LoggerInterface|MockObject $mockLogger;

	/**
	 * Per-test config overrides, consulted by the IAppConfig mock's
	 * getValueString() callback so individual tests can tighten a cap
	 * (e.g. max_attachment_bytes) without a bespoke mock per test.
	 *
	 * @var array<string, string>
	 */
	private array $configOverrides = [];

	/**
	 * Set up test environment. PdfService is real (it only wraps
	 * TemplateRenderer + mPDF, no NC services), matching PdfServiceTest's
	 * own setup, so Pdfa3ConversionService gets the real bundled DejaVu
	 * font directory via PdfService::getFontDirectory().
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->configOverrides = [];

		$this->mockAppConfig = $this->createMock(IAppConfig::class);
		$this->mockAppConfig->method('getValueString')
			->willReturnCallback(
				function (string $app, string $key, string $default): string {
					return $this->configOverrides[$key] ?? $default;
				}
			);

		$this->mockLogger = $this->createMock(LoggerInterface::class);

		$this->pdfService = new PdfService(
			$this->mockLogger,
			new TemplateRenderer($this->mockLogger, new ChartSvgRenderer(), new TableHtmlRenderer())
		);

		$this->service = new Pdfa3ConversionService(
			$this->pdfService,
			$this->mockAppConfig,
			$this->mockLogger
		);

	}//end setUp()

	/**
	 * Test convertHtml produces a genuine PDF/A-3 document: %PDF header
	 * plus the XMP pdfaid:part=3 / pdfaid:conformance=B identification
	 * markers required for archival compliance.
	 *
	 * @return void
	 */
	public function testConvertHtmlProducesPdfa3WithXmpIdentification(): void {
		$result = $this->service->convertHtml(html: '<h1>Archival Document</h1>', metadata: ['title' => 'Test Doc']);

		$this->assertStringStartsWith('%PDF-', $result['content']);
		$this->assertStringContainsString('<pdfaid:part>3</pdfaid:part>', $result['content']);
		$this->assertStringContainsString('<pdfaid:conformance>B</pdfaid:conformance>', $result['content']);
		$this->assertEquals('3-B', $result['conformance']);
		$this->assertEquals(1, $result['pages']);
		$this->assertEquals(64, strlen($result['checksumSha256']));
		$this->assertEquals(hash('sha256', $result['content']), $result['checksumSha256']);

	}//end testConvertHtmlProducesPdfa3WithXmpIdentification()

	/**
	 * Test convertExistingPdf imports an already-existing PDF's pages
	 * and re-emits it as PDF/A-3 — the "existing PDF" input path from
	 * the feature scope. The source PDF is produced by PdfService
	 * itself (real mPDF, non-PDF/A) so this exercises the real FPDI
	 * import path end to end.
	 *
	 * @return void
	 */
	public function testConvertExistingPdfImportsPagesAndProducesPdfa3(): void {
		$sourceBytes = $this->pdfService->generatePdfFromHtml(
			html: '<h1>Original non-archival document</h1>',
			options: ['title' => 'Original']
		);

		$mockFile = $this->createMock(File::class);
		$mockFile->method('getSize')->willReturn(strlen($sourceBytes));
		$mockFile->method('getContent')->willReturn($sourceBytes);
		$mockFile->method('getName')->willReturn('original.pdf');

		$result = $this->service->convertExistingPdf(source: $mockFile, metadata: ['identifier' => 'ZAAK-2026-001']);

		$this->assertStringStartsWith('%PDF-', $result['content']);
		$this->assertStringContainsString('<pdfaid:part>3</pdfaid:part>', $result['content']);
		$this->assertStringContainsString('<pdfaid:conformance>B</pdfaid:conformance>', $result['content']);
		$this->assertEquals(1, $result['pages']);

	}//end testConvertExistingPdfImportsPagesAndProducesPdfa3()

	/**
	 * Test attachment embedding: a caller-supplied attachment ends up
	 * in the output as a genuine PDF/A-3 embedded file (the feature
	 * that actually distinguishes PDF/A-3 from PDF/A-1/A-2).
	 *
	 * @return void
	 */
	public function testConvertHtmlEmbedsAttachment(): void {
		$result = $this->service->convertHtml(
			html: '<h1>Doc with attachment</h1>',
			metadata: [],
			attachments: [
				[
					'name' => 'source-record.xml',
					'mime' => 'text/xml',
					'content' => '<record><id>1</id></record>',
					'description' => 'Source system record',
					'AFRelationship' => 'Source',
				],
			]
		);

		$this->assertStringContainsString('/Type /Filespec', $result['content']);
		$this->assertStringContainsString('/Type /EmbeddedFile', $result['content']);
		$this->assertStringContainsString('source-record.xml', $result['content']);
		$this->assertStringContainsString('/AFRelationship /Source', $result['content']);

	}//end testConvertHtmlEmbedsAttachment()

	/**
	 * Test metadata mapping: standard fields go through mPDF's dedicated
	 * setters (visible in the XMP dc: block) and non-standard MDTO/archival
	 * fields are folded into the docudesk: XMP namespace AND an
	 * auto-generated metadata.xml sidecar attachment.
	 *
	 * @return void
	 */
	public function testConvertHtmlMapsMdtoMetadataIntoXmpAndSidecar(): void {
		$result = $this->service->convertHtml(
			html: '<h1>Beschikking</h1>',
			metadata: [
				'title' => 'Archival Test Title',
				'author' => 'Gemeente Voorbeeld',
				'identifier' => 'ZAAK-2026-001',
				'caseReference' => 'BEK-42',
			]
		);

		// Standard fields land in the XMP dc: block (uncompressed, plain UTF-8 text).
		$this->assertStringContainsString('Archival Test Title', $result['content']);
		$this->assertStringContainsString('Gemeente Voorbeeld', $result['content']);

		// Non-standard MDTO fields land in the docudesk: custom XMP namespace.
		// The prefix and its namespace URI deliberately keep the pre-rename
		// spelling — an XML namespace is an identifier baked into every PDF/A-3
		// already produced, and changing it declares a different vocabulary
		// rather than renaming this one. See Pdfa3MetadataAssembler.
		$this->assertStringContainsString('<docudesk:identifier>ZAAK-2026-001</docudesk:identifier>', $result['content']);
		$this->assertStringContainsString('<docudesk:caseReference>BEK-42</docudesk:caseReference>', $result['content']);

		// And an MDTO metadata sidecar was auto-embedded (attachment differentiator).
		$this->assertStringContainsString('mdto-metadata.xml', $result['content']);

	}//end testConvertHtmlMapsMdtoMetadataIntoXmpAndSidecar()

	/**
	 * Test that a disabled converter (tenant config flag off) fails
	 * gracefully with a typed, admin-actionable error instead of a
	 * generic exception or a 500.
	 *
	 * @return void
	 */
	public function testConvertHtmlFailsGracefullyWhenConverterDisabled(): void {
		$this->configOverrides['filinq.pdfa3.enabled'] = 'false';

		$this->expectException(Pdfa3ConversionException::class);

		try {
			$this->service->convertHtml(html: '<h1>x</h1>');
		} catch (Pdfa3ConversionException $e) {
			$this->assertEquals(Pdfa3ConversionException::REASON_CONVERTER_UNAVAILABLE, $e->getReason());
			$this->assertEquals(503, $e->getCode());
			$this->assertNotEmpty($e->getAdminHint());
			throw $e;
		}

	}//end testConvertHtmlFailsGracefullyWhenConverterDisabled()

	/**
	 * Test the source-size guardrail: a source PDF larger than the
	 * configured cap is rejected before its content is even read.
	 *
	 * @return void
	 */
	public function testConvertExistingPdfRejectsOversizedSource(): void {
		$this->configOverrides['filinq.pdfa3.max_input_bytes'] = '100';

		$mockFile = $this->createMock(File::class);
		$mockFile->method('getSize')->willReturn(200);
		$mockFile->method('getName')->willReturn('big.pdf');
		$mockFile->expects($this->never())->method('getContent');

		$this->expectException(Pdfa3ConversionException::class);

		try {
			$this->service->convertExistingPdf(source: $mockFile);
		} catch (Pdfa3ConversionException $e) {
			$this->assertEquals(Pdfa3ConversionException::REASON_SOURCE_TOO_LARGE, $e->getReason());
			$this->assertEquals(413, $e->getCode());
			throw $e;
		}

	}//end testConvertExistingPdfRejectsOversizedSource()

	/**
	 * Test the per-attachment size guardrail.
	 *
	 * @return void
	 */
	public function testConvertHtmlRejectsOversizedAttachment(): void {
		$this->configOverrides['filinq.pdfa3.max_attachment_bytes'] = '10';

		$this->expectException(Pdfa3ConversionException::class);

		try {
			$this->service->convertHtml(
				html: '<h1>x</h1>',
				attachments: [
					[
						'name' => 'too-big.txt',
						'mime' => 'text/plain',
						'content' => 'this content is definitely longer than ten bytes',
					],
				]
			);
		} catch (Pdfa3ConversionException $e) {
			$this->assertEquals(Pdfa3ConversionException::REASON_ATTACHMENT_TOO_LARGE, $e->getReason());
			$this->assertEquals(413, $e->getCode());
			throw $e;
		}

	}//end testConvertHtmlRejectsOversizedAttachment()

	/**
	 * Test the time-budget guardrail. Uses a subclass that overrides the
	 * protected now() clock hook so the deadline is deterministically
	 * already exceeded by the time the in-loop check runs — no real
	 * sleeping, no flakiness.
	 *
	 * @return void
	 */
	public function testConvertHtmlEnforcesTimeBudget(): void {
		$timedOutService = new class($this->pdfService, $this->mockAppConfig, $this->mockLogger) extends Pdfa3ConversionService {

			private int $calls = 0;

			protected function now(): float {
				// First call computes the deadline baseline; every
				// subsequent call (the in-loop budget check) reports a
				// time far past that deadline.
				$this->calls++;
				if ($this->calls === 1) {
					return 1000.0;
				}

				return 1000000.0;
			}//end now()
		};

		$this->expectException(Pdfa3ConversionException::class);

		try {
			$timedOutService->convertHtml(html: '<h1>x</h1>');
		} catch (Pdfa3ConversionException $e) {
			$this->assertEquals(Pdfa3ConversionException::REASON_TIME_LIMIT_EXCEEDED, $e->getReason());
			$this->assertEquals(504, $e->getCode());
			throw $e;
		}

	}//end testConvertHtmlEnforcesTimeBudget()

	/**
	 * Test the no-silent-passthrough guardrail directly: validateOutput()
	 * must reject bytes that don't start with the PDF magic header, even
	 * though it is only reachable internally after a real mPDF render —
	 * invoked via reflection to prove the check itself is correct in
	 * isolation.
	 *
	 * @return void
	 */
	public function testValidateOutputRejectsMissingPdfHeader(): void {
		$this->expectException(Pdfa3ConversionException::class);

		try {
			$this->invokeValidateOutput('not a pdf at all');
		} catch (Pdfa3ConversionException $e) {
			$this->assertEquals(Pdfa3ConversionException::REASON_OUTPUT_VALIDATION_FAILED, $e->getReason());
			throw $e;
		}

	}//end testValidateOutputRejectsMissingPdfHeader()

	/**
	 * Test the no-silent-passthrough guardrail: bytes that look like a
	 * PDF but carry no PDF/A XMP identification markers must never be
	 * returned as if they were compliant — this is the guard against a
	 * future regression that drops the PDFA config flag while still
	 * emitting 200/binary.
	 *
	 * @return void
	 */
	public function testValidateOutputRejectsPdfWithoutPdfaMarkers(): void {
		$this->expectException(Pdfa3ConversionException::class);

		try {
			$this->invokeValidateOutput('%PDF-1.7 no xmp identification markers here');
		} catch (Pdfa3ConversionException $e) {
			$this->assertEquals(Pdfa3ConversionException::REASON_OUTPUT_VALIDATION_FAILED, $e->getReason());
			throw $e;
		}

	}//end testValidateOutputRejectsPdfWithoutPdfaMarkers()

	/**
	 * Test that genuinely PDF/A-3 bytes pass validateOutput() without
	 * throwing (sanity check that the guardrail isn't over-strict).
	 *
	 * @return void
	 */
	public function testValidateOutputAcceptsGenuinePdfa3Bytes(): void {
		$result = $this->service->convertHtml(html: '<p>ok</p>');

		// Re-running the already-produced, already-validated bytes through
		// the guardrail must not throw.
		$this->invokeValidateOutput($result['content']);
		$this->addToAssertionCount(1);

	}//end testValidateOutputAcceptsGenuinePdfa3Bytes()

	/**
	 * Invoke the private validateOutput() method via reflection.
	 *
	 * @param string $pdfBytes Candidate PDF bytes.
	 *
	 * @return void
	 *
	 * @throws Pdfa3ConversionException When validation fails (propagated from the real method).
	 */
	private function invokeValidateOutput(string $pdfBytes): void {
		$reflection = new ReflectionClass($this->service);
		$method = $reflection->getMethod('validateOutput');
		$method->setAccessible(true);

		try {
			$method->invoke($this->service, $pdfBytes);
		} catch (\ReflectionException $e) {
			$this->fail('validateOutput() is not reachable via reflection: ' . $e->getMessage());
		}

	}//end invokeValidateOutput()
}//end class
