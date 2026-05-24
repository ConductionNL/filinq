<?php
/**
 * DocuDesk Event Handler
 *
 * Service for handling OpenRegister object CRUD events for DocuDesk.
 * Delegates enrichment logic to EnrichmentRunner.
 * Extracted from DocuDeskEventListener to reduce class complexity.
 *
 * @category  EventListener
 * @package   OCA\DocuDesk\EventListener
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-32
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
     * Handles object creation events
     *
     * @param ObjectCreatedEvent $event            The creation event
     * @param MetadataService    $metadataService  The metadata service
     * @param SettingsService    $settingsService  The settings service
     * @param LoggerInterface    $logger           The logger instance
     * @param EnrichmentRunner   $enrichmentRunner The enrichment runner
     *
     * @return void
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-32
     */
    public function handleObjectCreated(
        ObjectCreatedEvent $event,
        MetadataService $metadataService,
        SettingsService $settingsService,
        LoggerInterface $logger,
        EnrichmentRunner $enrichmentRunner
    ): void {
        $object = $event->getObject();
        if ($object === null) {
            $logger->warning('DocuDesk: ObjectCreatedEvent received with null object');
            return;
        }

        $enrichmentRunner->enrichObject(
            $object,
            $metadataService,
            $settingsService,
            $logger,
            'new object'
        );

    }//end handleObjectCreated()

    /**
     * Handles object update events
     *
     * @param ObjectUpdatedEvent $event            The update event
     * @param MetadataService    $metadataService  The metadata service
     * @param SettingsService    $settingsService  The settings service
     * @param LoggerInterface    $logger           The logger instance
     * @param EnrichmentRunner   $enrichmentRunner The enrichment runner
     *
     * @return void
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-32
     */
    public function handleObjectUpdated(
        ObjectUpdatedEvent $event,
        MetadataService $metadataService,
        SettingsService $settingsService,
        LoggerInterface $logger,
        EnrichmentRunner $enrichmentRunner
    ): void {
        $object    = $event->getNewObject();
        $oldObject = $event->getOldObject();

        if ($object === null) {
            $logger->warning('DocuDesk: ObjectUpdatedEvent received with null object');
            return;
        }

        $objectData    = $object->getObject();
        $oldObjectData = [];
        if ($oldObject !== null) {
            $oldObjectData = $oldObject->getObject();
        }

        if ($this->hasContentChanged(objectData: $objectData, oldObjectData: $oldObjectData) === false) {
            $logger->debug(
                'DocuDesk: No content change detected, skipping re-enrichment',
                ['objectId' => $object->getUuid()]
            );
            return;
        }

        $enrichmentRunner->enrichObject(
            $object,
            $metadataService,
            $settingsService,
            $logger,
            'updated object'
        );

    }//end handleObjectUpdated()

    /**
     * Handles object deletion events
     *
     * @param ObjectDeletedEvent $event  The deletion event
     * @param LoggerInterface    $logger The logger instance
     *
     * @return void
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-32
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

    /**
     * Check if content fields have changed between old and new object data
     *
     * @param array<string, mixed> $objectData    The new object data
     * @param array<string, mixed> $oldObjectData The old object data
     *
     * @return bool True if content has changed
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-32
     */
    private function hasContentChanged(array $objectData, array $oldObjectData): bool
    {
        $contentFields = ['content', 'text', 'description', 'title'];
        foreach ($contentFields as $field) {
            if (($objectData[$field] ?? '') !== ($oldObjectData[$field] ?? '')) {
                return true;
            }
        }

        return false;

    }//end hasContentChanged()
}//end class
