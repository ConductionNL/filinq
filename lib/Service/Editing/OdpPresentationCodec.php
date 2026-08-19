<?php

/**
 * DocuDesk OdpPresentationCodec
 *
 * Shape access for ODF presentations (`.odp`).
 *
 * 🔴 Pages are addressed by `draw:name`, never by position. Slide ORDER is not
 * a stable identity: an agent told to edit "slide 4" and a human who reordered
 * the deck will disagree about which slide that is, and the agent will edit the
 * wrong one confidently.
 *
 * ⚠️ Speaker notes live in `<presentation:notes>` INSIDE the page. They are
 * addressed as a distinct region so that drafting talking points cannot alter
 * what is on screen — the two are one XML subtree apart, which is exactly close
 * enough to confuse.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service\Editing
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 *
 * @link https://docudesk.app
 *
 * @spec openspec/changes/multi-format-editing-tools/tasks.md#2-presentation
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service\Editing;

use RuntimeException;

/**
 * Reads and writes shapes in ODF presentations.
 */
class OdpPresentationCodec implements PresentationFamilyCodec {

	/**
	 * The part carrying presentation content.
	 *
	 * @var string
	 */
	private const PART = 'content.xml';

	/**
	 * Constructor.
	 *
	 * @param PackagePartIo $io Package reader/writer.
	 */
	public function __construct(private readonly PackagePartIo $io) {
	}//end __construct()

	/**
	 * Whether this codec handles an extension.
	 *
	 * @param string $extension The lower-case file extension.
	 *
	 * @return bool True for odp.
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-2.1
	 */
	public function supports(string $extension): bool {
		return (strtolower($extension) === 'odp');
	}//end supports()

	/**
	 * Read every text-bearing frame, on slides and in notes.
	 *
	 * @param string $packageBytes The package.
	 *
	 * @return array<int, array{slide: string, shape: string, region: string, text: string}> The shapes.
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-2.1
	 */
	public function readShapes(string $packageBytes): array {
		$xml = $this->io->readPart(packageBytes: $packageBytes, part: self::PART);
		$shapes = [];

		foreach ($this->pages(xml: $xml) as $page) {
			foreach ($this->regionsOf(page: $page['markup']) as $region => $markup) {
				foreach ($this->framesIn(markup: $markup) as $frame) {
					$shapes[] = [
						'slide' => $page['name'],
						'shape' => $frame['name'],
						'region' => $region,
						'text' => $frame['text'],
					];
				}
			}
		}

		return $shapes;
	}//end readShapes()

	/**
	 * Replace one frame's text.
	 *
	 * @param string $packageBytes The package.
	 * @param string $slide        The page name.
	 * @param string $shape        The frame name.
	 * @param string $region       Either `slide` or `notes`.
	 * @param string $text         The replacement text.
	 *
	 * @return string The rewritten package.
	 *
	 * @throws RuntimeException When the frame cannot be located.
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-2.1
	 */
	public function writeShape(string $packageBytes, string $slide, string $shape, string $region, string $text): string {
		$xml = $this->io->readPart(packageBytes: $packageBytes, part: self::PART);
		$escaped = htmlspecialchars($text, ENT_QUOTES | ENT_XML1);
		$rewritten = false;

		$updated = preg_replace_callback(
			'/<draw:page\b[^>]*draw:name="([^"]*)"[^>]*>.*?<\/draw:page>/s',
			function (array $page) use ($slide, $shape, $region, $escaped, &$rewritten): string {
				if ($page[1] !== $slide) {
					return $page[0];
				}

				return $this->rewritePage(
					markup: $page[0],
					shape: $shape,
					region: $region,
					escaped: $escaped,
					rewritten: $rewritten
				);
			},
			$xml
		);

		if ($rewritten === false) {
			throw new RuntimeException(
				sprintf('Shape "%s" was not found on "%s" (%s).', $shape, $slide, $region)
			);
		}

		return $this->io->writePart(packageBytes: $packageBytes, part: self::PART, xml: (string)$updated);
	}//end writeShape()

	/**
	 * Rewrite one frame within one page, in the requested region only.
	 *
	 * 🔴 Works on OFFSETS, not string replacement. The notes subtree sits
	 * INSIDE the page, so a first attempt that stripped notes out, rewrote the
	 * remainder and concatenated them back put the notes after `</draw:page>` —
	 * outside the page entirely. The reader then found no notes at all, so a
	 * slide edit silently DELETED every speaker note on that slide.
	 *
	 * @param string $markup    The page markup.
	 * @param string $shape     The frame name.
	 * @param string $region    Either `slide` or `notes`.
	 * @param string $escaped   The escaped replacement text.
	 * @param bool   $rewritten Set true when a frame was rewritten.
	 *
	 * @return string The rewritten page markup.
	 */
	private function rewritePage(string $markup, string $shape, string $region, string $escaped, bool &$rewritten): string {
		$notesStart = null;
		$notesLength = 0;
		if (preg_match('/<presentation:notes\b.*?<\/presentation:notes>/s', $markup, $match, PREG_OFFSET_CAPTURE) === 1) {
			$notesStart = $match[0][1];
			$notesLength = strlen($match[0][0]);
		}

		if ($region === 'notes') {
			if ($notesStart === null) {
				return $markup;
			}

			$rewrittenNotes = $this->rewriteFrame(
				markup: substr($markup, $notesStart, $notesLength),
				shape: $shape,
				escaped: $escaped,
				rewritten: $rewritten
			);

			return substr_replace($markup, $rewrittenNotes, $notesStart, $notesLength);
		}

		// Slide region: rewrite everything EXCEPT the notes span, leaving the
		// notes bytes exactly where they were.
		if ($notesStart === null) {
			return $this->rewriteFrame(markup: $markup, shape: $shape, escaped: $escaped, rewritten: $rewritten);
		}

		$head = $this->rewriteFrame(
			markup: substr($markup, 0, $notesStart),
			shape: $shape,
			escaped: $escaped,
			rewritten: $rewritten
		);

		return $head . substr($markup, $notesStart);
	}//end rewritePage()

	/**
	 * Replace the first paragraph of a named frame.
	 *
	 * @param string $markup    The markup to search.
	 * @param string $shape     The frame name.
	 * @param string $escaped   The escaped replacement text.
	 * @param bool   $rewritten Set true when a frame was rewritten.
	 *
	 * @return string The rewritten markup.
	 */
	private function rewriteFrame(string $markup, string $shape, string $escaped, bool &$rewritten): string {
		$updated = preg_replace_callback(
			'/<draw:frame\b[^>]*draw:name="' . preg_quote($shape, '/') . '"[^>]*>.*?<\/draw:frame>/s',
			function (array $frame) use ($escaped, &$rewritten): string {
				$rewritten = true;

				// A placeholder frame can be EMPTY (`<draw:text-box/>`), which
				// has no paragraph to replace. Writing then requires creating
				// one, or the edit reports success and changes nothing.
				if (preg_match('/<text:p\b/', $frame[0]) !== 1) {
					return preg_replace(
						'/<draw:text-box\s*\/>/',
						'<draw:text-box><text:p>' . $escaped . '</text:p></draw:text-box>',
						$frame[0],
						1
					) ?? $frame[0];
				}

				return preg_replace(
					'/<text:p\b[^>]*>.*?<\/text:p>/s',
					'<text:p>' . $escaped . '</text:p>',
					$frame[0],
					1
				) ?? $frame[0];
			},
			$markup
		);

		return (string)$updated;
	}//end rewriteFrame()

	/**
	 * The pages in the content.
	 *
	 * @param string $xml The content.xml.
	 *
	 * @return array<int, array{name: string, markup: string}> The pages.
	 */
	private function pages(string $xml): array {
		$pages = [];
		preg_match_all('/<draw:page\b[^>]*draw:name="([^"]*)"[^>]*>.*?<\/draw:page>/s', $xml, $found, PREG_SET_ORDER);
		foreach ($found as $match) {
			$pages[] = ['name' => $match[1], 'markup' => $match[0]];
		}

		return $pages;
	}//end pages()

	/**
	 * Split a page into its slide and notes regions.
	 *
	 * @param string $page The page markup.
	 *
	 * @return array<string, string> Region name to markup.
	 */
	private function regionsOf(string $page): array {
		$notes = '';
		if (preg_match('/<presentation:notes\b.*?<\/presentation:notes>/s', $page, $match) === 1) {
			$notes = $match[0];
		}

		$slide = $page;
		if ($notes !== '') {
			$slide = str_replace($notes, '', $page);
		}

		$regions = ['slide' => $slide];
		if ($notes !== '') {
			$regions['notes'] = $notes;
		}

		return $regions;
	}//end regionsOf()

	/**
	 * The named, text-bearing frames in a region.
	 *
	 * @param string $markup The region markup.
	 *
	 * @return array<int, array{name: string, text: string}> The frames.
	 */
	private function framesIn(string $markup): array {
		$frames = [];
		preg_match_all('/<draw:frame\b[^>]*draw:name="([^"]*)"[^>]*>(.*?)<\/draw:frame>/s', $markup, $found, PREG_SET_ORDER);

		foreach ($found as $match) {
			preg_match_all('/<text:p[^>]*>(.*?)<\/text:p>/s', $match[2], $texts);
			$frames[] = [
				'name' => $match[1],
				'text' => html_entity_decode(strip_tags(implode(' ', $texts[1])), ENT_QUOTES | ENT_HTML5),
			];
		}

		return $frames;
	}//end framesIn()
}//end class
