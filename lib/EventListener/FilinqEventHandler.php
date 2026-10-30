<?php

/**
 * Filinq Event Handler
 *
 * Service for handling OpenRegister object CRUD events for Filinq.
 * Delegates enrichment logic to EnrichmentRunner.
 * Extracted from FilinqEventListener to reduce class complexity.
 *
 * @category  EventListener
 * @package   OCA\Filinq\EventListener
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/metadata-enrichment/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\EventListener;

use OCA\Filinq\Kernel\Contracts\LegalBasesSummaryServiceInterface;
use OCA\OpenRegister\Event\ObjectCreatedEvent;
use OCA\OpenRegister\Event\ObjectDeletedEvent;
use OCA\OpenRegister\Event\ObjectUpdatedEvent;
use OCP\EventDispatcher\Event;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Handles OpenRegister object events for Filinq metadata enrichment
 *
 * @category EventListener
 * @package  OCA\Filinq\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class FilinqEventHandler {
	/**
	 * The enrichment runner.
	 *
	 * @var EnrichmentRunner
	 */
	private readonly EnrichmentRunner $enrichmentRunner;

	/**
	 * Routes changed policy records to the retroactive policy layer.
	 *
	 * @var PolicyChangeRouter
	 */
	private readonly PolicyChangeRouter $policyRouter;

	/**
	 * Constructor for FilinqEventHandler
	 *
	 * @param ContainerInterface $container App container the legal-bases summary service is resolved from.
	 * @param EnrichmentRunner|null $enrichmentRunner The enrichment runner. Defaults to a
	 *                                                fresh instance that also classifies
	 *                                                inbound documents; injectable so
	 *                                                tests can substitute a double.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/inbound-auto-classification/tasks.md#2-3
	 */
	public function __construct(
		private readonly ContainerInterface $container,
		?EnrichmentRunner $enrichmentRunner = null,
	) {
		$this->enrichmentRunner = $enrichmentRunner ?? (new EnrichmentRunner())->withClassificationFrom(container: $container);
		$this->policyRouter = new PolicyChangeRouter();

	}//end __construct()

	/**
	 * Hand an OpenRegister object event to the handler for its type.
	 *
	 * @param Event $event The event to dispatch
	 * @param ObjectEventServices $services The services the event is handled with
	 *
	 * @return void
	 *
	 * @psalm-suppress TypeDoesNotContainType OpenRegister is an optional dep; event classes may not be loaded.
	 *
	 * @spec openspec/specs/metadata-enrichment/spec.md
	 */
	public function dispatch(Event $event, ObjectEventServices $services): void {
		if ($event instanceof ObjectCreatedEvent) {
			$this->handleObjectCreated(event: $event, services: $services);
			return;
		}

		if ($event instanceof ObjectUpdatedEvent) {
			$this->handleObjectUpdated(event: $event, services: $services);
			return;
		}

		if ($event instanceof ObjectDeletedEvent) {
			$this->handleObjectDeleted(event: $event, services: $services);
			return;
		}

		$services->logger->debug(
			'Filinq: Ignoring unhandled event type',
			[
				'eventType' => get_class($event),
			]
		);

	}//end dispatch()

	/**
	 * Handles object creation events
	 *
	 * @param ObjectCreatedEvent $event The creation event
	 * @param ObjectEventServices $services The metadata, settings, logging and retroactive-policy services
	 *
	 * @return void
	 *
	 * @spec openspec/specs/metadata-enrichment/spec.md
	 */
	public function handleObjectCreated(ObjectCreatedEvent $event, ObjectEventServices $services): void {
		$logger = $services->logger;
		$object = $event->getObject();
		if ($object === null) {
			$logger->warning('Filinq: ObjectCreatedEvent received with null object');
			return;
		}

		$this->policyRouter->route(
			change: new ObjectChange(objectData: $object->getObject(), reason: 'created'),
			services: $services
		);

		$this->enrichmentRunner->enrichObject(
			$object,
			$services->metadataService,
			$services->settingsService,
			$logger,
			'new object'
		);

	}//end handleObjectCreated()

	/**
	 * Handles object update events
	 *
	 * @param ObjectUpdatedEvent $event The update event
	 * @param ObjectEventServices $services The metadata, settings, logging and retroactive-policy services
	 *
	 * @return void
	 *
	 * @spec openspec/specs/metadata-enrichment/spec.md
	 */
	public function handleObjectUpdated(ObjectUpdatedEvent $event, ObjectEventServices $services): void {
		$logger = $services->logger;
		$object = $event->getNewObject();
		$oldObject = $event->getOldObject();

		if ($object === null) {
			$logger->warning('Filinq: ObjectUpdatedEvent received with null object');
			return;
		}

		$objectData = $object->getObject();
		$oldObjectData = [];
		if ($oldObject !== null) {
			$oldObjectData = $oldObject->getObject();
		}

		$change = new ObjectChange(objectData: $objectData, reason: 'updated', oldObjectData: $oldObjectData);
		$this->policyRouter->route(change: $change, services: $services);

		// Wave 4a: auto-regenerate the per-dossier grondslagen summary when a
		// dossier's `checkedOn` review timestamp changes (and the dossier
		// opts in via `configuration.grondslagen.autoRegenOnReview`, default
		// true). Runs synchronously inside the event flow; failures are
		// logged but do NOT roll back the dossier update.
		$this->maybeRegenerateGrondslagenSummary(change: $change, logger: $logger);

		if ($this->hasContentChanged(objectData: $objectData, oldObjectData: $oldObjectData) === false) {
			// An intake document gets its OCR text (contentText) by an update,
			// which is not an enrichment content field: offer it to
			// classification anyway. A file with a suggestion is skipped there.
			$this->enrichmentRunner->classify(object: $object, logger: $logger);
			$logger->debug(
				'Filinq: No content change detected, skipping re-enrichment',
				['objectId' => $object->getUuid()]
			);
			return;
		}

		$this->enrichmentRunner->enrichObject(
			$object,
			$services->metadataService,
			$services->settingsService,
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
	 * @param ObjectChange $change The new and previous dossier object data (the previous
	 *                             is empty when the event lacks a previous state).
	 * @param LoggerInterface $logger Structured logger.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/metadata-enrichment/spec.md
	 */
	private function maybeRegenerateGrondslagenSummary(ObjectChange $change, LoggerInterface $logger): void {
		$objectData = $change->objectData;
		if ($this->shouldRegenerateGrondslagenSummary(
			objectData: $objectData,
			oldObjectData: $change->oldObjectData
		) === false
		) {
			return;
		}

		$self = ($objectData['@self'] ?? []);
		$dossierUuid = (string)($self['id'] ?? $self['uuid'] ?? $objectData['id'] ?? $objectData['uuid'] ?? '');
		if ($dossierUuid === '') {
			$logger->warning('Filinq: dossier checkedOn change detected but UUID missing; skipping regen');
			return;
		}

		try {
			$service = $this->container->get(LegalBasesSummaryServiceInterface::class);
			$service->renderDossierSummary(dossierUuid: $dossierUuid);
		} catch (\Throwable $e) {
			$logger->warning(
				'Filinq: grondslagen-summary auto-regen failed',
				[
					'dossierUuid' => $dossierUuid,
					'error' => $e->getMessage(),
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
	 * @param array<string, mixed> $objectData The new dossier object data.
	 * @param array<string, mixed> $oldObjectData The previous dossier object data.
	 *
	 * @return bool True when the summary should be regenerated.
	 *
	 * @spec openspec/specs/metadata-enrichment/spec.md
	 */
	private function shouldRegenerateGrondslagenSummary(array $objectData, array $oldObjectData): bool {
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
	private function isGrondslagenAutoRegenEnabled(array $objectData): bool {
		$configuration = ($objectData['configuration'] ?? []);
		$grondslagen = [];
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
	private function looksLikeDossier(array $objectData): bool {
		return array_key_exists('name', $objectData) === true
			&& array_key_exists('bases', $objectData) === true
			&& (array_key_exists('checkedOn', $objectData) === true
				|| array_key_exists('configuration', $objectData) === true);

	}//end looksLikeDossier()

	/**
	 * Handles object deletion events
	 *
	 * @param ObjectDeletedEvent $event The deletion event
	 * @param ObjectEventServices $services The logging and retroactive-policy services
	 *
	 * @return void
	 */
	public function handleObjectDeleted(ObjectDeletedEvent $event, ObjectEventServices $services): void {
		$logger = $services->logger;
		$object = $event->getObject();
		if ($object === null) {
			$logger->warning('Filinq: ObjectDeletedEvent received with null object');
			return;
		}

		$this->policyRouter->route(
			change: new ObjectChange(objectData: $object->getObject(), reason: 'deleted'),
			services: $services
		);

		$logger->info(
			'Filinq: Object deleted',
			[
				'objectId' => $object->getUuid(),
				'schemaId' => $object->getSchema(),
				'registerId' => $object->getRegister(),
			]
		);

	}//end handleObjectDeleted()

	/**
	 * Check if content fields have changed between old and new object data
	 *
	 * @param array<string, mixed> $objectData The new object data
	 * @param array<string, mixed> $oldObjectData The old object data
	 *
	 * @return bool True if content has changed
	 *
	 * @spec openspec/specs/metadata-enrichment/spec.md
	 */
	private function hasContentChanged(array $objectData, array $oldObjectData): bool {
		$contentFields = ['content', 'text', 'description', 'title'];
		foreach ($contentFields as $field) {
			if (($objectData[$field] ?? '') !== ($oldObjectData[$field] ?? '')) {
				return true;
			}
		}

		return false;
	}//end hasContentChanged()
}//end class
