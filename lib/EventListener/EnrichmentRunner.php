<?php

/**
 * Enrichment Runner
 *
 * Service for running metadata enrichment on OpenRegister objects.
 * Checks if enrichment is enabled and delegates to MetadataService.
 * Extracted from FilinqEventHandler to reduce class complexity.
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
 * @spec openspec/specs/metadata-enrichment/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\EventListener;

use OCA\Filinq\Kernel\Contracts\InboundClassificationServiceInterface;
use OCA\Filinq\Kernel\Contracts\SettingsServiceInterface;
use OCP\AppFramework\Db\DoesNotExistException;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Runs metadata enrichment for Filinq objects
 *
 * @category EventListener
 * @package  OCA\Filinq\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/metadata-enrichment/spec.md
 */
class EnrichmentRunner {

	/**
	 * Constructor.
	 *
	 * @param InboundClassificationServiceInterface|null $classification Suggests a type, correspondent and dossier for an
	 *                                                          inbound document; null leaves classification out.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/inbound-auto-classification/tasks.md#2-3
	 */
	public function __construct(
		private readonly ?InboundClassificationServiceInterface $classification = null,
	) {

	}//end __construct()

	/**
	 * A runner that also classifies, with the service resolved from the app
	 * container; when it cannot be resolved the runner enriches as before.
	 *
	 * @param ContainerInterface $container The app container.
	 *
	 * @return self The runner.
	 *
	 * @spec openspec/changes/inbound-auto-classification/tasks.md#2-3
	 */
	public function withClassificationFrom(ContainerInterface $container): self {
		try {
			$service = $container->get(InboundClassificationServiceInterface::class);
		} catch (Throwable) {
			return $this;
		}

		if (($service instanceof InboundClassificationServiceInterface) === false) {
			return $this;
		}

		return new self(classification: $service);

	}//end withClassificationFrom()

	/**
	 * Offer an object to classification. Suggestions only: the service writes
	 * a classificationResult record and never a field of the object. It has
	 * its own toggle, so it runs whether or not enrichment is on, and a
	 * failure here never costs the object its enrichment or its save.
	 *
	 * @param mixed           $object The OpenRegister object entity.
	 * @param LoggerInterface $logger The logger.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/inbound-auto-classification/tasks.md#2-3
	 */
	public function classify(mixed $object, LoggerInterface $logger): void {
		if ($this->classification === null) {
			return;
		}

		try {
			$outcome = $this->classification->classify(
				objectData: $object->getObject(),
				objectRef: [
					'id' => (string) $object->getUuid(),
					'register' => (string) $object->getRegister(),
					'schema' => (string) $object->getSchema(),
				]
			);
			if ($outcome['outcome'] === 'suggested') {
				$logger->info('Filinq: classification suggested', ['objectId' => $object->getUuid()]);
			}
		} catch (Throwable $e) {
			$logger->warning('Filinq: classification failed', ['objectId' => $object->getUuid(), 'exception' => $e->getMessage()]);
		}

	}//end classify()
	/**
	 * Check if enrichment is enabled based on settings
	 *
	 * Reads the three toggles ONLY. It must not call `getAllSettings()`: that
	 * assembles the admin-settings payload, including a walk of every register
	 * and every schema on the instance. This runs inside an unrelated app's
	 * object save, and that walk issued 1,471 `SchemaMapper::find()` calls per
	 * create — 96% of all schema reads during an OpenRegister create, measured
	 * 2026-07-29, on a code path whose entire job is to answer a boolean.
	 *
	 * @param SettingsServiceInterface $settingsService The settings service
	 *
	 * @return bool True if at least one enrichment feature is enabled
	 *
	 * @spec openspec/specs/metadata-enrichment/spec.md
	 */
	public function isEnrichmentEnabled(SettingsServiceInterface $settingsService): bool {
		$settings = $settingsService->getFeatureToggles();
		$enableLanguage = $settings['enable_language_detection'] ?? true;
		$enableKeywords = $settings['enable_keyword_extraction'] ?? true;
		$enableTopic = $settings['enable_topic_classification'] ?? true;

		return $enableLanguage === true || $enableKeywords === true || $enableTopic === true;
	}//end isEnrichmentEnabled()

	/**
	 * Enrich an object with metadata if enrichment is enabled
	 *
	 * @param mixed               $object     The OpenRegister object
	 * @param ObjectEventServices $services   The metadata, settings and logging services
	 * @param string              $logContext Context string for logging
	 *
	 * @return void
	 *
	 * @spec openspec/specs/metadata-enrichment/spec.md
	 */
	public function enrichObject(mixed $object, ObjectEventServices $services, string $logContext): void {
		$logger = $services->logger;
		$objectId = $object->getUuid();

		$logger->info(
			'Filinq: Processing ' . $logContext,
			['objectId' => $objectId, 'schemaId' => $object->getSchema(), 'registerId' => $object->getRegister()]
		);

		$this->classify(object: $object, logger: $logger);

		try {
			$metadata = $this->enrichAndSave(object: $object, services: $services);
			if ($metadata !== []) {
				$logger->info(
					'Filinq: Metadata enrichment completed for ' . $logContext,
					['objectId' => $objectId, 'enrichedFields' => array_keys($metadata)]
				);
			}
		} catch (DoesNotExistException $e) {
			$logger->debug(
				'Filinq: Skipped metadata enrichment for ' . $logContext . ', the object is not readable',
				['objectId' => $objectId, 'exception' => $e->getMessage()]
			);
		} catch (\Exception $e) {
			$logger->error(
				'Filinq: Failed to enrich metadata for ' . $logContext,
				['objectId' => $objectId, 'exception' => $e->getMessage()]
			);
		}//end try

	}//end enrichObject()

	/**
	 * Enhance an object's metadata and write it back as a system operation.
	 *
	 * @param mixed               $object   The OpenRegister object
	 * @param ObjectEventServices $services The metadata and settings services
	 *
	 * @return array<string, mixed> The metadata written; empty when enrichment is off or found nothing.
	 */
	private function enrichAndSave(mixed $object, ObjectEventServices $services): array {
		if ($this->isEnrichmentEnabled(settingsService: $services->settingsService) === false) {
			return [];
		}

		$metadata = $services->metadataService->enhanceMetadata($object->getObject());
		if (empty($metadata) === true) {
			return [];
		}

		// Event listeners run without a user session in webcron/background
		// contexts; persist as a trusted system operation so OpenRegister
		// RBAC does not deny the write as 'Anonymous'.
		$services->metadataService->saveEnrichedMetadataAsSystem(
			$object->getUuid(),
			(string)$object->getRegister(),
			(string)$object->getSchema(),
			$metadata
		);

		return $metadata;

	}//end enrichAndSave()
}//end class
