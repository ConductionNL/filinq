<?php

/**
 * Unit tests for EmlEnvelopeRenderer
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use Mpdf\Mpdf;
use OCA\Filinq\Service\EmlEnvelopeRenderer;
use OCA\Filinq\Service\PdfService;
use OCA\Filinq\Service\TemplateRenderer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * The envelope renderer builds the Twig context from OR's redacted structure
 * (headers, body mode, inline images resolved to data URIs), falls back to a
 * minimal header-only envelope when Twig fails, and never emits body content
 * in that fallback.
 *
 * @covers \OCA\Filinq\Service\EmlEnvelopeRenderer
 * @uses   \OCA\Filinq\Service\EmlTemplateLoader
 * @uses   \OCA\Filinq\Service\EmlStructureReader
 * @uses   \OCA\Filinq\Service\PdfService
 */
class EmlEnvelopeRendererTest extends TestCase {

	/**
	 * Template renderer mock.
	 *
	 * @var TemplateRenderer&MockObject
	 */
	private TemplateRenderer&MockObject $templateRenderer;

	/**
	 * Logger mock.
	 *
	 * @var LoggerInterface&MockObject
	 */
	private LoggerInterface&MockObject $logger;

	/**
	 * Real PDF service (only its print-CSS helper is exercised).
	 *
	 * @var PdfService
	 */
	private PdfService $pdfService;

	/**
	 * Build the collaborators.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->templateRenderer = $this->createMock(TemplateRenderer::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->pdfService = new PdfService($this->createMock(LoggerInterface::class), $this->templateRenderer);

	}//end setUp()

	/**
	 * The renderer under test, with the default-constructed leaf helpers.
	 *
	 * @return EmlEnvelopeRenderer
	 */
	private function renderer(): EmlEnvelopeRenderer {
		return new EmlEnvelopeRenderer($this->pdfService, $this->templateRenderer, $this->logger);

	}//end renderer()

	/**
	 * The bundled envelope template's source.
	 *
	 * @return string
	 */
	private static function envelopeTemplate(): string {
		return (string)file_get_contents(dirname(__DIR__, 3) . '/lib/Resources/templates/eml/email_envelope.twig');

	}//end envelopeTemplate()

	/**
	 * A redacted structure with headers, an optional body and inline images.
	 *
	 * @param object|null $body The body object.
	 * @param array<string, mixed> $inlineImages The inline-image map.
	 *
	 * @return object
	 */
	private static function structure(?object $body, array $inlineImages = []): object {
		return (object)[
			'headers' => [
				'from' => 'a@x.nl',
				'to' => ['b@x.nl', ' c@x.nl '],
				'subject' => 'Onderwerp [NAAM: 1]',
			],
			'body' => $body,
			'inlineImages' => $inlineImages,
		];

	}//end structure()

	/**
	 * Capture the data context of each render call and answer with fixed HTML.
	 *
	 * @param array<int, array<string, mixed>> $calls Receives the data of each call.
	 *
	 * @return void
	 */
	private function captureRender(array &$calls): void {
		$this->templateRenderer->expects($this->once())->method('renderTemplate')
			->with(self::envelopeTemplate(), $this->anything())
			->willReturnCallback(
				static function (string $templateContent, array $data) use (&$calls): string {
					$calls[] = $data;
					return '<p>envelope</p>';
				}
			);

	}//end captureRender()

	/**
	 * An HTML body is rendered in html mode, with every resolvable cid: ref
	 * replaced by a data URI of the sniffed MIME type and unresolved refs kept.
	 *
	 * @return void
	 */
	public function testRenderResolvesInlineImagesInAnHtmlBody(): void {
		$png = "\x89PNG-bytes";
		$jpeg = "\xFF\xD8\xFF-bytes";
		$gif = 'GIF89a-bytes';
		$webp = 'RIFF0000WEBPVP8 ';
		$html = '<img src="cid:png1"><img src=\'cid:jpg1\'><img SRC = "cid:gif1">'
			. '<img src="cid:webp1"><img src="cid:missing">';
		$result = self::structure(
			(object)['plain' => 'plain text', 'html' => $html],
			['png1' => $png, '<jpg1>' => $jpeg, 'gif1' => $gif, 'webp1' => $webp, 'bad' => 42]
		);

		$calls = [];
		$this->captureRender($calls);
		$this->logger->expects($this->once())->method('debug')->with(
			'[EmlEnvelopeRenderer] Unresolved inline cid reference',
			['contentId' => 'missing']
		);

		$out = $this->renderer()->render($result, []);

		$this->assertSame('<p>envelope</p>', $out);
		$this->assertCount(1, $calls);
		$data = $calls[0];
		$this->assertSame('html', $data['bodyMode']);
		$this->assertSame('plain text', $data['bodyPlain']);
		$this->assertFalse($data['templateFailed']);
		$this->assertSame('a@x.nl', $data['headers']['from']);
		$this->assertSame('b@x.nl, c@x.nl', $data['headers']['to']);
		$this->assertSame(
			'<img src="data:image/png;base64,' . base64_encode($png) . '">'
			. '<img src=\'data:image/jpeg;base64,' . base64_encode($jpeg) . '\'>'
			. '<img src="data:image/gif;base64,' . base64_encode($gif) . '">'
			. '<img src="data:image/webp;base64,' . base64_encode($webp) . '">'
			. '<img src="cid:missing">',
			$data['bodyHtml']
		);

	}//end testRenderResolvesInlineImagesInAnHtmlBody()

	/**
	 * A RIFF container that is not WEBP falls back to PNG; a bracketed cid matches a bare key.
	 *
	 * @return void
	 */
	public function testRenderDefaultsAnUnknownRiffToPng(): void {
		$riff = 'RIFF0000WAVEfmt ';
		$result = self::structure((object)['html' => '<img src="cid:<wav1>">'], ['wav1' => $riff]);

		$calls = [];
		$this->captureRender($calls);

		$this->renderer()->render($result, []);

		$this->assertSame('<img src="data:image/png;base64,' . base64_encode($riff) . '">', $calls[0]['bodyHtml']);

	}//end testRenderDefaultsAnUnknownRiffToPng()

	/**
	 * A blank HTML body with a plain body renders in plain mode, with no body HTML.
	 *
	 * @return void
	 */
	public function testRenderUsesPlainModeForAPlainBody(): void {
		$result = self::structure((object)['plain' => 'Hallo', 'html' => "  \n"]);

		$calls = [];
		$this->captureRender($calls);

		$this->renderer()->render($result, []);

		$this->assertSame('plain', $calls[0]['bodyMode']);
		$this->assertSame('', $calls[0]['bodyHtml']);
		$this->assertSame('Hallo', $calls[0]['bodyPlain']);

	}//end testRenderUsesPlainModeForAPlainBody()

	/**
	 * A structure without a body object renders in empty mode.
	 *
	 * @return void
	 */
	public function testRenderUsesEmptyModeWithoutABody(): void {
		$calls = [];
		$this->captureRender($calls);

		$this->renderer()->render(self::structure(null), []);

		$this->assertSame('empty', $calls[0]['bodyMode']);
		$this->assertSame('', $calls[0]['bodyHtml']);
		$this->assertSame('', $calls[0]['bodyPlain']);

	}//end testRenderUsesEmptyModeWithoutABody()

	/**
	 * A blank plain body is still empty mode.
	 *
	 * @return void
	 */
	public function testRenderUsesEmptyModeForABlankPlainBody(): void {
		$calls = [];
		$this->captureRender($calls);

		$this->renderer()->render(self::structure((object)['plain' => '   ']), []);

		$this->assertSame('empty', $calls[0]['bodyMode']);

	}//end testRenderUsesEmptyModeForABlankPlainBody()

	/**
	 * PDF/A options prefix the rendered envelope with the print stylesheet.
	 *
	 * @return void
	 */
	public function testRenderAppliesPrintCssForPdfA(): void {
		$calls = [];
		$this->captureRender($calls);

		$out = $this->renderer()->render(self::structure(null), ['pdfa' => true]);

		$this->assertSame($this->pdfService->buildPrintCss('A4', 'P') . '<p>envelope</p>', $out);

	}//end testRenderAppliesPrintCssForPdfA()

	/**
	 * A failed render is logged and retried once with the failed-template context.
	 *
	 * @return void
	 */
	public function testRenderRetriesWithTheFailedContext(): void {
		$calls = [];
		$this->templateRenderer->expects($this->exactly(2))->method('renderTemplate')->willReturnCallback(
			static function (string $templateContent, array $data) use (&$calls): string {
				$calls[] = $data;
				if (count($calls) === 1) {
					throw new RuntimeException('twig exploded');
				}

				return '<p>minimal</p>';
			}
		);
		$this->logger->expects($this->once())->method('warning')->with(
			'[EmlEnvelopeRenderer] Envelope template render failed; using minimal envelope',
			['exception' => RuntimeException::class, 'message' => 'twig exploded']
		);

		$out = $this->renderer()->render(self::structure((object)['plain' => 'Hallo']), []);

		$this->assertSame('<p>minimal</p>', $out);
		$this->assertFalse($calls[0]['templateFailed']);
		$this->assertTrue($calls[1]['templateFailed']);
		$this->assertSame('failed', $calls[1]['bodyMode']);

	}//end testRenderRetriesWithTheFailedContext()

	/**
	 * When the retry fails too, the escaped header-only fallback is emitted.
	 *
	 * @return void
	 */
	public function testRenderFallsBackToHeaderOnlyHtml(): void {
		$this->templateRenderer->expects($this->exactly(2))->method('renderTemplate')
			->willThrowException(new RuntimeException('still broken'));
		$result = (object)[
			'headers' => ['from' => '<evil>&"', 'subject' => "O'Brien"],
			'body' => (object)['plain' => 'SECRET BODY'],
		];

		$out = $this->renderer()->render($result, []);

		$this->assertSame(
			'<div style="font-family:DejaVu Sans,sans-serif;font-size:10pt;"><p>'
			. 'Van: &lt;evil&gt;&amp;&quot;<br>Aan: <br>Onderwerp: O&#039;Brien<br>Datum: '
			. '</p><p style="color:#b00;font-style:italic;">(template rendering failed)</p></div>',
			$out
		);
		$this->assertStringNotContainsString('SECRET BODY', $out);

	}//end testRenderFallsBackToHeaderOnlyHtml()

	/**
	 * The minimal envelope adds a page when asked and writes the header-only HTML.
	 *
	 * @return void
	 */
	public function testWriteMinimalAddsAPageAndWritesHeaders(): void {
		$mpdf = $this->createMock(Mpdf::class);
		$mpdf->expects($this->once())->method('AddPage');
		$mpdf->expects($this->once())->method('WriteHTML')->with(
			$this->logicalAnd(
				$this->stringStartsWith('<div style="font-family:DejaVu Sans,sans-serif;font-size:10pt;"><p>Van: a@x.nl<br>'),
				$this->stringContains('Onderwerp: Onderwerp [NAAM: 1]'),
				$this->stringContains('(template rendering failed)')
			)
		);
		$this->logger->expects($this->never())->method('error');

		$this->renderer()->writeMinimal($mpdf, self::structure(null), [], true);

	}//end testWriteMinimalAddsAPageAndWritesHeaders()

	/**
	 * Without addPage no page is added, and PDF/A print CSS is prefixed.
	 *
	 * @return void
	 */
	public function testWriteMinimalWithoutAPageAppliesPrintCss(): void {
		$mpdf = $this->createMock(Mpdf::class);
		$mpdf->expects($this->never())->method('AddPage');
		$mpdf->expects($this->once())->method('WriteHTML')->with(
			$this->stringStartsWith($this->pdfService->buildPrintCss('A4', 'P') . '<div')
		);

		$this->renderer()->writeMinimal($mpdf, self::structure(null), ['pdfa' => true], false);

	}//end testWriteMinimalWithoutAPageAppliesPrintCss()

	/**
	 * A failing mPDF write is logged, not thrown.
	 *
	 * @return void
	 */
	public function testWriteMinimalLogsAWriteFailure(): void {
		$mpdf = $this->createMock(Mpdf::class);
		$mpdf->method('WriteHTML')->willThrowException(new RuntimeException('mpdf broke'));
		$this->logger->expects($this->once())->method('error')->with(
			'[EmlEnvelopeRenderer] Minimal envelope WriteHTML failed',
			['message' => 'mpdf broke']
		);

		$this->renderer()->writeMinimal($mpdf, self::structure(null), [], false);

	}//end testWriteMinimalLogsAWriteFailure()

	/**
	 * The subject is the redacted subject header, or '' when there is none.
	 *
	 * @return void
	 */
	public function testSubjectOfReadsTheRedactedSubject(): void {
		$renderer = $this->renderer();

		$this->assertSame('Onderwerp [NAAM: 1]', $renderer->subjectOf(self::structure(null)));
		$this->assertSame('', $renderer->subjectOf((object)['headers' => 'not-an-array']));

	}//end testSubjectOfReadsTheRedactedSubject()
}//end class
