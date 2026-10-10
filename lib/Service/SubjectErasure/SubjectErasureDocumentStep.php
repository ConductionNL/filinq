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
		$fileId = (int) $document['id'];
		$result = [
			'document' => (string) $fileId,
			'name' => (string) ($document['name'] ?? ''),
			'outcome' => self::EXCLUDED,
			'occurrences' => 0,
			'reason' => '',
			'obligations' => [],
			'newVersionFileId' => 0,
			'mappingEntriesDestroyed' => 0,
		];

		$refusals = $this->rules->refusals(document: $document);
		if ($refusals !== []) {
			// A held document is kept exactly as it is, its reversible key included.
			$result['outcome'] = self::REFUSED;
			$result['obligations'] = $refusals;
			$result['reason'] = (string) $refusals[0]['reason'];
			$this->audit->record(
				requestUuid: $requestUuid,
				action: SubjectErasureAudit::ACTION_DOCUMENT_REFUSED,
				userId: $userId,
				details: ['fileId' => $fileId, 'obligations' => array_column($refusals, 'obligation')]
			);

			return $result;
		}

		$wholeReason = $this->wholeDocumentExclusion(fileId: $fileId, exclusions: $exclusions);
		if ($wholeReason !== null) {
			$result['reason'] = $wholeReason;

			return $result;
		}

		$values = $this->valuesToErase(document: $document, exclusions: $exclusions);
		$result = $this->eraseContent(document: $document, values: $values, result: $result, requestUuid: $requestUuid);

		return $this->closeTheWayBack(fileId: $fileId, values: $values, result: $result, requestUuid: $requestUuid, userId: $userId);

	}//end process()

	/**
	 * Rewrite the document, or say why it was not.
	 *
	 * @param array<string, mixed> $document    The document.
	 * @param array<int, string>   $values      The values to take out.
	 * @param array<string, mixed> $result      The result so far.
	 * @param string               $requestUuid The request.
	 *
	 * @return array<string, mixed> The result.
	 */
	private function eraseContent(array $document, array $values, array $result, string $requestUuid): array {
		$fileId = (int) $document['id'];
		if ($values === []) {
			$result['reason'] = 'Every identifier found in this document was excluded.';

			return $result;
		}

		if ((string) ($document['unreadable'] ?? '') !== '') {
			$result['outcome'] = self::FAILED;
			$result['reason'] = (string) $document['unreadable'];

			return $result;
		}

		$file = $this->obligations->file(fileId: $fileId);
		try {
			if ($file === null) {
				throw new RuntimeException('The file no longer exists.');
			}

			$record = null;
			if (($document['finalVersion'] ?? false) === true) {
				$record = $this->finalDocuments->findCurrent(fileId: $fileId);
			}

			$done = ['newVersionFileId' => 0];
			if ($record === null) {
				$this->eraser->erase(file: $file, values: $values);
			}

			if ($record !== null) {
				$done = $this->eraser->eraseFinal(file: $file, values: $values, record: $record, requestUuid: $requestUuid);
			}
		} catch (RuntimeException $e) {
			$result['outcome'] = self::FAILED;
			$result['reason'] = $e->getMessage();

			return $result;
		}//end try

		$result['outcome'] = self::ERASED;
		$result['occurrences'] = (int) ($document['occurrences'] ?? 0);
		$result['newVersionFileId'] = (int) $done['newVersionFileId'];

		return $result;

	}//end eraseContent()

	/**
	 * Destroy the person's entries in the reversible keys of this document, and audit the outcome.
	 *
	 * @param int                  $fileId      The document.
	 * @param array<int, string>   $values      The erased values.
	 * @param array<string, mixed> $result      The result so far.
	 * @param string               $requestUuid The request.
	 * @param string               $userId      The caller.
	 *
	 * @return array<string, mixed> The result.
	 *
	 * @throws SubjectErasureRefusedException When the audit trail refuses an entry.
	 */
	private function closeTheWayBack(int $fileId, array $values, array $result, string $requestUuid, string $userId): array {
		if ($values !== []) {
			try {
				$destroyed = $this->maps->forgetValues(sourceFileId: $fileId, values: $values);
				$result['mappingEntriesDestroyed'] = $destroyed['entriesDestroyed'];
				if ($destroyed['entriesDestroyed'] > 0) {
					$this->audit->record(
						requestUuid: $requestUuid,
						action: SubjectErasureAudit::ACTION_MAPPING_DESTROYED,
						userId: $userId,
						details: array_merge(['fileId' => $fileId], $destroyed)
					);
				}
			} catch (RuntimeException $e) {
				// The way back is still open, so the document is not done.
				$result['outcome'] = self::FAILED;
				$result['reason'] = trim($result['reason'] . ' The reversible key could not be destroyed: ' . $e->getMessage());
			}
		}

		$action = SubjectErasureAudit::ACTION_DOCUMENT_ERASED;
		if ($result['outcome'] === self::FAILED) {
			$action = SubjectErasureAudit::ACTION_DOCUMENT_FAILED;
		}

		if ($result['outcome'] !== self::EXCLUDED) {
			$this->audit->record(
				requestUuid: $requestUuid,
				action: $action,
				userId: $userId,
				details: [
					'fileId' => $fileId,
					'occurrences' => $result['occurrences'],
					'newVersionFileId' => $result['newVersionFileId'],
					'reason' => $result['reason'],
				]
			);
		}

		return $result;

	}//end closeTheWayBack()

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
