<?php

/**
 * Template Preview Controller
 *
 * Controller for rendering template previews — either raw content posted by the
 * editor, or a stored template resolved by UUID. Split out of
 * `TemplatesController`, which retains template CRUD, duplication and locking.
 *
 * @category  Controller
 * @package   OCA\DocuDesk\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/changes/advanced-template-management/tasks.md#task-5
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Controller;

use Exception;
use OCA\DocuDesk\Service\TemplatePreviewService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserSession;

/**
 * Controller for template preview rendering
 *
 * @category Controller
 * @package  OCA\DocuDesk\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class TemplatePreviewController extends Controller {
	/**
	 * Constructor for TemplatePreviewController
	 *
	 * @param string $appName The application name
	 * @param IRequest $request The request object
	 * @param TemplateRequestHandler $requestHandler Request param parser and error handler
	 * @param TemplatePreviewService $previewService Service for preview rendering
	 * @param IUserSession $userSession User session for current user
	 *
	 * @return void
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly TemplateRequestHandler $requestHandler,
		private readonly TemplatePreviewService $previewService,
		private readonly IUserSession $userSession,
	) {
		parent::__construct(appName: $appName, request: $request);

	}//end __construct()

	/**
	 * Preview raw template content with sample data
	 *
	 * @return JSONResponse JSON response with rendered HTML
	 *
	 * @NoAdminRequired
	 *
	 * @spec openspec/changes/advanced-template-management/tasks.md#task-5
	 */
	public function preview(): JSONResponse {
		try {
			$user = $this->userSession->getUser();
			if ($user === null) {
				return new JSONResponse(
					data: ['error' => 'Not authenticated'],
					statusCode: Http::STATUS_UNAUTHORIZED
				);
			}

			$data = $this->requestHandler->parseBodyParams(request: $this->request);
			$content = $data['content'] ?? '';
			$context = $data['data'] ?? [];

			if (empty($content) === true) {
				throw new Exception(message: 'Content is required for preview', code: 400);
			}

			$html = $this->previewService->preview(content: $content, data: $context);
			return new JSONResponse(data: ['html' => $html]);
		} catch (Exception $e) {
			return $this->requestHandler->buildErrorResponse($e, 'Failed to preview template: ');
		}//end try

	}//end preview()

	/**
	 * Preview an existing template with sample data
	 *
	 * @param string $id The template UUID
	 *
	 * @return JSONResponse JSON response with rendered HTML
	 *
	 * @NoAdminRequired
	 *
	 * @spec openspec/changes/advanced-template-management/tasks.md#task-5
	 */
	public function previewTemplate(string $id): JSONResponse {
		try {
			$user = $this->userSession->getUser();
			if ($user === null) {
				return new JSONResponse(
					data: ['error' => 'Not authenticated'],
					statusCode: Http::STATUS_UNAUTHORIZED
				);
			}

			$data = $this->requestHandler->parseBodyParams(request: $this->request);
			$context = $data['data'] ?? [];
			$html = $this->previewService->previewTemplate(templateId: $id, data: $context);
			return new JSONResponse(data: ['html' => $html]);
		} catch (Exception $e) {
			return $this->requestHandler->buildErrorResponse($e, 'Failed to preview template: ');
		}

	}//end previewTemplate()
}//end class
