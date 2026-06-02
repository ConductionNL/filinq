<?php

/**
 * Signing Controller
 *
 * REST API controller for digital document signing operations.
 *
 * @category  Controller
 * @package   OCA\DocuDesk\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Controller;

use Exception;
use OCA\DocuDesk\Exception\RegisterNotConfiguredException;
use OCA\DocuDesk\Service\SigningAuditService;
use OCA\DocuDesk\Service\SigningService;
use OCA\DocuDesk\Service\SigningVerificationService;
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
 * @package  OCA\DocuDesk\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SigningController extends Controller
{
    /**
     * Constructor
     *
     * @param string                     $appName             App name
     * @param IRequest                   $request             Request object
     * @param SigningService             $signingService      Signing service
     * @param SigningAuditService        $auditService        Audit service
     * @param SigningVerificationService $verificationService Verification service
     * @param IUserSession               $userSession         User session
     * @param LoggerInterface            $logger              Logger
     * @param IL10N                      $l10n                Localization
     * @param IGroupManager              $groupManager        Group manager for admin checks
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
        private readonly IGroupManager $groupManager
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
    public function createRequest(): JSONResponse
    {
        try {
            $user = $this->userSession->getUser();
            if ($user === null) {
                return new JSONResponse(
                    data: ['error' => $this->l10n->t('Not authenticated')],
                    statusCode: Http::STATUS_UNAUTHORIZED
                );
            }

            $data   = $this->request->getParams();
            $result = $this->signingService->createRequest(data: $data);
            return new JSONResponse($result, Http::STATUS_CREATED);
        } catch (Exception $e) {
            return $this->errorResponse(message: 'Failed to create signing request: ', exception: $e);
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
    public function listRequests(): JSONResponse
    {
        try {
            $user = $this->userSession->getUser();
            if ($user === null) {
                return new JSONResponse(
                    data: ['error' => $this->l10n->t('Not authenticated')],
                    statusCode: Http::STATUS_UNAUTHORIZED
                );
            }

            $result = $this->signingService->listRequests();
            return new JSONResponse($result);
        } catch (RegisterNotConfiguredException $e) {
            // Configuration missing is a setup state, not a failure —
            // emit an empty list with a notConfigured flag so the UI can
            // render a calm "register not configured yet" empty state.
            $this->logger->info(
                'Signing requests list called but register/schema is not configured: '.$e->getMessage()
            );
            return new JSONResponse(
                data: [
                    'results'       => [],
                    'total'         => 0,
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
    public function showRequest(string $id): JSONResponse
    {
        try {
            $user = $this->userSession->getUser();
            if ($user === null) {
                return new JSONResponse(
                    data: ['error' => $this->l10n->t('Not authenticated')],
                    statusCode: Http::STATUS_UNAUTHORIZED
                );
            }

            $result = $this->signingService->getRequest(requestId: $id);
            return new JSONResponse($result);
        } catch (Exception $e) {
            return $this->errorResponse(message: 'Failed to get signing request: ', exception: $e);
        }

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
    public function cancelRequest(string $id): JSONResponse
    {
        try {
            $user = $this->userSession->getUser();
            if ($user === null) {
                return new JSONResponse(
                    data: ['error' => $this->l10n->t('Not authenticated')],
                    statusCode: Http::STATUS_UNAUTHORIZED
                );
            }

            $result = $this->signingService->cancelRequest(requestId: $id);
            return new JSONResponse($result);
        } catch (Exception $e) {
            return $this->errorResponse(message: 'Failed to cancel signing request: ', exception: $e);
        }

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
    public function sign(string $id): JSONResponse
    {
        try {
            $user = $this->userSession->getUser();
            if ($user === null) {
                return new JSONResponse(
                    data: ['error' => $this->l10n->t('Not authenticated')],
                    statusCode: Http::STATUS_UNAUTHORIZED
                );
            }

            $signerId = $this->request->getParam('signerId', '');
            $result   = $this->signingService->sign(requestId: $id, signerId: $signerId);
            return new JSONResponse($result);
        } catch (Exception $e) {
            return $this->errorResponse(message: 'Failed to sign document: ', exception: $e);
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
    public function decline(string $id): JSONResponse
    {
        try {
            $user = $this->userSession->getUser();
            if ($user === null) {
                return new JSONResponse(
                    data: ['error' => $this->l10n->t('Not authenticated')],
                    statusCode: Http::STATUS_UNAUTHORIZED
                );
            }

            $signerId = $this->request->getParam('signerId', '');
            $reason   = $this->request->getParam('reason', '');
            $result   = $this->signingService->decline(requestId: $id, signerId: $signerId, reason: $reason);
            return new JSONResponse($result);
        } catch (Exception $e) {
            return $this->errorResponse(message: 'Failed to decline signing request: ', exception: $e);
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
    public function bulkSign(): JSONResponse
    {
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
            return $this->errorResponse(message: 'Failed to bulk sign: ', exception: $e);
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
    public function verify(int $fileId): JSONResponse
    {
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
            return $this->errorResponse(message: 'Failed to verify document: ', exception: $e);
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
    public function getAudit(string $id): JSONResponse
    {
        try {
            $user = $this->userSession->getUser();
            if ($user === null) {
                return new JSONResponse(
                    data: ['error' => $this->l10n->t('Not authenticated')],
                    statusCode: Http::STATUS_UNAUTHORIZED
                );
            }

            // Security (M2): only the initiator, a listed signer, or an admin
            // may read the audit trail for a signing request — it contains IP
            // addresses and user identifiers that must not be exposed to
            // unrelated parties.
            if ($this->groupManager->isAdmin($user->getUID()) === false) {
                $request = $this->signingService->getRequest(requestId: $id);
                $uid     = $user->getUID();

                $isInitiator    = ($request['initiatorUserId'] ?? '') === $uid;
                $isSignerInList = in_array($uid, (array) ($request['signerIds'] ?? []), true);

                if ($isInitiator === false && $isSignerInList === false) {
                    return new JSONResponse(
                        data: ['error' => $this->l10n->t('Access denied')],
                        statusCode: Http::STATUS_FORBIDDEN
                    );
                }
            }

            $result = $this->auditService->getAuditTrail(signingRequestId: $id);
            return new JSONResponse($result);
        } catch (Exception $e) {
            return $this->errorResponse(message: 'Failed to get audit trail: ', exception: $e);
        }//end try

    }//end getAudit()

    /**
     * Build an error JSON response with logging
     *
     * @param string    $message   The log message prefix
     * @param Exception $exception The exception
     *
     * @return JSONResponse The error response
     */
    private function errorResponse(string $message, Exception $exception): JSONResponse
    {
        $this->logger->error($message.$exception->getMessage(), ['exception' => $exception]);

        return new JSONResponse(
            ['error' => $this->l10n->t($message.'%s', [$exception->getMessage()])],
            Http::STATUS_INTERNAL_SERVER_ERROR
        );

    }//end errorResponse()
}//end class
