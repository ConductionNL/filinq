<?php

/**
 * Bulk Signing Controller
 *
 * Bulk send endpoints: upload a recipient list, read the report, confirm,
 * follow progress, cancel.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md#requirement-bulk-send-validates-first-then-creates-isolated-ordinary-requests-req-ddbsf-002
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use OCA\Filinq\Service\BulkSigning\BulkSigningService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Every endpoint is for a signed-in user and reaches only the caller's own
 * batches, or every batch for an admin. Anything else is a 404, so a batch id
 * cannot be probed.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md#requirement-bulk-send-validates-first-then-creates-isolated-ordinary-requests-req-ddbsf-002
 */
class BulkSigningController extends Controller {

	/**
	 * Constructor.
	 *
	 * @param string             $appName      App name
	 * @param IRequest           $request      The request
	 * @param BulkSigningService $bulkSigning  Bulk send
	 * @param IUserSession       $userSession  The caller
	 * @param IGroupManager      $groupManager Admin check
	 * @param LoggerInterface    $logger       Logger
	 *
	 * @spec openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md#requirement-bulk-send-validates-first-then-creates-isolated-ordinary-requests-req-ddbsf-002
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly BulkSigningService $bulkSigning,
		private readonly IUserSession $userSession,
		private readonly IGroupManager $groupManager,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct(appName: $appName, request: $request);

	}//end __construct()

	/**
	 * Upload a recipient list (multipart `file`) with the document settings; answers the report.
	 *
	 * @return JSONResponse 201 with the batch, 400 when the list or the request is invalid
	 *
	 * @no-admin-idor-exempt Creates a new batch owned by the caller; no existing object is referenced.
	 *
	 * @spec openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md#requirement-bulk-send-validates-first-then-creates-isolated-ordinary-requests-req-ddbsf-002
	 */
	#[NoAdminRequired]
	public function create(): JSONResponse {
		$uid = $this->callerId();
		$upload = $this->request->getUploadedFile('file');
		if (is_array($upload) === false || ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
			return new JSONResponse(data: ['error' => 'Upload a .csv or .xlsx recipient list as "file"'], statusCode: Http::STATUS_BAD_REQUEST);
		}

		$settings = [];
		foreach (['title', 'documentFileId', 'documentName', 'signatureLevel', 'signingMode', 'provider'] as $key) {
			$value = $this->request->getParam($key);
			if ($value !== null && $value !== '') {
				$settings[$key] = (string) $value;
			}
		}

		return $this->answer(
			operation: fn (): array => $this->bulkSigning->createBatch(
				settings: $settings,
				content: (string) file_get_contents((string) $upload['tmp_name']),
				filename: (string) ($upload['name'] ?? ''),
				userId: $uid
			),
			status: Http::STATUS_CREATED
		);

	}//end create()

	/**
	 * List the caller's batches (every batch for an admin).
	 *
	 * @return JSONResponse `{results: [...]}`
	 *
	 * @spec openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md#requirement-bulk-send-validates-first-then-creates-isolated-ordinary-requests-req-ddbsf-002
	 */
	#[NoAdminRequired]
	public function index(): JSONResponse {
		$uid = $this->callerId();
		$isAdmin = $this->groupManager->isAdmin($uid);

		return $this->answer(operation: fn (): array => ['results' => $this->bulkSigning->listFor(userId: $uid, isAdmin: $isAdmin)]);

	}//end index()

	/**
	 * One batch: report and progress.
	 *
	 * @param string $id The batch uuid
	 *
	 * @return JSONResponse The batch, or 404
	 *
	 * @spec openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md#requirement-bulk-send-validates-first-then-creates-isolated-ordinary-requests-req-ddbsf-002
	 */
	#[NoAdminRequired]
	public function show(string $id): JSONResponse {
		$uid = $this->callerId();
		$isAdmin = $this->groupManager->isAdmin($uid);

		return $this->answer(operation: fn (): ?array => $this->bulkSigning->get(id: $id, userId: $uid, isAdmin: $isAdmin));

	}//end show()

	/**
	 * Confirm a ready batch: the requests are created in the background.
	 *
	 * @param string $id The batch uuid
	 *
	 * @return JSONResponse The batch, 404, or 409 when it is not ready
	 *
	 * @spec openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md#requirement-bulk-send-validates-first-then-creates-isolated-ordinary-requests-req-ddbsf-002
	 */
	#[NoAdminRequired]
	public function confirm(string $id): JSONResponse {
		$uid = $this->callerId();
		$isAdmin = $this->groupManager->isAdmin($uid);

		return $this->answer(operation: fn (): ?array => $this->bulkSigning->confirm(id: $id, userId: $uid, isAdmin: $isAdmin));

	}//end confirm()

	/**
	 * Cancel a batch and its member requests that can still be cancelled.
	 *
	 * @param string $id The batch uuid
	 *
	 * @return JSONResponse The batch, or 404
	 *
	 * @spec openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md#requirement-bulk-send-validates-first-then-creates-isolated-ordinary-requests-req-ddbsf-002
	 */
	#[NoAdminRequired]
	public function cancel(string $id): JSONResponse {
		$uid = $this->callerId();
		$isAdmin = $this->groupManager->isAdmin($uid);

		return $this->answer(operation: fn (): ?array => $this->bulkSigning->cancel(id: $id, userId: $uid, isAdmin: $isAdmin));

	}//end cancel()

	/**
	 * The caller's uid ('' only if the framework let an anonymous call through).
	 *
	 * @return string
	 */
	private function callerId(): string {
		return (string) $this->userSession->getUser()?->getUID();

	}//end callerId()

	/**
	 * Run an operation and map its outcome: null is 404; 400 and 409 carry their message.
	 *
	 * @param callable():(array|null) $operation The operation
	 * @param int                     $status    Success status
	 *
	 * @return JSONResponse
	 */
	private function answer(callable $operation, int $status = Http::STATUS_OK): JSONResponse {
		try {
			$result = $operation();
		} catch (Throwable $e) {
			$code = (int) $e->getCode();
			if (in_array($code, [Http::STATUS_BAD_REQUEST, Http::STATUS_CONFLICT], true) === true) {
				return new JSONResponse(data: ['error' => $e->getMessage()], statusCode: $code);
			}

			$this->logger->error('Bulk send failed: ' . $e->getMessage(), ['exception' => $e]);
			return new JSONResponse(data: ['error' => 'Bulk send failed'], statusCode: Http::STATUS_INTERNAL_SERVER_ERROR);
		}

		if ($result === null) {
			return new JSONResponse(data: ['error' => 'Bulk send not found'], statusCode: Http::STATUS_NOT_FOUND);
		}

		return new JSONResponse(data: $result, statusCode: $status);

	}//end answer()
}//end class
