<?php
/**
 * OpenRegister Service
 *
 * Service for interacting with OpenRegister API for document storage and retrieval.
 * This service provides a wrapper around OpenRegister's ObjectService to handle
 * document objects stored in OpenRegister.
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
use OCA\OpenRegister\Service\ObjectService;
use OCP\IAppConfig;
use Psr\Log\LoggerInterface;

/**
 * Service for interacting with OpenRegister for document storage and retrieval
 *
 * This service provides methods to store, retrieve, and manage documents
 * using OpenRegister as the storage backend. It handles register and schema
 * configuration for document objects.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class OpenRegisterService
{
    /**
     * Default document register type
     *
     * @var string
     */
    private const DEFAULT_REGISTER_TYPE = 'document';

    /**
     * Default document schema type
     *
     * @var string
     */
    private const DEFAULT_SCHEMA_TYPE = 'document';

    /**
     * Logger instance for error reporting
     *
     * @var LoggerInterface
     */
    private readonly LoggerInterface $logger;

    /**
     * Object service from OpenRegister
     *
     * @var ObjectService
     */
    private readonly ObjectService $objectService;

    /**
     * App configuration service
     *
     * @var IAppConfig
     */
    private readonly IAppConfig $appConfig;

    /**
     * Document register type
     *
     * @var string
     */
    private string $registerType;

    /**
     * Document schema type
     *
     * @var string
     */
    private string $schemaType;

    /**
     * Constructor for OpenRegisterService
     *
     * @param LoggerInterface $logger        Logger for error reporting
     * @param ObjectService   $objectService ObjectService from OpenRegister
     * @param IAppConfig      $appConfig     App configuration service
     *
     * @return void
     */
    public function __construct(
        LoggerInterface $logger,
        ObjectService $objectService,
        IAppConfig $appConfig
    ) {
        $this->logger        = $logger;
        $this->objectService = $objectService;
        $this->appConfig     = $appConfig;

        // Get register and schema configuration from app config.
        $this->registerType = $this->appConfig->getValueString(
            'docudesk',
            'document_register',
            self::DEFAULT_REGISTER_TYPE
        );
        $this->schemaType = $this->appConfig->getValueString(
            'docudesk',
            'document_schema',
            self::DEFAULT_SCHEMA_TYPE
        );

        // Configure ObjectService with document register and schema.
        $this->objectService->setRegister($this->registerType);
        $this->objectService->setSchema($this->schemaType);

    }//end __construct()

    /**
     * Create a document object in OpenRegister
     *
     * @param array<string, mixed> $documentData Document data to store
     * @param string|null          $uuid         Optional UUID for the document
     *
     * @return array<string, mixed> The created document object
     *
     * @throws Exception If document creation fails
     */
    public function createDocument(array $documentData, ?string $uuid=null): array
    {
        try {
            $object = $this->objectService->saveObject(
                object: $documentData,
                uuid: $uuid
            );

            $document = $object->jsonSerialize();
            $this->logger->debug(
                'Document created in OpenRegister',
                [
                    'documentId' => $document['id'] ?? null,
                    'register'   => $this->registerType,
                    'schema'     => $this->schemaType,
                ]
            );

            return $document;
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to create document in OpenRegister: '.$e->getMessage(),
                [
                    'exception' => $e,
                    'register'  => $this->registerType,
                    'schema'    => $this->schemaType,
                ]
            );
            throw new Exception('Failed to create document: '.$e->getMessage(), 0, $e);
        }

    }//end createDocument()

    /**
     * Get a document by ID from OpenRegister
     *
     * @param string $documentId The document ID
     *
     * @return array<string, mixed>|null The document object or null if not found
     */
    public function getDocument(string $documentId): ?array
    {
        try {
            $object = $this->objectService->getObject($this->schemaType, $documentId);
            if ($object === null) {
                return null;
            }

            return $object;
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to get document from OpenRegister: '.$e->getMessage(),
                [
                    'documentId' => $documentId,
                    'exception'  => $e,
                ]
            );
            return null;
        }

    }//end getDocument()

    /**
     * Update a document in OpenRegister
     *
     * @param string               $documentId   The document ID
     * @param array<string, mixed> $documentData Updated document data
     *
     * @return array<string, mixed> The updated document object
     *
     * @throws Exception If document update fails
     */
    public function updateDocument(string $documentId, array $documentData): array
    {
        try {
            $object = $this->objectService->saveObject(
                object: $documentData,
                uuid: $documentId
            );

            $document = $object->jsonSerialize();
            $this->logger->debug(
                'Document updated in OpenRegister',
                [
                    'documentId' => $documentId,
                ]
            );

            return $document;
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to update document in OpenRegister: '.$e->getMessage(),
                [
                    'documentId' => $documentId,
                    'exception'  => $e,
                ]
            );
            throw new Exception('Failed to update document: '.$e->getMessage(), 0, $e);
        }

    }//end updateDocument()

    /**
     * Delete a document from OpenRegister
     *
     * @param string $documentId The document ID
     *
     * @return bool True if deletion was successful, false otherwise
     */
    public function deleteDocument(string $documentId): bool
    {
        try {
            $result = $this->objectService->deleteObject($this->schemaType, $documentId);
            $this->logger->debug(
                'Document deleted from OpenRegister',
                [
                    'documentId' => $documentId,
                ]
            );
            return $result;
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to delete document from OpenRegister: '.$e->getMessage(),
                [
                    'documentId' => $documentId,
                    'exception'  => $e,
                ]
            );
            return false;
        }

    }//end deleteDocument()

    /**
     * Find documents matching the given filters
     *
     * @param array<string, mixed> $filters Search filters
     * @param int                  $limit  Maximum number of results
     * @param int                  $offset Offset for pagination
     *
     * @return array<array<string, mixed>> Array of document objects
     */
    public function findDocuments(array $filters=[], int $limit=50, int $offset=0): array
    {
        try {
            $config = [
                'filters' => array_merge(
                    $filters,
                    [
                        'register' => $this->registerType,
                        'schema'   => $this->schemaType,
                    ]
                ),
                'limit'   => $limit,
                'offset'  => $offset,
            ];

            $objects = $this->objectService->findAll($config);

            return array_map(
                function ($object) {
                    return $object->jsonSerialize();
                },
                $objects
            );
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to find documents in OpenRegister: '.$e->getMessage(),
                [
                    'filters'   => $filters,
                    'exception' => $e,
                ]
            );
            return [];
        }

    }//end findDocuments()

    /**
     * Get text content from a document stored in OpenRegister
     *
     * OpenRegister handles text extraction, so this method retrieves
     * the extracted text from the document object.
     *
     * @param string $documentId The document ID
     *
     * @return string|null The extracted text content or null if not available
     */
    public function getDocumentText(string $documentId): ?string
    {
        try {
            $document = $this->getDocument($documentId);
            if ($document === null) {
                return null;
            }

            // OpenRegister stores extracted text in the document object.
            return $document['text'] ?? $document['extractedText'] ?? null;
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to get document text from OpenRegister: '.$e->getMessage(),
                [
                    'documentId' => $documentId,
                    'exception'  => $e,
                ]
            );
            return null;
        }

    }//end getDocumentText()

    /**
     * Get the register type being used
     *
     * @return string The register type
     */
    public function getRegisterType(): string
    {
        return $this->registerType;

    }//end getRegisterType()

    /**
     * Get the schema type being used
     *
     * @return string The schema type
     */
    public function getSchemaType(): string
    {
        return $this->schemaType;

    }//end getSchemaType()

}//end class


