<?php
/**
 * Controller for handling settings-related operations
 *
 * @category Controller
 * @package  OCA\DocuDesk\Controller
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.DocuDesk.app
 */

namespace OCA\DocuDesk\Controller;

use OCP\IAppConfig;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IConfig;
use Psr\Container\ContainerInterface;
use OCP\App\IAppManager;
use OCA\OpenRegister\Service\ObjectService;

/**
 * Controller for handling settings-related operations
 *
 * This controller provides functionality for managing application settings,
 * including configuration of Presidio API endpoints, reporting settings,
 * and integration with OpenRegister for object type management.
 *
 * @category Controller
 * @package  OCA\DocuDesk\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 */
class SettingsController extends Controller
{

    /**
     * The OpenRegister object service.
     *
     * @var \OCA\OpenRegister\Service\ObjectService|null The OpenRegister object service.
     */
    private ?ObjectService $objectService = null;


    /**
     * SettingsController constructor.
     *
     * @param string             $appName    The name of the app
     * @param IRequest           $request    The request object
     * @param IAppConfig         $appConfig  The app configuration
     * @param IConfig            $config     The system configuration
     * @param ContainerInterface $container  The container
     * @param IAppManager        $appManager The app manager
     *
     * @return void
     */
    public function __construct(
        $appName,
        IRequest $request,
        private readonly IAppConfig $appConfig,
        private readonly IConfig $config,
        private readonly ContainerInterface $container,
        private readonly IAppManager $appManager
    ) {
        parent::__construct($appName, $request);

    }//end __construct()


    /**
     * Attempts to retrieve the OpenRegister service from the container.
     *
     * @return \OCA\OpenRegister\Service\ObjectService|null The OpenRegister service if available, null otherwise.
     * @throws \RuntimeException If the service is not available.
     */
    public function getObjectService(): ?ObjectService
    {
        if (($this->objectService === null) === true) {
            if (in_array('openregister', $this->appManager->getInstalledApps(), true) === true) {
                $this->objectService = $this->container->get('OCA\OpenRegister\Service\ObjectService');
                return $this->objectService;
            }

            throw new \RuntimeException('OpenRegister service is not available.');
        }

        return $this->objectService;

    }//end getObjectService()


    /**
     * Retrieve the current settings.
     *
     * @return JSONResponse JSON response containing the current settings
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @psalm-return   JSONResponse
     * @phpstan-return JSONResponse
     */
    public function index(): JSONResponse
    {
        // Initialize the data array.
        $data = [];
        $data['objectTypes']        = ['template', 'report', 'entity'];
        $data['openRegisters']      = false;
        $data['availableRegisters'] = [];

        try {
            // Check if the OpenRegister service is available.
            $objectService = $this->getObjectService();
            if ($objectService !== null) {
                $data['openRegisters'] = true;
                // Get all registers from the ObjectService.
                $data['availableRegisters'] = $objectService->getRegisters();
            }
        } catch (\RuntimeException $e) {
            // OpenRegister is not available, continue with default settings.
        }

        // Build defaults array dynamically based on object types.
        $defaults = [];
        foreach ($data['objectTypes'] as $type) {
            $defaults["{$type}_source"]   = 'internal';
            $defaults["{$type}_schema"]   = '';
            $defaults["{$type}_register"] = 'document';
        }

        // Add system configuration values.
        $data['presidioAnalyzerUrl']   = $this->config->getSystemValue(
            'docudesk_presidio_analyzer_url',
            'http://presidio-api:8080/analyze'
        );
        $data['presidioAnonymizerUrl'] = $this->config->getSystemValue(
            'docudesk_presidio_anonymizer_url',
            'http://presidio-api:8080/anonymize'
        );
        $data['confidenceThreshold']   = $this->config->getSystemValue('docudesk_confidence_threshold', 0.7);
        $data['enableReporting']       = $this->config->getSystemValue('docudesk_enable_reporting', true);
        $data['enableAnonymization']   = $this->config->getSystemValue('docudesk_enable_anonymization', true);
        $data['storeOriginalText']     = $this->config->getSystemValue('docudesk_store_original_text', true);

        // Get the current values for the object types from the configuration.
        try {
            foreach ($defaults as $key => $defaultValue) {
                $data[$key] = $this->appConfig->getValueString($this->appName, $key, $defaultValue);
            }

            // Add configuration object for object type mappings.
            $data['configuration'] = [];
            foreach ($data['objectTypes'] as $type) {
                $data['configuration']["{$type}_source"]   = $data["{$type}_source"] ?? 'openregister';
                $data['configuration']["{$type}_schema"]   = $data["{$type}_schema"] ?? '';
                $data['configuration']["{$type}_register"] = $data["{$type}_register"] ?? '';
            }

            return new JSONResponse($data);
        } catch (\Exception $e) {
            return new JSONResponse(['error' => $e->getMessage()], 500);
        }

    }//end index()


    /**
     * Handle the post request to update settings.
     *
     * @return JSONResponse JSON response containing the updated settings
     *
     * @NoCSRFRequired
     *
     * @psalm-return   JSONResponse
     * @phpstan-return JSONResponse
     */
    public function create(): JSONResponse
    {
        // Get all parameters from the request.
        $data = $this->request->getParams();

        try {
            // Separate system config values from app config values.
            $systemConfigKeys = [
                'presidioAnalyzerUrl'   => 'docudesk_presidio_analyzer_url',
                'presidioAnonymizerUrl' => 'docudesk_presidio_anonymizer_url',
                'confidenceThreshold'   => 'docudesk_confidence_threshold',
                'enableReporting'       => 'docudesk_enable_reporting',
                'enableAnonymization'   => 'docudesk_enable_anonymization',
                'storeOriginalText'     => 'docudesk_store_original_text',
            ];

            // Update system configuration values.
            foreach ($systemConfigKeys as $requestKey => $configKey) {
                if (isset($data[$requestKey]) === true) {
                    $this->config->setSystemValue($configKey, $data[$requestKey]);
                    // Add the updated value to the response.
                    $data[$requestKey] = $this->config->getSystemValue($configKey);
                }
            }

            // Update app configuration values (for object storage settings).
            foreach ($data as $key => $value) {
                // Skip system config keys that we've already processed.
                if (in_array($key, array_keys($systemConfigKeys)) === true) {
                    continue;
                }

                $this->appConfig->setValueString($this->appName, $key, $value);
                // Retrieve the updated value to confirm the change.
                $data[$key] = $this->appConfig->getValueString($this->appName, $key);
            }

            return new JSONResponse($data);
        } catch (\Exception $e) {
            return new JSONResponse(['error' => $e->getMessage()], 500);
        }//end try

    }//end create()


    /**
     * Test the connection to the Presidio Analyzer API.
     *
     * @return JSONResponse JSON response containing the test result
     *
     * @NoCSRFRequired
     *
     * @psalm-return   JSONResponse
     * @phpstan-return JSONResponse
     */
    public function testPresidioAnalyzer(): JSONResponse
    {
        $presidioUrl = $this->request->getParam('presidioUrl');

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
            $client = new \GuzzleHttp\Client([
                'timeout'         => 10,
                'connect_timeout' => 5,
            ]);

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
        } catch (\Exception $e) {
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
     * Test the connection to the Presidio Anonymizer API.
     *
     * @return JSONResponse JSON response containing the test result
     *
     * @NoCSRFRequired
     *
     * @psalm-return   JSONResponse
     * @phpstan-return JSONResponse
     */
    public function testPresidioAnonymizer(): JSONResponse
    {
        $presidioUrl = $this->request->getParam('presidioUrl');

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
            $client = new \GuzzleHttp\Client([
                'timeout'         => 10,
                'connect_timeout' => 5,
            ]);

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
        } catch (\Exception $e) {
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
     * Get API configuration.
     *
     * @return JSONResponse JSON response containing the API configuration
     *
     * @NoCSRFRequired
     *
     * @psalm-return   JSONResponse
     * @phpstan-return JSONResponse
     */
    public function getApiConfig(): JSONResponse
    {
        try {
            $apiConfig = [
                'presidio' => [
                    'analyzerUrl'   => $this->config->getSystemValue('docudesk_presidio_analyzer_url', ''),
                    'anonymizerUrl' => $this->config->getSystemValue('docudesk_presidio_anonymizer_url', ''),
                    'key'           => $this->config->getSystemValue('docudesk_presidio_api_key', ''),
                ],
                'chatgpt'  => [
                    'url' => $this->config->getSystemValue('docudesk_chatgpt_url', ''),
                    'key' => $this->config->getSystemValue('docudesk_chatgpt_api_key', ''),
                ],
                'nldocs'   => [
                    'url' => $this->config->getSystemValue('docudesk_nldocs_url', ''),
                    'key' => $this->config->getSystemValue('docudesk_nldocs_api_key', ''),
                ],
            ];

            return new JSONResponse($apiConfig);
        } catch (\Exception $e) {
            return new JSONResponse(['error' => $e->getMessage()], 500);
        }//end try

    }//end getApiConfig()


    /**
     * Save API configuration.
     *
     * @return JSONResponse JSON response containing the updated API configuration
     *
     * @NoCSRFRequired
     *
     * @psalm-return   JSONResponse
     * @phpstan-return JSONResponse
     */
    public function saveApiConfig(): JSONResponse
    {
        try {
            // Get all parameters from the request.
            $apiConfig = $this->request->getParams();

            if (is_array($apiConfig) === false) {
                return new JSONResponse(['error' => 'Invalid API configuration'], 400);
            }

            // Store Presidio API configuration directly without validation.
            if (isset($apiConfig['presidio']) === true) {
                if (isset($apiConfig['presidio']['analyzerUrl']) === true) {
                    $this->config->setSystemValue('docudesk_presidio_analyzer_url', $apiConfig['presidio']['analyzerUrl']);
                }

                if (isset($apiConfig['presidio']['anonymizerUrl']) === true) {
                    $this->config->setSystemValue(
                        'docudesk_presidio_anonymizer_url',
                        $apiConfig['presidio']['anonymizerUrl']
                    );
                }

                if (isset($apiConfig['presidio']['key']) === true) {
                    $this->config->setSystemValue('docudesk_presidio_api_key', $apiConfig['presidio']['key']);
                }
            }

            // Store ChatGPT API configuration directly without validation.
            if (isset($apiConfig['chatgpt']) === true) {
                if (isset($apiConfig['chatgpt']['url']) === true) {
                    $this->config->setSystemValue('docudesk_chatgpt_url', $apiConfig['chatgpt']['url']);
                }

                if (isset($apiConfig['chatgpt']['key']) === true) {
                    $this->config->setSystemValue('docudesk_chatgpt_api_key', $apiConfig['chatgpt']['key']);
                }
            }

            // Store NLDocs API configuration directly without validation.
            if (isset($apiConfig['nldocs']) === true) {
                if (isset($apiConfig['nldocs']['url']) === true) {
                    $this->config->setSystemValue('docudesk_nldocs_url', $apiConfig['nldocs']['url']);
                }

                if (isset($apiConfig['nldocs']['key']) === true) {
                    $this->config->setSystemValue('docudesk_nldocs_api_key', $apiConfig['nldocs']['key']);
                }
            }

            return new JSONResponse(['success' => true]);
        } catch (\Exception $e) {
            return new JSONResponse(['error' => $e->getMessage()], 500);
        }//end try

    }//end saveApiConfig()


    /**
     * Get report configuration.
     *
     * @return JSONResponse JSON response containing the report configuration
     *
     * @NoCSRFRequired
     *
     * @psalm-return   JSONResponse
     * @phpstan-return JSONResponse
     */
    public function getReportConfig(): JSONResponse
    {
        try {
            $reportConfig = [
                'enable_reporting'       => $this->config->getSystemValue('docudesk_enable_reporting', true),
                'enable_anonymization'   => $this->config->getSystemValue('docudesk_enable_anonymization', true),
                'synchronous_processing' => $this->config->getSystemValue('docudesk_synchronous_processing', false),
                'confidence_threshold'   => $this->config->getSystemValue('docudesk_confidence_threshold', 0.7),
                'store_original_text'    => $this->config->getSystemValue('docudesk_store_original_text', true),
                'report_object_type'     => $this->config->getSystemValue('docudesk_report_object_type', 'report'),
                'log_object_type'        => $this->config->getSystemValue('docudesk_log_object_type', 'documentLog'),
            ];

            return new JSONResponse($reportConfig);
        } catch (\Exception $e) {
            return new JSONResponse(['error' => $e->getMessage()], 500);
        }

    }//end getReportConfig()


    /**
     * Save report configuration.
     *
     * @return JSONResponse JSON response containing the updated report configuration
     *
     * @NoCSRFRequired
     *
     * @psalm-return   JSONResponse
     * @phpstan-return JSONResponse
     */
    public function saveReportConfig(): JSONResponse
    {
        try {
            // Get all parameters from the request.
            $reportConfig = $this->request->getParams();

            if (is_array($reportConfig) === false) {
                return new JSONResponse(['error' => 'Invalid report configuration'], 400);
            }

            // Store report configuration.
            if (isset($reportConfig['enable_reporting']) === true) {
                $this->config->setSystemValue('docudesk_enable_reporting', (bool) $reportConfig['enable_reporting']);
            }

            if (isset($reportConfig['enable_anonymization']) === true) {
                $this->config->setSystemValue('docudesk_enable_anonymization', (bool) $reportConfig['enable_anonymization']);
            }

            if (isset($reportConfig['synchronous_processing']) === true) {
                $this->config->setSystemValue(
                    'docudesk_synchronous_processing',
                    (bool) $reportConfig['synchronous_processing']
                );
            }

            if (isset($reportConfig['confidence_threshold']) === true) {
                $threshold = (float) $reportConfig['confidence_threshold'];
                // Ensure threshold is between 0 and 1.
                $threshold = max(0.0, min(1.0, $threshold));
                $this->config->setSystemValue('docudesk_confidence_threshold', $threshold);
            }

            if (isset($reportConfig['store_original_text']) === true) {
                $this->config->setSystemValue('docudesk_store_original_text', (bool) $reportConfig['store_original_text']);
            }

            if (isset($reportConfig['report_object_type']) === true) {
                $this->config->setSystemValue('docudesk_report_object_type', $reportConfig['report_object_type']);
            }

            if (isset($reportConfig['log_object_type']) === true) {
                $this->config->setSystemValue('docudesk_log_object_type', $reportConfig['log_object_type']);
            }

            return new JSONResponse(['success' => true]);
        } catch (\Exception $e) {
            return new JSONResponse(['error' => $e->getMessage()], 500);
        }//end try

    }//end saveReportConfig()


}//end class
