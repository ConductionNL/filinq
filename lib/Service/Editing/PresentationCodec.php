<?php

/**
 * Filinq PresentationCodec
 *
 * Reads and edits presentation shapes, dispatched to the package family.
 *
 * 🔴 Slides are addressed by ID and shapes by ID — never by ordinal position.
 * Slide order is not a stable identity: an agent told to edit "slide 4" and a
 * human who reordered the deck disagree about which slide that is, and the
 * agent edits the wrong one confidently.
 *
 * ⚠️ Speaker notes are a DISTINCT region, not a shape that happens to sit
 * nearby. Drafting talking points must not be able to alter what is on screen,
 * so `region` is explicit on every read and every write, and defaults to the
 * slide rather than to whatever was found first.
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
 * Cell-free, id-addressed editing for presentations.
 */
class PresentationCodec {

	/**
	 * The regions a shape can live in.
	 *
	 * @var array<int, string>
	 */
	public const REGIONS = ['slide', 'notes'];

	/**
	 * The most shapes one call may write.
	 *
	 * @var int
	 */
	public const MAX_SHAPES_PER_CALL = 100;

	/**
	 * The per-family codecs, tried in order.
	 *
	 * @var array<int, PresentationFamilyCodec>
	 */
	private array $families;

	/**
	 * Constructor.
	 *
	 * @param PackagePartIo                            $io       Package reader/writer.
	 * @param array<int, PresentationFamilyCodec>|null $families Optional override, for tests.
	 */
	public function __construct(PackagePartIo $io, ?array $families = null) {
		$this->families = ($families ?? [new PptxPresentationCodec($io), new OdpPresentationCodec($io)]);
	}//end __construct()

	/**
	 * Whether this codec handles an extension.
	 *
	 * @param string $extension The lower-case file extension.
	 *
	 * @return bool True when handled.
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-2.1
	 */
	public function supports(string $extension): bool {
		return ($this->codecFor(extension: $extension) !== null);
	}//end supports()

	/**
	 * Read every text-bearing shape.
	 *
	 * @param string $packageBytes The package.
	 * @param string $extension    The file extension.
	 *
	 * @return array<int, array{slide: string, shape: string, region: string, text: string}> The shapes.
	 *
	 * @throws RuntimeException When the extension is not a presentation.
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-2.1
	 */
	public function readShapes(string $packageBytes, string $extension): array {
		return $this->requireCodec(extension: $extension)->readShapes(packageBytes: $packageBytes);
	}//end readShapes()

	/**
	 * Replace the text of addressed shapes.
	 *
	 * @param string $packageBytes The package.
	 * @param string $extension    The file extension.
	 * @param array  $edits        Each `{slide, shape, text, region?}`.
	 *
	 * @return array{bytes: string, applied: array<int, string>} The result.
	 *
	 * @throws RuntimeException When an edit is refused.
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#2-presentation
	 */
	public function applyShapeEdits(string $packageBytes, string $extension, array $edits): array {
		if ($edits === []) {
			throw new RuntimeException('At least one shape edit is required.');
		}

		if (count($edits) > self::MAX_SHAPES_PER_CALL) {
			throw new RuntimeException(
				sprintf(
					'This call writes %d shapes; the limit is %d. The call is refused rather than '
					. 'truncated — a partial write reports success for edits it never made.',
					count($edits),
					self::MAX_SHAPES_PER_CALL
				)
			);
		}

		$codec = $this->requireCodec(extension: $extension);
		$bytes = $packageBytes;
		$applied = [];

		foreach ($edits as $position => $edit) {
			$slide = (string)($edit['slide'] ?? '');
			$shape = (string)($edit['shape'] ?? '');
			$region = strtolower((string)($edit['region'] ?? 'slide'));

			if ($slide === '' || $shape === '') {
				throw new RuntimeException(
					sprintf('Edit %d: both a slide id and a shape id are required.', ((int)$position + 1))
				);
			}

			// An unknown region is refused rather than quietly treated as the
			// slide: "notez" must not silently publish talking points on screen.
			if (in_array($region, self::REGIONS, true) === false) {
				throw new RuntimeException(
					sprintf(
						'Edit %d: unknown region "%s". Supported: %s.',
						((int)$position + 1),
						$region,
						implode(', ', self::REGIONS)
					)
				);
			}

			$bytes = $codec->writeShape(
				packageBytes: $bytes,
				slide: $slide,
				shape: $shape,
				region: $region,
				text: (string)($edit['text'] ?? '')
			);

			$applied[] = $slide . '/' . $region . '!' . $shape;
		}//end foreach

		return ['bytes' => $bytes, 'applied' => $applied];
	}//end applyShapeEdits()

	/**
	 * The codec for an extension, or null.
	 *
	 * @param string $extension The file extension.
	 *
	 * @return PresentationFamilyCodec|null The codec, or null.
	 */
	private function codecFor(string $extension): ?PresentationFamilyCodec {
		foreach ($this->families as $codec) {
			if ($codec->supports(extension: $extension) === true) {
				return $codec;
			}
		}

		return null;
	}//end codecFor()

	/**
	 * The codec for an extension, refusing by name when absent.
	 *
	 * @param string $extension The file extension.
	 *
	 * @return PresentationFamilyCodec The codec.
	 *
	 * @throws RuntimeException When unsupported.
	 */
	private function requireCodec(string $extension): PresentationFamilyCodec {
		$codec = $this->codecFor(extension: $extension);
		if ($codec === null) {
			throw new RuntimeException(
				sprintf('"%s" is not a presentation this codec edits. Supported: pptx, odp.', $extension)
			);
		}

		return $codec;
	}//end requireCodec()
}//end class
