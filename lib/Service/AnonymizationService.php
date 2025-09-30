<?php
/**
 * Service for anonymizing sensitive information in documents
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
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;
use OCA\OpenRegister\Service\ObjectService;

/**
 * Service for anonymizing sensitive information in documents
 *
 * This service handles the anonymization of sensitive information in documents
 * using Presidio for entity detection and replacement. Anonymization results
 * are stored directly on the report object.
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
    private LoggerInterface $logger;

    /**
     * HTTP client for API requests
     *
     * @var Client
     */
    private Client $client;

    /**
     * Configuration service
     *
     * @var IConfig
     */
    private IConfig $config;

    /**
     * Object service for storing report data
     *
     * @var ObjectService
     */
    private ObjectService $objectService;

    /**
     * Extraction service for getting text from documents
     *
     * @var ExtractionService
     */
    private ExtractionService $extractionService;

    /**
     * User session for getting current user
     *
     * @var IUserSession
     */
    private IUserSession $userSession;

    /**
     * Reporting service for getting reports
     *
     * @var ReportingService
     */
    private ReportingService $reportingService;

    /**
     * App config for getting app config
     *
     * @var IAppConfig
     */
    private IAppConfig $appConfig;


    /**
     * Constructor for AnonymizationService
     *
     * @param LoggerInterface   $logger            Logger for error reporting
     * @param IConfig           $config            Configuration service
     * @param ObjectService     $objectService     Service for storing objects
     * @param ExtractionService $extractionService Service for extracting text from documents
     * @param IUserSession      $userSession       User session for getting current user
     * @param IAppConfig        $appConfig         App configuration service
     *
     * @return void
     */
    public function __construct(
        LoggerInterface $logger,
        IConfig $config,
        ObjectService $objectService,
        ExtractionService $extractionService,
        IUserSession $userSession,
        IAppConfig $appConfig
    ) {
        $this->logger            = $logger;
        $this->config            = $config;
        $this->objectService     = $objectService;
        $this->extractionService = $extractionService;
        $this->userSession       = $userSession;
        $this->appConfig         = $appConfig;

        // Initialize Guzzle HTTP client.
        $this->client = new Client(
            [
                'timeout'         => 30,
                'connect_timeout' => 5,
            ]
        );

    }//end __construct()


    /**
     * Set the reporting service
     *
     * This method is used to set the reporting service after construction
     * to avoid circular dependencies.
     *
     * @param ReportingService $reportingService Service for generating reports
     *
     * @return void
     *
     * @psalm-return   void
     * @phpstan-return void
     */
    public function setReportingService(ReportingService $reportingService): void
    {
        $this->reportingService = $reportingService;

    }//end setReportingService()


    /**
     * Anonymize a Word document by replacing detected entities in the document structure
     *
     * @param \OCP\Files\Node $node               The file node to anonymize
     * @param array           $processedEntities  The processed entities with replacement info
     * @param string          $anonymizedFileName The name for the anonymized file
     *
     * @return \OCP\Files\File The new anonymized file node
     *
     * @throws Exception If anonymization fails
     *
     * @psalm-return   \OCP\Files\File
     * @phpstan-return \OCP\Files\File
     */
    private function anonymizeWordDocument(
        \OCP\Files\Node $node,
        array $processedEntities,
        string $anonymizedFileName
    ): \OCP\Files\File {
        // Get the file content as a stream and save to a temp file.
        $stream   = $node->fopen('r');
        $tempFile = tempnam(sys_get_temp_dir(), 'docudesk_word_');
        if ($tempFile === false) {
            throw new Exception('Failed to create temporary file');
        }

        $tempStream = fopen($tempFile, 'w');
        if ($tempStream === false) {
            unlink($tempFile);
            throw new Exception('Failed to open temporary file for writing');
        }

        stream_copy_to_stream($stream, $tempStream);
        fclose($tempStream);
        fclose($stream);

        // Load the document.
        $phpWord = \PhpOffice\PhpWord\IOFactory::load($tempFile);

        // Helper: Replace text in all elements recursively.
        $replaceInElements = function (array $elements, array $replacements) use (&$replaceInElements) {
            foreach ($elements as $element) {
                // Replace in text runs.
                if (method_exists($element, 'getText') === true && method_exists($element, 'setText') === true) {
                    $text = $element->getText();
                    foreach ($replacements as $replacement) {
                        $text = str_ireplace($replacement['originalText'], $replacement['replacementText'], $text);
                    }

                    $element->setText($text);
                }

                // Replace in tables.
                if (method_exists($element, 'getRows') === true) {
                    foreach ($element->getRows() as $row) {
                        foreach ($row->getCells() as $cell) {
                            $replaceInElements($cell->getElements(), $replacements);
                        }
                    }
                }

                // Replace in lists.
                if (method_exists($element, 'getItems') === true) {
                    foreach ($element->getItems() as $item) {
                        $replaceInElements($item->getElements(), $replacements);
                    }
                }

                // Replace in nested elements.
                if (method_exists($element, 'getElements') === true) {
                    $replaceInElements($element->getElements(), $replacements);
                }
            }//end foreach
        };

        // Build replacements array.
        $replacements = [];
        foreach ($processedEntities as $entity) {
            $replacements[] = [
                'originalText'    => $entity['text'],
                'replacementText' => '['.$entity['entityType'].': '.$entity['key'].']',
            ];
        }

        // Replace in headers.
        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getHeaders() as $header) {
                $replaceInElements($header->getElements(), $replacements);
            }
        }

        // Replace in main content.
        foreach ($phpWord->getSections() as $section) {
            $replaceInElements($section->getElements(), $replacements);
        }

        // Replace in footers.
        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getFooters() as $footer) {
                $replaceInElements($footer->getElements(), $replacements);
            }
        }

        // Save the anonymized document to a new temp file.
        $anonymizedTempFile = tempnam(sys_get_temp_dir(), 'docudesk_word_anon_');
        \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007')->save($anonymizedTempFile);

        // Get the parent folder and create the new file.
        $parentFolder = $node->getParent();
        if ($parentFolder->nodeExists($anonymizedFileName) === true) {
            $parentFolder->get($anonymizedFileName)->delete();
        }

        $anonymizedStream = fopen($anonymizedTempFile, 'r');
        $newFile          = $parentFolder->newFile($anonymizedFileName, $anonymizedStream);
        // Do NOT call fclose($anonymizedStream) here; Nextcloud handles the stream lifecycle internally.
        // Clean up temp files.
        unlink($tempFile);
        unlink($anonymizedTempFile);

        return $newFile;

    }//end anonymizeWordDocument()


    /**
     * Process anonymization for a document based on a report
     *
     * This method creates an anonymized file and stores the anonymization results
     * directly on the report object.
     *
     * @param \OCP\Files\Node           $node   The file node to anonymize
     * @param array<string, mixed>|null $report The report containing detected entities (optional)
     *
     * @return array<string, mixed> The updated report with anonymization results
     *
     * @throws Exception If anonymization fails
     *
     * @psalm-return   array<string, mixed>
     * @phpstan-return array<string, mixed>
     */
    public function processAnonymization(\OCP\Files\Node $node, ?array $report=null): array
    {
        $startTime = microtime(true);

        // Create a new file name with "_anonymized" suffix.
        $fileName      = $node->getName();
        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
        $fileNameWithoutExtension = pathinfo($fileName, PATHINFO_FILENAME);

        // If the file is already anonymized, skip processing and return report.
        if (str_ends_with($fileNameWithoutExtension, '_anonymized') === true) {
            $this->logger->info('Skipping anonymization for file already ending with _anonymized: '.$fileName);
            return $report ?? [];
        }

        // If no report is provided, try to get one.
        if ($report === null) {
            $this->logger->debug('No report provided, trying to get existing report');

            // Try to get existing report.
            $report = $this->reportingService->getReport($node);

            // If no report exists, create one.
            if ($report === null) {
                $this->logger->debug('No existing report found, creating new report');
                $report = $this->reportingService->createReport($node);
            }

            // If report is not completed, process it.
            if ($report['status'] !== 'completed') {
                $this->logger->debug('Report not completed, processing report');
                $report = $this->reportingService->processReport($report);
            }
        }

        // Create a new file name with "_anonymized" suffix.
        $anonymizedFileName = $fileNameWithoutExtension.'_anonymized';
        if (empty($fileExtension) === false) {
            $anonymizedFileName .= '.'.$fileExtension;
        }

        // Use ETag as file hash if available, otherwise calculate hash.
        $fileHash = null;
        if (method_exists($node, 'getEtag') === true) {
            $fileHash = $node->getEtag();
            $this->logger->debug('Using ETag as file hash: '.$fileHash);
        } else {
            // Fall back to calculating hash.
            $fileHash = $this->reportingService->calculateFileHash($node->getPath());
        }

        // Initialize anonymization result if not exists or if file changed.
        if (isset($report['anonymization']) === false 
            || ($report['anonymization']['fileHash'] ?? null) !== $fileHash
        ) {
            $report['anonymization'] = [
                'fileHash'           => $fileHash,
                'anonymizedFileName' => '',
                'anonymizedFilePath' => '',
                'replacements'       => [],
                'startTime'          => $startTime,
                'endTime'            => null,
                'processingTime'     => null,
                'status'             => 'pending',
                'message'            => '',
            ];
        } else if ($report['anonymization']['status'] === 'completed') {
            // Return existing anonymization if already completed and file hasn't changed.
            $this->logger->debug(
                'File hash matches existing anonymization, returning cached result',
                [
                    'fileHash' => $fileHash,
                    'reportId' => $report['id'] ?? null,
                ]
            );
            $report['anonymization']['message'] = 'File hash matches existing anonymization, returning cached result';
            return $report;
        }

        // Check if anonymization is needed (if there are entities).
        if (empty($report['entities']) === true) {
            $this->logger->info('No entities detected for anonymization in document: '.$node->getPath());

            // Update anonymization result.
            $report['anonymization']['status']         = 'completed';
            $report['anonymization']['message']        = 'No entities detected for anonymization in document: '.$node->getPath();
            $report['anonymization']['endTime']        = microtime(true);
            $report['anonymization']['processingTime'] = $report['anonymization']['endTime'] - $startTime;

            // Save the updated report.
            // Ensure ObjectService is configured for reports before saving
            $this->ensureReportConfiguration();
            $this->objectService->saveObject(object: $report, uuid: $report['id'] ?? null);

            return $report;
        }

        // Update anonymization status to processing.
        $report['anonymization']['status'] = 'processing';

        // Save the updated report.
        // Ensure ObjectService is configured for reports before saving
        $this->ensureReportConfiguration();
        $this->objectService->saveObject(object: $report, uuid: $report['id'] ?? null);

        // Process entities and find their positions in the content if not provided.
        $processedEntities = [];
        foreach ($report['entities'] as $entity) {
            $entityType = $entity['entityType'] ?? 'UNKNOWN';
            $entityText = $entity['text'] ?? '';
            $score      = $entity['score'] ?? 0;
            $entityKey  = $entity['key'] ?? substr(\Symfony\Component\Uid\Uuid::v4()->toRfc4122(), 0, 8);
            $anonymize  = $entity['anonymize'] ?? true;
            
            if (empty($entityText) === true) {
                continue;
            }

            // Only process entities that should be anonymized
            if ($anonymize === true) {
                $processedEntities[] = [
                    'entityType' => $entityType,
                    'text'       => $entityText,
                    'score'      => $score,
                    'key'        => $entityKey,
                ];
            }
        }

        // If the file is a Word document, anonymize using PhpWord.
        if (in_array(strtolower($fileExtension), ['doc', 'docx'], true) === true) {
            $newFile = $this->anonymizeWordDocument($node, $processedEntities, $anonymizedFileName);
        } else {
            // For other file types, use the old logic.
            $content = $node->getContent();
            if (empty($content) === true) {
                throw new Exception('Failed to get content from file: '.$node->getPath());
            }

            $anonymizedContent = $content;
            foreach ($processedEntities as $entity) {
                $anonymizedContent = str_ireplace(
                    $entity['text'],
                    '['.$entity['entityType'].': '.$entity['key'].']',
                    $anonymizedContent
                );
            }

            $parentFolder = $node->getParent();
            if ($parentFolder->nodeExists($anonymizedFileName) === true) {
                $parentFolder->get($anonymizedFileName)->delete();
            }

            $newFile = $parentFolder->newFile($anonymizedFileName, $anonymizedContent);
        }//end if

        // Update anonymization object.
        $endTime = microtime(true);
        $report['anonymization']['status']             = 'completed';
        $report['anonymization']['message']            = 'Anonymization completed successfully';
        $report['anonymization']['replacements']       = $processedEntities;
        $report['anonymization']['anonymizedFileName'] = $anonymizedFileName;
        $report['anonymization']['anonymizedFilePath'] = $newFile->getPath();
        $report['anonymization']['endTime']            = $endTime;
        $report['anonymization']['processingTime']     = $endTime - $startTime;

        // Save the updated report.
        // Ensure ObjectService is configured for reports before saving
        $this->ensureReportConfiguration();
        $this->objectService->saveObject(object: $report, uuid: $report['id'] ?? null);
        
        return $report;

    }//end processAnonymization()


    /**
     * Get anonymization results for a report
     *
     * This method retrieves the anonymization data from a report object.
     *
     * @param array $report The report object
     *
     * @return array<string, mixed>|null The anonymization data or null if not found
     *
     * @psalm-return   array<string, mixed>|null
     * @phpstan-return array<string, mixed>|null
     */
    public function getAnonymizationFromReport(array $report): ?array
    {
        return $report['anonymization'] ?? null;

    }//end getAnonymizationFromReport()


    /**
     * Check if a report has been anonymized
     *
     * @param array $report The report object
     *
     * @return bool True if the report has been anonymized, false otherwise
     *
     * @psalm-return   bool
     * @phpstan-return bool
     */
    public function isReportAnonymized(array $report): bool
    {
        $anonymization = $this->getAnonymizationFromReport($report);
        return $anonymization !== null && ($anonymization['status'] ?? '') === 'completed';

    }//end isReportAnonymized()


    /**
     * Ensure ObjectService is configured for reports
     *
     * This method ensures that the ObjectService is properly configured
     * for report operations when saving reports from AnonymizationService.
     *
     * @return void
     *
     * @psalm-return   void
     * @phpstan-return void
     */
    private function ensureReportConfiguration(): void
    {
        // Get report configuration from app config (same as ReportingService)
        $reportRegisterType = $this->appConfig->getValueString('docudesk', 'report_register', 'document');
        $reportSchemaType   = $this->appConfig->getValueString('docudesk', 'report_schema', 'report');
        
        // Reset ObjectService to report configuration
        $this->objectService->setRegister($reportRegisterType);
        $this->objectService->setSchema($reportSchemaType);
        
        $this->logger->debug(
            'ObjectService configured for reports in AnonymizationService',
            [
                'register' => $reportRegisterType,
                'schema'   => $reportSchemaType,
            ]
        );
    }

}//end class 