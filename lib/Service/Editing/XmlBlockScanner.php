<?php

/**
 * XML Block Scanner
 *
 * Locates the byte spans of an element inside a document part, honouring
 * nesting. Split out of {@see PackageCodec} because it is the one genuinely
 * intricate piece of that class and it has nothing to do with packages, ZIPs or
 * documents -- it is a string scan.
 *
 * A regular expression cannot do this correctly. OOXML nests `w:p` inside text
 * boxes (`w:txbxContent`), so a non-greedy match closes the paragraph on the
 * wrong tag and silently corrupts the package. Depth counting is the cheapest
 * thing that is actually right.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service\Editing
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/changes/document-editing-tools/tasks.md#task-2-4
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service\Editing;

/**
 * Finds element spans in a document part.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service\Editing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/specs/document-editing/spec.md#requirement-untouched-parts-of-a-document-package-survive-an-edit-unchanged
 */
class XmlBlockScanner {

	/**
	 * Characters that may follow an element name in a well-formed start tag.
	 *
	 * Without this check, `<w:t` matches `<w:tab/>` and `<w:tbl>`.
	 *
	 * @var array<int, string>
	 */
	private const NAME_DELIMITERS = [' ', '>', '/', "\t", "\n", "\r"];

	/**
	 * Locate every top-level occurrence of ANY of several elements, in document
	 * order.
	 *
	 * 🔴 One block model, several element names. ODF writes a paragraph as
	 * `text:p` and a HEADING as `text:h` — a different element, not a styled
	 * paragraph. Scanning only `text:p` therefore made every heading in an
	 * `.odt` invisible: measured on a four-block document, readDocument
	 * reported three, and an agent asked to edit the heading was told its
	 * anchor did not exist for text plainly on the page.
	 *
	 * Nested occurrences are still not returned separately, and the merged
	 * result is sorted by offset so callers keep the descending-rewrite
	 * guarantee that stops one edit moving another's offsets.
	 *
	 * @param string             $xml  The part XML.
	 * @param array<int, string> $tags The element names that count as a block.
	 *
	 * @return array<int, array{0: int, 1: int}> Offset/length pairs, in document order.
	 *
	 * @spec openspec/specs/document-editing/spec.md#requirement-untouched-parts-of-a-document-package-survive-an-edit-unchanged
	 */
	public function spansForTags(string $xml, array $tags): array {
		$spans = [];
		foreach ($tags as $tag) {
			foreach ($this->spans(xml: $xml, tag: $tag) as $span) {
				$spans[] = $span;
			}
		}

		usort($spans, static fn (array $a, array $b): int => ($a[0] <=> $b[0]));

		return $spans;
	}//end spansForTags()

	/**
	 * Locate every top-level occurrence of ONE element, in document order.
	 *
	 * Nested occurrences are NOT returned separately: an outer element's span
	 * already contains them, and returning both would let one edit rewrite a
	 * range another edit is still holding an offset into.
	 *
	 * @param string $xml The part XML.
	 * @param string $tag The element name.
	 *
	 * @return array<int, array{0: int, 1: int}> Offset/length pairs.
	 *
	 * @spec openspec/specs/document-editing/spec.md#requirement-untouched-parts-of-a-document-package-survive-an-edit-unchanged
	 */
	public function spans(string $xml, string $tag): array {
		$spans = [];
		$open = '<' . $tag;
		$close = '</' . $tag . '>';
		$openLength = strlen($open);
		$offset = 0;

		while (($start = strpos($xml, $open, $offset)) !== false) {
			if ($this->isElementStart(xml: $xml, position: ($start + $openLength)) === false) {
				$offset = ($start + $openLength);
				continue;
			}

			$tagEnd = strpos($xml, '>', $start);
			if ($tagEnd === false) {
				break;
			}

			if ($xml[($tagEnd - 1)] === '/') {
				$spans[] = [$start, (($tagEnd + 1) - $start)];
				$offset = ($tagEnd + 1);
				continue;
			}

			$end = $this->matchingClose(
				xml: $xml,
				cursor: ($tagEnd + 1),
				open: $open,
				close: $close,
				openLength: $openLength
			);

			if ($end === null) {
				break;
			}

			$spans[] = [$start, ($end - $start)];
			$offset = $end;
		}//end while

		return $spans;

	}//end spans()

	/**
	 * Walk forward to the close tag that matches an already-opened element.
	 *
	 * @param string $xml The part XML.
	 * @param int $cursor The offset just past the opening tag.
	 * @param string $open The `<name` prefix.
	 * @param string $close The `</name>` string.
	 * @param int $openLength The length of `$open`.
	 *
	 * @return int|null The offset just past the matching close tag, or null when the XML is malformed.
	 */
	private function matchingClose(string $xml, int $cursor, string $open, string $close, int $openLength): ?int {
		$depth = 1;
		$closeLength = strlen($close);

		while ($depth > 0) {
			$nextClose = strpos($xml, $close, $cursor);
			if ($nextClose === false) {
				return null;
			}

			$nextOpen = strpos($xml, $open, $cursor);
			if ($nextOpen === false || $nextOpen > $nextClose) {
				$depth--;
				$cursor = ($nextClose + $closeLength);
				continue;
			}

			$tagEnd = strpos($xml, '>', $nextOpen);
			if ($tagEnd === false) {
				return null;
			}

			if ($this->opensAnotherLevel(xml: $xml, nameEnd: ($nextOpen + $openLength), tagEnd: $tagEnd) === true) {
				$depth++;
			}

			$cursor = ($tagEnd + 1);
		}//end while

		return $cursor;

	}//end matchingClose()

	/**
	 * Whether a start tag at this position opens a nested level of the same element.
	 *
	 * A self-closing tag does not: it opens and closes in one place.
	 *
	 * @param string $xml The part XML.
	 * @param int $nameEnd The offset just past the element name.
	 * @param int $tagEnd The offset of the tag's closing angle bracket.
	 *
	 * @return bool True when depth increases here.
	 */
	private function opensAnotherLevel(string $xml, int $nameEnd, int $tagEnd): bool {
		if ($this->isElementStart(xml: $xml, position: $nameEnd) === false) {
			return false;
		}

		return ($xml[($tagEnd - 1)] !== '/');

	}//end opensAnotherLevel()

	/**
	 * Whether the character at a position legitimately ends an element name.
	 *
	 * @param string $xml The part XML.
	 * @param int $position The offset just past the element name.
	 *
	 * @return bool True when the name ends here.
	 */
	private function isElementStart(string $xml, int $position): bool {
		return in_array(($xml[$position] ?? ''), self::NAME_DELIMITERS, true);

	}//end isElementStart()
}//end class
