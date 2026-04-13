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
     * Constructor for DocuDeskEventListener
     */
    public function __construct()
    {

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
            $logger          = \OC::$server->get(LoggerInterface::class);
            $metadataService = \OC::$server->get(MetadataService::class);
            $settingsService = \OC::$server->get(SettingsService::class);
            $eventHandler    = new DocuDeskEventHandler();
            $enrichRunner    = new EnrichmentRunner();

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
                enrichRunner: $enrichRunner
            );
        } catch (\Exception $e) {
            $this->logHandlerError(exception: $e, event: $event);
        }//end try

    }//end handle()


    /**
     * Dispatch the event to the appropriate handler
     *
     * @param Event                $event           The event to dispatch
     * @param MetadataService      $metadataService The metadata service
     * @param SettingsService      $settingsService The settings service
     * @param LoggerInterface      $logger          The logger instance
     * @param DocuDeskEventHandler $eventHandler    The event handler
     * @param EnrichmentRunner     $enrichRunner    The enrichment runner
     *
     * @return void
     */
    private function dispatchEvent(
        Event $event,
        MetadataService $metadataService,
        SettingsService $settingsService,
        LoggerInterface $logger,
        DocuDeskEventHandler $eventHandler,
        EnrichmentRunner $enrichRunner
    ): void {
        if ($event instanceof ObjectCreatedEvent) {
            $eventHandler->handleObjectCreated(
                $event,
                $metadataService,
                $settingsService,
                $logger,
                $enrichRunner
            );
            return;
        }

        if ($event instanceof ObjectUpdatedEvent) {
            $eventHandler->handleObjectUpdated(
                $event,
                $metadataService,
                $settingsService,
                $logger,
                $enrichRunner
            );
            return;
        }

        if ($event instanceof ObjectDeletedEvent) {
            $eventHandler->handleObjectDeleted($event, $logger);
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
            $logger = \OC::$server->get(LoggerInterface::class);
            $logger->error(
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
