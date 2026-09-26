<?php

/**
 * Intake Service
 *
 * The inbox between a channel and a case. A scanner, a shared mailbox and
 * digital post all deliver documents before anyone knows which record they
 * belong to; this service receives them, lists what is waiting, and records the
 * clerk's decision: assigned to a record, or rejected with a reason.
 *
 * The verbs live here rather than in the controller because the intake leaf on
 * another app's page reaches the same decisions over the same checks.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use DateTimeImmutable;
use DateTimeZone;
use OCA\Filinq\Event\IntakeDocumentReceivedEvent;
use OCA\Filinq\Exception\IntakeRefusedException;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * Receives, lists, assigns and rejects the documents waiting for a record.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
 */
class IntakeService {

	/**
	 * Constructor.
	 *
	 * @param IntakeRepository $repository The intake document store.
	 * @param IntakeAuthorizationGate $gate The write-rights gate.
	 * @param IntakeFilePlacement $placement Moves an assigned file into the record's folder.
	 * @param IntakeDefaultRuleService $defaults Stamps the declared defaults at creation.
	 * @param IntakeRoutingService $routing Applies what a consuming app declared per record type.
	 * @param PartySuggestionService $parties Reads a party out of the document, and never files one.
	 * @param IUserSession $userSession The current session.
	 * @param LoggerInterface $logger Logger for diagnostics.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IntakeRepository $repository,
		private readonly IntakeAuthorizationGate $gate,
		private readonly IntakeFilePlacement $placement,
		private readonly IntakeDefaultRuleService $defaults,
		private readonly IntakeRoutingService $routing,
		private readonly PartySuggestionService $parties,
		private readonly IUserSession $userSession,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Take in one delivered document and put it in the inbox.
	 *
	 * A second delivery under the same source reference does NOT make a second
	 * row: the first one is returned unchanged, whatever state it is in. A
	 * feeder that retries would otherwise fill the inbox with duplicates that
	 * no clerk can tell apart.
	 *
	 * @param IntakeDocumentReceivedEvent $event What the channel delivered.
	 *
	 * @return array<string, mixed> The stored intake document.
	 *
	 * @throws IntakeRefusedException When the channel is not one this app accepts.
	 *
	 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
	 */
	public function receive(IntakeDocumentReceivedEvent $event): array {
		$channel = $event->getChannel();
		if (in_array(needle: $channel, haystack: IntakeDocumentReceivedEvent::CHANNELS, strict: true) === false) {
			throw new IntakeRefusedException(
				message: 'Unknown intake channel: ' . $channel,
				status: 400
			);
		}

		$existing = $this->repository->findBySourceRef(sourceRef: $event->getSourceRef());
		if ($existing !== null) {
			$this->logger->info(
				message: '[IntakeService] the channel delivered this document before, keeping the first intake document',
				context: [
					'file' => __FILE__,
					'line' => __LINE__,
					'sourceRef' => $event->getSourceRef(),
					'uuid' => ($existing['uuid'] ?? ''),
				]
			);

			return $existing;
		}

		$document = [
			'channel' => $channel,
			'subject' => $event->getSubject(),
			'sender' => $event->getSender(),
			'receivedAt' => ($event->getReceivedAt() ?? $this->now()),
			'fileName' => $event->getFileName(),
			'sourceRef' => $event->getSourceRef(),
			'status' => IntakeRepository::STATUS_RECEIVED,
		];

		$fileId = $event->getFileId();
		if ($fileId !== null) {
			$document['file'] = $fileId;
		}

		if ($event->getArrivedWith() !== '') {
			$document['arrivedWith'] = $event->getArrivedWith();
		}

		// STAMPED BEFORE ANYTHING ELSE LOOKS AT IT. The defaults are a decision
		// somebody wrote down; a classifier's guess arrives later and is offered
		// beside the stamped value, never over it.
		$document = $this->defaults->stamp(
			document: $document,
			channel: $channel,
			sender: $event->getSender()
		);

		if ($fileId !== null) {
			$document['partySuggestion'] = $this->parties->suggestFor(fileId: $fileId);
		}

		return $this->repository->save(document: $document);

	}//end receive()

	/**
	 * Take in a message and everything that came attached to it.
	 *
	 * Each attachment is an intake document of its own naming the message it
	 * arrived with. Folding attachments into the message would lose exactly the
	 * case that matters: one attachment often belongs to a different record
	 * from the letter it came with, and a folded attachment has no way to say
	 * so.
	 *
	 * @param IntakeDocumentReceivedEvent $message What the channel delivered as the message.
	 * @param array<int, IntakeDocumentReceivedEvent> $attachments What came with it.
	 *
	 * @return array<int, array<string, mixed>> The message first, then its attachments.
	 *
	 * @throws IntakeRefusedException When a channel is not one this app accepts.
	 *
	 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	public function receiveMessage(IntakeDocumentReceivedEvent $message, array $attachments = []): array {
		$stored = $this->receive(event: $message);
		$documents = [$stored];
		$messageUuid = (string)($stored['uuid'] ?? '');

		foreach ($attachments as $attachment) {
			$documents[] = $this->receive(
				event: new IntakeDocumentReceivedEvent(
					channel: $attachment->getChannel(),
					fileId: $attachment->getFileId(),
					fileName: $attachment->getFileName(),
					subject: $attachment->getSubject(),
					sender: $attachment->getSender(),
					sourceRef: $attachment->getSourceRef(),
					receivedAt: $attachment->getReceivedAt(),
					arrivedWith: $messageUuid
				)
			);
		}

		return $documents;

	}//end receiveMessage()

	/**
	 * Everything that arrived with one message, the message excluded.
	 *
	 * @param string $uuid The message's intake document.
	 *
	 * @return array<int, array<string, mixed>> The attachments.
	 *
	 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	public function attachmentsOf(string $uuid): array {
		return $this->repository->findArrivedWith(uuid: $uuid);

	}//end attachmentsOf()

	/**
	 * The worklist of documents taken back off a record.
	 *
	 * @return array<int, array<string, mixed>> The detached documents.
	 *
	 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	public function listDetached(): array {
		return $this->repository->findByStatus(status: IntakeRepository::STATUS_DETACHED);

	}//end listDetached()

	/**
	 * Everything waiting for a clerk.
	 *
	 * @return array<int, array<string, mixed>> The waiting documents.
	 *
	 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
	 */
	public function listWaiting(): array {
		return $this->repository->findWaiting();

	}//end listWaiting()

	/**
	 * Assign one waiting document to a record.
	 *
	 * @param string $uuid The intake document.
	 * @param array<string, mixed> $target The record, as `register`, `schema` and `id`,
	 *                                     optionally with the `declaringApp` and
	 *                                     `typeReference` the routing is declared against.
	 * This assigns the one document. To take everything that arrived with it
	 * along, call {@see assignWithAttachments()}.
	 *
	 * @return array<string, mixed> The assigned document.
	 *
	 * @throws IntakeRefusedException When the target is incomplete, the document
	 *                                has already left the inbox, or the clerk may
	 *                                not write one of the two schemas.
	 *
	 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
	 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	public function assign(string $uuid, array $target): array {
		$register = trim((string)($target['register'] ?? ''));
		$schema = trim((string)($target['schema'] ?? ''));
		$id = trim((string)($target['id'] ?? ''));

		if ($register === '' || $schema === '' || $id === '') {
			throw new IntakeRefusedException(
				message: 'An assignment names the register, the schema and the id of the record it goes to.',
				status: 400
			);
		}

		$document = $this->waiting(uuid: $uuid);
		$this->requireWrite(register: IntakeRepository::REGISTER, schema: IntakeRepository::SCHEMA);
		$this->requireWrite(register: $register, schema: $schema);

		$placedAt = $this->placement->place(
			fileId: (int)($document['file'] ?? 0),
			register: $register,
			schema: $schema,
			id: $id
		);

		$document['assignedTo'] = ['register' => $register, 'schema' => $schema, 'id' => $id];
		$document['assignedBy'] = $this->currentUserId();
		$document['assignedAt'] = $this->now();
		$document['status'] = IntakeRepository::STATUS_ASSIGNED;
		if ($placedAt !== null) {
			$document['filePath'] = $placedAt;
		}

		$document = $this->routing->apply(
			document: $document,
			declaringApp: trim((string)($target['declaringApp'] ?? '')),
			typeReference: trim((string)($target['typeReference'] ?? ''))
		);

		$assigned = $this->repository->save(document: $document, uuid: $uuid);

		$arrivedWith = trim((string)($document['arrivedWith'] ?? ''));
		if ($arrivedWith !== '') {
			$this->noteOnMessage(messageUuid: $arrivedWith, attachmentUuid: $uuid, target: $target);
		}

		return $assigned;

	}//end assign()

	/**
	 * Assign one waiting document and everything that arrived with it.
	 *
	 * The message is assigned first, so a refusal on the message itself stops
	 * before any attachment moves. An attachment that has already left the
	 * inbox is skipped rather than assigned twice.
	 *
	 * @param string $uuid The intake document of the message.
	 * @param array<string, mixed> $target The record, as `register`, `schema` and `id`,
	 *                                     optionally with the `declaringApp` and
	 *                                     `typeReference` the routing is declared against.
	 *
	 * @return array<string, mixed> The assigned message.
	 *
	 * @throws IntakeRefusedException When the message itself cannot be assigned.
	 *
	 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	public function assignWithAttachments(string $uuid, array $target): array {
		$assigned = $this->assign(uuid: $uuid, target: $target);

		foreach ($this->repository->findArrivedWith(uuid: $uuid) as $attachment) {
			$attachmentUuid = (string)($attachment['uuid'] ?? '');
			if ($attachmentUuid === '' || (string)($attachment['status'] ?? '') !== IntakeRepository::STATUS_RECEIVED) {
				continue;
			}

			$this->assign(uuid: $attachmentUuid, target: $target);
		}

		return $assigned;

	}//end assignWithAttachments()

	/**
	 * Note on a message that one of its attachments went somewhere.
	 *
	 * An attachment assigned on its own is the interesting case: the letter
	 * goes to one record and one of its bijlagen to another. Both records say
	 * so, because a reader of either one would otherwise have to guess.
	 *
	 * @param string $messageUuid The message's intake document.
	 * @param string $attachmentUuid The attachment's intake document.
	 * @param array<string, mixed> $target Where the attachment went.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	private function noteOnMessage(string $messageUuid, string $attachmentUuid, array $target): void {
		$message = $this->repository->findByUuid(uuid: $messageUuid);
		if ($message === null) {
			return;
		}

		$notes = [];
		if (isset($message['attachmentNotes']) === true && is_array($message['attachmentNotes']) === true) {
			$notes = $message['attachmentNotes'];
		}

		$notes[] = [
			'attachment' => $attachmentUuid,
			'assignedTo' => [
				'register' => (string)($target['register'] ?? ''),
				'schema' => (string)($target['schema'] ?? ''),
				'id' => (string)($target['id'] ?? ''),
			],
			'assignedBy' => $this->currentUserId(),
			'assignedAt' => $this->now(),
		];

		$message['attachmentNotes'] = $notes;
		$this->repository->save(document: $message, uuid: $messageUuid);

	}//end noteOnMessage()

	/**
	 * Reject one waiting document, with the reason it was rejected for.
	 *
	 * @param string $uuid The intake document.
	 * @param string $reason Why it does not belong here.
	 *
	 * @return array<string, mixed> The rejected document.
	 *
	 * @throws IntakeRefusedException When there is no reason, the document has
	 *                                already left the inbox, or the clerk may not
	 *                                write the intake register.
	 *
	 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
	 */
	public function reject(string $uuid, string $reason): array {
		$reason = trim($reason);
		if ($reason === '') {
			throw new IntakeRefusedException(
				message: 'A rejection says why the document does not belong here.',
				status: 400
			);
		}

		$document = $this->waiting(uuid: $uuid);
		$this->requireWrite(register: IntakeRepository::REGISTER, schema: IntakeRepository::SCHEMA);

		$document['rejectReason'] = $reason;
		$document['rejectedBy'] = $this->currentUserId();
		$document['rejectedAt'] = $this->now();
		$document['status'] = IntakeRepository::STATUS_REJECTED;

		return $this->repository->save(document: $document, uuid: $uuid);

	}//end reject()

	/**
	 * Read one document that is still waiting, or refuse.
	 *
	 * @param string $uuid The intake document.
	 *
	 * @return array<string, mixed> The waiting document.
	 *
	 * @throws IntakeRefusedException When it does not exist or has already left the inbox.
	 *
	 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
	 */
	private function waiting(string $uuid): array {
		$document = $this->repository->findByUuid(uuid: $uuid);
		if ($document === null) {
			throw new IntakeRefusedException(
				message: 'This document is not in the intake inbox.',
				status: 404
			);
		}

		$status = (string)($document['status'] ?? '');
		// A DETACHED document is waiting again. It came back off a record with
		// a reason, and the whole point of the worklist is that a clerk can act
		// on it, so treating it as "already left the inbox" would strand it.
		if (in_array($status, [IntakeRepository::STATUS_RECEIVED, IntakeRepository::STATUS_DETACHED], true) === false) {
			throw new IntakeRefusedException(
				message: 'This document has already left the inbox: it is ' . $status . '.',
				status: 409
			);
		}

		return $document;

	}//end waiting()

	/**
	 * Refuse the caller when they may not write this schema.
	 *
	 * @param string $register The register slug.
	 * @param string $schema The schema slug.
	 *
	 * @return void
	 *
	 * @throws IntakeRefusedException When the write is not allowed.
	 *
	 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
	 */
	private function requireWrite(string $register, string $schema): void {
		if ($this->gate->mayWrite(register: $register, schema: $schema) === true) {
			return;
		}

		throw new IntakeRefusedException(
			message: 'You may not write ' . $schema . ' in ' . $register . '.',
			status: 403
		);

	}//end requireWrite()

	/**
	 * The user id of the person at the keyboard.
	 *
	 * @return string The user id, or an empty string when there is no session.
	 *
	 * @spec exclude Session accessor with no behaviour of its own.
	 */
	private function currentUserId(): string {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return '';
		}

		return $user->getUID();

	}//end currentUserId()

	/**
	 * Now, in ISO 8601.
	 *
	 * @return string The timestamp.
	 *
	 * @spec exclude Clock accessor with no behaviour of its own.
	 */
	private function now(): string {
		return (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format(DateTimeImmutable::ATOM);

	}//end now()
}//end class
