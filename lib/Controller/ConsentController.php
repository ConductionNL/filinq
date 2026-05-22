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
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Controller;

use Exception;
use OCA\DocuDesk\Service\ConsentCrudService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

/**
 * Controller for consent-specific endpoints
 *
 * @category Controller
 * @package  OCA\DocuDesk\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class ConsentController extends Controller
{
    /**
     * Constructor for ConsentController
     *
     * @param string             $appName     The application name
     * @param IRequest           $request     The request object
     * @param LoggerInterface    $logger      Logger for error reporting
     * @param ConsentCrudService $crudService CRUD service for consent records
     * @param IL10N              $l10n        The localization service
     *
     * @return void
     */
    public function __construct(
        string $appName,
        IRequest $request,
        private readonly LoggerInterface $logger,
        private readonly ConsentCrudService $crudService,
        private readonly IL10N $l10n
    ) {
        parent::__construct(appName: $appName, request: $request);

    }//end __construct()

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
            500
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
     */
    public function index(): JSONResponse
    {
        try {
            $config = $this->crudService->getConsentConfig();
            if ($config === null) {
                return $this->notConfiguredResponse();
            }

            return new JSONResponse(
                $this->crudService->listConsents($config['register'], $config['schema'])
            );
        } catch (Exception $e) {
            return $this->errorResponse(message: 'Failed to list consents: ', exception: $e);
        }//end try

    }//end index()

    /**
     * Create a new consent request for a detected entity
     *
     * @return JSONResponse JSON response with the created consent record
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function create(): JSONResponse
    {
        try {
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
            return $this->errorResponse(message: 'Failed to create consent: ', exception: $e);
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
     * @SuppressWarnings(PHPMD.ShortVariable)
     */
    public function show(string $id): JSONResponse
    {
        try {
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

            return new JSONResponse($consent);
        } catch (Exception $e) {
            return $this->errorResponse(message: 'Failed to get consent: ', exception: $e);
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
     * @NoCSRFRequired
     *
     * @SuppressWarnings(PHPMD.ShortVariable)
     */
    public function update(string $id): JSONResponse
    {
        try {
            $config = $this->crudService->getConsentConfig();
            if ($config === null) {
                return $this->notConfiguredResponse();
            }

            $result = $this->crudService->updateConsentStatus(
                $id,
                $config['register'],
                $config['schema'],
                $this->request->getParams()
            );

            return new JSONResponse($result);
        } catch (Exception $e) {
            return $this->errorResponse(message: 'Failed to update consent: ', exception: $e);
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
     */
    public function byDocument(string $documentId): JSONResponse
    {
        try {
            $config = $this->crudService->getConsentConfig();
            if ($config === null) {
                return $this->notConfiguredResponse();
            }

            $consents = $this->crudService->getConsentsByDocument(
                $documentId,
                $config['register'],
                $config['schema']
            );

            return new JSONResponse($consents);
        } catch (Exception $e) {
            return $this->errorResponse(message: 'Failed to get consents for document: ', exception: $e);
        }//end try

    }//end byDocument()
}//end class
