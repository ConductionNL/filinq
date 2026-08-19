<?php

/**
 * Presentation editing: ids, not positions, and notes kept off the screen.
 *
 * @category Test
 * @package  OCA\DocuDesk\Tests\Unit\Service\Editing
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://docudesk.app
 *
 * @spec openspec/changes/multi-format-editing-tools/tasks.md#2-presentation
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Tests\Unit\Service\Editing;

use OCA\DocuDesk\Service\Editing\PackagePartIo;
use OCA\DocuDesk\Service\Editing\PresentationCodec;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use ZipArchive;

/**
 * Tests for PresentationCodec.
 */
class PresentationCodecTest extends TestCase {

	private PresentationCodec $codec;

	private string $odp;

	/**
	 * @return void
	 */
	protected function setUp(): void {
		$this->codec = new PresentationCodec(new PackagePartIo());
		$this->odp = $this->buildOdp();
	}//end setUp()

	/**
	 * A two-page deck where page1 carries both a slide frame and notes.
	 *
	 * @return string The package bytes.
	 */
	private function buildOdp(): string {
		$content = '<?xml version="1.0"?><office:document-content><office:body><office:presentation>'
			. '<draw:page draw:name="page1">'
			. '<draw:frame draw:name="Titel"><draw:text-box><text:p>Op het scherm</text:p></draw:text-box></draw:frame>'
			. '<presentation:notes>'
			. '<draw:frame draw:name="Notitie"><draw:text-box><text:p>Spreektekst</text:p></draw:text-box></draw:frame>'
			. '</presentation:notes>'
			. '</draw:page>'
			. '<draw:page draw:name="page2">'
			. '<draw:frame draw:name="Titel"><draw:text-box><text:p>Tweede</text:p></draw:text-box></draw:frame>'
			. '</draw:page>'
			. '</office:presentation></office:body></office:document-content>';

		$path = tempnam(sys_get_temp_dir(), 'odp');
		$zip = new ZipArchive();
		$zip->open($path, ZipArchive::OVERWRITE);
		$zip->addFromString('content.xml', $content);
		$zip->close();
		$bytes = (string)file_get_contents($path);
		unlink($path);

		return $bytes;
	}//end buildOdp()

	/**
	 * Shapes are reported with their page id and their region.
	 *
	 * @return void
	 */
	public function testShapesCarryTheirPageAndRegion(): void {
		$shapes = $this->codec->readShapes($this->odp, 'odp');

		$this->assertCount(3, $shapes);
		$this->assertSame('page1', $shapes[0]['slide']);
		$this->assertSame('slide', $shapes[0]['region']);
		$this->assertSame('notes', $shapes[1]['region']);
		$this->assertSame('Spreektekst', $shapes[1]['text']);
	}//end testShapesCarryTheirPageAndRegion()

	/**
	 * 🔴 A slide edit must not touch the notes. They are one XML subtree apart,
	 * which is exactly close enough to confuse — an early version stripped the
	 * notes out to rewrite the slide and reattached them OUTSIDE the page,
	 * silently deleting every speaker note on it.
	 *
	 * @return void
	 */
	public function testASlideEditLeavesTheNotesIntact(): void {
		$result = $this->codec->applyShapeEdits(
			$this->odp,
			'odp',
			[['slide' => 'page1', 'shape' => 'Titel', 'region' => 'slide', 'text' => 'Nieuw']]
		);

		$shapes = $this->codec->readShapes($result['bytes'], 'odp');
		$byRegion = [];
		foreach ($shapes as $shape) {
			$byRegion[$shape['region']][] = $shape['text'];
		}

		$this->assertCount(3, $shapes, 'the notes frame disappeared from the deck');
		$this->assertContains('Nieuw', $byRegion['slide']);
		$this->assertContains('Spreektekst', $byRegion['notes'], 'the speaker note was destroyed by a slide edit');
	}//end testASlideEditLeavesTheNotesIntact()

	/**
	 * 🔴 And the reverse: drafting talking points must not alter the screen.
	 *
	 * @return void
	 */
	public function testANotesEditLeavesTheSlideIntact(): void {
		$result = $this->codec->applyShapeEdits(
			$this->odp,
			'odp',
			[['slide' => 'page1', 'shape' => 'Notitie', 'region' => 'notes', 'text' => 'Andere tekst']]
		);

		$shapes = $this->codec->readShapes($result['bytes'], 'odp');
		$slide = array_values(array_filter($shapes, static fn (array $s): bool => $s['region'] === 'slide'));
		$notes = array_values(array_filter($shapes, static fn (array $s): bool => $s['region'] === 'notes'));

		$this->assertSame('Op het scherm', $slide[0]['text'], 'a notes edit changed what is on screen');
		$this->assertSame('Andere tekst', $notes[0]['text']);
	}//end testANotesEditLeavesTheSlideIntact()

	/**
	 * 🔴 A shape name repeated on another page must not be edited by accident.
	 * Both pages have a frame called "Titel"; addressing is page-scoped.
	 *
	 * @return void
	 */
	public function testAnEditIsScopedToTheAddressedPage(): void {
		$result = $this->codec->applyShapeEdits(
			$this->odp,
			'odp',
			[['slide' => 'page2', 'shape' => 'Titel', 'text' => 'Alleen pagina twee']]
		);

		// Filter by region as well as page: page1 carries a slide frame AND a
		// notes frame, so keying only by page silently compares the wrong one.
		$shapes = $this->codec->readShapes($result['bytes'], 'odp');
		$page1Slide = array_values(array_filter(
			$shapes,
			static fn (array $s): bool => ($s['slide'] === 'page1' && $s['region'] === 'slide')
		));
		$page2Slide = array_values(array_filter(
			$shapes,
			static fn (array $s): bool => ($s['slide'] === 'page2')
		));

		$this->assertSame('Op het scherm', $page1Slide[0]['text'], 'editing page2 changed page1');
		$this->assertSame('Alleen pagina twee', $page2Slide[0]['text']);
	}//end testAnEditIsScopedToTheAddressedPage()

	/**
	 * ⚠️ Slides are addressed by ID, never by ordinal. "1" is not a page name,
	 * and accepting it would let an agent edit a different slide than the one
	 * it meant the moment a human reorders the deck.
	 *
	 * @return void
	 */
	public function testAnOrdinalIsNotASlideId(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessageMatches('/was not found on "1"/');

		$this->codec->applyShapeEdits($this->odp, 'odp', [['slide' => '1', 'shape' => 'Titel', 'text' => 'x']]);
	}//end testAnOrdinalIsNotASlideId()

	/**
	 * An unknown region is refused rather than quietly treated as the slide:
	 * a typo must not publish talking points on screen.
	 *
	 * @return void
	 */
	public function testAnUnknownRegionIsRefused(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessageMatches('/unknown region "notez".*slide, notes/s');

		$this->codec->applyShapeEdits(
			$this->odp,
			'odp',
			[['slide' => 'page1', 'shape' => 'Titel', 'region' => 'notez', 'text' => 'x']]
		);
	}//end testAnUnknownRegionIsRefused()

	/**
	 * The region defaults to the SLIDE, not to whatever was found first.
	 *
	 * @return void
	 */
	public function testTheRegionDefaultsToTheSlide(): void {
		$result = $this->codec->applyShapeEdits(
			$this->odp,
			'odp',
			[['slide' => 'page1', 'shape' => 'Titel', 'text' => 'Standaard']]
		);

		$this->assertSame(['page1/slide!Titel'], $result['applied']);
	}//end testTheRegionDefaultsToTheSlide()

	/**
	 * Over the shape bound the call is refused rather than truncated.
	 *
	 * @return void
	 */
	public function testAnOversizedCallIsRefused(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessageMatches('/refused rather than.*truncated/s');

		$this->codec->applyShapeEdits(
			$this->odp,
			'odp',
			array_fill(0, (PresentationCodec::MAX_SHAPES_PER_CALL + 1), ['slide' => 'page1', 'shape' => 'Titel', 'text' => 'x'])
		);
	}//end testAnOversizedCallIsRefused()

	/**
	 * A non-presentation extension is refused, naming what IS supported.
	 *
	 * @return void
	 */
	public function testANonPresentationExtensionIsRefused(): void {
		$this->assertFalse($this->codec->supports('docx'));

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessageMatches('/not a presentation this codec edits.*pptx, odp/s');

		$this->codec->readShapes($this->odp, 'docx');
	}//end testANonPresentationExtensionIsRefused()
}//end class
