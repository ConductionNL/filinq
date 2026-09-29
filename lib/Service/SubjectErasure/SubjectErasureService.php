<?php

/**
 * A subject erasure request: placed, previewed, narrowed by exclusions.
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
 * @spec openspec/changes/archive/2026-09-29-erase-a-person-while-the-records-stay/tasks.md#task-2.1
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\SubjectErasure;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use OCA\Filinq\Exception\SubjectErasureRefusedException;
use OCP\AppFramework\Utility\ITimeFactory;

/**
 * Every public method checks SubjectErasureAuthority first and records a
 * denial; nothing is read before that check passes. Nothing here writes a
 * file: the preview is computed, stored as counts on the request, and shown.
 * SubjectErasureRun does the writing.
 */
class SubjectErasureService {

	/**
	 * States from which a preview may be (re)built.
	 *
	 * @var string[]
	 */
	public const PREVIEWABLE = ['received', 'previewed', 'partially_completed'];

	/**
	 * Constructor.
	 *
	 * @param SubjectErasureAuthority   $authority   Who may act.
	 * @param SubjectErasureStore       $store       The requests.
	 * @param SubjectErasureAudit       $audit       The audit trail.
	 * @param SubjectErasureLocator     $locator     Where the person appears.
	 * @param SubjectErasureObligations $obligations What stands over each document.
	 * @param SubjectErasurePreview     $preview     The capped preview.
	 * @param ITimeFactory              $time        The clock.
	 */
	public function __construct(
		private readonly SubjectErasureAuthority $authority,
		private readonly SubjectErasureStore $store,
		private readonly SubjectErasureAudit $audit,
		private readonly SubjectErasureLocator $locator,
		private readonly SubjectErasureObligations $obligations,
		private readonly SubjectErasurePreview $preview,
		private readonly ITimeFactory $time,
	) {

	}//end __construct()

	/**
	 * Refuse unless the user may act, and record the denial.
	 *
	 * @param string $userId      The caller.
	 * @param string $requestUuid The request the attempt was about, '' for none.
	 *
	 * @return void
	 *
	 * @throws SubjectErasureRefusedException When the caller may not act.
	 *
	 * @spec openspec/changes/archive/2026-09-29-erase-a-person-while-the-records-stay/tasks.md#task-4.1
	 */
	public function assertMayErase(string $userId, string $requestUuid = ''): void {
		try {
			$this->authority->assertMayErase(userId: $userId);
		} catch (SubjectErasureRefusedException $refusal) {
			$this->audit->record(
				requestUuid: $requestUuid,
				action: SubjectErasureAudit::ACTION_DENIED,
				userId: $userId,
				details: ['reason' => $refusal->getReason()]
			);
			throw $refusal;
		}

	}//end assertMayErase()

	/**
	 * Every request, newest first.
	 *
	 * @param string $userId The caller.
	 *
	 * @return array<int, array<string, mixed>> The requests.
	 *
	 * @throws SubjectErasureRefusedException When the caller may not act.
	 *
	 * @spec openspec/changes/archive/2026-09-29-erase-a-person-while-the-records-stay/tasks.md#task-2.1
	 */
	public function list(string $userId): array {
		$this->assertMayErase(userId: $userId);
		$requests = $this->store->search(schema: SubjectErasureStore::REQUEST);
		usort($requests, static fn (array $a, array $b): int => strcmp((string) ($b['requestedAt'] ?? ''), (string) ($a['requestedAt'] ?? '')));

		return $requests;

	}//end list()

	/**
	 * One request.
	 *
	 * @param string $uuid   The request uuid.
	 * @param string $userId The caller.
	 *
	 * @return array<string, mixed> The request.
	 *
	 * @throws SubjectErasureRefusedException When the caller may not act, or there is no such request.
	 *
	 * @spec openspec/changes/archive/2026-09-29-erase-a-person-while-the-records-stay/tasks.md#task-2.1
	 */
	public function get(string $uuid, string $userId): array {
		$this->assertMayErase(userId: $userId, requestUuid: $uuid);

		return $this->load(uuid: $uuid);

	}//end get()

	/**
	 * Place a request. The subject, the ground and the requester are recorded
	 * before anything is looked up (REQ-EPR-01).
	 *
	 * @param array<string, mixed> $input  subject, identifiers, ground, requester, dueAt.
	 * @param string               $userId The caller.
	 *
	 * @return array<string, mixed> The stored request.
	 *
	 * @throws SubjectErasureRefusedException When the caller may not act, or the input is incomplete.
	 *
	 * @spec openspec/changes/archive/2026-09-29-erase-a-person-while-the-records-stay/tasks.md#task-1.1
	 */
	public function create(array $input, string $userId): array {
		$this->assertMayErase(userId: $userId);

		$subject = trim((string) ($input['subject'] ?? ''));
		$ground = trim((string) ($input['ground'] ?? ''));
		$identifiers = $this->locator->clean(identifiers: array_merge([$subject], (array) ($input['identifiers'] ?? [])));
		if ($subject === '' || $ground === '' || mb_strlen($subject) > 255 || mb_strlen($ground) > 255) {
			throw new SubjectErasureRefusedException(
				reason: SubjectErasureRefusedException::REASON_INVALID,
				message: 'A request needs a subject and a legal ground, each at most 255 characters.'
			);
		}

		$now = $this->time->getDateTime();
		$request = $this->store->save(
			schema: SubjectErasureStore::REQUEST,
			row: [
				'subject' => $subject,
				'identifiers' => $identifiers,
				'ground' => $ground,
				'requester' => mb_substr(trim((string) ($input['requester'] ?? $userId)), 0, 255),
				'requestedAt' => $now->format(format: DateTimeInterface::ATOM),
				'dueAt' => $this->dueAt(input: (string) ($input['dueAt'] ?? ''), from: new DateTimeImmutable($now->format(format: DateTimeInterface::ATOM))),
				'status' => 'received',
				'progress' => ['documentsTotal' => 0, 'documentsDone' => 0, 'lastDocument' => '', 'occurrencesErased' => 0],
				'excluded' => [],
				'results' => [],
			]
		);

		$this->audit->record(
			requestUuid: (string) $request['uuid'],
			action: SubjectErasureAudit::ACTION_REQUESTED,
			userId: $userId,
			details: ['identifierCount' => count($identifiers), 'ground' => $ground]
		);

		return $request;

	}//end create()

	/**
	 * Build the preview. Nothing is written but the counts on the request.
	 *
	 * @param string $uuid   The request uuid.
	 * @param string $userId The caller.
	 *
	 * @return array<string, mixed> The preview: documents (capped), totals, cap,
	 *                              unprocessable, and each document's name and values.
	 *
	 * @throws SubjectErasureRefusedException When refused, or the catalogue cannot be read.
	 *
	 * @spec openspec/changes/archive/2026-09-29-erase-a-person-while-the-records-stay/tasks.md#task-2.1
	 */
	public function preview(string $uuid, string $userId): array {
		$this->assertMayErase(userId: $userId, requestUuid: $uuid);
		$request = $this->load(uuid: $uuid);
		$this->assertState(request: $request, allowed: self::PREVIEWABLE);

		$documents = $this->obligations->assess(located: $this->locator->locate(identifiers: (array) ($request['identifiers'] ?? [])));
		$preview = $this->withDetails(preview: $this->preview->build(documents: $documents), documents: $documents);
		$preview['excluded'] = (array) ($request['excluded'] ?? []);

		$this->audit->record(
			requestUuid: $uuid,
			action: SubjectErasureAudit::ACTION_PREVIEWED,
			userId: $userId,
			details: [
				'documentsTotal' => $preview['documentsTotal'],
				'occurrencesTotal' => $preview['occurrencesTotal'],
				'refusedDocuments' => $preview['refusedDocuments'],
				'unprocessableCount' => $preview['unprocessableCount'],
			]
		);

		if ((string) $request['status'] === 'received') {
			$request['status'] = 'previewed';
		}

		$request['progress'] = array_merge((array) ($request['progress'] ?? []), ['documentsTotal' => $preview['documentsTotal']]);
		$this->store->save(schema: SubjectErasureStore::REQUEST, row: $request);

		return $preview;

	}//end preview()

	/**
	 * Record exclusions. Each needs a reason; one without is refused and nothing is stored.
	 *
	 * @param string                           $uuid       The request uuid.
	 * @param array<int, array<string, mixed>> $exclusions {occurrence: "<fileId>" or "<fileId>:<value>", reason}.
	 * @param string                           $userId     The caller.
	 *
	 * @return array<string, mixed> The request with its exclusions.
	 *
	 * @throws SubjectErasureRefusedException When refused, not previewed, or an exclusion is incomplete.
	 *
	 * @spec openspec/changes/archive/2026-09-29-erase-a-person-while-the-records-stay/tasks.md#task-2.2
	 */
	public function exclude(string $uuid, array $exclusions, string $userId): array {
		$this->assertMayErase(userId: $userId, requestUuid: $uuid);
		$request = $this->load(uuid: $uuid);
		$this->assertState(request: $request, allowed: ['previewed', 'partially_completed']);

		$clean = [];
		foreach ($exclusions as $exclusion) {
			$occurrence = trim((string) ($exclusion['occurrence'] ?? ''));
			if (preg_match('/^[1-9][0-9]*(:.+)?$/', $occurrence) !== 1) {
				throw new SubjectErasureRefusedException(
					reason: SubjectErasureRefusedException::REASON_INVALID,
					message: 'An exclusion names a document id, or a document id and an identifier.'
				);
			}

			$clean[] = ['occurrence' => mb_substr($occurrence, 0, 255), 'reason' => trim((string) ($exclusion['reason'] ?? ''))];
		}

		$missing = $this->preview->refuseExclusions(exclusions: $clean);
		if ($missing !== []) {
			throw new SubjectErasureRefusedException(reason: SubjectErasureRefusedException::REASON_INVALID, message: implode(' ', $missing));
		}

		$this->audit->record(requestUuid: $uuid, action: SubjectErasureAudit::ACTION_EXCLUDED, userId: $userId, details: ['exclusions' => count($clean)]);
		$request['excluded'] = $clean;

		return $this->store->save(schema: SubjectErasureStore::REQUEST, row: $request);

	}//end exclude()

	/**
	 * A request, or not_found.
	 *
	 * @param string $uuid The uuid.
	 *
	 * @return array<string, mixed> The request.
	 *
	 * @throws SubjectErasureRefusedException When there is none.
	 *
	 * @spec openspec/changes/archive/2026-09-29-erase-a-person-while-the-records-stay/tasks.md#task-2.1
	 */
	public function load(string $uuid): array {
		$request = $this->store->find(schema: SubjectErasureStore::REQUEST, uuid: $uuid);
		if ($request === null) {
			throw new SubjectErasureRefusedException(reason: SubjectErasureRefusedException::REASON_NOT_FOUND, message: 'No erasure request ' . $uuid . '.');
		}

		return $request;

	}//end load()

	/**
	 * Refuse a step the request's state does not allow.
	 *
	 * @param array<string, mixed> $request The request.
	 * @param array<int, string>   $allowed The states the step accepts.
	 *
	 * @return void
	 *
	 * @throws SubjectErasureRefusedException When the state does not fit.
	 *
	 * @spec openspec/changes/archive/2026-09-29-erase-a-person-while-the-records-stay/tasks.md#task-3.1
	 */
	public function assertState(array $request, array $allowed): void {
		if (in_array((string) ($request['status'] ?? ''), $allowed, true) === false) {
			throw new SubjectErasureRefusedException(
				reason: SubjectErasureRefusedException::REASON_WRONG_STATE,
				message: 'This step is not possible while the request is ' . (string) ($request['status'] ?? '') . '.'
			);
		}

	}//end assertState()

	/**
	 * Add each listed document's name and matched identifiers to the preview.
	 *
	 * @param array<string, mixed>             $preview   The preview.
	 * @param array<int, array<string, mixed>> $documents The assessed documents.
	 *
	 * @return array<string, mixed> The preview.
	 */
	private function withDetails(array $preview, array $documents): array {
		$byId = array_column($documents, null, 'id');
		foreach ($preview['documents'] as $index => $listed) {
			$preview['documents'][$index]['name'] = (string) ($byId[$listed['document']]['name'] ?? '');
			$preview['documents'][$index]['values'] = (array) ($byId[$listed['document']]['values'] ?? []);
		}

		return $preview;

	}//end withDetails()

	/**
	 * The due date: the given date, else one month after the request (AVG article 12(3)).
	 *
	 * @param string            $input The given date, '' for the default.
	 * @param DateTimeImmutable $from  The request moment.
	 *
	 * @return string The due moment.
	 */
	private function dueAt(string $input, DateTimeImmutable $from): string {
		$parts = array_map('intval', explode('-', $input));
		if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $input) === 1 && checkdate($parts[1], $parts[2], $parts[0]) === true) {
			return (new DateTimeImmutable($input . 'T00:00:00+00:00'))->format(DateTimeInterface::ATOM);
		}

		return $from->add(new DateInterval('P1M'))->format(DateTimeInterface::ATOM);

	}//end dueAt()
}//end class
