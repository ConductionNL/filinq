<?php

/**
 * Batch Background Path: hands a large batch to the cron work units, and resumes it without double work.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/redaction-at-scale
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCA\Filinq\Service\Redaction\AnonymizationLinkReader;

/**
 * Everything the anonymize step does differently for a background batch:
 * whether a batch goes there, storing its reviewed request on it, reading
 * that request back for a work unit, whether a resumed batch goes straight
 * on after extraction, and which file of a resumed batch already has its
 * redacted copy.
 *
 * @spec openspec/changes/redaction-at-scale/specs/batch-anonymization/spec.md#requirement-batch-anonymization
 */
class BatchBackgroundPath {

	/**
	 * Constructor.
	 *
	 * @param BatchStateService       $stateService The batch store.
	 * @param BatchRouting            $routing      The caps and the engine switch.
	 * @param AnonymizationLinkReader $linkReader   Finds a file's redacted copy.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly BatchStateService $stateService,
		private readonly BatchRouting $routing,
		private readonly AnonymizationLinkReader $linkReader,
	) {

	}//end __construct()

	/**
	 * Whether the anonymize step of this batch runs in the background.
	 *
	 * @param array<string, mixed> $batch The batch.
	 *
	 * @return bool True above the synchronous cap with the background engine on.
	 *
	 * @spec openspec/changes/redaction-at-scale/specs/batch-anonymization/spec.md#requirement-batch-anonymization
	 */
	public function takes(array $batch): bool {
		return $this->routing->anonymizesInBackground(fileCount: count((array)($batch['files'] ?? [])));

	}//end takes()

	/**
	 * Whether a batch goes straight on to anonymize once its extraction is done.
	 *
	 * True for a background batch that carries its reviewed request, as a
	 * resumed one does: nothing is left for a person to review.
	 *
	 * @param array<string, mixed> $batch The batch.
	 *
	 * @return bool
	 *
	 * @spec openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#requirement-idempotent-resume-req-ddras-005
	 */
	public function continuesAfterExtraction(array $batch): bool {
		return ($batch['background'] ?? false) === true && is_array($batch['anonymizeRequest'] ?? null) === true;

	}//end continuesAfterExtraction()

	/**
	 * Hand a batch above the synchronous cap to the background path.
	 *
	 * The reviewed entity list and the call options are stored on the batch,
	 * so every work unit applies the same list and the same scope, and a
	 * person keeps one pseudonym across the whole batch.
	 *
	 * @param string               $batchId Identifier of the batch.
	 * @param array<string, mixed> $batch   The batch.
	 * @param BatchAnonymizeRequest $request The reviewed entities and call options.
	 *
	 * @return array<string, mixed> The run summary with `background: true` and the status location.
	 *
	 * @spec openspec/changes/redaction-at-scale/specs/batch-anonymization/spec.md#requirement-batch-anonymization
	 */
	public function enqueue(string $batchId, array $batch, BatchAnonymizeRequest $request): array {
		$batch['anonymizeRequest'] = [
			'entities' => $request->entities,
			'callOptions' => $request->callOptions,
		];
		$batch['status'] = 'anonymizing';
		$batch['background'] = true;
		$batch['totalFiles'] = count((array)($batch['files'] ?? []));
		if (isset($batch['startedAt']) === false) {
			$batch['startedAt'] = time();
		}

		$this->stateService->updateBatch($batchId, $batch);
		return [
			'batchId' => $batchId,
			'batchStatus' => 'anonymizing',
			'background' => true,
			'statusLocation' => '/apps/filinq/api/anonymization/batch/' . $batchId . '/status',
			'processedFiles' => 0,
			'skippedFiles' => [],
			'prohibitionSkippedFiles' => 0,
			'totalFiles' => $batch['totalFiles'],
		];
	}//end enqueue()


	/**
	 * The stored request of a background batch, or null when it has none.
	 *
	 * @param array<string, mixed> $batch The batch.
	 *
	 * @return BatchAnonymizeRequest|null
	 *
	 * @spec openspec/changes/redaction-at-scale/specs/batch-anonymization/spec.md#requirement-batch-anonymization
	 */
	public function storedRequest(array $batch): ?BatchAnonymizeRequest {
		$stored = ($batch['anonymizeRequest'] ?? null);
		if (is_array($stored) === false || is_array($stored['entities'] ?? null) === false) {
			return null;
		}

		$defaults = ['appendBasisSummary' => false, 'unredactedEntities' => [], 'outputFormat' => 'pdf-only', 'scope' => 'dossier'];
		$callOptions = array_merge($defaults, (array)($stored['callOptions'] ?? []));
		$callOptions['appendBasisSummary'] = ($callOptions['appendBasisSummary'] === true);
		$callOptions['unredactedEntities'] = (array)$callOptions['unredactedEntities'];
		$callOptions['outputFormat'] = (string)$callOptions['outputFormat'];
		$callOptions['scope'] = (string)$callOptions['scope'];
		return new BatchAnonymizeRequest(entities: $stored['entities'], callOptions: $callOptions);
	}//end storedRequest()


	/**
	 * Whether a file of a resumed batch already has its redacted copy.
	 *
	 * Keyed on the file's `anonymizationLink`: a link whose copy was made
	 * after this batch started means the file finished between the failure
	 * and the resume, so it is not anonymized a second time. Outside a
	 * resumed batch a link is not a reason to skip: re-anonymizing a file is
	 * a legitimate new run.
	 *
	 * @param array<string, mixed> $batch The batch.
	 * @param array<string, mixed> $entry The file entry.
	 *
	 * @return array<string, mixed>|null The link, or null when the file still needs anonymizing.
	 *
	 * @spec openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#requirement-idempotent-resume-req-ddras-005
	 */
	public function existingOutput(array $batch, array $entry): ?array {
		if (isset($batch['resumedAt']) === false) {
			return null;
		}

		$link = $this->linkReader->forSource(sourceFileId: (int)($entry['fileId'] ?? 0));
		if ($link === null || empty($link['anonymizedFileId']) === true) {
			return null;
		}

		$madeAt = $link['anonymizedAt'] ?? null;
		if (is_string($madeAt) === true) {
			$madeAt = strtotime($madeAt);
		}

		if (is_int($madeAt) === false || $madeAt < (int)($batch['startedAt'] ?? $batch['createdAt'] ?? 0)) {
			return null;
		}

		return $link;
	}//end existingOutput()
}//end class
