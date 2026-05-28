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
use OCA\DocuDesk\Service\SettingsService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
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
     * SettingsController constructor
     *
     * @param string          $appName         The name of the app
     * @param IRequest        $request         The request object
     * @param LoggerInterface $logger          Logger for error reporting
     * @param SettingsService $settingsService Service for settings operations
     *
     * @return void
     */
    public function __construct(
        string $appName,
        IRequest $request,
        private readonly LoggerInterface $logger,
        private readonly SettingsService $settingsService
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
     */
    public function index(): JSONResponse
    {
        try {
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
