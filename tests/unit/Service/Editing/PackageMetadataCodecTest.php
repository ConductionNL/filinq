<?php

/**
 * Unit tests for PackageMetadataCodec.
 *
 * openspec/changes/document-rich-editing.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 *
 * @category Test
 * @package  OCA\Filinq\Tests\Unit\Service\Editing
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git-id>
 *
 * @link https://filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Editing;

use OCA\Filinq\Service\Editing\PackageMetadataCodec;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use ZipArchive;

/**
 * Metadata read/write over real packages built on the fly.
 */
class PackageMetadataCodecTest extends TestCase {

	/**
	 * The codec under test.
	 *
	 * @var PackageMetadataCodec
	 */
	private PackageMetadataCodec $codec;

	/**
	 * Set up.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->codec = new PackageMetadataCodec();
	}//end setUp()

	/**
	 * Build a package from a map of entry name => contents.
	 *
	 * @param array<string, string> $entries The entries.
	 *
	 * @return string The package bytes.
	 */
	private function package(array $entries): string {
		$path = tempnam(sys_get_temp_dir(), 'mdtest') . '.zip';

		$zip = new ZipArchive();
		$zip->open($path, ZipArchive::CREATE);
		foreach ($entries as $name => $contents) {
			$zip->addFromString($name, $contents);
		}

		$zip->close();

		$bytes = (string)file_get_contents($path);
		unlink($path);

		return $bytes;
	}//end package()

	/**
	 * Read one entry from a package.
	 *
	 * @param string $bytes The package bytes.
	 * @param string $name The entry name.
	 *
	 * @return string|false The entry contents.
	 */
	private function entry(string $bytes, string $name) {
		$path = tempnam(sys_get_temp_dir(), 'mdread') . '.zip';
		file_put_contents($path, $bytes);

		$zip = new ZipArchive();
		$zip->open($path);
		$contents = $zip->getFromName($name);
		$zip->close();
		unlink($path);

		return $contents;
	}//end entry()

	/**
	 * REQ: existing OOXML metadata is read by format-neutral field name.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/document-rich-editing/spec.md
	 */
	public function testReadsOoxmlMetadata(): void {
		$bytes = $this->package(
			[
				'word/document.xml' => '<w:document/>',
				'docProps/core.xml' => '<?xml version="1.0"?><cp:coreProperties '
					. 'xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" '
					. 'xmlns:dc="http://purl.org/dc/elements/1.1/">'
					. '<dc:title>Subsidiebesluit</dc:title><cp:keywords>subsidie, besluit</cp:keywords>'
					. '</cp:coreProperties>',
			]
		);

		$meta = $this->codec->readMetadata($bytes, 'docx');

		$this->assertSame('Subsidiebesluit', $meta['title']);
		$this->assertSame('subsidie, besluit', $meta['keywords']);

		// An absent field is reported as empty, not omitted: a caller must be able
		// to tell "this document has no subject" from "I forgot to ask".
		$this->assertArrayHasKey('subject', $meta);
		$this->assertSame('', $meta['subject']);
	}//end testReadsOoxmlMetadata()

	/**
	 * REQ: keywords resolve to the right element per format.
	 *
	 * OOXML calls it `cp:keywords`, ODF calls it `meta:keyword`. A caller naming
	 * the element rather than the field would be writing per-format code, which is
	 * exactly what ADR-087 §2 exists to prevent.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/document-rich-editing/spec.md
	 */
	public function testKeywordsResolveToTheFormatsOwnElement(): void {
		$odt = $this->package(
			[
				'mimetype' => 'application/vnd.oasis.opendocument.text',
				'content.xml' => '<office:document-content/>',
			]
		);

		$written = $this->codec->writeMetadata($odt, 'odt', ['keywords' => 'zaak']);
		$meta = (string)$this->entry($written['bytes'], 'meta.xml');

		$this->assertStringContainsString('<meta:keyword>zaak</meta:keyword>', $meta);
		$this->assertStringNotContainsString('cp:keywords', $meta);
		$this->assertSame('zaak', $this->codec->readMetadata($written['bytes'], 'odt')['keywords']);
	}//end testKeywordsResolveToTheFormatsOwnElement()

	/**
	 * REQ: writing metadata leaves every other part byte-identical.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/document-rich-editing/spec.md
	 */
	public function testWritingMetadataLeavesOtherPartsUntouched(): void {
		$body = '<w:document><w:body><w:p><w:r><w:t>Hello</w:t></w:r></w:p></w:body></w:document>';
		$bytes = $this->package(['word/document.xml' => $body, 'docProps/core.xml' => '<cp:coreProperties/>']);

		$written = $this->codec->writeMetadata($bytes, 'docx', ['title' => 'New title']);

		$this->assertSame(
			$body,
			$this->entry($written['bytes'], 'word/document.xml'),
			'the document body must be byte-identical after a metadata write'
		);
	}//end testWritingMetadataLeavesOtherPartsUntouched()

	/**
	 * REQ: a document with no metadata part gets a well-formed one.
	 *
	 * Ordinary rather than exceptional — PhpWord-generated ODF often has none.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/document-rich-editing/spec.md
	 */
	public function testCreatesAMetadataPartWhenAbsent(): void {
		$bytes = $this->package(['word/document.xml' => '<w:document/>']);

		$written = $this->codec->writeMetadata($bytes, 'docx', ['title' => 'Fresh']);
		$meta = (string)$this->entry($written['bytes'], 'docProps/core.xml');

		$this->assertStringContainsString('<dc:title>Fresh</dc:title>', $meta);
		// Without the namespace declarations the elements resolve to nothing and
		// the suite silently ignores them.
		$this->assertStringContainsString('xmlns:dc=', $meta);
		$this->assertSame('Fresh', $this->codec->readMetadata($written['bytes'], 'docx')['title']);
	}//end testCreatesAMetadataPartWhenAbsent()

	/**
	 * REQ: a field not named is left alone.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/document-rich-editing/spec.md
	 */
	public function testUnnamedFieldsSurvive(): void {
		$bytes = $this->package(
			[
				'word/document.xml' => '<w:document/>',
				'docProps/core.xml' => '<cp:coreProperties '
					. 'xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" '
					. 'xmlns:dc="http://purl.org/dc/elements/1.1/">'
					. '<dc:title>Keep me</dc:title><dc:creator>Ruben</dc:creator></cp:coreProperties>',
			]
		);

		$written = $this->codec->writeMetadata($bytes, 'docx', ['subject' => 'Subsidie']);
		$meta = $this->codec->readMetadata($written['bytes'], 'docx');

		$this->assertSame('Keep me', $meta['title']);
		$this->assertSame('Ruben', $meta['creator']);
		$this->assertSame('Subsidie', $meta['subject']);
	}//end testUnnamedFieldsSurvive()

	/**
	 * REQ: values are XML-escaped.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/document-rich-editing/spec.md
	 */
	public function testValuesAreEscapedAndRoundTrip(): void {
		$bytes = $this->package(['word/document.xml' => '<w:document/>']);
		$value = 'Zaak <A & B> "spoed"';

		$written = $this->codec->writeMetadata($bytes, 'docx', ['title' => $value]);

		$this->assertStringNotContainsString('<A &', (string)$this->entry($written['bytes'], 'docProps/core.xml'));
		$this->assertSame($value, $this->codec->readMetadata($written['bytes'], 'docx')['title']);
	}//end testValuesAreEscapedAndRoundTrip()

	/**
	 * REQ: an unknown field is refused by name, listing what is supported.
	 *
	 * The caller is a language model. A silently-ignored misspelling would report
	 * success and change nothing.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/document-rich-editing/spec.md
	 */
	public function testUnknownFieldIsRefusedByName(): void {
		$bytes = $this->package(['word/document.xml' => '<w:document/>']);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessageMatches('/Unknown metadata field "author".*title, subject, creator/s');

		$this->codec->writeMetadata($bytes, 'docx', ['author' => 'Ruben']);
	}//end testUnknownFieldIsRefusedByName()

	/**
	 * REQ: an unsupported format is refused by name and names what works.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/document-rich-editing/spec.md
	 */
	public function testUnsupportedFormatIsRefusedByName(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessageMatches('/"xlsx".*docx, odt/s');

		$this->codec->readMetadata('irrelevant', 'xlsx');
	}//end testUnsupportedFormatIsRefusedByName()

	/**
	 * REQ: created/modified timestamps are NOT writable.
	 *
	 * They record what happened to the document. Letting an agent set them would
	 * turn a fact into a claim.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/document-rich-editing/spec.md
	 */
	public function testTimestampsAreNotWritable(): void {
		$this->assertNotContains('created', $this->codec->fields());
		$this->assertNotContains('modified', $this->codec->fields());

		$bytes = $this->package(['word/document.xml' => '<w:document/>']);

		$this->expectException(RuntimeException::class);
		$this->codec->writeMetadata($bytes, 'docx', ['created' => '2020-01-01T00:00:00Z']);
	}//end testTimestampsAreNotWritable()
}//end class
