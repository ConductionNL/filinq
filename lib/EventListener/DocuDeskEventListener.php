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
 */

declare(strict_types=1);

namespace OCA\DocuDesk\EventListener;

use OCA\DocuDesk\Service\MetadataService;
use OCA\DocuDesk\Service\PolicyRetroactiveService;
use OCA\DocuDesk\Service\SettingsService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCA\OpenRegister\Event\ObjectCreatedEvent;
use OCA\OpenRegister\Event\ObjectUpdatedEvent;
use OCA\OpenRegister\Event\ObjectDeletedEvent;
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
class DocuDeskEventListener implements IEventListener
{


    /**
     * Constructor for DocuDeskEventListener.
     *
     * Services are injected via Nextcloud's DI container — no more
     * `\OC::$server->get()` lookups inside `handle()`. The static accessor
     * is deprecated post-NC 30; constructor injection is the strict-DI
     * path and matches the pattern used by other Nextcloud event listeners.
     *
     * @param LoggerInterface          $logger           Structured log sink.
     * @param MetadataService          $metadataService  Metadata enricher.
     * @param SettingsService          $settingsService  Settings accessor.
     * @param PolicyRetroactiveService $retroactive      Retroactive policy applicator.
     * @param DocuDeskEventHandler     $eventHandler     Handler that contains the routing logic.
     * @param EnrichmentRunner         $enrichmentRunner Document enrichment runner.
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly MetadataService $metadataService,
        private readonly SettingsService $settingsService,
        private readonly PolicyRetroactiveService $retroactive,
        private readonly DocuDeskEventHandler $eventHandler,
        private readonly EnrichmentRunner $enrichmentRunner
    ) {

    }//end __construct()


    /**
     * Handles events related to DocuDesk document objects
     *
     * @param Event $event The event to handle
     *
     * @return void
     */
    public function handle(Event $event): void
    {
        try {
            $this->logger->info(
                'DocuDesk: Processing event',
                [
                    'eventType' => get_class($event),
                    'timestamp' => date('Y-m-d H:i:s'),
                ]
            );

            $this->dispatchEvent(
                event: $event,
                metadataService: $this->metadataService,
                settingsService: $this->settingsService,
                logger: $this->logger,
                eventHandler: $this->eventHandler,
                enrichRunner: $this->enrichmentRunner,
                retroactive: $this->retroactive
            );
        } catch (\Exception $e) {
            $this->logHandlerError(exception: $e, event: $event);
        }//end try

    }//end handle()


    /**
     * Dispatch the event to the appropriate handler
     *
     * @param Event                    $event           The event to dispatch
     * @param MetadataService          $metadataService The metadata service
     * @param SettingsService          $settingsService The settings service
     * @param LoggerInterface          $logger          The logger instance
     * @param DocuDeskEventHandler     $eventHandler    The event handler
     * @param EnrichmentRunner         $enrichRunner    The enrichment runner
     * @param PolicyRetroactiveService $retroactive     Retroactive policy applicator.
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
        EnrichmentRunner $enrichRunner,
        PolicyRetroactiveService $retroactive
    ): void {
        if ($event instanceof ObjectCreatedEvent) {
            $eventHandler->handleObjectCreated(
                $event,
                $metadataService,
                $settingsService,
                $logger,
                $enrichRunner,
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
                $enrichRunner,
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
     * @param Event      $event     The event being processed
     *
     * @return void
     */
    private function logHandlerError(\Exception $exception, Event $event): void
    {
        try {
            $this->logger->error(
                'DocuDesk: Error in event handler',
                [
                    'eventType' => get_class($event),
                    'exception' => $exception->getMessage(),
                    'file'      => $exception->getFile(),
                    'line'      => $exception->getLine(),
                ]
            );
        } catch (\Exception $logException) {
            // Silently fail if logging fails.
        }//end try

    }//end logHandlerError()


}//end class
