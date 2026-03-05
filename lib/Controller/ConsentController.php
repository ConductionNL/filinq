<?php
/**
 * Consent Controller
 *
 * Controller for GDPR publication consent operations.
 * Provides endpoints for managing consent records for entities
 * detected in documents.
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
use OCA\DocuDesk\Service\ConsentService;
use OCA\DocuDesk\Service\SettingsService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
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
     * @param string          $appName         The application name
     * @param IRequest        $request         The request object
     * @param LoggerInterface $logger          Logger for error reporting
     * @param ConsentService  $consentService  Service for consent operations
     * @param SettingsService $settingsService Service for settings
     *
     * @return void
     */
    public function __construct(
        string $appName,
        IRequest $request,
        private readonly LoggerInterface $logger,
        private readonly ConsentService $consentService,
        private readonly SettingsService $settingsService
    ) {
        parent::__construct(appName: $appName, request: $request);

    }//end __construct()


    /**
     * List consent records
     *
     * Uses ObjectService search via the consent service's register/schema.
     *
     * @return JSONResponse JSON response with list of consent records
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function index(): JSONResponse
    {
        try {
            $settings = $this->settingsService->getAllSettings();
            $register = $settings['configuration']['publicationConsent_register'] ?? '';
            $schema   = $settings['configuration']['publicationConsent_schema'] ?? '';

            if (empty($register) === true || empty($schema) === true) {
                return new JSONResponse(
                    ['error' => 'PublicationConsent register/schema not configured'],
                    400
                );
            }

            $objectService = $this->settingsService->getObjectService();
            $results       = $objectService->searchObjects(
                [
                    '@self' => ['register' => $register, 'schema' => $schema],
                ]
            );

            $consents = [];
            foreach ($results as $result) {
                if (is_object($result) === true && method_exists($result, 'jsonSerialize') === true) {
                    $consents[] = $result->jsonSerialize();
                } else {
                    $consents[] = (array) $result;
                }
            }

            return new JSONResponse($consents);
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to list consents: '.$e->getMessage(),
                ['exception' => $e]
            );
            return new JSONResponse(
                ['error' => 'Failed to list consents: '.$e->getMessage()],
                500
            );
        }//end try

    }//end index()


    /**
     * Get a specific consent record
     *
     * @param string $id The consent record UUID
     *
     * @return JSONResponse JSON response with consent record
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function show(string $id): JSONResponse
    {
        try {
            $settings = $this->settingsService->getAllSettings();
            $register = $settings['configuration']['publicationConsent_register'] ?? '';
            $schema   = $settings['configuration']['publicationConsent_schema'] ?? '';

            if (empty($register) === true || empty($schema) === true) {
                return new JSONResponse(
                    ['error' => 'PublicationConsent register/schema not configured'],
                    400
                );
            }

            $objectService = $this->settingsService->getObjectService();
            $object        = $objectService->find(
                id: $id,
                register: $register,
                schema: $schema,
                _rbac: false,
                _multitenancy: false
            );

            if ($object === null) {
                return new JSONResponse(
                    ['error' => 'Consent record not found'],
                    404
                );
            }

            if (is_object($object) === true && method_exists($object, 'jsonSerialize') === true) {
                $responseData = $object->jsonSerialize();
            } else {
                $responseData = (array) $object;
            }

            return new JSONResponse($responseData);
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to get consent: '.$e->getMessage(),
                ['exception' => $e]
            );
            return new JSONResponse(
                ['error' => 'Failed to get consent: '.$e->getMessage()],
                500
            );
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
     */
    public function update(string $id): JSONResponse
    {
        try {
            $data = $this->request->getParams();

            $settings = $this->settingsService->getAllSettings();
            $register = $settings['configuration']['publicationConsent_register'] ?? '';
            $schema   = $settings['configuration']['publicationConsent_schema'] ?? '';

            if (empty($register) === true || empty($schema) === true) {
                return new JSONResponse(
                    ['error' => 'PublicationConsent register/schema not configured'],
                    400
                );
            }

            $result = $this->consentService->updateConsentStatus($id, $register, $schema, $data);

            return new JSONResponse($result);
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to update consent: '.$e->getMessage(),
                ['exception' => $e]
            );
            return new JSONResponse(
                ['error' => 'Failed to update consent: '.$e->getMessage()],
                500
            );
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
            $settings = $this->settingsService->getAllSettings();
            $register = $settings['configuration']['publicationConsent_register'] ?? '';
            $schema   = $settings['configuration']['publicationConsent_schema'] ?? '';

            if (empty($register) === true || empty($schema) === true) {
                return new JSONResponse(
                    ['error' => 'PublicationConsent register/schema not configured'],
                    400
                );
            }

            $consents = $this->consentService->getConsentsByDocument($documentId, $register, $schema);

            return new JSONResponse($consents);
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to get consents for document: '.$e->getMessage(),
                ['exception' => $e]
            );
            return new JSONResponse(
                ['error' => 'Failed to get consents for document: '.$e->getMessage()],
                500
            );
        }//end try

    }//end byDocument()


}//end class
