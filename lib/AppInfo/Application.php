<?php

/**
 * Application bootstrap class for DocuDesk
 *
 * @category AppInfo
 * @package  OCA\DocuDesk\AppInfo
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/eml-pdf-assembly/tasks.md#task-8
 */

declare(strict_types=1);

namespace OCA\DocuDesk\AppInfo;

use OCA\DocuDesk\Dashboard\AnonymizationWidget;
use OCA\DocuDesk\Dashboard\FileEntitiesWidget;
use OCA\DocuDesk\EventListener\DocuDeskEventListener;
use OCA\DocuDesk\Service\Conversion\EmlBackend;
use OCA\DocuDesk\Service\Conversion\MpdfBackend;
use OCA\DocuDesk\Service\Conversion\OfficeAppBackend;
use OCA\DocuDesk\Service\Conversion\PhpWordBackend;
use OCA\DocuDesk\Service\EmlPdfAssemblyService;
use OCA\DocuDesk\Service\PdfConversionService;
use OCA\OpenRegister\Event\ObjectCreatedEvent;
use OCA\OpenRegister\Event\ObjectUpdatedEvent;
use OCA\OpenRegister\Event\ObjectDeletedEvent;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Files\Conversion\IConversionManager;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class Application
 *
 * @category AppInfo
 * @package  OCA\DocuDesk\AppInfo
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://conduction.nl
 *
 * @spec openspec/changes/eml-pdf-assembly/tasks.md#task-8
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
        // and need an explicit include.
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
     *
     * @spec openspec/changes/eml-pdf-assembly/tasks.md#task-8
     */
    public function register(IRegistrationContext $context): void
    {
        // Register dashboard widgets.
        $context->registerDashboardWidget(AnonymizationWidget::class);
        $context->registerDashboardWidget(FileEntitiesWidget::class);

        // Register event listeners for OpenRegister events.
        $context->registerEventListener(ObjectCreatedEvent::class, DocuDeskEventListener::class);
        $context->registerEventListener(ObjectUpdatedEvent::class, DocuDeskEventListener::class);
        $context->registerEventListener(ObjectDeletedEvent::class, DocuDeskEventListener::class);

        // Register PdfConversionService with its ordered cascade backends.
        // Cascade order (first success wins):
        // 1. OfficeAppBackend  — Collabora / OnlyOffice / Euro Office via NC IConversionManager
        // 2. PhpWordBackend    — DOC / DOCX / ODT / RTF / HTML via PhpWord + mPDF
        // 3. MpdfBackend       — HTML / TXT direct via mPDF
        // 4. EmlBackend        — EML rich PDF/A-3b via EmlPdfAssemblyService.
        $context->registerService(
            PdfConversionService::class,
            static function (ContainerInterface $c): PdfConversionService {
                // IConversionManager is NC 31+ only — degrade on older releases.
                $conversionManager = null;
                if (interface_exists(IConversionManager::class) === true) {
                    try {
                        $conversionManager = $c->get(IConversionManager::class);
                    } catch (\Throwable $e) {
                        $conversionManager = null;
                    }
                }

                $assemblyService = new EmlPdfAssemblyService(
                    pdfService: $c->get(\OCA\DocuDesk\Service\PdfService::class),
                    appConfig: $c->get(\OCP\IAppConfig::class),
                    logger: $c->get(LoggerInterface::class),
                );

                return new PdfConversionService(
                    backends: [
                        new OfficeAppBackend(
                            conversionManager: $conversionManager,
                            rootFolder: $c->get(\OCP\Files\IRootFolder::class),
                            userSession: $c->get(\OCP\IUserSession::class),
                            appConfig: $c->get(\OCP\IAppConfig::class),
                            logger: $c->get(LoggerInterface::class),
                        ),
                        new PhpWordBackend(
                            appConfig: $c->get(\OCP\IAppConfig::class),
                            tempManager: $c->get(\OCP\ITempManager::class),
                            pdfService: $c->get(\OCA\DocuDesk\Service\PdfService::class),
                            logger: $c->get(LoggerInterface::class),
                        ),
                        new MpdfBackend(
                            pdfService: $c->get(\OCA\DocuDesk\Service\PdfService::class),
                            appConfig: $c->get(\OCP\IAppConfig::class),
                            logger: $c->get(LoggerInterface::class),
                        ),
                        new EmlBackend(
                            appConfig: $c->get(\OCP\IAppConfig::class),
                            logger: $c->get(LoggerInterface::class),
                            assemblyService: $assemblyService,
                        ),
                    ],
                    logger: $c->get(LoggerInterface::class),
                );
            }
        );

    }//end register()

    /**
     * Boot the application
     *
     * @param IBootContext $context The boot context
     *
     * @return void
     *
     * @spec openspec/changes/eml-pdf-assembly/tasks.md#task-8
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
