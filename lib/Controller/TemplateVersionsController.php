<?php

/**
 * Template Versions Controller
 *
 * Controller for the version-history surface of reusable document templates —
 * listing versions, restoring a previous version, and diffing two versions.
 * Split out of `TemplatesController`, which retains template CRUD, duplication
 * and locking.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/template-management/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use Exception;
use OCA\Filinq\Service\TemplateService;
use OCA\Filinq\Service\TemplateVersionService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserSession;

/**
 * Controller for template version history, restore and diff
 *
 * @category Controller
 * @package  OCA\Filinq\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class TemplateVersionsController extends Controller {
	/**
	 * Constructor for TemplateVersionsController
	 *
	 * @param string $appName The application name
	 * @param IRequest $request The request object
	 * @param TemplateService $templateService Service for template operations
	 * @param TemplateRequestHandler $requestHandler Request param parser and error handler
	 * @param TemplateVersionService $versionService Service for version operations
	 * @param IUserSession $userSession User session for current user
	 *
	 * @return void
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly TemplateService $templateService,
		private readonly TemplateRequestHandler $requestHandler,
		private readonly TemplateVersionService $versionService,
		private readonly IUserSession $userSession,
	) {
		parent::__construct(appName: $appName, request: $request);

	}//end __construct()

	/**
	 * List version history for a template
	 *
	 * @param string $id The template UUID
	 *
	 * @return JSONResponse JSON response with version list
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @spec openspec/specs/template-management/spec.md
	 */
	public function versions(string $id): JSONResponse {
		try {
			$user = $this->userSession->getUser();
			if ($user === null) {
				return new JSONResponse(
					data: ['error' => 'Not authenticated'],
					statusCode: Http::STATUS_UNAUTHORIZED
				);
			}

			$limit = (int)$this->request->getParam('_limit', '20');
			$offset = (int)$this->request->getParam('_offset', '0');
			$result = $this->versionService->getVersions(
				templateId: $id,
				limit: $limit,
				offset: $offset
			);
			return new JSONResponse(data: $result);
		} catch (Exception $e) {
			return $this->requestHandler->buildErrorResponse($e, 'Failed to list versions: ');
		}

	}//end versions()

	/**
	 * Restore a template to a previous version
	 *
	 * @param string $id The template UUID
	 * @param string $versionId The version UUID to restore
	 *
	 * @return JSONResponse JSON response with the restored template object
	 *
	 * @NoAdminRequired
	 *
	 * @spec openspec/specs/template-management/spec.md
	 */
	public function restoreVersion(string $id, string $versionId): JSONResponse {
		try {
			$user = $this->userSession->getUser();
			if ($user === null) {
				return new JSONResponse(
					data: ['error' => 'Not authenticated'],
					statusCode: Http::STATUS_UNAUTHORIZED
				);
			}

			$result = $this->versionService->restoreVersion(
				templateId: $id,
				versionId: $versionId,
				editor: $user->getUID(),
				service: $this->templateService
			);
			return new JSONResponse(data: $result);
		} catch (Exception $e) {
			return $this->requestHandler->buildErrorResponse($e, 'Failed to restore version: ');
		}

	}//end restoreVersion()

	/**
	 * Get two versions for diff comparison
	 *
	 * The route carries the owning template UUID for REST symmetry, but the
	 * diff is resolved purely from the two version UUIDs in `from` / `to`, so
	 * the path segment is deliberately not part of this signature.
	 *
	 * @return JSONResponse JSON response with both version objects
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @spec openspec/specs/template-management/spec.md
	 */
	public function diffVersions(): JSONResponse {
		try {
			$user = $this->userSession->getUser();
			if ($user === null) {
				return new JSONResponse(
					data: ['error' => 'Not authenticated'],
					statusCode: Http::STATUS_UNAUTHORIZED
				);
			}

			$from = $this->request->getParam('from', '');
			$to = $this->request->getParam('to', '');

			if (empty($from) === true || empty($to) === true) {
				throw new Exception(
					message: 'Both "from" and "to" version UUIDs are required',
					code: 400
				);
			}

			$result = $this->versionService->getDiff(
				versionIdFrom: $from,
				versionIdTo: $to
			);
			return new JSONResponse(data: $result);
		} catch (Exception $e) {
			return $this->requestHandler->buildErrorResponse($e, 'Failed to get version diff: ');
		}//end try

	}//end diffVersions()
}//end class
