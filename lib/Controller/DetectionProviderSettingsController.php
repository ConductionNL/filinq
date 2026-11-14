<?php

/**
 * Detection Provider Settings Controller
 *
 * Admin routes for the per-organisation detection provider and the local
 * hermiq agentflow it calls.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/llm-entity-detection-provider/specs/llm-entity-detection-provider/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use InvalidArgumentException;
use OCA\Filinq\Kernel\AppIdentity;
use OCA\Filinq\Service\Detection\DetectionProviderConfig;
use OCA\Filinq\Service\Detection\LlmDetectionProvider;
use OCA\Filinq\Settings\FilinqAdmin;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\AuthorizedAdminSetting;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserSession;

/**
 * Get and set the detection-provider settings, and test the LLM endpoint.
 *
 * Fail-closed twice: the route attribute admits admins (and delegated admins of
 * Filinq's admin section) only, and every method also checks in its body that
 * the caller is signed in (401) and an instance admin (403) before reading or
 * writing anything. No secret is stored.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/llm-entity-detection-provider/specs/llm-entity-detection-provider/spec.md
 */
class DetectionProviderSettingsController extends Controller {

	/**
	 * Constructor.
	 *
	 * @param IRequest                $request      The request.
	 * @param DetectionProviderConfig $config       The detection settings.
	 * @param LlmDetectionProvider    $llmProvider  The LLM provider, for the connectivity test.
	 * @param IUserSession            $userSession  The session.
	 * @param IGroupManager           $groupManager The admin check.
	 *
	 * @spec openspec/changes/llm-entity-detection-provider/tasks.md#task-4.1
	 */
	public function __construct(
		IRequest $request,
		private readonly DetectionProviderConfig $config,
		private readonly LlmDetectionProvider $llmProvider,
		private readonly IUserSession $userSession,
		private readonly IGroupManager $groupManager,
	) {
		parent::__construct(appName: AppIdentity::APP_ID, request: $request);
	}//end __construct()

	/**
	 * The current settings.
	 *
	 * @return JSONResponse 200 with the settings, 401 or 403.
	 *
	 * @spec openspec/changes/llm-entity-detection-provider/tasks.md#task-4.1
	 */
	#[AuthorizedAdminSetting(FilinqAdmin::class)]
	public function index(): JSONResponse {
		$refusal = $this->refusal();
		if ($refusal !== null) {
			return $refusal;
		}

		return new JSONResponse($this->config->toArray());
	}//end index()

	/**
	 * Update the settings; returns them as stored.
	 *
	 * @return JSONResponse 200 with the settings, 400 on invalid input, 401 or 403.
	 *
	 * @spec openspec/changes/llm-entity-detection-provider/tasks.md#task-4.1
	 */
	#[AuthorizedAdminSetting(FilinqAdmin::class)]
	public function update(): JSONResponse {
		$refusal = $this->refusal();
		if ($refusal !== null) {
			return $refusal;
		}

		try {
			$this->config->update(data: $this->request->getParams());
		} catch (InvalidArgumentException $e) {
			return new JSONResponse(['error' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
		}

		return new JSONResponse($this->config->toArray());
	}//end update()

	/**
	 * Test whether the LLM provider could be called now.
	 *
	 * @return JSONResponse 200 with `available` and `reason`, 401 or 403.
	 *
	 * @spec openspec/changes/llm-entity-detection-provider/tasks.md#task-4.1
	 */
	#[AuthorizedAdminSetting(FilinqAdmin::class)]
	public function test(): JSONResponse {
		$refusal = $this->refusal();
		if ($refusal !== null) {
			return $refusal;
		}

		return new JSONResponse($this->llmProvider->testConnection());
	}//end test()

	/**
	 * The refusal for a caller who is not a signed-in admin, or null.
	 *
	 * @return JSONResponse|null 401, 403, or null when allowed.
	 */
	private function refusal(): ?JSONResponse {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return new JSONResponse(['error' => 'Not authenticated'], Http::STATUS_UNAUTHORIZED);
		}

		if ($this->groupManager->isAdmin($user->getUID()) !== true) {
			return new JSONResponse(['error' => 'Only an administrator may change detection settings.'], Http::STATUS_FORBIDDEN);
		}

		return null;
	}//end refusal()
}//end class
