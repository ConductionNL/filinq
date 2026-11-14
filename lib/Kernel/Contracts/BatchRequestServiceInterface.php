<?php

/**
 * Contract of BatchRequestService
 *
 * The public surface of the BatchRequestService service, so callers in other
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

/**
 * Contract of BatchRequestService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface BatchRequestServiceInterface {
	/**
	 * Validate the batch-anonymize request body up to the outputFormat gate.
	 *
	 * The unredactedEntities entries themselves are validated separately (see
	 * validateUnredactedEntries) because the controller resolves the
	 * outputFormat in between, and that ordering determines which HTTP 400 a
	 * doubly-malformed body receives.
	 *
	 * @param array<string, mixed> $params Request parameters.
	 *
	 * @return array{error: array{status: int, body: array<string, mixed>}|null,
	 *               request: array<string, mixed>|null} The first validation failure, or the
	 *                                                   normalised request under `request`.
	 *
	 * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-1
	 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-anonymization
	 */
	public function validateBody(array $params): array;

	/**
	 * Validate the structure of each unredactedEntities[] entry.
	 *
	 * Mirrors the single-file anonymize endpoint so the batch endpoint rejects
	 * malformed payloads with HTTP 400 before forwarding to the service layer.
	 *
	 * @param array<int, mixed> $entries The unredactedEntities array from the request.
	 *
	 * @return array{status: int, body: array<string, mixed>}|null HTTP 400 payload for the first
	 *                                                             invalid entry, null when all valid.
	 *
	 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-5
	 */
	public function validateUnredactedEntries(array $entries): ?array;

	/**
	 * Resolve the placeholder-numbering scope for a batch.
	 *
	 * A batch IS a folder/dossier, so the default is 'dossier' — a person gets
	 * the same scope-local number across all the batch's files. Any value other
	 * than 'document' keeps the dossier default.
	 *
	 * @param array<string, mixed> $params Request parameters.
	 *
	 * @return string Either 'dossier' or 'document'.
	 *
	 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-anonymization
	 */
	public function resolveScope(array $params): string;

	/**
	 * Resolve the HTTP status for a batch anonymization result.
	 *
	 * HTTP 200 — all files processed without prohibition failures.
	 * HTTP 207 — some files had per-file 422 prohibition violations; others succeeded.
	 * HTTP 422 — every processed file had a prohibition violation (none succeeded).
	 *
	 * @param array<string, mixed> $result Batch anonymization result.
	 *
	 * @return int HTTP status code.
	 *
	 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-5
	 */
	public function resolveHttpStatus(array $result): int;

	/**
	 * Aggregate a batch's per-file entries into the status snapshot.
	 *
	 * @param array<string, mixed> $batch The stored batch record.
	 *
	 * @return array{totalEntities: int, progress: float|int, totalFiles: int} The snapshot counters.
	 *
	 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-status-endpoint
	 */
	public function summariseBatch(array $batch): array;

	/**
	 * Count the files of a batch whose extraction has finished (or failed).
	 *
	 * @param array<string, mixed> $batch The stored batch record.
	 *
	 * @return int Number of files in a terminal extraction state.
	 *
	 * @spec openspec/specs/anonymization-entity-review/spec.md#requirement-consolidated-entity-list-endpoint
	 */
	public function countProcessedFiles(array $batch): int;

	/**
	 * Coerce and XOR-validate the folder-batch request parameters.
	 *
	 * @param mixed $rawFolderId Raw `folderId` param value from the request.
	 * @param mixed $rawFolderPath Raw `folderPath` param value from the request.
	 *
	 * @return array{folderId: int|null, folderPath: string|null,
	 *               error: array{status: int, body: array<string, mixed>}|null} The coerced params,
	 *                                                                          plus the validation
	 *                                                                          failure when both or
	 *                                                                          neither were supplied.
	 *
	 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-creation-via-multi-file-upload
	 */
	public function resolveFolderParams(mixed $rawFolderId, mixed $rawFolderPath): array;
}//end interface
