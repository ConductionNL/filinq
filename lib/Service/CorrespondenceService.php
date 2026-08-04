<?php
/**
 * Correspondence Service
 *
 * Orchestrates end-to-end correspondence generation: fetch template,
 * resolve recipient data, render with huisstijl, produce output in
 * multiple formats, and log to the correspondence register.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-batch-correspondence-rest-endpoints
 * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-output-format-selection
 * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-correspondence-generation-api
 * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-batch-correspondence-generation
 * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-huisstijl-default-configuration
 * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-email-body-generation
 * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-correspondence-register-logging
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use Exception;
use RuntimeException;
use OCP\App\IAppManager;
use OCP\BackgroundJob\IJobList;
use OCP\IAppConfig;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for generating correspondence from templates with recipient data
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 *
 * @spec openspec/changes/letter-correspondence-generation/tasks.md#task-2
 */
class CorrespondenceService
{

    /**
     * Maximum number of recipients for synchronous batch processing
     *
     * @var int
     */
    private const SYNC_BATCH_LIMIT = 10;

    /**
     * Default output format
     *
     * @var string
     */
    private const DEFAULT_FORMAT = 'pdf';

    /**
     * Valid output formats
     *
     * @var string[]
     */
    private const VALID_FORMATS = ['pdf', 'docx', 'html', 'email'];

    /**
     * Constructor for CorrespondenceService
     *
     * @param TemplateService     $templateService  Service for template CRUD
     * @param DataResolverService $dataResolver     Service for data resolution
     * @param TemplateRenderer    $templateRenderer Service for Twig rendering
     * @param PdfService          $pdfService       Service for PDF generation
     * @param ContainerInterface  $container        Container for DI
     * @param IAppManager         $appManager       App manager interface
     * @param IJobList            $jobList          Nextcloud job list for async
     * @param LoggerInterface     $logger           Logger for error reporting
     * @param IAppConfig          $appConfig        App configuration accessor
     *
     * @return void
     */
    public function __construct(
        private readonly TemplateService $templateService,
        private readonly DataResolverService $dataResolver,
        private readonly TemplateRenderer $templateRenderer,
        private readonly PdfService $pdfService,
        private readonly ContainerInterface $container,
        private readonly IAppManager $appManager,
        private readonly IJobList $jobList,
        private readonly LoggerInterface $logger,
        private readonly IAppConfig $appConfig
    ) {

    }//end __construct()

    /**
     * Resolve the configured sync-batch limit, falling back to the constant.
     *
     * Reads `docudesk.correspondence.sync_batch_limit` (canonical key declared
     * in manifest.yaml under docudesk-adopt-or-abstractions task 11).
     *
     * @return int
     */
    private function getSyncBatchLimit(): int
    {
        $value = $this->appConfig->getValueString(
            'docudesk',
            'correspondence.sync_batch_limit',
            ''
        );
        if ($value !== '') {
            return (int) $value;
        }

        return self::SYNC_BATCH_LIMIT;

    }//end getSyncBatchLimit()

    /**
     * Resolve the configured default output format, falling back to the constant.
     *
     * Reads `docudesk.correspondence.default_format` (canonical key declared in
     * manifest.yaml under docudesk-adopt-or-abstractions task 11).
     *
     * @return string
     */
    private function getDefaultFormat(): string
    {
        $value = $this->appConfig->getValueString(
            'docudesk',
            'correspondence.default_format',
            ''
        );
        if ($value !== '') {
            return $value;
        }

        return self::DEFAULT_FORMAT;

    }//end getDefaultFormat()

    /**
     * Get the ObjectService from OpenRegister
     *
     * @return \OCA\OpenRegister\Service\ObjectService The ObjectService instance
     *
     * @throws RuntimeException If OpenRegister is not available
     */
    private function getObjectService(): \OCA\OpenRegister\Service\ObjectService
    {
        if (in_array(
            needle: 'openregister',
            haystack: $this->appManager->getInstalledApps(),
            strict: true
        ) === true
        ) {
            return $this->container->get('OCA\OpenRegister\Service\ObjectService');
        }

        throw new RuntimeException(message: 'OpenRegister service is not available.');

    }//end getObjectService()

    /**
     * Generate a single correspondence document
     *
     * Fetches the template, resolves recipient data, applies huisstijl,
     * renders the template, produces the output, and logs to the register.
     *
     * @param string $templateId The UUID of the template to use
     * @param array  $dataRefs   Array of data references with register/schema/id
     * @param array  $options    Options: format (pdf|docx|html|email),
     *                           huisstijlId, caseReference, recipientType, userId
     *
     * @return array{content: string, format: string, warnings: array, registerEntry: array}
     *
     * @throws Exception If generation fails
     *
     * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-correspondence-generation-api
     */
    public function generate(string $templateId, array $dataRefs, array $options=[]): array
    {
        $format = $options['format'] ?? $this->getDefaultFormat();
        $this->validateFormat(format: $format);

        // Fetch template.
        $template = $this->templateService->getTemplate(id: $templateId);

        // Resolve data from OpenRegister.
        $resolution = $this->dataResolver->resolve(
            dataRefs: $dataRefs,
            adHocData: ($options['adHocData'] ?? [])
        );
        $data       = $resolution['data'];
        $warnings   = $resolution['warnings'];

        // Check for resolution errors (add as warnings, don't abort).
        foreach ($resolution['errors'] as $error) {
            $ref        = $error['register'].'/'.$error['schema'].'/'.$error['id'];
            $warnings[] = "Data resolution failed for {$ref}: {$error['message']}";
        }

        // Apply huisstijl if configured.
        $huisstijl   = $this->loadHuisstijl(huisstijlId: ($options['huisstijlId'] ?? null));
        $pdfOptions  = $this->buildPdfOptions(
            template: $template,
            huisstijl: $huisstijl,
            options: $options
        );
        $htmlContent = $this->renderWithHuisstijl(
            templateContent: $template['content'],
            data: $data,
            huisstijl: $huisstijl
        );

        // Produce output in requested format.
        $content = $this->produceOutput(
            htmlContent: $htmlContent,
            format: $format,
            pdfOptions: $pdfOptions
        );

        // Log to correspondence register.
        $registerEntry = $this->logCorrespondence(
            templateId: $templateId,
            templateName: ($template['name'] ?? ''),
            dataRefs: $dataRefs,
            format: $format,
            status: 'generated',
            errorMessage: null,
            options: $options
        );

        return [
            'content'       => $content,
            'format'        => $format,
            'warnings'      => $warnings,
            'registerEntry' => $registerEntry,
        ];

    }//end generate()

    /**
     * Generate correspondence for a batch of recipients
     *
     * For batches of 10 or fewer, generation is synchronous.
     * For larger batches, a background job is dispatched.
     *
     * @param string $templateId   The UUID of the template to use
     * @param array  $recipientIds Array of recipient object UUIDs
     * @param array  $options      Options: format, register, schema, huisstijlId,
     *                             caseReference, recipientType, userId
     *
     * @return array Synchronous results or job info
     *
     * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-batch-correspondence-generation
     */
    public function generateBatch(
        string $templateId,
        array $recipientIds,
        array $options=[]
    ): array {
        $count = count($recipientIds);

        if ($count <= $this->getSyncBatchLimit()) {
            return $this->generateBatchSync(
                templateId: $templateId,
                recipientIds: $recipientIds,
                options: $options
            );
        }

        return $this->dispatchBatchJob(
            templateId: $templateId,
            recipientIds: $recipientIds,
            options: $options
        );

    }//end generateBatch()

    /**
     * Process a batch synchronously
     *
     * @param string $templateId   The template UUID
     * @param array  $recipientIds Array of recipient UUIDs
     * @param array  $options      Generation options
     *
     * @return array{results: array, total: int, completed: int, errors: int}
     *
     * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-batch-correspondence-generation
     */
    private function generateBatchSync(
        string $templateId,
        array $recipientIds,
        array $options
    ): array {
        $register = $options['register'] ?? '';
        $schema   = $options['schema'] ?? '';
        $results  = [];

        foreach ($recipientIds as $recipientId) {
            $dataRefs = [
                [
                    'register' => $register,
                    'schema'   => $schema,
                    'id'       => $recipientId,
                ],
            ];

            try {
                $result    = $this->generate(
                    templateId: $templateId,
                    dataRefs: $dataRefs,
                    options: $options
                );
                $results[] = [
                    'recipientId' => $recipientId,
                    'status'      => 'success',
                    'content'     => $result['content'],
                    'warnings'    => $result['warnings'],
                ];
            } catch (Exception $e) {
                $results[] = [
                    'recipientId' => $recipientId,
                    'status'      => 'error',
                    'error'       => $e->getMessage(),
                ];

                $this->logCorrespondence(
                    templateId: $templateId,
                    templateName: '',
                    dataRefs: $dataRefs,
                    format: ($options['format'] ?? $this->getDefaultFormat()),
                    status: 'failed',
                    errorMessage: $e->getMessage(),
                    options: $options
                );
            }//end try
        }//end foreach

        $errorCount = count(
            array_filter(
                $results,
                static function ($resultItem) {
                    return $resultItem['status'] === 'error';
                }
            )
        );

        return [
            'results'   => $results,
            'total'     => count($recipientIds),
            'completed' => (count($recipientIds) - $errorCount),
            'errors'    => $errorCount,
        ];

    }//end generateBatchSync()

    /**
     * Dispatch a batch generation as a background job
     *
     * @param string $templateId   The template UUID
     * @param array  $recipientIds Array of recipient UUIDs
     * @param array  $options      Generation options
     *
     * @return array{jobId: string, status: string, totalRecipients: int}
     *
     * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-batch-correspondence-generation
     */
    private function dispatchBatchJob(
        string $templateId,
        array $recipientIds,
        array $options
    ): array {
        $jobId = $this->generateJobId();

        $this->jobList->add(
            \OCA\DocuDesk\BackgroundJob\BatchCorrespondenceJob::class,
            [
                'jobId'        => $jobId,
                'templateId'   => $templateId,
                'recipientIds' => $recipientIds,
                'options'      => $options,
            ]
        );

        // Store initial job status in app config via container.
        // SB1 fix: persist ownerUserId at the top level so the controller's
        // ownership check can actually read it (options.userId is never stored
        // in mid-job progress updates, so the old check always read null).
        $this->storeJobStatus(
            jobId: $jobId,
            data: [
                'status'      => 'queued',
                'total'       => count($recipientIds),
                'completed'   => 0,
                'errors'      => 0,
                'results'     => [],
                'ownerUserId' => (string) ($options['userId'] ?? ''),
            ]
        );

        return [
            'jobId'           => $jobId,
            'status'          => 'queued',
            'totalRecipients' => count($recipientIds),
        ];

    }//end dispatchBatchJob()

    /**
     * Get the status of a batch job
     *
     * @param string $jobId The job UUID
     *
     * @return array|null The job status or null if not found
     *
     * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-batch-correspondence-rest-endpoints
     */
    public function getJobStatus(string $jobId): ?array
    {
        return $this->loadJobStatus(jobId: $jobId);

    }//end getJobStatus()

    /**
     * Update the status of a batch job
     *
     * @param string $jobId The job UUID
     * @param array  $data  The status data to store
     *
     * @return void
     *
     * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-batch-correspondence-generation
     */
    public function storeJobStatus(string $jobId, array $data): void
    {
        try {
            $container = $this->container;
            $config    = $container->get(\OCP\IAppConfig::class);
            $config->setValueString(
                'docudesk',
                'correspondence_job_'.$jobId,
                json_encode($data)
            );
        } catch (Exception $e) {
            $this->logger->error(
                message: 'Failed to store job status: '.$e->getMessage(),
                context: ['jobId' => $jobId]
            );
        }

    }//end storeJobStatus()

    /**
     * Load the status of a batch job from app config
     *
     * @param string $jobId The job UUID
     *
     * @return array|null The job status or null if not found
     *
     * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-batch-correspondence-rest-endpoints
     */
    private function loadJobStatus(string $jobId): ?array
    {
        try {
            $container = $this->container;
            $config    = $container->get(\OCP\IAppConfig::class);
            $value     = $config->getValueString(
                'docudesk',
                'correspondence_job_'.$jobId,
                ''
            );

            if (empty($value) === true) {
                return null;
            }

            return json_decode($value, true);
        } catch (Exception $e) {
            $this->logger->error(
                message: 'Failed to load job status: '.$e->getMessage(),
                context: ['jobId' => $jobId]
            );
            return null;
        }//end try

    }//end loadJobStatus()

    /**
     * Validate the requested output format
     *
     * @param string $format The format to validate
     *
     * @return void
     *
     * @throws Exception If the format is not valid
     *
     * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-output-format-selection
     */
    private function validateFormat(string $format): void
    {
        if (in_array($format, self::VALID_FORMATS, true) === false) {
            $validFormats = implode(', ', self::VALID_FORMATS);
            throw new Exception(
                message: "Invalid format: {$format}. Valid formats: {$validFormats}",
                code: 400
            );
        }

    }//end validateFormat()

    /**
     * Load huisstijl configuration from OpenRegister
     *
     * @param string|null $huisstijlId Optional specific huisstijl UUID
     *
     * @return array|null The huisstijl configuration or null if not found
     *
     * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-huisstijl-default-configuration
     */
    private function loadHuisstijl(?string $huisstijlId): ?array
    {
        if ($huisstijlId === null) {
            return null;
        }

        try {
            $objectService = $this->getObjectService();
            $result        = $objectService->find(
                id: $huisstijlId,
                register: 'document',
                schema: 'huisstijl'
            );

            if (empty($result) === true) {
                return null;
            }

            if (is_object($result) === true
                && method_exists(object_or_class: $result, method: 'jsonSerialize') === true
            ) {
                return $result->jsonSerialize();
            }

            return $result;
        } catch (Exception $e) {
            $this->logger->warning(
                message: 'Failed to load huisstijl: '.$e->getMessage(),
                context: ['huisstijlId' => $huisstijlId]
            );
            return null;
        }//end try

    }//end loadHuisstijl()

    /**
     * Build PDF options from template, huisstijl, and request options
     *
     * @param array      $template  The template object
     * @param array|null $huisstijl The huisstijl configuration
     * @param array      $options   The request options
     *
     * @return array The merged PDF options
     *
     * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-huisstijl-default-configuration
     */
    private function buildPdfOptions(array $template, ?array $huisstijl, array $options): array
    {
        $pdfOptions = [
            'format'      => $template['format'] ?? 'A4',
            'orientation' => $template['orientation'] ?? 'P',
        ];

        // Apply huisstijl margins if available.
        if ($huisstijl !== null && isset($huisstijl['defaultMargins']) === true) {
            $pdfOptions['margin'] = $huisstijl['defaultMargins'];
        }

        // Request options override.
        if (isset($options['pdfOptions']) === true) {
            $pdfOptions = array_merge($pdfOptions, $options['pdfOptions']);
        }

        return $pdfOptions;

    }//end buildPdfOptions()

    /**
     * Render template content with huisstijl header and footer
     *
     * @param string     $templateContent The Twig template content
     * @param array      $data            The data context
     * @param array|null $huisstijl       The huisstijl configuration
     *
     * @return string The rendered HTML
     *
     * @throws Exception If rendering fails
     *
     * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-huisstijl-default-configuration
     */
    private function renderWithHuisstijl(
        string $templateContent,
        array $data,
        ?array $huisstijl
    ): string {
        $fullContent = '';

        // Add huisstijl header if configured.
        if ($huisstijl !== null && empty($huisstijl['headerHtml']) === false) {
            $headerData   = array_merge($data, ['huisstijl' => $huisstijl]);
            $header       = $this->templateRenderer->renderTemplate(
                templateContent: $huisstijl['headerHtml'],
                data: $headerData
            );
            $fullContent .= $header;
        }

        // Render main content.
        $fullContent .= $this->templateRenderer->renderTemplate(
            templateContent: $templateContent,
            data: $data
        );

        // Add huisstijl footer if configured.
        if ($huisstijl !== null && empty($huisstijl['footerHtml']) === false) {
            $footerData   = array_merge($data, ['huisstijl' => $huisstijl]);
            $footer       = $this->templateRenderer->renderTemplate(
                templateContent: $huisstijl['footerHtml'],
                data: $footerData
            );
            $fullContent .= $footer;
        }

        return $fullContent;

    }//end renderWithHuisstijl()

    /**
     * Produce output in the requested format
     *
     * @param string $htmlContent The rendered HTML content
     * @param string $format      The output format (pdf, docx, html, email)
     * @param array  $pdfOptions  The PDF generation options
     *
     * @return string The generated content
     *
     * @throws Exception If output generation fails
     *
     * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-output-format-selection
     */
    private function produceOutput(string $htmlContent, string $format, array $pdfOptions): string
    {
        switch ($format) {
            case 'html':
                return $htmlContent;

            case 'email':
                return $this->stripPageStyling(html: $htmlContent);

            case 'docx':
                return $this->convertToDocx(
                    htmlContent: $htmlContent,
                    pdfOptions: $pdfOptions
                );

            case 'pdf':
            default:
                return $this->pdfService->renderPdf(
                    templateContent: $htmlContent,
                    data: [],
                    options: $pdfOptions
                );
        }//end switch

    }//end produceOutput()

    /**
     * Strip page-specific CSS for email output
     *
     * Removes @page rules, fixed dimensions, and other print-specific CSS
     * to produce clean HTML suitable for email clients.
     *
     * @param string $html The rendered HTML content
     *
     * @return string Clean HTML for email use
     *
     * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-email-body-generation
     */
    private function stripPageStyling(string $html): string
    {
        // Remove @page rules.
        $html = preg_replace('/@page\s*\{[^}]*\}/s', '', $html);

        // Remove fixed width/height on body.
        $html = preg_replace('/\b(width|height)\s*:\s*\d+mm\b/i', '', $html);

        return $html;

    }//end stripPageStyling()

    /**
     * Convert HTML to DOCX using LibreOffice headless
     *
     * @param string $htmlContent The HTML content to convert
     * @param array  $pdfOptions  The page configuration options
     *
     * @return string The DOCX binary content
     *
     * @throws Exception If LibreOffice is not available or conversion fails
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter) $pdfOptions reserved for future page config
     *
     * @psalm-suppress UnusedParam $pdfOptions reserved for future page config
     * @psalm-suppress ForbiddenCode shell_exec is required to locate the LibreOffice binary
     *
     * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-output-format-selection
     */
    private function convertToDocx(string $htmlContent, array $pdfOptions): string
    {
        // Check if LibreOffice is available.
        $soffice = trim(shell_exec('which soffice 2>/dev/null') ?? '');
        if (empty($soffice) === true) {
            throw new Exception(
                message: 'DOCX conversion service unavailable: LibreOffice is not installed',
                code: 503
            );
        }

        // Write HTML to temp file.
        $tempDir = '/tmp/docudesk_convert';
        if (file_exists($tempDir) === false) {
            mkdir($tempDir, 0777, true);
        }

        $tempFile = $tempDir.'/'.uniqid('conv_').'.html';
        file_put_contents($tempFile, $htmlContent);

        try {
            $outDir  = escapeshellarg($tempDir);
            $inFile  = escapeshellarg($tempFile);
            $command = escapeshellcmd($soffice)." --headless --convert-to docx --outdir {$outDir} {$inFile} 2>&1";

            $output     = [];
            $returnCode = 0;
            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                throw new Exception(
                    message: 'DOCX conversion failed: '.implode("\n", $output),
                    code: 500
                );
            }

            $docxFile = preg_replace('/\.html$/', '.docx', $tempFile);
            if (file_exists($docxFile) === false) {
                throw new Exception(
                    message: 'DOCX output file not found after conversion',
                    code: 500
                );
            }

            $content = file_get_contents($docxFile);
            unlink($docxFile);

            return $content;
        } finally {
            if (file_exists($tempFile) === true) {
                unlink($tempFile);
            }
        }//end try

    }//end convertToDocx()

    /**
     * Log correspondence generation to the document register
     *
     * @param string      $templateId   The template UUID
     * @param string      $templateName The template name
     * @param array       $dataRefs     The data references used
     * @param string      $format       The output format
     * @param string      $status       The generation status (generated|failed)
     * @param string|null $errorMessage Error message if status is failed
     * @param array       $options      The request options
     *
     * @return array The created correspondence register entry
     *
     * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-correspondence-register-logging
     */
    private function logCorrespondence(
        string $templateId,
        string $templateName,
        array $dataRefs,
        string $format,
        string $status,
        ?string $errorMessage,
        array $options
    ): array {
        try {
            $objectService = $this->getObjectService();

            $recipientId = '';
            if (empty($dataRefs) === false && isset($dataRefs[0]['id']) === true) {
                $recipientId = $dataRefs[0]['id'];
            }

            $entry = [
                'templateId'    => $templateId,
                'templateName'  => $templateName,
                'recipientId'   => $recipientId,
                'recipientType' => $options['recipientType'] ?? '',
                'caseReference' => $options['caseReference'] ?? '',
                'generatedAt'   => date('c'),
                'format'        => $format,
                'status'        => $status,
                'generatedBy'   => $options['userId'] ?? '',
                'errorMessage'  => $errorMessage,
            ];

            $result = $objectService->saveObject(
                object: $entry,
                register: 'document',
                schema: 'correspondence'
            );

            if (is_object($result) === true
                && method_exists(object_or_class: $result, method: 'jsonSerialize') === true
            ) {
                return $result->jsonSerialize();
            }

            return $result;
        } catch (Exception $e) {
            $this->logger->error(
                message: 'Failed to log correspondence: '.$e->getMessage(),
                context: ['templateId' => $templateId, 'status' => $status]
            );
            return [];
        }//end try

    }//end logCorrespondence()

    /**
     * Generate a unique job ID using a cryptographically secure source
     *
     * Uses random_bytes() (CSPRNG) rather than mt_rand() so job IDs cannot
     * be predicted by an attacker who knows the approximate creation time
     * (C3 security fix).
     *
     * @return string A RFC-4122 v4 UUID job identifier
     */
    private function generateJobId(): string
    {
        $data    = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));

    }//end generateJobId()
}//end class
