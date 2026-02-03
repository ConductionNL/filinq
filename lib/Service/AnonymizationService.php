<?php
/**
 * Anonymization Service
 *
 * Service for anonymizing sensitive information in documents.
 * This service works with documents stored in OpenRegister and uses
 * Presidio for entity detection and anonymization.
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
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use OCA\DocuDesk\Service\OpenRegisterService;
use OCA\OpenRegister\Service\DocumentService;
use OCP\Files\Node;
use OCP\Files\IRootFolder;
use OCP\IConfig;
use OCP\IAppConfig;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Service for anonymizing sensitive information in documents
 *
 * This service handles the anonymization of sensitive information in documents
 * using Presidio for entity detection and replacement. It works with documents
 * stored in OpenRegister and stores anonymization results on the document object.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class AnonymizationService
{
    /**
     * Default Presidio API URL if not specified in configuration
     *
     * @var string
     */
    private const DEFAULT_PRESIDIO_ANALYZER_URL = 'http://presidio-api:8080/analyze';

    /**
     * Default Presidio Anonymizer API URL if not specified in configuration
     *
     * @var string
     */
    private const DEFAULT_PRESIDIO_ANONYMIZER_URL = 'http://presidio-api:8080/anonymize';

    /**
     * Default confidence threshold for entity detection
     *
     * @var float
     */
    private const DEFAULT_CONFIDENCE_THRESHOLD = 0.7;

    /**
     * Logger instance for error reporting
     *
     * @var LoggerInterface
     */
    private readonly LoggerInterface $logger;

    /**
     * HTTP client for API requests
     *
     * @var Client
     */
    private readonly Client $client;

    /**
     * Configuration service
     *
     * @var IConfig
     */
    private readonly IConfig $config;

    /**
     * OpenRegister service for document operations
     *
     * @var OpenRegisterService
     */
    private readonly OpenRegisterService $openRegisterService;

    /**
     * Document service from OpenRegister for word replacement
     *
     * @var DocumentService
     */
    private readonly DocumentService $documentService;

    /**
     * Root folder service for file operations
     *
     * @var IRootFolder
     */
    private readonly IRootFolder $rootFolder;

    /**
     * User session for getting current user
     *
     * @var IUserSession
     */
    private readonly IUserSession $userSession;

    /**
     * App config for getting app config
     *
     * @var IAppConfig
     */
    private readonly IAppConfig $appConfig;

    /**
     * Constructor for AnonymizationService
     *
     * @param LoggerInterface      $logger             Logger for error reporting
     * @param IConfig              $config             Configuration service
     * @param OpenRegisterService  $openRegisterService Service for OpenRegister operations
     * @param DocumentService      $documentService     Document service from OpenRegister
     * @param IRootFolder          $rootFolder         Root folder service for file operations
     * @param IUserSession         $userSession        User session for getting current user
     * @param IAppConfig           $appConfig          App configuration service
     *
     * @return void
     */
    public function __construct(
        LoggerInterface $logger,
        IConfig $config,
        OpenRegisterService $openRegisterService,
        DocumentService $documentService,
        IRootFolder $rootFolder,
        IUserSession $userSession,
        IAppConfig $appConfig
    ) {
        $this->logger             = $logger;
        $this->config             = $config;
        $this->openRegisterService = $openRegisterService;
        $this->documentService    = $documentService;
        $this->rootFolder         = $rootFolder;
        $this->userSession        = $userSession;
        $this->appConfig          = $appConfig;

        // Initialize Guzzle HTTP client.
        $this->client = new Client(
            [
                'timeout'         => 30,
                'connect_timeout' => 5,
            ]
        );

    }//end __construct()

    /**
     * Anonymize a document stored in OpenRegister
     *
     * This method retrieves a document from OpenRegister, gets its text content
     * (which was extracted by OpenRegister), detects entities using Presidio,
     * and creates an anonymized version of the document file.
     *
     * @param string $documentId The document ID in OpenRegister
     *
     * @return array<string, mixed> The updated document with anonymization results
     *
     * @throws Exception If anonymization fails
     */
    public function anonymizeDocument(string $documentId): array
    {
        $startTime = microtime(true);

        try {
            // Get document from OpenRegister.
            $document = $this->openRegisterService->getDocument($documentId);
            if ($document === null) {
                throw new Exception('Document not found: '.$documentId);
            }

            // Get file node if file path is available.
            $filePath = $document['filePath'] ?? null;
            if ($filePath === null) {
                throw new Exception('File path not available for document: '.$documentId);
            }

            $node = $this->rootFolder->get($filePath);
            if ($node->getType() !== \OCP\Files\FileInfo::TYPE_FILE) {
                throw new Exception('Node is not a file: '.$filePath);
            }

            // Get text content from OpenRegister (extracted by OpenRegister).
            $text = $this->openRegisterService->getDocumentText($documentId);
            if ($text === null || empty($text) === true) {
                throw new Exception('Text content not available for document: '.$documentId);
            }

            // Detect entities using Presidio.
            $entities = $this->detectEntities($text);

            // Check if anonymization is needed.
            if (empty($entities) === true) {
                $this->logger->info('No entities detected for anonymization in document: '.$documentId);

                // Update document with anonymization status.
                $document['anonymization'] = [
                    'status'         => 'completed',
                    'message'        => 'No entities detected for anonymization',
                    'endTime'        => microtime(true),
                    'processingTime' => microtime(true) - $startTime,
                    'entities'       => [],
                ];

                $this->openRegisterService->updateDocument($documentId, $document);
                return $document;
            }

            // Process anonymization using OpenRegister DocumentService.
            $anonymizedFile = $this->documentService->anonymizeDocument($node, $entities);

            // Update document with anonymization results.
            $endTime = microtime(true);
            $document['anonymization'] = [
                'status'             => 'completed',
                'message'            => 'Anonymization completed successfully',
                'anonymizedFileName' => $anonymizedFile->getName(),
                'anonymizedFilePath' => $anonymizedFile->getPath(),
                'entities'           => $entities,
                'endTime'            => $endTime,
                'processingTime'     => $endTime - $startTime,
            ];

            $updatedDocument = $this->openRegisterService->updateDocument($documentId, $document);

            $this->logger->debug(
                'Document anonymized successfully',
                [
                    'documentId' => $documentId,
                    'entities'   => count($entities),
                ]
            );

            return $updatedDocument;
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to anonymize document: '.$e->getMessage(),
                [
                    'documentId' => $documentId,
                    'exception'  => $e,
                ]
            );
            throw new Exception('Failed to anonymize document: '.$e->getMessage(), 0, $e);
        }

    }//end anonymizeDocument()

    /**
     * Preview anonymization without creating an anonymized file
     *
     * @param string $documentId The document ID in OpenRegister
     *
     * @return array<string, mixed> Preview data with detected entities
     *
     * @throws Exception If preview fails
     */
    public function previewAnonymization(string $documentId): array
    {
        try {
            // Get text content from OpenRegister.
            $text = $this->openRegisterService->getDocumentText($documentId);
            if ($text === null || empty($text) === true) {
                throw new Exception('Text content not available for document: '.$documentId);
            }

            // Detect entities using Presidio.
            $entities = $this->detectEntities($text);

            return [
                'documentId' => $documentId,
                'entities'   => $entities,
                'count'      => count($entities),
            ];
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to preview anonymization: '.$e->getMessage(),
                [
                    'documentId' => $documentId,
                    'exception'  => $e,
                ]
            );
            throw new Exception('Failed to preview anonymization: '.$e->getMessage(), 0, $e);
        }

    }//end previewAnonymization()

    /**
     * Detect entities in text using Presidio
     *
     * @param string $text Text content to analyze
     *
     * @return array<array<string, mixed>> Array of detected entities
     */
    private function detectEntities(string $text): array
    {
        try {
            // Get Presidio analyzer URL from configuration.
            $analyzerUrl = $this->appConfig->getValueString(
                'docudesk',
                'presidio_analyzer_url',
                self::DEFAULT_PRESIDIO_ANALYZER_URL
            );

            // Get confidence threshold from configuration.
            $threshold = (float) $this->appConfig->getValueString(
                'docudesk',
                'presidio_confidence_threshold',
                (string) self::DEFAULT_CONFIDENCE_THRESHOLD
            );

            // Prepare request to Presidio analyzer.
            $requestData = [
                'text'      => $text,
                'language'  => 'en',
                'entities'  => [
                    'PERSON',
                    'EMAIL_ADDRESS',
                    'PHONE_NUMBER',
                    'CREDIT_CARD',
                    'IBAN_CODE',
                    'IP_ADDRESS',
                    'DATE_TIME',
                    'LOCATION',
                    'ORGANIZATION',
                ],
            ];

            // Send request to Presidio analyzer.
            $response = $this->client->post(
                $analyzerUrl,
                [
                    'json'    => $requestData,
                    'timeout' => 30,
                ]
            );

            $responseData = json_decode($response->getBody()->getContents(), true);
            $entities     = $responseData['entities'] ?? [];

            // Filter entities by confidence threshold and add keys.
            $processedEntities = [];
            foreach ($entities as $entity) {
                $score = $entity['score'] ?? 0;
                if ($score >= $threshold) {
                    $processedEntities[] = [
                        'entityType' => $entity['type'] ?? 'UNKNOWN',
                        'text'       => substr($text, $entity['start'] ?? 0, ($entity['end'] ?? 0) - ($entity['start'] ?? 0)),
                        'start'      => $entity['start'] ?? 0,
                        'end'        => $entity['end'] ?? 0,
                        'score'      => $score,
                        'key'        => substr(Uuid::v4()->toRfc4122(), 0, 8),
                    ];
                }
            }

            return $processedEntities;
        } catch (GuzzleException $e) {
            $this->logger->error(
                'Failed to detect entities using Presidio: '.$e->getMessage(),
                [
                    'exception' => $e,
                ]
            );
            return [];
        }

    }//end detectEntities()


    /**
     * Get anonymization rules from configuration
     *
     * @return array<string, mixed> Anonymization rules configuration
     */
    public function getAnonymizationRules(): array
    {
        return [
            'presidio_analyzer_url'         => $this->appConfig->getValueString(
                'docudesk',
                'presidio_analyzer_url',
                self::DEFAULT_PRESIDIO_ANALYZER_URL
            ),
            'presidio_anonymizer_url'       => $this->appConfig->getValueString(
                'docudesk',
                'presidio_anonymizer_url',
                self::DEFAULT_PRESIDIO_ANONYMIZER_URL
            ),
            'presidio_confidence_threshold' => (float) $this->appConfig->getValueString(
                'docudesk',
                'presidio_confidence_threshold',
                (string) self::DEFAULT_CONFIDENCE_THRESHOLD
            ),
        ];

    }//end getAnonymizationRules()

    /**
     * Update anonymization rules in configuration
     *
     * @param array<string, mixed> $rules Anonymization rules to update
     *
     * @return void
     */
    public function updateAnonymizationRules(array $rules): void
    {
        if (isset($rules['presidio_analyzer_url']) === true) {
            $this->appConfig->setValueString(
                'docudesk',
                'presidio_analyzer_url',
                $rules['presidio_analyzer_url']
            );
        }

        if (isset($rules['presidio_anonymizer_url']) === true) {
            $this->appConfig->setValueString(
                'docudesk',
                'presidio_anonymizer_url',
                $rules['presidio_anonymizer_url']
            );
        }

        if (isset($rules['presidio_confidence_threshold']) === true) {
            $this->appConfig->setValueString(
                'docudesk',
                'presidio_confidence_threshold',
                (string) $rules['presidio_confidence_threshold']
            );
        }

    }//end updateAnonymizationRules()

}//end class
