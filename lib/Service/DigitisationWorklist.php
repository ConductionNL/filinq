<?php

/**
 * Digitisation worklist
 *
 * What the project page and the checking queue read and do: a project and
 * its originals as the person asking may see them, the originals counted per
 * box and state, and a checker's verdict on a scanned original. Every read and
 * write goes through OpenRegister's object API with the caller's own rights.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/intake-paper-archive-digitisation/specs/paper-archive-digitisation/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;
use OCA\Filinq\Kernel\Contracts\DigitisationWorklistInterface;
use OCA\OpenRegister\Contract\ObjectServiceInterface;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectFindOptions;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectFindTarget;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectSaveOptions;
use Throwable;

/**
 * Per-box progress and the check of a scan (REQ-PAD-001, REQ-PAD-004).
 *
 * @spec openspec/changes/intake-paper-archive-digitisation/specs/paper-archive-digitisation/spec.md#requirement-a-sampled-share-is-checked-by-a-person-req-pad-004
 */
class DigitisationWorklist implements DigitisationWorklistInterface {

	public const REGISTER = 'filinq';

	public const PROJECT = 'digitisationProject';

	public const ORIGINAL = 'paperOriginal';

	public const PASSED = 'passed';

	public const REJECTED = 'rejected';

	/**
	 * The most originals one project read takes.
	 */
	private const LIMIT = 10000;

	/**
	 * Constructor.
	 *
	 * @param ObjectServiceInterface $objectService OpenRegister's object API.
	 */
	public function __construct(
		private readonly ObjectServiceInterface $objectService,
	) {
	}//end __construct()

	/**
	 * A project as the caller may read it, with its `id`, or null.
	 *
	 * @param string $projectId The digitisationProject.
	 *
	 * @return array<string, mixed>|null
	 *
	 * @spec openspec/changes/intake-paper-archive-digitisation/specs/paper-archive-digitisation/spec.md#requirement-an-archivist-runs-a-digitisation-project-req-pad-001
	 */
	public function project(string $projectId): ?array {
		return $this->find(id: $projectId, schema: self::PROJECT);

	}//end project()

	/**
	 * The originals of a project, of one box when a box is given.
	 *
	 * @param string      $projectId The digitisationProject.
	 * @param string|null $box       The box, or null for every box.
	 *
	 * @return array<int, array<string, mixed>> Each with its `id`.
	 *
	 * @spec openspec/changes/intake-paper-archive-digitisation/specs/paper-archive-digitisation/spec.md#requirement-a-separator-sheet-names-the-original-it-precedes-req-pad-002
	 */
	public function originals(string $projectId, ?string $box = null): array {
		$filters = ['project' => $projectId, '_limit' => self::LIMIT];
		$results = (new SlugSearch(objects: $this->objectService))->search(register: self::REGISTER, schema: self::ORIGINAL, filters: $filters);
		if (is_array($results) === false) {
			return [];
		}

		$originals = [];
		foreach ($results as $result) {
			$original = self::payload(result: $result);
			if ($original === null || (string)($original['project'] ?? '') !== $projectId) {
				continue;
			}

			if ($box !== null && (string)($original['box'] ?? '') !== $box) {
				continue;
			}

			$originals[] = $original;
		}

		usort($originals, static fn (array $a, array $b): int => strnatcasecmp((string)($a['shelfMark'] ?? ''), (string)($b['shelfMark'] ?? '')));

		return $originals;

	}//end originals()

	/**
	 * The originals of a project counted per box and state.
	 *
	 * Each original counts once: a rejected scan counts as rejected until the
	 * original is scanned again, also after it went back to `registered`.
	 * Boxes the project names but holds no original yet are listed with zeros,
	 * in the project's order, before boxes only the originals name.
	 *
	 * @param array<string, mixed> $project The project, with its `id`.
	 *
	 * @return array<int, array<string, mixed>> Rows of id (the box), project, box, registered, scanned, checked, rejected and total.
	 *
	 * @spec openspec/changes/intake-paper-archive-digitisation/specs/paper-archive-digitisation/spec.md#requirement-an-archivist-runs-a-digitisation-project-req-pad-001
	 */
	public function boxCounts(array $project): array {
		$projectId = (string)($project['id'] ?? '');
		$rows = [];
		foreach ((array)($project['boxes'] ?? []) as $box) {
			if (is_scalar($box) === true && trim((string)$box) !== '') {
				$rows[trim((string)$box)] = self::emptyRow(projectId: $projectId, box: trim((string)$box));
			}
		}

		foreach ($this->originals(projectId: $projectId) as $original) {
			$box = trim((string)($original['box'] ?? ''));
			$rows[$box] ??= self::emptyRow(projectId: $projectId, box: $box);
			$rows[$box][self::bucket(original: $original)]++;
			$rows[$box]['total']++;
		}

		return array_values($rows);

	}//end boxCounts()

	/**
	 * Record a checker's verdict on a scanned original.
	 *
	 * Passed: the original is `checked`. Rejected: the note is required, the
	 * original goes `rejected` and then back to `registered` for a rescan, both
	 * through the declared lifecycle transitions.
	 *
	 * @param string $originalId The paperOriginal.
	 * @param string $result     passed or rejected.
	 * @param string $note       The checker's note.
	 * @param string $checkedBy  The checker's user id.
	 *
	 * @return array<string, mixed>|null The original as saved, or null when the caller cannot read it.
	 *
	 * @throws InvalidArgumentException When the verdict is unknown, a rejection has no note or the original is not waiting for a check.
	 *
	 * @spec openspec/changes/intake-paper-archive-digitisation/specs/paper-archive-digitisation/spec.md#requirement-a-sampled-share-is-checked-by-a-person-req-pad-004
	 */
	public function check(string $originalId, string $result, string $note, string $checkedBy): ?array {
		if (in_array($result, [self::PASSED, self::REJECTED], true) === false) {
			throw new InvalidArgumentException('A check is passed or rejected.');
		}

		$note = trim($note);
		if ($result === self::REJECTED && $note === '') {
			throw new InvalidArgumentException('A rejected scan needs a note saying what is wrong.');
		}

		$original = $this->find(id: $originalId, schema: self::ORIGINAL);
		if ($original === null) {
			return null;
		}

		if (($original['status'] ?? '') !== DigitisationProjectService::SCANNED) {
			throw new InvalidArgumentException('Only a scanned original waits for a check.');
		}

		$uuid = (string)$original['id'];
		unset($original['@self']);
		$original['check'] = [
			'result' => $result,
			'checkedBy' => $checkedBy,
			'checkedAt' => (new DateTimeImmutable())->format(DateTimeInterface::ATOM),
			'note' => $note,
		];

		if ($result === self::PASSED) {
			$original['status'] = DigitisationProjectService::CHECKED;
			$this->save(original: $original, uuid: $uuid);
			return $original;
		}

		$original['status'] = self::REJECTED;
		$this->save(original: $original, uuid: $uuid);
		$original['status'] = 'registered';
		$this->save(original: $original, uuid: $uuid);

		return $original;

	}//end check()

	/**
	 * The state an original counts under.
	 *
	 * @param array<string, mixed> $original The original.
	 *
	 * @return string registered, scanned, checked or rejected.
	 */
	private static function bucket(array $original): string {
		$status = (string)($original['status'] ?? 'registered');
		if ($status === 'registered' && (($original['check']['result'] ?? null) === self::REJECTED)) {
			return self::REJECTED;
		}

		if (in_array($status, ['registered', 'scanned', 'checked', 'rejected'], true) === false) {
			return 'registered';
		}

		return $status;

	}//end bucket()

	/**
	 * A box row with nothing counted yet.
	 *
	 * @param string $projectId The project.
	 * @param string $box       The box.
	 *
	 * @return array<string, mixed>
	 */
	private static function emptyRow(string $projectId, string $box): array {
		return ['id' => $box, 'project' => $projectId, 'box' => $box, 'registered' => 0, 'scanned' => 0, 'checked' => 0, 'rejected' => 0, 'total' => 0];

	}//end emptyRow()

	/**
	 * Save an original as the caller.
	 *
	 * @param array<string, mixed> $original The original.
	 * @param string               $uuid     Its uuid.
	 *
	 * @return void
	 */
	private function save(array $original, string $uuid): void {
		$saved = $original;
		unset($saved['id']);
		$this->objectService->saveObject(
			object: $saved,
			options: new ObjectSaveOptions(
				target: new ObjectFindTarget(register: self::REGISTER, schema: self::ORIGINAL),
				uuid: $uuid
			)
		);

	}//end save()

	/**
	 * One object as the caller may read it, with its `id`, or null.
	 *
	 * @param string $id     The uuid.
	 * @param string $schema The schema slug.
	 *
	 * @return array<string, mixed>|null
	 */
	private function find(string $id, string $schema): ?array {
		$id = trim($id);
		if ($id === '') {
			return null;
		}

		try {
			$entity = $this->objectService->find(id: $id, options: new ObjectFindOptions(register: self::REGISTER, schema: $schema));
		} catch (Throwable) {
			return null;
		}

		if ($entity === null) {
			return null;
		}

		$payload = $entity->getObject();
		$payload['id'] = (string)($entity->getUuid() ?? $id);

		return $payload;

	}//end find()

	/**
	 * The payload of one search result with its `id`, or null.
	 *
	 * @param mixed $result A row as searchObjectsBySlug answers it.
	 *
	 * @return array<string, mixed>|null
	 */
	private static function payload(mixed $result): ?array {
		if (is_object($result) === true && method_exists($result, 'getObject') === true) {
			$payload = (array)$result->getObject();
			$uuid = ($payload['id'] ?? '');
			if (method_exists($result, 'getUuid') === true) {
				$uuid = $result->getUuid();
			}

			$payload['id'] = (string)$uuid;
			return $payload;
		}

		if (is_object($result) === true && method_exists($result, 'jsonSerialize') === true) {
			$result = $result->jsonSerialize();
		}

		if (is_array($result) === false) {
			return null;
		}

		$id = (string)($result['id'] ?? ($result['@self']['id'] ?? ($result['uuid'] ?? '')));
		if ($id === '') {
			return null;
		}

		$result['id'] = $id;
		return $result;

	}//end payload()
}//end class
