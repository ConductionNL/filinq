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
use OCA\DocuDesk\Exception\PolicyRejectedException;
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
     * Build the 403 response for a policy-prohibited consent request
     *
     * A `PolicyRejectedException` is NOT an internal failure: it is the
     * deliberate, client-visible outcome mandated by the `consent-management`
     * capability ("Requirement: Prohibition match MUST throw
     * `PolicyRejectedException` ... No publicationConsent record MUST be
     * created or updated"). Routing it through {@see errorResponse()} mapped
     * its `code = 0` onto HTTP 500 and discarded the rule identity the
     * exception exists to carry, so every prohibited create looked like a
     * server crash.
     *
     * The rule UUID and name ARE returned to the caller. This is a deliberate,
     * bounded carve-out from the oracle-free rule on {@see errorResponse()}:
     * the identical disclosure is already mandated on the anonymise gate's 422
     * body by `anonymisation-prohibition-gate` ("The `ruleName` MUST be the
     * prohibition rule's `primaryName`, included to help the operator
     * understand WHY the entity is required to be anonymised"). The caller
     * supplied the matching entity text itself, so the response reveals only
     * which rule answered — not the existence of any record it does not own.
     *
     * @param PolicyRejectedException $exception The typed rejection.
     *
     * @return JSONResponse The 403 response carrying the rule identity
     *
     * @spec openspec/specs/consent-management/spec.md
     */
    private function policyRejectedResponse(PolicyRejectedException $exception): JSONResponse
    {
        $this->logger->info(
            'Consent creation rejected by a publication-prohibition rule',
            [
                'ruleUuid' => $exception->getRuleUuid(),
                'ruleName' => $exception->getRuleName(),
            ]
        );

        return new JSONResponse(
            [
                'error'     => $this->l10n->t('Publication prohibited by policy rule'),
                'matchKind' => 'prohibition',
                'ruleUuid'  => $exception->getRuleUuid(),
                'ruleName'  => $exception->getRuleName(),
            ],
            Http::STATUS_FORBIDDEN
        );

    }//end policyRejectedResponse()

    /**
     * Create a new consent request for a detected entity
     *
     * Idempotent on `(documentId, entityKey, scope: "document")`. The service
     * reports which branch it took through `wasUpdated`; the status line MUST
     * agree with it. HTTP 201 is reserved for "a new resource was created"
     * (RFC 9110 §15.3.2), so an idempotent re-submit — which creates nothing —
     * answers 200. Before this fix the controller returned a hardcoded 201
     * while the very same body said `wasUpdated: true`.
     *
     * @return JSONResponse JSON response with the created (201) or updated (200) consent record
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

            $statusCode = Http::STATUS_CREATED;
            if (($result['wasUpdated'] ?? false) === true) {
                $statusCode = Http::STATUS_OK;
            }

            return new JSONResponse($result, $statusCode);
        } catch (PolicyRejectedException $e) {
            return $this->policyRejectedResponse(exception: $e);
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
