<?php

/**
 * Custom Dictionary Service
 *
 * Organisation-gated CRUD + CSV/newline import for the `customDictionary`
 * and `customDictionaryTerm` register objects. Per ADR-022 this is a
 * justified non-pass-through controller-support service: it adds (1) the
 * organisation gate (fail-closed — a caller only ever sees/edits
 * dictionaries of their accessible organisations), (2) server-side
 * CSV/newline import parsing, and (3) term-count enrichment on top of
 * OpenRegister's bare `ObjectService` CRUD.
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

use Exception;
use InvalidArgumentException;
use OCP\AppFramework\Db\DoesNotExistException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Organisation-gated CRUD wrapper for custom dictionaries + terms.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
 */
class CustomDictionaryService {

	/**
	 * Register slug both schemas live in.
	 *
	 * @var string
	 */
	public const REGISTER = 'document';

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
	 * Maximum number of term rows accepted in a single import call —
	 * bounds the request against a denial-of-service via an oversized
	 * upload (design.md §Security Considerations).
	 *
	 * @var int
	 */
	private const MAX_IMPORT_ROWS = 2000;

	/**
	 * Maximum import payload size in bytes.
	 *
	 * @var int
	 */
	private const MAX_IMPORT_BYTES = 2097152;

	/**
	 * Constructor.
	 *
	 * @param CustomDictionaryRepository $repository Persistence primitives.
	 * @param CustomDictionaryAccessGate $accessGate Fail-closed organisation gate.
	 * @param CustomDictionaryPayloadNormaliser $normaliser Import parsing and payload coercion.
	 * @param LoggerInterface $logger Structured logger.
	 */
	public function __construct(
		private readonly CustomDictionaryRepository $repository,
		private readonly CustomDictionaryAccessGate $accessGate,
		private readonly CustomDictionaryPayloadNormaliser $normaliser,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Whether OpenRegister is installed. Callers use this to return an
	 * explanatory unavailable state instead of crashing (REQ-DDCDR-004).
	 *
	 * @return bool
	 *
	 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
	 */
	public function isAvailable(): bool {
		return $this->accessGate->isAvailable();
	}//end isAvailable()

	/**
	 * List dictionaries visible to the caller's accessible organisations,
	 * each enriched with its live `termCount`.
	 *
	 * @return array<int, array<string, mixed>>
	 *
	 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
	 */
	public function listDictionaries(): array {
		if ($this->isAvailable() === false) {
			return [];
		}

		$rows = $this->repository->listBySchema(schema: self::SCHEMA_DICTIONARY);
		$accessible = array_values(
			array_filter(
				$rows,
				fn (array $record): bool => $this->accessGate->callerHasAccess(record: $record)
			)
		);

		return array_map(
			fn (array $record): array => $this->enrichWithTermCount(dictionary: $record),
			$accessible
		);

	}//end listDictionaries()

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
	public function getDictionary(string $uuid): array {
		return $this->enrichWithTermCount(dictionary: $this->findDictionaryOrFail(uuid: $uuid));
	}//end getDictionary()

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
	public function createDictionary(array $data): array {
		$payload = $this->normaliser->stripFrameworkParams(data: $data);
		unset($payload['termCount']);
		$payload['active'] = ($payload['active'] ?? true);
		$payload['matchMode'] = $this->normaliser->sanitizeMatchMode(mode: ($payload['matchMode'] ?? null));

		$saved = $this->repository->save(schema: self::SCHEMA_DICTIONARY, data: $payload);
		return $this->enrichWithTermCount(dictionary: $saved);
	}//end createDictionary()

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
	public function updateDictionary(string $uuid, array $data): array {
		$existing = $this->findDictionaryOrFail(uuid: $uuid);

		$payload = array_merge($existing, $this->normaliser->stripFrameworkParams(data: $data));
		unset($payload['termCount'], $payload['@self']);
		$payload['matchMode'] = $this->normaliser->sanitizeMatchMode(mode: ($payload['matchMode'] ?? null));

		$saved = $this->repository->save(schema: self::SCHEMA_DICTIONARY, data: $payload, uuid: $uuid);
		return $this->enrichWithTermCount(dictionary: $saved);
	}//end updateDictionary()

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
	public function deleteDictionary(string $uuid): void {
		$existing = $this->findDictionaryOrFail(uuid: $uuid);

		foreach ($this->listTermsForDictionary(dictionary: $existing) as $term) {
			$termId = (string)($term['id'] ?? '');
			if ($termId !== '') {
				$this->repository->delete(schema: self::SCHEMA_TERM, uuid: $termId);
			}
		}

		$this->repository->delete(schema: self::SCHEMA_DICTIONARY, uuid: $uuid);

	}//end deleteDictionary()

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
	public function listTerms(string $dictionaryUuid): array {
		return $this->listTermsForDictionary(dictionary: $this->findDictionaryOrFail(uuid: $dictionaryUuid));
	}//end listTerms()

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
	public function createTerm(string $dictionaryUuid, array $data): array {
		$dictionary = $this->findDictionaryOrFail(uuid: $dictionaryUuid);

		$value = trim((string)($data['value'] ?? ''));
		if ($value === '') {
			throw new InvalidArgumentException('Term value must not be blank.');
		}

		return $this->repository->save(
			schema: self::SCHEMA_TERM,
			data: [
				'value' => $value,
				'label' => $this->normaliser->stringOrNull(value: ($data['label'] ?? null)),
				'dictionary' => (string)($dictionary['id'] ?? $dictionaryUuid),
			]
		);

	}//end createTerm()

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
	public function deleteTerm(string $dictionaryUuid, string $termUuid): void {
		$dictionary = $this->findDictionaryOrFail(uuid: $dictionaryUuid);

		$term = $this->repository->findOne(schema: self::SCHEMA_TERM, uuid: $termUuid);
		if ($term === null || $this->termBelongsToDictionary(term: $term, dictionary: $dictionary) === false) {
			throw new DoesNotExistException('Term not found for this dictionary.');
		}

		$this->repository->delete(schema: self::SCHEMA_TERM, uuid: $termUuid);

	}//end deleteTerm()

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
	public function importTerms(string $dictionaryUuid, string $content, bool $isCsv): array {
		$dictionary = $this->findDictionaryOrFail(uuid: $dictionaryUuid);

		if (strlen($content) > self::MAX_IMPORT_BYTES) {
			throw new InvalidArgumentException(
				sprintf('Import payload exceeds the %d byte limit.', self::MAX_IMPORT_BYTES)
			);
		}

		$rows = $this->normaliser->parseList(content: $content);
		if ($isCsv === true) {
			$rows = $this->normaliser->parseCsv(content: $content);
		}

		if (count($rows) > self::MAX_IMPORT_ROWS) {
			throw new InvalidArgumentException(
				sprintf('Import exceeds the %d row limit.', self::MAX_IMPORT_ROWS)
			);
		}

		$existingKeys = [];
		foreach ($this->listTermsForDictionary(dictionary: $dictionary) as $existingTerm) {
			$existingKeys[mb_strtolower(trim((string)($existingTerm['value'] ?? '')))] = true;
		}

		$dictionaryReference = (string)($dictionary['id'] ?? $dictionaryUuid);
		$added = 0;
		$skipped = 0;
		$seenThisBatch = [];

		foreach ($rows as $row) {
			// The normaliser always emits a string `value` (a missing CSV
			// column is coerced to ''), so no null-coalesce is needed here.
			$value = trim($row['value']);
			if ($value === '') {
				$skipped++;
				continue;
			}

			$key = mb_strtolower($value);
			if (isset($existingKeys[$key]) === true || isset($seenThisBatch[$key]) === true) {
				$skipped++;
				continue;
			}

			$seenThisBatch[$key] = true;

			$this->repository->save(
				schema: self::SCHEMA_TERM,
				data: [
					'value' => $value,
					'label' => $this->normaliser->stringOrNull(value: ($row['label'] ?? null)),
					'dictionary' => $dictionaryReference,
				]
			);
			$added++;
		}//end foreach

		return [
			'added' => $added,
			'skipped' => $skipped,
			'total' => ($added + $skipped),
		];

	}//end importTerms()

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
	public function listActiveDictionariesForDetection(): array {
		try {
			if ($this->isAvailable() === false) {
				return [];
			}

			$result = [];
			foreach ($this->listDictionaries() as $dictionary) {
				if (($dictionary['active'] ?? false) !== true) {
					continue;
				}

				$termRows = $this->buildDetectionTermRows(dictionary: $dictionary);
				if (empty($termRows) === true) {
					continue;
				}

				$result[] = [
					'label' => (string)($dictionary['label'] ?? ''),
					'matchMode' => $this->normaliser->sanitizeMatchMode(mode: ($dictionary['matchMode'] ?? null)),
					'terms' => $termRows,
				];
			}//end foreach

			return $result;
		} catch (Throwable $e) {
			$this->logger->warning(
				'[CustomDictionaryService] listActiveDictionariesForDetection failed; returning no dictionaries.',
				['file' => __FILE__, 'line' => __LINE__, 'error' => $e->getMessage()]
			);
			return [];
		}//end try

	}//end listActiveDictionariesForDetection()

	/**
	 * Build one dictionary's detection-shaped term rows: non-blank values
	 * only, each carrying a resolved label (the term's own label, else the
	 * dictionary's label, else the term value itself).
	 *
	 * @param array<string, mixed> $dictionary The parent dictionary record.
	 *
	 * @return array<int, array{value: string, label: string}> Term rows, possibly empty.
	 *
	 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
	 */
	private function buildDetectionTermRows(array $dictionary): array {
		$termRows = [];
		foreach ($this->listTermsForDictionary(dictionary: $dictionary) as $term) {
			$value = trim((string)($term['value'] ?? ''));
			if ($value === '') {
				continue;
			}

			$label = $term['label'] ?? null;
			if (is_string($label) === false || trim($label) === '') {
				$label = ($dictionary['label'] ?? $value);
			}

			$termRows[] = [
				'value' => $value,
				'label' => (string)$label,
			];
		}//end foreach

		return $termRows;
	}//end buildDetectionTermRows()

	/**
	 * Resolve a dictionary by UUID, enforcing the organisation gate.
	 *
	 * @param string $uuid Dictionary UUID.
	 *
	 * @return array<string, mixed>
	 *
	 * @throws DoesNotExistException When no dictionary exists for the UUID.
	 * @throws RuntimeException When the caller's accessible organisations do
	 *                          not include the dictionary's organisation.
	 */
	private function findDictionaryOrFail(string $uuid): array {
		$record = $this->repository->findOne(schema: self::SCHEMA_DICTIONARY, uuid: $uuid);
		if ($record === null) {
			throw new DoesNotExistException(sprintf('Custom dictionary %s not found.', $uuid));
		}

		if ($this->accessGate->callerHasAccess(record: $record) === false) {
			throw new RuntimeException('You do not have access to this custom dictionary.');
		}

		return $record;
	}//end findDictionaryOrFail()

	/**
	 * List terms belonging to one dictionary (matched by uuid OR slug —
	 * seed data references the parent by slug per the codebase's existing
	 * `dossier.bases` convention; API-created terms carry the parent's uuid).
	 *
	 * @param array<string, mixed> $dictionary The parent dictionary record.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function listTermsForDictionary(array $dictionary): array {
		$rows = $this->repository->listBySchema(schema: self::SCHEMA_TERM);
		return array_values(
			array_filter(
				$rows,
				fn (array $term): bool => $this->termBelongsToDictionary(term: $term, dictionary: $dictionary)
			)
		);

	}//end listTermsForDictionary()

	/**
	 * Whether a term row references the given dictionary.
	 *
	 * @param array<string, mixed> $term Term record.
	 * @param array<string, mixed> $dictionary Dictionary record.
	 *
	 * @return bool
	 */
	private function termBelongsToDictionary(array $term, array $dictionary): bool {
		$reference = (string)($term['dictionary'] ?? '');
		if ($reference === '') {
			return false;
		}

		$dictionaryId = (string)($dictionary['id'] ?? '');
		$dictionarySlug = (string)($this->accessGate->selfMeta(record: $dictionary)['slug'] ?? ($dictionary['slug'] ?? ''));

		return $reference === $dictionaryId || ($dictionarySlug !== '' && $reference === $dictionarySlug);
	}//end termBelongsToDictionary()

	/**
	 * Enrich a dictionary record with its live term count.
	 *
	 * @param array<string, mixed> $dictionary Dictionary record.
	 *
	 * @return array<string, mixed>
	 */
	private function enrichWithTermCount(array $dictionary): array {
		$dictionary['termCount'] = count($this->listTermsForDictionary(dictionary: $dictionary));
		return $dictionary;
	}//end enrichWithTermCount()
}//end class
