<?php

/**
 * DocuDesk BlockStyleCodec
 *
 * Applies style and layout properties to an anchored paragraph, in place.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 *
 * @category Service
 * @package  OCA\DocuDesk\Service\Editing
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git-id>
 *
 * @link https://docudesk.app
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service\Editing;

use RuntimeException;

/**
 * Style and layout for one paragraph's markup.
 *
 * Operates on a single `<w:p>` span's markup and returns rewritten markup. It does
 * not touch the package, does not know about anchors, and does not decide which
 * paragraph is being styled — {@see PackageCodec} owns all of that. Keeping this
 * class ignorant of the package is what lets it be tested against a string.
 *
 * ## Why OOXML only, said out loud
 *
 * OOXML carries direct formatting INSIDE the paragraph: `<w:pPr>` for paragraph
 * properties, `<w:rPr>` inside each `<w:r>` for run properties. Rewriting the span
 * is therefore sufficient and nothing outside it changes.
 *
 * ODF does not work that way. `text:p` carries only a `text:style-name` pointing at
 * a `<style:style>` defined in `<office:automatic-styles>` — a different region of
 * `content.xml` — and a heading is a different ELEMENT (`text:h`), not a styled
 * paragraph. Supporting ODF properly means minting automatic styles, guaranteeing
 * name uniqueness against existing ones, and in the heading case rewriting the
 * element the anchor scanner keys on.
 *
 * That is real work and it is not done here. What matters is that it is REFUSED BY
 * NAME rather than silently ignored: an ODF style request that returned the markup
 * unchanged would report success and change nothing, which is the failure mode this
 * codebase keeps meeting. `supports()` answers honestly and
 * {@see applyStyle()} throws with the reason.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service\Editing
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git-id>
 *
 * @link https://docudesk.app
 *
 * @spec openspec/specs/document-rich-editing/spec.md
 */
class BlockStyleCodec {

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
	 * Style keys this codec understands.
	 *
	 * @var array<int, string>
	 */
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
	 * Whether style can be applied to this package family.
	 *
	 * @param string $format The package family constant.
	 *
	 * @return bool True when supported.
	 *
	 * @spec openspec/specs/document-rich-editing/spec.md
	 */
	public function supports(string $format): bool {
		return ($format === PackageCodec::FORMAT_OOXML);
	}//end supports()

	/**
	 * Apply style properties to one paragraph's markup.
	 *
	 * @param string $markup The paragraph markup.
	 * @param array  $style  The style properties.
	 * @param string $format The package family constant.
	 *
	 * @return string The rewritten markup.
	 *
	 * @throws RuntimeException When the format is unsupported or a property is unknown.
	 *
	 * @spec openspec/specs/document-rich-editing/spec.md
	 */
	public function applyStyle(string $markup, array $style, string $format): string {
		if ($this->supports(format: $format) === false) {
			throw new RuntimeException(
				'Style and layout can only be applied to OOXML (.docx) documents. '
				. 'ODF direct formatting needs an automatic style minted in content.xml, and an ODF '
				. 'heading is a different element (text:h) rather than a styled paragraph; neither is '
				. 'implemented. Text edits and metadata DO work on .odt.'
			);
		}

		$this->assertKnownKeys(style: $style);

		$markup = $this->applyParagraphProperties(markup: $markup, style: $style);

		return $this->applyRunProperties(markup: $markup, style: $style);
	}//end applyStyle()

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

		$alignment = ($style['alignment'] ?? null);
		if ($alignment !== null && isset(self::ALIGNMENTS[(string)$alignment]) === false) {
			throw new RuntimeException(
				sprintf(
					'Unknown alignment "%s". Supported: %s.',
					(string)$alignment,
					implode(', ', array_keys(self::ALIGNMENTS))
				)
			);
		}

		$heading = ($style['heading'] ?? null);
		if ($heading !== null && (is_numeric($heading) === false || (int)$heading < 0 || (int)$heading > 9)) {
			throw new RuntimeException('Heading level must be between 0 (body text) and 9.');
		}
	}//end assertKnownKeys()

	/**
	 * Apply the paragraph-level properties.
	 *
	 * @param string $markup The paragraph markup.
	 * @param array  $style  The style properties.
	 *
	 * @return string The rewritten markup.
	 */
	private function applyParagraphProperties(string $markup, array $style): string {
		$properties = '';
		$removals   = [];

		if (isset($style['heading']) === true) {
			$level = (int)$style['heading'];
			if ($level > 0) {
				$properties .= sprintf('<w:pStyle w:val="Heading%d"/>', $level);
			} else {
				// Level 0 means body text. This is a REMOVAL, not an absence: an
				// early return here would leave an existing <w:pStyle> in place and
				// report the restyle as applied while changing nothing — which is
				// precisely what the first version of this method did.
				$removals[] = 'w:pStyle';
			}
		}

		if (isset($style['list']) === true && (bool)$style['list'] === false) {
			$removals[] = 'w:numPr';
		}

		if (isset($style['pageBreakBefore']) === true && (bool)$style['pageBreakBefore'] === false) {
			$removals[] = 'w:pageBreakBefore';
		}

		if (isset($style['list']) === true && (bool)$style['list'] === true) {
			$properties .= '<w:numPr><w:ilvl w:val="0"/><w:numId w:val="1"/></w:numPr>';
		}

		if (isset($style['pageBreakBefore']) === true && (bool)$style['pageBreakBefore'] === true) {
			$properties .= '<w:pageBreakBefore/>';
		}

		if (isset($style['alignment']) === true) {
			$properties .= sprintf('<w:jc w:val="%s"/>', self::ALIGNMENTS[(string)$style['alignment']]);
		}

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
