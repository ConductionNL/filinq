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
use OCA\DocuDesk\EventListener\DocuDeskEventListener;
use OCA\DocuDesk\EventListener\DossierCheckedOnListener;
use OCA\DocuDesk\Service\Conversion\EmlBackend;
use OCA\DocuDesk\Service\Conversion\MpdfBackend;
use OCA\DocuDesk\Service\Conversion\OfficeAppBackend;
use OCA\DocuDesk\Service\Conversion\PhpWordBackend;
use OCA\DocuDesk\Service\PdfConversionService;
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
        $context->registerEventListener(ObjectCreatedEvent::class, DocuDeskEventListener::class);
        $context->registerEventListener(ObjectUpdatedEvent::class, DocuDeskEventListener::class);
        $context->registerEventListener(ObjectDeletedEvent::class, DocuDeskEventListener::class);

        // Auto-regen dossier grondslagen summary when checkedOn is updated.
        $context->registerEventListener(ObjectUpdatedEvent::class, DossierCheckedOnListener::class);

        // Register the PDF-conversion service with its cascade backends.
        // Order matters: PdfConversionService walks the list left-to-
        // right, returning the first success and aggregating failures
        // into a ConversionFailedException. See
        // openspec/changes/anonymise-output-as-pdf-by-default/design.md (D2).
        //
        // Cascade:
        // 1. OfficeAppBackend  — Collabora / OnlyOffice / Euro Office
        // via Nextcloud's IConversionManager
        // (NC 31+ providers register here).
        // 2. PhpWordBackend    — DOC / DOCX / ODT / RTF / HTML via
        // PhpOffice\PhpWord + mPDF.
        // 3. MpdfBackend       — HTML / TXT direct via mPDF (reuses
        // PdfService's PDF/A-3b config).
        // 4. EmlBackend        — stub; activates when OR ships its
        // message/rfc822 text extractor.
        $context->registerService(
            PdfConversionService::class,
            static function (ContainerInterface $c): PdfConversionService {
                // IConversionManager is NC 31+ only — autowire as null
                // on older releases so OfficeAppBackend can degrade
                // gracefully via its own isAvailable=false path
                // rather than blowing up the DI graph.
                $conversionManager = null;
                if (interface_exists(IConversionManager::class) === true) {
                    try {
                        $conversionManager = $c->get(IConversionManager::class);
                    } catch (\Throwable $e) {
                        $conversionManager = null;
                    }
                }

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
