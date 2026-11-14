<?php

/**
 * Case System Delivery Controller
 *
 * The admin bridge panel's endpoint for case-system deliveries.
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
 * @spec openspec/changes/generate-store-in-case-system/specs/case-system-delivery/spec.md#requirement-a-deliverys-outcome-is-visible-and-a-failure-can-be-retried-req-gsc-003
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use OCA\Filinq\Kernel\AppIdentity;
use OCA\Filinq\Service\CaseSystemDeliveryService;
use OCA\Filinq\Settings\FilinqAdmin;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\AuthorizedAdminSetting;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserSession;
use RuntimeException;

/**
 * Puts a failed delivery back in the queue, for admins only.
 *
 * Fail-closed twice: the route attribute admits admins of Filinq's admin
 * section only, and the method also checks that the caller is signed in (401)
 * and an instance admin (403) before reading anything. Filinq never calls the
 * case system here; integriq picks the waiting delivery up again.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/generate-store-in-case-system/specs/case-system-delivery/spec.md#requirement-a-deliverys-outcome-is-visible-and-a-failure-can-be-retried-req-gsc-003
 */
class CaseSystemDeliveryController extends Controller {

	/**
	 * Constructor.
	 *
	 * @param IRequest                  $request      The request.
	 * @param CaseSystemDeliveryService $deliveries   The deliveries.
	 * @param IUserSession              $userSession  The session.
	 * @param IGroupManager             $groupManager The admin check.
	 *
	 * @return void
	 */
	public function __construct(
		IRequest $request,
		private readonly CaseSystemDeliveryService $deliveries,
		private readonly IUserSession $userSession,
		private readonly IGroupManager $groupManager,
	) {
		parent::__construct(appName: AppIdentity::APP_ID, request: $request);

	}//end __construct()

	/**
	 * POST /api/bridge/deliveries/{id}/retry
	 *
	 * @param string $id The delivery id.
	 *
	 * @return JSONResponse 200 with the waiting delivery; 401, 403, 404, or 409 when it did not fail.
	 *
	 * @spec openspec/changes/generate-store-in-case-system/specs/case-system-delivery/spec.md#requirement-a-deliverys-outcome-is-visible-and-a-failure-can-be-retried-req-gsc-003
	 */
	#[AuthorizedAdminSetting(FilinqAdmin::class)]
	public function retry(string $id): JSONResponse {
		$refusal = $this->refusal();
		if ($refusal !== null) {
			return $refusal;
		}

		try {
			return new JSONResponse($this->deliveries->retry(id: $id));
		} catch (RuntimeException $e) {
			$status = Http::STATUS_INTERNAL_SERVER_ERROR;
			if (in_array($e->getCode(), [Http::STATUS_NOT_FOUND, Http::STATUS_CONFLICT], true) === true) {
				$status = $e->getCode();
			}

			return new JSONResponse(['error' => $e->getMessage()], $status);
		}

	}//end retry()

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
			return new JSONResponse(['error' => 'Only an administrator may retry a delivery.'], Http::STATUS_FORBIDDEN);
		}

		return null;

	}//end refusal()
}//end class
