<?php

/**
 * Filinq PptxPresentationCodec
 *
 * Shape access for OOXML presentations (`.pptx`).
 *
 * 🔴 Slides are addressed by their PART name (`slide1`), never by position.
 * Slide ORDER lives in `presentation.xml`, not in the part numbering, so an
 * agent told to edit "slide 4" and a human who reordered the deck will disagree
 * about which slide that is — and the agent will edit the wrong one confidently.
 * A part name does not move when the deck is reordered.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Editing
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 *
 * @link https://filinq.app
 *
 * @spec openspec/changes/multi-format-editing-tools/tasks.md#2-presentation
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Editing;

use RuntimeException;

/**
 * Reads and writes shapes in OOXML presentations.
 */
class PptxPresentationCodec implements PresentationFamilyCodec {

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
	 * @return bool True for pptx.
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-2.1
	 */
	public function supports(string $extension): bool {
		return (strtolower($extension) === 'pptx');
	}//end supports()

	/**
	 * Read every text-bearing shape across all slides and their notes.
	 *
	 * @param string $packageBytes The package.
	 *
	 * @return array<int, array{slide: string, shape: string, region: string, text: string}> The shapes.
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-2.1
	 */
	public function readShapes(string $packageBytes): array {
		$shapes = [];

		foreach ($this->parts(packageBytes: $packageBytes) as $part) {
			$xml = $this->io->readPart(packageBytes: $packageBytes, part: $part['path']);
			foreach ($this->shapesIn(xml: $xml) as $shape) {
				$shapes[] = [
					'slide' => $part['slide'],
					'shape' => $shape['id'],
					'region' => $part['region'],
					'text' => $shape['text'],
				];
			}
		}

		return $shapes;
	}//end readShapes()

	/**
	 * Replace one shape's text.
	 *
	 * @param string $packageBytes The package.
	 * @param string $slide        The slide id.
	 * @param string $shape        The shape id.
	 * @param string $region       Either `slide` or `notes`.
	 * @param string $text         The replacement text.
	 *
	 * @return string The rewritten package.
	 *
	 * @throws RuntimeException When the shape cannot be located.
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-2.1
	 */
	public function writeShape(string $packageBytes, string $slide, string $shape, string $region, string $text): string {
		$path = $this->pathFor(packageBytes: $packageBytes, slide: $slide, region: $region);
		$xml = $this->io->readPart(packageBytes: $packageBytes, part: $path);
		$rewritten = false;

		$updated = preg_replace_callback(
			'/<p:sp>.*?<\/p:sp>/s',
			function (array $match) use ($shape, $text, &$rewritten): string {
				if (preg_match('/<p:cNvPr\b[^>]*\bid="' . preg_quote($shape, '/') . '"/', $match[0]) !== 1) {
					return $match[0];
				}

				$rewritten = true;

				return $this->replaceText(markup: $match[0], text: $text);
			},
			$xml
		);

		if ($rewritten === false) {
			throw new RuntimeException(
				sprintf('Shape %s was not found on %s (%s).', $shape, $slide, $region)
			);
		}

		return $this->io->writePart(packageBytes: $packageBytes, part: $path, xml: (string)$updated);
	}//end writeShape()

	/**
	 * Replace the visible text of a shape, keeping ONE run's formatting.
	 *
	 * Collapsing to a single run is deliberate and lossy in a known way: a
	 * shape whose text is split across runs carries per-run formatting that no
	 * longer maps onto replacement text of a different length. Keeping the
	 * FIRST run's properties preserves the shape's look; inventing a mapping
	 * would preserve nothing reliably while appearing to.
	 *
	 * @param string $markup The shape markup.
	 * @param string $text   The replacement text.
	 *
	 * @return string The rewritten shape.
	 */
	private function replaceText(string $markup, string $text): string {
		$escaped = htmlspecialchars($text, ENT_QUOTES | ENT_XML1);

		$properties = '';
		if (preg_match('/<a:rPr\b[^>]*(?:\/>|>.*?<\/a:rPr>)/s', $markup, $match) === 1) {
			$properties = $match[0];
		}

		$run = sprintf('<a:r>%s<a:t>%s</a:t></a:r>', $properties, $escaped);

		return preg_replace(
			'/<a:p\b[^>]*>.*?<\/a:p>/s',
			'<a:p>' . $run . '</a:p>',
			$markup,
			1
		) ?? $markup;
	}//end replaceText()

	/**
	 * The text-bearing shapes in one slide part.
	 *
	 * @param string $xml The slide XML.
	 *
	 * @return array<int, array{id: string, text: string}> The shapes.
	 */
	private function shapesIn(string $xml): array {
		$shapes = [];
		preg_match_all('/<p:sp>.*?<\/p:sp>/s', $xml, $found);

		foreach ($found[0] as $markup) {
			if (preg_match('/<p:cNvPr\b[^>]*\bid="([^"]+)"/', $markup, $id) !== 1) {
				continue;
			}

			preg_match_all('/<a:t>(.*?)<\/a:t>/s', $markup, $texts);
			$shapes[] = [
				'id' => $id[1],
				'text' => html_entity_decode(implode('', $texts[1]), ENT_QUOTES | ENT_HTML5),
			];
		}

		return $shapes;
	}//end shapesIn()

	/**
	 * Every slide and notes part in the package.
	 *
	 * @param string $packageBytes The package.
	 *
	 * @return array<int, array{slide: string, region: string, path: string}> The parts.
	 */
	private function parts(string $packageBytes): array {
		$parts = [];
		foreach ($this->io->listParts(packageBytes: $packageBytes) as $path) {
			if (preg_match('#^ppt/slides/(slide\d+)\.xml$#', $path, $match) === 1) {
				$parts[] = ['slide' => $match[1], 'region' => 'slide', 'path' => $path];
				continue;
			}

			if (preg_match('#^ppt/notesSlides/notesSlide(\d+)\.xml$#', $path, $match) === 1) {
				$parts[] = ['slide' => 'slide' . $match[1], 'region' => 'notes', 'path' => $path];
			}
		}

		return $parts;
	}//end parts()

	/**
	 * The part path for a slide's region.
	 *
	 * @param string $packageBytes The package.
	 * @param string $slide        The slide id.
	 * @param string $region       Either `slide` or `notes`.
	 *
	 * @return string The part path.
	 *
	 * @throws RuntimeException When the slide has no such region.
	 */
	private function pathFor(string $packageBytes, string $slide, string $region): string {
		foreach ($this->parts(packageBytes: $packageBytes) as $part) {
			if ($part['slide'] === $slide && $part['region'] === $region) {
				return $part['path'];
			}
		}

		throw new RuntimeException(
			sprintf('%s has no "%s" region in this deck.', $slide, $region)
		);
	}//end pathFor()
}//end class
