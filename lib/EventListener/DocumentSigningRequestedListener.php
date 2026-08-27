<?php

/**
 * Filinq DocumentSigningRequestedListener
 *
 * Maps an inbound cross-app DocumentSigningRequestedEvent to the existing
 * SigningService::createRequest() create logic (provenance-persisting). The
 * in-process replacement for the broken
 * `$registry->call('filinq','createSigningRequest',…)` delegation path: any
 * installed consumer app dispatches DocumentSigningRequestedEvent and Filinq
 * raises the signing request here, writing the resolved signingRequestId back
 * onto the event for the synchronous producer.
 *
 * @category  EventListener
 * @package   OCA\Filinq\EventListener
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/filinq-signing-events/specs/filinq-signing-events/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\EventListener;

use OCA\Filinq\Event\DocumentSigningRequestedEvent;
use OCA\Filinq\Service\SigningService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Psr\Log\LoggerInterface;

/**
 * Handles DocumentSigningRequestedEvent by delegating to SigningService.
 *
 * Reuses the existing create logic (ADR-022, no parallel CRUD): builds the
 * request-data array from the event's document reference, signers, signing
 * parameters and provenance and calls createRequest() POSITIONALLY. On success
 * the resolved signingRequestId + handled flag are written back onto the event;
 * on failure the listener logs and leaves the event unhandled — no exception
 * escapes into the dispatcher.
 *
 * @category EventListener
 * @package  OCA\Filinq\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @implements IEventListener<Event>
 *
 * @spec openspec/changes/filinq-signing-events/specs/filinq-signing-events/spec.md
 */
class DocumentSigningRequestedListener implements IEventListener {
	/**
	 * Constructor.
	 *
	 * @param SigningService $signingService The reused create-request service
	 * @param LoggerInterface $logger Logger
	 *
	 * @return void
	 */
	public function __construct(
		private readonly SigningService $signingService,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Handle a DocumentSigningRequestedEvent.
	 *
	 * @param Event $event The dispatched event
	 *
	 * @spec openspec/changes/filinq-signing-events/specs/filinq-signing-events/spec.md
	 *
	 * @return void
	 */
	public function handle(Event $event): void {
		if (($event instanceof DocumentSigningRequestedEvent) === false) {
			return;
		}

		try {
			$data = $this->buildRequestData(event: $event);
			$created = $this->signingService->createRequest($data);

			$requestId = (string)($created['id'] ?? $created['uuid'] ?? '');
			if ($requestId !== '') {
				$event->setSigningRequestId($requestId);
				$event->setHandled(true);
				$this->logger->info(
					'Filinq: handled DocumentSigningRequestedEvent',
					[
						'sourceApp' => $event->getSourceApp(),
						'subjectId' => $event->getSubjectId(),
						'signingRequestId' => $requestId,
					]
				);
				return;
			}

			// No id on the created request: leave the event unhandled and log.
			$this->logger->warning(
				'Filinq: DocumentSigningRequestedEvent not handled (no request id)',
				[
					'sourceApp' => $event->getSourceApp(),
					'subjectId' => $event->getSubjectId(),
				]
			);
		} catch (\Throwable $e) {
			// The event bus must never surface a Filinq failure as an
			// exception to the dispatching consumer; log and leave unhandled.
			$this->logger->error(
				'Filinq: DocumentSigningRequestedListener failed',
				[
					'sourceApp' => $event->getSourceApp(),
					'subjectId' => $event->getSubjectId(),
					'exception' => $e->getMessage(),
				]
			);
		}//end try

	}//end handle()

	/**
	 * Build the data array SigningService::createRequest() expects from the event.
	 *
	 * Threads the signing parameters plus the consumer provenance fields so the
	 * created request can later be correlated back to the consumer when it
	 * concludes.
	 *
	 * @param DocumentSigningRequestedEvent $event The request event
	 *
	 * @spec openspec/changes/filinq-signing-events/specs/filinq-signing-events/spec.md
	 *
	 * @return array<string, mixed>
	 */
	private function buildRequestData(DocumentSigningRequestedEvent $event): array {
		return [
			'documentFileId' => $event->getDocumentReference(),
			'documentName' => $event->getSubjectLabel(),
			'signatureLevel' => $event->getSignatureLevel(),
			'signingMode' => $event->getSigningMode(),
			'signers' => $event->getSigners(),
			'sourceApp' => $event->getSourceApp(),
			'subjectRegister' => $event->getSubjectRegister(),
			'subjectSchema' => $event->getSubjectSchema(),
			'subjectId' => $event->getSubjectId(),
			'subjectLabel' => $event->getSubjectLabel(),
			'externalReference' => $event->getExternalReference(),
			'correlationId' => $event->getCorrelationId(),
		];

	}//end buildRequestData()
}//end class
