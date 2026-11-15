<?php

/**
 * Accessible PDF output: the HTML goes to LibreOffice's tagged export
 * with a language and a title, the language is resolved option, template,
 * instance in that order, nothing is made without one, and a PDF that
 * comes back without tags, language or title is refused rather than
 * passed off as accessible. PdfService routes `accessible: true` here and
 * never to mPDF.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service\Conversion
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-pdfua-accessible-output/tasks.md#task-1.2
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Conversion;

use OCA\Filinq\Exception\ConversionFailedException;
use OCA\Filinq\Service\Conversion\AccessiblePdfRenderer;
use OCA\Filinq\Service\Conversion\LibreOfficeHeadlessBackend;
use OCA\Filinq\Service\PdfService;
use OCA\Filinq\Service\TemplateRenderer;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\Lock\ILockingProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class AccessiblePdfRendererTest extends TestCase {

	private const FIXTURES = __DIR__ . '/../../../sample-documents/pdfua/';

	private DiskLikeSofficeRunner $runner;

	/**
	 * A renderer whose soffice emits the given fixture.
	 *
	 * @param string $fixture         The fixture soffice "produces".
	 * @param string $defaultLanguage The instance default_language.
	 * @param bool   $soffice         Whether soffice is there.
	 *
	 * @return AccessiblePdfRenderer
	 */
	private function renderer(string $fixture = 'tagged-pdfua.pdf', string $defaultLanguage = 'nl', bool $soffice = true): AccessiblePdfRenderer {
		$this->runner = new DiskLikeSofficeRunner((string) file_get_contents(self::FIXTURES . $fixture));
		$appConfig = $this->createMock(IAppConfig::class);
		$appConfig->method('getValueString')->willReturnCallback(
			static fn (string $app, string $key, string $default = '') => match ($key) {
				'filinq.conversion.libreoffice_binary_path' => '/bin/sh',
				'filinq.conversion.backends.libreoffice_enabled' => ($soffice === true ? 'true' : 'false'),
				default => $default,
			}
		);
		$backend = new LibreOfficeHeadlessBackend(
			appConfig: $appConfig,
			lockingProvider: $this->createMock(ILockingProvider::class),
			logger: $this->createMock(LoggerInterface::class),
			processRunner: $this->runner,
		);
		$config = $this->createMock(IConfig::class);
		$config->method('getSystemValueString')->willReturnCallback(
			static fn (string $key, string $default = '') => ($key === 'default_language' ? $defaultLanguage : $default)
		);

		return new AccessiblePdfRenderer(libreOffice: $backend, config: $config);
	}//end renderer()

	public function testTheHtmlGoesToTheTaggedExportWithLanguageAndTitle(): void {
		$pdf = $this->renderer()->render(html: '<h1>Besluit</h1><img src="x.png" alt="Wapen">', options: ['title' => 'Besluit parkeervergunning', 'lang' => 'nl-NL']);

		$this->assertStringStartsWith('%PDF', $pdf);
		$html = $this->runner->inputs[0];
		$this->assertStringContainsString('<html lang="nl-NL">', $html);
		$this->assertStringContainsString('<title>Besluit parkeervergunning</title>', $html);
		$this->assertStringContainsString('<h1>Besluit</h1><img src="x.png" alt="Wapen">', $html, 'the structure is passed on, not flattened');
		$this->assertStringContainsString('PDFUACompliance', implode(' ', $this->runner->runs[0]));
	}//end testTheHtmlGoesToTheTaggedExportWithLanguageAndTitle()

	public function testTheLanguageComesFromTheOptionThenTheTemplateThenTheInstance(): void {
		$this->renderer(defaultLanguage: 'de')->render(html: '<p>x</p>', options: ['title' => 'T', 'templateLanguage' => 'fr']);
		$this->assertStringContainsString('<html lang="fr">', $this->runner->inputs[0]);

		$this->renderer(defaultLanguage: 'de')->render(html: '<p>x</p>', options: ['title' => 'T']);
		$this->assertStringContainsString('<html lang="de">', $this->runner->inputs[0]);

		$this->renderer(defaultLanguage: 'de')->render(html: '<p>x</p>', options: ['title' => 'T', 'lang' => 'en', 'templateLanguage' => 'fr']);
		$this->assertStringContainsString('<html lang="en">', $this->runner->inputs[0]);
	}//end testTheLanguageComesFromTheOptionThenTheTemplateThenTheInstance()

	public function testNoResolvableLanguageMakesNothing(): void {
		$renderer = $this->renderer(defaultLanguage: '');
		try {
			$renderer->render(html: '<p>x</p>', options: ['title' => 'T']);
			$this->fail('must refuse without a language');
		} catch (ConversionFailedException $e) {
			$this->assertStringContainsString('language', $e->getMessage());
			$this->assertSame(422, $e->getCode());
		}

		$this->assertSame([], $this->runner->runs);
	}//end testNoResolvableLanguageMakesNothing()

	public function testTheTitleFallsBackToTheTemplateName(): void {
		$this->renderer()->render(html: '<p>x</p>', options: ['templateName' => 'Besluit & brief']);

		$this->assertStringContainsString('<title>Besluit &amp; brief</title>', $this->runner->inputs[0]);
	}//end testTheTitleFallsBackToTheTemplateName()

	public function testAFullDocumentKeepsItsHeadAndGetsLanguageAndTitle(): void {
		$this->renderer()->render(html: '<html lang="en"><head><style>h1{color:red}</style></head><body><h1>x</h1></body></html>', options: ['title' => 'T', 'lang' => 'nl']);

		$html = $this->runner->inputs[0];
		$this->assertStringContainsString('<html lang="nl">', $html);
		$this->assertStringNotContainsString('lang="en"', $html);
		$this->assertStringContainsString('<style>h1{color:red}</style>', $html);
		$this->assertStringContainsString('<title>T</title>', $html);
	}//end testAFullDocumentKeepsItsHeadAndGetsLanguageAndTitle()

	public function testAnUntaggedResultIsRefusedNotPassedOffAsAccessible(): void {
		$this->expectException(ConversionFailedException::class);
		$this->expectExceptionMessageMatches('/tags/');

		$this->renderer(fixture: 'untagged-mpdf.pdf')->render(html: '<p>x</p>', options: ['title' => 'T']);
	}//end testAnUntaggedResultIsRefusedNotPassedOffAsAccessible()

	public function testAResultWithoutLanguageIsRefused(): void {
		$this->expectException(ConversionFailedException::class);
		$this->expectExceptionMessageMatches('/language/');

		$this->renderer(fixture: 'tagged-no-lang.pdf')->render(html: '<p>x</p>', options: ['title' => 'T']);
	}//end testAResultWithoutLanguageIsRefused()

	public function testAnInvalidLanguageTagIsRefused(): void {
		$this->expectException(ConversionFailedException::class);

		$this->renderer()->render(html: '<p>x</p>', options: ['title' => 'T', 'lang' => 'nl" onload="x']);
	}//end testAnInvalidLanguageTagIsRefused()

	public function testPdfServiceSendsAnAccessibleRequestHereAndNeverToMpdf(): void {
		$logger = $this->createMock(LoggerInterface::class);
		$templates = $this->createMock(TemplateRenderer::class);
		$templates->method('renderTemplate')->willReturn('<h1>Besluit</h1>');

		$pdf = (new PdfService($logger, $templates, $this->renderer()))->renderPdf('{{ x }}', [], ['accessible' => true, 'title' => 'Besluit']);
		$this->assertCount(1, $this->runner->runs);
		$this->assertStringContainsString('/StructTreeRoot', $pdf);

		$this->renderer(soffice: false);
		try {
			(new PdfService($logger, $templates, $this->renderer(soffice: false)))->renderPdf('{{ x }}', [], ['accessible' => true, 'title' => 'Besluit']);
			$this->fail('no soffice: must fail, not fall back to mPDF');
		} catch (ConversionFailedException $e) {
			$this->assertSame('libreoffice_headless', $e->getAttempts()[0]['name']);
		}

		$this->expectException(ConversionFailedException::class);
		(new PdfService($logger, $templates))->renderPdf('{{ x }}', [], ['accessible' => true, 'title' => 'Besluit']);
	}//end testPdfServiceSendsAnAccessibleRequestHereAndNeverToMpdf()
}//end class
