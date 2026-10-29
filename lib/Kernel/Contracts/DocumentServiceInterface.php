<?php

/**
 * Contract of DocumentService
 *
 * The public surface of the DocumentService service, so callers in other
 * components depend on this contract instead of the implementation.
 *
 * @category  Kernel
 * @package   OCA\Filinq\Kernel\Contracts
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Kernel\Contracts;

use Exception;

/**
 * Contract of DocumentService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface DocumentServiceInterface {
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
	public function generateDocument(string $templateId, array $dataRefs, array $options = [], array $recordFields = []): array;

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
	 *
	 * @SuppressWarnings(PHPMD.ExcessiveMethodLength) The plain-language rendition
	 * step pushed this past the threshold. It belongs in the same method because
	 * the formal document and its plain counterpart are filed together or not at
	 * all; splitting the step out would make a half-filed pair reachable.
	 */
	public function generateFromTemplate(
		string $templateId,
		array $template,
		array $dataRefs,
		array $options = [],
		array $recordFields = [],
	): array;

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
	public function generatePreview(string $templateId, array $dataRefs, array $options = []): array;

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
	public function generateMergePreview(string $templateId, array $dataRefs, array $options = []): array;

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
	public function generateBulk(string $templateId, array $objectIds, array $options = []): array;

	/**
	 * Get the status of an async bulk document generation job.
	 *
	 * @param string $jobId The job UUID
	 *
	 * @return array|null The job status or null if not found
	 *
	 * @spec openspec/changes/document-creatie-sjablonen/tasks.md#task-1
	 */
	public function getJobStatus(string $jobId): ?array;

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
	public function updateJobStatus(string $jobId, array $status): void;

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
	public function buildOutputTargetPath(string $templateId, ?string $explicitTargetPath, ?array $template = null): string;
}//end interface
