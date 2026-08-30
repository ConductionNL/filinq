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
 * @package  OCA\Filinq\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/document-creatie-sjablonen/tasks.md#task-1
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use Exception;
use OCA\Filinq\BackgroundJob\BatchDocumentJob;
use OCP\BackgroundJob\IJobList;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for generating formal documents from templates and OpenRegister data
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/document-creatie-sjablonen/tasks.md#task-1
 */
class DocumentService {

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
	 * Default output destination mode.
	 *
	 * @var string
	 */
	private const DEFAULT_OUTPUT_MODE = 'return';

	/**
	 * Valid output destination modes.
	 *
	 * @var string[]
	 */
	private const VALID_OUTPUT_MODES = ['return', 'files', 'both'];

	/**
	 * Default storage folder prefix (a template's namespace is appended).
	 *
	 * @var string
	 *
	 * ⚠️ STILL `DocuDesk`, DELIBERATELY, ACROSS THE FILINQ RENAME. This is a
	 * real FOLDER NAME in the user's Nextcloud Files tree — every document this
	 * app has ever generated or stored lives under `<user>/files/DocuDesk/…`.
	 * Renaming the constant does not move a single file: the app simply starts
	 * writing into a new, empty `Filinq/` folder while every existing document
	 * is orphaned where nothing looks for it any more. Same class of failure as
	 * renaming an OpenRegister register slug, and equally invisible — there is
	 * no error, the folder is just empty. Moving the folder needs its own
	 * repair step that renames the node per user.
	 */
	private const DEFAULT_OUTPUT_FOLDER_PREFIX = 'DocuDesk';

	/**
	 * Constructor for DocumentService.
	 *
	 * @param TemplateService $templateService Service for template CRUD
	 * @param DataResolverService $dataResolver Service for OpenRegister data resolution
	 * @param DocumentRenderPipeline $renderPipeline Huisstijl + Twig rendering and output production
	 * @param DocumentStorageService $storageService Service for storing output in Files
	 * @param GeneratedDocumentLogger $documentLogger Audit-trail writer for generated documents
	 * @param ContainerInterface $container Container for dependency injection
	 * @param IJobList $jobList Nextcloud job list for async processing
	 * @param LoggerInterface $logger Logger for error reporting
	 *
	 * @return void
	 */
	public function __construct(
		private readonly TemplateService $templateService,
		private readonly DataResolverService $dataResolver,
		private readonly DocumentRenderPipeline $renderPipeline,
		private readonly DocumentStorageService $storageService,
		private readonly GeneratedDocumentLogger $documentLogger,
		private readonly ContainerInterface $container,
		private readonly IJobList $jobList,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Generate a single document from a template and data references.
	 *
	 * Resolves data from OpenRegister objects, applies huisstijl, renders
	 * the Twig template, and produces output in the requested format.
	 * Generated document metadata is stored in the document register for
	 * audit trail (DCS-072).
	 *
	 * @param string $templateId The UUID of the template to use
	 * @param array $dataRefs Data references: [{register, schema, id}, ...]
	 * @param array $options Options: format (pdf|odf|html), huisstijlId,
	 *                       zaakId, adHocData, listRefs, pdfOptions, userId,
	 *                       filename, output.
	 *                       listRefs: [{register, schema, filter?, limit?,
	 *                       order?, as?}, ...] — each resolves to an array
	 *                       of objects under the Twig context key 'as'
	 *                       (default: schema + '_list')
	 *                       output: {mode?: return|files|both, targetPath?}
	 *                       — defaults to mode 'return' (byte-identical to
	 *                       this method's behaviour before output support
	 *                       existed)
	 *
	 * @return array{content: string, format: string, metadata: array, warnings: string[], output: array}
	 *
	 * @throws Exception If generation fails
	 *
	 * @spec openspec/changes/document-creatie-sjablonen/tasks.md#task-1
	 * @spec openspec/changes/document-generation-list-refs/specs/document-creatie-sjablonen/spec.md
	 * @spec openspec/changes/document-output-destinations-and-bulk-retention/specs/document-creatie-sjablonen/spec.md
	 */
	public function generateDocument(
		string $templateId,
		array $dataRefs,
		array $options = [],
	): array {
		$format = $options['format'] ?? self::DEFAULT_FORMAT;
		$this->validateFormat(format: $format);
		$outputMode = $this->resolveOutputMode(options: $options);

		$template = $this->templateService->getTemplate(id: $templateId);

		$resolution = $this->dataResolver->resolve(
			dataRefs: $dataRefs,
			listRefs: ($options['listRefs'] ?? []),
			adHocData: ($options['adHocData'] ?? [])
		);
		$data = $resolution['data'];
		$warnings = $resolution['warnings'];

		foreach ($resolution['errors'] as $error) {
			$ref = $error['register'] . '/' . $error['schema'] . '/' . $error['id'];
			$warnings[] = "Data resolution failed for {$ref}: {$error['message']}";
		}

		$huisstijl = $this->renderPipeline->loadHuisstijl(huisstijlId: ($options['huisstijlId'] ?? null));
		$pdfOptions = $this->renderPipeline->buildPdfOptions(
			template: $template,
			huisstijl: $huisstijl,
			options: $options
		);
		$renderResult = $this->renderPipeline->renderWithHuisstijl(
			templateContent: $template['content'],
			data: $data,
			huisstijl: $huisstijl
		);
		$htmlContent = $renderResult['html'];
		$warnings = array_merge($warnings, $renderResult['warnings']);

		$content = $this->renderPipeline->produceOutput(
			htmlContent: $htmlContent,
			format: $format,
			pdfOptions: $pdfOptions
		);

		$stored = $this->storeOutputIfRequested(
			mode: $outputMode,
			templateId: $templateId,
			template: $template,
			format: $format,
			content: $content,
			options: $options,
			warnings: $warnings
		);
		$warnings = $stored['warnings'];

		$metadata = $this->documentLogger->log(
			template: [
				'id' => $templateId,
				'version' => (int)($template['version'] ?? 1),
				'name' => ($template['name'] ?? ''),
			],
			dataRefs: $dataRefs,
			format: $format,
			outcome: [
				'status' => 'generated',
				'warnings' => $warnings,
				'caseId' => ($options['caseId'] ?? null),
				'errorMessage' => null,
				'fileId' => $stored['fileId'],
				'filePath' => $stored['path'],
			],
			userId: (string)($options['userId'] ?? '')
		);

		return [
			'content' => $content,
			'format' => $format,
			'metadata' => $metadata,
			'warnings' => $warnings,
			'output' => [
				'mode' => $outputMode,
				'fileId' => $stored['fileId'],
				'path' => $stored['path'],
				'name' => $stored['name'],
				'size' => $stored['size'],
			],
		];

	}//end generateDocument()

	/**
	 * Generate an HTML preview of a template without producing final output.
	 *
	 * Resolves data and renders the Twig template but returns plain HTML
	 * without PDF/ODF conversion. No audit log entry is created for previews.
	 *
	 * @param string $templateId The UUID of the template to preview
	 * @param array $dataRefs Data references: [{register, schema, id}, ...]
	 * @param array $options Options: huisstijlId, adHocData, listRefs.
	 *                       listRefs: [{register, schema, filter?, limit?,
	 *                       order?, as?}, ...] — each resolves to an array
	 *                       of objects under the Twig context key 'as'
	 *                       (default: schema + '_list')
	 *
	 * @return array{html: string, warnings: string[]}
	 *
	 * @throws Exception If rendering fails
	 *
	 * @spec openspec/changes/document-creatie-sjablonen/tasks.md#task-1
	 * @spec openspec/changes/document-generation-list-refs/specs/document-creatie-sjablonen/spec.md
	 */
	public function generatePreview(
		string $templateId,
		array $dataRefs,
		array $options = [],
	): array {
		$template = $this->templateService->getTemplate(id: $templateId);

		$resolution = $this->dataResolver->resolve(
			dataRefs: $dataRefs,
			listRefs: ($options['listRefs'] ?? []),
			adHocData: ($options['adHocData'] ?? [])
		);
		$data = $resolution['data'];
		$warnings = $resolution['warnings'];

		foreach ($resolution['errors'] as $error) {
			$ref = $error['register'] . '/' . $error['schema'] . '/' . $error['id'];
			$warnings[] = "Data resolution failed for {$ref}: {$error['message']}";
		}

		$huisstijl = $this->renderPipeline->loadHuisstijl(huisstijlId: ($options['huisstijlId'] ?? null));
		$renderResult = $this->renderPipeline->renderWithHuisstijl(
			templateContent: $template['content'],
			data: $data,
			huisstijl: $huisstijl
		);
		$warnings = array_merge($warnings, $renderResult['warnings']);

		return [
			'html' => $renderResult['html'],
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
	 * Note: `options.listRefs` is still NOT supported on this path. As of
	 * document-output-destinations-and-bulk-retention the async job no
	 * longer discards its per-object output (see REQ-DDOB-006) — the
	 * original justification for excluding listRefs (nowhere for a
	 * per-object-resolved collection to end up) no longer holds — but
	 * wiring listRefs through bulk was intentionally left out of this
	 * change's scope; it remains unimplemented pending a real use case.
	 * See openspec/changes/document-generation-list-refs/proposal.md and
	 * openspec/changes/document-output-destinations-and-bulk-retention/proposal.md.
	 *
	 * For batches larger than SYNC_BATCH_LIMIT (async), `options.output.mode`
	 * MUST be exactly 'files' — the generated bytes have nowhere to go once
	 * the job is queued and today's synchronous HTTP response has already
	 * returned, so 'return' (default) and 'both' are both rejected with
	 * HTTP 400 (REQ-DDOB-005).
	 *
	 * @param string $templateId The UUID of the template
	 * @param array $objectIds Array of object UUIDs to generate for
	 * @param array $options Options: register, schema, format, huisstijlId,
	 *                       userId, output. For batches >SYNC_BATCH_LIMIT,
	 *                       options.output.mode must be 'files'.
	 *
	 * @return array Synchronous: {results, total, completed, errors}
	 *               Async: {jobId, status, total}
	 *
	 * @throws Exception If dispatch fails, or if an async batch does not
	 *                   request options.output.mode 'files'
	 *
	 * @spec openspec/changes/document-creatie-sjablonen/tasks.md#task-1
	 * @spec openspec/changes/document-output-destinations-and-bulk-retention/specs/document-creatie-sjablonen/spec.md#req-ddob-005
	 */
	public function generateBulk(
		string $templateId,
		array $objectIds,
		array $options = [],
	): array {
		$count = count($objectIds);

		if ($count <= self::SYNC_BATCH_LIMIT) {
			return $this->generateBulkSync(
				templateId: $templateId,
				objectIds: $objectIds,
				options: $options
			);
		}

		$this->validateAsyncOutputMode(options: $options);

		return $this->dispatchBulkJob(
			templateId: $templateId,
			objectIds: $objectIds,
			options: $options
		);

	}//end generateBulk()

	/**
	 * Validate that an async (>SYNC_BATCH_LIMIT) bulk request requests
	 * options.output.mode 'files' — the only mode that makes sense once
	 * the job is queued and the HTTP response has already returned.
	 *
	 * @param array $options The request options
	 *
	 * @return void
	 *
	 * @throws Exception Code 400 if options.output.mode is not exactly 'files'
	 *
	 * @spec openspec/changes/document-output-destinations-and-bulk-retention/specs/document-creatie-sjablonen/spec.md#req-ddob-005
	 */
	private function validateAsyncOutputMode(array $options): void {
		$mode = $this->resolveOutputMode(options: $options);

		if ($mode !== 'files') {
			throw new Exception(
				message: 'Asynchronous bulk generation (more than ' . self::SYNC_BATCH_LIMIT . ' objects) '
					. 'discards generated output unless options.output.mode is "files". '
					. 'Set options.output.mode="files" and retry.',
				code: 400
			);
		}

	}//end validateAsyncOutputMode()

	/**
	 * Get the status of an async bulk document generation job.
	 *
	 * @param string $jobId The job UUID
	 *
	 * @return array|null The job status or null if not found
	 *
	 * @spec openspec/changes/document-creatie-sjablonen/tasks.md#task-1
	 */
	public function getJobStatus(string $jobId): ?array {
		try {
			$config = $this->container->get(\OCP\IAppConfig::class);
			$value = $config->getValueString(
				'filinq',
				'document_job_' . $jobId,
				''
			);

			if (empty($value) === true) {
				return null;
			}

			return json_decode($value, true);
		} catch (Exception $e) {
			$this->logger->error(
				message: 'Failed to load document job status: ' . $e->getMessage(),
				context: ['jobId' => $jobId]
			);
			return null;
		}//end try

	}//end getJobStatus()

	/**
	 * Update an async job status in the app config.
	 *
	 * @param string $jobId The job UUID
	 * @param array $status The status data to store
	 *
	 * @return void
	 *
	 * @spec openspec/changes/document-creatie-sjablonen/tasks.md#task-1
	 */
	public function updateJobStatus(string $jobId, array $status): void {
		try {
			$config = $this->container->get(\OCP\IAppConfig::class);
			$config->setValueString(
				'filinq',
				'document_job_' . $jobId,
				json_encode($status)
			);
		} catch (Exception $e) {
			$this->logger->error(
				message: 'Failed to store document job status: ' . $e->getMessage(),
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
	private function validateFormat(string $format): void {
		if (in_array(needle: $format, haystack: self::VALID_FORMATS, strict: true) === false) {
			$valid = implode(', ', self::VALID_FORMATS);
			throw new Exception(
				message: "Unsupported format '{$format}'. Valid formats: {$valid}",
				code: 400
			);
		}

	}//end validateFormat()

	/**
	 * Resolve and validate options.output.mode.
	 *
	 * @param array $options The request options
	 *
	 * @return string One of 'return', 'files', 'both'
	 *
	 * @throws Exception Code 400 if an unsupported mode is requested
	 *
	 * @spec openspec/changes/document-output-destinations-and-bulk-retention/specs/document-creatie-sjablonen/spec.md#req-ddob-001
	 */
	private function resolveOutputMode(array $options): string {
		$mode = $options['output']['mode'] ?? self::DEFAULT_OUTPUT_MODE;

		if (in_array(needle: $mode, haystack: self::VALID_OUTPUT_MODES, strict: true) === false) {
			$valid = implode(', ', self::VALID_OUTPUT_MODES);
			throw new Exception(
				message: "Unsupported options.output.mode '{$mode}'. Valid values: {$valid}",
				code: 400
			);
		}

		return $mode;
	}//end resolveOutputMode()

	/**
	 * Build the effective storage target path for a generation.
	 *
	 * An explicit `options.output.targetPath` always wins. Otherwise the
	 * default is `DocuDesk/<template namespace>/`. When a template array is
	 * not supplied (the async bulk job calls this once, before its
	 * per-object loop, with no template already loaded) the template is
	 * looked up via TemplateService.
	 *
	 * @param string $templateId The template UUID
	 * @param string|null $explicitTargetPath An explicit targetPath, if provided
	 * @param array|null $template A pre-fetched template array, if available
	 *
	 * @return string The effective target path (no trailing slash)
	 *
	 * @spec openspec/changes/document-output-destinations-and-bulk-retention/specs/document-creatie-sjablonen/spec.md#req-ddob-002
	 */
	public function buildOutputTargetPath(
		string $templateId,
		?string $explicitTargetPath,
		?array $template = null,
	): string {
		if (empty($explicitTargetPath) === false) {
			return rtrim($explicitTargetPath, '/');
		}

		if ($template === null) {
			try {
				$template = $this->templateService->getTemplate(id: $templateId);
			} catch (Exception $e) {
				$template = [];
			}
		}

		$namespace = $template['namespace'] ?? 'default';

		return self::DEFAULT_OUTPUT_FOLDER_PREFIX . '/' . $namespace;
	}//end buildOutputTargetPath()

	/**
	 * Build the filename a stored (or downloaded) document should use,
	 * mirroring the extension DocumentController's download response uses
	 * per format.
	 *
	 * @param string $filename The requested filename (without extension)
	 * @param string $format The output format (pdf, odf, html)
	 *
	 * @return string The filename including extension
	 */
	private function buildOutputFilename(string $filename, string $format): string {
		$basename = pathinfo($filename, PATHINFO_FILENAME);
		if (empty($basename) === true) {
			$basename = 'document';
		}

		$extension = '.pdf';
		if ($format === 'odf') {
			$extension = '.odt';
		} elseif ($format === 'html') {
			$extension = '.html';
		}

		return $basename . $extension;
	}//end buildOutputFilename()

	/**
	 * Store the generated output in Files when the requested mode calls for
	 * it, applying the fail-open-for-'both' rule (REQ-DDOB-003): a targetPath
	 * validation failure (code 400) always propagates; a storage execution
	 * failure (code 507) propagates for mode 'files' but is downgraded to a
	 * warning for mode 'both' so the binary is still returned.
	 *
	 * @param string $mode The resolved output mode
	 * @param string $templateId The template UUID
	 * @param array $template The already-fetched template array
	 * @param string $format The output format
	 * @param string $content The generated content to store
	 * @param array $options The request options
	 * @param array $warnings Warnings accumulated so far
	 *
	 * @return array{fileId: int|null, path: string|null, name: string|null, size: int|null, warnings: string[]}
	 *
	 * @throws Exception If storage fails and the mode does not fail open
	 *
	 * @spec openspec/changes/document-output-destinations-and-bulk-retention/specs/document-creatie-sjablonen/spec.md#req-ddob-003
	 */
	private function storeOutputIfRequested(
		string $mode,
		string $templateId,
		array $template,
		string $format,
		string $content,
		array $options,
		array $warnings,
	): array {
		$result = [
			'fileId' => null,
			'path' => null,
			'name' => null,
			'size' => null,
			'warnings' => $warnings,
		];

		if ($mode === 'return') {
			return $result;
		}

		$userId = (string)($options['userId'] ?? '');
		if ($userId === '') {
			throw new Exception(
				message: 'options.userId is required to store generated documents in Files',
				code: 400
			);
		}

		$targetPath = $this->buildOutputTargetPath(
			templateId: $templateId,
			explicitTargetPath: ($options['output']['targetPath'] ?? null),
			template: $template
		);
		$filename = $this->buildOutputFilename(
			filename: ($options['filename'] ?? 'document'),
			format: $format
		);

		try {
			$stored = $this->storageService->store(
				userId: $userId,
				targetPath: $targetPath,
				filename: $filename,
				content: $content
			);
			$result['fileId'] = $stored['fileId'];
			$result['path'] = $stored['path'];
			$result['name'] = $stored['name'];
			$result['size'] = $stored['size'];
		} catch (Exception $e) {
			$isStorageFailure = ($e->getCode() === DocumentStorageService::ERROR_CODE_STORAGE_FAILURE);
			if ($mode === 'both' && $isStorageFailure === true) {
				$result['warnings'][] = 'Document generated but could not be stored in Files: ' . $e->getMessage();
				return $result;
			}

			throw $e;
		}//end try

		return $result;
	}//end storeOutputIfRequested()

	/**
	 * Process a bulk generation request synchronously.
	 *
	 * Honours `options.output.mode` per object exactly as single-generate
	 * does (REQ-DDOB-007): 'return' (default) keeps today's inline
	 * `content`; 'files' stores each object's output and returns
	 * `{fileId, path, name, size}` inline instead of `content`; 'both'
	 * returns both.
	 *
	 * @param string $templateId The template UUID
	 * @param array $objectIds Array of object UUIDs
	 * @param array $options Generation options (register, schema, format, output, ...)
	 *
	 * @return array{results: array, total: int, completed: int, errors: int}
	 *
	 * @spec openspec/changes/document-output-destinations-and-bulk-retention/specs/document-creatie-sjablonen/spec.md#req-ddob-007
	 */
	private function generateBulkSync(
		string $templateId,
		array $objectIds,
		array $options,
	): array {
		$register = $options['register'] ?? '';
		$schema = $options['schema'] ?? '';
		$results = [];

		foreach ($objectIds as $objectId) {
			$dataRefs = [
				[
					'register' => $register,
					'schema' => $schema,
					'id' => $objectId,
				],
			];

			try {
				$result = $this->generateDocument(
					templateId: $templateId,
					dataRefs: $dataRefs,
					options: $options
				);
				$item = [
					'objectId' => $objectId,
					'status' => 'success',
					'warnings' => $result['warnings'],
				];

				$mode = $result['output']['mode'] ?? 'return';
				if ($mode === 'return' || $mode === 'both') {
					$item['content'] = $result['content'];
				}

				if ($mode === 'files' || $mode === 'both') {
					$item['fileId'] = $result['output']['fileId'];
					$item['path'] = $result['output']['path'];
					$item['name'] = $result['output']['name'];
					$item['size'] = $result['output']['size'];
				}

				$results[] = $item;
			} catch (Exception $e) {
				$results[] = [
					'objectId' => $objectId,
					'status' => 'error',
					'error' => $e->getMessage(),
				];

				$this->documentLogger->log(
					template: [
						'id' => $templateId,
						'version' => 0,
						'name' => '',
					],
					dataRefs: $dataRefs,
					format: ($options['format'] ?? self::DEFAULT_FORMAT),
					outcome: [
						'status' => 'failed',
						'warnings' => [],
						'caseId' => null,
						'errorMessage' => $e->getMessage(),
						'fileId' => null,
						'filePath' => null,
					],
					userId: (string)($options['userId'] ?? '')
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
			'results' => $results,
			'total' => count($objectIds),
			'completed' => (count($objectIds) - $errorCount),
			'errors' => $errorCount,
		];

	}//end generateBulkSync()

	/**
	 * Dispatch an async bulk generation background job.
	 *
	 * Computes the per-job storage folder once (REQ-DDOB-006):
	 * `<targetPath>/<jobId>/`, where `<targetPath>` is the request's
	 * `options.output.targetPath` if provided, else the same
	 * `DocuDesk/<template namespace>/` default single-generate uses. Every
	 * per-object `generateDocument()` call the job makes uses this same
	 * fixed path, so a large batch's outputs land in one folder instead of
	 * spraying across the destination.
	 *
	 * @param string $templateId The template UUID
	 * @param array $objectIds Array of object UUIDs
	 * @param array $options Generation options (options.output.mode is
	 *                       already validated to be 'files' by the caller)
	 *
	 * @return array{jobId: string, status: string, total: int}
	 *
	 * @spec openspec/changes/document-output-destinations-and-bulk-retention/specs/document-creatie-sjablonen/spec.md#req-ddob-006
	 */
	private function dispatchBulkJob(
		string $templateId,
		array $objectIds,
		array $options,
	): array {
		$jobId = $this->generateJobId();

		$baseTargetPath = $this->buildOutputTargetPath(
			templateId: $templateId,
			explicitTargetPath: ($options['output']['targetPath'] ?? null)
		);
		$options['output']['targetPath'] = $baseTargetPath . '/' . $jobId;

		$initialStatus = [
			'jobId' => $jobId,
			'status' => 'queued',
			'total' => count($objectIds),
			'completed' => 0,
			'errors' => 0,
			'results' => [],
			'options' => $options,
		];
		$this->updateJobStatus(jobId: $jobId, status: $initialStatus);

		$this->jobList->add(
			job: BatchDocumentJob::class,
			argument: [
				'jobId' => $jobId,
				'templateId' => $templateId,
				'objectIds' => $objectIds,
				'options' => $options,
			]
		);

		return [
			'jobId' => $jobId,
			'status' => 'queued',
			'total' => count($objectIds),
		];

	}//end dispatchBulkJob()

	/**
	 * Generate a cryptographically secure job UUID.
	 *
	 * @return string A RFC-4122 v4 UUID job identifier
	 */
	private function generateJobId(): string {
		$data = random_bytes(16);
		$data[6] = chr(ord($data[6]) & 0x0f | 0x40);
		$data[8] = chr(ord($data[8]) & 0x3f | 0x80);
		return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
	}//end generateJobId()
}//end class
