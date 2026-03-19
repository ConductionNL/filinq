<?php
/**
 * Metadata Service
 *
 * Service for extracting, enhancing, and managing document metadata.
 * This service works with documents stored in OpenRegister via ObjectService
 * and provides functionality to extract metadata from document objects and
 * enhance them with additional information.
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

use DateTime;
use Exception;
use RuntimeException;
use OCP\App\IAppManager;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for extracting and enhancing document metadata
 *
 * This service provides methods to extract metadata from documents,
 * enrich metadata with additional information, and standardize metadata
 * formats. It works with documents stored in OpenRegister via ObjectService.
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
     * @param LoggerInterface    $logger     Logger for error reporting
     * @param ContainerInterface $container  Container for dependency injection
     * @param IAppManager        $appManager App manager interface
     *
     * @return void
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ContainerInterface $container,
        private readonly IAppManager $appManager
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
     * Enhance metadata for a document object
     *
     * This method enriches existing metadata with additional information
     * such as language detection, topic classification, and keyword extraction.
     * Accepts object data directly rather than looking up via a service.
     *
     * @param array<string, mixed> $objectData The document object data from OpenRegister
     *
     * @return array<string, mixed> Enhanced metadata fields
     *
     * @throws Exception If metadata enhancement fails
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function enhanceMetadata(array $objectData): array
    {
        try {
            $metadata = [];

            // Get text content from object for analysis.
            $text = $objectData['content'] ?? $objectData['text'] ?? $objectData['description'] ?? '';

            if (is_string($text) === true && empty($text) === false) {
                // Detect language if not already present.
                if (isset($objectData['language']) === false || empty($objectData['language']) === true) {
                    $detected = $this->detectLanguage($text);
                    if ($detected !== null) {
                        $metadata['language'] = $detected;
                    }
                }

                // Extract keywords if not already present.
                if (isset($objectData['keywords']) === false || empty($objectData['keywords']) === true) {
                    $keywords = $this->extractKeywords($text);
                    if (empty($keywords) === false) {
                        $metadata['keywords'] = $keywords;
                    }
                }

                // Classify document topic if not already present.
                if (isset($objectData['topic']) === false || empty($objectData['topic']) === true) {
                    $topic = $this->classifyTopic($text);
                    if ($topic !== null) {
                        $metadata['topic'] = $topic;
                    }
                }
            }//end if

            // Standardize document type if present.
            if (isset($objectData['documentType']) === true && empty($objectData['documentType']) === false) {
                $metadata['documentType'] = $this->standardizeDocumentType($objectData['documentType']);
            }

            // Normalize dates in object data.
            $dateFields = ['created', 'modified', 'date', 'creationDate', 'modificationDate'];
            foreach ($dateFields as $field) {
                if (isset($objectData[$field]) === true && empty($objectData[$field]) === false) {
                    try {
                        $date = new DateTime($objectData[$field]);
                        $metadata[$field] = $date->format('c');
                    } catch (Exception $e) {
                        $this->logger->debug(
                            'Failed to normalize date field: '.$field,
                            [
                                'value'     => $objectData[$field],
                                'exception' => $e,
                            ]
                        );
                    }
                }
            }

            $this->logger->debug(
                'Metadata enhanced for document object',
                [
                    'enhancedFields' => array_keys($metadata),
                ]
            );

            return $metadata;
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to enhance metadata: '.$e->getMessage(),
                [
                    'exception' => $e,
                ]
            );
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
    public function saveEnrichedMetadata(string $objectId, string $register, string $schema, array $metadata): array
    {
        try {
            $objectService = $this->getObjectService();

            // Find the existing object.
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

            // Merge metadata into object data.
            $objectData = $object->getObject();
            $objectData = array_merge($objectData, $metadata);

            // Save back via ObjectService.
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
                [
                    'objectId'  => $objectId,
                    'exception' => $e,
                ]
            );
            throw new Exception('Failed to save enriched metadata: '.$e->getMessage(), 0, $e);
        }//end try

    }//end saveEnrichedMetadata()


    /**
     * Detect language from text content
     *
     * @param string $text Text content to analyze
     *
     * @return string|null Detected language code or null if detection fails
     */
    private function detectLanguage(string $text): ?string
    {
        $text = strtolower($text);

        // Common Dutch words.
        $dutchWords = ['de', 'het', 'een', 'en', 'van', 'is', 'zijn', 'op', 'voor', 'met'];
        $dutchCount = 0;
        foreach ($dutchWords as $word) {
            $dutchCount += substr_count($text, ' '.$word.' ');
        }

        // Common English words.
        $englishWords = ['the', 'be', 'to', 'of', 'and', 'a', 'in', 'that', 'have', 'it'];
        $englishCount = 0;
        foreach ($englishWords as $word) {
            $englishCount += substr_count($text, ' '.$word.' ');
        }

        if ($dutchCount > $englishCount && $dutchCount > 5) {
            return 'nl';
        }

        if ($englishCount > 5) {
            return 'en';
        }

        return null;

    }//end detectLanguage()


    /**
     * Extract keywords from text content
     *
     * @param string $text Text content to analyze
     *
     * @return array<string> Extracted keywords
     */
    private function extractKeywords(string $text): array
    {
        $words      = str_word_count(strtolower($text), 1);
        $wordCounts = array_count_values($words);

        // Filter out common stop words.
        $stopWords = [
            'the',
            'be',
            'to',
            'of',
            'and',
            'a',
            'in',
            'that',
            'have',
            'it',
            'de',
            'het',
            'een',
            'en',
            'van',
            'is',
            'zijn',
            'op',
            'voor',
            'met',
            'for',
            'not',
            'on',
            'with',
            'he',
            'as',
            'you',
            'do',
            'at',
        ];

        foreach ($stopWords as $stopWord) {
            unset($wordCounts[$stopWord]);
        }

        // Sort by frequency and return top keywords.
        arsort($wordCounts);
        $keywords = array_slice(array_keys($wordCounts), 0, 10);

        return $keywords;

    }//end extractKeywords()


    /**
     * Classify document topic based on text content
     *
     * @param string $text Text content to analyze
     *
     * @return string|null Classified topic or null if classification fails
     */
    private function classifyTopic(string $text): ?string
    {
        $text = strtolower($text);

        $topics = [
            'legal'     => ['contract', 'agreement', 'law', 'legal', 'court', 'judge'],
            'financial' => ['invoice', 'payment', 'budget', 'financial', 'account', 'money'],
            'medical'   => ['patient', 'diagnosis', 'treatment', 'medical', 'health', 'doctor'],
            'technical' => ['system', 'software', 'technical', 'code', 'development', 'api'],
        ];

        $scores = [];
        foreach ($topics as $topic => $keywords) {
            $score = 0;
            foreach ($keywords as $keyword) {
                $score += substr_count($text, $keyword);
            }

            $scores[$topic] = $score;
        }

        $maxScore = max($scores);
        if ($maxScore > 0) {
            $topic = array_search($maxScore, $scores);
            return $topic !== false ? (string) $topic : null;
        }

        return null;

    }//end classifyTopic()


    /**
     * Standardize document type classification
     *
     * @param string $documentType Document type to standardize
     *
     * @return string Standardized document type
     */
    private function standardizeDocumentType(string $documentType): string
    {
        $documentType = strtolower(trim($documentType));

        $typeMap = [
            'pdf'        => 'pdf',
            'word'       => 'word',
            'doc'        => 'word',
            'docx'       => 'word',
            'excel'      => 'spreadsheet',
            'xls'        => 'spreadsheet',
            'xlsx'       => 'spreadsheet',
            'powerpoint' => 'presentation',
            'ppt'        => 'presentation',
            'pptx'       => 'presentation',
            'text'       => 'text',
            'txt'        => 'text',
            'html'       => 'html',
            'image'      => 'image',
            'jpg'        => 'image',
            'jpeg'       => 'image',
            'png'        => 'image',
        ];

        return $typeMap[$documentType] ?? $documentType;

    }//end standardizeDocumentType()


}//end class
