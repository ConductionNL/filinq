<?php

/**
 * Unit tests for OdpPresentationCodec — slides, shapes and speaker notes.
 *
 * 🔴 The property these tests exist for: speaker notes are a region DISTINCT
 * from slide content. Drafting talking points must not alter what is on screen,
 * and a codec that folds the two together does exactly that — quietly, because
 * both are `<text:p>` inside the same page.
 *
 * Shapes are addressed by NAME, never by ordinal position: slide order and
 * shape order are not stable, so an ordinal write eventually edits a different
 * shape than the caller named.
 *
 * @category Test
 * @package  OCA\Filinq\Tests\Unit\Service\Editing
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

namespace OCA\Filinq\Tests\Unit\Service\Editing;

use OCA\Filinq\Service\Editing\OdpPresentationCodec;
use OCA\Filinq\Service\Editing\PackagePartIo;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the ODF presentation codec.
 *
 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-2.1
 */
class OdpPresentationCodecTest extends TestCase {
	/**
	 * A package double serving one `content.xml`, capturing what is written.
	 *
	 * @param string $contentXml The part to serve.
	 *
	 * @return PackagePartIo The double.
	 */
	private function io(string $contentXml): PackagePartIo {
		return new class($contentXml) extends PackagePartIo {
			/**
			 * The XML last written back, or null.
			 *
			 * @var string|null
			 */
			public ?string $written = null;

			/**
			 * @param string $contentXml The served part.
			 */
			public function __construct(
				private string $contentXml,
			) {
			}

			/**
			 * Serve the content part.
			 *
			 * @param string $packageBytes Ignored.
			 * @param string $part Ignored — this double holds one part.
			 *
			 * @return string The part.
			 */
			public function readPart(string $packageBytes, string $part): string {
				return $this->contentXml;
			}

			/**
			 * Capture the rewritten part instead of repacking a ZIP.
			 *
			 * @param string $packageBytes Ignored.
			 * @param string $part Ignored.
			 * @param string $xml The rewritten part.
			 *
			 * @return string A stand-in for the new package bytes.
			 */
			public function writePart(string $packageBytes, string $part, string $xml): string {
				$this->written = $xml;
				$this->contentXml = $xml;

				return 'package-bytes';
			}
		};
	}//end io()

	/**
	 * One slide carrying a title frame and a notes frame.
	 *
	 * @return string The content.xml.
	 */
	private function deck(): string {
		return '<office:document-content><office:presentation>'
			. '<draw:page draw:name="Intro">'
			. '<draw:frame draw:name="Title"><draw:text-box><text:p>Welcome</text:p></draw:text-box></draw:frame>'
			. '<presentation:notes>'
			. '<draw:frame draw:name="Notes"><draw:text-box><text:p>Mention the deadline</text:p></draw:text-box></draw:frame>'
			. '</presentation:notes>'
			. '</draw:page>'
			. '</office:presentation></office:document-content>';
	}//end deck()

	/**
	 * Shapes are addressed by slide name and shape name.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-2.1
	 */
	public function testShapesAreAddressedByName(): void {
		$codec = new OdpPresentationCodec($this->io($this->deck()));

		$shapes = $codec->readShapes(packageBytes: 'ignored');
		$byName = array_column($shapes, 'text', 'shape');

		$this->assertSame('Welcome', $byName['Title']);
		$this->assertSame('Intro', $shapes[0]['slide']);
	}//end testShapesAreAddressedByName()

	/**
	 * 🔴 Speaker notes are their own region, not part of the slide.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-2.2
	 */
	public function testNotesAreADistinctRegionFromSlideContent(): void {
		$codec = new OdpPresentationCodec($this->io($this->deck()));

		$byRegion = [];
		foreach ($codec->readShapes(packageBytes: 'ignored') as $shape) {
			$byRegion[$shape['region']][] = $shape['text'];
		}

		$this->assertSame(['Welcome'], $byRegion['slide'], 'the notes text must not appear as slide content');
		$this->assertSame(['Mention the deadline'], $byRegion['notes']);
	}//end testNotesAreADistinctRegionFromSlideContent()

	/**
	 * Writing the notes leaves the slide's own text untouched.
	 *
	 * Drafting talking points must not alter what is on screen — the failure
	 * this codec's region split exists to prevent.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-2.2
	 */
	public function testWritingNotesDoesNotTouchTheSlide(): void {
		$io = $this->io($this->deck());
		$codec = new OdpPresentationCodec($io);

		$codec->writeShape(
			packageBytes: 'ignored',
			slide: 'Intro',
			shape: 'Notes',
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
	 * Writing a slide shape leaves the speaker notes untouched.
	 *
	 * The mirror of the test above, because a region split that only works in
	 * one direction is not a split.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-2.2
	 */
	public function testWritingTheSlideDoesNotTouchTheNotes(): void {
		$io = $this->io($this->deck());
		$codec = new OdpPresentationCodec($io);

		$codec->writeShape(
			packageBytes: 'ignored',
			slide: 'Intro',
			shape: 'Title',
			region: 'slide',
			text: 'Good morning'
		);

		$byRegion = [];
		foreach ($codec->readShapes(packageBytes: 'ignored') as $shape) {
			$byRegion[$shape['region']][] = $shape['text'];
		}

		$this->assertSame(['Good morning'], $byRegion['slide']);
		$this->assertSame(['Mention the deadline'], $byRegion['notes'], 'the notes must survive a slide edit');
	}//end testWritingTheSlideDoesNotTouchTheNotes()

	/**
	 * Text is escaped on write, so a shape cannot inject markup.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-2.1
	 */
	public function testWrittenTextIsEscaped(): void {
		$io = $this->io($this->deck());
		$codec = new OdpPresentationCodec($io);

		$codec->writeShape(
			packageBytes: 'ignored',
			slide: 'Intro',
			shape: 'Title',
			region: 'slide',
			text: '<draw:frame draw:name="Injected"/>'
		);

		$this->assertStringNotContainsString(
			'<draw:frame draw:name="Injected"',
			(string)$io->written,
			'written text must be escaped, never spliced in as markup'
		);
		$this->assertCount(
			2,
			$codec->readShapes(packageBytes: 'ignored'),
			'escaping must not have created a third shape'
		);
	}//end testWrittenTextIsEscaped()

	/**
	 * The codec claims the ODF presentation extension and not the OOXML one.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.1
	 */
	public function testItSupportsOnlyItsOwnFormat(): void {
		$codec = new OdpPresentationCodec($this->io($this->deck()));

		$this->assertTrue($codec->supports(extension: 'odp'));
		$this->assertFalse($codec->supports(extension: 'pptx'));
		$this->assertFalse($codec->supports(extension: 'ods'));
	}//end testItSupportsOnlyItsOwnFormat()
}//end class
