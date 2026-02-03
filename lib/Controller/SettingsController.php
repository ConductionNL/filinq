<?php
/**
 * Settings Controller
 *
 * Controller for handling settings-related operations in DocuDesk.
 * Provides functionality for managing application settings, including
 * configuration of Presidio API endpoints and OpenRegister integration.
 *
 * @category Controller
 * @package  OCA\DocuDesk\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version  GIT: <git_id>
 * @link     https://www.DocuDesk.app
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Controller;

use Exception;
use OCA\DocuDesk\Service\SettingsService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

/**
 * Controller for handling settings-related operations
 *
 * This controller provides functionality for managing application settings,
 * including configuration of Presidio API endpoints and integration with
 * OpenRegister for document storage.
 *
 * @category Controller
 * @package  OCA\DocuDesk\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class SettingsController extends Controller
{
    /**
     * Logger instance for error reporting
     *
     * @var LoggerInterface
     */
    private readonly LoggerInterface $logger;

    /**
     * Settings service
     *
     * @var SettingsService
     */
    private readonly SettingsService $settingsService;

    /**
     * SettingsController constructor
     *
     * @param string           $appName        The name of the app
     * @param IRequest         $request        The request object
     * @param LoggerInterface  $logger         Logger for error reporting
     * @param SettingsService  $settingsService Service for settings operations
     *
     * @return void
     */
    public function __construct(
        string $appName,
        IRequest $request,
        LoggerInterface $logger,
        SettingsService $settingsService
    ) {
        parent::__construct($appName, $request);
        $this->logger          = $logger;
        $this->settingsService = $settingsService;

    }//end __construct()

    /**
     * Retrieve the current settings
     *
     * @return JSONResponse JSON response containing the current settings
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function index(): JSONResponse
    {
        try {
            // Delegate all business logic to service.
            $data = $this->settingsService->getAllSettings();
            return new JSONResponse($data);
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to retrieve settings',
                [
                    'exception' => $e->getMessage(),
                ]
            );
            return new JSONResponse(['error' => $e->getMessage()], 500);
        }

    }//end index()

    /**
     * Handle the post request to update settings
     *
     * @return JSONResponse JSON response containing the updated settings
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function create(): JSONResponse
    {
        try {
            // Get all parameters from the request.
            $data = $this->request->getParams();

            // Delegate update to service.
            $updatedData = $this->settingsService->updateSettings($data);

            return new JSONResponse($updatedData);
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to update settings',
                [
                    'exception' => $e->getMessage(),
                ]
            );
            return new JSONResponse(['error' => $e->getMessage()], 500);
        }

    }//end create()

    /**
     * Test the connection to the Presidio Analyzer API
     *
     * @return JSONResponse JSON response containing the test result
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function testPresidioAnalyzer(): JSONResponse
    {
        $presidioUrl = $this->request->getParam('presidioUrl');

        // Fallback to settings if not provided in request.
        if (empty($presidioUrl) === true) {
            try {
                $settings = $this->settingsService->getAllSettings();
                $presidioUrl = $settings['presidio_analyzer_url'] ?? null;
            } catch (Exception $e) {
                // Continue with error handling below.
            }
        }

        if (empty($presidioUrl) === true) {
            return new JSONResponse(['error' => 'Presidio Analyzer URL is required'], 400);
        }

        try {
            // Create a test payload.
            $payload = [
                'text'                    => 'John Smith lives in New York and his phone number is 212-555-1234.',
                'language'                => 'en',
                'score_threshold'         => 0.5,
                'return_decision_process' => false,
            ];

            // Create a Guzzle client.
            $client = new \GuzzleHttp\Client(
                [
                    'timeout'         => 10,
                    'connect_timeout' => 5,
                ]
            );

            // Send a test request to the Presidio Analyzer API.
            $response = $client->post(
                $presidioUrl,
                [
                    'json'    => $payload,
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept'       => 'application/json',
                    ],
                ]
            );

            // Check if the response is valid.
            $statusCode = $response->getStatusCode();
            $body       = json_decode($response->getBody()->getContents(), true);

            if ($statusCode === 200 && is_array($body) === true) {
                return new JSONResponse(
                    [
                        'success'           => true,
                        'message'           => 'Connection to Presidio Analyzer API successful',
                        'entities_detected' => count($body['entities'] ?? []),
                    ]
                );
            } else {
                return new JSONResponse(
                    [
                        'success' => false,
                        'message' => 'Invalid response from Presidio Analyzer API',
                    ],
                    500
                );
            }
        } catch (Exception $e) {
            return new JSONResponse(
                [
                    'success' => false,
                    'message' => 'Failed to connect to Presidio Analyzer API: '.$e->getMessage(),
                ],
                500
            );
        }//end try

    }//end testPresidioAnalyzer()

    /**
     * Test the connection to the Presidio Anonymizer API
     *
     * @return JSONResponse JSON response containing the test result
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function testPresidioAnonymizer(): JSONResponse
    {
        $presidioUrl = $this->request->getParam('presidioUrl');

        // Fallback to settings if not provided in request.
        if (empty($presidioUrl) === true) {
            try {
                $settings = $this->settingsService->getAllSettings();
                $presidioUrl = $settings['presidio_anonymizer_url'] ?? null;
            } catch (Exception $e) {
                // Continue with error handling below.
            }
        }

        if (empty($presidioUrl) === true) {
            return new JSONResponse(['error' => 'Presidio Anonymizer URL is required'], 400);
        }

        try {
            // Create a test payload.
            $payload = [
                'text'             => 'John Smith lives in New York and his phone number is 212-555-1234.',
                'analyzer_results' => [
                    [
                        'start'       => 0,
                        'end'         => 10,
                        'score'       => 0.8,
                        'entity_type' => 'PERSON',
                    ],
                ],
                'anonymizers'      => [
                    'DEFAULT' => [
                        'type'      => 'replace',
                        'new_value' => '[REDACTED]',
                    ],
                ],
            ];

            // Create a Guzzle client.
            $client = new \GuzzleHttp\Client(
                [
                    'timeout'         => 10,
                    'connect_timeout' => 5,
                ]
            );

            // Send a test request to the Presidio Anonymizer API.
            $response = $client->post(
                $presidioUrl,
                [
                    'json'    => $payload,
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept'       => 'application/json',
                    ],
                ]
            );

            // Check if the response is valid.
            $statusCode = $response->getStatusCode();
            $body       = json_decode($response->getBody()->getContents(), true);

            if ($statusCode === 200 && is_array($body) === true && isset($body['text']) === true) {
                return new JSONResponse(
                    [
                        'success'         => true,
                        'message'         => 'Connection to Presidio Anonymizer API successful',
                        'anonymized_text' => $body['text'],
                    ]
                );
            } else {
                return new JSONResponse(
                    [
                        'success' => false,
                        'message' => 'Invalid response from Presidio Anonymizer API',
                    ],
                    500
                );
            }
        } catch (Exception $e) {
            return new JSONResponse(
                [
                    'success' => false,
                    'message' => 'Failed to connect to Presidio Anonymizer API: '.$e->getMessage(),
                ],
                500
            );
        }//end try

    }//end testPresidioAnonymizer()

    /**
     * Get API configuration
     *
     * Retrieves API configuration from OpenRegister settings.
     *
     * @return JSONResponse JSON response containing the API configuration
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getApiConfig(): JSONResponse
    {
        try {
            // Settings are now read from OpenRegister via SettingsService.
            $settings = $this->settingsService->getAllSettings();

            $apiConfig = [
                'presidio' => [
                    'analyzerUrl'   => $settings['presidio_analyzer_url'] ?? 'http://presidio-api:8080/analyze',
                    'anonymizerUrl' => $settings['presidio_anonymizer_url'] ?? 'http://presidio-api:8080/anonymize',
                    'confidenceThreshold' => $settings['presidio_confidence_threshold'] ?? 0.7,
                ],
            ];

            return new JSONResponse($apiConfig);
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to get API config',
                [
                    'exception' => $e->getMessage(),
                ]
            );
            return new JSONResponse(['error' => $e->getMessage()], 500);
        }

    }//end getApiConfig()

    /**
     * Save API configuration
     *
     * @return JSONResponse JSON response containing the updated API configuration
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function saveApiConfig(): JSONResponse
    {
        try {
            // Get all parameters from the request.
            $apiConfig = $this->request->getParams();

            if (is_array($apiConfig) === false) {
                return new JSONResponse(['error' => 'Invalid API configuration'], 400);
            }

            // Prepare settings data for update.
            $settingsData = [];

            // Store Presidio API configuration.
            if (isset($apiConfig['presidio']) === true) {
                if (isset($apiConfig['presidio']['analyzerUrl']) === true) {
                    $settingsData['presidio_analyzer_url'] = $apiConfig['presidio']['analyzerUrl'];
                }

                if (isset($apiConfig['presidio']['anonymizerUrl']) === true) {
                    $settingsData['presidio_anonymizer_url'] = $apiConfig['presidio']['anonymizerUrl'];
                }

                if (isset($apiConfig['presidio']['confidenceThreshold']) === true) {
                    $settingsData['presidio_confidence_threshold'] = (string) $apiConfig['presidio']['confidenceThreshold'];
                }
            }

            // Update settings via service.
            $updatedData = $this->settingsService->updateSettings($settingsData);

            return new JSONResponse(['success' => true, 'config' => $updatedData]);
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to save API config',
                [
                    'exception' => $e->getMessage(),
                ]
            );
            return new JSONResponse(['error' => $e->getMessage()], 500);
        }

    }//end saveApiConfig()

}//end class
