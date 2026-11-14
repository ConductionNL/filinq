<?php

/**
 * Office Templates Controller
 *
 * Creates a template from an uploaded DOCX or ODT with `${field}` merge tags,
 * takes a new source version, serves the source and a PDF preview, and sets
 * the field mapping.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use Exception;
use OCA\Filinq\Exception\OfficeSourceRefusedException;
use OCA\Filinq\Kernel\Contracts\TemplateServiceInterface;
use OCA\Filinq\Service\DataResolverService;
use OCA\Filinq\Service\OfficeTemplate\OfficeSourceStore;
use OCA\Filinq\Service\OfficeTemplate\OfficeTemplateRenderer;
use OCA\Filinq\Service\OfficeTemplate\OfficeTemplateService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserSession;

/**
 * The office template endpoints under `/api/templates`.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md
 */
class OfficeTemplatesController extends Controller {
	use AnswersNotAuthenticated;

	/**
	 * Constructor.
	 *
	 * @param string                 $appName        The application name.
	 * @param IRequest               $request        The request.
	 * @param OfficeTemplateService  $officeTemplates Creates office templates.
	 * @param TemplateRequestHandler $requestHandler Shapes error responses.
	 * @param IUserSession           $userSession    The signed-in user.
	 * @param TemplateServiceInterface $templates   Reads templates with the caller's access.
	 * @param OfficeTemplateRenderer $renderer       Fills and converts office templates.
	 * @param OfficeSourceStore      $store          The stored sources.
	 * @param DataResolverService    $data           Resolves preview data references.
	 *
	 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly OfficeTemplateService $officeTemplates,
		private readonly TemplateRequestHandler $requestHandler,
		private readonly IUserSession $userSession,
		private readonly TemplateServiceInterface $templates,
		private readonly OfficeTemplateRenderer $renderer,
		private readonly OfficeSourceStore $store,
		private readonly DataResolverService $data,
	) {
		parent::__construct(appName: $appName, request: $request);
	}//end __construct()

	/**
	 * Create an office template from the multipart `file`, with `name`,
	 * `namespace` and the optional template fields as form fields.
	 *
	 * Answers the created template, whether an ODT was converted, and the tag
	 * report; 422 with the reason when the file or its tags are refused.
	 *
	 * @return JSONResponse
	 *
	 * @NoAdminRequired
	 *
	 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md
	 */
	public function create(): JSONResponse {
		if ($this->userSession->getUser() === null) {
			return $this->notAuthenticated(message: 'Not authenticated');
		}

		$upload = $this->upload();
		if ($upload === null) {
			return new JSONResponse(data: ['error' => 'Upload a DOCX or ODT file as "file".'], statusCode: Http::STATUS_BAD_REQUEST);
		}

		try {
			$result = $this->officeTemplates->create(
				fields: $this->requestHandler->parseBodyParams(request: $this->request),
				fileName: $upload['name'],
				bytes: $upload['bytes']
			);
			return new JSONResponse(data: $result, statusCode: Http::STATUS_CREATED);
		} catch (OfficeSourceRefusedException $e) {
			return new JSONResponse(
				data: array_merge(['error' => $e->getMessage()], $e->getErrorDetails()),
				statusCode: Http::STATUS_UNPROCESSABLE_ENTITY
			);
		} catch (Exception $e) {
			return $this->requestHandler->buildErrorResponse($e, 'Failed to create office template: ');
		}//end try
	}//end create()

	/**
	 * Give an office template a new source file (multipart `file`, optional
	 * `changelog`), as a new version under the edit lock.
	 *
	 * @param string $id The template's id.
	 *
	 * @return JSONResponse The saved template, whether an ODT was converted,
	 *                      and the tag report; 422 when the file is refused,
	 *                      409 when the template is locked or not an office template.
	 *
	 * @NoAdminRequired
	 *
	 * @spec openspec/changes/office-template-authoring/specs/template-management/spec.md
	 */
	public function newSource(string $id): JSONResponse {
		if ($this->userSession->getUser() === null) {
			return $this->notAuthenticated(message: 'Not authenticated');
		}

		$upload = $this->upload();
		if ($upload === null) {
			return new JSONResponse(data: ['error' => 'Upload a DOCX or ODT file as "file".'], statusCode: Http::STATUS_BAD_REQUEST);
		}

		try {
			$changelog = $this->request->getParam('changelog');
			if (is_string($changelog) === false) {
				$changelog = null;
			}

			$result = $this->officeTemplates->replaceSource(id: $id, fileName: $upload['name'], bytes: $upload['bytes'], changelog: $changelog);
			return new JSONResponse(data: $result);
		} catch (OfficeSourceRefusedException $e) {
			return new JSONResponse(
				data: array_merge(['error' => $e->getMessage()], $e->getErrorDetails()),
				statusCode: Http::STATUS_UNPROCESSABLE_ENTITY
			);
		} catch (Exception $e) {
			return $this->requestHandler->buildErrorResponse($e, 'Failed to replace office template source: ');
		}//end try
	}//end newSource()

	/**
	 * Download an office template's source DOCX.
	 *
	 * The template is read with the caller's access first, so a caller who
	 * may not read the template gets no file.
	 *
	 * @param string $id The template's id.
	 *
	 * @return JSONResponse|DataDownloadResponse
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md#requirement-office-files-are-accepted-as-first-class-templates-req-ddota-001
	 */
	public function source(string $id): JSONResponse|DataDownloadResponse {
		if ($this->userSession->getUser() === null) {
			return $this->notAuthenticated(message: 'Not authenticated');
		}

		try {
			$template = $this->officeTemplate(id: $id);
			$bytes = $this->store->read(fileId: (int) ($template['sourceFileId'] ?? 0));
			if ($bytes === null) {
				return new JSONResponse(data: ['error' => 'The source file of this office template is not stored.'], statusCode: Http::STATUS_NOT_FOUND);
			}

			return new DataDownloadResponse(
				$bytes,
				$this->fileName(name: (string) ($template['name'] ?? 'template'), extension: 'docx'),
				'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
			);
		} catch (Exception $e) {
			return $this->requestHandler->buildErrorResponse($e, 'Failed to read office template source: ');
		}
	}//end source()

	/**
	 * A PDF preview of an office template through the same conversion
	 * cascade a generation uses, filled with `dataRefs` and `adHocData` when
	 * given and empty tags otherwise. Nothing is stored or logged.
	 *
	 * @param string                           $id        The template's id.
	 * @param array<int, array<string, mixed>> $dataRefs  Data references: [{register, schema, id}, ...].
	 * @param array<string, mixed>             $adHocData Extra data.
	 *
	 * @return JSONResponse|DataDownloadResponse The PDF, with the warnings in the X-Filinq-Warnings header.
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @spec openspec/changes/office-template-authoring/specs/template-management/spec.md
	 */
	public function preview(string $id, array $dataRefs=[], array $adHocData=[]): JSONResponse|DataDownloadResponse {
		if ($this->userSession->getUser() === null) {
			return $this->notAuthenticated(message: 'Not authenticated');
		}

		try {
			$template = $this->officeTemplate(id: $id);
			$data = [];
			$warnings = [];
			if ($dataRefs !== [] || $adHocData !== []) {
				$resolution = $this->data->resolve(dataRefs: $dataRefs, listRefs: [], adHocData: $adHocData);
				$data = $resolution['data'];
				$warnings = $resolution['warnings'];
			}

			$rendered = $this->renderer->render(template: $template, data: $data, format: 'pdf');
			$name = $this->fileName(name: (string) ($template['name'] ?? 'template'), extension: 'pdf');
			$response = new DataDownloadResponse($rendered['content'], $name, 'application/pdf');
			// Shown in the browser's PDF viewer, not saved.
			$response->addHeader('Content-Disposition', 'inline; filename="'.$name.'"');
			$response->addHeader('X-Filinq-Warnings', (string) json_encode(array_merge($warnings, $rendered['warnings'])));

			return $response;
		} catch (Exception $e) {
			return $this->requestHandler->buildErrorResponse($e, 'Failed to preview office template: ');
		}//end try
	}//end preview()

	/**
	 * Set the field mapping of an office template: `fieldMap` is
	 * `{tag: data path}`; an empty path removes that tag's mapping.
	 *
	 * @param string               $id       The template's id.
	 * @param array<string, mixed> $fieldMap Tag => data path.
	 *
	 * @return JSONResponse The saved template; 400 for an unknown tag or a bad path.
	 *
	 * @NoAdminRequired
	 *
	 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md#requirement-bulk-import-migrates-existing-template-estates-req-ddota-005
	 */
	public function fieldMap(string $id, array $fieldMap=[]): JSONResponse {
		if ($this->userSession->getUser() === null) {
			return $this->notAuthenticated(message: 'Not authenticated');
		}

		try {
			return new JSONResponse(data: $this->officeTemplates->setFieldMap(id: $id, fieldMap: $fieldMap));
		} catch (Exception $e) {
			return $this->requestHandler->buildErrorResponse($e, 'Failed to set the field mapping: ');
		}
	}//end fieldMap()

	/**
	 * An office template, read with the caller's access.
	 *
	 * @param string $id The template's id.
	 *
	 * @return array<string, mixed>
	 *
	 * @throws Exception With code 404 when it is unknown, 409 when it is a Twig template.
	 */
	private function officeTemplate(string $id): array {
		$template = $this->templates->getTemplate(id: $id);
		if ($this->renderer->isOffice(template: $template) === false) {
			throw new Exception(message: 'This is not an office template', code: 409);
		}

		return $template;
	}//end officeTemplate()

	/**
	 * The uploaded `file`, or null when there is none.
	 *
	 * @return array{name: string, bytes: string}|null
	 */
	private function upload(): ?array {
		$upload = $this->request->getUploadedFile('file');
		if (is_array($upload) === false || ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
			|| is_readable((string) ($upload['tmp_name'] ?? '')) === false
		) {
			return null;
		}

		return ['name' => (string) ($upload['name'] ?? ''), 'bytes' => (string) file_get_contents((string) $upload['tmp_name'])];
	}//end upload()

	/**
	 * A safe download name.
	 *
	 * @param string $name      The template's name.
	 * @param string $extension The extension.
	 *
	 * @return string
	 */
	private function fileName(string $name, string $extension): string {
		$safe = trim((string) preg_replace('/[^\pL\pN _.-]+/u', '', $name));
		if ($safe === '') {
			$safe = 'template';
		}

		return $safe.'.'.$extension;
	}//end fileName()
}//end class
