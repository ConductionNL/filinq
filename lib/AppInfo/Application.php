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
use OCA\DocuDesk\Dashboard\AnonymizationWidget;
use OCA\DocuDesk\Dashboard\FileEntitiesWidget;
use OCA\DocuDesk\EventListener\DocuDeskEventListener;
use OCA\OpenRegister\Event\ObjectCreatedEvent;
use OCA\OpenRegister\Event\ObjectUpdatedEvent;
use OCA\OpenRegister\Event\ObjectDeletedEvent;

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
