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
use OCA\Filinq\Kernel\Contracts\DocumentServiceInterface;
use OCA\Filinq\Service\Wizard\WizardGenerationGate;

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
class DocumentService implements DocumentServiceInterface {

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
	public const DEFAULT_FORMAT = 'pdf';

	/**
	 * Valid output formats.
	 *
	 * @var string[]
	 */
	private const VALID_FORMATS = ['pdf', 'odf', 'html', 'docx'];

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
	 * @param DocumentJobStore $jobs Status records and queueing of async bulk jobs
	 * @param PlainLanguageRenditionService|null $plainRendition The plain-language counterpart, when a template declares one
	 * @param WizardGenerationGate|null $wizardGate Checks a wizard run before it renders (options.wizardContext)
	 * @param TemplateMergeFieldService|null $mergeFields Names the fields a template fills, for the merge preview
	 *
	 * @return void
	 */
	public function __construct(
		private readonly TemplateService $templateService,
		private readonly DataResolverService $dataResolver,
		private readonly DocumentRenderPipeline $renderPipeline,
		private readonly DocumentStorageService $storageService,
		private readonly GeneratedDocumentLogger $documentLogger,
		private readonly DocumentJobStore $jobs,
		private readonly ?PlainLanguageRenditionService $plainRendition = null,
		private readonly ?WizardGenerationGate $wizardGate = null,
		private readonly ?TemplateMergeFieldService $mergeFields = null,
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
	 * @param array $options Options: format (pdf|odf|html|docx), huisstijlId,
	 *                       zaakId, adHocData, listRefs, pdfOptions, userId,
	 *                       filename, output, templateVersion (render that stored version, not the head).
	 *                       listRefs: [{register, schema, filter?, limit?,
	 *                       order?, as?}, ...] — each resolves to an array
	 *                       of objects under the Twig context key 'as'
	 *                       (default: schema + '_list')
	 *                       output: {mode?: return|files|both, targetPath?}
	 *                       — defaults to mode 'return' (byte-identical to
	 *                       this method's behaviour before output support
	 *                       existed)
	 * @param array $recordFields Extra fields for the generatedDocument entry, such as the
	 *                            view a periodic run rendered over. A PHP-only parameter:
	 *                            no HTTP route passes it, so a request cannot write into
	 *                            its own audit entry. Canonical fields always win.
	 *
	 * @return array{content: string, format: string, metadata: array, warnings: string[], output: array,
	 *     templateVersion: int|null, sha256: string, pageCount?: int}
	 *
	 * @throws Exception If generation fails
	 *
	 * @spec openspec/changes/document-creatie-sjablonen/tasks.md#task-1
	 * @spec openspec/specs/document-creatie-sjablonen/spec.md
	 * @spec openspec/changes/document-output-destinations-and-bulk-retention/specs/document-creatie-sjablonen/spec.md
	 * @spec openspec/specs/document-creatie-sjablonen/spec.md
	 */
	public function generateDocument(
		string $templateId,
		array $dataRefs,
		array $options = [],
		array $recordFields = [],
	): array {
		if (isset($options['formats']) === true) {
			throw new Exception(message: 'options.formats is answered by MultiFormatOutputProducer::generate()', code: 400);
		}

		$format = $options['format'] ?? self::DEFAULT_FORMAT;
		$this->validateFormat(format: $format);
		$this->resolveOutputMode(options: $options);

		$template = null;
		if (isset($options['templateVersion']) === true) {
			$template = $this->templateService->getTemplateAtVersion(id: $templateId, version: (int) $options['templateVersion']);
		}

		$template ??= $this->templateService->getTemplate(id: $templateId);

		if (isset($options['wizardContext']) === true && $this->wizardGate !== null) {
			// A wizard run: the answers are checked against the stored wizard before
			// anything renders, and the entry records the interview (REQ-DDGDW-005/008).
			$wizardFields = $this->wizardGate->check(templateId: $templateId, dataRefs: $dataRefs, context: $options['wizardContext']);
			$recordFields = array_merge($recordFields, $wizardFields);
		}

		return $this->generateFromTemplate(
			templateId: $templateId,
			template: $template,
			dataRefs: $dataRefs,
			options: $options,
			recordFields: $recordFields
		);

	}//end generateDocument()

	/**
	 * Generate a document from a template the caller already holds.
	 *
	 * The body of {@see generateDocument()}, split out so a caller that
	 * resolved its template another way (by slug through
	 * {@see TemplateSlugResolver}, or as inline template text on a flow step)
	 * reaches the exact same render, store and audit path instead of a
	 * second copy of it. `generateDocument()` is this method plus a lookup by
	 * id, so its behaviour is unchanged.
	 *
	 * @param string $templateId The template's identifier, for the audit record and default folder.
	 * @param array $template The template: at least `content`; `name`, `namespace`, `version`,
	 *                        `format` and `orientation` when known.
	 * @param array $dataRefs Data references: [{register, schema, id}, ...]
	 * @param array $options The same options {@see generateDocument()} takes.
	 * @param array $recordFields The same extra entry fields {@see generateDocument()} takes.
	 *
	 * @return array{content: string, html: string, format: string, metadata: array, warnings: string[],
	 *     output: array, templateVersion: int|null, sha256: string, pageCount?: int}
	 *               `html` is the rendered template before format conversion.
	 *
	 * @throws Exception If generation fails
	 *
	 * @spec openspec/changes/flow-generate-document-node/specs/flow-document-generation/spec.md#requirement-one-generation-path-for-the-flow-node-the-command-event-and-the-api
	 */
	public function generateFromTemplate(
		string $templateId,
		array $template,
		array $dataRefs,
		array $options = [],
		array $recordFields = [],
	): array {
		$run = [
			'templateId' => $templateId,
			'template' => $template,
			'dataRefs' => $dataRefs,
			'options' => $options,
			'recordFields' => $recordFields,
			'format' => ($options['format'] ?? self::DEFAULT_FORMAT),
		];
		$this->validateFormat(format: $run['format']);
		$run['outputMode'] = $this->resolveOutputMode(options: $options);

		$resolved = $this->resolveTemplateData(run: $run);

		// 🔴 THE PLAIN RENDITION IS PLANNED BEFORE ANYTHING IS FILED. REQ-DIO-04's
		// fourth scenario asks a refused generation to leave NEITHER rendition
		// behind, and a check made after the formal document is stored cannot
		// give that: the formal letter would already be in the folder by the
		// time the plain one turned out to be impossible. A template declaring
		// no counterpart plans nothing, and this line costs it one null.
		$plainPlan = $this->plainRendition?->plan(
			template: $template,
			data: $resolved['data'],
			acceptance: (array)($options['plainRenditionAcceptance'] ?? [])
		);

		$rendered = $this->renderDocument(run: $run, resolved: $resolved);
		$stored   = $this->storeOutputIfRequested(run: $run, output: $rendered);
		$plain    = $this->producePlainRendition(run: $run, formal: $rendered + ['plan' => $plainPlan, 'stored' => $stored]);

		$outcome = [
			'rendered' => $rendered,
			'stored' => $stored,
			'plain' => $plain,
			'warnings' => array_merge($stored['warnings'], $plain['warnings']),
		];
		$outcome['metadata'] = $this->logGenerated(run: $run, outcome: $outcome);

		return $this->generationResult(run: $run, outcome: $outcome);

	}//end generateFromTemplate()

	/**
	 * Resolve the data a generation renders, turning failed references into warnings.
	 *
	 * @param array<string, mixed> $run The generation run: dataRefs and options.
	 *
	 * @return array{data: array<string, mixed>, warnings: array<int, string>} The data and the warnings so far.
	 *
	 * @spec openspec/changes/document-creatie-sjablonen/tasks.md#task-1
	 */
	private function resolveTemplateData(array $run): array {
		$resolution = $this->dataResolver->resolve(
			dataRefs: $run['dataRefs'],
			listRefs: ($run['options']['listRefs'] ?? []),
			adHocData: ($run['options']['adHocData'] ?? [])
		);
		$warnings   = $resolution['warnings'];

		foreach ($resolution['errors'] as $error) {
			$ref = $error['register'] . '/' . $error['schema'] . '/' . $error['id'];
			$warnings[] = "Data resolution failed for {$ref}: {$error['message']}";
		}

		return ['data' => $resolution['data'], 'warnings' => $warnings];
	}//end resolveTemplateData()

	/**
	 * Render the template with the huisstijl and produce the output bytes.
	 *
	 * @param array<string, mixed> $run      The generation run: template, format and options.
	 * @param array<string, mixed> $resolved The resolved data and the warnings so far.
	 *
	 * @return array{data: array<string, mixed>, huisstijl: array<string, mixed>|null, pdfOptions: array<string, mixed>,
	 *     html: string, content: string, warnings: array<int, string>} The rendering and the warnings so far.
	 *
	 * @spec openspec/changes/document-creatie-sjablonen/tasks.md#task-1
	 */
	private function renderDocument(array $run, array $resolved): array {
		$huisstijl    = $this->renderPipeline->loadHuisstijl(huisstijlId: ($run['options']['huisstijlId'] ?? null));
		$pdfOptions   = $this->renderPipeline->buildPdfOptions(template: $run['template'], huisstijl: $huisstijl, options: $run['options']);
		$renderResult = $this->renderPipeline->renderWithHuisstijl(
			templateContent: $run['template']['content'],
			data: $resolved['data'],
			huisstijl: $huisstijl
		);
		$content      = $this->renderPipeline->produceOutput(
			htmlContent: $renderResult['html'],
			format: $run['format'],
			pdfOptions: $pdfOptions
		);

		return [
			'data' => $resolved['data'],
			'huisstijl' => $huisstijl,
			'pdfOptions' => $pdfOptions,
			'html' => $renderResult['html'],
			'content' => $content,
			'warnings' => array_merge($resolved['warnings'], $renderResult['warnings'], $this->renderPipeline->getLastOutputWarnings()),
		];
	}//end renderDocument()

	/**
	 * Write the generatedDocument audit entry for a generation.
	 *
	 * @param array<string, mixed> $run     The generation run.
	 * @param array<string, mixed> $outcome The filed formal and plain documents and the warnings.
	 *
	 * @return array<string, mixed> The audit entry.
	 *
	 * @spec openspec/changes/document-creatie-sjablonen/tasks.md#task-1
	 */
	private function logGenerated(array $run, array $outcome): array {
		return $this->documentLogger->log(
			template: [
				'id' => $run['templateId'],
				'version' => ($run['template']['version'] ?? null),
				'name' => ($run['template']['name'] ?? ''),
			],
			dataRefs: $run['dataRefs'],
			format: $run['format'],
			outcome: [
				'status' => 'generated',
				'warnings' => $outcome['warnings'],
				'caseId' => ($run['options']['caseId'] ?? null),
				'errorMessage' => null,
				'fileId' => $outcome['stored']['fileId'],
				'filePath' => $outcome['stored']['path'],
			],
			userId: (string)($run['options']['userId'] ?? ''),
			extra: array_merge($run['recordFields'], $outcome['plain']['record'])
		);
	}//end logGenerated()

	/**
	 * The answer of a generation: the bytes, the audit entry and where the output went.
	 *
	 * @param array<string, mixed> $run     The generation run.
	 * @param array<string, mixed> $outcome The rendering, the filed documents, the audit entry and the warnings.
	 *
	 * @return array<string, mixed> The generation result.
	 *
	 * @spec openspec/changes/document-creatie-sjablonen/tasks.md#task-1
	 */
	private function generationResult(array $run, array $outcome): array {
		$stored  = $outcome['stored'];
		$content = $outcome['rendered']['content'];

		return [
			'content' => $content,
			'html' => $outcome['rendered']['html'],
			'format' => $run['format'],
			'metadata' => $outcome['metadata'],
			'plainRendition' => $outcome['plain']['rendition'],
			'templateVersion' => ($run['template']['version'] ?? null),
			'warnings' => $outcome['warnings'],
			'output' => [
				'mode' => $run['outputMode'],
				'fileId' => $stored['fileId'],
				'path' => $stored['path'],
				'name' => $stored['name'],
				'size' => $stored['size'],
			],
		] + $this->renderPipeline->describeOutput(content: $content, format: $run['format']);
	}//end generationResult()


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
	 * @spec openspec/specs/document-creatie-sjablonen/spec.md
	 */
	public function generatePreview(
		string $templateId,
		array $dataRefs,
		array $options = [],
	): array {
		$template = $this->templateService->getTemplate(id: $templateId);
		$preview = $this->renderPreview(run: ['template' => $template, 'dataRefs' => $dataRefs, 'options' => $options]);

		return [
			'html' => $preview['html'],
			'warnings' => $preview['warnings'],
		];

	}//end generatePreview()

	/**
	 * Preview a template and name the fields it fills.
	 *
	 * The preview of generatePreview(), plus the template's merge fields in
	 * the resolved data: each with its path, label, source, value and whether
	 * it is filled, and whether all are. Like the preview it stores nothing
	 * and logs nothing.
	 *
	 * @param string $templateId The UUID of the template to preview
	 * @param array  $dataRefs   Data references: [{register, schema, id}, ...]
	 * @param array  $options    Options: huisstijlId, adHocData, listRefs.
	 *
	 * @return array{html: string, warnings: string[], fields: array<int, array<string, string|bool>>, complete: bool}
	 *
	 * @throws Exception If rendering fails
	 *
	 * @spec openspec/changes/template-merge-preview/specs/document-creatie-sjablonen/spec.md
	 */
	public function generateMergePreview(
		string $templateId,
		array $dataRefs,
		array $options = [],
	): array {
		$template = $this->templateService->getTemplate(id: $templateId);
		$preview = $this->renderPreview(run: ['template' => $template, 'dataRefs' => $dataRefs, 'options' => $options]);
		$mergeFields = ($this->mergeFields ?? new TemplateMergeFieldService());
		$merge = $mergeFields->resolve(template: $template, data: $preview['data']);

		return [
			'html' => $preview['html'],
			'warnings' => $preview['warnings'],
			'fields' => $merge['fields'],
			'complete' => $merge['complete'],
		];

	}//end generateMergePreview()

	/**
	 * Resolve a template's data and render it with the house style, without output.
	 *
	 * @param array<string, mixed> $run The preview run: template, dataRefs
	 *                                  ([{register, schema, id}, ...]) and options
	 *                                  (huisstijlId, adHocData, listRefs).
	 *
	 * @return array{html: string, warnings: string[], data: array}
	 *
	 * @throws Exception If rendering fails
	 */
	private function renderPreview(array $run): array {
		$resolved = $this->resolveTemplateData(run: $run);

		$huisstijl = $this->renderPipeline->loadHuisstijl(huisstijlId: ($run['options']['huisstijlId'] ?? null));
		$renderResult = $this->renderPipeline->renderWithHuisstijl(
			templateContent: $run['template']['content'],
			data: $resolved['data'],
			huisstijl: $huisstijl
		);

		return [
			'html' => $renderResult['html'],
			'warnings' => array_merge($resolved['warnings'], $renderResult['warnings']),
			'data' => $resolved['data'],
		];

	}//end renderPreview()

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
	 * See openspec/changes/archive/2026-09-28-document-generation-list-refs/proposal.md and
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
			$bulk = new SyncBulkGeneration(documents: $this, documentLogger: $this->documentLogger);
			return $bulk->run(templateId: $templateId, objectIds: $objectIds, options: $options);
		}

		$this->validateAsyncOutputMode(options: $options);

		return $this->dispatchBulkJob(run: ['templateId' => $templateId, 'objectIds' => $objectIds, 'options' => $options]);

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
		return $this->jobs->get(jobId: $jobId);

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
		$this->jobs->put(jobId: $jobId, status: $status);

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
		} elseif ($format === 'docx') {
			$extension = '.docx';
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
	/**
	 * Produce the plain-language rendition, when the template declared one.
	 *
	 * 🔴 BOTH RENDITIONS COME OUT OF ONE GENERATION, which is what makes
	 * REQ-DIO-04's fifth scenario true by construction: regenerating the formal
	 * letter after a correction regenerates the plain one in the same act,
	 * because there is no path that produces one without the other. A separate
	 * "regenerate the plain version" call would be a path somebody can forget,
	 * and a stale plain letter beside a corrected formal one is the failure the
	 * scenario names.
	 *
	 * 🔑 IT RENDERS FROM THE SAME DATA AND THE SAME HUISSTIJL. Rendering the
	 * plain counterpart from anything else would let the two letters disagree
	 * about a date while both claim to describe one decision.
	 *
	 * 🔑 A PLAIN RENDITION THAT COULD NOT BE STORED IS A WARNING, NOT A THROW,
	 * and only once the formal document is already filed. Everything that can
	 * refuse the pair has refused before this point; failing here would mean
	 * losing a formal letter that is already on disk over its companion.
	 *
	 * @param array<string, mixed> $run    The generation run: format, options and output mode.
	 * @param array<string, mixed> $formal The formal rendering (data, huisstijl, pdfOptions), the plan
	 *                                     (null when no counterpart is declared) and the filed formal document.
	 *
	 * @return array{rendition: array<string, mixed>|null, record: array<string, mixed>,
	 *               warnings: array<int, string>} The rendition, its record fields and any warnings.
	 *
	 * @spec openspec/changes/documents-in-and-out-of-the-building/specs/letter-correspondence-generation/spec.md
	 */
	private function producePlainRendition(array $run, array $formal): array {
		$plan = $formal['plan'];
		if ($plan === null || $this->plainRendition === null) {
			return ['rendition' => null, 'record' => [], 'warnings' => []];
		}

		$rendered = $this->renderPipeline->renderWithHuisstijl(
			templateContent: $plan['content'],
			data: $formal['data'],
			huisstijl: $formal['huisstijl']
		);

		$content = $this->renderPipeline->produceOutput(
			htmlContent: $rendered['html'],
			format: $run['format'],
			pdfOptions: $formal['pdfOptions']
		);

		$plainRun = [
			'templateId' => $plan['templateId'],
			'template' => ['namespace' => ($run['options']['namespace'] ?? '')],
			'options' => ['filename' => ((string)($run['options']['filename'] ?? 'document')) . '-in-gewone-taal'] + $run['options'],
		] + $run;
		$stored   = $this->storeOutputIfRequested(
			run: $plainRun,
			output: ['content' => $content, 'warnings' => $this->renderPipeline->getLastOutputWarnings()]
		);

		$record = $this->plainRendition->recordFields(
			plan: $plan,
			formal: $formal['stored'],
			plainFile: $stored,
			moment: date('c')
		);

		return [
			'rendition' => [
				'content' => $content,
				'format' => $run['format'],
				'templateId' => $plan['templateId'],
				'explains' => $record['plainRenditionExplains'],
				'source' => $plan['source'],
				'acceptedBy' => $plan['acceptedBy'],
				'acceptedAt' => $plan['acceptedAt'],
				'fileId' => $stored['fileId'],
				'path' => $stored['path'],
			],
			'record' => $record,
			'warnings' => array_merge($rendered['warnings'], $stored['warnings']),
		];
	}//end producePlainRendition()

	/**
	 * File the rendered bytes when the caller asked for them to be stored.
	 *
	 * @param array<string, mixed> $run    The generation run: outputMode (`return` hands the bytes back unfiled,
	 *                                     anything else files them), templateId, template, format and options
	 *                                     (carrying `userId` and the target).
	 * @param array<string, mixed> $output The rendered bytes (`content`) and the warnings collected so far.
	 *
	 * @return array{fileId: ?int, path: ?string, name: ?string, size: ?int, warnings: array<int, string>}
	 *         What was filed, or nulls when the mode was `return`.
	 *
	 * @spec exclude Private helper of generateDocument(); the storing rule is specified there.
	 */
	private function storeOutputIfRequested(array $run, array $output): array {
		$result = [
			'fileId' => null,
			'path' => null,
			'name' => null,
			'size' => null,
			'warnings' => $output['warnings'],
		];

		if ($run['outputMode'] === 'return') {
			return $result;
		}

		$options = $run['options'];
		$userId = (string)($options['userId'] ?? '');
		if ($userId === '') {
			throw new Exception(
				message: 'options.userId is required to store generated documents in Files',
				code: 400
			);
		}

		$targetPath = $this->buildOutputTargetPath(
			templateId: $run['templateId'],
			explicitTargetPath: ($options['output']['targetPath'] ?? null),
			template: $run['template']
		);
		$filename = $this->buildOutputFilename(
			filename: ($options['filename'] ?? 'document'),
			format: $run['format']
		);

		try {
			$stored = $this->storageService->store(
				userId: $userId,
				targetPath: $targetPath,
				filename: $filename,
				content: $output['content']
			);
			$result['fileId'] = $stored['fileId'];
			$result['path'] = $stored['path'];
			$result['name'] = $stored['name'];
			$result['size'] = $stored['size'];
		} catch (Exception $e) {
			$isStorageFailure = ($e->getCode() === DocumentStorageService::ERROR_CODE_STORAGE_FAILURE);
			if ($run['outputMode'] === 'both' && $isStorageFailure === true) {
				$result['warnings'][] = 'Document generated but could not be stored in Files: ' . $e->getMessage();
				return $result;
			}

			throw $e;
		}//end try

		return $result;
	}//end storeOutputIfRequested()

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
	 * @param array<string, mixed> $run The bulk run: templateId, objectIds and options
	 *                                  (options.output.mode is already validated
	 *                                  to be 'files' by the caller).
	 *
	 * @return array{jobId: string, status: string, total: int}
	 *
	 * @spec openspec/changes/document-output-destinations-and-bulk-retention/specs/document-creatie-sjablonen/spec.md#req-ddob-006
	 */
	private function dispatchBulkJob(array $run): array {
		$templateId = $run['templateId'];
		$objectIds = $run['objectIds'];
		$options = $run['options'];
		$jobId = $this->jobs->newId();

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
		$this->jobs->enqueue(
			jobId: $jobId,
			status: $initialStatus,
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
}//end class
