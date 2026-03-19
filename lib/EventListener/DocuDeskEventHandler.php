<?php
/**
 * DocuDesk Event Handler
 *
 * Service for handling OpenRegister object CRUD events for DocuDesk.
 * Contains the business logic for metadata enrichment on object
 * create/update/delete. Extracted from DocuDeskEventListener
 * to reduce class complexity.
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
use OCA\OpenRegister\Event\ObjectCreatedEvent;
use OCA\OpenRegister\Event\ObjectUpdatedEvent;
use OCA\OpenRegister\Event\ObjectDeletedEvent;
use Psr\Log\LoggerInterface;

/**
 * Handles OpenRegister object events for DocuDesk metadata enrichment
 *
 * @category EventListener
 * @package  OCA\DocuDesk\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class DocuDeskEventHandler
{


    /**
     * Check if enrichment is enabled based on settings
     *
     * @param SettingsService $settingsService The settings service
     *
     * @return bool True if at least one enrichment feature is enabled
     */
    public function isEnrichmentEnabled(SettingsService $settingsService): bool
    {
        $settings       = $settingsService->getAllSettings();
        $enableLanguage = $settings['enable_language_detection'] ?? true;
        $enableKeywords = $settings['enable_keyword_extraction'] ?? true;
        $enableTopic    = $settings['enable_topic_classification'] ?? true;

        return $enableLanguage === true || $enableKeywords === true || $enableTopic === true;

    }//end isEnrichmentEnabled()


    /**
     * Run metadata enrichment for an object
     *
     * @param MetadataService $metadataService The metadata service
     * @param array           $objectData      The object data to enrich
     * @param string          $objectId        The object UUID
     * @param string          $registerId      The register ID
     * @param string          $schemaId        The schema ID
     * @param LoggerInterface $logger          The logger instance
     * @param string          $logContext       Context string for log messages
     *
     * @return void
     */
    public function runEnrichment(
        MetadataService $metadataService,
        array $objectData,
        string $objectId,
        string $registerId,
        string $schemaId,
        LoggerInterface $logger,
        string $logContext
    ): void {
        $metadata = $metadataService->enhanceMetadata($objectData);

        if (empty($metadata) === true) {
            return;
        }

        $metadataService->saveEnrichedMetadata($objectId, $registerId, $schemaId, $metadata);

        $logger->info(
            'DocuDesk: Metadata enrichment completed for '.$logContext,
            [
                'objectId'       => $objectId,
                'enrichedFields' => array_keys($metadata),
            ]
        );

    }//end runEnrichment()


    /**
     * Check if content fields have changed between old and new object data
     *
     * @param array<string, mixed> $objectData    The new object data
     * @param array<string, mixed> $oldObjectData The old object data
     *
     * @return bool True if content has changed
     */
    public function hasContentChanged(array $objectData, array $oldObjectData): bool
    {
        $contentFields = ['content', 'text', 'description', 'title'];
        foreach ($contentFields as $field) {
            if (($objectData[$field] ?? '') !== ($oldObjectData[$field] ?? '')) {
                return true;
            }
        }

        return false;

    }//end hasContentChanged()


    /**
     * Handles object creation events
     *
     * @param ObjectCreatedEvent $event           The creation event
     * @param MetadataService    $metadataService The metadata service
     * @param SettingsService    $settingsService The settings service
     * @param LoggerInterface    $logger          The logger instance
     *
     * @return void
     */
    public function handleObjectCreated(
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

        try {
            if ($this->isEnrichmentEnabled($settingsService) === true) {
                $this->runEnrichment(
                    $metadataService,
                    $objectData,
                    $objectId,
                    (string) $objectRegisterId,
                    (string) $objectSchemaId,
                    $logger,
                    'new object'
                );
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
     * @param ObjectUpdatedEvent $event           The update event
     * @param MetadataService    $metadataService The metadata service
     * @param SettingsService    $settingsService The settings service
     * @param LoggerInterface    $logger          The logger instance
     *
     * @return void
     */
    public function handleObjectUpdated(
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
        $oldObjectData    = [];
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

        if ($this->hasContentChanged($objectData, $oldObjectData) === false) {
            $logger->debug(
                'DocuDesk: No content change detected, skipping metadata re-enrichment',
                ['objectId' => $objectId]
            );
            return;
        }

        try {
            if ($this->isEnrichmentEnabled($settingsService) === true) {
                $this->runEnrichment(
                    $metadataService,
                    $objectData,
                    $objectId,
                    (string) $objectRegisterId,
                    (string) $objectSchemaId,
                    $logger,
                    'updated object'
                );
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
    public function handleObjectDeleted(ObjectDeletedEvent $event, LoggerInterface $logger): void
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
