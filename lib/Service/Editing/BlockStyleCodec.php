<?php

/**
 * Filinq BlockStyleCodec
 *
 * Applies style and layout properties to an anchored paragraph, in place.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 *
 * @category Service
 * @package  OCA\Filinq\Service\Editing
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

namespace OCA\Filinq\Service\Editing;

use RuntimeException;

/**
 * Style and layout for one block, dispatched to its package family.
 *
 * This class owns the parts that must NOT differ between families: the style
 * vocabulary, and the validation of a caller's request against it. Two copies
 * of "which keys are legal" is how two codecs drift into accepting different
 * things, and the caller here is a language model, which will misspell keys.
 *
 * The parts that genuinely do differ live behind
 * {@see BlockStyleFamilyCodec}. OOXML carries direct formatting INSIDE the
 * paragraph, so rewriting the span is sufficient. ODF has no direct formatting
 * at all: a block points at an automatic style defined elsewhere in
 * `content.xml`, and a heading is a different ELEMENT (`text:h`) rather than a
 * styled paragraph. Holding both in one class measured at a complexity of 65
 * against a threshold of 50 — the number saying these were two implementations
 * sharing a name.
 *
 * It knows nothing about packages, anchors or which block is being styled;
 * {@see PackageCodec} owns all of that. That ignorance is what lets every
 * family codec be tested against a plain string.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Editing
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git-id>
 *
 * @link https://filinq.app
 *
 * @spec openspec/specs/document-rich-editing/spec.md
 */
class BlockStyleCodec {

	/**
	 * Style keys this codec understands.
	 *
	 * @var array<int, string>
	 */
	/**
	 * The alignment vocabulary callers may use.
	 *
	 * The NAMES are shared; the spelling each family emits is not. OOXML writes
	 * `both` for justify and ODF writes `start`/`end` for left/right, so the
	 * mapping belongs to each codec while the accepted vocabulary — the thing a
	 * caller is validated against — belongs here. One list, validated once.
	 *
	 * @var array<int, string>
	 */
	public const ALIGNMENTS = [
		'left',
		'center',
		'right',
		'justify',
	];

	public const STYLE_KEYS = [
		'bold',
		'italic',
		'underline',
		'alignment',
		'heading',
		'list',
		'pageBreakBefore',
	];

	/**
	 * The per-family codecs, tried in order.
	 *
	 * @var array<int, BlockStyleFamilyCodec>
	 */
	private array $families;

	/**
	 * Constructor.
	 *
	 * @param array<int, BlockStyleFamilyCodec>|null $families Optional override, for tests.
	 */
	public function __construct(?array $families = null) {
		$this->families = ($families ?? [new OoxmlBlockStyleCodec(), new OdfBlockStyleCodec()]);
	}//end __construct()

	/**
	 * Whether style can be applied to this package family.
	 *
	 * @param string $format The package family constant.
	 *
	 * @return bool True when a codec handles it.
	 *
	 * @spec openspec/specs/document-rich-editing/spec.md
	 */
	public function supports(string $format): bool {
		return ($this->codecFor(format: $format) !== null);
	}//end supports()

	/**
	 * Apply style properties to one block's markup.
	 *
	 * Validation lives HERE rather than in each family: both share one style
	 * vocabulary, and two copies of "which keys are legal" is how they drift
	 * into accepting different things.
	 *
	 * @param string $markup    The block markup.
	 * @param array  $style     The style properties.
	 * @param string $format    The package family constant.
	 * @param string $styleName A unique name a family may mint a style under.
	 *
	 * @return array{markup: string, automaticStyle: string|null} The rewritten block and any style to inject.
	 *
	 * @throws RuntimeException When the format is unsupported or a property is unknown.
	 *
	 * @spec openspec/specs/document-rich-editing/spec.md
	 */
	public function applyStyle(string $markup, array $style, string $format, string $styleName = ''): array {
		$codec = $this->codecFor(format: $format);
		if ($codec === null) {
			throw new RuntimeException(
				sprintf('Style and layout are not supported for the "%s" package family.', $format)
			);
		}

		$this->assertKnownKeys(style: $style);

		return $codec->applyStyle(markup: $markup, style: $style, styleName: $styleName);
	}//end applyStyle()

	/**
	 * The codec handling a package family, or null.
	 *
	 * @param string $format The package family constant.
	 *
	 * @return BlockStyleFamilyCodec|null The codec, or null when unhandled.
	 */
	private function codecFor(string $format): ?BlockStyleFamilyCodec {
		foreach ($this->families as $codec) {
			if ($codec->supports(format: $format) === true) {
				return $codec;
			}
		}

		return null;
	}//end codecFor()

	/**
	 * Reject unknown style keys.
	 *
	 * A misspelled key that was silently ignored would report success and change
	 * nothing — and the caller here is a language model, which will misspell keys.
	 *
	 * @param array $style The style properties.
	 *
	 * @return void
	 *
	 * @throws RuntimeException When a key is not understood.
	 */
	private function assertKnownKeys(array $style): void {
		if ($style === []) {
			throw new RuntimeException('At least one style property is required.');
		}

		foreach (array_keys($style) as $key) {
			if (in_array((string)$key, self::STYLE_KEYS, true) === false) {
				throw new RuntimeException(
					sprintf(
						'Unknown style property "%s". Supported: %s.',
						(string)$key,
						implode(', ', self::STYLE_KEYS)
					)
				);
			}
		}

		$this->assertKnownValues(style: $style);
	}//end assertKnownKeys()

	/**
	 * Reject values that name something the codec cannot express.
	 *
	 * Split from {@see assertKnownKeys()} to keep both under phpmd's complexity
	 * threshold; the behaviour is unchanged.
	 *
	 * @param array $style The style properties.
	 *
	 * @return void
	 *
	 * @throws RuntimeException When a value is not understood.
	 */
	private function assertKnownValues(array $style): void {
		$alignment = ($style['alignment'] ?? null);
		if ($alignment !== null && in_array((string)$alignment, self::ALIGNMENTS, true) === false) {
			throw new RuntimeException(
				sprintf(
					'Unknown alignment "%s". Supported: %s.',
					(string)$alignment,
					implode(', ', self::ALIGNMENTS)
				)
			);
		}

		$heading = ($style['heading'] ?? null);
		if ($heading === null) {
			return;
		}

		if (is_numeric($heading) === false || (int)$heading < 0 || (int)$heading > 9) {
			throw new RuntimeException('Heading level must be between 0 (body text) and 9.');
		}
	}//end assertKnownValues()
}//end class
