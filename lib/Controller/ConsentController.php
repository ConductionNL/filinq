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
     * @param string          $appName         The application name
     * @param IRequest        $request         The request object
     * @param LoggerInterface $logger          Logger for error reporting
     * @param ConsentService  $consentService  Service for consent operations
     * @param SettingsService $settingsService Service for settings
     * @param IL10N           $l10n            The localization service
     *
     * @return void
     */
    public function __construct(
        string $appName,
        IRequest $request,
        private readonly LoggerInterface $logger,
        private readonly ConsentService $consentService,
        private readonly SettingsService $settingsService,
        private readonly IL10N $l10n
    ) {
        parent::__construct($appName, $request);

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
                    ['error' => $this->l10n->t('PublicationConsent register/schema not configured')],
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
                    continue;
                }

                $consents[] = (array) $result;
            }

            return new JSONResponse($consents);
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to list consents: '.$e->getMessage(),
                ['exception' => $e]
            );
            return new JSONResponse(
                ['error' => $this->l10n->t('Failed to list consents: %s', [$e->getMessage()])],
                500
            );
        }//end try

    }//end index()


    /**
     * Create a new consent request for a detected entity
     *
     * Expects JSON body with: documentId, entityType, entityText, and optionally extra fields.
     *
     * @return JSONResponse JSON response with the created consent record
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function create(): JSONResponse
    {
        try {
            $data = $this->request->getParams();

            // Validate required fields.
            $required = ['documentId', 'entityType', 'entityText'];
            foreach ($required as $field) {
                if (empty($data[$field]) === true) {
                    return new JSONResponse(
                        ['error' => $this->l10n->t('Missing required field: %s', [$field])],
                        400
                    );
                }
            }

            $settings = $this->settingsService->getAllSettings();
            $register = $settings['configuration']['publicationConsent_register'] ?? '';
            $schema   = $settings['configuration']['publicationConsent_schema'] ?? '';

            if (empty($register) === true || empty($schema) === true) {
                return new JSONResponse(
                    ['error' => $this->l10n->t('PublicationConsent register/schema not configured')],
                    400
                );
            }

            // Extract known fields and pass any remaining as extra.
            $knownFields = ['documentId', 'entityType', 'entityText'];
            $extra       = array_diff_key($data, array_flip($knownFields));

            // Remove framework-injected params that are not consent data.
            unset($extra['_route'], $extra['_method']);

            $result = $this->consentService->createConsentRequest(
                $data['documentId'],
                $data['entityType'],
                $data['entityText'],
                $register,
                $schema,
                $extra
            );

            return new JSONResponse($result, 201);
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to create consent: '.$e->getMessage(),
                ['exception' => $e]
            );
            return new JSONResponse(
                ['error' => $this->l10n->t('Failed to create consent: %s', [$e->getMessage()])],
                500
            );
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
            $settings = $this->settingsService->getAllSettings();
            $register = $settings['configuration']['publicationConsent_register'] ?? '';
            $schema   = $settings['configuration']['publicationConsent_schema'] ?? '';

            if (empty($register) === true || empty($schema) === true) {
                return new JSONResponse(
                    ['error' => $this->l10n->t('PublicationConsent register/schema not configured')],
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
                    ['error' => $this->l10n->t('Consent record not found')],
                    404
                );
            }

            if (is_object($object) === true && method_exists($object, 'jsonSerialize') === true) {
                return new JSONResponse($object->jsonSerialize());
            }

            return new JSONResponse((array) $object);
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to get consent: '.$e->getMessage(),
                ['exception' => $e]
            );
            return new JSONResponse(
                ['error' => $this->l10n->t('Failed to get consent: %s', [$e->getMessage()])],
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
     *
     * @SuppressWarnings(PHPMD.ShortVariable)
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
                    ['error' => $this->l10n->t('PublicationConsent register/schema not configured')],
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
                ['error' => $this->l10n->t('Failed to update consent: %s', [$e->getMessage()])],
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
                    ['error' => $this->l10n->t('PublicationConsent register/schema not configured')],
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
                ['error' => $this->l10n->t('Failed to get consents for document: %s', [$e->getMessage()])],
                500
            );
        }//end try

    }//end byDocument()


}//end class
