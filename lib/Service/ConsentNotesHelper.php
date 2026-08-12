<?php

/**
 * Consent Notes Helper
 *
 * Manages the sentinel-tagged region in publicationConsent.notes
 * for additional publication bases (basis 2..N). The helper writes,
 * replaces, and removes the bracketed HTML-comment region while
 * leaving operator-authored content outside the brackets untouched.
 *
 * Sentinel format per design D3:
 *
 *   <existing operator-authored notes content, if any>
 *
 *   <!-- docudesk:additional-publication-bases:begin -->
 *   **Aanvullende publicatiegrondslagen:**
 *   - <basis 2>
 *   - <basis 3>
 *   <!-- docudesk:additional-publication-bases:end -->
 *
 * HTML-comment sentinels are markdown-invisible so they do not render in
 * Nextcloud's markdown viewers. The begin/end pair uniquely brackets the
 * auto-managed region and the service regex matches across newlines via
 * the DOTALL (`s`) flag.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.DocuDesk.app
 *
 * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-3
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

/**
 * Helper for sentinel-tagged additional-publication-bases region in consent notes.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction Development Team <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-3
 */
class ConsentNotesHelper {

	/**
	 * Opening sentinel comment.
	 *
	 * @var string
	 */
	public const SENTINEL_BEGIN = '<!-- docudesk:additional-publication-bases:begin -->';

	/**
	 * Closing sentinel comment.
	 *
	 * @var string
	 */
	public const SENTINEL_END = '<!-- docudesk:additional-publication-bases:end -->';

	/**
	 * Maximum character length for the legalBasis field.
	 *
	 * @var int
	 */
	public const LEGAL_BASIS_MAX_LENGTH = 500;

	/**
	 * Write or replace the sentinel-tagged region in the notes string.
	 *
	 * Behaviour:
	 * - $additionalBases empty → strip the region (and its preceding blank line) and return.
	 * - $additionalBases non-empty → build the sentinel block and either
	 *   append it (separated by a blank line) or replace the existing block.
	 *
	 * Operator-authored content before the sentinel is preserved unchanged.
	 *
	 * @param string $currentNotes Current value of publicationConsent.notes.
	 * @param string[] $additionalBases Bases 2..N to render inside the sentinel region.
	 *
	 * @return string Updated notes string.
	 *
	 * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-3
	 */
	public function updateSentinelRegion(string $currentNotes, array $additionalBases): string {
		// Strip any existing sentinel region first (preserves operator content).
		$operatorContent = $this->stripSentinelRegion(notes: $currentNotes);

		if (count($additionalBases) === 0) {
			return $operatorContent;
		}

		$sentinelBlock = $this->buildSentinelBlock(bases: $additionalBases);

		if ($operatorContent === '') {
			return $sentinelBlock;
		}

		return rtrim($operatorContent) . "\n\n" . $sentinelBlock;
	}//end updateSentinelRegion()

	/**
	 * Remove the sentinel-tagged region from notes (including its leading blank line).
	 *
	 * Safe to call even when no sentinel region is present; returns the
	 * string unchanged in that case.
	 *
	 * @param string $notes Source notes string.
	 *
	 * @return string Notes with the sentinel region removed.
	 *
	 * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-3
	 */
	public function stripSentinelRegion(string $notes): string {
		// Pattern matches an optional run of newlines before the begin-sentinel,
		// then everything up to (and including) the end-sentinel. The `s` flag
		// (DOTALL) lets `.` cross newline boundaries so multi-line regions match.
		$begin = preg_quote(str: self::SENTINEL_BEGIN, delimiter: '/');
		$end = preg_quote(str: self::SENTINEL_END, delimiter: '/');
		$pattern = '/\n*' . $begin . '.*?' . $end . '/su';
		$result = preg_replace(pattern: $pattern, replacement: '', subject: $notes);
		return $result ?? $notes;
	}//end stripSentinelRegion()

	/**
	 * Truncate a string at word boundary up to $maxLength characters.
	 *
	 * Used for truncating `publicationBases[0]` to the `legalBasis` field limit.
	 *
	 * @param string $value Source string.
	 * @param int $maxLength Maximum character length.
	 *
	 * @return string Truncated string.
	 *
	 * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-4
	 */
	public function truncateAtWordBoundary(string $value, int $maxLength = self::LEGAL_BASIS_MAX_LENGTH): string {
		if (mb_strlen(string: $value) <= $maxLength) {
			return $value;
		}

		$truncated = mb_substr(string: $value, start: 0, length: $maxLength);

		// Step back to the last word boundary to avoid splitting mid-word.
		$lastSpace = mb_strrpos(haystack: $truncated, needle: ' ');
		if ($lastSpace !== false && $lastSpace > 0) {
			$truncated = mb_substr(string: $truncated, start: 0, length: $lastSpace);
		}

		return $truncated;
	}//end truncateAtWordBoundary()

	/**
	 * Build the sentinel block for the given list of additional bases.
	 *
	 * @param string[] $bases Non-empty list of additional publication bases.
	 *
	 * @return string The fully-formed sentinel block.
	 */
	private function buildSentinelBlock(array $bases): string {
		$lines = [
			self::SENTINEL_BEGIN,
			'**Aanvullende publicatiegrondslagen:**',
		];

		foreach ($bases as $basis) {
			$lines[] = '- ' . $basis;
		}

		$lines[] = self::SENTINEL_END;

		return implode(separator: "\n", array: $lines);
	}//end buildSentinelBlock()
}//end class
