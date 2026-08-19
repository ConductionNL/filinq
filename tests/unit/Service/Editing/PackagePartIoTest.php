<?php

/**
 * Unit tests for PackagePartIo — reading and writing parts of a ZIP package.
 *
 * 🔴 The property these tests exist for: writing one part leaves every OTHER
 * part byte-identical. An office document is a ZIP of many parts, and an editor
 * that rewrites the archive instead of replacing one entry silently drops the
 * styles, the relationships or the embedded media — the file still opens, and
 * the damage shows up as "the formatting is gone" rather than as an error.
 *
 * @category Test
 * @package  OCA\DocuDesk\Tests\Unit\Service\Editing
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.1
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Tests\Unit\Service\Editing;

use OCA\DocuDesk\Service\Editing\PackagePartIo;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use ZipArchive;

/**
 * Tests for package part IO.
 *
 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.1
 */
class PackagePartIoTest extends TestCase {
	/**
	 * The IO under test.
	 *
	 * @var PackagePartIo
	 */
	private PackagePartIo $io;

	/**
	 * Build the IO.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		if (class_exists(ZipArchive::class) === false) {
			$this->markTestSkipped('ext-zip is required to build a package fixture.');
		}

		$this->io = new PackagePartIo();
	}//end setUp()

	/**
	 * A minimal package carrying three parts.
	 *
	 * Built with ZipArchive rather than checked in as a binary, so the fixture
	 * is readable and its contents are asserted rather than assumed.
	 *
	 * @return string The package bytes.
	 */
	private function package(): string {
		$path = tempnam(sys_get_temp_dir(), 'ddpkg');
		$zip = new ZipArchive();
		$zip->open($path, ZipArchive::OVERWRITE);
		$zip->addFromString('mimetype', 'application/vnd.oasis.opendocument.text');
		$zip->addFromString('content.xml', '<office:document-content>hello</office:document-content>');
		$zip->addFromString('styles.xml', '<office:document-styles>styling</office:document-styles>');
		$zip->close();

		$bytes = (string)file_get_contents($path);
		unlink($path);

		return $bytes;
	}//end package()

	/**
	 * Every part in the package is listed.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.1
	 */
	public function testEveryPartIsListed(): void {
		$parts = $this->io->listParts(packageBytes: $this->package());

		$this->assertContains('mimetype', $parts);
		$this->assertContains('content.xml', $parts);
		$this->assertContains('styles.xml', $parts);
	}//end testEveryPartIsListed()

	/**
	 * A part reads back exactly as it was written.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.1
	 */
	public function testAPartReadsBackVerbatim(): void {
		$xml = $this->io->readPart(packageBytes: $this->package(), part: 'content.xml');

		$this->assertSame('<office:document-content>hello</office:document-content>', $xml);
	}//end testAPartReadsBackVerbatim()

	/**
	 * Presence is answered without reading the part.
	 *
	 * Asserted BOTH ways: a `hasPart` that always said true would satisfy the
	 * positive case while telling us nothing.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.1
	 */
	public function testPresenceIsAnsweredBothWays(): void {
		$package = $this->package();

		$this->assertTrue($this->io->hasPart(packageBytes: $package, part: 'styles.xml'));
		$this->assertFalse($this->io->hasPart(packageBytes: $package, part: 'xl/sharedStrings.xml'));
	}//end testPresenceIsAnsweredBothWays()

	/**
	 * Reading a part that is not there is refused, not answered with ''.
	 *
	 * An empty string would be indistinguishable from a genuinely empty part,
	 * and the codecs above this branch on exactly that difference.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.1
	 */
	public function testReadingAnAbsentPartThrows(): void {
		$this->expectException(RuntimeException::class);

		$this->io->readPart(packageBytes: $this->package(), part: 'no/such/part.xml');
	}//end testReadingAnAbsentPartThrows()

	/**
	 * Something that is not a package is refused rather than parsed.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.1
	 */
	public function testARandomBlobIsNotAPackage(): void {
		$this->expectException(RuntimeException::class);

		$this->io->listParts(packageBytes: 'this is not a zip file');
	}//end testARandomBlobIsNotAPackage()

	/**
	 * 🔴 Writing one part leaves every other part byte-identical.
	 *
	 * This is the whole contract. An editor that rebuilds the archive drops the
	 * styles, relationships and embedded media it did not know about — the file
	 * still opens, and the loss reads as "the formatting is gone" rather than as
	 * an error anyone can act on.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.1
	 */
	public function testWritingOnePartLeavesTheOthersIntact(): void {
		$package = $this->package();

		$written = $this->io->writePart(
			packageBytes: $package,
			part: 'content.xml',
			xml: '<office:document-content>changed</office:document-content>'
		);

		$this->assertSame(
			'<office:document-content>changed</office:document-content>',
			$this->io->readPart(packageBytes: $written, part: 'content.xml'),
			'the written part must carry the new content'
		);
		$this->assertSame(
			$this->io->readPart(packageBytes: $package, part: 'styles.xml'),
			$this->io->readPart(packageBytes: $written, part: 'styles.xml'),
			'a part nobody asked to change must survive byte-identical'
		);
		$this->assertSame(
			$this->io->readPart(packageBytes: $package, part: 'mimetype'),
			$this->io->readPart(packageBytes: $written, part: 'mimetype'),
			'the mimetype entry must survive a content write'
		);
	}//end testWritingOnePartLeavesTheOthersIntact()

	/**
	 * A written package still lists exactly the parts it started with.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.1
	 */
	public function testWritingDoesNotAddOrDropParts(): void {
		$package = $this->package();

		$before = $this->io->listParts(packageBytes: $package);
		$after = $this->io->listParts(
			packageBytes: $this->io->writePart(packageBytes: $package, part: 'content.xml', xml: '<a/>')
		);

		sort($before);
		sort($after);

		$this->assertSame($before, $after);
	}//end testWritingDoesNotAddOrDropParts()
}//end class
