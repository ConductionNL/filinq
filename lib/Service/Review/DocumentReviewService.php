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
	 * The detection method OpenRegister records on a manual entity.
	 *
	 * @var string
	 */
	private const METHOD_MANUAL = 'manual';

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
	 *
	 * @return void
	 */
	public function __construct(
		private readonly DocumentReviewRepository $repository,
		private readonly OpenRegisterServiceLocator $locator,
		private readonly IAppConfig $appConfig,
		private readonly LoggerInterface $logger,
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
	 *               manualEntityCount: int, review: array<string, mixed>|null}
	 *         `checked` is true only while a mark exists and its fingerprint still matches;
	 *         `stale` is true when a mark exists but the decisions changed after it.
	 *
	 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-2.3
	 */
	public function status(int $fileId): array {
		$decisions = $this->decisions(fileId: $fileId);
		$review = $this->repository->findFor(fileId: $fileId);
		$holds = ($review !== null
			&& $decisions !== null
			&& hash_equals((string)($review['decisionFingerprint'] ?? ''), $decisions['fingerprint']) === true);

		return [
			'fileId' => $fileId,
			'checked' => $holds,
			'stale' => ($review !== null && $holds === false),
			'extracted' => ($decisions !== null && $decisions['count'] > 0),
			'entityCount' => ($decisions['count'] ?? 0),
			'manualEntityCount' => ($decisions['manual'] ?? 0),
			'review' => $review,
		];

	}//end status()

	/**
	 * Mark one file checked by a user, replacing any earlier mark.
	 *
	 * The mark is saved with OpenRegister RBAC on, so only the roles the
	 * `documentReview` schema lets create one can check a document.
	 *
	 * @param int    $fileId The Nextcloud file id.
	 * @param string $userId The reviewer.
	 * @param string $note   The reviewer's note, may be empty.
	 *
	 * @return array<string, mixed> The status after the check, as {@see status}.
	 *
	 * @throws DocumentReviewRefusedException When extraction has not completed for the file.
	 *
	 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-2.3
	 */
	public function check(int $fileId, string $userId, string $note = ''): array {
		$decisions = $this->decisions(fileId: $fileId);
		if ($decisions === null || $decisions['count'] === 0) {
			throw new DocumentReviewRefusedException(
				reason: 'not_extracted',
				message: 'This document has no extracted findings yet; run detection before marking it reviewed.'
			);
		}

		$mark = [
			'fileId' => $fileId,
			'checkedOn' => (new DateTimeImmutable())->format(DateTimeInterface::ATOM),
			'checkedBy' => $userId,
			'entityCountAtCheck' => $decisions['count'],
			'manualEntityCount' => $decisions['manual'],
			'decisionFingerprint' => $decisions['fingerprint'],
		];
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
	 * The file's entity decisions: their count, manual count and fingerprint.
	 *
	 * @param int $fileId The Nextcloud file id.
	 *
	 * @return array{count: int, manual: int, fingerprint: string}|null Null when the relations cannot be read.
	 *
	 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-2.3
	 */
	private function decisions(int $fileId): ?array {
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
		foreach ((array)$relations as $relation) {
			$data = $this->rowOf(relation: $relation);
			if ($data === null) {
				continue;
			}

			if ((string)($data['detectionMethod'] ?? '') === self::METHOD_MANUAL) {
				$manual++;
			}

			$rows[] = $this->decisionOf(data: $data);
		}

		usort($rows, static fn (array $a, array $b): int => $a[0] <=> $b[0]);

		return [
			'count' => count($rows),
			'manual' => $manual,
			'fingerprint' => hash('sha256', (string)json_encode($rows)),
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
