<?php
/**
 * Service for generating and managing document reports
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.DocuDesk.app
 */

namespace OCA\DocuDesk\Service;

use DateTime;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use OCP\IConfig;
use OCP\IAppConfig;
use OCP\ILogger;
use OCP\Files\Node;
use OCP\Files\IRootFolder;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;
use OCA\DocuDesk\Service\AnonymizationService;
use OCA\OpenRegister\Service\ObjectService;
use OCA\OpenRegister\Db\ObjectEntity;
use OCA\DocuDesk\Service\EntityService;

/**
 * Service for generating and managing document reports
 *
 * This service provides functionality for creating, processing, and managing
 * document reports including entity detection, risk assessment, and integration
 * with external services like Presidio for privacy analysis.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 */
class ReportingService
{
    /**
     * Default Presidio API URL if not specified in configuration
     *
     * @var string
     */
    private const DEFAULT_PRESIDIO_URL = 'http://presidio-api:8080/analyze';

    /**
     * Default confidence threshold for entity detection
     *
     * @var float
     */
    private const DEFAULT_CONFIDENCE_THRESHOLD = 0.7;

    /**
     * Default report register type
     *
     * @var string
     */
    public $reportRegisterType;

    /**
     * Default report schema type
     *
     * @var string
     */
    public $reportSchemaType;

    /**
     * Entity service for managing entity objects
     *
     * @var EntityService
     */
    private EntityService $entityService;

    /**
     * Constructor for ReportingService
     *
     * @param LoggerInterface      $logger               Logger for error reporting
     * @param IConfig              $config               Configuration service
     * @param ObjectService        $objectService        Service for storing objects
     * @param ExtractionService    $extractionService    Service for extracting text from documents
     * @param IRootFolder          $rootFolder           Root folder service for accessing files
     * @param AnonymizationService $anonymizationService Service for anonymizing documents
     * @param IAppConfig           $appConfig            App configuration service
     * @param EntityService        $entityService        Service for managing entity objects
     *
     * @return void
     */
    public function __construct(
        LoggerInterface $logger,
        IConfig $config,
        ObjectService $objectService,
        ExtractionService $extractionService,
        IRootFolder $rootFolder,
        AnonymizationService $anonymizationService,
        IAppConfig $appConfig,
        EntityService $entityService
    ) {
        $this->logger            = $logger;
        $this->config            = $config;
        $this->objectService     = $objectService;
        $this->extractionService = $extractionService;
        $this->rootFolder        = $rootFolder;
        $this->anonymizationService = $anonymizationService;
        $this->appConfig            = $appConfig;
        $this->entityService        = $entityService;

        // Set this service in the anonymization service to avoid circular dependency.
        $this->anonymizationService->setReportingService($this);

        // Set the object service to use the reporting service.
        $reportRegisterType = $this->appConfig->getValueString('docudesk', 'report_register', 'document');
        $this->objectService->setRegister($reportRegisterType);

        $reportSchemaType = $this->appConfig->getValueString('docudesk', 'report_schema', 'report');
        $this->objectService->setSchema($reportSchemaType);

        $this->reportRegisterType = $reportRegisterType;
        $this->reportSchemaType   = $reportSchemaType;

        // Initialize Guzzle HTTP client.
        $this->client = new Client(
            [
                'timeout'         => 30,
                'connect_timeout' => 5,
            ]
        );

    }//end __construct()


    /**
     * Process a file by creating and processing a report
     *
     * This method creates a report for the given node and processes it immediately.
     *
     * @param Node $node The file node to process
     *
     * @return ObjectEntity The processed report object
     *
     * @throws \InvalidArgumentException If the node is invalid
     * @throws Exception If processing fails
     *
     * @psalm-return   ObjectEntity
     * @phpstan-return ObjectEntity
     */
    public function processFile(Node $node): ObjectEntity
    {
        $report = $this->getReport($node);
        return $this->processReport($report);

    }//end processFile()


    /**
     * Process a report for a document
     *
     * This method extracts text from a document, sends it to Presidio for analysis,
     * and stores the results as a report object. It can accept either a Node or an existing report array.
     * If anonymization is enabled, it will also anonymize the document.
     *
     * @param array|ObjectEntity $report    Either a Node object or an existing report array
     * @param float              $threshold Confidence threshold for entity detection (optional)
     *
     * @return array The processed report data
     *
     * @throws \InvalidArgumentException If input is invalid or node cannot be found
     * @throws Exception If report processing fails
     *
     * @psalm-return   array
     * @phpstan-return array
     */
    public function processReport(
        array | ObjectEntity $report,
        float $threshold=self::DEFAULT_CONFIDENCE_THRESHOLD
    ): array {

        if (is_array($report) === false) {
            $report = $report->jsonSerialize();
        }

        $nodeId = $report['nodeId'] ?? null;

        try {
            $node = $this->rootFolder->getById($nodeId)[0] ?? null;
        } catch (Exception $e) {
            throw new \InvalidArgumentException('Could not find node with ID: '.$nodeId);
        }

        // Get the report object type and set the status to processing.
        $this->logger->info('Processing report for node: '.$node->getId());

        $report['status'] = 'processing';
        
        // Ensure ObjectService is configured for reports before saving
        $this->ensureReportConfiguration();
        $this->objectService->saveObject(object: $report, uuid: $report['id']);

        // Extract text from document.
        $filePath = $node->getPath();
        try {
            $extraction     = $this->extractionService->extractText($node);
            $report['text'] = $extraction['text'];
            $report['errorMessage'] = $extraction['errorMessage'];
        } catch (Exception $e) {
            $this->logger->error('Error extracting text from document: '.$e->getMessage(), ['exception' => $e]);
            $report['status']       = 'failed';
            $report['errorMessage'] = 'Error extracting text from document: '.$e->getMessage();
            
            // Ensure ObjectService is configured for reports before saving
            $this->ensureReportConfiguration();
            $this->objectService->saveObject(object: $report, uuid: $report['id']);
            return  $report;
        }

        if (empty($report['text']) === true) {
            $this->logger->warning('No text content found in document: '.$filePath);
            $report['status'] = 'completed';
            // $report['errorMessage'] = 'Document appears to be empty or contains no extractable text';
            $report['entities'] = [];

            // Set appropriate values for non-text documents.
            $report['anonymizationResults'] = [
                'containsPersonalData' => false,
                'dataCategories'       => [],
                'anonymizationStatus'  => 'not_required',
            ];

            $report['riskLevel'] = 'low';
            
            // Ensure ObjectService is configured for reports before saving
            $this->ensureReportConfiguration();
            $this->objectService->saveObject(object: $report, uuid: $report['id']);
            return  $report;
        }

        // Send text to Presidio for analysis and get entities.
        $presidioResults = $this->analyzeWithPresidio($report['text'], $threshold);

        // Process entities from Presidio with new enhanced logic
        $report['entities'] = $this->processEntitiesFromPresidio($presidioResults['entities_found']);

        if (empty($report['entities']) === true) {
            $this->logger->debug('No entities detected in document: '.$filePath);
        }

        // Update report with results.
        $report['status'] = 'completed';

        // Lets calculate the risk score .
        $report['riskScore'] = $this->calculateRiskScore($report['entities']);

        // Lets calculate the risk level.
        $report['riskLevel'] = $this->getRiskLevel($report['riskScore']);

        // Save updated report.
        // Ensure ObjectService is configured for reports before saving
        $this->ensureReportConfiguration();
        $this->objectService->saveObject(object: $report, uuid: $report['id']);

        // Process anonymization if enabled.
        if ($this->isAnonymizationEnabled() === true && empty($report['entities']) === false) {
            $report = $this->anonymizationService->processAnonymization($node, $report);
        }

        return $report;

    }//end processReport()


    /**
     * Analyze text with Presidio
     *
     * Sends text to the Presidio API for entity analysis.
     *
     * @param string $text      Text to analyze
     * @param float  $threshold Confidence threshold for entity detection
     *
     * @return array{
     *     entities_found: array<int,array{
     *         entity_type: string,
     *         start: int,
     *         end: int,
     *         score: float,
     *         text: string
     *     }>,
     *     language: string
     * } Presidio analysis results
     *
     * @throws Exception If the API request fails
     */
    private function analyzeWithPresidio(string $text, float $threshold=self::DEFAULT_CONFIDENCE_THRESHOLD): array
    {
        // Get Presidio API URL from configuration or use default.
        $presidioUrl = $this->config->getSystemValue(
            'docudesk_presidio_analyzer_url',
            self::DEFAULT_PRESIDIO_URL
        );

        $this->logger->debug('Analyzing text with Presidio('.$presidioUrl.'): '.$text);

        // Prepare request payload.
        $payload = [
            'text'                    => $text,
            'language'                => 'nl',
        // Default to   @todo this should be configuration.
            'score_threshold'         => $threshold,
            'return_decision_process' => false,
        ];

        try {
            // Send request to Presidio.
            $response = $this->client->post(
                $presidioUrl,
                    [
                        'json'    => $payload,
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'Accept'       => 'application/json',
                        ],
                    ]
            );

            // Parse response.
            $responseBody = $response->getBody()->getContents();
            $results      = json_decode($responseBody, true);

            if (is_array($results) === false) {
                throw new Exception('Invalid response from Presidio API');
            }

            return $results;
        } catch (GuzzleException $e) {
            $this->logger->error('Presidio API request failed: '.$e->getMessage(), ['exception' => $e]);
            throw new Exception('Failed to communicate with Presidio API: '.$e->getMessage(), 0, $e);
        }//end try

    }//end analyzeWithPresidio()


    /**
     * Process entities from Presidio results with enhanced logic
     *
     * This method processes entities from Presidio by:
     * - Making entities unique by text property
     * - Adding key and anonymize boolean to each entity
     * - Looking up or creating entity objects
     * - Storing entity object IDs in the report entities
     *
     * @param array<int,array{
     *     entity_type: string,
     *     start: int,
     *     end: int,
     *     score: float,
     *     text: string
     * }> $presidioEntities Raw entities from Presidio
     *
     * @return array<int,array{
     *     text: string,
     *     score: float,
     *     entityType: string,
     *     start: int,
     *     end: int,
     *     key: string,
     *     anonymize: bool,
     *     entityObjectId: string
     * }> Processed entities with enhanced data
     *
     * @throws Exception If entity processing fails
     *
     * @psalm-return   array
     * @phpstan-return array
     */
    private function processEntitiesFromPresidio(array $presidioEntities): array
    {
        // Make entities unique by text property and aggregate scores
        $uniqueEntities = [];
        
        foreach ($presidioEntities as $entity) {
            $text       = $entity['text'] ?? '';
            $entityType = $entity['entity_type'] ?? 'UNKNOWN';
            $score      = $entity['score'] ?? 0.0;
            $start      = $entity['start'] ?? 0;
            $end        = $entity['end'] ?? 0;
            
            if (empty($text) === true) {
                continue;
            }
            
            // Use text as unique key for deduplication
            $uniqueKey = $text;
            
            if (isset($uniqueEntities[$uniqueKey]) === true) {
                // If entity already exists, keep the higher score
                if ($score > $uniqueEntities[$uniqueKey]['score']) {
                    $uniqueEntities[$uniqueKey]['score'] = $score;
                    $uniqueEntities[$uniqueKey]['start'] = $start;
                    $uniqueEntities[$uniqueKey]['end']   = $end;
                }
            } else {
                // New unique entity
                $uniqueEntities[$uniqueKey] = [
                    'text'       => $text,
                    'score'      => $score,
                    'entityType' => $entityType,
                    'start'      => $start,
                    'end'        => $end,
                ];
            }
        }
        
        // Process each unique entity
        $processedEntities = [];
        
        foreach ($uniqueEntities as $entity) {
            try {
                // Find or create entity object
                $entityObject = $this->entityService->findOrCreateEntity(
                    $entity['text'],
                    $entity['entityType']
                );
                
                // Verify the entity was created successfully before trying to update statistics
                if (empty($entityObject['id']) === true) {
                    throw new Exception('Entity object has no ID');
                }
                
                // Update entity statistics
                $this->entityService->updateEntityStatistics(
                    $entityObject['id'],
                    $entity['score']
                );
                
                // Generate unique key for this document-specific entity
                $entityKey = substr(\Symfony\Component\Uid\Uuid::v4()->toRfc4122(), 0, 8);
                
                // Add enhanced data to entity
                $enhancedEntity = [
                    'text'           => $entity['text'],
                    'score'          => $entity['score'],
                    'entityType'     => $entity['entityType'],
                    'start'          => $entity['start'],
                    'end'            => $entity['end'],
                    'key'            => $entityKey,
                    'anonymize'      => true, // Default to true for security-first approach
                    'entityObjectId' => $entityObject['id'],
                ];
                
                $processedEntities[] = $enhancedEntity;
                
                $this->logger->debug(
                    'Processed entity: '.$entity['text'].' with object ID: '.$entityObject['id']
                );
                
            } catch (Exception $e) {
                $this->logger->error(
                    'Failed to process entity: '.$e->getMessage(),
                    [
                        'entity'    => $entity,
                        'exception' => $e,
                    ]
                );
                
                // Add entity without object reference as fallback
                $entityKey = substr(\Symfony\Component\Uid\Uuid::v4()->toRfc4122(), 0, 8);
                
                $fallbackEntity = [
                    'text'           => $entity['text'],
                    'score'          => $entity['score'],
                    'entityType'     => $entity['entityType'],
                    'start'          => $entity['start'],
                    'end'            => $entity['end'],
                    'key'            => $entityKey,
                    'anonymize'      => true,
                    'entityObjectId' => null, // No object reference
                ];
                
                $processedEntities[] = $fallbackEntity;
            }//end try
        }//end foreach
        
        $this->logger->info(
            'Processed '.count($processedEntities).' unique entities from '.count($presidioEntities).' detected entities'
        );
        
        return $processedEntities;
        
    }//end processEntitiesFromPresidio()


    /**
     * Calculate a risk score based on detected entities
     *
     * @param array<int, array<string, mixed>> $entities List of entities detected in the document
     *
     * @return float Risk score between 0 and 100
     *
     * @psalm-return   float
     * @phpstan-return float
     */
    private function calculateRiskScore(array $entities): float
    {
        if (empty($entities) === true) {
            return 0.0;
        }

        // Define base scores for different entity types (out of 100).
        $entityBaseScores = [
            'PERSON'            => 50.0,
        // Finding a person is automatically medium risk.
            'EMAIL_ADDRESS'     => 60.0,
            'PHONE_NUMBER'      => 55.0,
            'CREDIT_CARD'       => 90.0,
            'IBAN_CODE'         => 85.0,
            'US_SSN'            => 90.0,
            'US_BANK_NUMBER'    => 85.0,
            'LOCATION'          => 30.0,
            'DATE_TIME'         => 10.0,
            'NRP'               => 70.0,
            'IP_ADDRESS'        => 45.0,
            'US_DRIVER_LICENSE' => 65.0,
            'US_PASSPORT'       => 85.0,
            'US_ITIN'           => 85.0,
            'MEDICAL_LICENSE'   => 60.0,
            'URL'               => 20.0,
            'DEFAULT'           => 40.0,
        ];

        // Calculate maximum risk score from entities.
        $maxRiskScore   = 0.0;
        $totalRiskScore = 0.0;
        $entityCount    = count($entities);

        foreach ($entities as $entity) {
            $type       = $entity['entityType'] ?? 'DEFAULT';
            $confidence = $entity['score'] ?? 0.7;

            // Get base score for this entity type.
            $baseScore = $entityBaseScores[$type] ?? $entityBaseScores['DEFAULT'];

            // Calculate risk score for this entity based on confidence.
            $entityRiskScore = $baseScore * $confidence;

            // Track highest individual risk score.
            $maxRiskScore = max($maxRiskScore, $entityRiskScore);

            // Add to total risk score.
            $totalRiskScore += $entityRiskScore;
        }

        // Final score is weighted combination of:
        // - Highest individual risk (70% weight).
        // - Average risk across all entities (30% weight).
        if ($entityCount > 0) {
            $averageRisk = $totalRiskScore / $entityCount;
        } else {
            $averageRisk = 0;
        }

        $finalScore = ($maxRiskScore * 0.7) + ($averageRisk * 0.3);

        // Apply multiplier based on number of entities.
        $countMultiplier = min(1 + ($entityCount / 5), 2.0);
        $finalScore     *= $countMultiplier;

        // Cap at 100.
        return min($finalScore, 100.0);

    }//end calculateRiskScore()


    /**
     * Get a risk level label based on the risk score
     *
     * @param float $riskScore Risk score between 0 and 100
     *
     * @return string Risk level label (Low, Medium, High, Critical)
     *
     * @psalm-return   string
     * @phpstan-return string
     */
    private function getRiskLevel(float $riskScore): string
    {
        if ($riskScore < 30) {
            return 'Low';
        } else if ($riskScore < 60) {
            return 'Medium';
        } else if ($riskScore < 85) {
            return 'High';
        } else {
            return 'Critical';
        }

    }//end getRiskLevel()


    /**
     * Get a report for a node
     *
     * @param \OCP\Files\Node $node The file node to get the report for
     *
     * @return array{
     *     nodeId: int,
     *     filePath: string,
     *     fileName: string,
     *     fileType: string,
     *     fileExtension: string,
     *     fileSize: int,
     *     status: string,
     *     errorMessage: string|null,
     *     riskScore: float|null,
     *     riskLevel: string,
     *     anonymizationResults: array<string,mixed>|null,
     *     entities: array<int,array<string,mixed>>,
     *     wcagComplianceResults: array<string,mixed>|null,
     *     languageLevelResults: array<string,mixed>|null,
     *     retentionPeriod: int,
     *     retentionExpiry: string|null,
     *     legalBasis: string|null,
     *     dataController: string|null,
     *     fileHash: string,
     *     text: string|null
     * }|null The report or null if not found
     *
     * @throws \InvalidArgumentException If the node is not a file
     * @throws \RuntimeException If multiple reports are found for the node
     */
    public function getReport(\OCP\Files\Node $node): ?ObjectEntity
    {
        // Validate that the node is a file.
        if ($node->getType() !== \OCP\Files\FileInfo::TYPE_FILE) {
            throw new \InvalidArgumentException('Node must be a file to get a report');
        }

        try {
            $reportObjectType = $this->config->getSystemValue('docudesk_report_object_type', 'report');

            $config['filters'] = [
                'nodeId'   => $node->getId(),
                'register' => $this->reportRegisterType,
                'schema'   => $this->reportSchemaType,
            ];

            $reports = $this->objectService->findAll($config);

            // Throw error if multiple reports found.
            if (count($reports) > 1) {
                throw new \RuntimeException(
                    'Multiple reports found for node '.$node->getId().'. There should only be one report per node.'
                );
            }

            if (empty($reports) === false) {
                return $reports[0]->jsonSerialize();
            } else {
                return null;
            }
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to retrieve report: '.$e->getMessage(),
                    [
                        'nodeId'    => $node->getId(),
                        'exception' => $e,
                    ]
            );
            return null;
        }//end try

    }//end getReport()


    /**
     * Delete a report by ID
     *
     * @param string $reportId ID of the report to delete
     *
     * @return bool True if deletion was successful, false otherwise
     *
     * @psalm-return   bool
     * @phpstan-return bool
     */
    public function deleteReport(string $reportId): bool
    {
        try {
            return $this->objectService->deleteObject('report', $reportId);
        } catch (Exception $e) {
            $this->logger->error('Failed to delete report: '.$e->getMessage(), ['exception' => $e]);
            return false;
        }

    }//end deleteReport()


     /**
      * Process an existing report
      *
      * @param \OCP\Files\Node $node The file node to process
      *
      * @return array{
      *     nodeId: int,
      *     filePath: string,
      *     fileName: string,
      *     fileType: string,
      *     fileExtension: string,
      *     fileSize: int,
      *     status: string,
      *     errorMessage: string|null,
      *     riskScore: float|null,
      *     riskLevel: string,
      *     anonymizationResults: array<string,mixed>|null,
      *     entities: array<int,array<string,mixed>>,
      *     wcagComplianceResults: array<string,mixed>|null,
      *     languageLevelResults: array<string,mixed>|null,
      *     retentionPeriod: int,
      *     retentionExpiry: string|null,
      *     legalBasis: string|null,
      *     dataController: string|null,
      *     fileHash: string,
      *     text: string|null
      * }|null The processed report or null if processing failed
      *
      * @throws Exception If report processing fails
      * @throws \InvalidArgumentException If the node is not a file
      */
    public function updateReport(\OCP\Files\Node $node): ?array
    {
        // Validate that the node is a file.
        if ($node->getType() !== \OCP\Files\FileInfo::TYPE_FILE) {
            throw new \InvalidArgumentException('Node must be a file to process a report');
        }

        // Get the existing report.
        $report = $this->getReport($node);

        // If no report is found, create a new report.
        if ($report === null) {
            return $this->createReport($node);
        }

        // Use ETag as file hash if available.
        $fileHash = null;
        if (method_exists($node, 'getEtag') === true) {
            $fileHash = $node->getEtag();
            $this->logger->debug('Using ETag as file hash: '.$fileHash);
        } else {
            // Fall back to calculating hash.
            $fileHash = $this->calculateFileHash($node->getPath());
        }

        // If the file hash has not changed, skip the report update.
        if ($fileHash === $report['fileHash']) {
            $this->logger->debug('File hash has not changed, skipping report update');
            return $report;
        }

        // Update the report object with new values.
        $report['filePath']      = $node->getPath();
        $report['fileName']      = $node->getName();
        $report['fileType']      = $node->getMimetype();
        $report['fileExtension'] = pathinfo($node->getName(), PATHINFO_EXTENSION);
        $report['fileSize']      = $node->getSize();
        $report['status']        = 'pending';
        // Reset status to pending to trigger a new report.
        $report['fileHash'] = $fileHash;

        // Reset analysis results since we're going to reprocess.
        $report['errorMessage'] = null;

        // Only reset these if they exist (they might be null in older reports).
        if (isset($report['anonymizationResults']) === true) {
            $report['anonymizationResults'] = null;
        }

        if (isset($report['wcagComplianceResults']) === true) {
            $report['wcagComplianceResults'] = null;
        }

        if (isset($report['languageLevelResults']) === true) {
            $report['languageLevelResults'] = null;
        }

        // Save the updated report.
        // Ensure ObjectService is configured for reports before saving
        $this->ensureReportConfiguration();
        $this->objectService->saveObject(object: $report, uuid: $report['id']);

        // Process the report now if synchronous processing is enabled.
        if ($this->isSynchronousProcessingEnabled() === true) {
            return $this->processReport($report);
        }

        return $this->processReport($report);

    }//end updateReport()


    /**
     * Calculate a hash for a file
     *
     * @param string $filePath Path to the file
     *
     * @return string The file hash
     *
     * @psalm-return   string
     * @phpstan-return string
     */
    public function calculateFileHash(string $filePath): string
    {
        try {
            // For small files, use the content hash.
            $fileSize = filesize($filePath);
            if ($fileSize !== false && $fileSize < 10 * 1024 * 1024) {
                // 10 MB
                $content = file_get_contents($filePath);
                if ($content !== false) {
                    $hash = md5($content);
                    $this->logger->debug(
                        'Calculated content hash for file',
                            [
                                'filePath' => $filePath,
                                'hash'     => $hash,
                                'method'   => 'content',
                            ]
                    );
                    return $hash;
                }
            }

            // For larger files, use a combination of metadata.
            $stats = stat($filePath);
            if ($stats !== false) {
                $hash = md5(
                    $filePath.$stats['size'].$stats['mtime']
                );
                $this->logger->debug(
                    'Calculated metadata hash for file',
                        [
                            'filePath' => $filePath,
                            'hash'     => $hash,
                            'method'   => 'metadata',
                        ]
                );
                return $hash;
            }

            // Fallback to just the path.
            $hash = md5($filePath);
            $this->logger->debug(
                'Calculated fallback hash for file',
                    [
                        'filePath' => $filePath,
                        'hash'     => $hash,
                        'method'   => 'path',
                    ]
            );
            return $hash;
        } catch (Exception $e) {
            $this->logger->warning(
                'Failed to calculate file hash: '.$e->getMessage(),
                    [
                        'filePath'  => $filePath,
                        'exception' => $e,
                    ]
            );

            // Fallback to just the path.
            $hash = md5($filePath);
            $this->logger->debug(
                'Calculated fallback hash after error',
                    [
                        'filePath' => $filePath,
                        'hash'     => $hash,
                        'method'   => 'path',
                    ]
            );
            return $hash;
        }//end try

    }//end calculateFileHash()


    /**
     * Process pending reports
     *
     * @param int $limit Maximum number of reports to process
     *
     * @return int Number of reports processed
     *
     * @psalm-return   int
     * @phpstan-return int
     */
    public function processPendingReports(int $limit=10): int
    {
        // Check if reporting is enabled.
        if ($this->isReportingEnabled() === false) {
            $this->logger->debug('Reporting is disabled, skipping processing of pending reports');
            return 0;
        }

        try {
            // Find pending reports.
            $reportObjectType = $this->config->getSystemValue('docudesk_report_object_type', 'report');
            $filters          = [
                'status' => 'pending',
            ];

            $pendingReports = $this->objectService->getObjects(
                $reportObjectType,
                $limit,
                0,
                $filters
            );

            if (empty($pendingReports) === true) {
                $this->logger->debug('No pending reports found');
                return 0;
            }

            $this->logger->info('Processing '.count($pendingReports).' pending reports');

            $processedCount = 0;
            foreach ($pendingReports as $report) {
                try {
                    $nodeId   = $report['nodeId'] ?? null;
                    $filePath = $report['filePath'] ?? null;
                    $fileName = $report['fileName'] ?? null;

                    if ($nodeId === null) {
                        $this->logger->warning(
                            'Report has no nodeId, marking as failed',
                                [
                                    'reportId' => $report['id'] ?? 'unknown',
                                ]
                        );

                        $report['status']       = 'failed';
                        $report['errorMessage'] = 'Missing nodeId';
                        
                        // Ensure ObjectService is configured for reports before saving
                        $this->ensureReportConfiguration();
                        $this->objectService->saveObject(object: $report, uuid: $report['id']);
                        continue;
                    }

                    // Process the report.
                    $this->processReport($report);
                    $processedCount++;
                } catch (Exception $e) {
                    $this->logger->error(
                        'Error processing report: '.$e->getMessage(),
                            [
                                'reportId'  => $report['id'] ?? 'unknown',
                                'exception' => $e,
                            ]
                    );
                }//end try
            }//end foreach

            return $processedCount;
        } catch (Exception $e) {
            $this->logger->error(
                'Error processing pending reports: '.$e->getMessage(),
                    [
                        'exception' => $e,
                    ]
            );
            return 0;
        }//end try

    }//end processPendingReports()


    /**
     * Check if reporting is enabled
     *
     * @return bool True if reporting is enabled, false otherwise
     *
     * @psalm-return   bool
     * @phpstan-return bool
     */
    public function isReportingEnabled(): bool
    {
        return $this->config->getSystemValue('docudesk_enable_reporting', true);

    }//end isReportingEnabled()


    /**
     * Check if synchronous processing is enabled
     *
     * @return bool True if synchronous processing is enabled, false otherwise
     *
     * @psalm-return   bool
     * @phpstan-return bool
     */
    public function isSynchronousProcessingEnabled(): bool
    {
        return $this->config->getSystemValue('docudesk_synchronous_processing', false);

    }//end isSynchronousProcessingEnabled()


    /**
     * Check if anonymization is enabled
     *
     * @return bool True if anonymization is enabled, false otherwise
     *
     * @psalm-return   bool
     * @phpstan-return bool
     */
    public function isAnonymizationEnabled(): bool
    {
        return $this->config->getSystemValue('docudesk_enable_anonymization', true);

    }//end isAnonymizationEnabled()


    /**
     * Create a report from a Nextcloud node
     *
     * @param \OCP\Files\Node $node The file node
     *
     * @return array{
     *     nodeId: int,
     *     filePath: string,
     *     fileName: string,
     *     fileType: string,
     *     fileExtension: string,
     *     fileSize: int,
     *     status: string,
     *     errorMessage: string|null,
     *     riskScore: float|null,
     *     riskLevel: string,
     *     anonymizationResults: array<string,mixed>|null,
     *     entities: array<int,array<string,mixed>>,
     *     wcagComplianceResults: array<string,mixed>|null,
     *     languageLevelResults: array<string,mixed>|null,
     *     retentionPeriod: int,
     *     retentionExpiry: string|null,
     *     legalBasis: string|null,
     *     dataController: string|null,
     *     fileHash: string,
     *     text: string|null
     * }|null The created report or null if creation failed
     *
     * @throws \InvalidArgumentException If the node is not a file
     * @throws Exception If report creation fails
     */
    public function createReport(\OCP\Files\Node $node): array | null
    {
        // Validate that the node is a file.
        if ($node->getType() !== \OCP\Files\FileInfo::TYPE_FILE) {
            throw new \InvalidArgumentException('Node must be a file to create a report');
        }

        // Skip reporting for anonymized files (safeguard)
        $fileName = $node->getName();
        $fileNameWithoutExtension = pathinfo($fileName, PATHINFO_FILENAME);
        
        if (str_ends_with($fileNameWithoutExtension, '_anonymized') === true) {
            $this->logger->info(
                'Skipping report creation for anonymized file: '.$fileName.' (safeguard check)',
                ['nodeId' => $node->getId(), 'path' => $node->getPath()]
            );
            return null;
        }

        // Check if reporting is enabled.
        if ($this->isReportingEnabled() === false) {
            $this->logger->debug('Reporting is disabled, skipping report creation for node: '.$node->getId());
            return null;
        }

        // Check if a report already exists for this node and return updated report if found.
        if (($existingReport = $this->getReport($node)) !== null) {
            $this->logger->debug(
                'Report already exists for node: '.$node->getId().' with hash: '.$existingReport['fileHash']
            );
            return $this->updateReport($node);
        }

        $this->logger->debug('lets create a report for node: '.$node->getId());

        // Lets setup the report object with all fields from the documentation.
        $report = [
            'nodeId'                => $node->getId(),
            'filePath'              => $node->getPath(),
            'fileName'              => $node->getName(),
            'fileType'              => $node->getMimetype(),
            'fileExtension'         => pathinfo($node->getName(), PATHINFO_EXTENSION),
            'fileSize'              => $node->getSize(),
            'status'                => 'pending',
            'errorMessage'          => null,
            'riskScore'             => null,
        // Will be calculated during processing.
            'riskLevel'             => 'unknown',
        // Default value, will be updated during processing.
            'anonymizationResults'  => [],
        // Will be populated during processing.
            'entities'              => [],
        // Will be populated during processing.
            'wcagComplianceResults' => [],
        // Will be populated if WCAG analysis is enabled.
            'languageLevelResults'  => [],
        // Will be populated if language level analysis is enabled.
            'retentionPeriod'       => 0,
        // Default to indefinite retention.
            'retentionExpiry'       => null,
            'legalBasis'            => null,
            'dataController'        => null,
        ];

        // Use ETag as file hash if available.
        if (method_exists($node, 'getEtag') === true) {
            $report['fileHash'] = $node->getEtag();
            $this->logger->debug('Using ETag as file hash: '.$report['fileHash']);
        } else {
            // Fall back to calculating hash.
            $report['fileHash'] = $this->calculateFileHash($node->getPath());
        }

        // Save the report.
        // Ensure ObjectService is configured for reports before saving
        $this->ensureReportConfiguration();
        $reportEntity = $this->objectService->saveObject(object: $report);
        $report       = $reportEntity->jsonSerialize();

        $this->logger->debug('lets save the report: '.$report['id']);

        // Process the report now if synchronous processing is enabled.
        if ($this->isSynchronousProcessingEnabled() === true) {
            return $this->processReport($report);
        }

        return $this->processReport($report);

    }//end createReport()


    /**
     * Ensure ObjectService is configured for reports
     *
     * This method ensures that the ObjectService is properly configured
     * for report operations, as it might have been changed by EntityService.
     *
     * @return void
     *
     * @psalm-return   void
     * @phpstan-return void
     */
    private function ensureReportConfiguration(): void
    {
        // Reset ObjectService to report configuration
        $this->objectService->setRegister($this->reportRegisterType);
        $this->objectService->setSchema($this->reportSchemaType);
        
        $this->logger->debug(
            'ObjectService configured for reports',
            [
                'register' => $this->reportRegisterType,
                'schema'   => $this->reportSchemaType,
            ]
        );
        
        // Verify configuration was set correctly
        $currentRegister = $this->objectService->getRegister();
        $currentSchema = $this->objectService->getSchema();
        
        if ($currentRegister !== $this->reportRegisterType || $currentSchema !== $this->reportSchemaType) {
            $this->logger->warning(
                'ObjectService configuration mismatch after setting',
                [
                    'expected_register' => $this->reportRegisterType,
                    'actual_register'   => $currentRegister,
                    'expected_schema'   => $this->reportSchemaType,
                    'actual_schema'     => $currentSchema,
                ]
            );
        }
    }

}//end class
