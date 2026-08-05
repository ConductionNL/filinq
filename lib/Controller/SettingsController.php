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
 *
 * @spec openspec/specs/admin-settings/spec.md
 * @spec openspec/specs/admin-settings/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Controller;

use Exception;
use OCA\DocuDesk\Service\AnonymiserBackendStateClient;
use OCA\DocuDesk\Service\SettingsService;
use OCA\DocuDesk\Settings\DocuDeskAdmin;
use OCP\App\IAppManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\AuthorizedAdminSetting;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * Controller for handling settings-related operations
 *
 * @category Controller
 * @package  OCA\DocuDesk\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/changes/anonymiser-backend-warning/tasks.md#task-3
 */
class SettingsController extends Controller
{
    /**
     * SettingsController constructor
     *
     * @param string                       $appName          The name of the app
     * @param IRequest                     $request          The request object
     * @param IAppManager                  $appManager       The app manager
     * @param IGroupManager                $groupManager     The group manager
     * @param IUserSession                 $userSession      The user session
     * @param LoggerInterface              $logger           Logger for error reporting
     * @param SettingsService              $settingsService  Service for settings operations
     * @param AnonymiserBackendStateClient $anonymiserClient Backend state client
     * @param IConfig                      $config           Nextcloud config for user values
     *
     * @return void
     */
    public function __construct(
        string $appName,
        IRequest $request,
        private readonly IAppManager $appManager,
        private readonly IGroupManager $groupManager,
        private readonly IUserSession $userSession,
        private readonly LoggerInterface $logger,
        private readonly SettingsService $settingsService,
        private readonly AnonymiserBackendStateClient $anonymiserClient,
        private readonly IConfig $config,
    ) {
        parent::__construct(appName: $appName, request: $request);

    }//end __construct()

    /**
     * Retrieve the current settings
     *
     * @return JSONResponse JSON response containing the current settings
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @spec openspec/specs/admin-settings/spec.md
     */
    public function index(): JSONResponse
    {
        try {
            $user    = $this->userSession->getUser();
            $isAdmin = $user !== null && $this->groupManager->isAdmin($user->getUID());

            $backendState = $this->anonymiserClient->getState();
            $dismissed    = false;
            if ($user !== null) {
                $dismissed = $this->config->getUserValue(
                    userId: $user->getUID(),
                    appName: 'docudesk',
                    key: \OCA\DocuDesk\Controller\AnonymiserWarningController::DISMISSED_KEY,
                    default: ''
                ) === '1';
            }

            $data = $this->settingsService->getAllSettings();
            return new JSONResponse(
                array_merge(
                    $data,
                    [
                        'openRegisters'     => in_array(needle: 'openregister', haystack: $this->appManager->getInstalledApps()),
                        'isAdmin'           => $isAdmin,
                        'anonymiserBackend' => array_merge(
                            $backendState,
                            [
                                'warningDismissed' => $dismissed,
                                'showWarning'      => ($backendState['method'] ?? 'regex') === 'regex' && $dismissed === false,
                            ]
                        ),
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
     * Admin-only: writing settings (including signing_verification_secret,
     * register/schema pointers, signing provider) must be restricted to
     * administrators to prevent authenticated non-admin users from forging
     * the HMAC verification secret or redirecting data to attacker-controlled
     * registers (wave-3 C1).
     *
     * @return JSONResponse JSON response containing the updated settings
     *
     * @spec openspec/specs/admin-settings/spec.md
     */
    #[AuthorizedAdminSetting(DocuDeskAdmin::class)]
    public function create(): JSONResponse
    {
        try {
            $user = $this->userSession->getUser();
            if ($user === null) {
                return new JSONResponse(
                    data: ['error' => 'Not authenticated'],
                    statusCode: Http::STATUS_UNAUTHORIZED
                );
            }

            if ($this->groupManager->isAdmin($user->getUID()) === false) {
                return new JSONResponse(
                    data: ['error' => 'Admin privileges required'],
                    statusCode: Http::STATUS_FORBIDDEN
                );
            }

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
        }//end try

    }//end create()

    /**
     * Update settings — the canonical ADR-066 write verb.
     *
     * `OCA\OpenRegister\AppHost\Routes::standard()` ships `settings#update`
     * (PUT /api/settings) for EVERY app, and DocuDesk's own SettingsController
     * had no `update()`: the route existed, resolved to this class, and blew up
     * on dispatch. Same write path (and the same admin guard) as
     * {@see create()}, which stays for the fleet's legacy POST dialect.
     *
     * @return JSONResponse JSON response containing the updated settings
     *
     * @spec openspec/specs/admin-settings/spec.md
     */
    #[AuthorizedAdminSetting(DocuDeskAdmin::class)]
    public function update(): JSONResponse
    {
        return $this->create();

    }//end update()

    /**
     * Re-run the register/schema initialisation from the app's register JSON.
     *
     * Counterpart to `settings#load` (POST /api/settings/load) in the canonical
     * AppHost route table — likewise routed for every app and likewise missing
     * here until now.
     *
     * @return JSONResponse The initialisation result.
     *
     * @spec openspec/specs/admin-settings/spec.md
     */
    #[AuthorizedAdminSetting(DocuDeskAdmin::class)]
    public function load(): JSONResponse
    {
        try {
            $user = $this->userSession->getUser();
            if ($user === null) {
                return new JSONResponse(
                    data: ['error' => 'Not authenticated'],
                    statusCode: Http::STATUS_UNAUTHORIZED
                );
            }

            if ($this->groupManager->isAdmin($user->getUID()) === false) {
                return new JSONResponse(
                    data: ['error' => 'Admin privileges required'],
                    statusCode: Http::STATUS_FORBIDDEN
                );
            }

            return new JSONResponse($this->settingsService->initialize());
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to load register configuration',
                [
                    'exception' => $e->getMessage(),
                ]
            );
            return new JSONResponse(['error' => $e->getMessage()], 500);
        }//end try

    }//end load()
}//end class
