<?php

/**
 * Filinq Document Event Translator
 *
 * Turns OpenRegister object events on four filinq schemas into the outward
 * document events an outside system subscribes to: a `signingRequest` that
 * takes its `complete` transition, and a newly created `anonymizationLink`,
 * `generatedDocument` (status `generated` only) or `documentRegistration`.
 * Listening to the object events rather than to filinq's own services means
 * a write through OpenRegister's API, the portal or an agent raises the same
 * event as a click in filinq. The listener does no I/O of its own: it reads
 * the record, builds the envelope and dispatches.
 *
 * @category  EventListener
 * @package   OCA\Filinq\EventListener
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/connect-document-webhooks/specs/document-webhooks/spec.md#requirement-filinq-raises-four-document-events-whatever-path-wrote-the-record-req-cdw-001
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\EventListener;

use OCA\Filinq\Event\Webhook\DocumentAnonymisedEvent;
use OCA\Filinq\Event\Webhook\DocumentGeneratedEvent;
use OCA\Filinq\Event\Webhook\DocumentRegisteredEvent;
use OCA\Filinq\Event\Webhook\DocumentSignedEvent;
use OCA\Filinq\Event\Webhook\DocumentWebhookSubject;
use OCA\OpenRegister\Event\ObjectCreatedEvent;
use OCA\OpenRegister\Event\ObjectTransitionedEvent;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\EventDispatcher\IEventListener;
use OCP\IUserSession;

/**
 * Re-emits object events on filinq's document records as document events.
 *
 * Subscribed from {@see ObjectEventRegistrar::boot()} for the four schemas
 * only, so it is never woken for any other write. Because the three created
 * schemas share one subscription, the record is told apart by the fields its
 * schema requires.
 *
 * @category EventListener
 * @package  OCA\Filinq\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @template-implements IEventListener<Event>
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects) This class IS the mapping
 * from two OpenRegister object events to four document events, so its
 * coupling is the count of them plus the dispatcher, session and clock.
 *
 * @spec openspec/changes/connect-document-webhooks/specs/document-webhooks/spec.md#requirement-filinq-raises-four-document-events-whatever-path-wrote-the-record-req-cdw-001
 */
class DocumentEventTranslator implements IEventListener {

	/**
	 * The lifecycle action that completes a signing request.
	 */
	private const SIGNING_COMPLETE_ACTION = 'complete';

	/**
	 * Constructor.
	 *
	 * @param IEventDispatcher $dispatcher  Dispatches the document event.
	 * @param IUserSession     $userSession Names the actor of a created record.
	 * @param ITimeFactory     $time        The clock for `occurredAt`.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/connect-document-webhooks/specs/document-webhooks/spec.md#requirement-filinq-raises-four-document-events-whatever-path-wrote-the-record-req-cdw-001
	 */
	public function __construct(
		private readonly IEventDispatcher $dispatcher,
		private readonly IUserSession $userSession,
		private readonly ITimeFactory $time,
	) {

	}//end __construct()

	/**
	 * Translate one object event, or ignore it.
	 *
	 * @param Event $event The OpenRegister object event.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/connect-document-webhooks/specs/document-webhooks/spec.md#requirement-filinq-raises-four-document-events-whatever-path-wrote-the-record-req-cdw-001
	 */
	public function handle(Event $event): void {
		$documentEvent = null;
		if ($event instanceof ObjectTransitionedEvent) {
			$documentEvent = $this->fromTransition(event: $event);
		}

		if ($event instanceof ObjectCreatedEvent) {
			$documentEvent = $this->fromCreation(event: $event);
		}

		if ($documentEvent !== null) {
			$this->dispatcher->dispatchTyped($documentEvent);
		}

	}//end handle()

	/**
	 * A signing request that took its `complete` transition.
	 *
	 * @param ObjectTransitionedEvent $event The transition.
	 *
	 * @return Event|null The signed event, null for any other move.
	 */
	private function fromTransition(ObjectTransitionedEvent $event): ?Event {
		if ($event->getAction() !== self::SIGNING_COMPLETE_ACTION) {
			return null;
		}

		$entity = $event->getObject();
		$data   = $entity->getObject();
		if (array_key_exists('signatureLevel', $data) === false || array_key_exists('documentFileId', $data) === false) {
			return null;
		}

		return new DocumentSignedEvent(
			subject: $this->subject(entity: $entity, fileId: ($data['documentFileId'] ?? null), name: ($data['documentName'] ?? null)),
			actor: $event->getUserId(),
			details: [
				'signingRequestId' => $entity->getUuid(),
				'signedFileRef' => $this->stringOrNull(value: ($data['signedDocumentRef'] ?? null)),
				'signatureLevel' => $this->stringOrNull(value: ($data['signatureLevel'] ?? null)),
			],
			occurredAt: $this->now()
		);

	}//end fromTransition()

	/**
	 * A newly created anonymisation link, generated document or registration.
	 *
	 * @param ObjectCreatedEvent $event The creation.
	 *
	 * @return Event|null The document event, null when the record raises none.
	 */
	private function fromCreation(ObjectCreatedEvent $event): ?Event {
		$entity = $event->getObject();
		if ($entity === null) {
			return null;
		}

		$data  = $entity->getObject();
		$actor = $this->userSession->getUser()?->getUID();

		if ($this->has(data: $data, keys: ['direction', 'unit', 'document']) === true) {
			return new DocumentRegisteredEvent(
				subject: $this->subject(entity: $entity, fileId: ($data['document'] ?? null), name: null),
				actor: $actor,
				details: [
					'direction' => $this->stringOrNull(value: ($data['direction'] ?? null)),
					'unit' => $this->stringOrNull(value: ($data['unit'] ?? null)),
					'registrationNumber' => $this->stringOrNull(value: ($data['registrationNumber'] ?? null)),
				],
				occurredAt: $this->now()
			);
		}

		if ($this->has(data: $data, keys: ['sourceFileId', 'anonymizedFileId']) === true) {
			return new DocumentAnonymisedEvent(
				subject: $this->subject(entity: $entity, fileId: ($data['sourceFileId'] ?? null), name: ($data['sourceFileName'] ?? null)),
				actor: $actor,
				details: [
					'sourceFileId' => $data['sourceFileId'],
					'anonymisedFileId' => $data['anonymizedFileId'],
					'verificationVerdict' => $this->stringOrNull(value: ($data['verificationVerdict'] ?? null)),
				],
				occurredAt: $this->now()
			);
		}

		if ($this->has(data: $data, keys: ['templateId', 'format', 'status']) === true) {
			return $this->generated(entity: $entity, data: $data, actor: $actor);
		}

		return null;

	}//end fromCreation()

	/**
	 * A generated document, but only one whose generation succeeded.
	 *
	 * @param object               $entity The OpenRegister object entity.
	 * @param array<string, mixed> $data   Its data.
	 * @param string|null          $actor  The user who caused it.
	 *
	 * @return Event|null The generated event, null for a failed generation.
	 */
	private function generated(object $entity, array $data, ?string $actor): ?Event {
		if ($data['status'] !== 'generated') {
			return null;
		}

		$path = $this->stringOrNull(value: ($data['filePath'] ?? null));
		$name = null;
		if ($path !== null) {
			$name = basename($path);
		}

		return new DocumentGeneratedEvent(
			subject: $this->subject(entity: $entity, fileId: ($data['fileId'] ?? null), name: $name),
			actor: $actor,
			details: [
				'templateId' => $data['templateId'],
				'templateVersion' => ($data['templateVersion'] ?? null),
				'format' => $this->stringOrNull(value: $data['format']),
			],
			occurredAt: $this->now()
		);

	}//end generated()

	/**
	 * The document and record a document event names.
	 *
	 * @param object $entity The OpenRegister object entity.
	 * @param mixed  $fileId The document's file id or reference.
	 * @param mixed  $name   The document's name.
	 *
	 * @return DocumentWebhookSubject The subject.
	 */
	private function subject(object $entity, mixed $fileId, mixed $name): DocumentWebhookSubject {
		if (is_int($fileId) === false && is_string($fileId) === false) {
			$fileId = null;
		}

		$organisation = null;
		if (method_exists($entity, 'getOrganisation') === true) {
			$organisation = $this->stringOrNull(value: $entity->getOrganisation());
		}

		return new DocumentWebhookSubject(
			fileId: $fileId,
			name: $this->stringOrNull(value: $name),
			register: $this->stringOrNull(value: $entity->getRegister()),
			schema: $this->stringOrNull(value: $entity->getSchema()),
			id: $this->stringOrNull(value: $entity->getUuid()),
			organisation: $organisation
		);

	}//end subject()

	/**
	 * Whether every key is present in the record.
	 *
	 * @param array<string, mixed> $data The record's data.
	 * @param array<int, string>   $keys The keys its schema requires.
	 *
	 * @return bool True when all are present.
	 */
	private function has(array $data, array $keys): bool {
		foreach ($keys as $key) {
			if (array_key_exists($key, $data) === false) {
				return false;
			}
		}

		return true;

	}//end has()

	/**
	 * A scalar as a string, anything else as null.
	 *
	 * @param mixed $value The value.
	 *
	 * @return string|null The string, or null.
	 */
	private function stringOrNull(mixed $value): ?string {
		if (is_scalar($value) === false || $value === '') {
			return null;
		}

		return (string) $value;

	}//end stringOrNull()

	/**
	 * The current time in ISO 8601.
	 *
	 * @return string The time.
	 */
	private function now(): string {
		return $this->time->getDateTime()->format('c');

	}//end now()
}//end class
