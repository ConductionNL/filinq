<?php

/**
 * Signing Controller
 *
 * REST API controller for digital document signing operations.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use Exception;
use OCA\Filinq\Exception\RegisterNotConfiguredException;
use OCA\Filinq\Service\SigningAuditService;
use OCA\Filinq\Service\SigningService;
use OCA\Filinq\Service\SigningVerificationService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * Controller for signing-specific endpoints
 *
 * @category Controller
 * @package  OCA\Filinq\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/document-signing/spec.md
 */
class SigningController extends Controller {
	/**
	 * Constructor
	 *
	 * @param string $appName App name
	 * @param IRequest $request Request object
	 * @param SigningService $signingService Signing service
	 * @param SigningAuditService $auditService Audit service
	 * @param SigningVerificationService $verificationService Verification service
	 * @param IUserSession $userSession User session
	 * @param LoggerInterface $logger Logger
	 * @param IL10N $l10n Localization
	 * @param IGroupManager $groupManager Group manager for admin checks
	 *
	 * @return void
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly SigningService $signingService,
		private readonly SigningAuditService $auditService,
		private readonly SigningVerificationService $verificationService,
		private readonly IUserSession $userSession,
		private readonly LoggerInterface $logger,
		private readonly IL10N $l10n,
		private readonly IGroupManager $groupManager,
	) {
		parent::__construct(appName: $appName, request: $request);

	}//end __construct()

	/**
	 * Create a new signing request
	 *
	 * @return JSONResponse The created signing request
	 *
	 * @NoAdminRequired
	 *
	 * @spec openspec/changes/digital-signing-integration/tasks.md#6-1
	 */
	public function createRequest(): JSONResponse {
		try {
			$user = $this->userSession->getUser();
			if ($user === null) {
				return new JSONResponse(
					data: ['error' => $this->l10n->t('Not authenticated')],
					statusCode: Http::STATUS_UNAUTHORIZED
				);
			}

			$data = $this->request->getParams();
			$result = $this->signingService->createRequest(data: $data);
			return new JSONResponse($result, Http::STATUS_CREATED);
		} catch (Exception $e) {
			return $this->errorResponse(message: 'Failed to create signing request', exception: $e);
		}

	}//end createRequest()

	/**
	 * List signing requests
	 *
	 * @return JSONResponse List of signing requests
	 *
	 * @NoAdminRequired
	 *
	 * @spec openspec/changes/digital-signing-integration/tasks.md#6-1
	 */
	public function listRequests(): JSONResponse {
		try {
			$user = $this->userSession->getUser();
			if ($user === null) {
				return new JSONResponse(
					data: ['error' => $this->l10n->t('Not authenticated')],
					statusCode: Http::STATUS_UNAUTHORIZED
				);
			}

			// WF2 security fix: pass the caller identity so the service scopes
			// results to requests the caller initiated or is a signer on. An
			// admin lists UNSCOPED, which the service spells as callerUserId=''
			// — the single explicit bypass (see SigningService::listRequests()).
			$uid = $user->getUID();
			$isAdmin = $this->groupManager->isAdmin($uid);
			$scope = $uid;
			if ($isAdmin === true) {
				$scope = '';
			}

			$result = $this->signingService->listRequests(callerUserId: $scope);
			return new JSONResponse($result);
		} catch (RegisterNotConfiguredException $e) {
			// Configuration missing is a setup state, not a failure —
			// emit an empty list with a notConfigured flag so the UI can
			// render a calm "register not configured yet" empty state.
			$this->logger->info(
				'Signing requests list called but register/schema is not configured: ' . $e->getMessage()
			);
			return new JSONResponse(
				data: [
					'results' => [],
					'total' => 0,
					'notConfigured' => true,
				]
			);
		}//end try

	}//end listRequests()

	/**
	 * Get a specific signing request
	 *
	 * @param string $id The signing request ID
	 *
	 * @return JSONResponse The signing request details
	 *
	 * @NoAdminRequired
	 *
	 * @spec openspec/changes/digital-signing-integration/tasks.md#6-1
	 */
	public function showRequest(string $id): JSONResponse {
		try {
			$user = $this->userSession->getUser();
			if ($user === null) {
				return new JSONResponse(
					data: ['error' => $this->l10n->t('Not authenticated')],
					statusCode: Http::STATUS_UNAUTHORIZED
				);
			}

			// WF2 + Wilco #6 fix (filinq#100, 2026-06-06): pass the caller
			// identity so the service enforces that only the initiator or a
			// signer can read a request — and returns null on BOTH not-found
			// and access-denied so we emit a single 404 (never a 404-vs-403
			// split, which would be an existence-probing oracle). An admin
			// reads UNSCOPED, which the service spells as callerUserId=''.
			$uid = $user->getUID();
			$isAdmin = $this->groupManager->isAdmin($uid);
			$scope = $uid;
			if ($isAdmin === true) {
				$scope = '';
			}

			$result = $this->signingService->getRequest(requestId: $id, callerUserId: $scope);
			if ($result === null) {
				return new JSONResponse(
					data: ['error' => $this->l10n->t('Signing request not found')],
					statusCode: Http::STATUS_NOT_FOUND
				);
			}

			return new JSONResponse($result);
		} catch (Exception $e) {
			return $this->errorResponse(message: 'Failed to get signing request', exception: $e);
		}//end try

	}//end showRequest()

	/**
	 * Cancel a signing request
	 *
	 * @param string $id The signing request ID
	 *
	 * @return JSONResponse The cancelled request
	 *
	 * @NoAdminRequired
	 *
	 * @spec openspec/changes/digital-signing-integration/tasks.md#6-1
	 */
	public function cancelRequest(string $id): JSONResponse {
		try {
			$user = $this->userSession->getUser();
			if ($user === null) {
				return new JSONResponse(
					data: ['error' => $this->l10n->t('Not authenticated')],
					statusCode: Http::STATUS_UNAUTHORIZED
				);
			}

			// WF1 fix (filinq#100): only the initiator or an admin may
			// cancel a signing request. A non-initiator, non-admin caller is
			// rejected with 403 BEFORE cancelRequest() is ever called.
			//
			// getRequest() is invoked WITHOUT caller scoping (callerUserId='')
			// so the record itself comes back and the controller can apply the
			// tighter initiator gate locally — a signer (in-scope for READ) is
			// still not allowed to perform the destructive cancel. A genuinely
			// not-found request throws RuntimeException('Signing request not
			// found'), which we catch here and map to a single 404 (no UUID is
			// echoed, so existence is not leaked).
			$uid = $user->getUID();
			$isAdmin = $this->groupManager->isAdmin($uid);
			if ($isAdmin === false) {
				try {
					$request = $this->signingService->getRequest(requestId: $id);
				} catch (Exception $e) {
					return new JSONResponse(
						data: ['error' => $this->l10n->t('Signing request not found')],
						statusCode: Http::STATUS_NOT_FOUND
					);
				}

				if ($request === null) {
					return new JSONResponse(
						data: ['error' => $this->l10n->t('Signing request not found')],
						statusCode: Http::STATUS_NOT_FOUND
					);
				}

				// The destructive transition needs the tighter initiator gate
				// (signers are in scope for READ only).
				if (($request['initiatorUserId'] ?? '') !== $uid) {
					return new JSONResponse(
						data: ['error' => $this->l10n->t('You are not allowed to cancel this signing request')],
						statusCode: Http::STATUS_FORBIDDEN
					);
				}
			}//end if

			$result = $this->signingService->cancelRequest(requestId: $id);
			if ($result === null) {
				// Admin path or post-recheck null — race / vanished
				// between fetch and cancel. Same generic 404.
				return new JSONResponse(
					data: ['error' => $this->l10n->t('Signing request not found')],
					statusCode: Http::STATUS_NOT_FOUND
				);
			}

			return new JSONResponse($result);
		} catch (Exception $e) {
			return $this->errorResponse(message: 'Failed to cancel signing request', exception: $e);
		}//end try

	}//end cancelRequest()

	/**
	 * Sign a document
	 *
	 * @param string $id The signing request ID
	 *
	 * @return JSONResponse The updated signer record
	 *
	 * @NoAdminRequired
	 *
	 * @spec openspec/changes/digital-signing-integration/tasks.md#6-1
	 */
	public function sign(string $id): JSONResponse {
		try {
			$user = $this->userSession->getUser();
			if ($user === null) {
				return new JSONResponse(
					data: ['error' => $this->l10n->t('Not authenticated')],
					statusCode: Http::STATUS_UNAUTHORIZED
				);
			}

			$signerId = $this->request->getParam('signerId', '');
			$result = $this->signingService->sign(requestId: $id, signerId: $signerId);
			return new JSONResponse($result);
		} catch (Exception $e) {
			return $this->errorResponse(message: 'Failed to sign document', exception: $e);
		}

	}//end sign()

	/**
	 * Decline a signing request
	 *
	 * @param string $id The signing request ID
	 *
	 * @return JSONResponse The updated signer record
	 *
	 * @NoAdminRequired
	 *
	 * @spec openspec/changes/digital-signing-integration/tasks.md#6-1
	 */
	public function decline(string $id): JSONResponse {
		try {
			$user = $this->userSession->getUser();
			if ($user === null) {
				return new JSONResponse(
					data: ['error' => $this->l10n->t('Not authenticated')],
					statusCode: Http::STATUS_UNAUTHORIZED
				);
			}

			$signerId = $this->request->getParam('signerId', '');
			$reason = $this->request->getParam('reason', '');
			$result = $this->signingService->decline(requestId: $id, signerId: $signerId, reason: $reason);
			return new JSONResponse($result);
		} catch (Exception $e) {
			return $this->errorResponse(message: 'Failed to decline signing request', exception: $e);
		}

	}//end decline()

	/**
	 * Bulk sign multiple signing requests
	 *
	 * @return JSONResponse Results for each request
	 *
	 * @NoAdminRequired
	 *
	 * @spec openspec/changes/digital-signing-integration/tasks.md#6-1
	 */
	public function bulkSign(): JSONResponse {
		try {
			$user = $this->userSession->getUser();
			if ($user === null) {
				return new JSONResponse(
					data: ['error' => $this->l10n->t('Not authenticated')],
					statusCode: Http::STATUS_UNAUTHORIZED
				);
			}

			$requestIds = $this->request->getParam('requestIds', []);
			if (is_array($requestIds) === false) {
				$requestIds = [];
			}

			$results = $this->signingService->bulkSign(requestIds: $requestIds);
			return new JSONResponse($results);
		} catch (Exception $e) {
			return $this->errorResponse(message: 'Failed to bulk sign', exception: $e);
		}

	}//end bulkSign()

	/**
	 * Verify signatures in a document
	 *
	 * @param int $fileId The Nextcloud file ID
	 *
	 * @return JSONResponse The verification results
	 *
	 * @NoAdminRequired
	 *
	 * @spec openspec/changes/digital-signing-integration/tasks.md#6-1
	 */
	public function verify(int $fileId): JSONResponse {
		try {
			$user = $this->userSession->getUser();
			if ($user === null) {
				return new JSONResponse(
					['error' => $this->l10n->t('Not authenticated')],
					Http::STATUS_UNAUTHORIZED
				);
			}

			$result = $this->verificationService->verifyDocument(fileId: $fileId, userId: $user->getUID());
			return new JSONResponse($result);
		} catch (Exception $e) {
			return $this->errorResponse(message: 'Failed to verify document', exception: $e);
		}

	}//end verify()

	/**
	 * Get the audit trail for a signing request
	 *
	 * @param string $id The signing request ID
	 *
	 * @return JSONResponse The audit trail entries
	 *
	 * @NoAdminRequired
	 *
	 * @spec openspec/changes/digital-signing-integration/tasks.md#6-1
	 */
	public function getAudit(string $id): JSONResponse {
		try {
			$user = $this->userSession->getUser();
			if ($user === null) {
				return new JSONResponse(
					data: ['error' => $this->l10n->t('Not authenticated')],
					statusCode: Http::STATUS_UNAUTHORIZED
				);
			}

			// Security (M2 + Wilco #6 / filinq#100): only the initiator,
			// a listed signer, or an admin may read the audit trail —
			// it contains IP addresses + user identifiers that must not
			// leak to unrelated parties. getRequest() now returns null on
			// BOTH not-found and access-denied so we emit a single 404
			// (never split into 404-vs-403).
			$uid = $user->getUID();
			$isAdmin = $this->groupManager->isAdmin($uid);
			if ($isAdmin === false) {
				// Non-admin branch only, so the read is always scoped to $uid.
				$request = $this->signingService->getRequest(
					requestId: $id,
					callerUserId: $uid
				);
				if ($request === null) {
					return new JSONResponse(
						data: ['error' => $this->l10n->t('Signing request not found')],
						statusCode: Http::STATUS_NOT_FOUND
					);
				}
			}

			$result = $this->auditService->getAuditTrail(signingRequestId: $id);
			return new JSONResponse($result);
		} catch (Exception $e) {
			return $this->errorResponse(message: 'Failed to get audit trail', exception: $e);
		}//end try

	}//end getAudit()

	/**
	 * Build an error JSON response with logging
	 *
	 * @param string $message The log message prefix
	 * @param Exception $exception The exception
	 *
	 * @return JSONResponse The error response
	 */
	private function errorResponse(string $message, Exception $exception): JSONResponse {
		// Wilco #6 fix (filinq#100, 2026-06-06): do NOT include the
		// exception message in the response body. Previously, a not-found
		// exception ("Signing request not found: <UUID>") and an access-
		// denied exception ("Access denied: signing request belongs to
		// another user") both surfaced verbatim in the 500 body — distinct
		// text confirmed request-ID existence even when the status code
		// didn't. The body now contains only a generic translated message,
		// identical regardless of which exception fired. Operators get
		// the full detail via the logger call.
		$this->logger->error($message . ': ' . $exception->getMessage(), ['exception' => $exception]);

		// Honour an HTTP status carried on the exception code (e.g. 400 for invalid
		// input) so client errors are not masked as a generic 500.
		$statusCode = Http::STATUS_INTERNAL_SERVER_ERROR;
		if ($exception->getCode() >= 400 && $exception->getCode() < 600) {
			$statusCode = $exception->getCode();
		}

		// Wilco #6 fix (filinq#100): the response body carries ONLY the
		// generic translated message — never the exception text — so it can
		// no longer act as an existence-probing oracle. The status code is
		// still honoured from the exception (e.g. 400 for invalid input) so
		// genuine client errors are not masked as a generic 500.
		return new JSONResponse(
			['error' => $this->l10n->t($message)],
			$statusCode
		);

	}//end errorResponse()
}//end class
