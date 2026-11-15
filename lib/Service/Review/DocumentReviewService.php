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
		$decisions = $this->decisions(fileId: $fileId, certainFrom: $thresholds['certainFrom']);
		$review = $this->repository->findFor(fileId: $fileId);
		$rendition = $this->renditions->findFor(sourceFileId: $fileId);
		$holds = ($review !== null
			&& $decisions !== null
			&& hash_equals((string)($review['decisionFingerprint'] ?? ''), $decisions['fingerprint']) === true
			&& $this->sameRendition(review: $review, rendition: $rendition) === true);

		return [
			'fileId' => $fileId,
			'checked' => $holds,
			'stale' => ($review !== null && $holds === false),
			'extracted' => ($decisions !== null && $decisions['count'] > 0),
			'entityCount' => ($decisions['count'] ?? 0),
			'manualEntityCount' => ($decisions['manual'] ?? 0),
			'review' => $review,
			'rendition' => $rendition,
			'thresholds' => $thresholds,
			'uncertainCount' => ($decisions['uncertain'] ?? 0),
			'undecidedUncertain' => ($decisions['undecidedUncertain'] ?? []),
		];

	}//end status()

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

		// A re-check replaces the earlier mark by removing it and creating a new
		// one: the schema lets a woo officer create a mark but not update one.
		$this->uncheck(fileId: $fileId);
		$this->repository->save(mark: $mark);

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
	private function assertCheckable(?array $rendition, ?array $decisions, bool $byEye, array $decided): void {
		$unreadable = (($rendition['textLayer'] ?? '') === self::LAYER_NONE);
		if ($unreadable === true && $byEye === false) {
			throw new DocumentReviewRefusedException(
				reason: 'no_text_layer',
				message: 'Detection could not read the pages of this document. Confirm that you read every page by eye before marking it reviewed.'
			);
		}

		if ($decisions === null || ($decisions['count'] === 0 && $unreadable === false)) {
			throw new DocumentReviewRefusedException(
				reason: 'not_extracted',
				message: 'This document has no extracted findings yet; run detection before marking it reviewed.'
			);
		}

		// An uncertain finding counts as decided when OpenRegister holds a decision
		// for it, or when the reviewer included or skipped it in the workbench.
		$decidedIds = array_map('intval', $decided);
		$undecided = array_values(
			array_filter(
				$decisions['undecidedUncertain'],
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
	 * Remove a file's mark; a file without one is left as it is.
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

		$this->repository->delete(uuid: (string)$existing['uuid']);
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

		$rows = [];
		$manual = 0;
		$uncertain = 0;
		$undecided = [];
		foreach ((array)$relations as $relation) {
			$data = $this->rowOf(relation: $relation);
			if ($data === null) {
				continue;
			}

			if ((string)($data['detectionMethod'] ?? '') === self::METHOD_MANUAL) {
				$manual++;
			}

			$rows[] = $this->decisionOf(data: $data);
			if ($this->thresholds->isUncertain(relation: $data, certainFrom: $certainFrom) === false) {
				continue;
			}

			$uncertain++;
			if ((string)($data['decision'] ?? $data['decisionUuid'] ?? '') === '') {
				$undecided[] = [
					'relationId' => (int)($data['id'] ?? $data['relation_id'] ?? 0),
					'entityId' => (int)($data['entityId'] ?? $data['entity_id'] ?? 0),
					'confidence' => (float)($data['confidence'] ?? 0.0),
				];
			}
		}//end foreach

		usort($rows, static fn (array $a, array $b): int => $a[0] <=> $b[0]);
		usort($undecided, static fn (array $a, array $b): int => $a['relationId'] <=> $b['relationId']);

		return [
			'count' => count($rows),
			'manual' => $manual,
			'fingerprint' => hash('sha256', (string)json_encode($rows)),
			'uncertain' => $uncertain,
			'undecidedUncertain' => $undecided,
		];

	}//end decisions()

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
