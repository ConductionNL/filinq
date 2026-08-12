<?php

/**
 * Custom Dictionary Match Service
 *
 * Pure, unit-pinned matcher for organisation-managed term lists
 * (`custom-dictionary-recognition`). Given a text and a set of terms it
 * returns every occurrence of every (non-blank) term according to the
 * dictionary's match mode. This class has NO Nextcloud/OpenRegister
 * dependency — it is a pure function of its inputs and the primary
 * phpunit-provable seam for this change (design.md §D2).
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use ErrorException;

/**
 * Deterministic term-matching engine for custom dictionaries.
 *
 * Match modes:
 *   - `exact`: byte-for-byte, case-sensitive.
 *   - `caseInsensitive` (default): case-folded on both sides.
 *   - `wordBoundary`: case-insensitive AND delimited by a non-word boundary
 *     (Unicode `\b` semantics) so a term does not match inside a longer word
 *     (e.g. "Berg" does not match inside "Bergen").
 *
 * Implementation note: all three modes are implemented via `preg_match_all`
 * with the `u` (Unicode) modifier — the same technique OpenRegister's own
 * `ChunkTextMatcher::buildPattern()` uses for its whole-word manual-entity
 * matching — so position offsets are produced in the same unit OpenRegister's
 * redaction pipeline already consumes elsewhere in this codebase.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
 */
class CustomDictionaryMatchService {
	/**
	 * Byte-for-byte, case-sensitive match mode.
	 *
	 * @var string
	 */
	public const MODE_EXACT = 'exact';

	/**
	 * Case-insensitive match mode (default).
	 *
	 * @var string
	 */
	public const MODE_CASE_INSENSITIVE = 'caseInsensitive';

	/**
	 * Case-insensitive, word-boundary-delimited match mode.
	 *
	 * @var string
	 */
	public const MODE_WORD_BOUNDARY = 'wordBoundary';

	/**
	 * The set of match modes this service accepts. An unrecognised mode
	 * falls back to {@see MODE_CASE_INSENSITIVE} (mirrors the schema
	 * default declared in `docudesk_register.json`).
	 *
	 * @var array<int, string>
	 */
	private const VALID_MODES = [
		self::MODE_EXACT,
		self::MODE_CASE_INSENSITIVE,
		self::MODE_WORD_BOUNDARY,
	];

	/**
	 * Find every occurrence of every (non-blank) term in `$text`.
	 *
	 * `$terms` rows carry `value` (the needle) and an optional `label`
	 * (falls back to the term's own value when absent — the dictionary-level
	 * default-label fallback is the caller's responsibility per design.md
	 * §D2, so this method never has to resolve a dictionary label itself).
	 *
	 * Overlap handling: terms are processed longest-value-first so a shorter
	 * term cannot pre-empt a longer one at an overlapping position (mirrors
	 * OpenRegister's redaction longest-needle rule). Once a position range is
	 * claimed by a match it is never claimed again by a shorter term's match.
	 * The `fuzzy` flag is accepted-and-ignored — no approximate matching in
	 * this version (design.md Open Questions).
	 *
	 * Occurrences are returned in document order. Each `value` is the literal
	 * substring found at that position, which may differ in case from the
	 * declared term under `caseInsensitive`/`wordBoundary`.
	 *
	 * @param string $text The document text to search.
	 * @param array<int, array{value: string, label?: string}> $terms Candidate terms.
	 * @param string $mode One of {@see VALID_MODES}; an
	 *                     unrecognised value is treated
	 *                     as {@see
	 *                     MODE_CASE_INSENSITIVE}.
	 *
	 * @return array<int, array{value: string, label: string, positionStart: int, positionEnd: int}>
	 *
	 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
	 */
	public function match(string $text, array $terms, string $mode): array {
		if ($text === '' || empty($terms) === true) {
			return [];
		}

		$normalizedMode = $this->normalizeMode(mode: $mode);
		$candidates = $this->buildCandidates(terms: $terms);
		if (empty($candidates) === true) {
			return [];
		}

		// Longest-term-first so a shorter term cannot pre-empt a longer one.
		// Ties keep their original relative order for deterministic output.
		usort(
			$candidates,
			static function (array $a, array $b): int {
				$lengthDiff = (mb_strlen($b['value']) <=> mb_strlen($a['value']));
				if ($lengthDiff !== 0) {
					return $lengthDiff;
				}

				return ($a['originalIndex'] <=> $b['originalIndex']);
			}
		);

		$claimed = [];
		$occurrences = [];

		foreach ($candidates as $candidate) {
			$pattern = $this->buildPattern(needle: $candidate['value'], mode: $normalizedMode);
			$rawHits = $this->findRawHits(pattern: $pattern, text: $text);

			foreach ($rawHits as $rawMatch) {
				[$matchedText, $start] = $rawMatch;
				$start = (int)$start;
				$end = ($start + strlen((string)$matchedText));

				if ($this->overlapsClaimed(start: $start, end: $end, claimed: $claimed) === true) {
					continue;
				}

				$claimed[] = [$start, $end];
				$occurrences[] = [
					'value' => (string)$matchedText,
					'label' => $candidate['label'],
					'positionStart' => $start,
					'positionEnd' => $end,
				];
			}//end foreach
		}//end foreach

		usort(
			$occurrences,
			static fn (array $a, array $b): int => ($a['positionStart'] <=> $b['positionStart'])
		);

		return $occurrences;
	}//end match()

	/**
	 * Run one term's compiled pattern over the text, returning the raw
	 * `PREG_OFFSET_CAPTURE` hits for group 0.
	 *
	 * A malformed operator-supplied term can produce a pattern PCRE refuses to
	 * compile. PHP signals that with a `preg_match_all(): Compilation failed`
	 * WARNING plus a `false` return. Rather than suppressing the warning with
	 * `@` (which also hides every unrelated diagnostic the call could raise),
	 * the warning is converted into an `ErrorException` by a narrowly scoped
	 * error handler, caught here, and turned into "this one term matched
	 * nothing" — every other term in the dictionary still gets its pass.
	 *
	 * @param string $pattern The compiled pattern from {@see buildPattern()}.
	 * @param string $text The document text to search.
	 *
	 * @return array<int, array{0: string, 1: int}> Raw `[matchedText, byteOffset]` hits.
	 */
	private function findRawHits(string $pattern, string $text): array {
		$matches = [];

		set_error_handler(
			static function (int $severity, string $message, string $file, int $line): bool {
				throw new ErrorException($message, 0, $severity, $file, $line);
			}
		);

		try {
			$count = preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE);
		} catch (ErrorException) {
			// Uncompilable term — skip it, keep matching the rest.
			return [];
		} finally {
			restore_error_handler();
		}//end try

		if ($count === false || $count === 0) {
			return [];
		}

		return $matches[0];
	}//end findRawHits()

	/**
	 * Sanitise the caller-supplied match mode.
	 *
	 * @param string $mode Raw mode value.
	 *
	 * @return string A value from {@see VALID_MODES}.
	 */
	private function normalizeMode(string $mode): string {
		if (in_array($mode, self::VALID_MODES, true) === true) {
			return $mode;
		}

		return self::MODE_CASE_INSENSITIVE;
	}//end normalizeMode()

	/**
	 * Filter blank/whitespace-only terms and default each term's label to
	 * its own value, preserving the original index for a stable sort
	 * tie-break.
	 *
	 * This is the sanitisation seam, so it takes the RAW row shape — both keys
	 * optional — rather than {@see match()}'s already-validated public
	 * contract: a dictionary row loaded from OpenRegister is arbitrary JSON and
	 * may legitimately arrive without a `value`.
	 *
	 * @param array<int, array{value?: string, label?: string}> $terms Raw term rows.
	 *
	 * @return array<int, array{value: string, label: string, originalIndex: int}> Sanitised candidates.
	 */
	private function buildCandidates(array $terms): array {
		$candidates = [];
		$index = 0;
		foreach ($terms as $term) {
			$value = (string)($term['value'] ?? '');
			if (trim($value) === '') {
				continue;
			}

			$label = (string)($term['label'] ?? '');
			if (trim($label) === '') {
				$label = $value;
			}

			$candidates[] = [
				'value' => $value,
				'label' => $label,
				'originalIndex' => $index,
			];
			$index++;
		}//end foreach

		return $candidates;
	}//end buildCandidates()

	/**
	 * Build the compiled regex pattern for one term + mode.
	 *
	 * @param string $needle Term value.
	 * @param string $mode Normalised match mode.
	 *
	 * @return string Compiled pattern ready for `preg_match_all`.
	 */
	private function buildPattern(string $needle, string $mode): string {
		$quoted = preg_quote($needle, '/');
		if ($mode === self::MODE_WORD_BOUNDARY) {
			$quoted = '\b' . $quoted . '\b';
		}

		$flags = 'u';
		if ($mode !== self::MODE_EXACT) {
			$flags .= 'i';
		}

		return '/' . $quoted . '/' . $flags;
	}//end buildPattern()

	/**
	 * Whether `[start, end)` overlaps any already-claimed range.
	 *
	 * @param int $start Candidate match start.
	 * @param int $end Candidate match end.
	 * @param array<int, array{0:int,1:int}> $claimed Already-claimed `[start, end]` pairs.
	 *
	 * @return bool True when the candidate overlaps a claimed range.
	 */
	private function overlapsClaimed(int $start, int $end, array $claimed): bool {
		foreach ($claimed as [$claimedStart, $claimedEnd]) {
			if ($start < $claimedEnd && $end > $claimedStart) {
				return true;
			}
		}

		return false;
	}//end overlapsClaimed()
}//end class
