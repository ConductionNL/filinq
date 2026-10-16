<?php

/**
 * Filinq DocumentGenerationRequestedListener
 *
 * Carries out a DocumentGenerationRequestedEvent another app dispatched, and
 * writes the outcome back onto the event for the synchronous dispatcher.
 *
 * All the work is DocumentGenerationRequestService::generate(), the same
 * method the `filinq.generate-document` flow node calls, so the command event
 * and the node cannot drift apart.
 *
 * @category  EventListener
 * @package   OCA\Filinq\EventListener
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/flow-generate-document-node/specs/flow-document-generation/spec.md#requirement-other-apps-request-a-document-through-a-command-event
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\EventListener;

use OCA\Filinq\Event\DocumentGenerationRequestedEvent;
use OCA\Filinq\Service\DocumentGenerationRequestService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Handles DocumentGenerationRequestedEvent by delegating to the shared service.
 *
 * A failure is written to the event's error slot and logged; it never escapes
 * into the dispatcher, because the dispatching app must be able to read the
 * refusal and decide for itself, the same way it reads a success.
 *
 * @category EventListener
 * @package  OCA\Filinq\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @implements IEventListener<Event>
 *
 * @spec openspec/changes/flow-generate-document-node/specs/flow-document-generation/spec.md#requirement-other-apps-request-a-document-through-a-command-event
 */
class DocumentGenerationRequestedListener implements IEventListener {

	/**
	 * Constructor.
	 *
	 * @param DocumentGenerationRequestService $generator The shared generation service.
	 * @param LoggerInterface                  $logger    The logger.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly DocumentGenerationRequestService $generator,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Handle a DocumentGenerationRequestedEvent.
	 *
	 * @param Event $event The dispatched event.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/flow-generate-document-node/specs/flow-document-generation/spec.md#requirement-other-apps-request-a-document-through-a-command-event
	 */
	public function handle(Event $event): void {
		if (($event instanceof DocumentGenerationRequestedEvent) === false) {
			return;
		}

		try {
			$event->setResult(
				$this->generator->generate(
					request: $event->getRequest(),
					requestingApp: $event->getRequestingApp()
				)
			);
		} catch (Throwable $e) {
			$event->setError($e->getMessage());
			$this->logger->error(
				'Filinq: DocumentGenerationRequestedEvent could not be carried out',
				[
					'app' => 'filinq',
					'requestingApp' => $event->getRequestingApp(),
					'exception' => $e->getMessage(),
				]
			);
		}
	}//end handle()
}//end class
