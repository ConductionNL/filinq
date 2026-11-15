<?php

/**
 * The seam where the review gate actually stops a write.
 *
 * 🔴 A GATE CLASS NOBODY CALLS IS A GATE NOBODY PASSES THROUGH. The decision
 * lives in `RedactionReviewGate` and it is correct in isolation, which is
 * exactly the shape that reports green while every output path runs past it.
 * This class is the call site: it sits in `AnonymizationService::runAnonymize`,
 * which is the one funnel the screen, the API, the batch path and the folder
 * job all reach, so there is no route that is merely less convenient.
 *
 * 🔴 IT REFUSES WHEN IT CANNOT TELL. No mark, an unreadable register, a run it
 * cannot name: all three refuse. The alternative is a redaction that proceeds
 * because the thing that would have stopped it was unavailable, which is the
 * failure `anonymisation-prohibition-gate` already ruled out for the detector.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Redaction
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Redaction;

use OCA\Filinq\Exception\RedactionNotReviewedException;
use OCA\Filinq\Kernel\Contracts\RedactionOutputGuardInterface;

/**
 * Refuses a redacted write until somebody has checked this detection run.
 */
class RedactionOutputGuard implements RedactionOutputGuardInterface {

	/**
	 * Constructor.
	 *
	 * @param RedactionReviewGate            $gate     The decision.
	 * @param RedactionReviewMarkRepository  $marks    Where the marks are kept.
	 * @param DetectionRunIdentity           $runs     Names one detection run.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly RedactionReviewGate $gate,
		private readonly RedactionReviewMarkRepository $marks,
		private readonly DetectionRunIdentity $runs,
	) {

	}//end __construct()

	/**
	 * Stop unless this document has been checked for this run.
	 *
	 * @param int                              $fileId   The document about to be written.
	 * @param array<int, array<string, mixed>> $entities The entities this run found.
	 * @param string                           $etag     The file's etag, when known.
	 *
	 * @return void
	 *
	 * @throws RedactionNotReviewedException When nobody has checked this run.
	 *
	 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
	 */
	public function assertMayWrite(int $fileId, array $entities, string $etag = ''): void {
		$document = (string)$fileId;
		$runId = $this->runs->of(fileId: $fileId, entities: $entities, etag: $etag);
		$mark = $this->marks->findFor(document: $document);

		$refusal = $this->gate->refuse(mark: $mark, detectionRunId: $runId);
		if ($refusal === null) {
			return;
		}

		throw new RedactionNotReviewedException(
			reason: (string)($refusal['reason'] ?? ''),
			message: (string)($refusal['message'] ?? ''),
			document: $document,
			checkedRun: (string)($mark['detectionRun'] ?? ''),
			currentRun: $runId,
			instruction: (string)($refusal['instruction'] ?? '')
		);

	}//end assertMayWrite()

	/**
	 * Record that a person checked this run, and say which run that was.
	 *
	 * @param int                              $fileId    The document.
	 * @param array<int, array<string, mixed>> $entities  The entities this run found.
	 * @param string                           $checkedBy Who checked it.
	 * @param string                           $etag      The file's etag, when known.
	 * @param string                           $note      What the checker wanted recorded.
	 *
	 * @return array<string, mixed> The stored mark.
	 *
	 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
	 */
	public function markChecked(
		int $fileId,
		array $entities,
		string $checkedBy,
		string $etag = '',
		string $note = '',
	): array {
		$mark = [
			'document' => (string)$fileId,
			'detectionRun' => $this->runs->of(fileId: $fileId, entities: $entities, etag: $etag),
			'checkedBy' => trim($checkedBy),
			'checkedAt' => gmdate(format: 'c'),
			'entityCount' => count($entities),
		];

		if (trim($note) !== '') {
			$mark['note'] = trim($note);
		}

		return $this->marks->save(mark: $mark);

	}//end markChecked()

	/**
	 * The latest mark of each document, keyed by file id, for a status column.
	 *
	 * Only who and when travel: the mark says a run was checked, and whether
	 * that run is still the current one is for the write path to decide.
	 *
	 * @param array<int, int> $fileIds The documents.
	 *
	 * @return array<int, array{checkedBy: string, checkedAt: string, entityCount: int}> The marks found.
	 *
	 * @spec openspec/changes/archive/2026-10-30-anonymise-asks-for-the-review-mark/tasks.md#task-3
	 */
	public function marksFor(array $fileIds): array {
		$marks = [];
		foreach ($fileIds as $fileId) {
			$mark = $this->marks->findFor(document: (string)$fileId);
			if ($mark === null) {
				continue;
			}

			$marks[$fileId] = [
				'checkedBy' => (string)($mark['checkedBy'] ?? ''),
				'checkedAt' => (string)($mark['checkedAt'] ?? ''),
				'entityCount' => (int)($mark['entityCount'] ?? 0),
			];
		}

		return $marks;

	}//end marksFor()

	/**
	 * Which of these documents may be written, and why the others may not.
	 *
	 * A batch asks once rather than per document, so the operator gets one
	 * list of what is holding the run up instead of a refusal per file.
	 *
	 * @param array<int, array<string, mixed>> $documents Each with `fileId` and `entities`.
	 *
	 * @return array<string, mixed> `allowed` file ids and `refused` entries.
	 *
	 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
	 */
	public function screen(array $documents): array {
		$allowed = [];
		$refused = [];

		foreach ($documents as $document) {
			$fileId = (int)($document['fileId'] ?? 0);
			$entities = $document['entities'] ?? [];
			if (is_array($entities) === false) {
				$entities = [];
			}

			try {
				$this->assertMayWrite(
					fileId: $fileId,
					entities: $entities,
					etag: (string)($document['etag'] ?? '')
				);
				$allowed[] = $fileId;
			} catch (RedactionNotReviewedException $e) {
				$refused[] = [
					'fileId' => $fileId,
					'reason' => $e->getReason(),
					'message' => $e->getMessage(),
				];
			}
		}

		return ['allowed' => $allowed, 'refused' => $refused];

	}//end screen()
}//end class
