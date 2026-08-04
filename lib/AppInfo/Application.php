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
use OCP\EventDispatcher\IEventDispatcher;
use OCA\DocuDesk\Dashboard\AnonymizationWidget;
use OCA\DocuDesk\Dashboard\FileEntitiesWidget;
use OCA\DocuDesk\Event\DocumentSigningRequestedEvent;
use OCA\DocuDesk\EventListener\ApprovalStepListener;
use OCA\DocuDesk\EventListener\DocumentSigningRequestedListener;
use OCA\DocuDesk\EventListener\DocuDeskEventListener;
use OCA\DocuDesk\EventListener\DossierCheckedOnListener;
use OCA\DocuDesk\Middleware\LanguageNegotiationMiddleware;
use OCA\DocuDesk\Service\Conversion\EmlBackend;
use OCA\DocuDesk\Service\Conversion\LibreOfficeHeadlessBackend;
use OCA\DocuDesk\Service\Conversion\MpdfBackend;
use OCA\DocuDesk\Service\Conversion\OfficeAppBackend;
use OCA\DocuDesk\Service\Conversion\PhpWordBackend;
use OCA\DocuDesk\Service\PdfConversionService;
use OCA\OpenRegister\Event\ApprovalStepApprovedEvent;
use OCA\OpenRegister\Event\ApprovalStepCompletedEvent;
use OCA\OpenRegister\Event\ApprovalStepInitiatedEvent;
use OCA\OpenRegister\Event\ApprovalStepRejectedEvent;
use OCA\OpenRegister\Event\ObjectCreatedEvent;
use OCA\OpenRegister\Event\ObjectUpdatedEvent;
use OCA\OpenRegister\Event\ObjectDeletedEvent;
use OCP\Files\Conversion\IConversionManager;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

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
        // Register dashboard widgets.
        $context->registerDashboardWidget(AnonymizationWidget::class);
        $context->registerDashboardWidget(FileEntitiesWidget::class);

        // Register event listeners for OpenRegister events.
        // When documents are created/updated/deleted in OpenRegister,
        // DocuDesk will enrich metadata and manage consent tracking.
        //
        // Deliberately NOT narrowed to a register/schema set: DocuDeskEventHandler
        // identifies its work by PAYLOAD SHAPE (`looksLikeDossier()`,
        // `detectPolicyShape()`) rather than by schema, and EnrichmentRunner
        // enriches metadata on EVERY object on the instance regardless of
        // register. Declaring any slug list here would silently drop work.
        $context->registerEventListener(ObjectCreatedEvent::class, DocuDeskEventListener::class);
        $context->registerEventListener(ObjectUpdatedEvent::class, DocuDeskEventListener::class);
        $context->registerEventListener(ObjectDeletedEvent::class, DocuDeskEventListener::class);

        // Bridge OR ApprovalStep events into typed docudesk Signer*Events
        // and invoke the configured SigningProviderInterface when a step
        // becomes pending. Per migrate-signing-to-or-approval-workflow
        // (D2.1) — OR's `add-approval-step-events` shipped upstream as of
        // 2026-06-12 so the four event classes referenced below resolve at
        // runtime; if the OR app is absent (degraded install) the listener
        // simply never receives the events.
        $context->registerEventListener(ApprovalStepInitiatedEvent::class, ApprovalStepListener::class);
        $context->registerEventListener(ApprovalStepApprovedEvent::class, ApprovalStepListener::class);
        $context->registerEventListener(ApprovalStepRejectedEvent::class, ApprovalStepListener::class);
        $context->registerEventListener(ApprovalStepCompletedEvent::class, ApprovalStepListener::class);

        // Cross-app delegated-signing contract (docudesk-signing-events): any
        // installed consumer app (e.g. shillinq) dispatches
        // DocumentSigningRequestedEvent and DocuDesk raises the signing request
        // synchronously via SigningService::createRequest, writing the resolved
        // signingRequestId back onto the event. The in-process replacement for
        // the broken $registry->call('docudesk','createSigningRequest',…) path.
        $context->registerEventListener(
            DocumentSigningRequestedEvent::class,
            DocumentSigningRequestedListener::class
        );

        // Wire the PDF-conversion cascade. PdfConversionService takes an
        // ordered array of backends in its constructor; Nextcloud's DI cannot
        // autowire an `array` parameter, so without this explicit registration
        // every service that depends on PdfConversionService (e.g.
        // AnonymizationService → AnonymizationController) fails to construct
        // and the request 500s with a "Could not resolve backends!"
        // QueryException before the controller body ever runs. Order =
        // OfficeApp → LibreOffice → PhpWord → mPDF → EML (first success wins).
        // LibreOfficeHeadlessBackend (pdf-conversion-service) shells out to
        // `soffice --headless` with a lock + timeout as a high-fidelity
        // fallback when the NC IConversionManager providers are unavailable.
        $context->registerService(
            PdfConversionService::class,
            static function ($c): PdfConversionService {
                return new PdfConversionService(
                    backends: [
                        // OURS DI style ($c->get) preserved — autowires each
                        // backend, keeps development's LibreOfficeHeadlessBackend
                        // fallback, and pulls in Robert's EmlBackend (which now
                        // autowires EmlPdfAssemblyService via its constructor).
                        // Robert's explicit `new` variant referenced an undefined
                        // $conversionManager and dropped the LibreOffice backend.
                        $c->get(OfficeAppBackend::class),
                        $c->get(LibreOfficeHeadlessBackend::class),
                        $c->get(PhpWordBackend::class),
                        $c->get(MpdfBackend::class),
                        $c->get(EmlBackend::class),
                    ],
                    logger: $c->get(\Psr\Log\LoggerInterface::class),
                );
            }
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

        // AppHost observability adoption (ADR-006 / ADR-040). DocuDesk's
        // HealthController / MetricsController serve /api/health and
        // /api/metrics (URLs unchanged in appinfo/routes.php) — driven by the
        // declarative `observability` block in src/manifest.json. The bespoke
        // checks / metric lines / MetricsCollector are gone. Auth posture is
        // owned by the controllers (health public, metrics admin-only).
        //
        // Both controllers now auto-wire from OCP alone and resolve the engine
        // by FQCN string at dispatch time, so the only thing left to bind is
        // the MetricsEngine itself (see below for why it needs a factory).
        $this->registerAppHostObservability(context: $context);

        // AppHost boilerplate adoption (ADR-040). The DashboardController
        // (SPA page + catch-all) and PreferencesController (per-user key/value
        // UI flags) implement the AppHost boilerplate LOCALLY against OCP.
        // URLs, route names' targets, auth posture and JSON contracts are
        // unchanged from the engine generics they used to subclass.
        //
        // ⚠️ Do NOT re-introduce container aliases that bind leaf AppHost class
        // names to the OpenRegister generics, and do NOT turn those controllers
        // back into subclasses. Nextcloud's router `ReflectionClass()`es every
        // file in lib/Controller/ while MATCHING a route, so one unresolvable
        // parent returns HTTP 500 for EVERY docudesk route — and DocuDesk does
        // not declare `<app>openregister</app>`, so an admin can create exactly
        // that configuration. `extends` is resolved by the AUTOLOADER, not this
        // container, so lazy registration cannot rescue it. See decidesk#377.
        //
        // NOT adopted (kept bespoke — domain-entangled): SettingsController
        // (its index() merges the openregister-installed flag + isAdmin +
        // anonymiser-backend warning state on top of the bespoke
        // SettingsService->getAllSettings() envelope, and create() is
        // admin-gated via AuthorizedAdminSetting(DocuDeskAdmin::class) +
        // explicit isAdmin recheck — the generic SettingsController reproduces
        // none of that), the DocuDeskAdmin AdminSettings, and the GDPR/consent,
        // anonymisation, PDF/print, signing and dossier domain controllers.
        //
        // The `/` + `/{path}` and `/api/preferences/{key}` routes are named
        // `dashboard#…` / `preferences#…`, which Nextcloud resolves to
        // OCA\DocuDesk\Controller\{Dashboard,Preferences}Controller. Both are
        // real classes in this app that extend OCP\AppFramework\Controller and
        // take only auto-wirable OCP dependencies, so neither needs an explicit
        // registration and neither can drag OpenRegister into the router's
        // reflection pass. templates/index.php and the `pref_` user-value
        // namespace stay scoped to docudesk via Application::APP_ID.
    }//end register()

    /**
     * Register an object-lifecycle listener that declares its interest up front.
     *
     * OpenRegister's `ObjectEventSubscription` records the register/schema slugs
     * a listener reacts to and routes dispatches through a single shared proxy,
     * so an uninterested listener is neither constructed nor invoked. When
     * OpenRegister is absent — DocuDesk carries no hard dependency on it — this
     * degrades to the plain global registration it replaced, which is exactly
     * the behaviour every listener had before.
     *
     * MUST be called from boot(), never from register(). Nextcloud enables each
     * app's autoloader immediately before calling that app's own register()
     * (`OC\AppFramework\Bootstrap\Coordinator::registerApps()`), so at register()
     * time OpenRegister's classes are only autoloadable to apps that boot after
     * it. DocuDesk is app 21 of 92 and OpenRegister is 52, so the class_exists()
     * guard below was ALWAYS false here and this app silently fell back to an
     * unfiltered registration — one of seven fleet conversions that looked
     * successful while being inert. boot() runs only after every app's
     * register() has completed, which makes the guard order-independent.
     *
     * @param IEventDispatcher  $dispatcher The live event dispatcher.
     * @param string            $event      OpenRegister event class name.
     * @param string            $listener   Listener class name.
     * @param array<int,string> $registers  Register slugs the listener reacts to.
     * @param array<int,string> $schemas    Schema slugs the listener reacts to.
     *
     * @return void
     */
    private function registerFilteredObjectListener(
        IEventDispatcher $dispatcher,
        string $event,
        string $listener,
        array $registers,
        array $schemas
    ): void {
        $subscription = '\\OCA\\OpenRegister\\Event\\ObjectEventSubscription';
        if (class_exists($subscription) === true) {
            $subscription::subscribe(
                dispatcher: $dispatcher,
                event: $event,
                listener: $listener,
                registers: $registers,
                schemas: $schemas
            );
            return;
        }

        // Loud on purpose. This fallback is correct but UNFILTERED, and while it
        // was silent it was indistinguishable from a working narrowing.
        \OCP\Server::get(LoggerInterface::class)->warning(
            'OpenRegister ObjectEventSubscription unavailable: '.$listener
            .' fell back to an UNFILTERED registration for '.$event
            .' and will be invoked on every object write instance-wide.',
            ['app' => self::APP_ID]
        );

        $dispatcher->addServiceListener($event, $listener);

    }//end registerFilteredObjectListener()

    /**
     * Wire the AppHost metrics engine into DocuDesk's container.
     *
     * DocuDesk's HealthController / MetricsController no longer subclass the
     * OpenRegister generics, and no longer take engine collaborators as
     * constructor parameters — they resolve them out of the container BY FQCN
     * STRING at dispatch time and degrade (health `degraded` / metrics 503)
     * when the lookup fails. Both auto-wire from OCP alone, so neither needs a
     * factory here any more.
     *
     * MetricsEngine still does: OpenRegister's own MetricsEngine factory is
     * registered under the `openregister` app container and is not visible
     * here, and auto-wiring it fresh would fail on the multi-arg constructor.
     * Registering it under its own FQCN string keeps that explicit construction
     * while letting MetricsController find it with a plain `$container->get()`.
     *
     * ⚠️ The closure body is the ONLY place these OpenRegister class names are
     * resolved, and a closure body runs on `get()`, never at registration. The
     * closure therefore must NOT declare an OpenRegister return type — that
     * would be resolved on invocation too, but more importantly it keeps this
     * file free of any import that a static analyser or a future refactor could
     * promote into a class-declaration-time reference. See decidesk#377.
     *
     * @param IRegistrationContext $context The registration context.
     *
     * @return void
     *
     * @spec openspec/specs/adopt-apphost/spec.md
     */
    private function registerAppHostObservability(IRegistrationContext $context): void
    {
        $context->registerService(
            'OCA\\OpenRegister\\AppHost\\Observability\\MetricsEngine',
            static function (ContainerInterface $container): object {
                return new \OCA\OpenRegister\AppHost\Observability\MetricsEngine(
                    objectSource: $container->get(\OCA\OpenRegister\AppHost\Observability\Source\ObjectMetricSource::class),
                    tableSource: $container->get(\OCA\OpenRegister\AppHost\Observability\Source\TableMetricSource::class),
                    appConfigSource: $container->get(\OCA\OpenRegister\AppHost\Observability\Source\AppConfigMetricSource::class),
                    providerSource: $container->get(\OCA\OpenRegister\AppHost\Observability\Source\ProviderMetricSource::class),
                    renderer: $container->get(\OCA\OpenRegister\AppHost\Observability\PrometheusRenderer::class),
                    manifestLoader: $container->get(\OCA\OpenRegister\AppHost\Observability\ManifestLoader::class),
                    cacheFactory: $container->get(\OCP\ICacheFactory::class),
                    config: $container->get(\OCP\IConfig::class),
                    logger: $container->get(\Psr\Log\LoggerInterface::class)
                );
            }
        );
    }//end registerAppHostObservability()

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

        // Auto-regen dossier grondslagen summary when checkedOn is updated.
        // Declared here rather than in register() so the OpenRegister guard is
        // independent of this app's position in the bootstrap order.
        $this->registerFilteredObjectListener(
            dispatcher: $container->get(IEventDispatcher::class),
            event: ObjectUpdatedEvent::class,
            listener: DossierCheckedOnListener::class,
            registers: ['docudesk'],
            schemas: ['dossier']
        );

        // Initialize OpenRegister configuration on boot.
        try {
            $settingsService = $container->get(\OCA\DocuDesk\Service\SettingsService::class);
            $settingsService->initialize();
        } catch (\Exception $e) {
            // Silently fail - initialization errors are logged by SettingsService.
        }

    }//end boot()
}//end class
