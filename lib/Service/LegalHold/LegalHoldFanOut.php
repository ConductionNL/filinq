<?php

/**
 * Freezes and unfreezes the records of one case, one record at a time.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category  Service
 * @package   OCA\Filinq\Service\LegalHold
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-2.1
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\LegalHold;

use OCP\AppFramework\Utility\ITimeFactory;
use Throwable;

/**
 * One fan-out entry per record: the record hold first (primary), then the
 * file lock (backstop). A failure is written on the entry, never swallowed,
 * so the case can say it is not fully protective and offer a retry.
 */
class LegalHoldFanOut {

	/**
	 * Record outcomes that mean the record is frozen.
	 *
	 * @var array<int, string>
	 */
	public const HELD = ['held', 'held_by_other'];

	/**
	 * Constructor.
	 *
	 * @param LegalHoldRecordFreeze $records The OpenRegister record hold.
	 * @param LegalHoldFileFreeze   $files   The file lock backstop.
	 * @param ITimeFactory          $time    The clock.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly LegalHoldRecordFreeze $records,
		private readonly LegalHoldFileFreeze $files,
		private readonly ITimeFactory $time,
	) {

	}//end __construct()

	/**
	 * Whether the file lock backstop can be used at all.
	 *
	 * @return bool False without files_lock.
	 *
	 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-2.6
	 */
	public function fileLocksAvailable(): bool {
		return $this->files->available();

	}//end fileLocksAvailable()

	/**
	 * Whether a record is held, as the caller may see it.
	 *
	 * @param string $ref The record uuid.
	 *
	 * @return bool|null Null when the caller cannot read the record.
	 *
	 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-3.2
	 */
	public function heldAsSeenBy(string $ref): ?bool {
		return $this->records->heldAsSeenBy(ref: $ref);

	}//end heldAsSeenBy()

	/**
	 * Freeze one record for a case.
	 *
	 * @param string $ref      The record uuid.
	 * @param string $kind     document or dossier.
	 * @param string $caseUuid The case.
	 *
	 * @return array{entry: array<string, mixed>, owner: string} The fan-out entry and the record's owner.
	 *
	 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-2.1
	 */
	public function freeze(string $ref, string $kind, string $caseUuid): array {
		$entry = ['ref' => $ref, 'kind' => $kind, 'record' => 'failed', 'file' => 'no_files', 'at' => $this->now()];
		try {
			$entity = $this->records->load(ref: $ref);
		} catch (Throwable $e) {
			return ['entry' => $entry + ['recordError' => $this->short(message: $e->getMessage())], 'owner' => ''];
		}

		if ($entity === null) {
			return ['entry' => $entry + ['recordError' => 'The record could not be found.'], 'owner' => ''];
		}

		try {
			$entry['record'] = $this->records->place(entity: $entity, caseUuid: $caseUuid);
		} catch (Throwable $e) {
			$entry['recordError'] = $this->short(message: $e->getMessage());
		}

		$entry = array_merge($entry, $this->files->lock(entity: $entity));

		return ['entry' => $entry, 'owner' => $this->owner(entity: $entity)];

	}//end freeze()

	/**
	 * Unfreeze one record for a released case.
	 *
	 * @param string      $ref           The record uuid.
	 * @param string      $kind          document or dossier.
	 * @param string|null $survivorUuid  An other active case covering it, or null.
	 * @param string      $releaseReason Why the case was released.
	 *
	 * @return array{entry: array<string, mixed>, owner: string} The fan-out entry and the record's owner.
	 *
	 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-2.2
	 */
	public function unfreeze(string $ref, string $kind, ?string $survivorUuid, string $releaseReason): array {
		$entry = ['ref' => $ref, 'kind' => $kind, 'record' => 'failed', 'file' => 'no_files', 'at' => $this->now()];
		try {
			$entity = $this->records->load(ref: $ref);
		} catch (Throwable $e) {
			return ['entry' => $entry + ['recordError' => $this->short(message: $e->getMessage())], 'owner' => ''];
		}

		if ($entity === null) {
			// Nothing left to hold: a record that is gone cannot stay frozen.
			$entry['record'] = 'released';
			return ['entry' => $entry, 'owner' => ''];
		}

		try {
			$entry['record'] = $this->records->release(entity: $entity, survivorUuid: $survivorUuid, releaseReason: $releaseReason);
		} catch (Throwable $e) {
			$entry['recordError'] = $this->short(message: $e->getMessage());
		}

		$entry = array_merge($entry, $this->files->unlock(entity: $entity, survivorUuid: $survivorUuid));

		return ['entry' => $entry, 'owner' => $this->owner(entity: $entity)];

	}//end unfreeze()

	/**
	 * The record's owner, '' when it has none.
	 *
	 * @param object $entity The record.
	 *
	 * @return string The owner's user id.
	 */
	private function owner(object $entity): string {
		if (method_exists($entity, 'getOwner') === false) {
			return '';
		}

		return (string) ($entity->getOwner() ?? '');

	}//end owner()

	/**
	 * Now, as stored.
	 *
	 * @return string An ISO 8601 date-time.
	 */
	private function now(): string {
		return $this->time->getDateTime()->format(format: DATE_ATOM);

	}//end now()

	/**
	 * A message short enough for the schema.
	 *
	 * @param string $message The message.
	 *
	 * @return string At most 500 characters.
	 */
	private function short(string $message): string {
		return mb_substr($message, 0, 500);

	}//end short()
}//end class
