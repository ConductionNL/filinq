<?php

/**
 * Signing Conclusion Emitter
 *
 * Owns the cross-app delegated-signing conclusion contract
 * (docudesk-signing-events): deciding whether a concluded signing request is
 * delegated at all, building the outcome envelope, dispatching it, and
 * fail-softing any dispatch error. Extracted from SigningService, which had
 * grown past the complexity and coupling thresholds and which has no other
 * reason to know about IEventDispatcher or the event shape.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/docudesk-signing-events/specs/docudesk-signing-events/spec.md
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use OCA\DocuDesk\Event\SigningConcludedEventFactory;
use OCP\EventDispatcher\IEventDispatcher;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Dispatches the terminal SigningConcludedEvent for a delegated signing request.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/changes/docudesk-signing-events/specs/docudesk-signing-events/spec.md
 */
class SigningConclusionEmitter {
	/**
	 * Constructor.
	 *
	 * @param IEventDispatcher $eventDispatcher Dispatches the cross-app contract event
	 * @param LoggerInterface $logger Logger
	 * @param SigningConcludedEventFactory $eventFactory Maps the persisted request onto the event
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IEventDispatcher $eventDispatcher,
		private readonly LoggerInterface $logger,
		private readonly SigningConcludedEventFactory $eventFactory,
	) {

	}//end __construct()

	/**
	 * Emit a SigningConcludedEvent when a delegated request concludes.
	 *
	 * Cross-app delegated-signing contract (docudesk-signing-events): only
	 * fires for a signing request that carries provenance (`sourceApp` set and
	 * non-empty) — internal DocuDesk requests emit nothing. The outcome
	 * envelope is built from the persisted request fields (signers, signed
	 * document reference) and dispatched via IEventDispatcher so the originating
	 * consumer (e.g. shillinq) can run its own downstream side effects.
	 * Fail-soft: any dispatch error is logged and the already-persisted
	 * terminal transition is never rolled back.
	 *
	 * @param array<string, mixed> $request The persisted (terminal) signing-request array
	 * @param string $status Normalised status (signed|declined|expired|cancelled)
	 * @param string|null $signedDocumentRef Reference to the signed document, when signed
	 *
	 * @spec openspec/changes/docudesk-signing-events/specs/docudesk-signing-events/spec.md
	 *
	 * @return void
	 */
	public function emitIfDelegated(array $request, string $status, ?string $signedDocumentRef = null): void {
		$sourceApp = (string)($request['sourceApp'] ?? '');
		if ($sourceApp === '') {
			// Internal request (no consumer is waiting) — emit nothing.
			return;
		}

		try {
			$event = $this->eventFactory->create(
				request: $request,
				status: $status,
				signedDocumentRef: $signedDocumentRef
			);

			$this->eventDispatcher->dispatchTyped($event);

			$this->logger->info(
				'DocuDesk: dispatched SigningConcludedEvent',
				[
					'signingRequestId' => $event->getSigningRequestId(),
					'sourceApp' => $sourceApp,
					'status' => $status,
				]
			);
		} catch (Throwable $e) {
			// The terminal transition has already persisted; a dispatch failure
			// must not roll it back.
			$this->logger->error(
				'DocuDesk: signing request concluded but SigningConcludedEvent dispatch failed',
				[
					'sourceApp' => $sourceApp,
					'status' => $status,
					'exception' => $e->getMessage(),
				]
			);
		}//end try

	}//end emitIfDelegated()
}//end class
