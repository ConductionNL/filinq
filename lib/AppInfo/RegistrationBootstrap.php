<?php

/**
 * DocuDesk Registration Bootstrap
 *
 * Composes the per-concern registrars that make up DocuDesk's DI wiring, so
 * `Application` stays a thin Nextcloud bootstrap entry point instead of holding
 * a reference to every collaborator in the app.
 *
 * @category  AppInfo
 * @package   OCA\DocuDesk\AppInfo
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\AppInfo;

use Exception;
use OCA\DocuDesk\Middleware\LanguageNegotiationMiddleware;
use OCA\DocuDesk\Service\SettingsService;
use OCA\OpenRegister\Contract\ObjectServiceInterface;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use Psr\Container\ContainerInterface;

/**
 * Runs every registration and boot step DocuDesk needs.
 *
 * @category AppInfo
 * @package  OCA\DocuDesk\AppInfo
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class RegistrationBootstrap {
	/**
	 * Register every DocuDesk service, listener and middleware.
	 *
	 * @param IRegistrationContext $context The registration context.
	 *
	 * @return void
	 */
	public function register(IRegistrationContext $context): void {
		(new ObjectEventRegistrar())->register(context: $context);
		(new SigningEventRegistrar())->register(context: $context);
		(new PdfConversionRegistrar())->register(context: $context);

		// ADR-084: services type-hint OpenRegister's PUBLISHED interface, never
		// its concrete class, so a leaf app's unit tests can mock a type they
		// are able to load. Nextcloud autowires concrete classes across apps but
		// not interfaces, so the binding has to be stated — and the composition
		// root is the right place to state it.
		//
		// An ALIAS, not a factory: it is resolved when something actually asks
		// for the interface, so an instance without OpenRegister fails at the
		// route that needed the data rather than at registration. Both class
		// names are `::class` strings here and neither triggers an autoload,
		// which is what keeps ADR-083 rule 3's promise that the start screen
		// still boots.
		$context->registerServiceAlias(
			ObjectServiceInterface::class,
			'OCA\OpenRegister\Service\ObjectService'
		);

		// Background jobs are declared in appinfo/info.xml under
		// <background-jobs>; Nextcloud auto-registers them with the IJobList.
		// IRegistrationContext has no registerBackgroundJob() method.
		// register-i18n adoption (Task 3.2): wire the docudesk-side
		// language-negotiation middleware so OR's `LanguageService`
		// sees Accept-Language / ?_lang / X-Translation-Target-Language
		// on requests that hit docudesk routes (the OR LanguageMiddleware
		// only fires for OR's own routes). This lets the OR
		// TranslationHandler resolve translatable properties on docudesk
		// objects to the right variant.
		$context->registerMiddleware(LanguageNegotiationMiddleware::class);

		// AppHost observability adoption (ADR-006 / ADR-040). Registers only the
		// MetricsEngine; the Health/Metrics controllers auto-wire from OCP and
		// resolve the engine by FQCN string at dispatch time.
		//
		// AppHost boilerplate adoption (ADR-040) has deliberately NO registrar
		// of its own. The `/` + `/{path}` and `/api/preferences/{key}` routes
		// are named `dashboard#…` / `preferences#…`, which Nextcloud resolves to
		// OCA\DocuDesk\Controller\{Dashboard,Preferences}Controller. Both are
		// real classes in this app that extend OCP\AppFramework\Controller and
		// take only auto-wirable OCP dependencies, so neither needs an explicit
		// registration and neither can drag OpenRegister into the router's
		// reflection pass. templates/index.php and the `pref_` user-value
		// namespace stay scoped to docudesk via Application::APP_ID.
		//
		// ⚠️ Do NOT re-introduce container aliases binding leaf AppHost class
		// names to the OpenRegister generics — that is what docudesk#369
		// removed to stop a 500 on EVERY docudesk route when openregister is
		// absent. See ObservabilityRegistrar::register() for the full rationale.
		(new ObservabilityRegistrar())->register(context: $context);

	}//end register()

	/**
	 * Run every boot-time step.
	 *
	 * @param ContainerInterface $container The server container.
	 * @param string $appName The docudesk app id.
	 *
	 * @return void
	 */
	public function boot(ContainerInterface $container, string $appName): void {
		(new ObjectEventRegistrar())->boot(container: $container, appName: $appName);

		// Initialize OpenRegister configuration on boot.
		try {
			$settingsService = $container->get(SettingsService::class);
			$settingsService->initialize();
		} catch (Exception $e) {
			// Silently fail - initialization errors are logged by SettingsService.
		}

	}//end boot()
}//end class
