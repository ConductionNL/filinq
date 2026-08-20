<?php

/**
 * Custom Dictionary Payload Normaliser
 *
 * Pure input-shaping for {@see CustomDictionaryService}: server-side CSV /
 * newline-list import parsing (design.md §D5 — parsing MUST NOT be delegated
 * to the browser), `matchMode` sanitisation against the schema enum, and the
 * small coercions applied before persistence. Extracted from
 * CustomDictionaryService so that service holds only CRUD orchestration.
 *
 * Every method here is a pure function of its arguments — no I/O, no state.
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

/**
 * Pure input-shaping helpers for custom dictionary payloads.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
 */
class CustomDictionaryPayloadNormaliser {

	/**
	 * Valid `matchMode` values (mirrors the schema enum).
	 *
	 * @var array<int, string>
	 */
	public const VALID_MATCH_MODES = ['exact', 'caseInsensitive', 'wordBoundary'];

	/**
	 * Default match mode when unset/invalid.
	 *
	 * @var string
	 */
	public const DEFAULT_MATCH_MODE = 'caseInsensitive';

	/**
	 * Sanitise a `matchMode` value against the schema enum.
	 *
	 * @param mixed $mode Raw value.
	 *
	 * @return string A value from {@see VALID_MATCH_MODES}.
	 *
	 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
	 */
	public function sanitizeMatchMode(mixed $mode): string {
		if (is_string($mode) === true && in_array($mode, self::VALID_MATCH_MODES, true) === true) {
			return $mode;
		}

		return self::DEFAULT_MATCH_MODE;
	}//end sanitizeMatchMode()

	/**
	 * Parse CSV import content into `{value, label}` rows.
	 *
	 * @param string $content Raw CSV content.
	 *
	 * @return array<int, array{value: string, label: string|null}>
	 *
	 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
	 */
	public function parseCsv(string $content): array {
		$rows = [];
		foreach ($this->splitLines(content: $content) as $line) {
			// Explicit $escape (PHP 8.4 deprecates the implicit default) —
			// no escape character: dictionary term CSVs are simple
			// value[,label] rows, never quoted-and-escaped fields.
			$columns = str_getcsv(string: $line, separator: ',', enclosure: '"', escape: '');
			$rows[] = [
				'value' => (string)($columns[0] ?? ''),
				'label' => ($columns[1] ?? null),
			];
		}

		return $rows;
	}//end parseCsv()

	/**
	 * Parse newline-separated plain-text import content into
	 * `{value, label}` rows.
	 *
	 * @param string $content Raw plain-text content.
	 *
	 * @return array<int, array{value: string, label: string|null}>
	 *
	 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
	 */
	public function parseList(string $content): array {
		$rows = [];
		foreach ($this->splitLines(content: $content) as $line) {
			$rows[] = [
				'value' => $line,
				'label' => null,
			];
		}

		return $rows;
	}//end parseList()

	/**
	 * Strip framework-injected request params before persistence.
	 *
	 * @param array<string, mixed> $data Raw incoming data.
	 *
	 * @return array<string, mixed>
	 *
	 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
	 */
	public function stripFrameworkParams(array $data): array {
		unset($data['_route'], $data['_method'], $data['id'], $data['uuid']);
		return $data;
	}//end stripFrameworkParams()

	/**
	 * Coerce a value to a trimmed string, or null when blank/absent.
	 *
	 * @param mixed $value Raw value.
	 *
	 * @return string|null
	 *
	 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
	 */
	public function stringOrNull(mixed $value): ?string {
		if (is_string($value) === false) {
			return null;
		}

		$trimmed = trim($value);
		if ($trimmed === '') {
			return null;
		}

		return $trimmed;
	}//end stringOrNull()

	/**
	 * Split raw import content into lines.
	 *
	 * Normalises line endings so a Windows-authored CSV/list parses the same
	 * as a Unix one, then drops exactly one trailing newline artifact (a
	 * pasted textarea value or an uploaded file almost always ends with one)
	 * so it is not counted as an extra blank line. Every OTHER
	 * blank/whitespace-only line is preserved as a row — importTerms() counts
	 * it toward `skipped` per REQ-DDCDR-005's scenario numbers (blank lines
	 * are part of the reported total, not silently dropped pre-count).
	 *
	 * @param string $content Raw content.
	 *
	 * @return array<int, string>
	 */
	private function splitLines(string $content): array {
		$normalized = str_replace(["\r\n", "\r"], "\n", $content);

		if (str_ends_with($normalized, "\n") === true) {
			$normalized = substr($normalized, 0, -1);
		}

		return explode("\n", $normalized);
	}//end splitLines()
}//end class
