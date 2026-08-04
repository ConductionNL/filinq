<?php
/**
 * Enrichment Runner
 *
 * Service for running metadata enrichment on OpenRegister objects.
 * Checks if enrichment is enabled and delegates to MetadataService.
 * Extracted from DocuDeskEventHandler to reduce class complexity.
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
 * @spec openspec/specs/metadata-enrichment/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\EventListener;

use OCA\DocuDesk\Service\MetadataService;
use OCA\DocuDesk\Service\SettingsService;
use Psr\Log\LoggerInterface;

/**
 * Runs metadata enrichment for DocuDesk objects
 *
 * @category EventListener
 * @package  OCA\DocuDesk\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/specs/metadata-enrichment/spec.md
 */
class EnrichmentRunner
{
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
     * @param SettingsService $settingsService The settings service
     *
     * @return bool True if at least one enrichment feature is enabled
     *
     * @spec openspec/specs/metadata-enrichment/spec.md
     */
    public function isEnrichmentEnabled(SettingsService $settingsService): bool
    {
        $settings       = $settingsService->getFeatureToggles();
        $enableLanguage = $settings['enable_language_detection'] ?? true;
        $enableKeywords = $settings['enable_keyword_extraction'] ?? true;
        $enableTopic    = $settings['enable_topic_classification'] ?? true;

        return $enableLanguage === true || $enableKeywords === true || $enableTopic === true;

    }//end isEnrichmentEnabled()

    /**
     * Enrich an object with metadata if enrichment is enabled
     *
     * @param mixed           $object          The OpenRegister object
     * @param MetadataService $metadataService The metadata service
     * @param SettingsService $settingsService The settings service
     * @param LoggerInterface $logger          The logger instance
     * @param string          $logContext      Context string for logging
     *
     * @return void
     *
     * @spec openspec/specs/metadata-enrichment/spec.md
     */
    public function enrichObject(
        mixed $object,
        MetadataService $metadataService,
        SettingsService $settingsService,
        LoggerInterface $logger,
        string $logContext
    ): void {
        $objectId = $object->getUuid();

        $logger->info(
            'DocuDesk: Processing '.$logContext,
            [
                'objectId'   => $objectId,
                'schemaId'   => $object->getSchema(),
                'registerId' => $object->getRegister(),
            ]
        );

        try {
            if ($this->isEnrichmentEnabled(settingsService: $settingsService) === false) {
                return;
            }

            $metadata = $metadataService->enhanceMetadata($object->getObject());

            if (empty($metadata) === true) {
                return;
            }

            // Event listeners run without a user session in webcron/background
            // contexts; persist as a trusted system operation so OpenRegister
            // RBAC does not deny the write as 'Anonymous'.
            $metadataService->saveEnrichedMetadataAsSystem(
                $objectId,
                (string) $object->getRegister(),
                (string) $object->getSchema(),
                $metadata
            );

            $logger->info(
                'DocuDesk: Metadata enrichment completed for '.$logContext,
                [
                    'objectId'       => $objectId,
                    'enrichedFields' => array_keys($metadata),
                ]
            );
        } catch (\Exception $e) {
            $logger->error(
                'DocuDesk: Failed to enrich metadata for '.$logContext,
                [
                    'objectId'  => $objectId,
                    'exception' => $e->getMessage(),
                ]
            );
        }//end try

    }//end enrichObject()
}//end class
