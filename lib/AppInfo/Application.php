<?php

/**
 * Application bootstrap class for DocuDesk
 *
 * Thin Nextcloud entry point: it owns the app id and the bundled-vendor
 * autoload include, and delegates all service/listener/middleware wiring to
 * {@see RegistrationBootstrap} and its per-concern registrars.
 *
 * @category  AppInfo
 * @package   OCA\DocuDesk\AppInfo
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\AppInfo;

use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

/**
 * Class Application
 *
 * @package OCA\DocuDesk\AppInfo
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
    ) {
        parent::__construct(appName: self::APP_ID, urlParams: $urlParams);

        // Register the app's bundled vendor autoload so third-party
        // packages (mpdf, fpdi, twig, …) declared in composer.json
        // resolve at runtime. Nextcloud only autoloads the app's own
        // PSR-4 namespace by default; vendor deps live outside that
        // and need an explicit include. Mirrors OpenRegister's
        // Application::__construct pattern.
        $autoload = __DIR__.'/../../vendor/autoload.php';
        if (is_file($autoload) === true) {
            include_once $autoload;
        }

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
        // LOAD-ORDER HAZARD: OC_App::getEnabledApps() sort()s the app list and
        // Coordinator::registerApps() calls registerAutoloading() then register()
        // one app at a time, so this method runs BEFORE OCA\OpenRegister\ is
        // autoloadable (this app sorts before `openregister`). Any AppHost
        // reference here — including a class_exists() probe — therefore answers
        // FALSE on a perfectly healthy instance. Put OpenRegister's prefix on the
        // autoloader ourselves; registerAutoloading() touches only the autoloader
        // and is idempotent ($alreadyRegistered key guard). Deliberately NOT
        // IAppManager::loadApp(), which would mark OpenRegister loaded and boot it
        // before its own register() had run.
        try {
            $openRegisterPath = \OCP\Server::get(\OCP\App\IAppManager::class)->getAppPath('openregister');
            \OC_App::registerAutoloading('openregister', $openRegisterPath);
        } catch (\Throwable) {
            // OpenRegister absent/disabled — fall through to the degraded path.
        }

        $bootstrap = new RegistrationBootstrap();
        $bootstrap->register(context: $context);

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
        $bootstrap = new RegistrationBootstrap();
        $bootstrap->boot(container: $context->getServerContainer(), appName: self::APP_ID);

    }//end boot()
}//end class
