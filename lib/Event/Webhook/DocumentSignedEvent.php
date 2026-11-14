<?php

/**
 * Filinq DocumentSignedEvent
 *
 * Raised when a signing request completes: every signer signed and the
 * signed artifact exists. OpenRegister delivers it to every webhook that subscribes to
 * this class name.
 *
 * @category  Event
 * @package   OCA\Filinq\Event\Webhook
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

namespace OCA\Filinq\Event\Webhook;

/**
 * The `document.signed` document event.
 *
 * @category Event
 * @package  OCA\Filinq\Event\Webhook
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/connect-document-webhooks/specs/document-webhooks/spec.md#requirement-filinq-raises-four-document-events-whatever-path-wrote-the-record-req-cdw-001
 */
final class DocumentSignedEvent extends DocumentWebhookEvent {

	/**
	 * The public event name.
	 *
	 * @return string The event name.
	 *
	 * @spec openspec/changes/connect-document-webhooks/specs/document-webhooks/spec.md#requirement-the-event-names-the-document-and-never-carries-its-content-req-cdw-002
	 */
	public function getEventName(): string {
		return 'document.signed';

	}//end getEventName()

	/**
	 * The fields this event adds to the envelope.
	 *
	 * @return array<int, string> The detail keys.
	 *
	 * @spec openspec/changes/connect-document-webhooks/specs/document-webhooks/spec.md#requirement-the-event-names-the-document-and-never-carries-its-content-req-cdw-002
	 */
	public function detailKeys(): array {
		return ['signingRequestId', 'signedFileRef', 'signatureLevel'];

	}//end detailKeys()
}//end class
