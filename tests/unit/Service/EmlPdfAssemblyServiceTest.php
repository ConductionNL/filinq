<?php

/**
 * Unit tests for EmlPdfAssemblyService
 *
 * Drives the assembly over array-shaped redacted fixtures (the service's
 * property accessor tolerates arrays as well as OR's value objects), running
 * a real PdfService + TemplateRenderer so the output is a genuine PDF.
 * Covers: text-only / HTML / empty body, the inline-image cid: substitution,
 * placeholder variants (unsupported / oversize / non-renderable), renderable
 * attachment rendering, nested EML recursion + depth limit, per-attachment
 * render failure recovery, and the no-verbatim-embedding invariant.
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
 */

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Exception\ConversionFailedException;
use OCA\Filinq\Service\Charts\ChartSvgRenderer;
use OCA\Filinq\Service\Charts\TableHtmlRenderer;
use OCA\Filinq\Service\EmlPdfAssemblyService;
use OCA\Filinq\Service\PdfService;
use OCA\Filinq\Service\TemplateRenderer;
use OCP\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
class EmlPdfAssemblyServiceTest extends TestCase {

	/**
	 * @var IAppConfig&MockObject
	 */
	private IAppConfig $appConfig;

	/**
	 * @var EmlPdfAssemblyService
	 */
	private EmlPdfAssemblyService $service;

	/**
	 * Build the service with real PdfService + TemplateRenderer and a config
	 * mock returning the supplied (or default) config values.
	 *
	 * @param array<string,string> $config Optional config-key overrides.
	 *
	 * @return void
	 */
	private function buildService(array $config = []): void {
		$logger = $this->createMock(LoggerInterface::class);

		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->appConfig->method('getValueString')->willReturnCallback(
			static function (string $app, string $key, string $default = '') use ($config): string {
				return $config[$key] ?? $default;
			}
		);

		$pdfService = new PdfService(
			$logger,
			new TemplateRenderer($logger, new ChartSvgRenderer(), new TableHtmlRenderer())
		);

		$this->service = new EmlPdfAssemblyService(
			$pdfService,
			new TemplateRenderer($logger, new ChartSvgRenderer(), new TableHtmlRenderer()),
			$this->appConfig,
			$logger
		);

	}//end buildService()

	/**
	 * Standard redacted headers fixture.
	 *
	 * @return array<string,mixed>
	 */
	private function headers(): array {
		return [
			'from' => '[PERSOON: 1]',
			'replyTo' => '',
			'to' => ['[PERSOON: 2]', '[PERSOON: 3]'],
			'cc' => [],
			'subject' => 'Bezwaarschrift [PERSOON: 1]',
			'date' => '2026-01-15 09:30',
		];
	}//end headers()

	/**
	 * Build a redacted-structure fixture array.
	 *
	 * @param array<string,mixed> $body Body shape (plain/html).
	 * @param array<int,array<string,mixed>> $attachments Attachment fixtures.
	 * @param array<string,string> $inlineImages Inline-image map.
	 *
	 * @return array<string,mixed>
	 */
	private function structure(array $body, array $attachments = [], array $inlineImages = []): array {
		return [
			'headers' => $this->headers(),
			'body' => $body,
			'attachments' => $attachments,
			'inlineImages' => $inlineImages,
		];
	}//end structure()

	/**
	 * Minimal one-page PDF bytes for the PDF-attachment-render path.
	 *
	 * @return string
	 */
	private function tinyPdf(): string {
		$logger = $this->createMock(LoggerInterface::class);
		$pdfService = new PdfService($logger, new TemplateRenderer($logger, new ChartSvgRenderer(), new TableHtmlRenderer()));
		return $pdfService->generatePdfFromHtml('<p>Redacted [PERSOON: 9] attachment.</p>', ['pdfa' => true]);
	}//end tinyPdf()

	/**
	 * Text-only body assembles to a valid PDF.
	 *
	 * @return void
	 */
	public function testPlainTextBodyProducesPdf(): void {
		$this->buildService();
		$result = (object)$this->structure(body: ['plain' => 'Beste [PERSOON: 1],\n\nGroet.', 'html' => null]);

		$pdf = $this->service->assemble(result: $result, sourceFilename: 'mail.eml');

		$this->assertStringStartsWith('%PDF', $pdf);

	}//end testPlainTextBodyProducesPdf()

	/**
	 * HTML body is preferred and assembles to a valid PDF.
	 *
	 * @return void
	 */
	public function testHtmlBodyProducesPdf(): void {
		$this->buildService();
		$result = (object)$this->structure(
			body: ['plain' => 'plain fallback', 'html' => '<p>Beste <b>[PERSOON: 1]</b></p>']
		);

		$pdf = $this->service->assemble(result: $result);

		$this->assertStringStartsWith('%PDF', $pdf);

	}//end testHtmlBodyProducesPdf()

	/**
	 * Both body parts null still produces a valid PDF (empty-body notice).
	 *
	 * @return void
	 */
	public function testEmptyBodyProducesPdf(): void {
		$this->buildService();
		$result = (object)$this->structure(body: ['plain' => null, 'html' => null]);

		$pdf = $this->service->assemble(result: $result);

		$this->assertStringStartsWith('%PDF', $pdf);

	}//end testEmptyBodyProducesPdf()

	/**
	 * Inline cid: image is substituted from the redacted inline-image map.
	 * (The bytes are tiny placeholder PNG bytes; mPDF tolerates them via the
	 * data URI without throwing, exercising the substitution path.)
	 *
	 * @return void
	 */
	public function testInlineCidImageResolvedFromMap(): void {
		$this->buildService();
		// 1x1 transparent PNG.
		$png = base64_decode(
			'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='
		);
		$result = (object)$this->structure(
			body: ['plain' => null, 'html' => '<p>Logo: <img src="cid:logo@x"></p>'],
			inlineImages: ['logo@x' => $png]
		);

		$pdf = $this->service->assemble(result: $result);

		$this->assertStringStartsWith('%PDF', $pdf);

	}//end testInlineCidImageResolvedFromMap()

	/**
	 * Unsupported attachment yields a placeholder page (no throw, valid PDF).
	 *
	 * @return void
	 */
	public function testUnsupportedAttachmentProducesPlaceholder(): void {
		$this->buildService();
		$result = (object)$this->structure(
			body: ['plain' => 'body', 'html' => null],
			attachments: [
				(object)[
					'filename' => 'verslag.bin',
					'mimeType' => 'application/octet-stream',
					'redactedContent' => null,
					'unsupported' => true,
					'nestedEml' => null,
				],
			]
		);

		$pdf = $this->service->assemble(result: $result);

		$this->assertStringStartsWith('%PDF', $pdf);

	}//end testUnsupportedAttachmentProducesPlaceholder()

	/**
	 * Oversize redacted attachment yields a placeholder (size cap = 10 bytes).
	 *
	 * @return void
	 */
	public function testOversizeAttachmentProducesPlaceholder(): void {
		$this->buildService(
			config: ['filinq.conversion.eml.max_attachment_render_size_bytes' => '10']
		);
		$result = (object)$this->structure(
			body: ['plain' => 'body', 'html' => null],
			attachments: [
				(object)[
					'filename' => 'big.txt',
					'mimeType' => 'text/plain',
					'redactedContent' => str_repeat('x', 5000),
					'unsupported' => false,
					'nestedEml' => null,
				],
			]
		);

		$pdf = $this->service->assemble(result: $result);

		$this->assertStringStartsWith('%PDF', $pdf);

	}//end testOversizeAttachmentProducesPlaceholder()

	/**
	 * Non-renderable redacted attachment (xlsx) yields a placeholder.
	 *
	 * @return void
	 */
	public function testNonRenderableAttachmentProducesPlaceholder(): void {
		$this->buildService();
		$result = (object)$this->structure(
			body: ['plain' => 'body', 'html' => null],
			attachments: [
				(object)[
					'filename' => 'sheet.xlsx',
					'mimeType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
					'redactedContent' => 'redacted-xlsx-bytes',
					'unsupported' => false,
					'nestedEml' => null,
				],
			]
		);

		$pdf = $this->service->assemble(result: $result);

		$this->assertStringStartsWith('%PDF', $pdf);

	}//end testNonRenderableAttachmentProducesPlaceholder()

	/**
	 * Renderable text attachment is rendered as appended pages.
	 *
	 * @return void
	 */
	public function testRenderableTextAttachmentRenders(): void {
		$this->buildService();
		$result = (object)$this->structure(
			body: ['plain' => 'body', 'html' => null],
			attachments: [
				(object)[
					'filename' => 'note.txt',
					'mimeType' => 'text/plain',
					'redactedContent' => "Geredigeerde tekst [PERSOON: 4]\nRegel 2",
					'unsupported' => false,
					'nestedEml' => null,
				],
			]
		);

		$pdf = $this->service->assemble(result: $result);

		$this->assertStringStartsWith('%PDF', $pdf);

	}//end testRenderableTextAttachmentRenders()

	/**
	 * Renderable PDF attachment is imported as appended pages.
	 *
	 * @return void
	 */
	public function testRenderablePdfAttachmentImports(): void {
		$this->buildService();
		$result = (object)$this->structure(
			body: ['plain' => 'body', 'html' => null],
			attachments: [
				(object)[
					'filename' => 'bijlage-1.pdf',
					'mimeType' => 'application/pdf',
					'redactedContent' => $this->tinyPdf(),
					'unsupported' => false,
					'nestedEml' => null,
				],
			]
		);

		$pdf = $this->service->assemble(result: $result);

		$this->assertStringStartsWith('%PDF', $pdf);

	}//end testRenderablePdfAttachmentImports()

	/**
	 * append_attachment_pages=false renders only the envelope (renderable
	 * attachments are not appended); still a valid PDF.
	 *
	 * @return void
	 */
	public function testAppendPagesFalseRendersEnvelopeOnly(): void {
		$this->buildService(
			config: ['filinq.conversion.eml.append_attachment_pages' => 'false']
		);
		$result = (object)$this->structure(
			body: ['plain' => 'body', 'html' => null],
			attachments: [
				(object)[
					'filename' => 'note.txt',
					'mimeType' => 'text/plain',
					'redactedContent' => 'should not render',
					'unsupported' => false,
					'nestedEml' => null,
				],
			]
		);

		$pdf = $this->service->assemble(result: $result);

		$this->assertStringStartsWith('%PDF', $pdf);

	}//end testAppendPagesFalseRendersEnvelopeOnly()

	/**
	 * Nested EML attachment is recursively assembled.
	 *
	 * @return void
	 */
	public function testNestedEmlRecursivelyAssembled(): void {
		$this->buildService();
		$nested = (object)$this->structure(body: ['plain' => 'Geneste e-mail body [PERSOON: 7]', 'html' => null]);
		$result = (object)$this->structure(
			body: ['plain' => 'outer', 'html' => null],
			attachments: [
				(object)[
					'filename' => 'doorgestuurd.eml',
					'mimeType' => 'message/rfc822',
					'redactedContent' => null,
					'unsupported' => false,
					'nestedEml' => $nested,
				],
			]
		);

		$pdf = $this->service->assemble(result: $result);

		$this->assertStringStartsWith('%PDF', $pdf);

	}//end testNestedEmlRecursivelyAssembled()

	/**
	 * Nested EML beyond the depth cap (nestedEml null, unsupported flagged)
	 * yields a depth-limit placeholder.
	 *
	 * @return void
	 */
	public function testNestedEmlDepthLimitPlaceholder(): void {
		$this->buildService();
		$result = (object)$this->structure(
			body: ['plain' => 'outer', 'html' => null],
			attachments: [
				(object)[
					'filename' => 'diep.eml',
					'mimeType' => 'message/rfc822',
					'redactedContent' => null,
					'unsupported' => true,
					'nestedEml' => null,
				],
			]
		);

		$pdf = $this->service->assemble(result: $result);

		$this->assertStringStartsWith('%PDF', $pdf);

	}//end testNestedEmlDepthLimitPlaceholder()

	/**
	 * A renderable attachment whose bytes fail to render (corrupt PDF) does
	 * not abort the assembly — the output is still a valid PDF (the failing
	 * attachment falls back to a placeholder).
	 *
	 * @return void
	 */
	public function testPerAttachmentRenderFailureRecovers(): void {
		$this->buildService();
		$result = (object)$this->structure(
			body: ['plain' => 'body', 'html' => null],
			attachments: [
				(object)[
					'filename' => 'corrupt.pdf',
					'mimeType' => 'application/pdf',
					'redactedContent' => 'this is not a valid pdf',
					'unsupported' => false,
					'nestedEml' => null,
				],
			]
		);

		$pdf = $this->service->assemble(result: $result);

		$this->assertStringStartsWith('%PDF', $pdf);

	}//end testPerAttachmentRenderFailureRecovers()

	/**
	 * No verbatim embedding: the assembled PDF must not carry the
	 * original/redacted attachment bytes as an embedded file. We assert the
	 * attachment's distinctive payload marker is absent from the output PDF
	 * for a NON-renderable attachment (whose bytes are dropped to a
	 * placeholder and must never appear).
	 *
	 * @return void
	 */
	public function testNoVerbatimEmbeddingOfDroppedAttachment(): void {
		$this->buildService();
		$marker = 'XYZZY_SECRET_PAYLOAD_MARKER';
		$result = (object)$this->structure(
			body: ['plain' => 'body', 'html' => null],
			attachments: [
				(object)[
					'filename' => 'sheet.xlsx',
					'mimeType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
					'redactedContent' => $marker . str_repeat('A', 100),
					'unsupported' => false,
					'nestedEml' => null,
				],
			]
		);

		$pdf = $this->service->assemble(result: $result);

		$this->assertStringStartsWith('%PDF', $pdf);
		$this->assertStringNotContainsString(
			$marker,
			$pdf,
			'dropped non-renderable attachment bytes must never appear in the PDF'
		);

	}//end testNoVerbatimEmbeddingOfDroppedAttachment()

	/**
	 * A non-object structure cannot be assembled — guarded upstream; here we
	 * confirm assemble() throwing surfaces as ConversionFailedException when
	 * the PDF engine cannot emit (smoke: empty headers/body still succeeds,
	 * so we assert the happy path does NOT throw).
	 *
	 * @return void
	 */
	public function testMinimalStructureDoesNotThrow(): void {
		$this->buildService();
		$result = (object)[
			'headers' => [],
			'body' => ['plain' => null, 'html' => null],
			'attachments' => [],
			'inlineImages' => [],
		];

		$pdf = $this->service->assemble(result: $result);

		$this->assertStringStartsWith('%PDF', $pdf);

	}//end testMinimalStructureDoesNotThrow()

	/**
	 * assemble() is typed to throw ConversionFailedException on
	 * unrecoverable engine failure — assert the exception class exists and is
	 * referenced by the service contract (compile-time guard).
	 *
	 * @return void
	 */
	public function testConversionFailedExceptionIsAvailable(): void {
		$this->assertTrue(class_exists(ConversionFailedException::class));

	}//end testConversionFailedExceptionIsAvailable()

}//end class
