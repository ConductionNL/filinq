<?php

/**
 * Filinq DocumentWebhookEvent
 *
 * The base of the four document events an outside system can subscribe to
 * through OpenRegister's webhooks: signed, anonymised, generated and
 * registered. Each carries one small envelope that names the document and
 * the record it is about, never the file's content. OpenRegister delivers
 * it; filinq only dispatches it.
 *
 * @category  Event
 * @package   OCA\Filinq\Event\Webhook
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/connect-document-webhooks/specs/document-webhooks/spec.md#requirement-the-event-names-the-document-and-never-carries-its-content-req-cdw-002
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Event\Webhook;

use OCP\EventDispatcher\Event;

/**
 * One document event and its envelope.
 *
 * The envelope is built from an allow-list: the common keys plus the
 * event's own {@see detailKeys()}. A detail the caller passes that is not on
 * that list is dropped, so nothing reaches a receiver by accident.
 *
 * @category Event
 * @package  OCA\Filinq\Event\Webhook
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/connect-document-webhooks/specs/document-webhooks/spec.md#requirement-the-event-names-the-document-and-never-carries-its-content-req-cdw-002
 */
abstract class DocumentWebhookEvent extends Event {

	/**
	 * Construct the event.
	 *
	 * @param DocumentWebhookSubject $subject    The document and the record it is about.
	 * @param string|null            $actor      The user who caused it, null for a system write.
	 * @param array<string, mixed>   $details    The event's own fields; keys outside
	 *                                           {@see detailKeys()} are dropped.
	 * @param string                 $occurredAt ISO 8601 time the event happened.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/connect-document-webhooks/specs/document-webhooks/spec.md#requirement-the-event-names-the-document-and-never-carries-its-content-req-cdw-002
	 */
	public function __construct(
		private readonly DocumentWebhookSubject $subject,
		private readonly ?string $actor,
		private readonly array $details,
		private readonly string $occurredAt,
	) {
		parent::__construct();

	}//end __construct()

	/**
	 * The public event name, e.g. `document.signed`.
	 *
	 * @return string The event name.
	 *
	 * @spec openspec/changes/connect-document-webhooks/specs/document-webhooks/spec.md#requirement-the-event-names-the-document-and-never-carries-its-content-req-cdw-002
	 */
	abstract public function getEventName(): string;

	/**
	 * The fields this event adds to the common envelope.
	 *
	 * @return array<int, string> The detail keys, in envelope order.
	 *
	 * @spec openspec/changes/connect-document-webhooks/specs/document-webhooks/spec.md#requirement-the-event-names-the-document-and-never-carries-its-content-req-cdw-002
	 */
	abstract public function detailKeys(): array;

	/**
	 * The organisation of the record, so delivery can find its webhooks.
	 *
	 * @return string|null The organisation id, null when the record names none.
	 *
	 * @spec openspec/changes/connect-document-webhooks/specs/document-webhooks/spec.md#requirement-delivery-is-openregister-s-req-cdw-004
	 */
	public function getOrganisation(): ?string {
		return $this->subject->organisation;

	}//end getOrganisation()

	/**
	 * The envelope a receiver gets.
	 *
	 * @return array<string, mixed> event, occurredAt, document, record, actor
	 *                              and the event's own fields.
	 *
	 * @spec openspec/changes/connect-document-webhooks/specs/document-webhooks/spec.md#requirement-the-event-names-the-document-and-never-carries-its-content-req-cdw-002
	 */
	public function getPayload(): array {
		$payload = [
			'event' => $this->getEventName(),
			'occurredAt' => $this->occurredAt,
			'document' => [
				'fileId' => $this->subject->fileId,
				'name' => $this->subject->name,
			],
			'record' => [
				'register' => $this->subject->register,
				'schema' => $this->subject->schema,
				'id' => $this->subject->id,
			],
			'actor' => $this->actor,
		];

		foreach ($this->detailKeys() as $key) {
			$payload[$key] = ($this->details[$key] ?? null);
		}

		return $payload;

	}//end getPayload()
}//end class
