<?php
/**
 * Policy Controller
 *
 * Controller for CRUD on the `publicationProhibition` policy surface
 * (deny-list). The sibling `publicationConsent` / `scope: "entity"` surface
 * (standing consents) lives in {@see StandingConsentController}.
 *
 * @category  Controller
 * @package   OCA\DocuDesk\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
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
use InvalidArgumentException;
use OCA\DocuDesk\Service\PolicyCrudService;
use OCA\OpenRegister\Exception\ArchivalImmutableException;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * Prohibition policy-surface CRUD endpoints.
 *
 * @category Controller
 * @package  OCA\DocuDesk\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class PolicyController extends Controller
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
     * List all prohibitions.
     *
     * @return JSONResponse
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function indexProhibitions(): JSONResponse
    {
        if ($this->userSession->getUser() === null) {
            return new JSONResponse(['error' => $this->l10n->t('Not authenticated')], Http::STATUS_UNAUTHORIZED);
        }

        try {
            $this->crudService->requirePolicyPermission(
                surface: PolicyCrudService::SURFACE_PROHIBITION,
                action: 'read'
            );
            return new JSONResponse($this->crudService->listProhibitions());
        } catch (Exception $e) {
            return $this->error(message: 'Failed to list prohibitions: ', exception: $e);
        }

    }//end indexProhibitions()

    /**
     * Show a single prohibition.
     *
     * @param string $id Record UUID.
     *
     * @return JSONResponse
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function showProhibition(string $id): JSONResponse
    {
        if ($this->userSession->getUser() === null) {
            return new JSONResponse(['error' => $this->l10n->t('Not authenticated')], Http::STATUS_UNAUTHORIZED);
        }

        try {
            $this->crudService->requirePolicyPermission(
                surface: PolicyCrudService::SURFACE_PROHIBITION,
                action: 'read'
            );
            $record = $this->crudService->getProhibition(uuid: $id);
            if ($record === null) {
                return new JSONResponse(['error' => $this->l10n->t('Prohibition not found')], 404);
            }

            return new JSONResponse($record);
        } catch (Exception $e) {
            return $this->error(message: 'Failed to load prohibition: ', exception: $e);
        }

    }//end showProhibition()

    /**
     * Create a prohibition.
     *
     * @return JSONResponse
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function createProhibition(): JSONResponse
    {
        if ($this->userSession->getUser() === null) {
            return new JSONResponse(['error' => $this->l10n->t('Not authenticated')], Http::STATUS_UNAUTHORIZED);
        }

        try {
            $this->crudService->requirePolicyPermission(
                surface: PolicyCrudService::SURFACE_PROHIBITION,
                action: 'create'
            );
            $data = $this->request->getParams();
            return new JSONResponse($this->crudService->createProhibition(data: $data), 201);
        } catch (InvalidArgumentException $e) {
            return new JSONResponse(['error' => $e->getMessage()], 400);
        } catch (Exception $e) {
            return $this->error(message: 'Failed to create prohibition: ', exception: $e);
        }

    }//end createProhibition()

    /**
     * Update a prohibition.
     *
     * @param string $id Record UUID.
     *
     * @return JSONResponse
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function updateProhibition(string $id): JSONResponse
    {
        if ($this->userSession->getUser() === null) {
            return new JSONResponse(['error' => $this->l10n->t('Not authenticated')], Http::STATUS_UNAUTHORIZED);
        }

        try {
            $this->crudService->requirePolicyPermission(
                surface: PolicyCrudService::SURFACE_PROHIBITION,
                action: 'update'
            );
            $data = $this->request->getParams();
            return new JSONResponse($this->crudService->updateProhibition(uuid: $id, data: $data));
        } catch (InvalidArgumentException $e) {
            return new JSONResponse(['error' => $e->getMessage()], 400);
        } catch (Exception $e) {
            return $this->error(message: 'Failed to update prohibition: ', exception: $e);
        }

    }//end updateProhibition()

    /**
     * Delete a prohibition.
     *
     * `publicationProhibition` declares `x-openregister-archival` in
     * `lib/Settings/docudesk_register.json` (retention P10Y — prohibitions are
     * court-order-backed and must survive for audit and appeal). OpenRegister
     * therefore refuses EVERY user-driven delete on this schema: rows expire
     * only through `OCA\OpenRegister\Cron\ArchivalRetentionTask`. The endpoint
     * is consequently impossible to satisfy — never merely "currently failing"
     * — and previously leaked that as an opaque HTTP 500.
     *
     * It now answers HTTP 409 Conflict: the request conflicts with the
     * resource's declared retention state, and no retry, permission change or
     * payload change can make it succeed. The body names retention as the
     * reason and carries OpenRegister's own `SCHEMA_ARCHIVAL_IMMUTABLE`
     * discriminator so a client can tell this apart from the 403 the
     * permission gate raises.
     *
     * @param string $id Record UUID.
     *
     * @return JSONResponse 409 while the schema declares archival retention
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @spec openspec/specs/entity-publication-policies/spec.md
     */
    public function deleteProhibition(string $id): JSONResponse
    {
        if ($this->userSession->getUser() === null) {
            return new JSONResponse(['error' => $this->l10n->t('Not authenticated')], Http::STATUS_UNAUTHORIZED);
        }

        try {
            $this->crudService->requirePolicyPermission(
                surface: PolicyCrudService::SURFACE_PROHIBITION,
                action: 'delete'
            );
            $this->crudService->deleteProhibition(uuid: $id);
            return new JSONResponse(['deleted' => $id]);
        } catch (ArchivalImmutableException $e) {
            return $this->retentionProtectedResponse(id: $id, exception: $e);
        } catch (Exception $e) {
            return $this->error(message: 'Failed to delete prohibition: ', exception: $e);
        }

    }//end deleteProhibition()

    /**
     * Build the 409 response for a retention-protected delete.
     *
     * @param string                     $id        The record UUID the caller tried to delete.
     * @param ArchivalImmutableException $exception OpenRegister's archival refusal.
     *
     * @return JSONResponse The 409 Conflict response
     *
     * @spec openspec/specs/entity-publication-policies/spec.md
     */
    private function retentionProtectedResponse(string $id, ArchivalImmutableException $exception): JSONResponse
    {
        $this->logger->info(
            'Prohibition delete refused: schema declares archival retention',
            ['prohibitionId' => $id, 'exception' => $exception]
        );

        return new JSONResponse(
            [
                'error'     => 'SCHEMA_ARCHIVAL_IMMUTABLE',
                'message'   => $this->l10n->t(
                    'Publication prohibitions are retained under a declared archival '
                    .'retention policy and cannot be deleted. Records expire automatically '
                    .'when their retention period ends.'
                ),
                'schema'    => 'publicationProhibition',
                'operation' => 'delete',
                'id'        => $id,
            ],
            Http::STATUS_CONFLICT
        );

    }//end retentionProtectedResponse()

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
