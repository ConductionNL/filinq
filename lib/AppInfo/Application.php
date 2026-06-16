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
use OCA\DocuDesk\Controller\HealthController;
use OCA\DocuDesk\Controller\MetricsController;
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

        // Auto-regen dossier grondslagen summary when checkedOn is updated.
        $context->registerEventListener(ObjectUpdatedEvent::class, DossierCheckedOnListener::class);

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

        // AppHost observability adoption (ADR-006 / ADR-040). The thin
        // HealthController / MetricsController subclasses of OpenRegister's
        // engine-owned generic controllers serve /api/health and /api/metrics
        // (URLs unchanged in appinfo/routes.php) — driven by the declarative
        // `observability` block in src/manifest.json. The bespoke checks /
        // metric lines / MetricsCollector are gone. Auth posture is owned by
        // the controllers (health public, metrics admin-only). These factories
        // supply the engine dependencies the subclasses inherit from the
        // generic controllers' constructors.
        $this->registerAppHostObservability(context: $context);
    }//end register()

    /**
     * Wire the AppHost-backed health + metrics controllers.
     *
     * Registers DocuDesk's HealthController / MetricsController (thin subclasses
     * of OpenRegister's GenericHealthController / GenericMetricsController) with
     * `appName = docudesk`, so the engine loads docudesk's src/manifest.json
     * `observability` block. ManifestLoader + HealthCheckExecutor resolve
     * through the server container; MetricsEngine is built explicitly because
     * OpenRegister's own MetricsEngine factory is registered under the
     * `openregister` app container and is not visible here (auto-wiring it
     * fresh would fail on the multi-arg constructor).
     *
     * @param IRegistrationContext $context The registration context.
     *
     * @return void
     *
     * @spec openspec/changes/adopt-apphost/tasks.md
     */
    private function registerAppHostObservability(IRegistrationContext $context): void
    {
        $context->registerService(
            HealthController::class,
            static function (ContainerInterface $container): HealthController {
                return new HealthController(
                    appName: self::APP_ID,
                    request: $container->get(\OCP\IRequest::class),
                    manifestLoader: $container->get(\OCA\OpenRegister\AppHost\Observability\ManifestLoader::class),
                    executor: $container->get(\OCA\OpenRegister\AppHost\Observability\HealthCheckExecutor::class)
                );
            }
        );

        $context->registerService(
            MetricsController::class,
            static function (ContainerInterface $container): MetricsController {
                $engine = new \OCA\OpenRegister\AppHost\Observability\MetricsEngine(
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

                return new MetricsController(
                    appName: self::APP_ID,
                    request: $container->get(\OCP\IRequest::class),
                    manifestLoader: $container->get(\OCA\OpenRegister\AppHost\Observability\ManifestLoader::class),
                    engine: $engine
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

        // Initialize OpenRegister configuration on boot.
        try {
            $settingsService = $container->get(\OCA\DocuDesk\Service\SettingsService::class);
            $settingsService->initialize();
        } catch (\Exception $e) {
            // Silently fail - initialization errors are logged by SettingsService.
        }

    }//end boot()
}//end class
