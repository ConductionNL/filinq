<?php

/**
 * Template Imports Controller
 *
 * Starts a bulk import of office templates and text fragments and answers
 * its progress.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md#requirement-bulk-import-migrates-existing-template-estates-req-ddota-005
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use Exception;
use OCA\Filinq\Service\OfficeTemplate\TemplateImportService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserSession;

/**
 * `POST /api/templates/import` and `GET /api/templates/import/{jobId}`.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md#requirement-bulk-import-migrates-existing-template-estates-req-ddota-005
 */
class TemplateImportsController extends Controller {
	use AnswersNotAuthenticated;

	/**
	 * Constructor.
	 *
	 * @param string                 $appName        The application name.
	 * @param IRequest               $request        The request.
	 * @param TemplateImportService  $imports        Starts and reads imports.
	 * @param TemplateRequestHandler $requestHandler Shapes error responses.
	 * @param IUserSession           $userSession    The signed-in user.
	 *
	 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md#requirement-bulk-import-migrates-existing-template-estates-req-ddota-005
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly TemplateImportService $imports,
		private readonly TemplateRequestHandler $requestHandler,
		private readonly IUserSession $userSession,
	) {
		parent::__construct(appName: $appName, request: $request);
	}//end __construct()

	/**
	 * Start an import of the multipart ZIP `file`, or of the Files folder
	 * `path`, with `namespace` and the optional `boundRegister`/`boundSchema`.
	 *
	 * @return JSONResponse 202 with the queued job.
	 *
	 * @NoAdminRequired
	 *
	 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md#requirement-bulk-import-migrates-existing-template-estates-req-ddota-005
	 */
	public function create(): JSONResponse {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return $this->notAuthenticated(message: 'Not authenticated');
		}

		$archive = null;
		$upload = $this->request->getUploadedFile('file');
		if (is_array($upload) === true && ($upload['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK
			&& is_readable((string) ($upload['tmp_name'] ?? '')) === true
		) {
			$archive = (string) file_get_contents((string) $upload['tmp_name']);
		}

		try {
			$job = $this->imports->start(
				fields: $this->requestHandler->parseBodyParams(request: $this->request),
				archive: $archive,
				userId: $user->getUID()
			);
			return new JSONResponse(data: $job, statusCode: Http::STATUS_ACCEPTED);
		} catch (Exception $e) {
			return $this->requestHandler->buildErrorResponse($e, 'Failed to start template import: ');
		}
	}//end create()

	/**
	 * An import job: its status, counts and per-file report.
	 *
	 * @param string $jobId The job's id.
	 *
	 * @return JSONResponse
	 *
	 * @NoAdminRequired
	 *
	 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md#requirement-bulk-import-migrates-existing-template-estates-req-ddota-005
	 */
	public function show(string $jobId): JSONResponse {
		if ($this->userSession->getUser() === null) {
			return $this->notAuthenticated(message: 'Not authenticated');
		}

		try {
			return new JSONResponse(data: $this->imports->get(jobId: $jobId));
		} catch (Exception $e) {
			return $this->requestHandler->buildErrorResponse($e, 'Failed to read template import: ');
		}
	}//end show()
}//end class
