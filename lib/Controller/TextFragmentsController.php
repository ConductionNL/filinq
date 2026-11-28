<?php

/**
 * Text Fragments Controller
 *
 * CRUD for text fragments (bouwstenen): text written once and inserted into
 * many templates with ${fragment:slug}.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-11-25-office-template-authoring/specs/office-template-authoring/spec.md#requirement-reusable-text-fragments-bouwstenen-resolve-in-templates-req-ddota-004
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use Exception;
use OCA\Filinq\Service\OfficeTemplate\TextFragmentService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserSession;

/**
 * `/api/text-fragments`: list, read, create, update and delete fragments.
 *
 * Who may write is decided by the `textFragment` schema's authorisation in
 * OpenRegister (template editors); every call goes through it.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-11-25-office-template-authoring/specs/office-template-authoring/spec.md#requirement-reusable-text-fragments-bouwstenen-resolve-in-templates-req-ddota-004
 */
class TextFragmentsController extends Controller {
	use AnswersNotAuthenticated;

	/**
	 * The query parameters a list filters on.
	 */
	private const FILTERS = ['namespace', 'category', 'language', 'slug', '_search'];

	/**
	 * Constructor.
	 *
	 * @param string                 $appName        The application name.
	 * @param IRequest               $request        The request.
	 * @param TextFragmentService    $fragments      The fragments.
	 * @param TemplateRequestHandler $requestHandler Shapes error responses.
	 * @param IUserSession           $userSession    The signed-in user.
	 *
	 * @spec openspec/changes/archive/2026-11-25-office-template-authoring/specs/office-template-authoring/spec.md#requirement-reusable-text-fragments-bouwstenen-resolve-in-templates-req-ddota-004
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly TextFragmentService $fragments,
		private readonly TemplateRequestHandler $requestHandler,
		private readonly IUserSession $userSession,
	) {
		parent::__construct(appName: $appName, request: $request);
	}//end __construct()

	/**
	 * A page of fragments, filtered by namespace, category, language, slug or `_search`.
	 *
	 * @param int $_limit  The page size (1-200).
	 * @param int $_offset The offset.
	 *
	 * @return JSONResponse
	 *
	 * @NoAdminRequired
	 *
	 * @spec openspec/changes/archive/2026-11-25-office-template-authoring/specs/office-template-authoring/spec.md#requirement-reusable-text-fragments-bouwstenen-resolve-in-templates-req-ddota-004
	 */
	public function index(int $_limit=20, int $_offset=0): JSONResponse {
		return $this->answer(
			work: function () use ($_limit, $_offset): array {
				$filters = [];
				foreach (self::FILTERS as $key) {
					$value = $this->request->getParam($key);
					if (is_string($value) === true && $value !== '') {
						$filters[$key] = $value;
					}
				}

				return $this->fragments->list(filters: $filters, limit: max(1, min(200, $_limit)), offset: max(0, $_offset));
			},
			status: Http::STATUS_OK
		);
	}//end index()

	/**
	 * One fragment.
	 *
	 * @param string $id The fragment's id.
	 *
	 * @return JSONResponse
	 *
	 * @NoAdminRequired
	 *
	 * @spec openspec/changes/archive/2026-11-25-office-template-authoring/specs/office-template-authoring/spec.md#requirement-reusable-text-fragments-bouwstenen-resolve-in-templates-req-ddota-004
	 */
	public function show(string $id): JSONResponse {
		return $this->answer(work: fn (): array => $this->fragments->get(id: $id), status: Http::STATUS_OK);
	}//end show()

	/**
	 * Create a fragment from the body (name, slug, content, namespace, category, tags, language).
	 *
	 * @return JSONResponse 201 with the fragment; 400 or 409 when refused.
	 *
	 * @NoAdminRequired
	 *
	 * @spec openspec/changes/archive/2026-11-25-office-template-authoring/specs/office-template-authoring/spec.md#requirement-reusable-text-fragments-bouwstenen-resolve-in-templates-req-ddota-004
	 */
	public function create(): JSONResponse {
		return $this->answer(
			work: fn (): array => $this->fragments->create(data: $this->requestHandler->parseBodyParams(request: $this->request)),
			status: Http::STATUS_CREATED
		);
	}//end create()

	/**
	 * Update a fragment with the fields in the body.
	 *
	 * @param string $id The fragment's id.
	 *
	 * @return JSONResponse
	 *
	 * @NoAdminRequired
	 *
	 * @spec openspec/changes/archive/2026-11-25-office-template-authoring/specs/office-template-authoring/spec.md#requirement-reusable-text-fragments-bouwstenen-resolve-in-templates-req-ddota-004
	 */
	public function update(string $id): JSONResponse {
		return $this->answer(
			work: fn (): array => $this->fragments->update(id: $id, data: $this->requestHandler->parseBodyParams(request: $this->request, stripKeys: ['id'])),
			status: Http::STATUS_OK
		);
	}//end update()

	/**
	 * Delete a fragment.
	 *
	 * @param string $id The fragment's id.
	 *
	 * @return JSONResponse
	 *
	 * @NoAdminRequired
	 *
	 * @spec openspec/changes/archive/2026-11-25-office-template-authoring/specs/office-template-authoring/spec.md#requirement-reusable-text-fragments-bouwstenen-resolve-in-templates-req-ddota-004
	 */
	public function destroy(string $id): JSONResponse {
		return $this->answer(
			work: function () use ($id): array {
				$this->fragments->delete(id: $id);
				return ['deleted' => true];
			},
			status: Http::STATUS_OK
		);
	}//end destroy()

	/**
	 * Run the work for a signed-in user and shape its answer or error.
	 *
	 * @param callable $work   Returns the response data.
	 * @param int      $status The success status.
	 *
	 * @return JSONResponse
	 */
	private function answer(callable $work, int $status): JSONResponse {
		if ($this->userSession->getUser() === null) {
			return $this->notAuthenticated(message: 'Not authenticated');
		}

		try {
			return new JSONResponse(data: $work(), statusCode: $status);
		} catch (Exception $e) {
			return $this->requestHandler->buildErrorResponse($e, 'Text fragment request failed: ');
		}
	}//end answer()
}//end class
