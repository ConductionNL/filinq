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

use OCA\Filinq\Kernel\Contracts\MetadataServiceInterface;
use OCA\Filinq\Kernel\Contracts\PolicyRetroactiveServiceInterface;
use OCA\Filinq\Kernel\Contracts\SettingsServiceInterface;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Psr\Container\ContainerInterface;
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
	 *
	 * @param ContainerInterface $container App container this listener's collaborators are resolved from.
	 */
	public function __construct(
		private readonly ContainerInterface $container,
	) {

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
			$logger = $this->container->get(LoggerInterface::class);
			$services = new ObjectEventServices(
				metadataService: $this->container->get(MetadataServiceInterface::class),
				settingsService: $this->container->get(SettingsServiceInterface::class),
				logger: $logger,
				retroactive: $this->container->get(PolicyRetroactiveServiceInterface::class)
			);
			$eventHandler = new FilinqEventHandler(container: $this->container);

			$logger->info(
				'Filinq: Processing event',
				[
					'eventType' => get_class($event),
					'timestamp' => date('Y-m-d H:i:s'),
				]
			);

			$eventHandler->dispatch(event: $event, services: $services);

			// Validation verdict fallback (document-validation-checks): until
			// OR's ADR-031 calculation runtime invokes DocumentValidationService
			// directly, compute + store the verdict here. The listener only
			// resolves services and delegates; all validation logic lives in
			// DocumentValidationService, all orchestration in ValidationRunner.
			(new ValidationRunner(container: $this->container))->runFallbackForEvent(
				event: $event,
				metadataService: $services->metadataService,
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
	 * Log an error from the event handler
	 *
	 * @param \Throwable $exception The error or exception that occurred
	 * @param Event $event The event being processed
	 *
	 * @return void
	 *
	 * @psalm-suppress UnusedParam $exception and $event are passed to the runtime-resolved logger,
	 *                             but Psalm cannot see the call because the container returns mixed.
	 */
	private function logHandlerError(\Throwable $exception, Event $event): void {
		try {
			$logger = $this->container->get(LoggerInterface::class);
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
