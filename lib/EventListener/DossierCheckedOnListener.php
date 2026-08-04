<?php

/**
 * Dossier CheckedOn Listener
 *
 * Listens for OpenRegister ObjectUpdatedEvent on dossier objects.
 * When a dossier's `checkedOn` field is updated and
 * `configuration.grondslagen.autoRegenOnReview` is true (default),
 * triggers an automatic regeneration of the per-dossier grondslagen summary PDF.
 *
 * Regen failure is deliberately swallowed: the dossier update MUST succeed
 * regardless of whether the summary can be rendered.
 *
 * @category EventListener
 * @package  OCA\DocuDesk\EventListener
 *
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/anonymisation-grondslagen-summary-rendering/tasks.md#task-7
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\EventListener;

use OCA\DocuDesk\Service\GrondslagenSummaryService;
use OCA\OpenRegister\Event\ObjectUpdatedEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Psr\Log\LoggerInterface;


/**
 * Event listener that auto-regenerates the dossier grondslagen summary on checkedOn update
 *
 * @category EventListener
 * @package  OCA\DocuDesk\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://conduction.nl
 *
 * @spec openspec/changes/anonymisation-grondslagen-summary-rendering/tasks.md#task-7
 *
 * @psalm-suppress MismatchingDocblockReturnType
 */
class DossierCheckedOnListener implements IEventListener
{

    /**
     * DocuDesk register slug for dossier objects.
     *
     * @var string
     */
    private const REGISTER = 'docudesk';

    /**
     * Dossier schema slug.
     *
     * @var string
     */
    private const DOSSIER_SCHEMA = 'dossier';

    /**
     * Constructor for DossierCheckedOnListener.
     *
     * @param LoggerInterface           $logger         Logger for diagnostics
     * @param GrondslagenSummaryService $summaryService Dossier grondslagen summary renderer
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-grondslagen-summary-rendering/tasks.md#task-7
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly GrondslagenSummaryService $summaryService,
    ) {

    }//end __construct()

    /**
     * Handle an ObjectUpdatedEvent and trigger auto-regen when applicable.
     *
     * Regen fires iff:
     * 1. The updated object is a dossier (register=docudesk, schema=dossier)
     * 2. `checkedOn` has changed between the old and new object data
     * 3. `configuration.grondslagen.autoRegenOnReview` is true (or absent, defaults to true)
     *
     * @param Event $event The dispatched event
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-grondslagen-summary-rendering/tasks.md#task-7
     */
    public function handle(Event $event): void
    {
        if (($event instanceof ObjectUpdatedEvent) === false) {
            return;
        }

        try {
            $this->processObjectUpdatedEvent(event: $event);
        } catch (\Throwable $e) {
            $this->logError(exception: $e, context: 'DossierCheckedOnListener::handle');
        }

    }//end handle()

    /**
     * Process the ObjectUpdatedEvent for dossier checkedOn changes.
     *
     * @param ObjectUpdatedEvent $event The update event
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-grondslagen-summary-rendering/tasks.md#task-7
     */
    private function processObjectUpdatedEvent(ObjectUpdatedEvent $event): void
    {
        $newObject = $event->getNewObject();
        $oldObject = $event->getOldObject();

        if ($newObject === null) {
            return;
        }

        // Only process dossier objects.
        if ($this->isDossierObject(object: $newObject) === false) {
            return;
        }

        $newData = $newObject->getObject();
        $oldData = [];
        if ($oldObject !== null) {
            $oldData = $oldObject->getObject();
        }

        // Only regen when checkedOn has actually changed.
        if ($this->hasCheckedOnChanged(newData: $newData, oldData: $oldData) === false) {
            return;
        }

        // Honour the opt-out flag (default is true / auto-regen enabled).
        if ($this->isAutoRegenEnabled(data: $newData) === false) {
            return;
        }

        $dossierId = $newObject->getId();
        if ($dossierId === null) {
            return;
        }

        // Run synchronously; catch any failure to preserve the review result.
        try {
            $this->logger->info(
                message: 'DocuDesk: Auto-regenerating dossier grondslagen summary on checkedOn update',
                context: ['dossierId' => $dossierId]
            );

            $this->summaryService->renderDossierSummary(dossierUuid: (string) $dossierId);
        } catch (\Throwable $e) {
            // Log but do NOT rethrow — the dossier update must succeed.
            $this->logError(
                exception: $e,
                context: 'GrondslagenSummaryService::renderDossierSummary (auto-regen)',
                extra: ['dossierId' => $dossierId]
            );
        }//end try

    }//end processObjectUpdatedEvent()

    /**
     * Determine whether the updated object is a dossier.
     *
     * Checks the object's register and schema slugs against DocuDesk constants.
     *
     * @param mixed $object The updated ObjectEntity
     *
     * @return bool True when this is a dossier object
     */
    private function isDossierObject(mixed $object): bool
    {
        if (is_object($object) === false) {
            return false;
        }

        $register = null;
        $schema   = null;

        if (method_exists(object_or_class: $object, method: 'getRegister') === true) {
            $register = $object->getRegister();
        }

        if (method_exists(object_or_class: $object, method: 'getSchema') === true) {
            $schema = $object->getSchema();
        }

        return $this->matchesSlug(candidate: $register, expected: self::REGISTER) === true
            && $this->matchesSlug(candidate: $schema, expected: self::DOSSIER_SCHEMA) === true;

    }//end isDossierObject()

    /**
     * Determine whether a register/schema reference matches an expected slug.
     *
     * Accepts both a bare slug string and a Register/Schema entity exposing
     * `getSlug()`, so numeric/UUID-keyed references resolve conservatively.
     *
     * @param mixed  $candidate The register or schema reference from the object
     * @param string $expected  The expected slug
     *
     * @return bool True when the reference matches the expected slug
     */
    private function matchesSlug(mixed $candidate, string $expected): bool
    {
        if ($candidate === $expected) {
            return true;
        }

        if (is_object($candidate) === false) {
            return false;
        }

        if (method_exists(object_or_class: $candidate, method: 'getSlug') === false) {
            return false;
        }

        return $candidate->getSlug() === $expected;

    }//end matchesSlug()

    /**
     * Determine whether `checkedOn` has changed between old and new object data.
     *
     * @param array<string, mixed> $newData New object data
     * @param array<string, mixed> $oldData Old object data (may be empty)
     *
     * @return bool True when checkedOn has changed or was added
     */
    private function hasCheckedOnChanged(array $newData, array $oldData): bool
    {
        $newCheckedOn = $newData['checkedOn'] ?? null;
        $oldCheckedOn = $oldData['checkedOn'] ?? null;

        return $newCheckedOn !== $oldCheckedOn && $newCheckedOn !== null;

    }//end hasCheckedOnChanged()

    /**
     * Read `configuration.grondslagen.autoRegenOnReview` from object data.
     *
     * Defaults to true when the field is absent.
     *
     * @param array<string, mixed> $data Object data
     *
     * @return bool True when auto-regen is enabled
     */
    private function isAutoRegenEnabled(array $data): bool
    {
        $config = $data['configuration'] ?? [];
        if (is_array($config) === false) {
            return true;
        }

        $grondslagen = $config['grondslagen'] ?? [];
        if (is_array($grondslagen) === false) {
            return true;
        }

        if (isset($grondslagen['autoRegenOnReview']) === false) {
            return true;
        }

        return $grondslagen['autoRegenOnReview'] === true;

    }//end isAutoRegenEnabled()

    /**
     * Log an error from the listener without throwing.
     *
     * @param \Throwable           $exception The caught exception
     * @param string               $context   Human-readable context label
     * @param array<string, mixed> $extra     Optional additional context
     *
     * @return void
     */
    private function logError(\Throwable $exception, string $context, array $extra=[]): void
    {
        $this->logger->error(
            message: 'DocuDesk: '.$context.' failed: '.$exception->getMessage(),
            context: array_merge(
                $extra,
                [
                    'exception' => $exception->getMessage(),
                    'file'      => $exception->getFile(),
                    'line'      => $exception->getLine(),
                ]
            )
        );

    }//end logError()
}//end class
