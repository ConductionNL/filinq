<?php

/**
 * Document Service
 *
 * Orchestrates end-to-end document creation from templates: resolve data from
 * OpenRegister objects, merge into Twig/HTML templates, enforce huisstijl, and
 * produce output in PDF, ODF (.odt) or HTML format. Extends the lower-level
 * pdf-generation and template-management building blocks with a higher-level
 * workflow suitable for formal government documents (beschikkingen, brieven, etc.)
 *
 * Supports single generation, HTML preview, and bulk generation with async
 * processing for large batches (>10 objects).
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.DocuDesk.app
 *
 * @spec openspec/changes/document-creatie-sjablonen/tasks.md#task-1
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use Exception;
use RuntimeException;
use OCA\DocuDesk\BackgroundJob\BatchDocumentJob;
use OCP\App\IAppManager;
use OCP\BackgroundJob\IJobList;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for generating formal documents from templates and OpenRegister data
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/changes/document-creatie-sjablonen/tasks.md#task-1
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class DocumentService
{

    /**
     * Maximum number of objects for synchronous bulk processing.
     *
     * @var int
     */
    private const SYNC_BATCH_LIMIT = 10;

    /**
     * Default output format.
     *
     * @var string
     */
    private const DEFAULT_FORMAT = 'pdf';

    /**
     * Valid output formats.
     *
     * @var string[]
     */
    private const VALID_FORMATS = ['pdf', 'odf', 'html'];

    /**
     * Constructor for DocumentService.
     *
     * @param TemplateService     $templateService  Service for template CRUD
     * @param DataResolverService $dataResolver     Service for OpenRegister data resolution
     * @param TemplateRenderer    $templateRenderer Service for Twig rendering
     * @param PdfService          $pdfService       Service for PDF generation
     * @param ContainerInterface  $container        Container for dependency injection
     * @param IAppManager         $appManager       App manager interface
     * @param IJobList            $jobList          Nextcloud job list for async processing
     * @param LoggerInterface     $logger           Logger for error reporting
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
        private readonly LoggerInterface $logger
    ) {

    }//end __construct()

    /**
     * Get the ObjectService from OpenRegister.
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
     * Generate a single document from a template and data references.
     *
     * Resolves data from OpenRegister objects, applies huisstijl, renders
     * the Twig template, and produces output in the requested format.
     * Generated document metadata is stored in the document register for
     * audit trail (DCS-072).
     *
     * @param string $templateId The UUID of the template to use
     * @param array  $dataRefs   Data references: [{register, schema, id}, ...]
     * @param array  $options    Options: format (pdf|odf|html), huisstijlId,
     *                           zaakId, adHocData, pdfOptions, userId
     *
     * @return array{content: string, format: string, metadata: array, warnings: string[]}
     *
     * @throws Exception If generation fails
     *
     * @spec openspec/changes/document-creatie-sjablonen/tasks.md#task-1
     */
    public function generateDocument(
        string $templateId,
        array $dataRefs,
        array $options=[]
    ): array {
        $format = $options['format'] ?? self::DEFAULT_FORMAT;
        $this->validateFormat(format: $format);

        $template = $this->templateService->getTemplate(id: $templateId);

        $resolution = $this->dataResolver->resolve(
            dataRefs: $dataRefs,
            adHocData: ($options['adHocData'] ?? [])
        );
        $data       = $resolution['data'];
        $warnings   = $resolution['warnings'];

        foreach ($resolution['errors'] as $error) {
            $ref        = $error['register'].'/'.$error['schema'].'/'.$error['id'];
            $warnings[] = "Data resolution failed for {$ref}: {$error['message']}";
        }

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

        $content = $this->produceOutput(
            htmlContent: $htmlContent,
            format: $format,
            pdfOptions: $pdfOptions
        );

        $templateVersion = (int) ($template['version'] ?? 1);
        $metadata        = $this->logGeneratedDocument(
            templateId: $templateId,
            templateVersion: $templateVersion,
            templateName: ($template['name'] ?? ''),
            dataRefs: $dataRefs,
            format: $format,
            status: 'generated',
            warnings: $warnings,
            zaakId: ($options['zaakId'] ?? null),
            errorMessage: null,
            options: $options
        );

        return [
            'content'  => $content,
            'format'   => $format,
            'metadata' => $metadata,
            'warnings' => $warnings,
        ];

    }//end generateDocument()

    /**
     * Generate an HTML preview of a template without producing final output.
     *
     * Resolves data and renders the Twig template but returns plain HTML
     * without PDF/ODF conversion. No audit log entry is created for previews.
     *
     * @param string $templateId The UUID of the template to preview
     * @param array  $dataRefs   Data references: [{register, schema, id}, ...]
     * @param array  $options    Options: huisstijlId, adHocData
     *
     * @return array{html: string, warnings: string[]}
     *
     * @throws Exception If rendering fails
     *
     * @spec openspec/changes/document-creatie-sjablonen/tasks.md#task-1
     */
    public function generatePreview(
        string $templateId,
        array $dataRefs,
        array $options=[]
    ): array {
        $template = $this->templateService->getTemplate(id: $templateId);

        $resolution = $this->dataResolver->resolve(
            dataRefs: $dataRefs,
            adHocData: ($options['adHocData'] ?? [])
        );
        $data       = $resolution['data'];
        $warnings   = $resolution['warnings'];

        foreach ($resolution['errors'] as $error) {
            $ref        = $error['register'].'/'.$error['schema'].'/'.$error['id'];
            $warnings[] = "Data resolution failed for {$ref}: {$error['message']}";
        }

        $huisstijl   = $this->loadHuisstijl(huisstijlId: ($options['huisstijlId'] ?? null));
        $htmlContent = $this->renderWithHuisstijl(
            templateContent: $template['content'],
            data: $data,
            huisstijl: $huisstijl
        );

        return [
            'html'     => $htmlContent,
            'warnings' => $warnings,
        ];

    }//end generatePreview()

    /**
     * Generate documents for multiple objects in a single request.
     *
     * For batches <= SYNC_BATCH_LIMIT objects processing is synchronous.
     * For larger batches a queued background job is dispatched and a jobId
     * is returned so the caller can poll GET /api/documents/jobs/{jobId}.
     *
     * @param string $templateId The UUID of the template
     * @param array  $objectIds  Array of object UUIDs to generate for
     * @param array  $options    Options: register, schema, format, huisstijlId, userId
     *
     * @return array Synchronous: {results, total, completed, errors}
     *               Async: {jobId, status, total}
     *
     * @throws Exception If dispatch fails
     *
     * @spec openspec/changes/document-creatie-sjablonen/tasks.md#task-1
     */
    public function generateBulk(
        string $templateId,
        array $objectIds,
        array $options=[]
    ): array {
        $count = count($objectIds);

        if ($count <= self::SYNC_BATCH_LIMIT) {
            return $this->generateBulkSync(
                templateId: $templateId,
                objectIds: $objectIds,
                options: $options
            );
        }

        return $this->dispatchBulkJob(
            templateId: $templateId,
            objectIds: $objectIds,
            options: $options
        );

    }//end generateBulk()

    /**
     * Get the status of an async bulk document generation job.
     *
     * @param string $jobId The job UUID
     *
     * @return array|null The job status or null if not found
     *
     * @spec openspec/changes/document-creatie-sjablonen/tasks.md#task-1
     */
    public function getJobStatus(string $jobId): ?array
    {
        try {
            $config = $this->container->get(\OCP\IAppConfig::class);
            $value  = $config->getValueString(
                'docudesk',
                'document_job_'.$jobId,
                ''
            );

            if (empty($value) === true) {
                return null;
            }

            return json_decode($value, true);
        } catch (Exception $e) {
            $this->logger->error(
                message: 'Failed to load document job status: '.$e->getMessage(),
                context: ['jobId' => $jobId]
            );
            return null;
        }//end try

    }//end getJobStatus()

    /**
     * Update an async job status in the app config.
     *
     * @param string $jobId  The job UUID
     * @param array  $status The status data to store
     *
     * @return void
     *
     * @spec openspec/changes/document-creatie-sjablonen/tasks.md#task-1
     */
    public function updateJobStatus(string $jobId, array $status): void
    {
        try {
            $config = $this->container->get(\OCP\IAppConfig::class);
            $config->setValueString(
                'docudesk',
                'document_job_'.$jobId,
                json_encode($status)
            );
        } catch (Exception $e) {
            $this->logger->error(
                message: 'Failed to store document job status: '.$e->getMessage(),
                context: ['jobId' => $jobId]
            );
        }//end try

    }//end updateJobStatus()

    /**
     * Validate that the requested format is supported.
     *
     * @param string $format The output format
     *
     * @return void
     *
     * @throws Exception If the format is not supported
     */
    private function validateFormat(string $format): void
    {
        if (in_array(needle: $format, haystack: self::VALID_FORMATS, strict: true) === false) {
            $valid = implode(', ', self::VALID_FORMATS);
            throw new Exception(
                message: "Unsupported format '{$format}'. Valid formats: {$valid}",
                code: 400
            );
        }

    }//end validateFormat()

    /**
     * Load the huisstijl configuration from OpenRegister.
     *
     * @param string|null $huisstijlId UUID of the huisstijl object, or null
     *
     * @return array|null The huisstijl configuration or null if not configured
     */
    private function loadHuisstijl(?string $huisstijlId): ?array
    {
        if (empty($huisstijlId) === true) {
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
     * Build PDF generation options from template and huisstijl config.
     *
     * @param array      $template  The template object
     * @param array|null $huisstijl The huisstijl configuration
     * @param array      $options   The request options
     *
     * @return array The merged PDF options
     */
    private function buildPdfOptions(array $template, ?array $huisstijl, array $options): array
    {
        $pdfOptions = [
            'format'      => $template['format'] ?? 'A4',
            'orientation' => $template['orientation'] ?? 'P',
        ];

        if ($huisstijl !== null && isset($huisstijl['defaultMargins']) === true) {
            $pdfOptions['margin'] = $huisstijl['defaultMargins'];
        }

        if (isset($options['pdfOptions']) === true) {
            $pdfOptions = array_merge($pdfOptions, $options['pdfOptions']);
        }

        return $pdfOptions;

    }//end buildPdfOptions()

    /**
     * Render template content with optional huisstijl header and footer.
     *
     * @param string     $templateContent The Twig template content
     * @param array      $data            The data context
     * @param array|null $huisstijl       The huisstijl configuration
     *
     * @return string The rendered HTML
     *
     * @throws Exception If rendering fails
     */
    private function renderWithHuisstijl(
        string $templateContent,
        array $data,
        ?array $huisstijl
    ): string {
        $fullContent = '';

        if ($huisstijl !== null && empty($huisstijl['headerHtml']) === false) {
            $headerData   = array_merge($data, ['huisstijl' => $huisstijl]);
            $fullContent .= $this->templateRenderer->renderTemplate(
                templateContent: $huisstijl['headerHtml'],
                data: $headerData
            );
        }

        $fullContent .= $this->templateRenderer->renderTemplate(
            templateContent: $templateContent,
            data: $data
        );

        if ($huisstijl !== null && empty($huisstijl['footerHtml']) === false) {
            $footerData   = array_merge($data, ['huisstijl' => $huisstijl]);
            $fullContent .= $this->templateRenderer->renderTemplate(
                templateContent: $huisstijl['footerHtml'],
                data: $footerData
            );
        }

        return $fullContent;

    }//end renderWithHuisstijl()

    /**
     * Produce output in the requested format.
     *
     * @param string $htmlContent The rendered HTML content
     * @param string $format      The output format (pdf, odf, html)
     * @param array  $pdfOptions  The PDF generation options
     *
     * @return string The generated content (binary for pdf/odf, string for html)
     *
     * @throws Exception If output generation fails
     */
    private function produceOutput(string $htmlContent, string $format, array $pdfOptions): string
    {
        switch ($format) {
            case 'html':
                return $htmlContent;

            case 'odf':
                return $this->convertToOdf(
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
     * Convert HTML to ODF (.odt) using LibreOffice headless.
     *
     * @param string $htmlContent The HTML content to convert
     * @param array  $pdfOptions  The page configuration options (reserved)
     *
     * @return string The ODT binary content
     *
     * @throws Exception If LibreOffice is not available or conversion fails
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter) $pdfOptions reserved for future page config
     *
     * @psalm-suppress UnusedParam $pdfOptions reserved for future page config
     * @psalm-suppress ForbiddenCode shell_exec is required to locate the LibreOffice binary
     */
    private function convertToOdf(string $htmlContent, array $pdfOptions): string
    {
        $soffice = trim((string) shell_exec('which soffice 2>/dev/null'));
        if (empty($soffice) === true) {
            throw new Exception(
                message: 'ODF conversion service unavailable: LibreOffice is not installed',
                code: 503
            );
        }

        $tempDir = '/tmp/docudesk_odf_convert';
        if (file_exists($tempDir) === false) {
            mkdir($tempDir, 0700, true);
        }

        $tempFile = $tempDir.'/'.uniqid('odf_').'.html';
        file_put_contents($tempFile, $htmlContent);

        try {
            $outDir  = escapeshellarg($tempDir);
            $inFile  = escapeshellarg($tempFile);
            $command = escapeshellcmd($soffice)." --headless --convert-to odt --outdir {$outDir} {$inFile} 2>&1";

            $output     = [];
            $returnCode = 0;
            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                throw new Exception(
                    message: 'ODF conversion failed: '.implode("\n", $output),
                    code: 500
                );
            }

            $odtFile = preg_replace('/\.html$/', '.odt', $tempFile);
            if (file_exists($odtFile) === false) {
                throw new Exception(
                    message: 'ODF output file not found after conversion',
                    code: 500
                );
            }

            $content = file_get_contents($odtFile);
            unlink($odtFile);

            return $content;
        } finally {
            if (file_exists($tempFile) === true) {
                unlink($tempFile);
            }
        }//end try

    }//end convertToOdf()

    /**
     * Log a generated document to the document register for audit trail (DCS-072).
     *
     * Stores template UUID + version number per DCS-051, data sources, format,
     * status, and generating user.
     *
     * @param string      $templateId      The template UUID
     * @param int         $templateVersion The template version number
     * @param string      $templateName    The template name
     * @param array       $dataRefs        The data references used
     * @param string      $format          The output format
     * @param string      $status          'generated' or 'failed'
     * @param string[]    $warnings        Any warnings encountered
     * @param string|null $zaakId          Optional zaak UUID to link
     * @param string|null $errorMessage    Error message if status is failed
     * @param array       $options         The request options (for userId)
     *
     * @return array The created document register entry
     *
     * @spec openspec/changes/document-creatie-sjablonen/tasks.md#task-1
     */
    private function logGeneratedDocument(
        string $templateId,
        int $templateVersion,
        string $templateName,
        array $dataRefs,
        string $format,
        string $status,
        array $warnings,
        ?string $zaakId,
        ?string $errorMessage,
        array $options
    ): array {
        try {
            $objectService = $this->getObjectService();

            $entry = [
                'templateId'      => $templateId,
                'templateVersion' => $templateVersion,
                'templateName'    => $templateName,
                'dataRefs'        => $dataRefs,
                'format'          => $format,
                'status'          => $status,
                'generatedAt'     => date('c'),
                'generatedBy'     => $options['userId'] ?? '',
                'warnings'        => $warnings,
                'zaakId'          => $zaakId,
                'errorMessage'    => $errorMessage,
            ];

            $result = $objectService->saveObject(
                object: $entry,
                register: 'document',
                schema: 'generatedDocument'
            );

            if (is_object($result) === true
                && method_exists(object_or_class: $result, method: 'jsonSerialize') === true
            ) {
                return $result->jsonSerialize();
            }

            if (is_array($result) === true) {
                return $result;
            }

            return [];
        } catch (Exception $e) {
            $this->logger->error(
                message: 'Failed to log generated document: '.$e->getMessage(),
                context: [
                    'templateId'      => $templateId,
                    'templateVersion' => $templateVersion,
                    'status'          => $status,
                ]
            );
            return [];
        }//end try

    }//end logGeneratedDocument()

    /**
     * Process a bulk generation request synchronously.
     *
     * @param string $templateId The template UUID
     * @param array  $objectIds  Array of object UUIDs
     * @param array  $options    Generation options (register, schema, format, ...)
     *
     * @return array{results: array, total: int, completed: int, errors: int}
     */
    private function generateBulkSync(
        string $templateId,
        array $objectIds,
        array $options
    ): array {
        $register = $options['register'] ?? '';
        $schema   = $options['schema'] ?? '';
        $results  = [];

        foreach ($objectIds as $objectId) {
            $dataRefs = [
                [
                    'register' => $register,
                    'schema'   => $schema,
                    'id'       => $objectId,
                ],
            ];

            try {
                $result    = $this->generateDocument(
                    templateId: $templateId,
                    dataRefs: $dataRefs,
                    options: $options
                );
                $results[] = [
                    'objectId' => $objectId,
                    'status'   => 'success',
                    'content'  => $result['content'],
                    'warnings' => $result['warnings'],
                ];
            } catch (Exception $e) {
                $results[] = [
                    'objectId' => $objectId,
                    'status'   => 'error',
                    'error'    => $e->getMessage(),
                ];

                $this->logGeneratedDocument(
                    templateId: $templateId,
                    templateVersion: 0,
                    templateName: '',
                    dataRefs: $dataRefs,
                    format: ($options['format'] ?? self::DEFAULT_FORMAT),
                    status: 'failed',
                    warnings: [],
                    zaakId: null,
                    errorMessage: $e->getMessage(),
                    options: $options
                );
            }//end try
        }//end foreach

        $errorCount = count(
            array_filter(
                $results,
                static function (array $r): bool {
                    return $r['status'] === 'error';
                }
            )
        );

        return [
            'results'   => $results,
            'total'     => count($objectIds),
            'completed' => (count($objectIds) - $errorCount),
            'errors'    => $errorCount,
        ];

    }//end generateBulkSync()

    /**
     * Dispatch an async bulk generation background job.
     *
     * @param string $templateId The template UUID
     * @param array  $objectIds  Array of object UUIDs
     * @param array  $options    Generation options
     *
     * @return array{jobId: string, status: string, total: int}
     */
    private function dispatchBulkJob(
        string $templateId,
        array $objectIds,
        array $options
    ): array {
        $jobId = $this->generateJobId();

        $initialStatus = [
            'jobId'     => $jobId,
            'status'    => 'queued',
            'total'     => count($objectIds),
            'completed' => 0,
            'errors'    => 0,
            'results'   => [],
            'options'   => $options,
        ];
        $this->updateJobStatus(jobId: $jobId, status: $initialStatus);

        $this->jobList->add(
            job: BatchDocumentJob::class,
            argument: [
                'jobId'      => $jobId,
                'templateId' => $templateId,
                'objectIds'  => $objectIds,
                'options'    => $options,
            ]
        );

        return [
            'jobId'  => $jobId,
            'status' => 'queued',
            'total'  => count($objectIds),
        ];

    }//end dispatchBulkJob()

    /**
     * Generate a cryptographically secure job UUID.
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
