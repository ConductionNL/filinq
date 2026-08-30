<?php

/**
 * Application bootstrap class for Filinq
 *
 * Thin Nextcloud entry point: it owns the app id and the bundled-vendor
 * autoload include, and delegates all service/listener/middleware wiring to
 * {@see RegistrationBootstrap} and its per-concern registrars.
 *
 * @category  AppInfo
 * @package   OCA\Filinq\AppInfo
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\AppInfo;

use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

/**
 * Class Application
 *
 * @package OCA\Filinq\AppInfo
 */
class Application extends App implements IBootstrap {
	public const APP_ID = 'filinq';

	/**
	 * Constructor
	 *
	 * @param array $urlParams URL parameters for the application
	 */
	public function __construct(
		array $urlParams = [],
	) {
		parent::__construct(appName: self::APP_ID, urlParams: $urlParams);

		// Register the app's bundled vendor autoload so third-party
		// packages (mpdf, fpdi, twig, …) declared in composer.json
		// resolve at runtime. Nextcloud only autoloads the app's own
		// PSR-4 namespace by default; vendor deps live outside that
		// and need an explicit include. Mirrors OpenRegister's
		// Application::__construct pattern.
		$autoload = __DIR__ . '/../../vendor/autoload.php';
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
	public function register(IRegistrationContext $context): void {
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
	public function boot(IBootContext $context): void {
		$bootstrap = new RegistrationBootstrap();
		$bootstrap->boot(container: $context->getServerContainer(), appName: self::APP_ID);

	}//end boot()
}//end class
