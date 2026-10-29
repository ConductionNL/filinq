<?php

/**
 * Contract of BatchAnonymizeService
 *
 * The public surface of the BatchAnonymizeService service, so callers in other
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

use Exception;

/**
 * Contract of BatchAnonymizeService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface BatchAnonymizeServiceInterface {
	/**
	 * Anonymize every extracted file in a batch using the approved entity list.
	 *
	 * No grondslagen summary is produced; see
	 * {@see anonymizeBatchWithBasisSummary()} for the summary-producing variant.
	 * Files with a non-extracted status are skipped (previous errors are
	 * recorded in the skipped list; other states are ignored).
	 *
	 * When unredactedEntities is non-empty, the prohibition gate is checked
	 * per file. Files with prohibition violations are recorded with a
	 * `prohibitionViolation` status and counted in prohibitionSkippedFiles.
	 *
	 * @param string $batchId Identifier of the batch to anonymize.
	 * @param array<int, array<string, mixed>> $entities User-approved entities to anonymize.
	 * @param array<int, array<string, mixed>> $unredactedEntities Entities to publish unredacted with consent creation.
	 * @param string $outputFormat Per-batch output format gate
	 *                             ('pdf-only'|'pdf'|'preserve',
	 *                             default 'pdf-only'). Passed
	 *                             through to each per-file
	 *                             anonymise call. Per-file
	 *                             ConversionFailedException is
	 *                             recorded as an error on that
	 *                             file's batch entry and the
	 *                             batch continues with the
	 *                             next file.
	 * @param string $scope Placeholder-numbering scope forwarded to OpenRegister for every file in
	 *                      the batch. Defaults to `"dossier"` because a batch IS a folder/dossier:
	 *                      a person gets the SAME scope-local number across all the batch's files
	 *                      (OpenRegister derives the dossier from each file's parent folder).
	 *
	 * @return array<string, mixed> Summary of the run — see runBatch().
	 *
	 * @throws Exception When the batch cannot be found.
	 *
	 * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-3
	 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-anonymization
	 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-5
	 */
	public function anonymizeBatch(
		string $batchId,
		array $entities,
		array $unredactedEntities = [],
		string $outputFormat = 'pdf-only',
		string $scope = 'dossier',
	): array;

	/**
	 * Anonymize every extracted file in a batch AND append a grondslagen summary.
	 *
	 * Identical to {@see anonymizeBatch()} except that every per-file anonymise
	 * call also renders the grondslagen summary. Summary failures are collected
	 * as per-file `warning` entries and do not abort the batch; the batch always
	 * completes as HTTP 200.
	 *
	 * @param string $batchId Identifier of the batch to anonymize.
	 * @param array<int, array<string, mixed>> $entities User-approved entities to anonymize.
	 * @param array<int, array<string, mixed>> $unredactedEntities Entities to publish unredacted with consent creation.
	 * @param string $outputFormat Per-batch output format gate
	 *                             ('pdf-only'|'pdf'|'preserve', default 'pdf-only').
	 * @param string $scope Placeholder-numbering scope forwarded to OpenRegister.
	 *
	 * @return array<string, mixed> Summary of the run — see runBatch().
	 *
	 * @throws Exception When the batch cannot be found.
	 *
	 * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-3
	 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-anonymization
	 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-5
	 */
	public function anonymizeBatchWithBasisSummary(
		string $batchId,
		array $entities,
		array $unredactedEntities = [],
		string $outputFormat = 'pdf-only',
		string $scope = 'dossier',
	): array;
}//end interface
