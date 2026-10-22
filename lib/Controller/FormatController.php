<?php

/**
 * Format matrix endpoints
 *
 * Tells the generation and correspondence screens which output formats the
 * server can make now, so a clerk picks from what works instead of finding
 * out by failing.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-multi-format-output/tasks.md#task-3.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use Exception;
use OCA\Filinq\Service\FormatMatrixService;
use OCA\Filinq\Service\TemplateService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserSession;

/**
 * Serves the format matrix per instance and per template.
 *
 * @spec openspec/changes/archive/2026-09-29-multi-format-output/tasks.md#task-3.1
 *
 * @category Controller
 * @package  OCA\Filinq\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class FormatController extends Controller {
	/**
	 * Constructor.
	 *
	 * @param string              $appName     The app name.
	 * @param IRequest            $request     The request.
	 * @param FormatMatrixService $matrix      Computes the matrix.
	 * @param TemplateService     $templates   Reads the template, under OpenRegister's access rules.
	 * @param IUserSession        $userSession Who is asking.
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly FormatMatrixService $matrix,
		private readonly TemplateService $templates,
		private readonly IUserSession $userSession,
	) {
		parent::__construct(appName: $appName, request: $request);

	}//end __construct()

	/**
	 * GET /api/documents/formats?flow=documents|correspondence
	 *
	 * @return JSONResponse {formats: {<format>: {available, reason?}}}
	 *
	 * @NoAdminRequired
	 *
	 * @spec openspec/changes/archive/2026-09-29-multi-format-output/tasks.md#task-3.1
	 *
	 * @no-admin-idor-exempt reads no object: it reports which converters this server has.
	 */
	#[NoAdminRequired]
	public function instance(): JSONResponse {
		if ($this->userSession->getUser() === null) {
			return $this->answer(data: ['error' => 'Not authenticated'], status: Http::STATUS_UNAUTHORIZED);
		}

		$flow = 'documents';
		if ($this->request->getParam('flow') === 'correspondence') {
			$flow = 'correspondence';
		}

		return $this->answer(data: ['formats' => $this->matrix->forInstance(flow: $flow)], status: Http::STATUS_OK);

	}//end instance()

	/**
	 * GET /api/templates/{id}/formats
	 *
	 * @param string $id The template's id.
	 *
	 * @return JSONResponse {templateId, formats: {<format>: {available, reason?}}}, 404 when the template cannot be read
	 *
	 * @NoAdminRequired
	 *
	 * @spec openspec/changes/archive/2026-09-29-multi-format-output/tasks.md#task-3.1
	 *
	 * @no-admin-idor-exempt the template is read through OpenRegister with RBAC on
	 * (no `_rbac: false` on this path), so a template the caller may not read is a 404.
	 */
	#[NoAdminRequired]
	public function template(string $id): JSONResponse {
		if ($this->userSession->getUser() === null) {
			return $this->answer(data: ['error' => 'Not authenticated'], status: Http::STATUS_UNAUTHORIZED);
		}

		try {
			$template = $this->templates->getTemplate(id: $id);
		} catch (Exception $e) {
			return $this->answer(data: ['error' => 'Template not found'], status: Http::STATUS_NOT_FOUND);
		}

		return $this->answer(
			data: ['templateId' => $id, 'formats' => $this->matrix->forTemplate(template: $template)],
			status: Http::STATUS_OK
		);

	}//end template()

	/**
	 * A JSON answer nobody may cache: the matrix changes when LibreOffice does.
	 *
	 * @param array $data   The body.
	 * @param int   $status The HTTP status.
	 *
	 * @return JSONResponse
	 */
	private function answer(array $data, int $status): JSONResponse {
		$response = new JSONResponse(data: $data, statusCode: $status);
		$response->addHeader('Cache-Control', 'no-store');

		return $response;

	}//end answer()
}//end class
