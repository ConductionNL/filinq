<?php

/**
 * DocuDesk OdfBlockStyleCodec
 *
 * Block style for the ODF family (`.odt`).
 *
 * 🔴 ODF has NO direct formatting on a block. A property is expressed by
 * pointing the block at an AUTOMATIC STYLE defined elsewhere in `content.xml`,
 * so this codec returns a definition alongside the rewritten markup and the
 * caller injects it. That indirection is why ODF styling was refused outright
 * for a long time, and it is the whole of the difficulty.
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
 * @spec openspec/specs/document-rich-editing/spec.md
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service\Editing;

use RuntimeException;

/**
 * Applies block style to ODF packages.
 */
class OdfBlockStyleCodec implements BlockStyleFamilyCodec {

	/**
	 * ODF's spelling of the same alignment vocabulary.
	 *
	 * ODF uses XSL-FO names: `start`/`end` rather than `left`/`right`, and
	 * `justify` rather than OOXML's `both`. Reusing the OOXML map here would
	 * emit values a reader silently ignores — a restyle that reports success
	 * and changes nothing.
	 *
	 * @var array<string, string>
	 */
	private const ODF_ALIGNMENTS = [
		'left'    => 'start',
		'center'  => 'center',
		'right'   => 'end',
		'justify' => 'justify',
	];

	/**
	 * Whether this codec handles the given package family.
	 *
	 * @param string $format The package family constant.
	 *
	 * @return bool True for ODF.
	 *
	 * @spec openspec/specs/document-rich-editing/spec.md
	 */
	public function supports(string $format): bool {
		return ($format === PackageCodec::FORMAT_ODF);
	}//end supports()

	/**
	 * Apply style to an ODF block.
	 *
	 * ⚠️ ODF has no direct formatting on a paragraph. A property is expressed by
	 * pointing the block at an AUTOMATIC STYLE defined in `content.xml`, so this
	 * returns the definition alongside the rewritten markup and the caller
	 * injects it. That indirection is the reason ODF styling was refused
	 * outright for so long, and it is the whole of the difficulty.
	 *
	 * 🔴 A heading is `text:h`, not a styled `text:p`, so `heading` REWRITES THE
	 * ELEMENT. That is only safe because the block scanner spans both names —
	 * before it did, converting a paragraph to a heading would have made the
	 * block vanish from the next read.
	 *
	 * @param string $markup    The block markup.
	 * @param array  $style     The style properties.
	 * @param string $styleName The automatic style name to mint.
	 *
	 * @return array{markup: string, automaticStyle: string|null} The rewritten block and its style.
	 *
	 * @throws RuntimeException When a property has no ODF expression here.
	 *
	 * @spec openspec/specs/document-rich-editing/spec.md
	 */
	public function applyStyle(string $markup, array $style, string $styleName): array {
		if (($style['list'] ?? false) === true) {
			throw new RuntimeException(
				'`list` is not supported on .odt. An ODF list is a <text:list> element WRAPPING the '
				. 'paragraph, not a property of it, so turning one on restructures the document rather '
				. 'than restyling a block. Every other style key works on .odt.'
			);
		}

		$markup = $this->applyOdfHeading(markup: $markup, style: $style);

		$paragraphProperties = $this->odfParagraphProperties(style: $style);
		$textProperties = $this->odfTextProperties(style: $style);

		if ($paragraphProperties === '' && $textProperties === '') {
			return ['markup' => $markup, 'automaticStyle' => null];
		}

		$definition = sprintf('<style:style style:name="%s" style:family="paragraph">', $styleName);
		if ($paragraphProperties !== '') {
			$definition .= sprintf('<style:paragraph-properties%s/>', $paragraphProperties);
		}

		if ($textProperties !== '') {
			$definition .= sprintf('<style:text-properties%s/>', $textProperties);
		}

		$definition .= '</style:style>';

        return [
			'markup' => $this->pointAtStyle(markup: $markup, styleName: $styleName),
			'automaticStyle' => $definition,
		];
	}//end applyStyle()

	/**
	 * The ODF paragraph-level properties for a style.
	 *
	 * @param array $style The style properties.
	 *
	 * @return string The attribute string, empty when none apply.
	 */
	private function odfParagraphProperties(array $style): string {
		$properties = '';

		if (isset($style['alignment']) === true) {
			$properties .= sprintf(' fo:text-align="%s"', self::ODF_ALIGNMENTS[(string)$style['alignment']]);
		}

		if (($style['pageBreakBefore'] ?? false) === true) {
			$properties .= ' fo:break-before="page"';
		}

		return $properties;
	}//end odfParagraphProperties()

	/**
	 * The ODF text-level properties for a style.
	 *
	 * Each property is written whenever the key is PRESENT, including when it
	 * is false: switching bold off is `fo:font-weight="normal"`, not the
	 * absence of the attribute. Omitting it would leave the inherited value in
	 * place and report the restyle as applied while changing nothing.
	 *
	 * @param array $style The style properties.
	 *
	 * @return string The attribute string, empty when none apply.
	 */
	private function odfTextProperties(array $style): string {
		$properties = '';

		if (array_key_exists('bold', $style) === true) {
			$weight = 'normal';
			if ($style['bold'] === true) {
				$weight = 'bold';
			}

			$properties .= sprintf(' fo:font-weight="%s"', $weight);
		}

		if (array_key_exists('italic', $style) === true) {
			$posture = 'normal';
			if ($style['italic'] === true) {
				$posture = 'italic';
			}

			$properties .= sprintf(' fo:font-style="%s"', $posture);
		}

		if (array_key_exists('underline', $style) === true) {
			$underline = 'none';
			if ($style['underline'] === true) {
				$underline = 'solid';
			}

			$properties .= sprintf(' style:text-underline-style="%s"', $underline);
		}

		return $properties;
	}//end odfTextProperties()

	/**
	 * Convert between `text:p` and `text:h` for the `heading` property.
	 *
	 * @param string $markup The block markup.
	 * @param array  $style  The style properties.
	 *
	 * @return string The rewritten markup.
	 */
	private function applyOdfHeading(string $markup, array $style): string {
		if (isset($style['heading']) === false) {
			return $markup;
		}

		$level = (int)$style['heading'];

		if ($level > 0) {
			$markup = preg_replace('/^<text:p\b/', '<text:h', $markup, 1);
			$markup = preg_replace('/<\/text:p>$/', '</text:h>', $markup, 1);
			$markup = preg_replace('/\s+text:outline-level="\d+"/', '', $markup, 1);

			return preg_replace(
				'/^<text:h\b/',
				sprintf('<text:h text:outline-level="%d"', $level),
				$markup,
				1
			);
		}

		// A heading of 0 demotes back to a paragraph. Switching a property off is a
		// removal, not an absence — leaving text:h in place would report the
		// restyle as applied and change nothing.
		$markup = preg_replace('/\s+text:outline-level="\d+"/', '', $markup, 1);
		$markup = preg_replace('/^<text:h\b/', '<text:p', $markup, 1);

		return preg_replace('/<\/text:h>$/', '</text:p>', $markup, 1);
	}//end applyOdfHeading()

	/**
	 * Point a block at an automatic style, replacing any existing reference.
	 *
	 * @param string $markup    The block markup.
	 * @param string $styleName The automatic style name.
	 *
	 * @return string The rewritten markup.
	 */
	private function pointAtStyle(string $markup, string $styleName): string {
		$stripped = preg_replace('/\s+text:style-name="[^"]*"/', '', $markup, 1);

		return preg_replace(
			'/^<(text:[ph])\b/',
			sprintf('<$1 text:style-name="%s"', $styleName),
			$stripped,
			1
		);
	}//end pointAtStyle()
}//end class
