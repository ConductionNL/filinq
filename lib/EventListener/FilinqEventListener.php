<?php

/**
 * Filinq Event Listener
 *
 * Listener for handling events from OpenRegister specific to Filinq.
 * Delegates event handling to FilinqEventHandler.
 *
 * @category  EventListener
 * @package   OCA\Filinq\EventListener
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\EventListener;

use OCA\Filinq\Service\MetadataService;
use OCA\Filinq\Service\PolicyRetroactiveService;
use OCA\Filinq\Service\SettingsService;
use OCA\OpenRegister\Event\ObjectCreatedEvent;
use OCA\OpenRegister\Event\ObjectDeletedEvent;
use OCA\OpenRegister\Event\ObjectUpdatedEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Psr\Log\LoggerInterface;

/**
 * Event listener for handling Filinq-specific events from OpenRegister
 *
 * @category EventListener
 * @package  OCA\Filinq\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class FilinqEventListener implements IEventListener {
	/**
	 * Constructor for FilinqEventListener
	 */
	public function __construct() {

	}//end __construct()

	/**
	 * Handles events related to Filinq document objects
	 *
	 * @param Event $event The event to handle
	 *
	 * @return void
	 */
	public function handle(Event $event): void {
		try {
			$logger = \OC::$server->get(LoggerInterface::class);
			$metadataService = \OC::$server->get(MetadataService::class);
			$settingsService = \OC::$server->get(SettingsService::class);
			$retroactive = \OC::$server->get(PolicyRetroactiveService::class);
			$eventHandler = new FilinqEventHandler();

			$logger->info(
				'Filinq: Processing event',
				[
					'eventType' => get_class($event),
					'timestamp' => date('Y-m-d H:i:s'),
				]
			);

			$this->dispatchEvent(
				event: $event,
				metadataService: $metadataService,
				settingsService: $settingsService,
				logger: $logger,
				eventHandler: $eventHandler,
				retroactive: $retroactive
			);

			// Validation verdict fallback (document-validation-checks): until
			// OR's ADR-031 calculation runtime invokes DocumentValidationService
			// directly, compute + store the verdict here. The listener only
			// resolves services and delegates; all validation logic lives in
			// DocumentValidationService, all orchestration in ValidationRunner.
			(new ValidationRunner())->runFallbackForEvent(
				event: $event,
				metadataService: $metadataService,
				logger: $logger
			);
		} catch (\Throwable $e) {
			/* Throwable, not Exception. This listener runs INSIDE another
			   app's object save — OpenRegister dispatches the event — so
			   anything that escapes here does not just lose this app's
			   enrichment, it fails the caller's write. A \Error from a
			   collaborator (a TypeError on a changed signature, say) is
			   exactly the kind that used to escape, and
			   ValidationRunner::runFallbackForEvent() on this very path
			   already catches Throwable, so the two disagreed inside one
			   method. */
			$this->logHandlerError(exception: $e, event: $event);
		}//end try

	}//end handle()

	/**
	 * Dispatch the event to the appropriate handler
	 *
	 * @param Event $event The event to dispatch
	 * @param MetadataService $metadataService The metadata service
	 * @param SettingsService $settingsService The settings service
	 * @param LoggerInterface $logger The logger instance
	 * @param FilinqEventHandler $eventHandler The event handler
	 * @param PolicyRetroactiveService $retroactive Retroactive policy applicator.
	 *
	 * @return void
	 *
	 * @psalm-suppress TypeDoesNotContainType OpenRegister is an optional dep; event classes may not be loaded.
	 */
	private function dispatchEvent(
		Event $event,
		MetadataService $metadataService,
		SettingsService $settingsService,
		LoggerInterface $logger,
		FilinqEventHandler $eventHandler,
		PolicyRetroactiveService $retroactive,
	): void {
		if ($event instanceof ObjectCreatedEvent) {
			$eventHandler->handleObjectCreated(
				$event,
				$metadataService,
				$settingsService,
				$logger,
				$retroactive
			);
			return;
		}

		if ($event instanceof ObjectUpdatedEvent) {
			$eventHandler->handleObjectUpdated(
				$event,
				$metadataService,
				$settingsService,
				$logger,
				$retroactive
			);
			return;
		}

		if ($event instanceof ObjectDeletedEvent) {
			$eventHandler->handleObjectDeleted($event, $logger, $retroactive);
			return;
		}

		$logger->debug(
			'Filinq: Ignoring unhandled event type',
			[
				'eventType' => get_class($event),
			]
		);

	}//end dispatchEvent()

	/**
	 * Log an error from the event handler
	 *
	 * @param \Throwable $exception The error or exception that occurred
	 * @param Event $event The event being processed
	 *
	 * @return void
	 *
	 * @psalm-suppress UnusedParam $exception and $event are passed to the runtime-resolved logger,
	 *                             but Psalm cannot see the call because \OC::$server->get() is mixed.
	 */
	private function logHandlerError(\Throwable $exception, Event $event): void {
		try {
			$logger = \OC::$server->get(LoggerInterface::class);
			$logger->error(
				'Filinq: Error in event handler',
				[
					'eventType' => get_class($event),
					'exception' => $exception->getMessage(),
					'file' => $exception->getFile(),
					'line' => $exception->getLine(),
				]
			);
		} catch (\Throwable $logException) {
			/* Silently fail if logging itself fails — and Throwable here too,
			   both because a logger blowing up must never be the thing that
			   fails another app's write, and because referencing only
			   Throwable in this class keeps its CouplingBetweenObjects at 12
			   rather than adding \Exception as a thirteenth type. */
		}//end try

	}//end logHandlerError()
}//end class
