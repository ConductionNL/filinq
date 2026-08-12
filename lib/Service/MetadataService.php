<?php
/**
 * Metadata Service
 *
 * Service for enhancing and managing document metadata.
 * This service works with documents stored in OpenRegister via ObjectService
 * and provides functionality to enrich metadata with text analysis results.
 * Delegates text extraction and date normalization to DocumentTextExtractor.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
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

namespace OCA\DocuDesk\Service;

use Exception;
use RuntimeException;
use OCP\App\IAppManager;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for enhancing document metadata via text analysis
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/specs/metadata-enrichment/spec.md
 */
class MetadataService
{
    /**
     * Constructor for MetadataService
     *
     * @param LoggerInterface       $logger              Logger for error reporting
     * @param ContainerInterface    $container           Container for dependency injection
     * @param IAppManager           $appManager          App manager interface
     * @param TextAnalysisService   $textAnalysisService Text analysis service
     * @param DocumentTextExtractor $textExtractor       Text extraction and date normalization
     *
     * @return void
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ContainerInterface $container,
        private readonly IAppManager $appManager,
        private readonly TextAnalysisService $textAnalysisService,
        private readonly DocumentTextExtractor $textExtractor
    ) {

    }//end __construct()

    /**
     * Get the ObjectService from OpenRegister
     *
     * @return \OCA\OpenRegister\Service\ObjectService The ObjectService instance
     *
     * @throws \RuntimeException If OpenRegister is not available
     */
    private function getObjectService(): \OCA\OpenRegister\Service\ObjectService
    {
        if (in_array('openregister', $this->appManager->getInstalledApps(), true) === true) {
            return $this->container->get('OCA\OpenRegister\Service\ObjectService');
        }

        throw new RuntimeException('OpenRegister service is not available.');

    }//end getObjectService()

    /**
     * Enhance text-based metadata (language, keywords, topic)
     *
     * @param string               $text       The text content to analyze
     * @param array<string, mixed> $objectData The document object data
     *
     * @return array<string, mixed> Enhanced metadata from text analysis
     *
     * @spec openspec/specs/metadata-enrichment/spec.md
     */
    private function enhanceTextMetadata(string $text, array $objectData): array
    {
        $metadata = [];

        if (empty($objectData['language']) === true) {
            $detected = $this->textAnalysisService->detectLanguage($text);
            if ($detected !== null) {
                $metadata['language'] = $detected;
            }
        }

        if (empty($objectData['keywords']) === true) {
            $keywords = $this->textAnalysisService->extractKeywords($text);
            if (empty($keywords) === false) {
                $metadata['keywords'] = $keywords;
            }
        }

        if (empty($objectData['topic']) === true) {
            $topic = $this->textAnalysisService->classifyTopic($text);
            if ($topic !== null) {
                $metadata['topic'] = $topic;
            }
        }

        return $metadata;

    }//end enhanceTextMetadata()

    /**
     * Enhance metadata for a document object
     *
     * @param array<string, mixed> $objectData The document object data from OpenRegister
     *
     * @return array<string, mixed> Enhanced metadata fields
     *
     * @throws Exception If metadata enhancement fails
     *
     * @spec openspec/specs/metadata-enrichment/spec.md
     */
    public function enhanceMetadata(array $objectData): array
    {
        try {
            $metadata = [];
            $text     = $this->textExtractor->extractTextContent($objectData);

            if (empty($text) === false) {
                $metadata = $this->enhanceTextMetadata(text: $text, objectData: $objectData);
            }

            if (empty($objectData['documentType']) === false) {
                $metadata['documentType'] = $this->textAnalysisService->standardizeDocumentType(
                    $objectData['documentType']
                );
            }

            $metadata = array_merge($metadata, $this->textExtractor->normalizeDateFields($objectData));

            $this->logger->debug(
                'Metadata enhanced for document object',
                ['enhancedFields' => array_keys($metadata)]
            );

            return $metadata;
        } catch (Exception $e) {
            $this->logger->error('Failed to enhance metadata: '.$e->getMessage(), ['exception' => $e]);
            throw new Exception('Failed to enhance metadata: '.$e->getMessage(), 0, $e);
        }//end try

    }//end enhanceMetadata()

    /**
     * Enrich a document object with metadata and save it back via ObjectService
     *
     * The read + write run under the requesting user's OpenRegister RBAC. This
     * is the variant every controller / user-request caller must use. Callers
     * without a user session (event listeners reacting to webcron-created
     * objects) must use {@see saveEnrichedMetadataAsSystem()} instead.
     *
     * @param string               $objectId The object UUID in OpenRegister
     * @param string               $register The register ID
     * @param string               $schema   The schema ID
     * @param array<string, mixed> $metadata The metadata to merge into the object
     *
     * @return array<string, mixed> Updated object data
     *
     * @throws Exception If saving fails
     *
     * @spec openspec/specs/metadata-enrichment/spec.md
     * @spec exclude system-context adoption
     */
    public function saveEnrichedMetadata(
        string $objectId,
        string $register,
        string $schema,
        array $metadata
    ): array {
        $persist = function () use ($objectId, $register, $schema, $metadata) {
            return $this->persistEnrichedMetadata(
                objectService: $this->getObjectService(),
                objectId: $objectId,
                register: $register,
                schema: $schema,
                metadata: $metadata
            );
        };

        return $this->runEnrichedMetadataPersist(
            persist: $persist,
            objectId: $objectId,
            metadata: $metadata
        );

    }//end saveEnrichedMetadata()

    /**
     * Enrich a document object with metadata as a trusted system operation.
     *
     * The read + write run inside OpenRegister's `ObjectService::runAsSystem()`
     * scoped elevation. This is for app-initiated maintenance without a user
     * session (event listeners reacting to webcron-created objects), where RBAC
     * would otherwise deny every write as 'Anonymous'. On released OpenRegister
     * versions without `runAsSystem()` the call falls back to the direct
     * (non-elevated) path.
     *
     * @param string               $objectId The object UUID in OpenRegister
     * @param string               $register The register ID
     * @param string               $schema   The schema ID
     * @param array<string, mixed> $metadata The metadata to merge into the object
     *
     * @return array<string, mixed> Updated object data
     *
     * @throws Exception If saving fails
     *
     * @spec openspec/specs/metadata-enrichment/spec.md
     * @spec exclude system-context adoption
     */
    public function saveEnrichedMetadataAsSystem(
        string $objectId,
        string $register,
        string $schema,
        array $metadata
    ): array {
        $persist = function () use ($objectId, $register, $schema, $metadata) {
            $objectService = $this->getObjectService();

            $direct = fn() => $this->persistEnrichedMetadata(
                objectService: $objectService,
                objectId: $objectId,
                register: $register,
                schema: $schema,
                metadata: $metadata
            );

            if (method_exists($objectService, 'runAsSystem') === true) {
                return $objectService->runAsSystem($direct);
            }

            return $direct();
        };

        return $this->runEnrichedMetadataPersist(
            persist: $persist,
            objectId: $objectId,
            metadata: $metadata
        );

    }//end saveEnrichedMetadataAsSystem()

    /**
     * Read the object, merge the metadata into it and save it back.
     *
     * Security (C2): `_rbac:false` / `_multitenancy:false` are deliberately not
     * passed, and system-context callers elevate via `runAsSystem()` instead of
     * disabling the guards wholesale. That much is real and worth keeping.
     *
     * ⚠️ But read what each half actually buys, because this comment used to
     * claim more than the code delivers:
     *
     *  - The MULTITENANCY half is live. Organisation scoping still applies.
     *  - The per-object RBAC half currently enforces NOTHING for this app.
     *    OpenRegister resolves authorization through a register/schema cascade
     *    and treats "configured nowhere" as OPEN. Every schema in
     *    `lib/Settings/docudesk_register.json` declares `"authorization": null`
     *    except `publicationProhibition`, and no register declares the key at
     *    all — so for the schemas this method writes, OR permits the read and
     *    the write regardless of who is asking.
     *
     * So this is NOT a per-user boundary today. Do not treat the absence of the
     * bypass flags as an ownership check, and do not delete a caller-side
     * ownership guard on the strength of it. Making the RBAC half real means
     * declaring `authorization` on the schemas (ConductionNL/openregister#2011),
     * not changing anything here.
     *
     * @param \OCA\OpenRegister\Service\ObjectService $objectService The resolved OpenRegister object service
     * @param string                                  $objectId      The object UUID in OpenRegister
     * @param string                                  $register      The register ID
     * @param string                                  $schema        The schema ID
     * @param array<string, mixed>                    $metadata      The metadata to merge into the object
     *
     * @return mixed The saved ObjectEntity
     *
     * @throws Exception If the object cannot be found.
     */
    private function persistEnrichedMetadata(
        \OCA\OpenRegister\Service\ObjectService $objectService,
        string $objectId,
        string $register,
        string $schema,
        array $metadata
    ): mixed {
        $object = $objectService->find(
            id: $objectId,
            register: $register,
            schema: $schema
        );

        if ($object === null) {
            throw new Exception('Object not found: '.$objectId);
        }

        $objectData = array_merge($object->getObject(), $metadata);

        return $objectService->saveObject(
            object: $objectData,
            register: $register,
            schema: $schema
        );

    }//end persistEnrichedMetadata()

    /**
     * Execute a metadata persist closure with uniform logging and error wrapping.
     *
     * @param \Closure             $persist  The persist closure to run
     * @param string               $objectId The object UUID, for log context
     * @param array<string, mixed> $metadata The metadata that was merged, for log context
     *
     * @return array<string, mixed> Updated object data
     *
     * @throws Exception If saving fails
     */
    private function runEnrichedMetadataPersist(
        \Closure $persist,
        string $objectId,
        array $metadata
    ): array {
        try {
            $savedObject = $persist();

            $this->logger->info(
                'Enriched metadata saved for object',
                [
                    'objectId'       => $objectId,
                    'enrichedFields' => array_keys($metadata),
                ]
            );

            return $savedObject->getObject();
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to save enriched metadata: '.$e->getMessage(),
                ['objectId' => $objectId, 'exception' => $e]
            );
            throw new Exception('Failed to save enriched metadata: '.$e->getMessage(), 0, $e);
        }//end try

    }//end runEnrichedMetadataPersist()
}//end class
