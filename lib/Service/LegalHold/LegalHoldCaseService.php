<?php

/**
 * Places, extends, retries and releases legal hold cases.
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

use OCA\Filinq\Exception\LegalHoldRefusedException;
use OCP\AppFramework\Utility\ITimeFactory;

/**
 * The case layer over OpenRegister's per-object legal holds.
 *
 * Coverage is derived, never stored twice: a record is covered by every
 * active case whose scope lists it. That is what makes release overlap-safe:
 * a record stays frozen while any other active case still lists it.
 *
 * Order matters for safety. A new case is saved active BEFORE its records are
 * frozen, and a released case is saved released BEFORE they are unfrozen, so a
 * crash between the two leaves records frozen (over-held), never unfrozen
 * under an active case. The retry picks up where it stopped.
 */
class LegalHoldCaseService {

	/**
	 * The matter types a case can have.
	 *
	 * @var array<int, string>
	 */
	public const HOLD_TYPES = LegalHoldCaseShape::HOLD_TYPES;

	/**
	 * Constructor.
	 *
	 * @param LegalHoldAuthority      $authority     Who may place and release.
	 * @param LegalHoldCaseRepository $cases         The hold register.
	 * @param LegalHoldFanOut         $fanOut        Freezes and unfreezes one record.
	 * @param LegalHoldNotifications  $notifications Tells owners and the custodian.
	 * @param LegalHoldCaseShape      $shape         The rules over a case array.
	 * @param ITimeFactory            $time          The clock.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly LegalHoldAuthority $authority,
		private readonly LegalHoldCaseRepository $cases,
		private readonly LegalHoldFanOut $fanOut,
		private readonly LegalHoldNotifications $notifications,
		private readonly LegalHoldCaseShape $shape,
		private readonly ITimeFactory $time,
	) {

	}//end __construct()

	/**
	 * The cases matching the filters.
	 *
	 * @param array<string, string> $filters status, holdType and/or custodian.
	 * @param string                $userId  The caller.
	 *
	 * @return array<int, array<string, mixed>> The cases.
	 *
	 * @throws LegalHoldRefusedException When the caller has no hold authority.
	 *
	 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-3.1
	 */
	public function list(array $filters, string $userId): array {
		$this->authority->assertAuthority(userId: $userId);
		$wanted = [];
		foreach (['status', 'holdType', 'custodian'] as $field) {
			if (isset($filters[$field]) === true && is_string($filters[$field]) === true && $filters[$field] !== '') {
				$wanted[$field] = $filters[$field];
			}
		}

		return $this->cases->search(filters: $wanted);

	}//end list()

	/**
	 * One case.
	 *
	 * @param string $uuid   The case.
	 * @param string $userId The caller.
	 *
	 * @return array<string, mixed> The case.
	 *
	 * @throws LegalHoldRefusedException When the caller has no hold authority or there is no such case.
	 *
	 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-3.1
	 */
	public function get(string $uuid, string $userId): array {
		$this->authority->assertAuthority(userId: $userId);

		return $this->existing(uuid: $uuid);

	}//end get()

	/**
	 * Open a case and freeze its records.
	 *
	 * @param array<string, mixed> $input  name, holdType, reason, caseReference, custodian, scopeDocuments, scopeDossiers.
	 * @param string               $userId The caller.
	 *
	 * @return array<string, mixed> The case, with its fan-out.
	 *
	 * @throws LegalHoldRefusedException When the caller has no hold authority or the input is incomplete.
	 *
	 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-2.1
	 */
	public function place(array $input, string $userId): array {
		$this->authority->assertAuthority(userId: $userId);
		$case = [
			'name' => $this->shape->required(input: $input, field: 'name', max: 255),
			'holdType' => $this->shape->holdType(input: $input),
			'reason' => $this->shape->required(input: $input, field: 'reason', max: 2000),
			'scopeDocuments' => $this->shape->refs(value: ($input['scopeDocuments'] ?? [])),
			'scopeDossiers' => $this->shape->refs(value: ($input['scopeDossiers'] ?? [])),
			'status' => 'active',
			'placedBy' => $userId,
			'placedAt' => $this->now(),
			'fanOut' => [],
			'notifiedOwners' => [],
		];
		foreach (['caseReference' => 255, 'custodian' => 64] as $field => $max) {
			$value = trim((string) ($input[$field] ?? ''));
			if ($value !== '') {
				$case[$field] = mb_substr($value, 0, $max);
			}
		}

		if ($case['scopeDocuments'] === [] && $case['scopeDossiers'] === []) {
			throw new LegalHoldRefusedException(reason: LegalHoldRefusedException::REASON_INVALID, message: 'A hold needs at least one document or dossier.');
		}

		// Saved active first: from here on, a release of any other case
		// sees this one and keeps these records frozen.
		$case = $this->cases->save(case: $case);

		return $this->freezeRefs(case: $case, refs: $this->shape->scope(case: $case), subject: LegalHoldNotifications::SUBJECT_PLACED);

	}//end place()

	/**
	 * Add references to an active case and freeze only those.
	 *
	 * @param string               $uuid   The case.
	 * @param array<string, mixed> $input  scopeDocuments and/or scopeDossiers to add.
	 * @param string               $userId The caller.
	 *
	 * @return array<string, mixed> The case.
	 *
	 * @throws LegalHoldRefusedException When refused.
	 *
	 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-2.2
	 */
	public function addScope(string $uuid, array $input, string $userId): array {
		$this->authority->assertAuthority(userId: $userId);
		$case = $this->active(uuid: $uuid);
		$added = [];
		foreach (['scopeDocuments' => 'document', 'scopeDossiers' => 'dossier'] as $field => $kind) {
			$current = (array) ($case[$field] ?? []);
			foreach ($this->shape->refs(value: ($input[$field] ?? [])) as $ref) {
				if (in_array($ref, $current, true) === false) {
					$current[] = $ref;
					$added[$ref] = $kind;
				}
			}

			$case[$field] = $current;
		}

		if ($added === []) {
			return $case;
		}

		$case = $this->cases->save(case: $case);

		return $this->freezeRefs(case: $case, refs: $added, subject: LegalHoldNotifications::SUBJECT_PLACED);

	}//end addScope()

	/**
	 * Try again for every record whose freeze or unfreeze failed.
	 *
	 * @param string $uuid   The case.
	 * @param string $userId The caller.
	 *
	 * @return array<string, mixed> The case.
	 *
	 * @throws LegalHoldRefusedException When refused.
	 *
	 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-2.1
	 */
	public function retry(string $uuid, string $userId): array {
		$this->authority->assertAuthority(userId: $userId);
		$case = $this->existing(uuid: $uuid);
		$pending = [];
		foreach ((array) ($case['fanOut'] ?? []) as $entry) {
			if (($entry['record'] ?? '') === 'failed' || ($entry['file'] ?? '') === 'failed') {
				$pending[(string) $entry['ref']] = (string) $entry['kind'];
			}
		}

		// A record in scope with no entry at all was never reached.
		foreach ($this->shape->scope(case: $case) as $ref => $kind) {
			if ($this->shape->entryFor(case: $case, ref: $ref) === null) {
				$pending[$ref] = $kind;
			}
		}

		if ($pending === []) {
			return $case;
		}

		if ($case['status'] === 'released') {
			return $this->unfreezeRefs(case: $case, refs: $pending, subject: '');
		}

		return $this->freezeRefs(case: $case, refs: $pending, subject: '');

	}//end retry()

	/**
	 * Release a case: a reason is required, and records another active case
	 * still lists stay frozen.
	 *
	 * @param string $uuid          The case.
	 * @param string $releaseReason Why.
	 * @param string $userId        The caller.
	 *
	 * @return array<string, mixed> The released case.
	 *
	 * @throws LegalHoldRefusedException When refused.
	 *
	 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-2.2
	 */
	public function release(string $uuid, string $releaseReason, string $userId): array {
		$this->authority->assertAuthority(userId: $userId);
		$case = $this->active(uuid: $uuid);
		$reason = trim($releaseReason);
		if ($reason === '') {
			throw new LegalHoldRefusedException(reason: LegalHoldRefusedException::REASON_INVALID, message: 'A release needs a reason.');
		}

		$case['status'] = 'released';
		$case['releasedBy'] = $userId;
		$case['releasedAt'] = $this->now();
		$case['releaseReason'] = mb_substr($reason, 0, 2000);
		// Saved released first: a crash from here leaves records frozen, the
		// safe side, and retry finishes the job.
		$case = $this->cases->save(case: $case);

		return $this->unfreezeRefs(case: $case, refs: $this->shape->scope(case: $case), subject: LegalHoldNotifications::SUBJECT_RELEASED);

	}//end release()

	/**
	 * Whether a record is frozen, and by which cases.
	 *
	 * Anyone who can open the record learns whether it is held; only someone
	 * with hold authority learns which matter holds it, because the existence of
	 * a lawsuit is not everybody's business.
	 *
	 * @param string $ref    The record uuid.
	 * @param string $userId The caller.
	 *
	 * @return array{held: bool, cases: array<int, array{uuid: string, name: string}>} The status.
	 *
	 * @throws LegalHoldRefusedException When the caller cannot read the record.
	 *
	 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-3.2
	 */
	public function statusFor(string $ref, string $userId): array {
		$held = $this->fanOut->heldAsSeenBy(ref: $ref);
		if ($held === null) {
			throw new LegalHoldRefusedException(reason: LegalHoldRefusedException::REASON_NOT_FOUND, message: 'No record ' . $ref . ' the caller can read.');
		}

		$cases = [];
		if ($this->authority->hasAuthority(userId: $userId) === true) {
			foreach ($this->shape->covering(ref: $ref, exceptUuid: '', activeCases: $this->cases->search(filters: ['status' => 'active'])) as $case) {
				$cases[] = ['uuid' => (string) $case['uuid'], 'name' => (string) ($case['name'] ?? '')];
			}
		}

		return ['held' => $held, 'cases' => $cases];

	}//end statusFor()

	/**
	 * Freeze records, record the outcome on the case, and notify.
	 *
	 * @param array<string, mixed>  $case   The saved case.
	 * @param array<string, string> $refs   Record uuid => kind.
	 * @param string                $subject The notification subject, '' for none.
	 *
	 * @return array<string, mixed> The case as saved.
	 */
	private function freezeRefs(array $case, array $refs, string $subject): array {
		$owners = [];
		foreach ($refs as $ref => $kind) {
			$result = $this->fanOut->freeze(ref: (string) $ref, kind: $kind, caseUuid: (string) $case['uuid']);
			$case = $this->shape->withEntry(case: $case, entry: $result['entry']);
			$owners[] = $result['owner'];
		}

		return $this->finish(case: $case, owners: $owners, subject: $subject);

	}//end freezeRefs()

	/**
	 * Unfreeze records of a released case, keeping those another active case lists.
	 *
	 * @param array<string, mixed>  $case   The released case.
	 * @param array<string, string> $refs   Record uuid => kind.
	 * @param string                $subject The notification subject, '' for none.
	 *
	 * @return array<string, mixed> The case as saved.
	 */
	private function unfreezeRefs(array $case, array $refs, string $subject): array {
		$active = $this->cases->search(filters: ['status' => 'active']);
		$owners = [];
		foreach ($refs as $ref => $kind) {
			$survivors = $this->shape->covering(ref: (string) $ref, exceptUuid: (string) $case['uuid'], activeCases: $active);
			$survivor = null;
			if ($survivors !== []) {
				$survivor = (string) $survivors[0]['uuid'];
			}

			$result = $this->fanOut->unfreeze(ref: (string) $ref, kind: $kind, survivorUuid: $survivor, releaseReason: (string) $case['releaseReason']);
			$case = $this->shape->withEntry(case: $case, entry: $result['entry']);
			$owners[] = $result['owner'];
		}

		return $this->finish(case: $case, owners: $owners, subject: $subject);

	}//end unfreezeRefs()

	/**
	 * Derive protection, notify, and save.
	 *
	 * @param array<string, mixed> $case    The case.
	 * @param array<int, string>   $owners  The owners of the records touched.
	 * @param string               $subject The notification subject, '' for none.
	 *
	 * @return array<string, mixed> The case as saved.
	 */
	private function finish(array $case, array $owners, string $subject): array {
		$case['fileLockBackstop'] = 'unavailable';
		if ($this->fanOut->fileLocksAvailable() === true) {
			$case['fileLockBackstop'] = 'available';
		}

		$case['protection'] = $this->shape->protection(case: $case);
		if ($subject !== '') {
			$notified = $this->notifications->notify(
				userIds: array_merge($owners, [(string) ($case['custodian'] ?? '')]),
				case: $case,
				subject: $subject
			);
			$case['notifiedOwners'] = array_values(array_unique(array_merge((array) ($case['notifiedOwners'] ?? []), $notified)));
		}

		return $this->cases->save(case: $case);

	}//end finish()

	/**
	 * A case that exists.
	 *
	 * @param string $uuid The case.
	 *
	 * @return array<string, mixed> The case.
	 *
	 * @throws LegalHoldRefusedException When there is none.
	 */
	private function existing(string $uuid): array {
		$case = $this->cases->find(uuid: $uuid);
		if ($case === null) {
			throw new LegalHoldRefusedException(reason: LegalHoldRefusedException::REASON_NOT_FOUND, message: 'No hold case ' . $uuid . '.');
		}

		return $case;

	}//end existing()

	/**
	 * A case that exists and is still active.
	 *
	 * @param string $uuid The case.
	 *
	 * @return array<string, mixed> The case.
	 *
	 * @throws LegalHoldRefusedException When there is none, or it is released.
	 */
	private function active(string $uuid): array {
		$case = $this->existing(uuid: $uuid);
		if (($case['status'] ?? '') !== 'active') {
			throw new LegalHoldRefusedException(
				reason: LegalHoldRefusedException::REASON_RELEASED,
				message: 'Hold case ' . $uuid . ' is released; a released case is final.'
			);
		}

		return $case;

	}//end active()

	/**
	 * Now, as stored.
	 *
	 * @return string An ISO 8601 date-time.
	 */
	private function now(): string {
		return $this->time->getDateTime()->format(format: DATE_ATOM);

	}//end now()
}//end class
