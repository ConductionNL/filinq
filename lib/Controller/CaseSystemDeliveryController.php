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

use InvalidArgumentException;
use OCA\Filinq\Kernel\AppIdentity;
use OCA\Filinq\Kernel\Contracts\BridgeApiInterface;
use OCA\Filinq\Kernel\Contracts\CaseSystemDeliveryServiceInterface;
use OCA\Filinq\Settings\FilinqAdmin;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\AuthorizedAdminSetting;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Lists and declares case-system destinations, lists the waiting and failed
 * deliveries, and puts a failed delivery back in the queue, for admins only.
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
	 * @param IRequest                           $request      The request.
	 * @param CaseSystemDeliveryServiceInterface $deliveries   The deliveries.
	 * @param IUserSession                       $userSession  The session.
	 * @param IGroupManager                      $groupManager The admin check.
	 * @param BridgeApiInterface|null            $bridge       The bridge sources, for the panel's source list.
	 * @param LoggerInterface|null               $logger       Logs a failed source read.
	 *
	 * @return void
	 */
	public function __construct(
		IRequest $request,
		private readonly CaseSystemDeliveryServiceInterface $deliveries,
		private readonly IUserSession $userSession,
		private readonly IGroupManager $groupManager,
		private readonly ?BridgeApiInterface $bridge = null,
		private readonly ?LoggerInterface $logger = null,
	) {
		parent::__construct(appName: AppIdentity::APP_ID, request: $request);

	}//end __construct()

	/**
	 * GET /api/bridge/sources
	 *
	 * @return JSONResponse 200 with `results`, every bridge source with its health; 401, 403 or 500.
	 *
	 * @spec openspec/changes/zgw-document-bridge
	 */
	#[AuthorizedAdminSetting(FilinqAdmin::class)]
	public function sources(): JSONResponse {
		$refusal = $this->refusal();
		if ($refusal !== null) {
			return $refusal;
		}

		try {
			return new JSONResponse(['results' => ($this->bridge?->sources() ?? [])]);
		} catch (Throwable $e) {
			$this->logger?->error('[CaseSystemDeliveryController] reading the bridge sources failed', ['exception' => $e->getMessage()]);

			return new JSONResponse(['error' => 'The bridge request failed.'], Http::STATUS_INTERNAL_SERVER_ERROR);
		}

	}//end sources()

	/**
	 * GET /api/bridge/case-system
	 *
	 * @return JSONResponse 200 with `destinations` and the waiting and failed `deliveries`; 401 or 403.
	 *
	 * @spec openspec/changes/generate-store-in-case-system/specs/case-system-delivery/spec.md#requirement-a-deliverys-outcome-is-visible-and-a-failure-can-be-retried-req-gsc-003
	 */
	#[AuthorizedAdminSetting(FilinqAdmin::class)]
	public function index(): JSONResponse {
		$refusal = $this->refusal();
		if ($refusal !== null) {
			return $refusal;
		}

		return new JSONResponse(
			[
				'destinations' => $this->deliveries->listDestinations(),
				'deliveries' => $this->deliveries->openDeliveries(),
			]
		);

	}//end index()

	/**
	 * POST /api/bridge/destinations
	 *
	 * Creates a destination, or updates the one named by `id`.
	 *
	 * @return JSONResponse 200 with the destination; 400 naming what is wrong, such as a second active destination; 401 or 403.
	 *
	 * @spec openspec/changes/generate-store-in-case-system/specs/case-system-delivery/spec.md#requirement-an-admin-declares-where-generated-documents-go-in-the-case-system-req-gsc-001
	 */
	#[AuthorizedAdminSetting(FilinqAdmin::class)]
	public function saveDestination(): JSONResponse {
		$refusal = $this->refusal();
		if ($refusal !== null) {
			return $refusal;
		}

		try {
			return new JSONResponse($this->deliveries->saveDestination(input: $this->request->getParams()));
		} catch (InvalidArgumentException $e) {
			return new JSONResponse(['error' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
		}

	}//end saveDestination()

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
