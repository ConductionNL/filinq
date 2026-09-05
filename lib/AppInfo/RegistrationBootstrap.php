<?php

/**
 * Filinq Registration Bootstrap
 *
 * Composes the per-concern registrars that make up Filinq's DI wiring, so
 * `Application` stays a thin Nextcloud bootstrap entry point instead of holding
 * a reference to every collaborator in the app.
 *
 * @category  AppInfo
 * @package   OCA\Filinq\AppInfo
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\AppInfo;

use Exception;
use OCA\Filinq\Mcp\FilinqScannableServices;
use OCA\Filinq\Middleware\LanguageNegotiationMiddleware;
use OCA\Filinq\Service\SettingsService;
use OCA\OpenRegister\AppHost\Bootstrap;
use OCA\OpenRegister\Contract\ObjectServiceInterface;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use Psr\Container\ContainerInterface;

/**
 * Runs every registration and boot step Filinq needs.
 *
 * @category AppInfo
 * @package  OCA\Filinq\AppInfo
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects) This IS the composition
 * root: its whole job is to name every class the app wires together, so the
 * coupling count measures the size of the app rather than a design fault.
 * Splitting it to satisfy the metric would scatter the wiring across files
 * that each know part of the answer, which is what the registrars it calls
 * already do one layer down.
 */
class RegistrationBootstrap {
	/**
	 * Register every Filinq service, listener and middleware.
	 *
	 * @param IRegistrationContext $context The registration context.
	 *
	 * @return void
	 */
	public function register(IRegistrationContext $context): void {
		(new ObjectEventRegistrar())->register(context: $context);
		(new SigningEventRegistrar())->register(context: $context);
		(new PdfConversionRegistrar())->register(context: $context);

		$this->bindStoreController(context: $context);

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

		// ADR-063 chain 3/3: the per-app opt-in telling OpenRegister which of our
		// service classes its AttributeToolScanner may reflect for `#[McpTool]`
		// methods. The alias KEY is the discovery convention -- OR enumerates
		// `IMcpScannableServices::<appId>` -- so the string matters as much as
		// the class it points at.
		//
		// An alias, not a factory, for the same reason as the binding above: it
		// resolves only when something asks, so an instance without OpenRegister
		// still boots. Filinq ships no hand-written IMcpToolProvider; the read
		// surface comes from the `x-openregister-mcp` blocks in the schema
		// register instead.
		$context->registerServiceAlias(
			'OCA\\OpenRegister\\Mcp\\IMcpScannableServices::filinq',
			FilinqScannableServices::class
		);

		// Background jobs are declared in appinfo/info.xml under
		// <background-jobs>; Nextcloud auto-registers them with the IJobList.
		// IRegistrationContext has no registerBackgroundJob() method.
		// register-i18n adoption (Task 3.2): wire the filinq-side
		// language-negotiation middleware so OR's `LanguageService`
		// sees Accept-Language / ?_lang / X-Translation-Target-Language
		// on requests that hit filinq routes (the OR LanguageMiddleware
		// only fires for OR's own routes). This lets the OR
		// TranslationHandler resolve translatable properties on filinq
		// objects to the right variant.
		$context->registerMiddleware(LanguageNegotiationMiddleware::class);

		// AppHost observability adoption (ADR-006 / ADR-040). Registers only the
		// MetricsEngine; the Health/Metrics controllers auto-wire from OCP and
		// resolve the engine by FQCN string at dispatch time.
		//
		// AppHost boilerplate adoption (ADR-040) has deliberately NO registrar
		// of its own. The `/` + `/{path}` and `/api/preferences/{key}` routes
		// are named `dashboard#…` / `preferences#…`, which Nextcloud resolves to
		// OCA\Filinq\Controller\{Dashboard,Preferences}Controller. Both are
		// real classes in this app that extend OCP\AppFramework\Controller and
		// take only auto-wirable OCP dependencies, so neither needs an explicit
		// registration and neither can drag OpenRegister into the router's
		// reflection pass. templates/index.php and the `pref_` user-value
		// namespace stay scoped to filinq via Application::APP_ID.
		//
		// ⚠️ Do NOT re-introduce container aliases binding leaf AppHost class
		// names to the OpenRegister generics — that is what filinq#369
		// removed to stop a 500 on EVERY filinq route when openregister is
		// absent. See ObservabilityRegistrar::register() for the full rationale.
		(new ObservabilityRegistrar())->register(context: $context);

	}//end register()

	/**
	 * Run every boot-time step.
	 *
	 * @param ContainerInterface $container The server container.
	 * @param string $appName The filinq app id.
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
	/**
	 * Bind the store controller the adopted route table already declares.
	 *
	 * 🔴 THIS ROUTE ARRIVES WHETHER THE APP WANTS IT OR NOT.
	 *
	 * `Routes::standard()`, which appinfo/routes.php adopts, declares
	 * `/api/store/items`. The binding normally comes from
	 * `Bootstrap::register()`, and filinq does not call that: it composes its
	 * own registrars and keeps its own settings, signing and conversion
	 * classes. The store controller was simply never bound.
	 *
	 * So the route matched a controller class that does not exist, and every
	 * request to it returned HTTP 500 rather than 404. Measured on a running
	 * instance 2026-09-03, alongside decidiq and planninq.
	 *
	 * @param IRegistrationContext $context The registration context.
	 *
	 * @return void
	 *
	 * @SuppressWarnings(PHPMD.StaticAccess) OCA\OpenRegister\AppHost\Bootstrap
	 * is a cross-app static entry point in a SIBLING app that may be absent or
	 * unloadable here — the call is guarded by class_exists() and wrapped in a
	 * catch(\Throwable) for exactly that reason. It cannot be injected: this
	 * runs at the composition root, so there is no container to resolve an
	 * adapter from. OpenRegisterAutoloader::register() is static for the same
	 * reason.
	 */
	private function bindStoreController(IRegistrationContext $context): void {
		// ⚠️ THE PRELUDE IS NOT OPTIONAL HERE. `filinq` sorts before
		// `openregister`, and Nextcloud registers apps one at a time in sorted
		// order, so OCA\OpenRegister\ is NOT on the autoloader yet. Without
		// this the class_exists() below answers false on a perfectly healthy
		// instance and the binding is skipped in silence.
		OpenRegisterAutoloader::register();

		// The class_exists() guard MUST stay in this method: it is also the
		// assertion psalm relies on to accept the Bootstrap call below, and
		// psalm does not carry that narrowing across a call.
		if (class_exists(Bootstrap::class) === true) {
			try {
				Bootstrap::aliasStoreController(
					context: $context,
					appId: 'filinq',
					controllerNs: 'OCA\\Filinq\\Controller'
				);
			} catch (\Throwable) {
				// An OpenRegister older than the helper, or present but
				// unloadable. The store route is then no worse off than it is
				// today, and every registration around this one still runs.
			}
		}

	}//end bindStoreController()

}//end class
