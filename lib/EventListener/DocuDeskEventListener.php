<?php

/**
 * DocuDesk Event Listener
 *
 * Listener for handling events from OpenRegister specific to DocuDesk.
 * Delegates event handling to DocuDeskEventHandler.
 *
 * @category  EventListener
 * @package   OCA\DocuDesk\EventListener
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\EventListener;

use OCA\DocuDesk\Service\MetadataService;
use OCA\DocuDesk\Service\PolicyRetroactiveService;
use OCA\DocuDesk\Service\SettingsService;
use OCA\OpenRegister\Event\ObjectCreatedEvent;
use OCA\OpenRegister\Event\ObjectDeletedEvent;
use OCA\OpenRegister\Event\ObjectUpdatedEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Psr\Log\LoggerInterface;

/**
 * Event listener for handling DocuDesk-specific events from OpenRegister
 *
 * @category EventListener
 * @package  OCA\DocuDesk\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class DocuDeskEventListener implements IEventListener {
	/**
	 * Constructor for DocuDeskEventListener
	 */
	public function __construct() {

	}//end __construct()

	/**
	 * Handles events related to DocuDesk document objects
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
			$eventHandler = new DocuDeskEventHandler();

			$logger->info(
				'DocuDesk: Processing event',
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
		} catch (\Exception $e) {
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
	 * @param DocuDeskEventHandler $eventHandler The event handler
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
		DocuDeskEventHandler $eventHandler,
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
			'DocuDesk: Ignoring unhandled event type',
			[
				'eventType' => get_class($event),
			]
		);

	}//end dispatchEvent()

	/**
	 * Log an error from the event handler
	 *
	 * @param \Exception $exception The exception that occurred
	 * @param Event $event The event being processed
	 *
	 * @return void
	 *
	 * @psalm-suppress UnusedParam $exception and $event are passed to the runtime-resolved logger,
	 *                             but Psalm cannot see the call because \OC::$server->get() is mixed.
	 */
	private function logHandlerError(\Exception $exception, Event $event): void {
		try {
			$logger = \OC::$server->get(LoggerInterface::class);
			$logger->error(
				'DocuDesk: Error in event handler',
				[
					'eventType' => get_class($event),
					'exception' => $exception->getMessage(),
					'file' => $exception->getFile(),
					'line' => $exception->getLine(),
				]
			);
		} catch (\Exception $logException) {
			// Silently fail if logging fails.
		}//end try

	}//end logHandlerError()
}//end class
