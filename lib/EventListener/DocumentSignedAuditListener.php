<?php

/**
 * Writes a document's signing into its audit trail
 *
 * @category  EventListener
 * @package   OCA\Filinq\EventListener
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/specify-capability-rows/specs/document-register/spec.md#requirement-every-action-on-a-document-is-in-its-audit-trail-req-scr-007
 */

declare(strict_types=1);

namespace OCA\Filinq\EventListener;

use OCA\Filinq\Event\Webhook\DocumentSignedEvent;
use OCA\Filinq\Service\Audit\DocumentActionAudit;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Turns a completed signing request into the `signed` action of the document
 * that was signed.
 *
 * Signing completes in several providers, but every one of them ends in the
 * signing request's `complete` transition, which filinq already turns into
 * {@see DocumentSignedEvent} whatever path wrote it. Listening to that event
 * writes one entry per completed signing, from one place.
 *
 * @category EventListener
 * @package  OCA\Filinq\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @template-implements IEventListener<Event>
 *
 * @spec openspec/changes/specify-capability-rows/specs/document-register/spec.md#requirement-every-action-on-a-document-is-in-its-audit-trail-req-scr-007
 */
class DocumentSignedAuditListener implements IEventListener {

	/**
	 * Constructor.
	 *
	 * @param DocumentActionAudit $audit  The document trail.
	 * @param LoggerInterface     $logger Logger.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/specify-capability-rows/specs/document-register/spec.md#requirement-every-action-on-a-document-is-in-its-audit-trail-req-scr-007
	 */
	public function __construct(
		private readonly DocumentActionAudit $audit,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Write `signed` on the signed document, naming the signing request and level.
	 *
	 * @param Event $event The event.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/specify-capability-rows/specs/document-register/spec.md#requirement-every-action-on-a-document-is-in-its-audit-trail-req-scr-007
	 */
	public function handle(Event $event): void {
		if (($event instanceof DocumentSignedEvent) === false) {
			return;
		}

		$payload = $event->getPayload();
		$fileId  = $payload['document']['fileId'] ?? null;
		if (is_numeric($fileId) === false || (int) $fileId <= 0) {
			return;
		}

		try {
			$this->audit->record(
				fileId: (int) $fileId,
				action: 'signed',
				context: [
					'signingRequestId' => $payload['signingRequestId'] ?? null,
					'signatureLevel' => $payload['signatureLevel'] ?? null,
				]
			);
		} catch (Throwable $e) {
			// A signing must never fail because its trail could not be written.
			$this->logger->warning(
				message: '[DocumentSignedAuditListener] a signing could not be audited',
				context: ['fileId' => $fileId, 'error' => $e->getMessage()]
			);
		}

	}//end handle()
}//end class
