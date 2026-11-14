<?php

/**
 * Unit tests for PackageCodec giving text to paragraphs that carry none.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Editing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Editing;

use OCA\Filinq\Service\Editing\PackageCodec;
use OCA\Filinq\Service\Editing\PackagePartIo;
use PHPUnit\Framework\TestCase;
use ZipArchive;

/**
 * A properties-only paragraph accepts new text.
 *
 * @covers \OCA\Filinq\Service\Editing\PackageCodec
 * @uses   \OCA\Filinq\Service\Editing\PackagePartIo
 * @uses   \OCA\Filinq\Service\Editing\XmlBlockScanner
 * @uses   \OCA\Filinq\Service\Editing\BlockStyleCodec
 * @uses   \OCA\Filinq\Service\Editing\OdfBlockStyleCodec
 * @uses   \OCA\Filinq\Service\Editing\OoxmlBlockStyleCodec
 */
class PackageCodecEmptyRunTest extends TestCase {

	/**
	 * A docx whose only paragraph carries no text.
	 *
	 * @return string The package bytes.
	 */
	private function docx(): string {
		$document = '<w:document xmlns:w="urn:w"><w:body>'
			. '<w:p><w:pPr><w:pStyle w:val="Heading1"/></w:pPr></w:p>'
			. '</w:body></w:document>';

		$path = tempnam(sys_get_temp_dir(), 'pkgempty');
		$zip = new ZipArchive();
		$zip->open($path, ZipArchive::OVERWRITE);
		$zip->addFromString('[Content_Types].xml', '<Types/>');
		$zip->addFromString('word/document.xml', $document);
		$zip->close();
		$bytes = (string)file_get_contents($path);
		unlink($path);

		return $bytes;
	}//end docx()

	/**
	 * Replace one block's text and return the rewritten document part.
	 *
	 * @param int    $index The block index.
	 * @param string $text  The new text.
	 *
	 * @return string The document.xml after the edit.
	 */
	private function replace(int $index, string $text): string {
		$codec = new PackageCodec();
		$before = $this->docx();
		$anchor = $codec->readBlocks($before, 'docx')['blocks'][$index]['anchor'];

		$after = $codec->applyEdits($before, 'docx', [['anchor' => $anchor, 'action' => 'replace', 'text' => $text]])['bytes'];

		return (new PackagePartIo())->readPart(packageBytes: $after, part: 'word/document.xml');
	}//end replace()

	/**
	 * A paragraph with properties but no run gets a run before its close tag, keeping its style.
	 *
	 * @return void
	 */
	public function testAPropertiesOnlyParagraphGetsARun(): void {
		$this->assertStringContainsString(
			'<w:p><w:pPr><w:pStyle w:val="Heading1"/></w:pPr><w:r><w:t xml:space="preserve">Scope &amp; aim</w:t></w:r></w:p>',
			$this->replace(index: 0, text: 'Scope & aim')
		);
	}//end testAPropertiesOnlyParagraphGetsARun()
}//end class
