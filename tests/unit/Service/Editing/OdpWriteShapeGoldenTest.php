<?php

/**
 * Golden cases of writing one frame in an ODP deck.
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

use OCA\Filinq\Service\Editing\OdpPresentationCodec;
use OCA\Filinq\Service\Editing\PackagePartIo;
use OCA\Filinq\Tests\Unit\Support\RecordsCalls;
use PHPUnit\Framework\TestCase;

/**
 * The part each writeShape() call writes back, or the refusal, compared with
 * JSON recorded from the codec whose page rewrite took five parameters.
 *
 * @covers \OCA\Filinq\Service\Editing\OdpPresentationCodec
 * @uses   \OCA\Filinq\Service\Editing\FrameRewrite
 *
 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-2.1
 */
class OdpWriteShapeGoldenTest extends TestCase {
	use RecordsCalls;

	private const GOLDEN = __DIR__.'/golden/odp-write-shape.json';

	/**
	 * The cases: the content.xml, slide, shape, region and text.
	 *
	 * @return array<string, array{0: string, 1: string, 2: string, 3: string, 4: string}>
	 */
	public static function cases(): array {
		$notes   = '<presentation:notes><draw:frame draw:name="Notes"><draw:text-box><text:p>Mention the deadline</text:p></draw:text-box></draw:frame></presentation:notes>';
		$title   = '<draw:frame draw:name="Title"><draw:text-box><text:p a="1">Welcome</text:p><text:p>Second</text:p></draw:text-box></draw:frame>';
		$deck    = self::doc(pages: '<draw:page draw:name="Intro">'.$title.$notes.'</draw:page>');
		$bare    = self::doc(pages: '<draw:page draw:name="Intro">'.$title.'</draw:page>');
		$empty   = self::doc(pages: '<draw:page draw:name="Intro"><draw:frame draw:name="Title"><draw:text-box/></draw:frame>'.$notes.'</draw:page>');
		$twice   = self::doc(pages: '<draw:page draw:name="Intro">'.$title.'</draw:page><draw:page draw:name="Outro">'.$title.$notes.'</draw:page>');
		$shared  = self::doc(pages: '<draw:page draw:name="Intro"><draw:frame draw:name="Notes"><draw:text-box><text:p>On the slide</text:p></draw:text-box></draw:frame>'.$notes.'</draw:page>');
		$emptyNt = self::doc(pages: '<draw:page draw:name="Intro">'.$title.'<presentation:notes><draw:frame draw:name="Notes"><draw:text-box/></draw:frame></presentation:notes></draw:page>');
		return [
			'slide frame on a page with notes'        => [$deck, 'Intro', 'Title', 'slide', 'Hello'],
			'notes frame'                             => [$deck, 'Intro', 'Notes', 'notes', 'Say thanks'],
			'a notes frame is not on the slide'       => [$deck, 'Intro', 'Notes', 'slide', 'x'],
			'a slide frame is not in the notes'       => [$deck, 'Intro', 'Title', 'notes', 'x'],
			'slide frame on a page without notes'     => [$bare, 'Intro', 'Title', 'slide', 'Hello'],
			'notes region on a page without notes'    => [$bare, 'Intro', 'Title', 'notes', 'x'],
			'an empty text box gets a paragraph'      => [$empty, 'Intro', 'Title', 'slide', 'Filled'],
			'an empty notes box gets a paragraph'     => [$emptyNt, 'Intro', 'Notes', 'notes', 'Filled'],
			'an unknown slide'                        => [$deck, 'Missing', 'Title', 'slide', 'x'],
			'an unknown frame'                        => [$deck, 'Intro', 'Body', 'slide', 'x'],
			'only the named page changes'             => [$twice, 'Outro', 'Title', 'slide', 'Bye'],
			'only the named page notes change'        => [$twice, 'Outro', 'Notes', 'notes', 'Bye'],
			'same name on slide and notes, slide'     => [$shared, 'Intro', 'Notes', 'slide', 'Slide only'],
			'same name on slide and notes, notes'     => [$shared, 'Intro', 'Notes', 'notes', 'Notes only'],
			'text is escaped'                         => [$deck, 'Intro', 'Title', 'slide', 'a < b & "c" \'d\''],
			'a frame name with regex characters'      => [self::doc(pages: '<draw:page draw:name="Intro"><draw:frame draw:name="T.(1)"><draw:text-box><text:p>v</text:p></draw:text-box></draw:frame></draw:page>'), 'Intro', 'T.(1)', 'slide', 'ok'],
		];

	}//end cases()

	/**
	 * The written part, or the refusal, matches the recorded one.
	 *
	 * @param string $xml    The content.xml.
	 * @param string $slide  The page name.
	 * @param string $shape  The frame name.
	 * @param string $region The region.
	 * @param string $text   The replacement text.
	 *
	 * @dataProvider cases
	 *
	 * @return void
	 */
	public function testWriteShape(string $xml, string $slide, string $shape, string $region, string $text): void {
		$io    = $this->recorder(
			PackagePartIo::class,
			['readPart' => $xml, 'writePart' => 'package-bytes']
		);
		$codec = new OdpPresentationCodec(io: $io);
		$this->traceCall(fn () => $codec->writeShape(packageBytes: 'pkg', slide: $slide, shape: $shape, region: $region, text: $text));

		$this->assertTrace(file: self::GOLDEN, name: (string) $this->dataName());

	}//end testWriteShape()

	/**
	 * A content.xml holding $pages.
	 *
	 * @param string $pages The page markup.
	 *
	 * @return string The document.
	 */
	private static function doc(string $pages): string {
		return '<office:document-content><office:presentation>'.$pages.'</office:presentation></office:document-content>';

	}//end doc()
}//end class
