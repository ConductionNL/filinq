<?php
/**
 * Standing Consent Controller
 *
 * Controller for CRUD on `publicationConsent` records with `scope: "entity"` —
 * the standing-consent policy surface. Split out of `PolicyController`, which
 * retains the `publicationProhibition` (deny-list) surface.
 *
 * @category  Controller
 * @package   OCA\DocuDesk\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/specs/entity-publication-policies/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Controller;

use Exception;
use InvalidArgumentException;
use OCA\DocuDesk\Service\PolicyCrudService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Standing-consent CRUD endpoints.
 *
 * @category Controller
 * @package  OCA\DocuDesk\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class StandingConsentController extends Controller
{
    /**
     * Constructor.
     *
     * @param string            $appName     App name.
     * @param IRequest          $request     Request abstraction.
     * @param LoggerInterface   $logger      Logger.
     * @param PolicyCrudService $crudService CRUD wrapper.
     * @param IL10N             $l10n        Localisation.
     * @param IUserSession      $userSession User session for authentication.
     */
    public function __construct(
        string $appName,
        IRequest $request,
        private readonly LoggerInterface $logger,
        private readonly PolicyCrudService $crudService,
        private readonly IL10N $l10n,
        private readonly IUserSession $userSession
    ) {
        parent::__construct(appName: $appName, request: $request);

    }//end __construct()

    /**
     * List standing consents.
     *
     * @return JSONResponse
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @spec openspec/specs/entity-publication-policies/spec.md
     */
    public function index(): JSONResponse
    {
        if ($this->userSession->getUser() === null) {
            return new JSONResponse(['error' => $this->l10n->t('Not authenticated')], Http::STATUS_UNAUTHORIZED);
        }

        try {
            $this->crudService->requirePolicyPermission(
                surface: PolicyCrudService::SURFACE_STANDING_CONSENT,
                action: 'read'
            );
            return new JSONResponse($this->crudService->listStandingConsents());
        } catch (Exception $e) {
            return $this->error(message: 'Failed to list standing consents: ', exception: $e);
        }

    }//end index()

    /**
     * Show a single standing consent.
     *
     * @param string $id Record UUID.
     *
     * @return JSONResponse
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @spec openspec/specs/entity-publication-policies/spec.md
     */
    public function show(string $id): JSONResponse
    {
        if ($this->userSession->getUser() === null) {
            return new JSONResponse(['error' => $this->l10n->t('Not authenticated')], Http::STATUS_UNAUTHORIZED);
        }

        try {
            $this->crudService->requirePolicyPermission(
                surface: PolicyCrudService::SURFACE_STANDING_CONSENT,
                action: 'read'
            );
            $record = $this->crudService->getStandingConsent(uuid: $id);
            if ($record === null) {
                return new JSONResponse(['error' => $this->l10n->t('Standing consent not found')], 404);
            }

            return new JSONResponse($record);
        } catch (Exception $e) {
            return $this->error(message: 'Failed to load standing consent: ', exception: $e);
        }

    }//end show()

    /**
     * Create a standing consent.
     *
     * @return JSONResponse
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @spec openspec/specs/entity-publication-policies/spec.md
     */
    public function create(): JSONResponse
    {
        if ($this->userSession->getUser() === null) {
            return new JSONResponse(['error' => $this->l10n->t('Not authenticated')], Http::STATUS_UNAUTHORIZED);
        }

        try {
            $this->crudService->requirePolicyPermission(
                surface: PolicyCrudService::SURFACE_STANDING_CONSENT,
                action: 'create'
            );
            $data = $this->request->getParams();
            return new JSONResponse($this->crudService->createStandingConsent(data: $data), 201);
        } catch (RuntimeException $e) {
            return new JSONResponse(['error' => $e->getMessage()], 403);
        } catch (InvalidArgumentException $e) {
            return new JSONResponse(['error' => $e->getMessage()], 400);
        } catch (Exception $e) {
            return $this->error(message: 'Failed to create standing consent: ', exception: $e);
        }

    }//end create()

    /**
     * Update a standing consent.
     *
     * @param string $id Record UUID.
     *
     * @return JSONResponse
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @spec openspec/specs/entity-publication-policies/spec.md
     */
    public function update(string $id): JSONResponse
    {
        if ($this->userSession->getUser() === null) {
            return new JSONResponse(['error' => $this->l10n->t('Not authenticated')], Http::STATUS_UNAUTHORIZED);
        }

        try {
            $this->crudService->requirePolicyPermission(
                surface: PolicyCrudService::SURFACE_STANDING_CONSENT,
                action: 'update'
            );
            $data = $this->request->getParams();
            return new JSONResponse(
                $this->crudService->updateStandingConsent(uuid: $id, data: $data)
            );
        } catch (RuntimeException $e) {
            return new JSONResponse(['error' => $e->getMessage()], 403);
        } catch (InvalidArgumentException $e) {
            return new JSONResponse(['error' => $e->getMessage()], 400);
        } catch (Exception $e) {
            return $this->error(message: 'Failed to update standing consent: ', exception: $e);
        }

    }//end update()

    /**
     * Delete a standing consent.
     *
     * @param string $id Record UUID.
     *
     * @return JSONResponse
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @spec openspec/specs/entity-publication-policies/spec.md
     */
    public function destroy(string $id): JSONResponse
    {
        if ($this->userSession->getUser() === null) {
            return new JSONResponse(['error' => $this->l10n->t('Not authenticated')], Http::STATUS_UNAUTHORIZED);
        }

        try {
            $this->crudService->requirePolicyPermission(
                surface: PolicyCrudService::SURFACE_STANDING_CONSENT,
                action: 'delete'
            );
            $this->crudService->deleteStandingConsent(uuid: $id);
            return new JSONResponse(['deleted' => $id]);
        } catch (RuntimeException $e) {
            return new JSONResponse(['error' => $e->getMessage()], 403);
        } catch (Exception $e) {
            return $this->error(message: 'Failed to delete standing consent: ', exception: $e);
        }

    }//end destroy()

    /**
     * Wrap an exception into a 500 JSON response and log it.
     *
     * @param string    $message   Log prefix and user-facing prefix.
     * @param Exception $exception The exception.
     *
     * @return JSONResponse
     */
    private function error(string $message, Exception $exception): JSONResponse
    {
        $this->logger->error(
            $message.$exception->getMessage(),
            ['exception' => $exception]
        );
        return new JSONResponse(
            ['error' => $this->l10n->t($message.'%s', [$exception->getMessage()])],
            500
        );

    }//end error()
}//end class
