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
class RegistrationBootstrap
{
    /**
     * Register every DocuDesk service, listener and middleware.
     *
     * @param IRegistrationContext $context The registration context.
     * @param string               $appName The docudesk app id.
     *
     * @return void
     */
    public function register(IRegistrationContext $context, string $appName): void
    {
        (new ObjectEventRegistrar())->register(context: $context);
        (new SigningEventRegistrar())->register(context: $context);
        (new PdfConversionRegistrar())->register(context: $context);

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

        (new ObservabilityRegistrar())->register(context: $context, appName: $appName);
        (new AppHostControllerRegistrar())->register(context: $context, appName: $appName);

    }//end register()

    /**
     * Run every boot-time step.
     *
     * @param ContainerInterface $container The server container.
     * @param string             $appName   The docudesk app id.
     *
     * @return void
     */
    public function boot(ContainerInterface $container, string $appName): void
    {
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
