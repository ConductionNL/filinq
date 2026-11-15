<?php

/**
 * One document of an erasure run: refuse, exclude, or erase, then close the way back.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category  Service
 * @package   OCA\Filinq\Service\SubjectErasure
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-erase-a-person-while-the-records-stay/tasks.md#task-3.1
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\SubjectErasure;

use OCA\Filinq\Exception\SubjectErasureRefusedException;
use OCA\Filinq\Service\FinalDocumentRepository;
use OCA\Filinq\Service\Pseudonymisation\PseudonymMapService;
use OCP\Files\File;
use RuntimeException;

/**
 * Refusals come first, read at run time rather than from the preview: a hold
 * placed after the preview still wins. Then exclusions, then the erasure, then
 * the reversible key. Every outcome but an operator's exclusion writes an audit
 * entry, and a document whose entry cannot be written stops the run.
 */
class SubjectErasureDocumentStep {

	public const ERASED = 'erased';

	public const REFUSED = 'refused';

	public const EXCLUDED = 'excluded';

	public const FAILED = 'failed';

	/**
	 * Constructor.
	 *
	 * @param SubjectErasureRules       $rules          What refuses.
	 * @param SubjectErasureObligations $obligations    Resolves the file.
	 * @param SubjectErasureEraser      $eraser         Rewrites the file.
	 * @param FinalDocumentRepository   $finalDocuments The finalisation records.
	 * @param PseudonymMapService       $maps           The reversible keys.
	 * @param SubjectErasureAudit       $audit          The audit trail.
	 */
	public function __construct(
		private readonly SubjectErasureRules $rules,
		private readonly SubjectErasureObligations $obligations,
		private readonly SubjectErasureEraser $eraser,
		private readonly FinalDocumentRepository $finalDocuments,
		private readonly PseudonymMapService $maps,
		private readonly SubjectErasureAudit $audit,
	) {

	}//end __construct()

	/**
	 * Process one assessed document.
	 *
	 * @param array<string, mixed>             $document    From SubjectErasureObligations::assess().
	 * @param array<int, array<string, mixed>> $exclusions  The request's exclusions.
	 * @param string                           $requestUuid The request.
	 * @param string                           $userId      The caller.
	 *
	 * @return array<string, mixed> The result: document, name, outcome, occurrences,
	 *                              reason, obligations, newVersionFileId, mappingEntriesDestroyed.
	 *
	 * @throws SubjectErasureRefusedException When the audit trail refuses the entry.
	 *
	 * @spec openspec/changes/archive/2026-09-29-erase-a-person-while-the-records-stay/tasks.md#task-4.1
	 */
	public function process(array $document, array $exclusions, string $requestUuid, string $userId): array {
		$run = $this->startRun(document: $document, requestUuid: $requestUuid);
		$run->userId = $userId;

		$untouched = $this->refusedOrExcluded(run: $run, exclusions: $exclusions);
		if ($untouched !== null) {
			return $untouched;
		}

		$run->values = $this->valuesToErase(document: $document, exclusions: $exclusions);
		$this->eraseContent(run: $run);

		return $this->closeTheWayBack(run: $run);

	}//end process()

	/**
	 * Start the run for one document, its result still `excluded` with nothing done.
	 *
	 * @param array<string, mixed> $document    The document.
	 * @param string               $requestUuid The request.
	 *
	 * @return DocumentErasureRun The run.
	 */
	private function startRun(array $document, string $requestUuid): DocumentErasureRun {
		$run = new DocumentErasureRun();
		$run->document = $document;
		$run->fileId = (int) $document['id'];
		$run->requestUuid = $requestUuid;
		$run->result = $this->untouchedResult(document: $document);

		return $run;

	}//end startRun()

	/**
	 * The result of a document nothing was done to yet: `excluded`, no reason.
	 *
	 * @param array<string, mixed> $document The document.
	 *
	 * @return array<string, mixed> The result.
	 */
	private function untouchedResult(array $document): array {
		return [
			'document' => (string) (int) $document['id'],
			'name' => (string) ($document['name'] ?? ''),
			'outcome' => self::EXCLUDED,
			'occurrences' => 0,
			'reason' => '',
			'obligations' => [],
			'newVersionFileId' => 0,
			'mappingEntriesDestroyed' => 0,
		];

	}//end untouchedResult()

	/**
	 * The result for a document that is refused or excluded as a whole, or null when it is to be erased.
	 *
	 * Refusals come first, read at run time: a hold placed after the preview still wins.
	 *
	 * @param DocumentErasureRun               $run        The run.
	 * @param array<int, array<string, mixed>> $exclusions The request's exclusions.
	 *
	 * @return array<string, mixed>|null The result, or null.
	 *
	 * @throws SubjectErasureRefusedException When the audit trail refuses the entry.
	 */
	private function refusedOrExcluded(DocumentErasureRun $run, array $exclusions): ?array {
		$refusals = $this->rules->refusals(document: $run->document);
		if ($refusals !== []) {
			return $this->refuse(run: $run, refusals: $refusals);
		}

		$wholeReason = $this->wholeDocumentExclusion(fileId: $run->fileId, exclusions: $exclusions);
		if ($wholeReason === null) {
			return null;
		}

		$run->result['reason'] = $wholeReason;

		return $run->result;

	}//end refusedOrExcluded()

	/**
	 * Refuse a held document and audit the refusal.
	 *
	 * A held document is kept exactly as it is, its reversible key included.
	 *
	 * @param DocumentErasureRun               $run      The run.
	 * @param array<int, array<string, mixed>> $refusals The obligations that refuse it.
	 *
	 * @return array<string, mixed> The result.
	 *
	 * @throws SubjectErasureRefusedException When the audit trail refuses the entry.
	 */
	private function refuse(DocumentErasureRun $run, array $refusals): array {
		$run->result['outcome'] = self::REFUSED;
		$run->result['obligations'] = $refusals;
		$run->result['reason'] = (string) $refusals[0]['reason'];
		$this->audit->record(
			requestUuid: $run->requestUuid,
			action: SubjectErasureAudit::ACTION_DOCUMENT_REFUSED,
			userId: $run->userId,
			details: ['fileId' => $run->fileId, 'obligations' => array_column($refusals, 'obligation')]
		);

		return $run->result;

	}//end refuse()

	/**
	 * Rewrite the document, or say why it was not.
	 *
	 * @param DocumentErasureRun $run The run.
	 *
	 * @return void
	 */
	private function eraseContent(DocumentErasureRun $run): void {
		if ($run->values === []) {
			$run->result['reason'] = 'Every identifier found in this document was excluded.';

			return;
		}

		if ((string) ($run->document['unreadable'] ?? '') !== '') {
			$run->result['outcome'] = self::FAILED;
			$run->result['reason'] = (string) $run->document['unreadable'];

			return;
		}

		$this->rewrite(run: $run);

	}//end eraseContent()

	/**
	 * Rewrite a readable document, recording the erasure or the failure.
	 *
	 * @param DocumentErasureRun $run The run.
	 *
	 * @return void
	 */
	private function rewrite(DocumentErasureRun $run): void {
		$file = $this->obligations->file(fileId: $run->fileId);
		try {
			$done = $this->rewriteFile(run: $run, file: $file);
		} catch (RuntimeException $e) {
			$run->result['outcome'] = self::FAILED;
			$run->result['reason'] = $e->getMessage();

			return;
		}

		$run->result['outcome'] = self::ERASED;
		$run->result['occurrences'] = (int) ($run->document['occurrences'] ?? 0);
		$run->result['newVersionFileId'] = (int) $done['newVersionFileId'];

	}//end rewrite()

	/**
	 * Erase the values from the file: a final current version gets a new version beside it.
	 *
	 * @param DocumentErasureRun $run  The run.
	 * @param File|null          $file The file, null when it no longer exists.
	 *
	 * @return array<string, mixed> What was done: at least `newVersionFileId`.
	 *
	 * @throws RuntimeException When the file is gone or cannot be rewritten.
	 */
	private function rewriteFile(DocumentErasureRun $run, ?File $file): array {
		if ($file === null) {
			throw new RuntimeException('The file no longer exists.');
		}

		$record = $this->currentFinalRecord(run: $run);
		if ($record === null) {
			$this->eraser->erase(file: $file, values: $run->values);

			return ['newVersionFileId' => 0];
		}

		return $this->eraser->eraseFinal(file: $file, values: $run->values, record: $record, requestUuid: $run->requestUuid);

	}//end rewriteFile()

	/**
	 * The finalisation record of a final version, or null.
	 *
	 * @param DocumentErasureRun $run The run.
	 *
	 * @return array<string, mixed>|null The current record, null for a document that is not final.
	 */
	private function currentFinalRecord(DocumentErasureRun $run): ?array {
		if (($run->document['finalVersion'] ?? false) === true) {
			return $this->finalDocuments->findCurrent(fileId: $run->fileId);
		}

		return null;

	}//end currentFinalRecord()

	/**
	 * Destroy the person's entries in the reversible keys of this document, and audit the outcome.
	 *
	 * @param DocumentErasureRun $run The run.
	 *
	 * @return array<string, mixed> The result.
	 *
	 * @throws SubjectErasureRefusedException When the audit trail refuses an entry.
	 */
	private function closeTheWayBack(DocumentErasureRun $run): array {
		if ($run->values !== []) {
			$this->forgetMappings(run: $run);
		}

		$this->auditOutcome(run: $run);

		return $run->result;

	}//end closeTheWayBack()

	/**
	 * Destroy the person's entries in the reversible keys, failing the document when that is impossible.
	 *
	 * @param DocumentErasureRun $run The run.
	 *
	 * @return void
	 *
	 * @throws SubjectErasureRefusedException When the audit trail refuses the entry.
	 */
	private function forgetMappings(DocumentErasureRun $run): void {
		try {
			$destroyed = $this->maps->forgetValues(sourceFileId: $run->fileId, values: $run->values);
			$run->result['mappingEntriesDestroyed'] = $destroyed['entriesDestroyed'];
			if ($destroyed['entriesDestroyed'] > 0) {
				$this->auditMappingDestroyed(run: $run, destroyed: $destroyed);
			}
		} catch (RuntimeException $e) {
			// The way back is still open, so the document is not done.
			$run->result['outcome'] = self::FAILED;
			$run->result['reason'] = trim($run->result['reason'] . ' The reversible key could not be destroyed: ' . $e->getMessage());
		}

	}//end forgetMappings()

	/**
	 * Audit the destroyed reversible key entries.
	 *
	 * @param DocumentErasureRun   $run       The run.
	 * @param array<string, mixed> $destroyed What forgetValues() destroyed.
	 *
	 * @return void
	 *
	 * @throws SubjectErasureRefusedException When the audit trail refuses the entry.
	 */
	private function auditMappingDestroyed(DocumentErasureRun $run, array $destroyed): void {
		$this->audit->record(
			requestUuid: $run->requestUuid,
			action: SubjectErasureAudit::ACTION_MAPPING_DESTROYED,
			userId: $run->userId,
			details: array_merge(['fileId' => $run->fileId], $destroyed)
		);

	}//end auditMappingDestroyed()

	/**
	 * Audit an erased or failed document; an operator's exclusion is not audited here.
	 *
	 * @param DocumentErasureRun $run The run.
	 *
	 * @return void
	 *
	 * @throws SubjectErasureRefusedException When the audit trail refuses the entry.
	 */
	private function auditOutcome(DocumentErasureRun $run): void {
		if ($run->result['outcome'] === self::EXCLUDED) {
			return;
		}

		$this->audit->record(
			requestUuid: $run->requestUuid,
			action: $this->outcomeAction(outcome: $run->result['outcome']),
			userId: $run->userId,
			details: $this->outcomeDetails(run: $run)
		);

	}//end auditOutcome()

	/**
	 * The audit details of a processed document.
	 *
	 * @param DocumentErasureRun $run The run.
	 *
	 * @return array<string, mixed> The details.
	 */
	private function outcomeDetails(DocumentErasureRun $run): array {
		return [
			'fileId' => $run->fileId,
			'occurrences' => $run->result['occurrences'],
			'newVersionFileId' => $run->result['newVersionFileId'],
			'reason' => $run->result['reason'],
		];

	}//end outcomeDetails()

	/**
	 * The audit action for a processed document's outcome.
	 *
	 * @param string $outcome The outcome: erased or failed.
	 *
	 * @return string The action.
	 */
	private function outcomeAction(string $outcome): string {
		if ($outcome === self::FAILED) {
			return SubjectErasureAudit::ACTION_DOCUMENT_FAILED;
		}

		return SubjectErasureAudit::ACTION_DOCUMENT_ERASED;

	}//end outcomeAction()

	/**
	 * The reason a whole document was excluded, or null.
	 *
	 * @param int                              $fileId     The document.
	 * @param array<int, array<string, mixed>> $exclusions The exclusions.
	 *
	 * @return string|null The reason.
	 */
	private function wholeDocumentExclusion(int $fileId, array $exclusions): ?string {
		foreach ($exclusions as $exclusion) {
			if ((string) ($exclusion['occurrence'] ?? '') === (string) $fileId) {
				return (string) ($exclusion['reason'] ?? '');
			}
		}

		return null;

	}//end wholeDocumentExclusion()

	/**
	 * The document's identifiers minus those excluded for it.
	 *
	 * @param array<string, mixed>             $document   The document.
	 * @param array<int, array<string, mixed>> $exclusions The exclusions.
	 *
	 * @return array<int, string> The values.
	 */
	private function valuesToErase(array $document, array $exclusions): array {
		$prefix = (string) $document['id'] . ':';
		$excluded = [];
		foreach ($exclusions as $exclusion) {
			$occurrence = (string) ($exclusion['occurrence'] ?? '');
			if (str_starts_with($occurrence, $prefix) === true) {
				$excluded[] = substr($occurrence, strlen($prefix));
			}
		}

		return array_values(array_diff((array) ($document['values'] ?? []), $excluded));

	}//end valuesToErase()
}//end class
