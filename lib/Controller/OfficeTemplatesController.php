<?php

/**
 * Office templates controller
 *
 * Upload of DOCX/ODT templates, new source revisions, the field mapping,
 * the source download and the bulk import of a ZIP.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/office-template-authoring/tasks.md#3-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use Exception;
use OCA\Filinq\Service\OfficeTemplate\OfficeTemplateRefused;
use OCA\Filinq\Service\OfficeTemplate\OfficeTemplateService;
use OCA\Filinq\Service\OfficeTemplate\TemplateEditorGuard;
use OCA\Filinq\Service\OfficeTemplate\TemplateImportService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserSession;

/**
 * Endpoints for office templates and their bulk import.
 *
 * @spec openspec/changes/office-template-authoring/tasks.md#3-1
 */
class OfficeTemplatesController extends Controller {

	/**
	 * Constructor.
	 *
	 * @param string                $appName         The app name.
	 * @param IRequest              $request         The request.
	 * @param OfficeTemplateService $officeTemplates Creates and revises office templates.
	 * @param TemplateImportService $imports         Runs bulk imports.
	 * @param TemplateEditorGuard   $editors         Checks the caller may change templates.
	 * @param TemplateRequestHandler $requestHandler Error responses.
	 * @param IUserSession          $userSession     The caller.
	 *
	 * @return void
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly OfficeTemplateService $officeTemplates,
		private readonly TemplateImportService $imports,
		private readonly TemplateEditorGuard $editors,
		private readonly TemplateRequestHandler $requestHandler,
		private readonly IUserSession $userSession,
	) {
		parent::__construct(appName: $appName, request: $request);

	}//end __construct()

	/**
	 * Create an office template from an uploaded DOCX or ODT (multipart: file, name, namespace, ...).
	 *
	 * @return JSONResponse The template, the converted flag and the tag report; 422 when refused.
	 *
	 * @NoAdminRequired
	 *
	 * @spec openspec/changes/office-template-authoring/tasks.md#3-1
	 */
	public function create(): JSONResponse {
		try {
			$upload = $this->upload();
			$result = $this->officeTemplates->createFromUpload(
				fileName: $upload['name'],
				bytes: $upload['bytes'],
				meta: $this->meta()
			);

			return new JSONResponse(data: $result, statusCode: Http::STATUS_CREATED);
		} catch (OfficeTemplateRefused $e) {
			return new JSONResponse(data: $e->toResponse(), statusCode: $e->getCode());
		} catch (Exception $e) {
			return $this->requestHandler->buildErrorResponse($e, 'Failed to create office template: ');
		}

	}//end create()

	/**
	 * Upload a new source revision of an office template.
	 *
	 * @param string $id The template.
	 *
	 * @return JSONResponse The template, the converted flag and the tag report.
	 *
	 * @NoAdminRequired
	 *
	 * @spec openspec/changes/office-template-authoring/tasks.md#3-1
	 */
	public function replaceSource(string $id): JSONResponse {
		try {
			$uid = $this->uid();
			$this->editors->requireTemplateEditor(userId: $uid);
			$upload = $this->upload();
			$result = $this->officeTemplates->replaceSource(
				templateId: $id,
				fileName: $upload['name'],
				bytes: $upload['bytes'],
				userId: $uid
			);

			return new JSONResponse(data: $result);
		} catch (OfficeTemplateRefused $e) {
			return new JSONResponse(data: $e->toResponse(), statusCode: $e->getCode());
		} catch (Exception $e) {
			return $this->requestHandler->buildErrorResponse($e, 'Failed to upload the office source: ');
		}

	}//end replaceSource()

	/**
	 * Store the field mapping of an office template (body: fieldMap).
	 *
	 * @param string $id The template.
	 *
	 * @return JSONResponse The template with its rechecked tag report.
	 *
	 * @NoAdminRequired
	 *
	 * @spec openspec/changes/office-template-authoring/tasks.md#4-3
	 */
	public function fieldMap(string $id): JSONResponse {
		try {
			$uid = $this->uid();
			$this->editors->requireTemplateEditor(userId: $uid);
			$fieldMap = $this->request->getParam('fieldMap', []);
			if (is_array($fieldMap) === false) {
				throw new OfficeTemplateRefused(message: 'fieldMap must be an object.', reason: 'field-map', code: 400);
			}

			return new JSONResponse(data: $this->officeTemplates->updateFieldMap(templateId: $id, fieldMap: $fieldMap, userId: $uid));
		} catch (OfficeTemplateRefused $e) {
			return new JSONResponse(data: $e->toResponse(), statusCode: $e->getCode());
		} catch (Exception $e) {
			return $this->requestHandler->buildErrorResponse($e, 'Failed to store the field mapping: ');
		}

	}//end fieldMap()

	/**
	 * Download the DOCX source of an office template.
	 *
	 * @param string $id The template.
	 *
	 * @return DataDownloadResponse|JSONResponse The DOCX.
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @no-admin-idor-exempt Templates are readable by every signed-in user (template schema read: authenticated), as GET api/templates/{id} is.
	 *
	 * @spec openspec/changes/office-template-authoring/tasks.md#4-2
	 */
	public function source(string $id): DataDownloadResponse|JSONResponse {
		try {
			$this->uid();
			$source = $this->officeTemplates->source(templateId: $id);

			return new DataDownloadResponse(
				$source['bytes'],
				$source['name'],
				'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
			);
		} catch (OfficeTemplateRefused $e) {
			return new JSONResponse(data: $e->toResponse(), statusCode: $e->getCode());
		} catch (Exception $e) {
			return $this->requestHandler->buildErrorResponse($e, 'Failed to read the office source: ');
		}

	}//end source()

	/**
	 * Start a bulk import of a ZIP (multipart: file, namespace, boundRegister, boundSchema).
	 *
	 * @return JSONResponse The queued templateImportJob (202).
	 *
	 * @NoAdminRequired
	 *
	 * @spec openspec/changes/office-template-authoring/tasks.md#3-1
	 */
	public function import(): JSONResponse {
		try {
			$uid = $this->uid();
			$upload = $this->upload();
			$job = $this->imports->start(
				archiveName: $upload['name'],
				bytes: $upload['bytes'],
				meta: $this->meta(),
				userId: $uid
			);

			return new JSONResponse(data: $job, statusCode: Http::STATUS_ACCEPTED);
		} catch (OfficeTemplateRefused $e) {
			return new JSONResponse(data: $e->toResponse(), statusCode: $e->getCode());
		} catch (Exception $e) {
			return $this->requestHandler->buildErrorResponse($e, 'Failed to start the import: ');
		}

	}//end import()

	/**
	 * The state and report of an import job, for the user who started it.
	 *
	 * @param string $jobId The job.
	 *
	 * @return JSONResponse The job; 404 for another user's job.
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @spec openspec/changes/office-template-authoring/tasks.md#3-1
	 */
	public function importStatus(string $jobId): JSONResponse {
		try {
			$job = $this->imports->status(jobId: $jobId, userId: $this->uid());
			$this->requireImportOwner(job: $job);

			return new JSONResponse(data: $job);
		} catch (OfficeTemplateRefused $e) {
			return new JSONResponse(data: $e->toResponse(), statusCode: $e->getCode());
		} catch (Exception $e) {
			return $this->requestHandler->buildErrorResponse($e, 'Failed to read the import: ');
		}

	}//end importStatus()

	/**
	 * Refuse a job the caller did not start (the service already answers 404; this is the visible check).
	 *
	 * @param array $job The job.
	 *
	 * @return void
	 *
	 * @throws OfficeTemplateRefused 404 for another user's job.
	 */
	private function requireImportOwner(array $job): void {
		if (($job['startedBy'] ?? '') !== $this->uid()) {
			throw new OfficeTemplateRefused(message: 'Import job not found.', reason: 'not-found', code: 404);
		}

	}//end requireImportOwner()

	/**
	 * The caller's user id.
	 *
	 * @return string The uid.
	 *
	 * @throws OfficeTemplateRefused 401 without a user.
	 */
	private function uid(): string {
		$user = $this->userSession->getUser();
		if ($user === null) {
			throw new OfficeTemplateRefused(message: 'Not authenticated', reason: 'unauthenticated', code: 401);
		}

		return $user->getUID();

	}//end uid()

	/**
	 * The uploaded file of the request.
	 *
	 * @return array{name: string, bytes: string}
	 *
	 * @throws OfficeTemplateRefused 400 without a file.
	 */
	private function upload(): array {
		$file = $this->request->getUploadedFile('file');
		$readable = is_array($file) === true && is_readable((string) ($file['tmp_name'] ?? '')) === true;
		if ($readable === false || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
			throw new OfficeTemplateRefused(message: 'Upload a file in the field "file".', reason: 'no-file', code: 400);
		}

		return ['name' => (string) ($file['name'] ?? 'upload'), 'bytes' => (string) file_get_contents((string) $file['tmp_name'])];

	}//end upload()

	/**
	 * The template fields sent beside the file.
	 *
	 * @return array<string, mixed> The fields.
	 */
	private function meta(): array {
		$meta = [];
		foreach (['name', 'namespace', 'description', 'category', 'boundRegister', 'boundSchema'] as $key) {
			$value = $this->request->getParam($key);
			if (is_string($value) === true && trim($value) !== '') {
				$meta[$key] = trim($value);
			}
		}

		foreach (['tags', 'fieldMap'] as $key) {
			$value = $this->request->getParam($key);
			if (is_string($value) === true) {
				$value = json_decode($value, true);
			}

			if (is_array($value) === true) {
				$meta[$key] = $value;
			}
		}

		return $meta;

	}//end meta()
}//end class
