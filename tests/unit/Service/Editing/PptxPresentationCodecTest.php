<?php

/**
 * Unit tests for PptxPresentationCodec — OOXML slides, shapes and notes.
 *
 * 🔴 The property these tests exist for: notes live in a DIFFERENT PART from
 * the slide (`ppt/notesSlides/notesSlideN.xml` beside `ppt/slides/slideN.xml`),
 * and both must be reported against the same slide under different regions. A
 * codec that reads only `ppt/slides/` silently loses every speaker note; one
 * that folds them together lets a notes edit rewrite what is on screen.
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
 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-2.1
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Tests\Unit\Service\Editing;

use OCA\DocuDesk\Service\Editing\PackagePartIo;
use OCA\DocuDesk\Service\Editing\PptxPresentationCodec;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the OOXML presentation codec.
 *
 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-2.1
 */
class PptxPresentationCodecTest extends TestCase {
	/**
	 * A package double holding several named parts in memory.
	 *
	 * @param array<string, string> $parts Path => XML.
	 *
	 * @return PackagePartIo The double.
	 */
	private function io(array $parts): PackagePartIo {
		return new class($parts) extends PackagePartIo {
			/**
			 * @param array<string, string> $parts Path => XML.
			 */
			public function __construct(public array $parts) {
			}

			/**
			 * List the part paths.
			 *
			 * @param string $packageBytes Ignored.
			 *
			 * @return array<int, string> The paths.
			 */
			public function listParts(string $packageBytes): array {
				return array_keys($this->parts);
			}

			/**
			 * Read one part.
			 *
			 * @param string $packageBytes Ignored.
			 * @param string $part         The path.
			 *
			 * @return string The XML.
			 */
			public function readPart(string $packageBytes, string $part): string {
				return ($this->parts[$part] ?? '');
			}

			/**
			 * Replace one part in memory.
			 *
			 * @param string $packageBytes Ignored.
			 * @param string $part         The path.
			 * @param string $xml          The new XML.
			 *
			 * @return string A stand-in for the new package bytes.
			 */
			public function writePart(string $packageBytes, string $part, string $xml): string {
				$this->parts[$part] = $xml;

				return 'package-bytes';
			}
		};
	}//end io()

	/**
	 * One slide and its notes part, plus a part that is neither.
	 *
	 * @return array<string, string> The package parts.
	 */
	private function deck(): array {
		return [
			'ppt/presentation.xml' => '<p:presentation/>',
			'ppt/slides/slide1.xml' => '<p:sld><p:sp><p:nvSpPr><p:cNvPr id="2" name="Title"/></p:nvSpPr>'
				. '<p:txBody><a:p><a:t>Welcome</a:t></a:p></p:txBody></p:sp></p:sld>',
			'ppt/notesSlides/notesSlide1.xml' => '<p:notes><p:sp><p:nvSpPr><p:cNvPr id="3" name="Notes"/></p:nvSpPr>'
				. '<p:txBody><a:p><a:t>Mention the deadline</a:t></a:p></p:txBody></p:sp></p:notes>',
		];
	}//end deck()

	/**
	 * A slide's shapes are reported against that slide.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-2.1
	 */
	public function testSlideShapesAreReportedAgainstTheirSlide(): void {
		$codec = new PptxPresentationCodec($this->io($this->deck()));

		$slide = array_values(array_filter(
			$codec->readShapes(packageBytes: 'ignored'),
			static fn (array $s): bool => $s['region'] === 'slide'
		));

		$this->assertCount(1, $slide);
		$this->assertSame('slide1', $slide[0]['slide']);
		$this->assertSame('Welcome', $slide[0]['text']);
	}//end testSlideShapesAreReportedAgainstTheirSlide()

	/**
	 * 🔴 The notes part is read, and reported against the SAME slide.
	 *
	 * A codec that only walks `ppt/slides/` loses every speaker note without
	 * erroring — the deck reads as if nobody wrote any.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-2.2
	 */
	public function testNotesAreReadAndBelongToTheirSlide(): void {
		$codec = new PptxPresentationCodec($this->io($this->deck()));

		$notes = array_values(array_filter(
			$codec->readShapes(packageBytes: 'ignored'),
			static fn (array $s): bool => $s['region'] === 'notes'
		));

		$this->assertCount(1, $notes);
		$this->assertSame('slide1', $notes[0]['slide'], 'notesSlide1 belongs to slide1');
		$this->assertSame('Mention the deadline', $notes[0]['text']);
	}//end testNotesAreReadAndBelongToTheirSlide()

	/**
	 * Parts that are neither a slide nor its notes are ignored.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-2.1
	 */
	public function testUnrelatedPartsAreNotWalked(): void {
		$codec = new PptxPresentationCodec($this->io($this->deck()));

		$this->assertCount(
			2,
			$codec->readShapes(packageBytes: 'ignored'),
			'ppt/presentation.xml holds no shapes and must not contribute one'
		);
	}//end testUnrelatedPartsAreNotWalked()

	/**
	 * Shapes are addressed by their `cNvPr` id, not by position.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-2.1
	 */
	public function testShapesAreAddressedById(): void {
		$codec = new PptxPresentationCodec($this->io($this->deck()));

		$ids = array_column($codec->readShapes(packageBytes: 'ignored'), 'shape');

		$this->assertContains('2', $ids);
		$this->assertContains('3', $ids);
	}//end testShapesAreAddressedById()

	/**
	 * Writing the notes leaves the slide's own text untouched.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-2.2
	 */
	public function testWritingNotesDoesNotTouchTheSlide(): void {
		$io = $this->io($this->deck());
		$codec = new PptxPresentationCodec($io);

		$codec->writeShape(
			packageBytes: 'ignored',
			slide: 'slide1',
			shape: '3',
			region: 'notes',
			text: 'Skip the deadline slide'
		);

		$byRegion = [];
		foreach ($codec->readShapes(packageBytes: 'ignored') as $shape) {
			$byRegion[$shape['region']][] = $shape['text'];
		}

		$this->assertSame(['Skip the deadline slide'], $byRegion['notes']);
		$this->assertSame(['Welcome'], $byRegion['slide'], 'the slide text must survive a notes edit');
	}//end testWritingNotesDoesNotTouchTheSlide()

	/**
	 * The codec claims the OOXML presentation extension and not the ODF one.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.1
	 */
	public function testItSupportsOnlyItsOwnFormat(): void {
		$codec = new PptxPresentationCodec($this->io($this->deck()));

		$this->assertTrue($codec->supports(extension: 'pptx'));
		$this->assertFalse($codec->supports(extension: 'odp'));
		$this->assertFalse($codec->supports(extension: 'xlsx'));
	}//end testItSupportsOnlyItsOwnFormat()
}//end class
