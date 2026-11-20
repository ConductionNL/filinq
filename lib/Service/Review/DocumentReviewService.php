<?php

/**
 * The per-document checked mark of the anonymisation review workbench.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Review
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-2.3
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Review;

use DateTimeImmutable;
use DateTimeInterface;
use OCA\Filinq\Exception\DocumentReviewRefusedException;
use OCA\Filinq\Service\OpenRegisterServiceLocator;
use OCP\IAppConfig;
use OCP\IL10N;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Marks a document checked, reads whether the mark still holds, and answers the commit gate.
 *
 * The mark stores a fingerprint of the file's entity decisions (which relations
 * exist, whether each is skipped, and its grondslagen). Any later toggle, manual
 * entity or bases change alters the fingerprint, so the mark no longer holds and
 * the document counts as unchecked until it is checked again (design.md D2).
 *
 * @category Service
 * @package  OCA\Filinq\Service\Review
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity) The complexity is the sum of small independent
 * checks of one review (findings, decisions and the checked gate).
 *
 * @spec openspec/changes/anonymization-review-workbench/specs/anonymization-review-workbench/spec.md#requirement-per-document-checked-gate-req-ddarw-007
 */
class DocumentReviewService {

	/**
	 * App-config key of the gate mode.
	 *
	 * @var string
	 */
	public const GATE_KEY = 'filinq.review.checked_gate';

	/**
	 * Gate mode: an unchecked document is refused.
	 *
	 * @var string
	 */
	public const MODE_ENFORCED = 'enforced';

	/**
	 * Gate mode: an unchecked document proceeds, and the verdict is reported.
	 *
	 * @var string
	 */
	public const MODE_ADVISORY = 'advisory';

	/**
	 * The refusal reason of a single-file commit.
	 *
	 * @var string
	 */
	public const REASON_DOCUMENT = 'document_not_reviewed';

	/**
	 * The refusal reason of a batch commit.
	 *
	 * @var string
	 */
	public const REASON_BATCH = 'documents_not_reviewed';

	/**
	 * The detection method OpenRegister records on a manual entity.
	 *
	 * @var string
	 */
	private const METHOD_MANUAL = 'manual';

	/**
	 * The text layer of a rendition detection could not read (ReviewRenditionService::LAYER_NONE).
	 *
	 * @var string
	 */
	private const LAYER_NONE = 'none';

	/**
	 * The relation mapper OpenRegister exposes.
	 *
	 * @var string
	 */
	private const RELATION_MAPPER = 'OCA\OpenRegister\Db\EntityRelationMapper';

	/**
	 * The decisions of a file whose relations could not be read: nothing extracted, and no
	 * fingerprint, so no mark holds.
	 *
	 * @var array{count: 0, manual: 0, fingerprint: null, uncertain: 0, undecidedUncertain: array{}}
	 */
	private const NO_DECISIONS = ['count' => 0, 'manual' => 0, 'fingerprint' => null, 'uncertain' => 0, 'undecidedUncertain' => []];

	/**
	 * Constructor.
	 *
	 * @param DocumentReviewRepository   $repository The `documentReview` store.
	 * @param OpenRegisterServiceLocator $locator    Resolver for OpenRegister's relation mapper.
	 * @param IAppConfig                 $appConfig  App config (gate mode).
	 * @param LoggerInterface            $logger     Logger for diagnostics.
	 * @param IL10N                      $l10n       Translator for the refusal message.
	 * @param ReviewRenditionRepository  $renditions The current review rendition per source.
	 * @param CertaintyThresholds        $thresholds The detection floor and certain-from.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly DocumentReviewRepository $repository,
		private readonly OpenRegisterServiceLocator $locator,
		private readonly IAppConfig $appConfig,
		private readonly LoggerInterface $logger,
		private readonly IL10N $l10n,
		private readonly ReviewRenditionRepository $renditions,
		private readonly CertaintyThresholds $thresholds,
	) {

	}//end __construct()

	/**
	 * The gate mode; anything but `advisory` reads as `enforced`.
	 *
	 * @return string `enforced` or `advisory`.
	 *
	 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-2.4
	 */
	public function gateMode(): string {
		$mode = $this->appConfig->getValueString('filinq', self::GATE_KEY, self::MODE_ENFORCED);
		if ($mode === self::MODE_ADVISORY) {
			return self::MODE_ADVISORY;
		}

		return self::MODE_ENFORCED;

	}//end gateMode()

	/**
	 * Where one file's review stands.
	 *
	 * @param int $fileId The Nextcloud file id.
	 *
	 * @return array{fileId: int, checked: bool, stale: bool, extracted: bool, entityCount: int,
	 *               manualEntityCount: int, review: array<string, mixed>|null,
	 *               rendition: array<string, mixed>|null, thresholds: array<string, mixed>,
	 *               uncertainCount: int, undecidedUncertain: list<array<string, mixed>>}
	 *         `checked` is true only while a mark exists, its fingerprint still matches and it
	 *         was made on the current rendition; `stale` is true when a mark exists but no longer holds.
	 *
	 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-2.3
	 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-5.2
	 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-5.3
	 */
	public function status(int $fileId): array {
		$thresholds = $this->thresholds->read();
		$decisions = ($this->decisions(fileId: $fileId, certainFrom: $thresholds['certainFrom']) ?? self::NO_DECISIONS);
		$review = $this->repository->findFor(fileId: $fileId);
		$rendition = $this->renditions->findFor(sourceFileId: $fileId);
		$holds = $this->markHolds(review: $review, decisions: $decisions, rendition: $rendition);

		return [
			'fileId' => $fileId,
			'checked' => $holds,
			'stale' => ($review !== null && $holds === false),
			'extracted' => ($decisions['count'] > 0),
			'entityCount' => $decisions['count'],
			'manualEntityCount' => $decisions['manual'],
			'review' => $review,
			'rendition' => $rendition,
			'thresholds' => $thresholds,
			'uncertainCount' => $decisions['uncertain'],
			'undecidedUncertain' => $decisions['undecidedUncertain'],
		];

	}//end status()

	/**
	 * Whether a mark still holds: it exists, its fingerprint matches the decisions and it was
	 * made on the current rendition. Decisions that could not be read never hold a mark.
	 *
	 * @param array<string, mixed>|null $review    The mark.
	 * @param array<string, mixed>      $decisions The decisions, as {@see decisions}, or NO_DECISIONS.
	 * @param array<string, mixed>|null $rendition The current rendition.
	 *
	 * @return bool True when the mark holds.
	 *
	 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-5.2
	 */
	private function markHolds(array|null $review, array $decisions, array|null $rendition): bool {
		if ($review === null || $decisions['fingerprint'] === null) {
			return false;
		}

		return hash_equals((string)($review['decisionFingerprint'] ?? ''), $decisions['fingerprint']) === true
			&& $this->sameRendition(review: $review, rendition: $rendition) === true;

	}//end markHolds()

	/**
	 * Whether a mark was made on the rendition that is current now.
	 *
	 * A mark made before any rendition existed holds while there still is none;
	 * once one exists, only a mark on its sha256 holds.
	 *
	 * @param array<string, mixed>      $review    The mark.
	 * @param array<string, mixed>|null $rendition The current rendition.
	 *
	 * @return bool True when they match.
	 *
	 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-5.2
	 */
	private function sameRendition(array $review, ?array $rendition): bool {
		return hash_equals((string)($rendition['sha256'] ?? ''), (string)($review['renditionSha256'] ?? ''));

	}//end sameRendition()

	/**
	 * Mark one file checked by a user, replacing any earlier mark.
	 *
	 * The mark is saved with OpenRegister RBAC on, so only the roles the
	 * `documentReview` schema lets create one can check a document.
	 *
	 * @param int    $fileId The Nextcloud file id.
	 * @param string $userId The reviewer.
	 * @param string $note   The reviewer's note, may be empty.
	 * @param bool   $byEye  The reviewer confirms every page was read by eye; required when the
	 *                       rendition has no text layer.
	 * @param array<int|string> $decided The relation ids of the uncertain findings the reviewer decided
	 *                                 (included or skipped) in the workbench.
	 *
	 * @return array<string, mixed> The status after the check, as {@see status}.
	 *
	 * @throws DocumentReviewRefusedException When extraction has not completed for the file, the
	 *                                        rendition has no text layer and the by-eye confirmation
	 *                                        is missing, or an uncertain finding is undecided.
	 *
	 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-2.3
	 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-5.2
	 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-5.3
	 *
	 * @SuppressWarnings(PHPMD.BooleanArgumentFlag) The confirmation is the reviewer's own yes or no.
	 */
	public function check(int $fileId, string $userId, string $note = '', bool $byEye = false, array $decided = []): array {
		$rendition = $this->renditions->findFor(sourceFileId: $fileId);
		$decisions = $this->decisions(fileId: $fileId, certainFrom: $this->thresholds->certainFrom());
		$this->assertCheckable(rendition: $rendition, decisions: $decisions, byEye: $byEye, decided: $decided);

		$mark = [
			'fileId' => $fileId,
			'checkedOn' => (new DateTimeImmutable())->format(DateTimeInterface::ATOM),
			'checkedBy' => $userId,
			'entityCountAtCheck' => $decisions['count'],
			'manualEntityCount' => $decisions['manual'],
			'decisionFingerprint' => $decisions['fingerprint'],
		];
		if ($rendition !== null) {
			$mark['renditionSha256'] = (string)($rendition['sha256'] ?? '');
		}

		if ($byEye === true) {
			$mark['readByEye'] = true;
		}

		if ($note !== '') {
			$mark['note'] = $note;
		}

		// A re-check replaces the earlier mark by creating a new one and then
		// removing the old: the schema lets a woo officer create a mark but not
		// update or delete one. The save runs under the caller's rights first,
		// so a caller who may not mark a document removes nothing.
		$earlier = $this->repository->findFor(fileId: $fileId);
		$this->repository->save(mark: $mark);
		if ((string)($earlier['uuid'] ?? '') !== '') {
			$this->repository->delete(uuid: (string)$earlier['uuid']);
		}

		return $this->status(fileId: $fileId);

	}//end check()

	/**
	 * Refuse a check that cannot hold: nothing to review, unread pages, or an undecided uncertain finding.
	 *
	 * A rendition without a text layer has no findings because detection could
	 * not read it, so it needs the by-eye confirmation instead of findings.
	 *
	 * @param array<string, mixed>|null $rendition The current rendition.
	 * @param array<string, mixed>|null $decisions The file's decisions, as {@see decisions}.
	 * @param bool                      $byEye     The by-eye confirmation.
	 * @param array<int|string>         $decided   The relation ids the reviewer decided in the workbench.
	 *
	 * @return void
	 *
	 * @throws DocumentReviewRefusedException With the reason of the refusal.
	 *
	 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-5.2
	 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-5.3
	 *
	 * @SuppressWarnings(PHPMD.BooleanArgumentFlag) The confirmation is the reviewer's own yes or no.
	 */
	private function assertCheckable(array|null $rendition, array|null $decisions, bool $byEye, array $decided): void {
		$unreadable = $this->assertReadable(rendition: $rendition, byEye: $byEye);
		if ($decisions === null || ($decisions['count'] === 0 && $unreadable === false)) {
			throw new DocumentReviewRefusedException(
				reason: 'not_extracted',
				message: 'This document has no extracted findings yet; run detection before marking it reviewed.'
			);
		}

		$this->assertUncertainDecided(undecidedUncertain: $decisions['undecidedUncertain'], decided: $decided);

	}//end assertCheckable()

	/**
	 * Refuse a check of a rendition detection could not read, unless the reviewer read it by eye.
	 *
	 * @param array<string, mixed>|null $rendition The current rendition.
	 * @param bool                      $byEye     The by-eye confirmation.
	 *
	 * @return bool True when the rendition has no text layer (and the reviewer read it by eye).
	 *
	 * @throws DocumentReviewRefusedException When it has no text layer and the confirmation is missing.
	 *
	 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-5.2
	 *
	 * @SuppressWarnings(PHPMD.BooleanArgumentFlag) The confirmation is the reviewer's own yes or no.
	 */
	private function assertReadable(array|null $rendition, bool $byEye): bool {
		$unreadable = (($rendition['textLayer'] ?? '') === self::LAYER_NONE);
		if ($unreadable === true && $byEye === false) {
			throw new DocumentReviewRefusedException(
				reason: 'no_text_layer',
				message: 'Detection could not read the pages of this document. Confirm that you read every page by eye before marking it reviewed.'
			);
		}

		return $unreadable;

	}//end assertReadable()

	/**
	 * Refuse a check while an uncertain finding is undecided.
	 *
	 * An uncertain finding counts as decided when OpenRegister holds a decision
	 * for it, or when the reviewer included or skipped it in the workbench.
	 *
	 * @param list<array{relationId: int, entityId: int, confidence: float}> $undecidedUncertain The undecided findings.
	 * @param array<int|string>                                                $decided            The ids decided in the workbench.
	 *
	 * @return void
	 *
	 * @throws DocumentReviewRefusedException When one is still undecided.
	 *
	 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-5.3
	 */
	private function assertUncertainDecided(array $undecidedUncertain, array $decided): void {
		$decidedIds = array_map('intval', $decided);
		$undecided = array_values(
			array_filter(
				$undecidedUncertain,
				static fn (array $finding): bool => in_array($finding['relationId'], $decidedIds, true) === false
			)
		);
		if ($undecided !== []) {
			throw new DocumentReviewRefusedException(
				reason: 'uncertain_undecided',
				message: 'Decide on every uncertain finding before marking this document reviewed.',
				details: ['undecidedFindings' => $undecided]
			);
		}

	}//end assertCheckable()

	/**
	 * Remove a file's mark as the calling user; a file without one is left as it is.
	 *
	 * @param int $fileId The Nextcloud file id.
	 *
	 * @return bool True when a mark was removed.
	 *
	 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-2.3
	 */
	public function uncheck(int $fileId): bool {
		$existing = $this->repository->findFor(fileId: $fileId);
		if ($existing === null || (string)($existing['uuid'] ?? '') === '') {
			return false;
		}

		// As the caller: the schema's delete rule decides who may remove a mark.
		$this->repository->deleteAsCaller(uuid: (string)$existing['uuid']);
		return true;

	}//end uncheck()

	/**
	 * The checked-gate verdict for a set of files.
	 *
	 * @param array<int, array{fileId: int|string, fileName?: string}> $files The files a commit covers.
	 *
	 * @return array{passed: bool, mode: string, uncheckedFiles: list<array{fileId: int, fileName: string}>} The verdict.
	 *
	 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-2.4
	 */
	public function gate(array $files): array {
		$unchecked = [];
		foreach ($files as $file) {
			$fileId = (int)$file['fileId'];
			if ($this->status(fileId: $fileId)['checked'] === true) {
				continue;
			}

			$unchecked[] = ['fileId' => $fileId, 'fileName' => (string)($file['fileName'] ?? '')];
		}

		return [
			'passed' => ($unchecked === []),
			'mode' => $this->gateMode(),
			'uncheckedFiles' => $unchecked,
		];

	}//end gate()

	/**
	 * The commit gate for a set of files: a 409 refusal, an advisory report, or neither.
	 *
	 * Enforced and not every file checked: the refusal, and the commit must not run.
	 * Advisory: no refusal, and the report the commit's answer carries as `checkedGate`.
	 * Enforced and all checked: neither, so the commit's answer is unchanged.
	 *
	 * @param array<int, array{fileId: int|string, fileName?: string}> $files  The files the commit covers.
	 * @param string                                                   $reason REASON_DOCUMENT or REASON_BATCH.
	 *
	 * @return array{refusal: array{status: int, body: array<string, mixed>}|null,
	 *               report: array{passed: bool, uncheckedFiles: list<array{fileId: int, fileName: string}>}|null}
	 *
	 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-2.4
	 */
	public function commitGate(array $files, string $reason): array {
		$verdict = $this->gate(files: $files);
		$report = ['passed' => $verdict['passed'], 'uncheckedFiles' => $verdict['uncheckedFiles']];
		if ($verdict['mode'] === self::MODE_ADVISORY) {
			return ['refusal' => null, 'report' => $report];
		}

		if ($verdict['passed'] === true) {
			return ['refusal' => null, 'report' => null];
		}

		$message = $this->l10n->t('This document has not been marked as reviewed since its findings last changed. Review it before anonymising.');
		if ($reason === self::REASON_BATCH) {
			$message = $this->l10n->t('Not every document in this batch has been marked as reviewed since its findings last changed. Nothing was anonymised.');
		}

		return [
			'refusal' => [
				'status' => 409,
				'body' => ['error' => $message, 'reason' => $reason, 'uncheckedFiles' => $verdict['uncheckedFiles']],
			],
			'report' => null,
		];

	}//end commitGate()

	/**
	 * The files of a batch the gate judges: every file not in error.
	 *
	 * @param array<string, mixed>|null $batch The batch record, or null when it is unknown.
	 *
	 * @return list<array{fileId: int, fileName: string}> The files.
	 *
	 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-2.4
	 */
	public function batchFiles(?array $batch): array {
		$files = [];
		foreach ((array)($batch['files'] ?? []) as $file) {
			if (($file['status'] ?? '') !== 'error' && isset($file['fileId']) === true) {
				$files[] = ['fileId' => (int)$file['fileId'], 'fileName' => (string)($file['fileName'] ?? '')];
			}
		}

		return $files;

	}//end batchFiles()

	/**
	 * The file's entity decisions: their count, manual count, fingerprint and uncertain findings.
	 *
	 * @param int   $fileId      The Nextcloud file id.
	 * @param float $certainFrom A detected finding below it is uncertain.
	 *
	 * @return array{count: int, manual: int, fingerprint: string, uncertain: int,
	 *               undecidedUncertain: list<array{relationId: int, entityId: int, confidence: float}>}|null
	 *         Null when the relations cannot be read.
	 *
	 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-2.3
	 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-5.3
	 */
	private function decisions(int $fileId, float $certainFrom): ?array {
		try {
			$relations = $this->locator->get(className: self::RELATION_MAPPER)->findByFileId($fileId);
		} catch (Throwable $e) {
			$this->logger->warning(
				'[DocumentReviewService] could not read the entity decisions; the document counts as unchecked',
				['fileId' => $fileId, 'error' => $e->getMessage()]
			);
			return null;
		}

		$tally = ['rows' => [], 'manual' => 0, 'uncertain' => 0, 'undecided' => []];
		foreach ((array)$relations as $relation) {
			$data = $this->rowOf(relation: $relation);
			if ($data !== null) {
				$tally = $this->tally(tally: $tally, data: $data, certainFrom: $certainFrom);
			}
		}

		$rows = $tally['rows'];
		$undecided = $tally['undecided'];
		usort($rows, static fn (array $a, array $b): int => $a[0] <=> $b[0]);
		usort($undecided, static fn (array $a, array $b): int => $a['relationId'] <=> $b['relationId']);

		return [
			'count' => count($rows),
			'manual' => $tally['manual'],
			'fingerprint' => hash('sha256', (string)json_encode($rows)),
			'uncertain' => $tally['uncertain'],
			'undecidedUncertain' => $undecided,
		];

	}//end decisions()

	/**
	 * Count one relation into the running tally of a file's decisions.
	 *
	 * @param array{rows: list<array<int, mixed>>, manual: int, uncertain: int, undecided: list<array<string, mixed>>} $tally       The tally.
	 * @param array<string, mixed> $data        The relation's fields.
	 * @param float                $certainFrom A detected finding below it is uncertain.
	 *
	 * @return array{rows: list<array<int, mixed>>, manual: int, uncertain: int, undecided: list<array<string, mixed>>} The new tally.
	 *
	 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-5.3
	 */
	private function tally(array $tally, array $data, float $certainFrom): array {
		if ((string)($data['detectionMethod'] ?? '') === self::METHOD_MANUAL) {
			$tally['manual']++;
		}

		$tally['rows'][] = $this->decisionOf(data: $data);
		if ($this->thresholds->isUncertain(relation: $data, certainFrom: $certainFrom) === false) {
			return $tally;
		}

		$tally['uncertain']++;
		if ((string)($data['decision'] ?? $data['decisionUuid'] ?? '') === '') {
			$tally['undecided'][] = $this->undecidedOf(data: $data);
		}

		return $tally;

	}//end tally()

	/**
	 * An uncertain finding without a decision, as the check refusal lists it.
	 *
	 * @param array<string, mixed> $data The relation's fields.
	 *
	 * @return array{relationId: int, entityId: int, confidence: float} The finding.
	 */
	private function undecidedOf(array $data): array {
		return [
			'relationId' => (int)($data['id'] ?? $data['relation_id'] ?? 0),
			'entityId' => (int)($data['entityId'] ?? $data['entity_id'] ?? 0),
			'confidence' => (float)($data['confidence'] ?? 0.0),
		];

	}//end undecidedOf()

	/**
	 * A relation as an array, or null when it has no array shape.
	 *
	 * @param mixed $relation An EntityRelation or an array.
	 *
	 * @return array<string, mixed>|null The relation's fields.
	 */
	private function rowOf(mixed $relation): ?array {
		if (is_object($relation) === true && method_exists($relation, 'jsonSerialize') === true) {
			$relation = $relation->jsonSerialize();
		}

		if (is_array($relation) === false) {
			return null;
		}

		return $relation;

	}//end rowOf()

	/**
	 * The parts of one relation a reviewer decides on, in a stable order.
	 *
	 * @param array<string, mixed> $data The relation's fields.
	 *
	 * @return array{0: int, 1: int, 2: bool, 3: list<string>} Relation id, entity id, skipped, sorted bases.
	 */
	private function decisionOf(array $data): array {
		$bases = [];
		foreach ((array)($data['bases'] ?? []) as $base) {
			if (is_scalar($base) === true) {
				$bases[] = (string)$base;
			}
		}

		sort($bases);

		return [
			(int)($data['id'] ?? $data['relation_id'] ?? 0),
			(int)($data['entityId'] ?? $data['entity_id'] ?? 0),
			(bool)($data['skipAnonymization'] ?? $data['skip_anonymization'] ?? false),
			$bases,
		];

	}//end decisionOf()
}//end class
