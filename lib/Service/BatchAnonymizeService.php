<?php

/**
 * Batch Anonymize Service
 *
 * Iterates over the files in a reviewed batch and applies the user-approved
 * entity list to each document via AnonymizationService. Failures are
 * recorded on the file entry and reported back to the caller rather than
 * aborting the batch.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-anonymization
 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-5
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use Exception;
use OCA\DocuDesk\Exception\ConversionFailedException;

/**
 * Applies a user-reviewed entity list across every file in a batch.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-5
 */
class BatchAnonymizeService {
	/**
	 * Constructor for BatchAnonymizeService
	 *
	 * @param AnonymizationService $anonService Service that performs single-document anonymization.
	 * @param BatchStateService $stateService Service that persists per-batch state between calls.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly AnonymizationService $anonService,
		private readonly BatchStateService $stateService,
	) {

	}//end __construct()

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
	): array {
		return $this->runBatch(
			batchId: $batchId,
			entities: $entities,
			callOptions: [
				'appendBasisSummary' => false,
				'unredactedEntities' => $unredactedEntities,
				'outputFormat' => $outputFormat,
				'scope' => $scope,
			]
		);

	}//end anonymizeBatch()

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
	): array {
		return $this->runBatch(
			batchId: $batchId,
			entities: $entities,
			callOptions: [
				'appendBasisSummary' => true,
				'unredactedEntities' => $unredactedEntities,
				'outputFormat' => $outputFormat,
				'scope' => $scope,
			]
		);

	}//end anonymizeBatchWithBasisSummary()

	/**
	 * Shared implementation behind both batch entry points.
	 *
	 * @param string $batchId Identifier of the batch to anonymize.
	 * @param array<int, array<string, mixed>> $entities User-approved entities to anonymize.
	 * @param array<string, mixed> $callOptions Per-batch options (appendBasisSummary,
	 *                                          unredactedEntities, outputFormat, scope)
	 *                                          forwarded verbatim to each per-file call.
	 *
	 * @return array Summary of the run, with shape:
	 *               {
	 *               batchId: string,
	 *               batchStatus: string,
	 *               processedFiles: int,
	 *               skippedFiles: array<int, array{fileId: mixed, reason: string}>,
	 *               prohibitionSkippedFiles: int,
	 *               totalFiles: int,
	 *               }
	 *
	 * @throws Exception When the batch cannot be found.
	 *
	 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-anonymization
	 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-5
	 */
	private function runBatch(string $batchId, array $entities, array $callOptions): array {
		$unredactedEntities = $callOptions['unredactedEntities'];

		$batch = $this->stateService->getBatch($batchId);
		if ($batch === null) {
			throw new Exception('Batch not found or expired', 404);
		}

		$batch['status'] = 'anonymizing';
		$this->stateService->updateBatch($batchId, $batch);
		$skipped = [];
		$processed = 0;
		$prohibitionSkipped = 0;

		// $callOptions is constant for every file in the batch and forwarded
		// verbatim to each per-file anonymise call. The dossierKey:null entry
		// applied there makes OpenRegister fall back to each file's parent
		// folder as the dossier (= the batch's folder), so a person is numbered
		// consistently across the batch.
		foreach ($batch['files'] as $i => $file) {
			if ($file['status'] === 'error') {
				$skipped[] = ['fileId' => $file['fileId'], 'reason' => $file['error'] ?? 'Previous error'];
				continue;
			}

			if ($file['status'] !== 'extracted') {
				continue;
			}

			$violations = $this->prohibitionViolationsFor(unredactedEntities: $unredactedEntities);
			if ($violations !== []) {
				$batch['files'][$i]['status'] = 'prohibitionViolation';
				$batch['files'][$i]['prohibitedEntries'] = $violations;
				$skipped[] = [
					'fileId' => $file['fileId'],
					'reason' => 'Prohibition violation on unredacted entities',
					'httpStatus' => 422,
				];
				$prohibitionSkipped++;
				continue;
			}

			$outcome = $this->anonymizeBatchFile(
				entry: $batch['files'][$i],
				entities: $entities,
				callOptions: $callOptions
			);
			$batch['files'][$i] = $outcome['entry'];

			if ($outcome['error'] === null) {
				$processed++;
				continue;
			}

			$skipped[] = ['fileId' => $file['fileId'], 'reason' => $outcome['error']];
		}//end foreach

		$batch['status'] = 'completed';
		$this->stateService->updateBatch($batchId, $batch);
		return [
			'batchId' => $batchId,
			'batchStatus' => 'completed',
			'processedFiles' => $processed,
			'skippedFiles' => $skipped,
			'prohibitionSkippedFiles' => $prohibitionSkipped,
			'totalFiles' => count($batch['files']),
		];

	}//end runBatch()

	/**
	 * Resolve the prohibition violations for the batch's unredacted entities.
	 *
	 * Returns an empty array without consulting the prohibition gate when the
	 * caller supplied no unredacted entities, mirroring the original inline
	 * guard.
	 *
	 * @param array<int, array<string, mixed>> $unredactedEntities Entities to publish unredacted.
	 *
	 * @return array<int, array<string, mixed>> Violation records; empty when the entries may proceed.
	 *
	 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-5
	 */
	private function prohibitionViolationsFor(array $unredactedEntities): array {
		if ($unredactedEntities === []) {
			return [];
		}

		return $this->anonService->checkUnredactedProhibitions(
			unredactedEntities: $unredactedEntities
		);

	}//end prohibitionViolationsFor()

	/**
	 * Anonymize one batch file and fold the outcome into its batch entry.
	 *
	 * A per-file failure is recorded on the entry (status `error`, plus the
	 * conversion attempts surface when the PDF cascade was exhausted) and
	 * reported back through the `error` key so the batch can continue with the
	 * next file.
	 *
	 * @param array<string, mixed> $entry The batch entry for this file.
	 * @param array<int, array<string, mixed>> $entities User-approved entities to anonymize.
	 * @param array<string, mixed> $callOptions Per-batch options; `appendBasisSummary` selects
	 *                                          which AnonymizationService entry point is used,
	 *                                          the rest are forwarded verbatim.
	 *
	 * @return array{entry: array<string, mixed>, error: string|null} The updated entry plus the
	 *                                                                failure reason, or null when
	 *                                                                the file was anonymized.
	 *
	 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-anonymization
	 */
	private function anonymizeBatchFile(array $entry, array $entities, array $callOptions): array {
		try {
			$result = $this->anonymizeOneFile(
				fileId: (int)$entry['fileId'],
				entities: $entities,
				callOptions: $callOptions
			);

			return [
				'entry' => $this->applyAnonymizedResult(entry: $entry, result: $result),
				'error' => null,
			];
		} catch (ConversionFailedException $e) {
			// PDF conversion exhausted the cascade for this file — mark this
			// file as error, attach the attempts surface for the batch caller
			// to inspect, and let the batch continue with the next file.
			$entry['status'] = 'error';
			$entry['error'] = $e->getMessage();
			$entry['conversionAttempts'] = $e->getAttempts();

			return ['entry' => $entry, 'error' => $e->getMessage()];
		} catch (Exception $e) {
			$entry['status'] = 'error';
			$entry['error'] = $e->getMessage();

			return ['entry' => $entry, 'error' => $e->getMessage()];
		}//end try

	}//end anonymizeBatchFile()

	/**
	 * Call the AnonymizationService entry point this batch asked for.
	 *
	 * `appendBasisSummary` selects between the plain and the summary-producing
	 * entry point; every other option is forwarded verbatim. The dossierKey:null
	 * argument makes OpenRegister fall back to the file's parent folder.
	 *
	 * @param int $fileId The Nextcloud file id to anonymize.
	 * @param array<int, array<string, mixed>> $entities User-approved entities to anonymize.
	 * @param array<string, mixed> $callOptions Per-batch options.
	 *
	 * @return array<string, mixed> The AnonymizationService result payload.
	 *
	 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-anonymization
	 */
	private function anonymizeOneFile(int $fileId, array $entities, array $callOptions): array {
		if ($callOptions['appendBasisSummary'] === true) {
			return $this->anonService->anonymizeDocumentWithBasisSummary(
				fileId: $fileId,
				entities: $entities,
				unredactedEntities: $callOptions['unredactedEntities'],
				outputFormat: $callOptions['outputFormat'],
				scope: $callOptions['scope'],
				dossierKey: null
			);
		}

		return $this->anonService->anonymizeDocument(
			fileId: $fileId,
			entities: $entities,
			unredactedEntities: $callOptions['unredactedEntities'],
			outputFormat: $callOptions['outputFormat'],
			scope: $callOptions['scope'],
			dossierKey: null
		);

	}//end anonymizeOneFile()

	/**
	 * Fold a successful anonymise result into the file's batch entry.
	 *
	 * @param array<string, mixed> $entry The batch entry for this file.
	 * @param array<string, mixed> $result The AnonymizationService result payload.
	 *
	 * @return array<string, mixed> The entry with the anonymised status and result fields applied.
	 *
	 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-anonymization
	 */
	private function applyAnonymizedResult(array $entry, array $result): array {
		$entry['status'] = 'anonymized';
		$entry['replacementCount'] = $result['replacementCount'] ?? 0;
		$entry['anonymizedFileId'] = $result['anonymizedFileId'] ?? null;

		if (isset($result['warning']) === true) {
			$entry['warning'] = $result['warning'];
		}

		if (isset($result['summaryFileId']) === true) {
			$entry['summaryFileId'] = $result['summaryFileId'];
			$entry['summaryFilePath'] = $result['summaryFilePath'] ?? null;
		}

		if (isset($result['createdConsents']) === true) {
			$entry['createdConsents'] = $result['createdConsents'];
		}

		return $entry;
	}//end applyAnonymizedResult()
}//end class
