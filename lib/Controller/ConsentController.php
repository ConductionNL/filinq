<?php
/**
 * Consent Controller
 *
 * Controller for GDPR publication consent operations.
 * Provides endpoints for managing consent records.
 * Delegates CRUD logic to ConsentCrudService.
 *
 * @category  Controller
 * @package   OCA\DocuDesk\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/specs/consent-management/spec.md
 * @spec openspec/specs/consent-management/spec.md
 * @spec openspec/specs/consent-management/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Controller;

use Exception;
use OCA\DocuDesk\Service\ConsentCrudService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * Controller for consent-specific endpoints
 *
 * @category Controller
 * @package  OCA\DocuDesk\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/specs/consent-endpoint-hardening/spec.md
 */
class ConsentController extends Controller
{
    /**
     * Constructor for ConsentController
     *
     * @param string             $appName      The application name
     * @param IRequest           $request      The request object
     * @param LoggerInterface    $logger       Logger for error reporting
     * @param ConsentCrudService $crudService  CRUD service for consent records
     * @param IL10N              $l10n         The localization service
     * @param IUserSession       $userSession  User session for authentication
     * @param IGroupManager      $groupManager Group manager for admin checks
     *
     * @return void
     */
    public function __construct(
        string $appName,
        IRequest $request,
        private readonly LoggerInterface $logger,
        private readonly ConsentCrudService $crudService,
        private readonly IL10N $l10n,
        private readonly IUserSession $userSession,
        private readonly IGroupManager $groupManager
    ) {
        parent::__construct(appName: $appName, request: $request);

    }//end __construct()

    /**
     * Check whether the current user may access a consent record
     *
     * Enforces per-object ownership for non-admin users (security finding
     * #283). OpenRegister reads are default-open, so a controller-level
     * ownership guard is required to keep consent records isolated between
     * users. Administrators retain full access.
     *
     * @param array<string, mixed> $consent The consent record to check
     *
     * @return bool True if the current user may access the record
     */
    private function canAccessConsent(array $consent): bool
    {
        $user = $this->userSession->getUser();
        if ($user === null) {
            return false;
        }

        if ($this->groupManager->isAdmin($user->getUID()) === true) {
            return true;
        }

        $owner = $consent['@self']['owner'] ?? ($consent['owner'] ?? null);
        if (is_array($owner) === true) {
            $owner = $owner['id'] ?? ($owner['uid'] ?? null);
        }

        return $owner !== null && (string) $owner === $user->getUID();

    }//end canAccessConsent()

    /**
     * Build an error JSON response with logging
     *
     * Oracle-free (signing-trust-rebuild REQ-DDSTR-009, closing the #283
     * residual): the response body carries ONLY a generic translated message —
     * never the exception text, a record identifier, or any other detail that
     * differs by failure class. Full detail goes to the logger only. Mirrors
     * the fix already shipped on `SigningController::errorResponse()`
     * (docudesk#100 / Wilco #6). A legitimate HTTP status carried on the
     * exception code (e.g. 400 for invalid input) is still honoured so client
     * errors are not masked as a generic 500.
     *
     * @param string    $message   The log message prefix
     * @param Exception $exception The exception
     *
     * @return JSONResponse The error response
     *
     * @spec openspec/specs/consent-endpoint-hardening/spec.md
     */
    private function errorResponse(string $message, Exception $exception): JSONResponse
    {
        $this->logger->error($message.$exception->getMessage(), ['exception' => $exception]);

        $statusCode = Http::STATUS_INTERNAL_SERVER_ERROR;
        if ($exception->getCode() >= 400 && $exception->getCode() < 600) {
            $statusCode = $exception->getCode();
        }

        return new JSONResponse(
            ['error' => $this->l10n->t($message)],
            $statusCode
        );

    }//end errorResponse()

    /**
     * Build a not-configured error response
     *
     * @return JSONResponse The 400 error response
     */
    private function notConfiguredResponse(): JSONResponse
    {
        return new JSONResponse(
            ['error' => $this->l10n->t('PublicationConsent register/schema not configured')],
            400
        );

    }//end notConfiguredResponse()

    /**
     * List consent records
     *
     * @return JSONResponse JSON response with list of consent records
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @spec openspec/specs/consent-management/spec.md
     */
    public function index(): JSONResponse
    {
        try {
            if ($this->userSession->getUser() === null) {
                return new JSONResponse(
                    data: ['error' => $this->l10n->t('Not authenticated')],
                    statusCode: Http::STATUS_UNAUTHORIZED
                );
            }

            $config = $this->crudService->getConsentConfig();
            if ($config === null) {
                return $this->notConfiguredResponse();
            }

            $user = $this->userSession->getUser();
            // $user cannot be null here (checked above), but appease Psalm.
            $uid = '';
            if ($user !== null) {
                $uid = $user->getUID();
            }

            $isAdmin = ($user !== null && $this->groupManager->isAdmin($uid) === true);

            $filterUid = $uid;
            if ($isAdmin === true) {
                $filterUid = null;
            }

            return new JSONResponse(
                $this->crudService->listConsents(
                    $config['register'],
                    $config['schema'],
                    $filterUid
                )
            );
        } catch (Exception $e) {
            return $this->errorResponse(message: 'Failed to list consents', exception: $e);
        }//end try

    }//end index()

    /**
     * Create a new consent request for a detected entity
     *
     * @return JSONResponse JSON response with the created consent record
     *
     * @NoAdminRequired
     *
     * @spec openspec/specs/consent-management/spec.md
     */
    public function create(): JSONResponse
    {
        try {
            if ($this->userSession->getUser() === null) {
                return new JSONResponse(
                    data: ['error' => $this->l10n->t('Not authenticated')],
                    statusCode: Http::STATUS_UNAUTHORIZED
                );
            }

            $data     = $this->request->getParams();
            $required = ['documentId', 'entityType', 'entityText'];
            foreach ($required as $field) {
                if (empty($data[$field]) === true) {
                    return new JSONResponse(
                        ['error' => $this->l10n->t('Missing required field: %s', [$field])],
                        400
                    );
                }
            }

            $config = $this->crudService->getConsentConfig();
            if ($config === null) {
                return $this->notConfiguredResponse();
            }

            $result = $this->crudService->createFromRequest(
                $data,
                $config['register'],
                $config['schema']
            );

            return new JSONResponse($result, 201);
        } catch (Exception $e) {
            return $this->errorResponse(message: 'Failed to create consent', exception: $e);
        }//end try

    }//end create()

    /**
     * Get a specific consent record
     *
     * @param string $id The consent record UUID
     *
     * @return JSONResponse JSON response with consent record
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @spec openspec/specs/consent-management/spec.md
     */
    public function show(string $id): JSONResponse
    {
        try {
            if ($this->userSession->getUser() === null) {
                return new JSONResponse(
                    data: ['error' => $this->l10n->t('Not authenticated')],
                    statusCode: Http::STATUS_UNAUTHORIZED
                );
            }

            $config = $this->crudService->getConsentConfig();
            if ($config === null) {
                return $this->notConfiguredResponse();
            }

            $consent = $this->crudService->getConsent($id, $config['register'], $config['schema']);
            if ($consent === null) {
                return new JSONResponse(
                    ['error' => $this->l10n->t('Consent record not found')],
                    404
                );
            }

            // Per-object ownership guard (security finding #283): return 404
            // (not 403) so non-owners cannot probe for record existence.
            if ($this->canAccessConsent(consent: $consent) === false) {
                return new JSONResponse(
                    ['error' => $this->l10n->t('Consent record not found')],
                    404
                );
            }

            return new JSONResponse($consent);
        } catch (Exception $e) {
            return $this->errorResponse(message: 'Failed to get consent', exception: $e);
        }//end try

    }//end show()

    /**
     * Update a consent record
     *
     * @param string $id The consent record UUID
     *
     * @return JSONResponse JSON response with updated consent record
     *
     * @NoAdminRequired
     *
     * @spec openspec/specs/consent-management/spec.md
     */
    public function update(string $id): JSONResponse
    {
        try {
            if ($this->userSession->getUser() === null) {
                return new JSONResponse(
                    data: ['error' => $this->l10n->t('Not authenticated')],
                    statusCode: Http::STATUS_UNAUTHORIZED
                );
            }

            $config = $this->crudService->getConsentConfig();
            if ($config === null) {
                return $this->notConfiguredResponse();
            }

            // Per-object ownership guard (security finding #283): a non-owner
            // must not be able to overwrite another user's consent record.
            $existing = $this->crudService->getConsent($id, $config['register'], $config['schema']);
            if ($existing === null || $this->canAccessConsent(consent: $existing) === false) {
                return new JSONResponse(
                    ['error' => $this->l10n->t('Consent record not found')],
                    404
                );
            }

            $result = $this->crudService->updateConsentStatus(
                $id,
                $config['register'],
                $config['schema'],
                $this->request->getParams(),
                // Plumb the acting user so ConsentService can re-check
                // the standing-consent admin group when the existing
                // record is scope=entity (revoke/expire RBAC).
                $this->userSession->getUser()
            );

            return new JSONResponse($result);
        } catch (\OCP\AppFramework\OCS\OCSForbiddenException $e) {
            return new JSONResponse(
                ['error' => $this->l10n->t('You are not allowed to revoke or modify standing consents.')],
                Http::STATUS_FORBIDDEN
            );
        } catch (Exception $e) {
            return $this->errorResponse(message: 'Failed to update consent', exception: $e);
        }//end try

    }//end update()

    /**
     * Get all consent records for a specific document
     *
     * @param string $documentId The document UUID
     *
     * @return JSONResponse JSON response with consent records for the document
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @spec openspec/specs/consent-management/spec.md
     */
    public function byDocument(string $documentId): JSONResponse
    {
        try {
            if ($this->userSession->getUser() === null) {
                return new JSONResponse(
                    data: ['error' => $this->l10n->t('Not authenticated')],
                    statusCode: Http::STATUS_UNAUTHORIZED
                );
            }

            $config = $this->crudService->getConsentConfig();
            if ($config === null) {
                return $this->notConfiguredResponse();
            }

            $user = $this->userSession->getUser();
            $uid  = '';
            if ($user !== null) {
                $uid = $user->getUID();
            }

            $isAdmin = ($user !== null && $this->groupManager->isAdmin($uid) === true);

            $filterUid = $uid;
            if ($isAdmin === true) {
                $filterUid = null;
            }

            $consents = $this->crudService->getConsentsByDocument(
                $documentId,
                $config['register'],
                $config['schema'],
                $filterUid
            );

            return new JSONResponse($consents);
        } catch (Exception $e) {
            return $this->errorResponse(message: 'Failed to get consents for document', exception: $e);
        }//end try

    }//end byDocument()
}//end class
