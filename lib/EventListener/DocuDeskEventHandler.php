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
 */

declare(strict_types=1);

namespace OCA\DocuDesk\EventListener;

use OCA\DocuDesk\Service\GrondslagenSummaryService;
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

        // Wave 4a: auto-regenerate the per-dossier grondslagen summary when a
        // dossier's `checkedOn` review timestamp changes (and the dossier
        // opts in via `configuration.grondslagen.autoRegenOnReview`, default
        // true). Runs synchronously inside the event flow; failures are
        // logged but do NOT roll back the dossier update.
        $this->maybeRegenerateGrondslagenSummary(
            objectData: $objectData,
            oldObjectData: $oldObjectData,
            logger: $logger
        );

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
     * On a dossier `checkedOn` change, fire the per-dossier grondslagen summary regen.
     *
     * Detection is payload-shape based (no schema-id lookup): an object is
     * treated as a dossier when it carries a `checkedOn` field at the top
     * level alongside `name` AND `bases` (the canonical dossier signature
     * per `add-dossier-schema`). This avoids a round-trip to OR's
     * SchemaMapper for every ObjectUpdatedEvent dispatched.
     *
     * The regen runs only when:
     *   - `configuration.grondslagen.autoRegenOnReview` is missing OR true.
     *   - `checkedOn` actually changed between old and new payloads
     *     (creating a checkedOn for the first time also counts).
     *
     * Regen failure is logged at warning level; the dossier update itself
     * is not affected. The dossier's `configuration.grondslagen.{fileId,
     * lastGeneratedAt}` is updated by the summary service after a
     * successful render.
     *
     * @param array<string, mixed> $objectData    The new dossier object data.
     * @param array<string, mixed> $oldObjectData The previous dossier object data
     *                                            (empty when the event lacks a previous state).
     * @param LoggerInterface      $logger        Structured logger.
     *
     * @return void
     */
    private function maybeRegenerateGrondslagenSummary(
        array $objectData,
        array $oldObjectData,
        LoggerInterface $logger
    ): void {
        if ($this->looksLikeDossier(objectData: $objectData) === false) {
            return;
        }

        $newCheckedOn = ($objectData['checkedOn'] ?? null);
        $oldCheckedOn = ($oldObjectData['checkedOn'] ?? null);
        if ($newCheckedOn === null || $newCheckedOn === '' || $newCheckedOn === $oldCheckedOn) {
            return;
        }

        $configuration = ($objectData['configuration'] ?? []);
        $grondslagen   = [];
        if (is_array($configuration) === true && is_array(($configuration['grondslagen'] ?? null)) === true) {
            $grondslagen = $configuration['grondslagen'];
        }

        $autoRegen = ($grondslagen['autoRegenOnReview'] ?? true);
        if ($autoRegen !== true) {
            return;
        }

        $self        = ($objectData['@self'] ?? []);
        $dossierUuid = (string) ($self['id'] ?? $self['uuid'] ?? $objectData['id'] ?? $objectData['uuid'] ?? '');
        if ($dossierUuid === '') {
            $logger->warning('DocuDesk: dossier checkedOn change detected but UUID missing; skipping regen');
            return;
        }

        try {
            $service = \OC::$server->get(GrondslagenSummaryService::class);
            $service->renderDossierSummary(dossierUuid: $dossierUuid);
        } catch (\Throwable $e) {
            $logger->warning(
                'DocuDesk: grondslagen-summary auto-regen failed',
                [
                    'dossierUuid' => $dossierUuid,
                    'error'       => $e->getMessage(),
                ]
            );
        }

    }//end maybeRegenerateGrondslagenSummary()


    /**
     * Shape-detect whether an object looks like a `dossier` register record.
     *
     * Cheap heuristic: the schema-id lookup would round-trip to OR per
     * event; instead we check for the canonical dossier fields (`name` +
     * `bases` + presence of either a current `checkedOn` field on the
     * object, OR the field's slot existing on the object's `@self` /
     * configuration). This matches every real dossier and is unlikely to
     * collide with other schemas in this register.
     *
     * @param array<string, mixed> $objectData The event's object payload.
     *
     * @return bool
     */
    private function looksLikeDossier(array $objectData): bool
    {
        return array_key_exists('name', $objectData) === true
            && array_key_exists('bases', $objectData) === true
            && (array_key_exists('checkedOn', $objectData) === true
                || array_key_exists('configuration', $objectData) === true);

    }//end looksLikeDossier()


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


    /**
     * Check if content fields have changed between old and new object data
     *
     * @param array<string, mixed> $objectData    The new object data
     * @param array<string, mixed> $oldObjectData The old object data
     *
     * @return bool True if content has changed
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
