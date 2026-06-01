<?php
/**
 * Policy Controller
 *
 * Controller for CRUD on the two policy surfaces — `publicationProhibition`
 * records (deny-list) and `publicationConsent` records with `scope: "entity"`
 * (standing consents). Backs the three admin pages in the Vue UI.
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
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use OCP\IRequest;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Policy-surface CRUD endpoints.
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
     */
    public function __construct(
        string $appName,
        IRequest $request,
        private readonly LoggerInterface $logger,
        private readonly PolicyCrudService $crudService,
        private readonly IL10N $l10n
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
        try {
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
     *
     * @SuppressWarnings(PHPMD.ShortVariable)
     */
    public function showProhibition(string $id): JSONResponse
    {
        try {
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
        try {
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
     *
     * @SuppressWarnings(PHPMD.ShortVariable)
     */
    public function updateProhibition(string $id): JSONResponse
    {
        try {
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
     * @param string $id Record UUID.
     *
     * @return JSONResponse
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @SuppressWarnings(PHPMD.ShortVariable)
     */
    public function deleteProhibition(string $id): JSONResponse
    {
        try {
            $this->crudService->deleteProhibition(uuid: $id);
            return new JSONResponse(['deleted' => $id]);
        } catch (Exception $e) {
            return $this->error(message: 'Failed to delete prohibition: ', exception: $e);
        }

    }//end deleteProhibition()


    /**
     * List standing consents.
     *
     * @return JSONResponse
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function indexStandingConsents(): JSONResponse
    {
        try {
            return new JSONResponse($this->crudService->listStandingConsents());
        } catch (Exception $e) {
            return $this->error(message: 'Failed to list standing consents: ', exception: $e);
        }

    }//end indexStandingConsents()


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
     * @SuppressWarnings(PHPMD.ShortVariable)
     */
    public function showStandingConsent(string $id): JSONResponse
    {
        try {
            $record = $this->crudService->getStandingConsent(uuid: $id);
            if ($record === null) {
                return new JSONResponse(['error' => $this->l10n->t('Standing consent not found')], 404);
            }

            return new JSONResponse($record);
        } catch (Exception $e) {
            return $this->error(message: 'Failed to load standing consent: ', exception: $e);
        }

    }//end showStandingConsent()


    /**
     * Create a standing consent.
     *
     * @return JSONResponse
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function createStandingConsent(): JSONResponse
    {
        try {
            $data = $this->request->getParams();
            return new JSONResponse($this->crudService->createStandingConsent(data: $data), 201);
        } catch (RuntimeException $e) {
            return new JSONResponse(['error' => $e->getMessage()], 403);
        } catch (InvalidArgumentException $e) {
            return new JSONResponse(['error' => $e->getMessage()], 400);
        } catch (Exception $e) {
            return $this->error(message: 'Failed to create standing consent: ', exception: $e);
        }

    }//end createStandingConsent()


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
     * @SuppressWarnings(PHPMD.ShortVariable)
     */
    public function updateStandingConsent(string $id): JSONResponse
    {
        try {
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

    }//end updateStandingConsent()


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
     * @SuppressWarnings(PHPMD.ShortVariable)
     */
    public function deleteStandingConsent(string $id): JSONResponse
    {
        try {
            $this->crudService->deleteStandingConsent(uuid: $id);
            return new JSONResponse(['deleted' => $id]);
        } catch (RuntimeException $e) {
            return new JSONResponse(['error' => $e->getMessage()], 403);
        } catch (Exception $e) {
            return $this->error(message: 'Failed to delete standing consent: ', exception: $e);
        }

    }//end deleteStandingConsent()


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
