<?php

/**
 * Office Templates Controller
 *
 * Creates a template from an uploaded DOCX or ODT with `${field}` merge tags.
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
use OCA\Filinq\Service\OfficeTemplate\OfficeTemplateService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserSession;

/**
 * `POST /api/templates/office`: an uploaded office file becomes a template.
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
	 *
	 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly OfficeTemplateService $officeTemplates,
		private readonly TemplateRequestHandler $requestHandler,
		private readonly IUserSession $userSession,
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

		$upload = $this->request->getUploadedFile('file');
		if (is_array($upload) === false || ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
			|| is_readable((string) ($upload['tmp_name'] ?? '')) === false
		) {
			return new JSONResponse(data: ['error' => 'Upload a DOCX or ODT file as "file".'], statusCode: Http::STATUS_BAD_REQUEST);
		}

		try {
			$result = $this->officeTemplates->create(
				fields: $this->requestHandler->parseBodyParams(request: $this->request),
				fileName: (string) ($upload['name'] ?? ''),
				bytes: (string) file_get_contents((string) $upload['tmp_name'])
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
}//end class
