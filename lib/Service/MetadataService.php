<?php
/**
 * Metadata Service
 *
 * Service for extracting, enhancing, and managing document metadata.
 * This service works with documents stored in OpenRegister and provides
 * functionality to extract metadata from document objects and enhance
 * them with additional information.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version  GIT: <git_id>
 * @link     https://www.DocuDesk.app
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use Exception;
use OCA\DocuDesk\Service\OpenRegisterService;
use OCP\Files\Node;
use Psr\Log\LoggerInterface;

/**
 * Service for extracting and enhancing document metadata
 *
 * This service provides methods to extract metadata from documents,
 * enrich metadata with additional information, and standardize metadata
 * formats. It works with documents stored in OpenRegister.
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
     * Logger instance for error reporting
     *
     * @var LoggerInterface
     */
    private readonly LoggerInterface $logger;

    /**
     * OpenRegister service for document operations
     *
     * @var OpenRegisterService
     */
    private readonly OpenRegisterService $openRegisterService;

    /**
     * Constructor for MetadataService
     *
     * @param LoggerInterface      $logger             Logger for error reporting
     * @param OpenRegisterService $openRegisterService Service for OpenRegister operations
     *
     * @return void
     */
    public function __construct(
        LoggerInterface $logger,
        OpenRegisterService $openRegisterService
    ) {
        $this->logger             = $logger;
        $this->openRegisterService = $openRegisterService;

    }//end __construct()

    /**
     * Extract metadata from a document stored in OpenRegister
     *
     * This method retrieves a document from OpenRegister and extracts
     * metadata from it. OpenRegister handles text extraction, so this
     * method focuses on extracting structured metadata.
     *
     * @param string $documentId The document ID in OpenRegister
     *
     * @return array<string, mixed> Extracted metadata
     *
     * @throws Exception If metadata extraction fails
     */
    public function extractMetadata(string $documentId): array
    {
        try {
            $document = $this->openRegisterService->getDocument($documentId);
            if ($document === null) {
                throw new Exception('Document not found: '.$documentId);
            }

            // Extract basic metadata from document object.
            $metadata = [
                'documentId'    => $document['id'] ?? null,
                'title'         => $document['title'] ?? $document['name'] ?? null,
                'author'        => $document['author'] ?? null,
                'created'       => $document['created'] ?? null,
                'modified'      => $document['modified'] ?? null,
                'documentType'  => $document['documentType'] ?? $document['type'] ?? null,
                'mimeType'      => $document['mimeType'] ?? null,
                'fileSize'      => $document['fileSize'] ?? null,
                'fileName'      => $document['fileName'] ?? null,
                'filePath'      => $document['filePath'] ?? null,
            ];

            // Extract additional metadata if available.
            if (isset($document['metadata']) === true && is_array($document['metadata']) === true) {
                $metadata = array_merge($metadata, $document['metadata']);
            }

            // Extract language if available.
            if (isset($document['language']) === true) {
                $metadata['language'] = $document['language'];
            }

            // Extract keywords if available.
            if (isset($document['keywords']) === true) {
                $metadata['keywords'] = is_array($document['keywords']) === true
                    ? $document['keywords']
                    : explode(',', $document['keywords']);
            }

            $this->logger->debug(
                'Metadata extracted from document',
                [
                    'documentId' => $documentId,
                    'metadata'   => $metadata,
                ]
            );

            return $metadata;
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to extract metadata: '.$e->getMessage(),
                [
                    'documentId' => $documentId,
                    'exception'  => $e,
                ]
            );
            throw new Exception('Failed to extract metadata: '.$e->getMessage(), 0, $e);
        }

    }//end extractMetadata()

    /**
     * Enhance metadata with additional information
     *
     * This method enriches existing metadata with additional information
     * such as language detection, topic classification, and keyword extraction.
     *
     * @param string               $documentId The document ID
     * @param array<string, mixed> $metadata   Existing metadata to enhance
     *
     * @return array<string, mixed> Enhanced metadata
     *
     * @throws Exception If metadata enhancement fails
     */
    public function enhanceMetadata(string $documentId, array $metadata): array
    {
        try {
            // Get document text for analysis.
            $text = $this->openRegisterService->getDocumentText($documentId);

            // Enhance with language detection if text is available.
            if ($text !== null && empty($text) === false) {
                if (isset($metadata['language']) === false || empty($metadata['language']) === true) {
                    $metadata['language'] = $this->detectLanguage($text);
                }

                // Extract keywords if not already present.
                if (isset($metadata['keywords']) === false || empty($metadata['keywords']) === true) {
                    $metadata['keywords'] = $this->extractKeywords($text);
                }

                // Classify document topic if not already present.
                if (isset($metadata['topic']) === false || empty($metadata['topic']) === true) {
                    $metadata['topic'] = $this->classifyTopic($text);
                }
            }

            // Normalize dates.
            $metadata = $this->normalizeDates($metadata);

            // Standardize document type.
            if (isset($metadata['documentType']) === true) {
                $metadata['documentType'] = $this->standardizeDocumentType($metadata['documentType']);
            }

            $this->logger->debug(
                'Metadata enhanced for document',
                [
                    'documentId' => $documentId,
                    'metadata'   => $metadata,
                ]
            );

            return $metadata;
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to enhance metadata: '.$e->getMessage(),
                [
                    'documentId' => $documentId,
                    'exception'  => $e,
                ]
            );
            throw new Exception('Failed to enhance metadata: '.$e->getMessage(), 0, $e);
        }

    }//end enhanceMetadata()

    /**
     * Update document metadata in OpenRegister
     *
     * @param string               $documentId The document ID
     * @param array<string, mixed> $metadata   Metadata to update
     *
     * @return array<string, mixed> Updated document object
     *
     * @throws Exception If metadata update fails
     */
    public function updateMetadata(string $documentId, array $metadata): array
    {
        try {
            $document = $this->openRegisterService->getDocument($documentId);
            if ($document === null) {
                throw new Exception('Document not found: '.$documentId);
            }

            // Merge metadata into document.
            $document = array_merge($document, $metadata);

            // Update document in OpenRegister.
            $updatedDocument = $this->openRegisterService->updateDocument($documentId, $document);

            $this->logger->debug(
                'Metadata updated for document',
                [
                    'documentId' => $documentId,
                ]
            );

            return $updatedDocument;
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to update metadata: '.$e->getMessage(),
                [
                    'documentId' => $documentId,
                    'exception'  => $e,
                ]
            );
            throw new Exception('Failed to update metadata: '.$e->getMessage(), 0, $e);
        }

    }//end updateMetadata()

    /**
     * Detect language from text content
     *
     * @param string $text Text content to analyze
     *
     * @return string|null Detected language code or null if detection fails
     */
    private function detectLanguage(string $text): ?string
    {
        // Simple language detection based on common words.
        // In production, this could use a proper language detection library.
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
        } else if ($englishCount > 5) {
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
        // Simple keyword extraction based on word frequency.
        // In production, this could use NLP libraries for better extraction.
        $words = str_word_count(strtolower($text), 1);
        $wordCounts = array_count_values($words);

        // Filter out common stop words.
        $stopWords = [
            'the', 'be', 'to', 'of', 'and', 'a', 'in', 'that', 'have', 'it',
            'de', 'het', 'een', 'en', 'van', 'is', 'zijn', 'op', 'voor', 'met',
            'for', 'not', 'on', 'with', 'he', 'as', 'you', 'do', 'at',
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
        // Simple topic classification based on keyword matching.
        // In production, this could use ML models for better classification.
        $text = strtolower($text);

        $topics = [
            'legal'      => ['contract', 'agreement', 'law', 'legal', 'court', 'judge'],
            'financial'  => ['invoice', 'payment', 'budget', 'financial', 'account', 'money'],
            'medical'    => ['patient', 'diagnosis', 'treatment', 'medical', 'health', 'doctor'],
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
            return array_search($maxScore, $scores);
        }

        return null;

    }//end classifyTopic()

    /**
     * Normalize date formats in metadata
     *
     * @param array<string, mixed> $metadata Metadata array
     *
     * @return array<string, mixed> Metadata with normalized dates
     */
    private function normalizeDates(array $metadata): array
    {
        $dateFields = ['created', 'modified', 'date', 'creationDate', 'modificationDate'];

        foreach ($dateFields as $field) {
            if (isset($metadata[$field]) === true && empty($metadata[$field]) === false) {
                try {
                    $date = new \DateTime($metadata[$field]);
                    $metadata[$field] = $date->format('c');
                } catch (Exception $e) {
                    // Keep original value if date parsing fails.
                    $this->logger->debug(
                        'Failed to normalize date field: '.$field,
                        [
                            'value'     => $metadata[$field],
                            'exception' => $e,
                        ]
                    );
                }
            }
        }

        return $metadata;

    }//end normalizeDates()

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

        // Map common variations to standard types.
        $typeMap = [
            'pdf'              => 'pdf',
            'word'              => 'word',
            'doc'               => 'word',
            'docx'              => 'word',
            'excel'             => 'spreadsheet',
            'xls'               => 'spreadsheet',
            'xlsx'              => 'spreadsheet',
            'powerpoint'        => 'presentation',
            'ppt'               => 'presentation',
            'pptx'              => 'presentation',
            'text'              => 'text',
            'txt'               => 'text',
            'html'              => 'html',
            'image'             => 'image',
            'jpg'               => 'image',
            'jpeg'              => 'image',
            'png'               => 'image',
        ];

        return $typeMap[$documentType] ?? $documentType;

    }//end standardizeDocumentType()

}//end class


