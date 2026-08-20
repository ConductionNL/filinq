<?php

/**
 * DocuDesk OoxmlBlockStyleCodec
 *
 * Block style for the OOXML family (`.docx`).
 *
 * OOXML writes style as properties INSIDE the paragraph — `<w:pPr>` for the
 * block and `<w:rPr>` on every run — so a styled block is self-contained and
 * this codec never asks the caller to inject anything document-level. That is
 * the whole difference from ODF, and the reason the two are separate classes.
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

/**
 * Applies block style to OOXML packages.
 */
class OoxmlBlockStyleCodec implements BlockStyleFamilyCodec {

	/**
	 * Paragraph alignment values, mapped to their OOXML `w:jc` value.
	 *
	 * @var array<string, string>
	 */
	private const ALIGNMENTS = [
		'left'    => 'left',
		'center'  => 'center',
		'right'   => 'right',
		'justify' => 'both',
	];

	/**
	 * Whether this codec handles the given package family.
	 *
	 * @param string $format The package family constant.
	 *
	 * @return bool True for OOXML.
	 *
	 * @spec openspec/specs/document-rich-editing/spec.md
	 */
	public function supports(string $format): bool {
		return ($format === PackageCodec::FORMAT_OOXML);
	}//end supports()

	/**
	 * Apply style properties to one paragraph's markup.
	 *
	 * @param string $markup    The paragraph markup.
	 * @param array  $style     The style properties.
	 * @param string $styleName Unused: OOXML style is inline, so nothing is minted.
	 *
	 * @return array{markup: string, automaticStyle: string|null} The rewritten block; never a definition.
	 *
	 * @spec openspec/specs/document-rich-editing/spec.md
	 */
	public function applyStyle(string $markup, array $style, string $styleName): array {
		$markup = $this->applyParagraphProperties(markup: $markup, style: $style);

		return ['markup' => $this->applyRunProperties(markup: $markup, style: $style), 'automaticStyle' => null];
	}//end applyStyle()

	/**
	 * Apply the paragraph-level properties.
	 *
	 * @param string $markup The paragraph markup.
	 * @param array  $style  The style properties.
	 *
	 * @return string The rewritten markup.
	 */
	private function applyParagraphProperties(string $markup, array $style): string {
		$properties = $this->paragraphAdditions(style: $style);
		$removals   = $this->paragraphRemovals(style: $style);

		if ($properties === '' && $removals === []) {
			return $markup;
		}

		return $this->mergeParagraphProperties(
			markup: $markup,
			properties: $properties,
			removals: $removals
		);
	}//end applyParagraphProperties()

	/**
	 * The paragraph properties this style adds.
	 *
	 * @param array $style The style properties.
	 *
	 * @return string The property markup.
	 */
	private function paragraphAdditions(array $style): string {
		$properties = '';

		$heading = (int)($style['heading'] ?? 0);
		if ($heading > 0) {
			$properties .= sprintf('<w:pStyle w:val="Heading%d"/>', $heading);
		}

		if (($style['list'] ?? false) === true) {
			$properties .= '<w:numPr><w:ilvl w:val="0"/><w:numId w:val="1"/></w:numPr>';
		}

		if (($style['pageBreakBefore'] ?? false) === true) {
			$properties .= '<w:pageBreakBefore/>';
		}

		if (isset($style['alignment']) === true) {
			$properties .= sprintf('<w:jc w:val="%s"/>', self::ALIGNMENTS[(string)$style['alignment']]);
		}

		return $properties;
	}//end paragraphAdditions()

	/**
	 * The paragraph properties this style REMOVES.
	 *
	 * Switching a property off is a removal, not an absence. Treating it as
	 * "nothing to add" leaves the existing element in place and reports the
	 * restyle as applied while changing nothing — which is exactly what the first
	 * version of this codec did with `heading: 0`.
	 *
	 * @param array $style The style properties.
	 *
	 * @return array<int, string> The element names to remove.
	 */
	private function paragraphRemovals(array $style): array {
		$removals = [];

		if (isset($style['heading']) === true && (int)$style['heading'] === 0) {
			$removals[] = 'w:pStyle';
		}

		if (isset($style['list']) === true && (bool)$style['list'] === false) {
			$removals[] = 'w:numPr';
		}

		if (isset($style['pageBreakBefore']) === true && (bool)$style['pageBreakBefore'] === false) {
			$removals[] = 'w:pageBreakBefore';
		}

		return $removals;
	}//end paragraphRemovals()

	/**
	 * Merge properties into the paragraph's `<w:pPr>`, creating it when absent.
	 *
	 * Existing properties of the SAME name are replaced; unrelated ones survive.
	 * Wholesale replacement of `<w:pPr>` would silently drop spacing, indentation
	 * and numbering the user set by hand — the class of loss ADR-087 §2 warns
	 * about, and one no test would notice.
	 *
	 * @param string        $markup     The paragraph markup.
	 * @param string        $properties The properties to merge.
	 * @param array<string> $removals   Element names to remove outright.
	 *
	 * @return string The rewritten markup.
	 */
	private function mergeParagraphProperties(string $markup, string $properties, array $removals = []): string {
		if (preg_match('#<w:pPr>(.*?)</w:pPr>#s', $markup, $matches) === 1) {
			$existing = $this->dropSameNamed(existing: $matches[1], incoming: $properties);
			$existing = $this->dropNamed(existing: $existing, names: $removals);

			return (string)preg_replace(
				'#<w:pPr>.*?</w:pPr>#s',
				'<w:pPr>' . $existing . $properties . '</w:pPr>',
				$markup,
				1
			);
		}

		if ($properties === '') {
			// Nothing to add and no <w:pPr> to clean: creating an empty one would
			// be noise in the document.
			return $markup;
		}

		// `<w:pPr>` must be the FIRST child of `<w:p>`; Word rejects it elsewhere.
		return (string)preg_replace(
			'#(<w:p(?:\s[^>]*)?>)#',
			'$1<w:pPr>' . $properties . '</w:pPr>',
			$markup,
			1
		);
	}//end mergeParagraphProperties()

	/**
	 * Remove existing properties whose element name is being set again.
	 *
	 * @param string $existing The existing property markup.
	 * @param string $incoming The incoming property markup.
	 *
	 * @return string The retained existing markup.
	 */
	private function dropSameNamed(string $existing, string $incoming): string {
		preg_match_all('#<(w:[A-Za-z]+)#', $incoming, $matches);

		return $this->dropNamed(existing: $existing, names: array_unique($matches[1]));
	}//end dropSameNamed()

	/**
	 * Remove named elements from a property block.
	 *
	 * @param string        $existing The existing property markup.
	 * @param array<string> $names    The element names to remove.
	 *
	 * @return string The retained markup.
	 */
	private function dropNamed(string $existing, array $names): string {
		foreach ($names as $name) {
			$quoted   = preg_quote($name, '#');
			$existing = (string)preg_replace('#<' . $quoted . '(?:\s[^>]*)?/>#', '', $existing);
			$existing = (string)preg_replace('#<' . $quoted . '(?:\s[^>]*)?>.*?</' . $quoted . '>#s', '', $existing);
		}

		return $existing;
	}//end dropNamed()

	/**
	 * Apply the run-level properties to every run in the paragraph.
	 *
	 * @param string $markup The paragraph markup.
	 * @param array  $style  The style properties.
	 *
	 * @return string The rewritten markup.
	 */
	private function applyRunProperties(string $markup, array $style): string {
		$properties = '';

		foreach (['bold' => 'w:b', 'italic' => 'w:i', 'underline' => 'w:u'] as $key => $element) {
			if (isset($style[$key]) === false) {
				continue;
			}

			if ((bool)$style[$key] === false) {
				// Explicit off, not absent: `<w:b w:val="0"/>` overrides a style
				// that turns it on, which simply omitting the element would not.
				$properties .= sprintf('<%s w:val="0"/>', $element);
				continue;
			}

			if ($element === 'w:u') {
				// Underline is the odd one out: it carries a STYLE, not a flag, so
				// a bare `<w:u/>` is not "underlined".
				$properties .= '<w:u w:val="single"/>';
				continue;
			}

			$properties .= sprintf('<%s/>', $element);
		}

		if ($properties === '') {
			return $markup;
		}

		return (string)preg_replace_callback(
			'#<w:r(?:\s[^>]*)?>(.*?)</w:r>#s',
			function (array $match) use ($properties): string {
				return $this->mergeRunProperties(run: $match[0], properties: $properties);
			},
			$markup
		);
	}//end applyRunProperties()

	/**
	 * Merge run properties into one `<w:r>`, creating `<w:rPr>` when absent.
	 *
	 * @param string $run        The run markup.
	 * @param string $properties The properties to merge.
	 *
	 * @return string The rewritten run.
	 */
	private function mergeRunProperties(string $run, string $properties): string {
		if (preg_match('#<w:rPr>(.*?)</w:rPr>#s', $run, $matches) === 1) {
			$existing = $this->dropSameNamed(existing: $matches[1], incoming: $properties);

			return (string)preg_replace(
				'#<w:rPr>.*?</w:rPr>#s',
				'<w:rPr>' . $existing . $properties . '</w:rPr>',
				$run,
				1
			);
		}

		return (string)preg_replace(
			'#(<w:r(?:\s[^>]*)?>)#',
			'$1<w:rPr>' . $properties . '</w:rPr>',
			$run,
			1
		);
	}//end mergeRunProperties()
}//end class
