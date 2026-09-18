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
	 * @param IUserSession $userSession The current session.
	 * @param LoggerInterface $logger Logger for diagnostics.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IntakeRepository $repository,
		private readonly IntakeAuthorizationGate $gate,
		private readonly IntakeFilePlacement $placement,
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

		return $this->repository->save(document: $document);

	}//end receive()

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
	 * @param array<string, mixed> $target The record, as `register`, `schema` and `id`.
	 *
	 * @return array<string, mixed> The assigned document.
	 *
	 * @throws IntakeRefusedException When the target is incomplete, the document
	 *                                has already left the inbox, or the clerk may
	 *                                not write one of the two schemas.
	 *
	 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
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

		return $this->repository->save(document: $document, uuid: $uuid);

	}//end assign()

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
		if ($status !== IntakeRepository::STATUS_RECEIVED) {
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
		return (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format(\DateTimeInterface::ATOM);

	}//end now()
}//end class
