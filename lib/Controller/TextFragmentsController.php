<?php

/**
 * Text fragments controller
 *
 * CRUD for text fragments (bouwstenen) that templates use as
 * `${fragment:slug}`.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/office-template-authoring/tasks.md#2-4
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use Exception;
use OCA\Filinq\Service\OfficeTemplate\OfficeTemplateRefused;
use OCA\Filinq\Service\OfficeTemplate\TemplateEditorGuard;
use OCA\Filinq\Service\OfficeTemplate\TextFragmentService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserSession;

/**
 * Endpoints for text fragments.
 *
 * @spec openspec/changes/office-template-authoring/tasks.md#2-4
 */
class TextFragmentsController extends Controller {

	/**
	 * Constructor.
	 *
	 * @param string                 $appName        The app name.
	 * @param IRequest               $request        The request.
	 * @param TextFragmentService    $fragments      The fragments.
	 * @param TemplateEditorGuard    $editors        Checks the caller may change fragments.
	 * @param TemplateRequestHandler $requestHandler Body parsing and error responses.
	 * @param IUserSession           $userSession    The caller.
	 *
	 * @return void
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly TextFragmentService $fragments,
		private readonly TemplateEditorGuard $editors,
		private readonly TemplateRequestHandler $requestHandler,
		private readonly IUserSession $userSession,
	) {
		parent::__construct(appName: $appName, request: $request);

	}//end __construct()

	/**
	 * List fragments, optionally of one namespace.
	 *
	 * @return JSONResponse {results, total}
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @spec openspec/changes/office-template-authoring/tasks.md#2-4
	 */
	public function index(): JSONResponse {
		return $this->respond(work: function (): array {
			$this->uid();
			$namespace = $this->request->getParam('namespace');
			$filters = [];
			if (is_string($namespace) === true && $namespace !== '') {
				$filters['namespace'] = $namespace;
			}

			$results = $this->fragments->list(filters: $filters);

			return ['results' => $results, 'total' => count($results)];
		});

	}//end index()

	/**
	 * One fragment.
	 *
	 * @param string $id The fragment.
	 *
	 * @return JSONResponse The fragment.
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @no-admin-idor-exempt Fragments are readable by every signed-in user (textFragment schema read: authenticated); the read keeps RBAC.
	 *
	 * @spec openspec/changes/office-template-authoring/tasks.md#2-4
	 */
	public function show(string $id): JSONResponse {
		return $this->respond(work: function () use ($id): array {
			$this->uid();

			return $this->fragments->get(id: $id);
		});

	}//end show()

	/**
	 * Create a fragment.
	 *
	 * @return JSONResponse The fragment (201).
	 *
	 * @NoAdminRequired
	 *
	 * @spec openspec/changes/office-template-authoring/tasks.md#2-4
	 */
	public function create(): JSONResponse {
		return $this->respond(
			work: function (): array {
				$this->uid();

				return $this->fragments->create(data: $this->requestHandler->parseBodyParams(request: $this->request));
			},
			status: Http::STATUS_CREATED
		);

	}//end create()

	/**
	 * Update a fragment.
	 *
	 * @param string $id The fragment.
	 *
	 * @return JSONResponse The fragment.
	 *
	 * @NoAdminRequired
	 *
	 * @spec openspec/changes/office-template-authoring/tasks.md#2-4
	 */
	public function update(string $id): JSONResponse {
		return $this->respond(work: function () use ($id): array {
			$this->editors->requireTemplateEditor(userId: $this->uid());

			return $this->fragments->update(id: $id, data: $this->requestHandler->parseBodyParams(request: $this->request));
		});

	}//end update()

	/**
	 * Delete a fragment.
	 *
	 * @param string $id The fragment.
	 *
	 * @return JSONResponse {success: true}
	 *
	 * @NoAdminRequired
	 *
	 * @spec openspec/changes/office-template-authoring/tasks.md#2-4
	 */
	public function destroy(string $id): JSONResponse {
		return $this->respond(work: function () use ($id): array {
			$this->editors->requireTemplateEditor(userId: $this->uid());
			$this->fragments->delete(id: $id);

			return ['success' => true];
		});

	}//end destroy()

	/**
	 * Run the work and shape its answer or refusal.
	 *
	 * @param callable $work   Returns the response data.
	 * @param int      $status The success status.
	 *
	 * @return JSONResponse The response.
	 */
	private function respond(callable $work, int $status=Http::STATUS_OK): JSONResponse {
		try {
			return new JSONResponse(data: $work(), statusCode: $status);
		} catch (OfficeTemplateRefused $e) {
			return new JSONResponse(data: $e->toResponse(), statusCode: $e->getCode());
		} catch (Exception $e) {
			return $this->requestHandler->buildErrorResponse($e, 'Text fragment request failed: ');
		}

	}//end respond()

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
}//end class
