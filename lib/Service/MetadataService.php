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
     */
    public function enhanceMetadata(array $objectData): array
    {
        try {
            $metadata = [];
            $text     = $this->textExtractor->extractTextContent($objectData);

            if (empty($text) === false) {
                $metadata = $this->enhanceTextMetadata($text, $objectData);
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
     * @param string               $objectId The object UUID in OpenRegister
     * @param string               $register The register ID
     * @param string               $schema   The schema ID
     * @param array<string, mixed> $metadata The metadata to merge into the object
     *
     * @return array<string, mixed> Updated object data
     *
     * @throws Exception If saving fails
     */
    public function saveEnrichedMetadata(
        string $objectId,
        string $register,
        string $schema,
        array $metadata
    ): array {
        try {
            $objectService = $this->getObjectService();

            $object = $objectService->find(
                id: $objectId,
                register: $register,
                schema: $schema,
                _rbac: false,
                _multitenancy: false
            );

            if ($object === null) {
                throw new Exception('Object not found: '.$objectId);
            }

            $objectData  = array_merge($object->getObject(), $metadata);
            $savedObject = $objectService->saveObject(
                object: $objectData,
                register: $register,
                schema: $schema,
                _rbac: false,
                _multitenancy: false
            );

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

    }//end saveEnrichedMetadata()


}//end class
