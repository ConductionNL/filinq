<?php

/**
 * Unit tests for PhpWordHtmlRenderer
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

use OCA\Filinq\Service\PhpWordHtmlRenderer;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Word-family bytes become HTML through the reader the extension or MIME
 * type names, and the temp file never outlives the call.
 */
#[CoversClass(PhpWordHtmlRenderer::class)]
class PhpWordHtmlRendererTest extends TestCase {

	/**
	 * A minimal RTF document.
	 */
	private const RTF = "{\\rtf1\\ansi Hello Filinq world\\par}";

	/**
	 * The subject.
	 *
	 * @var PhpWordHtmlRenderer
	 */
	private PhpWordHtmlRenderer $renderer;

	/**
	 * Build the subject.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->renderer = new PhpWordHtmlRenderer();
	}//end setUp()

	/**
	 * The temp files this renderer creates.
	 *
	 * @return list<string>
	 */
	private function tempFiles(): array {
		return (glob(sys_get_temp_dir() . '/eml_word_*') ?: []);
	}//end tempFiles()

	/**
	 * The filename extension picks the reader even when the MIME type is unrelated.
	 *
	 * @return void
	 */
	public function testTheExtensionPicksTheReader(): void {
		$before = $this->tempFiles();

		$html = $this->renderer->renderToHtml(bytes: self::RTF, mimeType: 'application/octet-stream', filename: 'Letter.RTF');

		$this->assertStringContainsString('Hello Filinq world', $html);
		$this->assertStringContainsString('<html>', $html);
		$this->assertSame($before, $this->tempFiles());
	}//end testTheExtensionPicksTheReader()

	/**
	 * Without a usable extension the MIME type picks the reader.
	 *
	 * @return void
	 */
	public function testTheMimeTypePicksTheReaderWithoutAnExtension(): void {
		$phpWord = new PhpWord();
		$phpWord->addSection()->addText('Docx body text');
		$path = tempnam(sys_get_temp_dir(), 'filinq_test_docx_');
		IOFactory::createWriter($phpWord, 'Word2007')->save($path);
		$bytes = (string)file_get_contents($path);
		unlink($path);

		$html = $this->renderer->renderToHtml(
			bytes: $bytes,
			mimeType: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			filename: 'attachment'
		);

		$this->assertStringContainsString('Docx body text', $html);
		$this->assertStringNotContainsString('@page', $html);
	}//end testTheMimeTypePicksTheReaderWithoutAnExtension()

	/**
	 * An unknown extension falls back to the MIME type (text/rtf).
	 *
	 * @return void
	 */
	public function testAnUnknownExtensionFallsBackToTheMimeType(): void {
		$html = $this->renderer->renderToHtml(bytes: self::RTF, mimeType: 'text/rtf', filename: 'note.bin');

		$this->assertStringContainsString('Hello Filinq world', $html);
	}//end testAnUnknownExtensionFallsBackToTheMimeType()

	/**
	 * Neither extension nor MIME type naming a reader is refused, and no temp file is made.
	 *
	 * @return void
	 */
	public function testNoReaderIsRefused(): void {
		$before = $this->tempFiles();

		try {
			$this->renderer->renderToHtml(bytes: 'x', mimeType: 'application/pdf', filename: 'file.pdf');
			$this->fail('Expected a RuntimeException');
		} catch (RuntimeException $e) {
			$this->assertSame('No PhpWord reader for Word MIME application/pdf', $e->getMessage());
		}

		$this->assertSame($before, $this->tempFiles());
	}//end testNoReaderIsRefused()

	/**
	 * A reader failure propagates and still removes the temp file.
	 *
	 * @return void
	 */
	public function testAReadFailureStillRemovesTheTempFile(): void {
		$before = $this->tempFiles();

		try {
			$this->renderer->renderToHtml(bytes: 'this is not a zip', mimeType: '', filename: 'broken.docx');
			$this->fail('Expected the reader to fail');
		} catch (\Throwable $e) {
			$this->assertNotSame('', $e->getMessage());
		}

		$this->assertSame($before, $this->tempFiles());
	}//end testAReadFailureStillRemovesTheTempFile()
}//end class
