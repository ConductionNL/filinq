<?php

/**
 * Unit tests for style edits applied through PackageCodec.
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
use PHPUnit\Framework\TestCase;
use RuntimeException;
use ZipArchive;

/**
 * A style edit restyles the block and, for ODF, adds the automatic style it points at.
 *
 * @covers \OCA\Filinq\Service\Editing\PackageCodec
 * @uses   \OCA\Filinq\Service\Editing\XmlBlockScanner
 * @uses   \OCA\Filinq\Service\Editing\BlockStyleCodec
 * @uses   \OCA\Filinq\Service\Editing\OdfBlockStyleCodec
 * @uses   \OCA\Filinq\Service\Editing\OoxmlBlockStyleCodec
 * @uses   \OCA\Filinq\Service\Editing\PackagePartIo
 */
class PackageCodecStyleEditTest extends TestCase {

	/**
	 * Temporary files to remove.
	 *
	 * @var string[]
	 */
	private array $spilled = [];

	/**
	 * Remove temporary files.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		foreach ($this->spilled as $path) {
			if (is_file($path) === true) {
				unlink($path);
			}
		}

		$this->spilled = [];
		parent::tearDown();
	}//end tearDown()

	/**
	 * Build a zip package.
	 *
	 * @param array<string, string> $entries Entry name => body.
	 *
	 * @return string
	 */
	private function zip(array $entries): string {
		$path = tempnam(sys_get_temp_dir(), 'filinq-style-');
		$this->spilled[] = $path;
		unlink($path);

		$zip = new ZipArchive();
		$zip->open($path, ZipArchive::CREATE);
		foreach ($entries as $name => $body) {
			$zip->addFromString($name, $body);
		}

		$zip->close();
		return (string) file_get_contents($path);
	}//end zip()

	/**
	 * Read one entry from a package.
	 *
	 * @param string $bytes The package.
	 * @param string $entry The entry name.
	 *
	 * @return string
	 */
	private function entry(string $bytes, string $entry): string {
		$path = tempnam(sys_get_temp_dir(), 'filinq-style-');
		$this->spilled[] = $path;
		file_put_contents($path, $bytes);

		$zip = new ZipArchive();
		$zip->open($path);
		$body = (string) $zip->getFromName($entry);
		$zip->close();
		return $body;
	}//end entry()

	/**
	 * An .odt package whose content.xml has the given head before the body.
	 *
	 * @param string $head Markup between the root and the body.
	 *
	 * @return string
	 */
	private function odt(string $head): string {
		$content = '<?xml version="1.0" encoding="UTF-8"?>'
			. '<office:document-content xmlns:office="urn:office" xmlns:text="urn:text" xmlns:style="urn:style" xmlns:fo="urn:fo">'
			. $head
			. '<office:body><office:text>'
			. '<text:p text:style-name="Standard">Eerste</text:p>'
			. '<text:p>Tweede</text:p>'
			. '</office:text></office:body></office:document-content>';

		return $this->zip(['mimetype' => 'application/vnd.oasis.opendocument.text', 'content.xml' => $content]);
	}//end odt()

	/**
	 * Apply one style edit to the second block.
	 *
	 * @param string               $bytes     The package.
	 * @param string               $extension The extension.
	 * @param array<string, mixed> $style     The style.
	 *
	 * @return string The new package bytes.
	 */
	private function styleSecondBlock(string $bytes, string $extension, array $style): string {
		$codec = new PackageCodec();
		$anchor = $codec->readBlocks(packageBytes: $bytes, extension: $extension)['blocks'][1]['anchor'];

		$result = $codec->applyEdits(packageBytes: $bytes, extension: $extension, edits: [['anchor' => $anchor, 'action' => 'style', 'style' => $style]]);

		$this->assertSame([$anchor], $result['applied']);
		return $result['bytes'];
	}//end styleSecondBlock()

	/**
	 * The new automatic style joins an existing automatic-styles section, named past the existing ones.
	 *
	 * @return void
	 */
	public function testOdfStyleJoinsExistingAutomaticStyles(): void {
		$head = '<office:automatic-styles><style:style style:name="P1" style:family="paragraph"/></office:automatic-styles>';

		$content = $this->entry($this->styleSecondBlock(bytes: $this->odt(head: $head), extension: 'odt', style: ['bold' => true]), 'content.xml');

		$this->assertStringContainsString(
			'<office:automatic-styles><style:style style:name="P1" style:family="paragraph"/>'
			. '<style:style style:name="DdAgent2" style:family="paragraph"><style:text-properties fo:font-weight="bold"/></style:style>'
			. '</office:automatic-styles>',
			$content
		);
		$this->assertStringContainsString('<text:p text:style-name="DdAgent2">Tweede</text:p>', $content);
		$this->assertStringContainsString('<text:p text:style-name="Standard">Eerste</text:p>', $content);
		$this->assertNotFalse(simplexml_load_string($content));
	}//end testOdfStyleJoinsExistingAutomaticStyles()

	/**
	 * Without an automatic-styles section one is minted right before the body.
	 *
	 * @return void
	 */
	public function testOdfStyleMintsAutomaticStylesBeforeTheBody(): void {
		$content = $this->entry($this->styleSecondBlock(bytes: $this->odt(head: ''), extension: 'odt', style: ['alignment' => 'center']), 'content.xml');

		$this->assertStringContainsString(
			'<office:automatic-styles><style:style style:name="DdAgent1" style:family="paragraph">'
			. '<style:paragraph-properties fo:text-align="center"/></style:style></office:automatic-styles><office:body>',
			$content
		);
		$this->assertStringContainsString('<text:p text:style-name="DdAgent1">Tweede</text:p>', $content);
	}//end testOdfStyleMintsAutomaticStylesBeforeTheBody()

	/**
	 * A heading-only ODF style needs no automatic style: the block becomes a heading.
	 *
	 * @return void
	 */
	public function testOdfHeadingNeedsNoAutomaticStyle(): void {
		$content = $this->entry($this->styleSecondBlock(bytes: $this->odt(head: ''), extension: 'odt', style: ['heading' => 2]), 'content.xml');

		$this->assertStringContainsString('<text:h text:outline-level="2">Tweede</text:h>', $content);
		$this->assertStringNotContainsString('automatic-styles', $content);
	}//end testOdfHeadingNeedsNoAutomaticStyle()

	/**
	 * In a .docx the style lands in the paragraph's own properties.
	 *
	 * @return void
	 */
	public function testOoxmlStyleIsWrittenIntoTheParagraph(): void {
		$document = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
			. '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>'
			. '<w:p><w:r><w:t>Een</w:t></w:r></w:p>'
			. '<w:p><w:r><w:t>Twee</w:t></w:r></w:p>'
			. '</w:body></w:document>';
		$bytes = $this->zip(['[Content_Types].xml' => '<Types/>', 'word/document.xml' => $document]);

		$xml = $this->entry($this->styleSecondBlock(bytes: $bytes, extension: 'docx', style: ['alignment' => 'right']), 'word/document.xml');

		$this->assertStringContainsString('<w:p><w:r><w:t>Een</w:t></w:r></w:p>', $xml);
		$this->assertStringContainsString('<w:jc w:val="right"/>', $xml);
		$this->assertStringNotContainsString('DdAgent', $xml);
		$rewritten = $this->zip(['[Content_Types].xml' => '<Types/>', 'word/document.xml' => $xml]);
		$blocks = (new PackageCodec())->readBlocks(packageBytes: $rewritten, extension: 'docx')['blocks'];
		$this->assertSame(['Een', 'Twee'], array_column($blocks, 'text'));
	}//end testOoxmlStyleIsWrittenIntoTheParagraph()

	/**
	 * A style edit without any style is refused.
	 *
	 * @return void
	 */
	public function testStyleEditWithoutStyleIsRefused(): void {
		$bytes = $this->odt(head: '');
		$codec = new PackageCodec();
		$anchor = $codec->readBlocks(packageBytes: $bytes, extension: 'odt')['blocks'][0]['anchor'];

		$this->expectException(RuntimeException::class);

		$codec->applyEdits(packageBytes: $bytes, extension: 'odt', edits: [['anchor' => $anchor, 'action' => 'style', 'style' => []]]);
	}//end testStyleEditWithoutStyleIsRefused()
}//end class
