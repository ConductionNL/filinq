<?php

/**
 * Application bootstrap class for DocuDesk
 *
 * @category  AppInfo
 * @package   OCA\DocuDesk\AppInfo
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 */

declare(strict_types=1);

namespace OCA\DocuDesk\AppInfo;

use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCA\DocuDesk\Service\SettingsService;

/**
 * Class Application
 *
 * @package OCA\LarpingApp\AppInfo
 */
class Application extends App implements IBootstrap
{
    public const APP_ID = 'docudesk';


    /**
     * Constructor
     *
     * @param array $urlParams URL parameters for the application
     */
    public function __construct(
        array $urlParams=[],
    )
    {
        parent::__construct(appName: self::APP_ID, urlParams: $urlParams);

    }//end __construct()


    /**
     * Register services and event listeners
     *
     * @param IRegistrationContext $context The registration context
     *
     * @return void
     */
    public function register(IRegistrationContext $context): void
    {
        // Event listeners and search providers have been removed.
        // Text extraction and search are now handled by OpenRegister.
        // DocuDesk focuses on document anonymization and metadata enhancement.
        // No migrations needed as DocuDesk uses OpenRegister for storage.

    }//end register()


    /**
     * Boot the application
     *
     * @param IBootContext $context The boot context
     *
     * @return void
     */
    public function boot(IBootContext $context): void
    {
        $container = $context->getServerContainer();

        // @TODO: We should look into performance here, since its called on every call to the app.
        // Right now i can see a complete update going on. Perhaps we should see if our app
        // version is higher that the config version or something (This adds 15ms to every call).
        // Install and enable OpenRegister.
        try {
            // Install and enable OpenRegister.
            $settingsService = $container->get(\OCA\DocuDesk\Service\SettingsService::class);
            $settingsService->initialize();
//            $this->logger->info('DocuDesk has been installed, enabled and configured successfully');
        } catch (\Exception $e) {
//            $this->logger->warning('Failed to install/enable/configrue DocuDesk: '.$e->getMessage());
        }

    }//end boot()


}//end class
