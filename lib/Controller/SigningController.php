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
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use Throwable;

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
        private readonly IL10N $l10n
    ) {
        parent::__construct(appName: $appName, request: $request);

    }//end __construct()

    /**
     * Create a new signing request
     *
     * @return JSONResponse The created signing request
     *
     * @NoAdminRequired
     */
    public function createRequest(): JSONResponse
    {
        try {
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
     */
    public function listRequests(): JSONResponse
    {
        try {
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
        } catch (Throwable $e) {
            // Cascade fallback for the OR-sidecar-lag scenario: when
            // the deployed OpenRegister build lacks a method that
            // SigningService calls (e.g. `getObjects`), PHP raises an
            // `Error` which `catch (Exception)` would miss. Returning
            // an empty list keeps the page rendering instead of 500ing
            // while the sidecar catches up. The error is logged at
            // warning level so ops still see drift.
            $this->logger->warning(
                'Signing requests list failed — returning empty list. '
                .'Likely a missing OpenRegister method on the deployed sidecar. '
                .'Underlying: '.$e->getMessage()
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
     */
    public function showRequest(string $id): JSONResponse
    {
        try {
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
     */
    public function cancelRequest(string $id): JSONResponse
    {
        try {
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
     */
    public function sign(string $id): JSONResponse
    {
        try {
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
     */
    public function decline(string $id): JSONResponse
    {
        try {
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
     */
    public function bulkSign(): JSONResponse
    {
        try {
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
     */
    public function getAudit(string $id): JSONResponse
    {
        try {
            $result = $this->auditService->getAuditTrail(signingRequestId: $id);
            return new JSONResponse($result);
        } catch (Exception $e) {
            return $this->errorResponse(message: 'Failed to get audit trail: ', exception: $e);
        }

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
