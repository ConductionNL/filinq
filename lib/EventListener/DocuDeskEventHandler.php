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
 * @spec openspec/specs/metadata-enrichment/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\EventListener;

use OCA\DocuDesk\Service\GrondslagenSummaryService;
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
     * Constructor for DocuDeskEventHandler
     *
     * @param EnrichmentRunner $enrichmentRunner The enrichment runner. Defaults to a
     *                                           fresh stateless instance; injectable
     *                                           so tests can substitute a double.
     *
     * @return void
     */
    public function __construct(
        private readonly EnrichmentRunner $enrichmentRunner=new EnrichmentRunner()
    ) {

    }//end __construct()

    /**
     * Handles object creation events
     *
     * @param ObjectCreatedEvent       $event           The creation event
     * @param MetadataService          $metadataService The metadata service
     * @param SettingsService          $settingsService The settings service
     * @param LoggerInterface          $logger          The logger instance
     * @param PolicyRetroactiveService $retroactive     Retroactive policy applicator
     *                                                  (injected here, not via
     *                                                  service-locator).
     *
     * @return void
     *
     * @spec openspec/specs/metadata-enrichment/spec.md
     */
    public function handleObjectCreated(
        ObjectCreatedEvent $event,
        MetadataService $metadataService,
        SettingsService $settingsService,
        LoggerInterface $logger,
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

        $this->enrichmentRunner->enrichObject(
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
     * @param ObjectUpdatedEvent       $event           The update event
     * @param MetadataService          $metadataService The metadata service
     * @param SettingsService          $settingsService The settings service
     * @param LoggerInterface          $logger          The logger instance
     * @param PolicyRetroactiveService $retroactive     Retroactive policy applicator.
     *
     * @return void
     *
     * @spec openspec/specs/metadata-enrichment/spec.md
     */
    public function handleObjectUpdated(
        ObjectUpdatedEvent $event,
        MetadataService $metadataService,
        SettingsService $settingsService,
        LoggerInterface $logger,
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

        $this->enrichmentRunner->enrichObject(
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
     *
     * @spec openspec/specs/metadata-enrichment/spec.md
     */
    private function maybeRegenerateGrondslagenSummary(
        array $objectData,
        array $oldObjectData,
        LoggerInterface $logger
    ): void {
        if ($this->shouldRegenerateGrondslagenSummary(
            objectData: $objectData,
            oldObjectData: $oldObjectData
        ) === false
        ) {
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
     * Decide whether a dossier update warrants a grondslagen-summary regen.
     *
     * True only when the payload looks like a dossier, `checkedOn` actually
     * changed to a non-empty value, and auto-regen is not opted out of.
     *
     * @param array<string, mixed> $objectData    The new dossier object data.
     * @param array<string, mixed> $oldObjectData The previous dossier object data.
     *
     * @return bool True when the summary should be regenerated.
     *
     * @spec openspec/specs/metadata-enrichment/spec.md
     */
    private function shouldRegenerateGrondslagenSummary(array $objectData, array $oldObjectData): bool
    {
        if ($this->looksLikeDossier(objectData: $objectData) === false) {
            return false;
        }

        $newCheckedOn = ($objectData['checkedOn'] ?? null);
        $oldCheckedOn = ($oldObjectData['checkedOn'] ?? null);
        if ($newCheckedOn === null || $newCheckedOn === '' || $newCheckedOn === $oldCheckedOn) {
            return false;
        }

        return $this->isGrondslagenAutoRegenEnabled(objectData: $objectData);

    }//end shouldRegenerateGrondslagenSummary()

    /**
     * Read `configuration.grondslagen.autoRegenOnReview`, defaulting to enabled.
     *
     * @param array<string, mixed> $objectData The dossier object data.
     *
     * @return bool True when auto-regen is enabled (the default).
     *
     * @spec openspec/specs/metadata-enrichment/spec.md
     */
    private function isGrondslagenAutoRegenEnabled(array $objectData): bool
    {
        $configuration = ($objectData['configuration'] ?? []);
        $grondslagen   = [];
        if (is_array($configuration) === true && is_array(($configuration['grondslagen'] ?? null)) === true) {
            $grondslagen = $configuration['grondslagen'];
        }

        return ($grondslagen['autoRegenOnReview'] ?? true) === true;

    }//end isGrondslagenAutoRegenEnabled()

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
        if ($this->looksLikeProhibition(objectData: $objectData) === true) {
            return 'prohibition';
        }

        if (isset($objectData['consentStatus']) === true) {
            return $this->detectConsentScope(objectData: $objectData);
        }

        return null;

    }//end detectPolicyShape()

    /**
     * Structural test for a `publicationProhibition` payload.
     *
     * A prohibition is identified by the combination of `matchRules` plus
     * `reason` or `legalAuthority`, and the absence of a `consentStatus` field
     * (consent records always carry it).
     *
     * @param array<string, mixed> $objectData The changed object's payload.
     *
     * @return bool True when the payload is a prohibition record.
     */
    private function looksLikeProhibition(array $objectData): bool
    {
        $hasMatchRules = isset($objectData['matchRules']) === true
            && is_array($objectData['matchRules']) === true
            && count($objectData['matchRules']) > 0;
        if ($hasMatchRules === false) {
            return false;
        }

        if (isset($objectData['consentStatus']) === true) {
            return false;
        }

        $hasReason = isset($objectData['reason']) === true && (string) $objectData['reason'] !== '';

        return $hasReason === true || isset($objectData['legalAuthority']) === true;

    }//end looksLikeProhibition()

    /**
     * Classify a consent payload by its `scope` field.
     *
     * @param array<string, mixed> $objectData The changed object's payload.
     *
     * @return string 'standing_consent' for scope=entity, otherwise 'document_consent'.
     */
    private function detectConsentScope(array $objectData): string
    {
        $scope = (string) ($objectData['scope'] ?? 'document');
        if ($scope === 'entity') {
            return 'standing_consent';
        }

        return 'document_consent';

    }//end detectConsentScope()

    /**
     * Check if content fields have changed between old and new object data
     *
     * @param array<string, mixed> $objectData    The new object data
     * @param array<string, mixed> $oldObjectData The old object data
     *
     * @return bool True if content has changed
     *
     * @spec openspec/specs/metadata-enrichment/spec.md
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
