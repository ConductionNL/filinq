<?php
/**
 * Settings Controller
 *
 * Controller for handling settings-related operations in DocuDesk.
 * Provides functionality for managing consent and metadata enrichment settings.
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
use RuntimeException;
use OCA\DocuDesk\Service\SettingsService;
use OCP\App\IAppManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Controller for handling settings-related operations
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
     * The OpenRegister object service.
     *
     * @var \OCA\OpenRegister\Service\ObjectService|null The OpenRegister object service.
     */
    private ?\OCA\OpenRegister\Service\ObjectService $objectService = null;

    /**
     * SettingsController constructor
     *
     * @param string             $appName         The name of the app
     * @param IRequest           $request         The request object
     * @param ContainerInterface $container       The container
     * @param IAppManager        $appManager      The app manager
     * @param IGroupManager      $groupManager    The group manager
     * @param IUserSession       $userSession     The user session
     * @param LoggerInterface    $logger          Logger for error reporting
     * @param SettingsService    $settingsService Service for settings operations
     *
     * @return void
     */
    public function __construct(
        string $appName,
        IRequest $request,
        private readonly ContainerInterface $container,
        private readonly IAppManager $appManager,
        private readonly IGroupManager $groupManager,
        private readonly IUserSession $userSession,
        private readonly LoggerInterface $logger,
        private readonly SettingsService $settingsService
    ) {
        parent::__construct(appName: $appName, request: $request);

    }//end __construct()

    /**
     * Attempts to retrieve the OpenRegister service from the container.
     *
     * @return \OCA\OpenRegister\Service\ObjectService|null The OpenRegister service if available, null otherwise.
     * @throws \RuntimeException If the service is not available.
     */
    public function getObjectService(): ?\OCA\OpenRegister\Service\ObjectService
    {
        if (in_array(needle: 'openregister', haystack: $this->appManager->getInstalledApps()) === true) {
            $this->objectService = $this->container->get('OCA\OpenRegister\Service\ObjectService');
            return $this->objectService;
        }

        throw new RuntimeException('OpenRegister service is not available.');

    }//end getObjectService()

    /**
     * Attempts to retrieve the Configuration service from the container.
     *
     * @return \OCA\OpenRegister\Service\ConfigurationService|null The Configuration service if available, null otherwise.
     * @throws \RuntimeException If the service is not available.
     */
    public function getConfigurationService(): ?\OCA\OpenRegister\Service\ConfigurationService
    {
        if (in_array(needle: 'openregister', haystack: $this->appManager->getInstalledApps()) === true) {
            $configurationService = $this->container->get('OCA\OpenRegister\Service\ConfigurationService');
            return $configurationService;
        }

        throw new RuntimeException('Configuration service is not available.');

    }//end getConfigurationService()

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
            $user    = $this->userSession->getUser();
            $isAdmin = $user !== null && $this->groupManager->isAdmin($user->getUID());

            $data = $this->settingsService->getAllSettings();
            return new JSONResponse(
                array_merge(
                    $data,
                    [
                        'openRegisters' => in_array(needle: 'openregister', haystack: $this->appManager->getInstalledApps()),
                        'isAdmin'       => $isAdmin,
                    ]
                )
            );
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to retrieve settings',
                [
                    'exception' => $e->getMessage(),
                ]
            );
            return new JSONResponse(['error' => $e->getMessage()], 500);
        }//end try

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
            $data = $this->request->getParams();

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
}//end class
