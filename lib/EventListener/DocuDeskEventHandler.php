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

use OCA\DocuDesk\Service\MetadataService;
use OCA\DocuDesk\Service\PolicyRetroactiveService;
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
     * @param ObjectCreatedEvent       $event            The creation event
     * @param MetadataService          $metadataService  The metadata service
     * @param SettingsService          $settingsService  The settings service
     * @param LoggerInterface          $logger           The logger instance
     * @param EnrichmentRunner         $enrichmentRunner The enrichment runner
     * @param PolicyRetroactiveService $retroactive      Retroactive policy applicator
     *                                                   (injected here, not via
     *                                                   service-locator).
     *
     * @return void
     */
    public function handleObjectCreated(
        ObjectCreatedEvent $event,
        MetadataService $metadataService,
        SettingsService $settingsService,
        LoggerInterface $logger,
        EnrichmentRunner $enrichmentRunner,
        PolicyRetroactiveService $retroactive
    ): void {
        $object = $event->getObject();
        if ($object === null) {
            $logger->warning('DocuDesk: ObjectCreatedEvent received with null object');
            return;
        }

        $this->dispatchPolicyRetroactive(
            objectData: $object->getObject(),
            logger: $logger,
            reason: 'created',
            retroactive: $retroactive
        );

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
     * @param ObjectUpdatedEvent       $event            The update event
     * @param MetadataService          $metadataService  The metadata service
     * @param SettingsService          $settingsService  The settings service
     * @param LoggerInterface          $logger           The logger instance
     * @param EnrichmentRunner         $enrichmentRunner The enrichment runner
     * @param PolicyRetroactiveService $retroactive      Retroactive policy applicator.
     *
     * @return void
     */
    public function handleObjectUpdated(
        ObjectUpdatedEvent $event,
        MetadataService $metadataService,
        SettingsService $settingsService,
        LoggerInterface $logger,
        EnrichmentRunner $enrichmentRunner,
        PolicyRetroactiveService $retroactive
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

        $this->dispatchPolicyRetroactive(
            objectData: $objectData,
            logger: $logger,
            reason: 'updated',
            retroactive: $retroactive
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
     * Handles object deletion events
     *
     * @param ObjectDeletedEvent       $event       The deletion event
     * @param LoggerInterface          $logger      The logger instance
     * @param PolicyRetroactiveService $retroactive Retroactive policy applicator.
     *
     * @return void
     */
    public function handleObjectDeleted(
        ObjectDeletedEvent $event,
        LoggerInterface $logger,
        PolicyRetroactiveService $retroactive
    ): void {
        $object = $event->getObject();
        if ($object === null) {
            $logger->warning('DocuDesk: ObjectDeletedEvent received with null object');
            return;
        }

        $this->dispatchPolicyRetroactive(
            objectData: $object->getObject(),
            logger: $logger,
            reason: 'deleted',
            retroactive: $retroactive
        );

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
     * Route policy-surface mutations to the retroactive layer.
     *
     * Identifies the changed object as a `publicationProhibition` or a
     * `publicationConsent` (scope=entity / scope=document) using payload-shape
     * heuristics rather than schema-ID lookups, since:
     *   - the event's `$object->getSchema()` returns a schema *ID* (numeric),
     *     not a slug, so we would need an extra DB round-trip per event;
     *   - the discriminating fields (`reason` + `legalAuthority` for
     *     prohibitions; `scope` for consents) are stable across versions.
     *
     * For non-policy events this is a cheap no-op and returns immediately.
     *
     * @param array<string, mixed>     $objectData  The changed object's payload.
     * @param LoggerInterface          $logger      Structured log sink.
     * @param string                   $reason      'created' | 'updated' | 'deleted'.
     * @param PolicyRetroactiveService $retroactive Retroactive policy applicator
     *                                              (constructor/method-injected at
     *                                              the calling public handler).
     *
     * @return void
     *
     * @psalm-suppress UnusedParam Both $logger and $reason are used in the conditional branches —
     *                             Psalm misreads the path coverage through the try/catch.
     */
    private function dispatchPolicyRetroactive(
        array $objectData,
        LoggerInterface $logger,
        string $reason,
        PolicyRetroactiveService $retroactive
    ): void {
        $shape = $this->detectPolicyShape(objectData: $objectData);
        if ($shape === null) {
            return;
        }

        try {
            if ($shape === 'prohibition') {
                if ($reason === 'deleted') {
                    $retroactive->applyRuleRemoval();
                    return;
                }

                $resolved = $retroactive->applyProhibitionMutation(prohibition: $objectData);
                if ($resolved > 0) {
                    $logger->info(
                        'DocuDesk: prohibition mutation force-resolved in-flight records',
                        ['resolved' => $resolved, 'reason' => $reason]
                    );
                }

                return;
            }

            if ($shape === 'standing_consent') {
                if ($reason === 'deleted') {
                    $retroactive->applyRuleRemoval();
                    return;
                }

                $retroactive->applyStandingConsentMutation();
                return;
            }

            // Document_consent shape (workflow record): not a policy rule, no-op.
        } catch (\Exception $e) {
            $logger->warning(
                'DocuDesk: retroactive policy dispatch failed',
                ['error' => $e->getMessage(), 'reason' => $reason]
            );
        }//end try

    }//end dispatchPolicyRetroactive()

    /**
     * Classify a payload as a policy record by structural signature.
     *
     * @param array<string, mixed> $objectData The changed object's payload.
     *
     * @return string|null 'prohibition', 'standing_consent', 'document_consent', or null.
     */
    private function detectPolicyShape(array $objectData): ?string
    {
        // A prohibition is identified by the combination of `reason` + `matchRules`
        // and the absence of a `consentStatus` field (consent records always carry it).
        $hasMatchRules     = isset($objectData['matchRules']) === true
            && is_array($objectData['matchRules']) === true
            && count($objectData['matchRules']) > 0;
        $hasReason         = isset($objectData['reason']) === true && (string) $objectData['reason'] !== '';
        $hasLegalAuthority = isset($objectData['legalAuthority']) === true;
        $hasConsentStatus  = isset($objectData['consentStatus']) === true;

        if ($hasMatchRules === true
            && ($hasReason === true || $hasLegalAuthority === true)
            && $hasConsentStatus === false
        ) {
            return 'prohibition';
        }

        if ($hasConsentStatus === true) {
            $scope = (string) ($objectData['scope'] ?? 'document');
            if ($scope === 'entity') {
                return 'standing_consent';
            }

            return 'document_consent';
        }

        return null;

    }//end detectPolicyShape()

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
