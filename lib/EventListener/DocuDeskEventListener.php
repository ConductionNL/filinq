<?php
/**
 * DocuDesk Event Listener
 *
 * Listener for handling events from OpenRegister specific to DocuDesk.
 * When documents are created or updated in Open Register, this listener
 * triggers metadata enrichment and consent tracking.
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
 * When documents are created or updated in Open Register, this listener
 * runs metadata enrichment (language detection, keyword extraction, topic
 * classification) and checks whether consent tracking is needed.
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

            $logger->info(
                'DocuDesk: Processing event',
                [
                    'eventType' => get_class($event),
                    'timestamp' => date('Y-m-d H:i:s'),
                ]
            );

            if ($event instanceof ObjectCreatedEvent) {
                $this->handleObjectCreated($event, $metadataService, $settingsService, $logger);
                return;
            }

            if ($event instanceof ObjectUpdatedEvent) {
                $this->handleObjectUpdated($event, $metadataService, $settingsService, $logger);
                return;
            }

            if ($event instanceof ObjectDeletedEvent) {
                $this->handleObjectDeleted($event, $logger);
                return;
            }

            $logger->debug(
                'DocuDesk: Ignoring unhandled event type',
                [
                    'eventType' => get_class($event),
                ]
            );
        } catch (\Exception $e) {
            try {
                $logger = \OC::$server->get(LoggerInterface::class);
                $logger->error(
                    'DocuDesk: Error in event handler',
                    [
                        'eventType' => get_class($event),
                        'exception' => $e->getMessage(),
                        'file'      => $e->getFile(),
                        'line'      => $e->getLine(),
                    ]
                );
            } catch (\Exception $logException) {
                // Silently fail if logging fails.
            }//end try
        }//end try

    }//end handle()


    /**
     * Handles object creation events
     *
     * When a document is created in Open Register, run metadata enrichment.
     *
     * @param ObjectCreatedEvent $event           The creation event
     * @param MetadataService    $metadataService The metadata service
     * @param SettingsService    $settingsService The settings service
     * @param LoggerInterface    $logger          The logger instance
     *
     * @return void
     */
    private function handleObjectCreated(
        ObjectCreatedEvent $event,
        MetadataService $metadataService,
        SettingsService $settingsService,
        LoggerInterface $logger
    ): void {
        $object = $event->getObject();
        if ($object === null) {
            $logger->warning('DocuDesk: ObjectCreatedEvent received with null object');
            return;
        }

        $objectId         = $object->getUuid();
        $objectSchemaId   = $object->getSchema();
        $objectRegisterId = $object->getRegister();
        $objectData       = $object->getObject();

        $logger->info(
            'DocuDesk: Processing object creation',
            [
                'objectId'   => $objectId,
                'schemaId'   => $objectSchemaId,
                'registerId' => $objectRegisterId,
            ]
        );

        // Check settings for which enrichment features are enabled.
        try {
            $settings       = $settingsService->getAllSettings();
            $enableLanguage = $settings['enable_language_detection'] ?? true;
            $enableKeywords = $settings['enable_keyword_extraction'] ?? true;
            $enableTopic    = $settings['enable_topic_classification'] ?? true;

            // Only run enrichment if at least one feature is enabled.
            if ($enableLanguage === true || $enableKeywords === true || $enableTopic === true) {
                $metadata = $metadataService->enhanceMetadata($objectData);

                if (empty($metadata) === false) {
                    $metadataService->saveEnrichedMetadata(
                        $objectId,
                        (string) $objectRegisterId,
                        (string) $objectSchemaId,
                        $metadata
                    );

                    $logger->info(
                        'DocuDesk: Metadata enrichment completed for new object',
                        [
                            'objectId'       => $objectId,
                            'enrichedFields' => array_keys($metadata),
                        ]
                    );
                }
            }
        } catch (\Exception $e) {
            $logger->error(
                'DocuDesk: Failed to enrich metadata for new object',
                [
                    'objectId'  => $objectId,
                    'exception' => $e->getMessage(),
                ]
            );
        }//end try

    }//end handleObjectCreated()


    /**
     * Handles object update events
     *
     * When a document is updated in Open Register, re-run metadata enrichment
     * if content has changed.
     *
     * @param ObjectUpdatedEvent $event           The update event
     * @param MetadataService    $metadataService The metadata service
     * @param SettingsService    $settingsService The settings service
     * @param LoggerInterface    $logger          The logger instance
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function handleObjectUpdated(
        ObjectUpdatedEvent $event,
        MetadataService $metadataService,
        SettingsService $settingsService,
        LoggerInterface $logger
    ): void {
        $object    = $event->getNewObject();
        $oldObject = $event->getOldObject();

        if ($object === null) {
            $logger->warning('DocuDesk: ObjectUpdatedEvent received with null object');
            return;
        }

        $objectId         = $object->getUuid();
        $objectSchemaId   = $object->getSchema();
        $objectRegisterId = $object->getRegister();
        $objectData       = $object->getObject();
        $oldObjectData = [];
        if ($oldObject !== null) {
            $oldObjectData = $oldObject->getObject();
        }

        $logger->info(
            'DocuDesk: Processing object update',
            [
                'objectId'   => $objectId,
                'schemaId'   => $objectSchemaId,
                'registerId' => $objectRegisterId,
            ]
        );

        // Check if content fields have changed.
        $contentFields  = ['content', 'text', 'description', 'title'];
        $contentChanged = false;
        foreach ($contentFields as $field) {
            if (($objectData[$field] ?? '') !== ($oldObjectData[$field] ?? '')) {
                $contentChanged = true;
                break;
            }
        }

        if ($contentChanged === false) {
            $logger->debug(
                'DocuDesk: No content change detected, skipping metadata re-enrichment',
                [
                    'objectId' => $objectId,
                ]
            );
            return;
        }

        // Re-enrich metadata since content changed.
        try {
            $settings       = $settingsService->getAllSettings();
            $enableLanguage = $settings['enable_language_detection'] ?? true;
            $enableKeywords = $settings['enable_keyword_extraction'] ?? true;
            $enableTopic    = $settings['enable_topic_classification'] ?? true;

            if ($enableLanguage === true || $enableKeywords === true || $enableTopic === true) {
                $metadata = $metadataService->enhanceMetadata($objectData);

                if (empty($metadata) === false) {
                    $metadataService->saveEnrichedMetadata(
                        $objectId,
                        (string) $objectRegisterId,
                        (string) $objectSchemaId,
                        $metadata
                    );

                    $logger->info(
                        'DocuDesk: Metadata re-enrichment completed for updated object',
                        [
                            'objectId'       => $objectId,
                            'enrichedFields' => array_keys($metadata),
                        ]
                    );
                }
            }
        } catch (\Exception $e) {
            $logger->error(
                'DocuDesk: Failed to re-enrich metadata for updated object',
                [
                    'objectId'  => $objectId,
                    'exception' => $e->getMessage(),
                ]
            );
        }//end try

    }//end handleObjectUpdated()


    /**
     * Handles object deletion events
     *
     * @param ObjectDeletedEvent $event  The deletion event
     * @param LoggerInterface    $logger The logger instance
     *
     * @return void
     */
    private function handleObjectDeleted(ObjectDeletedEvent $event, LoggerInterface $logger): void
    {
        $object = $event->getObject();
        if ($object === null) {
            $logger->warning('DocuDesk: ObjectDeletedEvent received with null object');
            return;
        }

        $logger->info(
            'DocuDesk: Object deleted',
            [
                'objectId'   => $object->getUuid(),
                'schemaId'   => $object->getSchema(),
                'registerId' => $object->getRegister(),
            ]
        );

    }//end handleObjectDeleted()


}//end class
