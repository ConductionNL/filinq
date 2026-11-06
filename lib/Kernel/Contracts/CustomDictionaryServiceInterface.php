<?php

/**
 * Contract of CustomDictionaryService
 *
 * The public surface of the CustomDictionaryService service, so callers in other
 * components depend on this contract instead of the implementation.
 *
 * @category  Kernel
 * @package   OCA\Filinq\Kernel\Contracts
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Kernel\Contracts;

use OCP\AppFramework\Db\DoesNotExistException;
use Exception;
use InvalidArgumentException;
use RuntimeException;

/**
 * Contract of CustomDictionaryService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface CustomDictionaryServiceInterface {
	/**
	 * Register slug both schemas live in.
	 *
	 * `filinq`, not `document`: this app declares ONE register holding all 23
	 * schemas. The five it used to declare are retired.
	 *
	 * @var string
	 */
	public const REGISTER = 'filinq';

	/**
	 * Dictionary schema slug.
	 *
	 * @var string
	 */
	public const SCHEMA_DICTIONARY = 'customDictionary';

	/**
	 * Term schema slug.
	 *
	 * @var string
	 */
	public const SCHEMA_TERM = 'customDictionaryTerm';

	/**
	 * Whether OpenRegister is installed. Callers use this to return an
	 * explanatory unavailable state instead of crashing (REQ-DDCDR-004).
	 *
	 * @return bool
	 *
	 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
	 */
	public function isAvailable(): bool;

	/**
	 * List dictionaries visible to the caller's accessible organisations,
	 * each enriched with its live `termCount`.
	 *
	 * @return array<int, array<string, mixed>>
	 *
	 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
	 */
	public function listDictionaries(): array;

	/**
	 * Get a single dictionary, organisation-gated.
	 *
	 * @param string $uuid Dictionary UUID.
	 *
	 * @return array<string, mixed>
	 *
	 * @throws DoesNotExistException When no dictionary exists for the UUID.
	 * @throws RuntimeException When the dictionary exists but the caller's
	 *                          accessible organisations do not include it
	 *                          (mapped to HTTP 403 by the controller).
	 *
	 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
	 */
	public function getDictionary(string $uuid): array;

	/**
	 * Create a dictionary. Organisation is stamped by OpenRegister's
	 * `SaveObject` from the caller's active organisation (design.md §D1) —
	 * this service does not set `@self.organisation` explicitly.
	 *
	 * @param array<string, mixed> $data Caller-supplied dictionary data.
	 *
	 * @return array<string, mixed> The created record, enriched with `termCount`.
	 *
	 * @throws Exception On write failure.
	 *
	 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
	 */
	public function createDictionary(array $data): array;

	/**
	 * Update a dictionary. Organisation-gated: throws before any write is
	 * attempted when the caller cannot access the existing record.
	 *
	 * @param string $uuid Dictionary UUID.
	 * @param array<string, mixed> $data Updated fields.
	 *
	 * @return array<string, mixed> The updated record, enriched with `termCount`.
	 *
	 * @throws DoesNotExistException When no dictionary exists for the UUID.
	 * @throws RuntimeException When the caller is not permitted (HTTP 403).
	 * @throws Exception On write failure.
	 *
	 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
	 */
	public function updateDictionary(string $uuid, array $data): array;

	/**
	 * Delete a dictionary and cascade-delete its terms.
	 *
	 * @param string $uuid Dictionary UUID.
	 *
	 * @return void
	 *
	 * @throws DoesNotExistException When no dictionary exists for the UUID.
	 * @throws RuntimeException When the caller is not permitted (HTTP 403).
	 *
	 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
	 */
	public function deleteDictionary(string $uuid): void;

	/**
	 * List the terms of one dictionary, organisation-gated via the parent.
	 *
	 * @param string $dictionaryUuid Dictionary UUID.
	 *
	 * @return array<int, array<string, mixed>>
	 *
	 * @throws DoesNotExistException When no dictionary exists for the UUID.
	 * @throws RuntimeException When the caller is not permitted (HTTP 403).
	 *
	 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
	 */
	public function listTerms(string $dictionaryUuid): array;

	/**
	 * Add a single term to a dictionary.
	 *
	 * @param string $dictionaryUuid Dictionary UUID.
	 * @param array<string, mixed> $data Term data (`value`, optional `label`).
	 *
	 * @return array<string, mixed> The created term.
	 *
	 * @throws DoesNotExistException When no dictionary exists for the UUID.
	 * @throws RuntimeException When the caller is not permitted (HTTP 403).
	 * @throws InvalidArgumentException When `value` is blank.
	 *
	 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
	 */
	public function createTerm(string $dictionaryUuid, array $data): array;

	/**
	 * Delete a single term, verifying it belongs to the given dictionary.
	 *
	 * @param string $dictionaryUuid Dictionary UUID.
	 * @param string $termUuid Term UUID.
	 *
	 * @return void
	 *
	 * @throws DoesNotExistException When the dictionary or the term does not exist,
	 *                               or the term does not belong to this dictionary.
	 * @throws RuntimeException When the caller is not permitted (HTTP 403).
	 *
	 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
	 */
	public function deleteTerm(string $dictionaryUuid, string $termUuid): void;

	/**
	 * Import terms from a CSV upload or a newline-separated plain-text list.
	 *
	 * Server-side only (design.md §D5 — parsing MUST NOT be delegated to the
	 * browser). Trims values, skips blank lines, de-duplicates
	 * case-insensitively against the dictionary's existing terms (and within
	 * the same import batch), and bounds the import size.
	 *
	 * @param string $dictionaryUuid Dictionary UUID.
	 * @param string $content Raw uploaded/pasted content.
	 * @param bool $isCsv True for CSV parsing, false for newline-list parsing.
	 *
	 * @return array{added: int, skipped: int, total: int}
	 *
	 * @throws DoesNotExistException When no dictionary exists for the UUID.
	 * @throws RuntimeException When the caller is not permitted (HTTP 403).
	 * @throws InvalidArgumentException When the payload exceeds the size/row bound.
	 *
	 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
	 */
	public function importTerms(string $dictionaryUuid, string $content, bool $isCsv): array;

	/**
	 * Active dictionaries + their non-blank terms, scoped to the caller's
	 * accessible organisations, shaped for
	 * {@see CustomDictionaryMatchService::match()}.
	 *
	 * Reuses the same organisation gate the CRUD surface enforces so
	 * automatic detection (hooked from `AnonymizationService`) only ever
	 * runs dictionaries the acting user — and therefore the file they are
	 * working on — can see. Best-effort: any failure returns an empty list
	 * rather than throwing, so a detection call degrades to "no dictionary
	 * hits" instead of blocking OpenRegister's own detection.
	 *
	 * @return array<int, array{label: string, matchMode: string, terms: array<int, array{value: string, label: string}>}>
	 *
	 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
	 */
	public function listActiveDictionariesForDetection(): array;
}//end interface
