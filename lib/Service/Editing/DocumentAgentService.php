<?php

/**
 * Document Agent Service
 *
 * DocuDesk's curated, agent-reachable document operations: read a document's
 * anchored blocks, apply anchored edits to it, and convert it to PDF.
 *
 * These are the only document-editing tools an agent sees. Everything the
 * operations need -- locking, the version precondition, the codec, the ADR-088
 * mark, the refusals -- happens below this class and is never separately
 * callable, so there is no sequence of tool calls that arrives at a write
 * without passing through the guards.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service\Editing
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/changes/document-editing-tools/tasks.md#task-2-5
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service\Editing;

use OCA\DocuDesk\Service\GeneratedDocumentLogger;
use OCA\DocuDesk\Service\PdfConversionService;
use OCA\OpenRegister\Mcp\Attribute\McpTool;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * The agent-facing document operations.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service\Editing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/specs/document-editing/spec.md#requirement-no-document-attachment-or-signature-bytes-leave-through-this-capability
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DocumentAgentService {

	/**
	 * Constructor.
	 *
	 * @param EditSessionService $editSession The edit session owner.
	 * @param PdfConversionService $pdfConversion The PDF conversion cascade.
	 * @param AgentArtefactMarker $marker The ADR-088 artefact marker.
	 * @param GeneratedDocumentLogger $documentLogger The generated-document audit logger.
	 * @param IRootFolder $rootFolder The Nextcloud root folder.
	 * @param IUserSession $userSession The acting user's session.
	 * @param LoggerInterface $logger Logger for diagnostics.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly EditSessionService $editSession,
		private readonly PdfConversionService $pdfConversion,
		private readonly AgentArtefactMarker $marker,
		private readonly GeneratedDocumentLogger $documentLogger,
		private readonly IRootFolder $rootFolder,
		private readonly IUserSession $userSession,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	// ⚠️ ATTRIBUTE BEFORE DOCBLOCK, deliberately, on all three tools below.
	//
	// The idiomatic order (docblock, then attribute, then declaration) makes
	// hydra gate-16 report these methods as missing `@spec` when they are not:
	// `check_spec_coverage.py::_docblock_block()` walks up from the declaration
	// skipping lines that start with `#[` or `]`, and a multi-line attribute
	// closes on `)]` — which matches neither, so the walk stops there and never
	// reaches the docblock holding the tag.
	//
	// Both orders are valid PHP and reflection reads the attribute and the
	// docblock identically either way (verified). Putting the attribute first is
	// the smaller price than dropping the multi-line form, which cannot hold
	// these descriptions inside the 150-column limit. Revert once the gate
	// handles `)]`.

	#[McpTool(
		name: 'readDocument',
		description: 'Read a Word (.docx) or OpenDocument (.odt) file as a list of anchored text blocks, '
			. 'one per paragraph. Use this before editDocument: it returns the anchors and the version '
			. 'that editDocument requires. Returns text, never the file bytes.',
		readOnlyHint: true,
		destructiveHint: false,
		idempotentHint: true,
		scope: 'read'
	)]
	/**
	 * Read the editable text of a Word or OpenDocument text file, block by block.
	 *
	 * Returns one entry per paragraph, each with a stable `anchor` derived from
	 * that paragraph's own text. Pass those anchors, and the `version` this call
	 * returns, back to `editDocument` to change the document. Anchors change
	 * whenever the text does, so an anchor from an out-of-date read is refused
	 * rather than applied to the wrong paragraph.
	 *
	 * @param int $fileId The Nextcloud file id of the document to read.
	 *
	 * @return array<string, mixed> The document's name, format, version and anchored blocks.
	 *
	 * @throws RuntimeException When the file cannot be read or is not an editable format.
	 *
	 * @spec openspec/specs/document-editing/spec.md#requirement-edits-address-stable-anchors-never-positional-indexes
	 */
	public function readDocument(int $fileId): array {
		return $this->editSession->openForAgent(uid: $this->requireUid(), fileId: $fileId);

	}//end readDocument()

	#[McpTool(
		name: 'editDocument',
		description: 'Change a Word (.docx) or OpenDocument (.odt) file by replacing, inserting after, or '
			. 'deleting anchored paragraphs, or by restyling one with action "style". A style edit carries a '
			. '"style" object instead of text: bold, italic, underline (true/false), alignment '
			. '(left/center/right/justify), heading (0-9, where 0 means body text), list (true/false), '
			. 'pageBreakBefore (true/false). Style edits need a .docx -- .odt is refused by name. Call '
			. 'readDocument first to get the anchors and version. Writes into the file by default (restorable '
			. 'via Nextcloud file versions); pass outputMode "sibling" to write a new file instead. Refuses if '
			. 'the document changed since it was read, if it is open in an editor, if it is under a signing '
			. 'request, or if it is anonymisation output.',
		readOnlyHint: false,
		destructiveHint: true,
		idempotentHint: false,
		scope: 'update'
	)]
	/**
	 * Change the text of a Word or OpenDocument text file.
	 *
	 * Each edit names an `anchor` from a preceding `readDocument`, an `action`
	 * (`replace`, `insertAfter` or `delete`) and, for the first two, the `text`
	 * to use. The whole set is applied or none of it is.
	 *
	 * By default the change is written into the file itself, producing a
	 * Nextcloud file version that can be restored. Pass `outputMode` as
	 * `sibling` to write a new file beside the original instead, leaving the
	 * original untouched.
	 *
	 * Every file this produces is tagged "Agent authored" in Files.
	 *
	 * @param int $fileId The Nextcloud file id of the document to change.
	 * @param array<int, array{anchor: string, action?: string, text?: string}> $edits The edits to apply.
	 * @param string $version The `version` returned by the `readDocument` call these anchors came from.
	 * @param string $outputMode Either `inPlace` (default) or `sibling`. May narrow the configured mode, never widen it.
	 *
	 * @return array<string, mixed> The produced file's id, name, path and applied anchors.
	 *
	 * @throws RuntimeException On any refusal. Nothing is written when this throws.
	 *
	 * @spec openspec/specs/document-editing/spec.md#requirement-editing-writes-in-place-by-default-with-a-recoverable-prior-version
	 * @spec openspec/specs/document-editing/spec.md#requirement-every-produced-file-is-recorded-with-its-identity-and-without-its-content
	 */
	public function editDocument(int $fileId, array $edits, string $version, string $outputMode = ''): array {
		$uid = $this->requireUid();

		// An omitted argument means "use the configured mode", not "an unknown
		// mode" — the empty string is how MCP delivers "not supplied".
		$requestedMode = $outputMode;
		if ($requestedMode === '') {
			$requestedMode = null;
		}

		$result = $this->editSession->editForAgent(
			uid: $uid,
			fileId: $fileId,
			edits: $edits,
			version: $version,
			requestedMode: $requestedMode
		);

		$this->record(
			uid: $uid,
			fileId: (int)$result['fileId'],
			path: (string)$result['path'],
			format: 'docx',
			note: sprintf('Agent edit of file %d (%s output)', $fileId, (string)$result['outputMode'])
		);

		return ($result + ['artefact' => ['type' => 'file', 'id' => (string)$result['fileId']]]);

	}//end editDocument()

	#[McpTool(
		name: 'readSpreadsheet',
		description: 'Read a spreadsheet (.ods or .xlsx) as a list of cells addressed Sheet!Cell, each with '
			. 'its value and, when it has one, its formula. Use this before editSpreadsheet: it returns the '
			. 'version that call requires. A cell address is a durable identity, so there are no anchors here.',
		readOnlyHint: true,
		destructiveHint: false,
		idempotentHint: true,
		scope: 'read'
	)]
	/**
	 * Read a spreadsheet's cells.
	 *
	 * @param int $fileId The Nextcloud file id of the spreadsheet.
	 *
	 * @return array<string, mixed> The sheet's cells and the version an edit requires.
	 *
	 * @throws RuntimeException When the file cannot be read or is not a spreadsheet.
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.2
	 */
	public function readSpreadsheet(int $fileId): array {
		return $this->editSession->openSpreadsheetForAgent(uid: $this->requireUid(), fileId: $fileId);

	}//end readSpreadsheet()

	#[McpTool(
		name: 'editSpreadsheet',
		description: 'Write literal values into spreadsheet cells addressed Sheet!Cell. Read the sheet first '
			. 'and pass back its version. Writing over a cell that holds a FORMULA is refused unless that '
			. 'edit sets replaceFormula true — the flag is per cell and is not carried across a bulk write. '
			. 'The result lists cells whose cached values no longer follow from their inputs.',
		readOnlyHint: false,
		destructiveHint: true,
		idempotentHint: false,
		scope: 'update'
	)]
	/**
	 * Write literal values into a spreadsheet's cells.
	 *
	 * @param int    $fileId  The Nextcloud file id of the spreadsheet.
	 * @param array  $edits   Each `{cell, value, replaceFormula?}`.
	 * @param string $version The `version` from the read that produced these addresses.
	 *
	 * @return array<string, mixed> The outcome, including cells whose cached values went stale.
	 *
	 * @throws RuntimeException On any refusal. Nothing is written on a throw.
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.2
	 */
	public function editSpreadsheet(int $fileId, array $edits, string $version): array {
		$uid = $this->requireUid();

		$result = $this->editSession->editSpreadsheetForAgent(
			uid: $uid,
			fileId: $fileId,
			edits: $edits,
			version: $version
		);

		$this->record(
			uid: $uid,
			fileId: (int)$result['fileId'],
			path: (string)$result['path'],
			format: 'spreadsheet',
			note: sprintf('Agent cell edit of file %d', $fileId)
		);

		return ($result + ['artefact' => ['type' => 'file', 'id' => (string)$result['fileId']]]);

	}//end editSpreadsheet()

	#[McpTool(
		name: 'readPresentation',
		description: 'Read a presentation (.pptx or .odp) as a list of shapes, each carrying its slide id, '
			. 'shape id, region (slide or notes) and text. Use this before editPresentation: it returns the '
			. 'version that call requires. Slides are identified by ID, never by position.',
		readOnlyHint: true,
		destructiveHint: false,
		idempotentHint: true,
		scope: 'read'
	)]
	/**
	 * Read a presentation's shapes.
	 *
	 * @param int $fileId The Nextcloud file id of the presentation.
	 *
	 * @return array<string, mixed> The deck's shapes and the version an edit requires.
	 *
	 * @throws RuntimeException When the file cannot be read or is not a presentation.
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-2.1
	 */
	public function readPresentation(int $fileId): array {
		return $this->editSession->openPresentationForAgent(uid: $this->requireUid(), fileId: $fileId);

	}//end readPresentation()

	#[McpTool(
		name: 'editPresentation',
		description: 'Replace the text of presentation shapes, addressed by slide id and shape id from '
			. 'readPresentation. Never address a slide by its position: slide order changes and the ids do '
			. 'not. Set region to notes to write speaker notes; it defaults to the slide, so talking points '
			. 'are never put on screen by accident.',
		readOnlyHint: false,
		destructiveHint: true,
		idempotentHint: false,
		scope: 'update'
	)]
	/**
	 * Replace the text of addressed presentation shapes.
	 *
	 * @param int    $fileId  The Nextcloud file id of the presentation.
	 * @param array  $edits   Each `{slide, shape, text, region?}`.
	 * @param string $version The `version` from the read that produced these ids.
	 *
	 * @return array<string, mixed> The outcome.
	 *
	 * @throws RuntimeException On any refusal. Nothing is written on a throw.
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-2.1
	 */
	public function editPresentation(int $fileId, array $edits, string $version): array {
		$uid = $this->requireUid();

		$result = $this->editSession->editPresentationForAgent(
			uid: $uid,
			fileId: $fileId,
			edits: $edits,
			version: $version
		);

		$this->record(
			uid: $uid,
			fileId: (int)$result['fileId'],
			path: (string)$result['path'],
			format: 'presentation',
			note: sprintf('Agent shape edit of file %d', $fileId)
		);

		return ($result + ['artefact' => ['type' => 'file', 'id' => (string)$result['fileId']]]);

	}//end editPresentation()

	#[McpTool(
		name: 'addDocumentChart',
		description: 'Add a bar, line or pie chart to a Word (.docx) file. The chart is a real chart the user '
			. 'can select and resize in Word or Nextcloud Office, not a picture. Give it a type, a title, a list '
			. 'of categories, and one or more series each carrying exactly one value per category (a pie chart '
			. 'takes one series). Call readDocument first and pass back the version; pass an anchor to place the '
			. 'chart after that paragraph, or omit it to append at the end. Charts need a .docx -- .odt is '
			. 'refused by name. The file is tagged "Agent authored" and the previous version stays restorable.',
		readOnlyHint: false,
		destructiveHint: false,
		idempotentHint: false,
		scope: 'update'
	)]
	/**
	 * Add a chart to a Word document.
	 *
	 * The chart is native DrawingML: the user can select, resize and restyle it in
	 * the office suite. Its values are carried in the chart's own cache, which is
	 * what every suite renders from -- so it draws correctly, but "Edit data" has
	 * no worksheet to open, because no embedded workbook is written.
	 *
	 * Verified 2026-08-16 against a live ONLYOFFICE: rendering the same document
	 * with and without the chart produced a 25,793-byte and a 51,777-byte PDF. The
	 * chart is drawn, not skipped.
	 *
	 * @param int $fileId The Nextcloud file id of the document to change.
	 * @param array<string, mixed> $chart The chart: type, title, categories, series.
	 * @param string $version The `version` returned by the preceding readDocument.
	 * @param string $afterAnchor An anchor to place the chart after, or empty to append.
	 * @param string $outputMode Empty for the configured default, or "sibling" to write a new file.
	 *
	 * @return array<string, mixed> The written file's id, name, path and version.
	 *
	 * @throws RuntimeException When the version is stale, the definition is invalid, or the write is refused.
	 *
	 * @spec openspec/specs/document-chart-embedding/spec.md
	 */
	public function addDocumentChart(
		int $fileId,
		array $chart,
		string $version,
		string $afterAnchor = '',
		string $outputMode = ''
	): array {
		$anchor = null;
		if ($afterAnchor !== '') {
			$anchor = $afterAnchor;
		}

		$mode = null;
		if ($outputMode !== '') {
			$mode = $outputMode;
		}

		return $this->editSession->embedChartForAgent(
			uid: $this->requireUid(),
			fileId: $fileId,
			chart: $chart,
			version: $version,
			afterAnchor: $anchor,
			requestedMode: $mode
		);

	}//end addDocumentChart()

	#[McpTool(
		name: 'readDocumentMetadata',
		description: 'Read a Word (.docx) or OpenDocument (.odt) file\'s document properties: title, subject, '
			. 'creator, keywords and description. Use this before setDocumentMetadata: it returns the version '
			. 'that call requires. A property the document does not carry comes back as an empty string.',
		readOnlyHint: true,
		destructiveHint: false,
		idempotentHint: true,
		scope: 'read'
	)]
	/**
	 * Read a document's properties.
	 *
	 * Fields are named format-neutrally (`title`, `subject`, `creator`, `keywords`,
	 * `description`). The caller never sees that OOXML stores keywords as
	 * `cp:keywords` and ODF as `meta:keyword` -- a caller that had to know would be
	 * writing per-format code, which ADR-087 §2 exists to prevent.
	 *
	 * @param int $fileId The Nextcloud file id of the document to read.
	 *
	 * @return array<string, mixed> The document's name, version and metadata fields.
	 *
	 * @throws RuntimeException When the file cannot be read or the format has no metadata part.
	 *
	 * @spec openspec/specs/document-rich-editing/spec.md
	 */
	public function readDocumentMetadata(int $fileId): array {
		return $this->editSession->readMetadataForAgent(uid: $this->requireUid(), fileId: $fileId);

	}//end readDocumentMetadata()

	#[McpTool(
		name: 'setDocumentMetadata',
		description: 'Set a Word (.docx) or OpenDocument (.odt) file\'s document properties. Supported fields: '
			. 'title, subject, creator, keywords, description. Call readDocumentMetadata first and pass back '
			. 'the version it returned. Fields you do not name are left unchanged. Created and modified '
			. 'timestamps cannot be set. The file is tagged "Agent authored" and the previous version stays '
			. 'restorable in Nextcloud.',
		readOnlyHint: false,
		destructiveHint: false,
		idempotentHint: true,
		scope: 'update'
	)]
	/**
	 * Set a document's properties.
	 *
	 * Goes through the same editing session as a text change: same lock, same
	 * version recheck immediately before the write, same agent-authored tag applied
	 * before the bytes become visible. Metadata is a smaller change than a
	 * paragraph rewrite, not a less accountable one.
	 *
	 * `created` and `modified` are deliberately not writable. They record what
	 * happened to the document, and an agent that could set them would turn that
	 * record from a fact into a claim.
	 *
	 * @param int $fileId The Nextcloud file id of the document to change.
	 * @param array<string, string> $metadata Field name => new value.
	 * @param string $version The version returned by readDocumentMetadata.
	 * @param string $outputMode Empty for the configured default, or "sibling" to write a new file.
	 *
	 * @return array<string, mixed> The written file's id, name, path, version and the fields written.
	 *
	 * @throws RuntimeException When the version is stale, a field is unknown, or the write is refused.
	 *
	 * @spec openspec/specs/document-rich-editing/spec.md
	 */
	public function setDocumentMetadata(
		int $fileId,
		array $metadata,
		string $version,
		string $outputMode = ''
	): array {
		$mode = null;
		if ($outputMode !== '') {
			$mode = $outputMode;
		}

		return $this->editSession->setMetadataForAgent(
			uid: $this->requireUid(),
			fileId: $fileId,
			values: $metadata,
			version: $version,
			requestedMode: $mode
		);

	}//end setDocumentMetadata()

	#[McpTool(
		name: 'convertDocumentToPdf',
		description: 'Convert a document in the user\'s files to PDF, writing a new PDF file and leaving the '
			. 'source untouched. Reports which conversion backend produced the PDF. The produced file is '
			. 'tagged "Agent authored" in Files.',
		readOnlyHint: false,
		destructiveHint: false,
		idempotentHint: false,
		scope: 'create'
	)]
	/**
	 * Convert a document to PDF, leaving the source untouched.
	 *
	 * The conversion backend is chosen by DocuDesk's cascade, not by the caller
	 * -- so the tool cannot be steered onto a particular process -- but the
	 * backend that claimed the conversion IS reported, because an office-app
	 * conversion and the built-in fallback differ visibly in fidelity.
	 *
	 * @param int $fileId The Nextcloud file id of the document to convert.
	 *
	 * @return array<string, mixed> The produced PDF's id, name and path, and the backend that produced it.
	 *
	 * @throws RuntimeException When no backend in the cascade can convert this file.
	 *
	 * @spec openspec/specs/document-editing/spec.md#requirement-conversion-routes-through-the-nextcloud-conversion-broker
	 */
	public function convertDocumentToPdf(int $fileId): array {
		$uid = $this->requireUid();
		$source = $this->resolveReadableFile(uid: $uid, fileId: $fileId);

		try {
			$converted = $this->pdfConversion->convertToPdfReporting(source: $source);
		} catch (Throwable $e) {
			throw new RuntimeException(
				sprintf('"%s" could not be converted to PDF: %s', $source->getName(), $e->getMessage()),
				0,
				$e
			);
		}

		$pdf = $converted['file'];
		$this->marker->mark(fileId: $pdf->getId());

		$path = $this->relativePath(uid: $uid, file: $pdf);
		$this->record(
			uid: $uid,
			fileId: $pdf->getId(),
			path: $path,
			format: 'pdf',
			note: sprintf('Agent conversion of file %d via %s', $fileId, (string)$converted['backend'])
		);

		return [
			'fileId' => $pdf->getId(),
			'name' => $pdf->getName(),
			'path' => $path,
			'backend' => $converted['backend'],
			'agentAuthoredTag' => AgentArtefactMarker::TAG_NAME,
			'artefact' => [
				'type' => 'file',
				'id' => (string)$pdf->getId(),
			],
		];

	}//end convertDocumentToPdf()

	/**
	 * Record the produced artefact in the generated-document audit trail.
	 *
	 * There is no template behind an agent edit or a conversion, so `templateId`
	 * is genuinely empty rather than filled with a plausible-looking id. The row
	 * exists so the artefact is findable from DocuDesk's own register; the
	 * authoritative account of who asked for it is Hermiq's invocation record,
	 * which the returned `artefact` descriptor feeds.
	 *
	 * A failure to record is logged and swallowed: the file is already written
	 * and already tagged, and throwing here would report a failure that did not
	 * happen.
	 *
	 * @param string $uid The acting user id.
	 * @param int $fileId The produced file id.
	 * @param string $path The produced file path.
	 * @param string $format The produced format.
	 * @param string $note A human-readable note about the operation.
	 *
	 * @return void
	 */
	private function record(string $uid, int $fileId, string $path, string $format, string $note): void {
		try {
			$this->documentLogger->log(
				template: [
					'id' => '',
					'version' => 0,
					'name' => $note,
				],
				dataRefs: [],
				format: $format,
				outcome: [
					'status' => 'generated',
					'warnings' => [$note],
					'caseId' => null,
					'errorMessage' => null,
					'fileId' => $fileId,
					'filePath' => $path,
				],
				userId: $uid
			);
		} catch (Throwable $e) {
			$this->logger->warning('DocuDesk could not record an agent document artefact: ' . $e->getMessage());
		}

	}//end record()

	/**
	 * Resolve a file the acting user can read.
	 *
	 * @param string $uid The acting user id.
	 * @param int $fileId The Nextcloud file id.
	 *
	 * @return File The file.
	 *
	 * @throws RuntimeException When the id names nothing the user can reach.
	 */
	private function resolveReadableFile(string $uid, int $fileId): File {
		try {
			$node = $this->rootFolder->getUserFolder($uid)->getFirstNodeById($fileId);
		} catch (Throwable $e) {
			throw new RuntimeException('Could not open file ' . $fileId . ': ' . $e->getMessage(), 0, $e);
		}

		if (($node instanceof File) === false) {
			throw new RuntimeException('File ' . $fileId . ' was not found in your files.');
		}

		return $node;

	}//end resolveReadableFile()

	/**
	 * Express a file's path relative to the acting user's root.
	 *
	 * @param string $uid The acting user id.
	 * @param File $file The file.
	 *
	 * @return string The relative path, or the file name when it cannot be derived.
	 */
	private function relativePath(string $uid, File $file): string {
		try {
			return $this->rootFolder->getUserFolder($uid)->getRelativePath($file->getPath()) ?? $file->getName();
		} catch (Throwable) {
			return $file->getName();
		}

	}//end relativePath()

	/**
	 * The acting user, or a refusal.
	 *
	 * These tools run in the caller's ambient Nextcloud session (ADR-041). There
	 * is no service user and no impersonation, so no session means no operation.
	 *
	 * @return string The acting user id.
	 *
	 * @throws RuntimeException When there is no signed-in user.
	 */
	private function requireUid(): string {
		$user = $this->userSession->getUser();
		if ($user === null) {
			throw new RuntimeException('Document tools require a signed-in user.');
		}

		return $user->getUID();

	}//end requireUid()
}//end class
