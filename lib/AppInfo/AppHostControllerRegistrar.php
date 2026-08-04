<?php

/**
 * DocuDesk AppHost Controller Registrar
 *
 * Binds DocuDesk's conventional AppHost controller class names to OpenRegister's
 * shared generic controllers with docudesk's app id injected as `$appName`.
 * Extracted from `Application`.
 *
 * @category  AppInfo
 * @package   OCA\DocuDesk\AppInfo
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/specs/adopt-apphost/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\AppInfo;

use OCA\DocuDesk\Controller\DashboardController;
use OCA\OpenRegister\AppHost\Controller\GenericDashboardController;
use OCA\OpenRegister\AppHost\Controller\GenericPreferencesController;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Container\ContainerInterface;

/**
 * Registers the AppHost dashboard/preferences generics under docudesk's names.
 *
 * @category AppInfo
 * @package  OCA\DocuDesk\AppInfo
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class AppHostControllerRegistrar
{
    /**
     * Register the AppHost boilerplate controllers.
     *
     * AppHost boilerplate adoption (ADR-040). The bespoke DashboardController
     * (SPA page + catch-all) and PreferencesController (per-user key/value UI
     * flags) were byte-for-byte copies of OpenRegister's shared AppHost
     * generics, so bind docudesk's conventional AppHost controller class names
     * to the engine generics with docudesk's app id injected as $appName. URLs,
     * route names' targets and JSON contracts are unchanged; the engine owns the
     * (identical) auth posture so the leaf cannot drift it. The bespoke classes
     * were deleted.
     *
     * NOT adopted (kept bespoke — domain-entangled): SettingsController (its
     * index() merges the openregister-installed flag + isAdmin +
     * anonymiser-backend warning state on top of the bespoke
     * SettingsService->getAllSettings() envelope, and create() is admin-gated
     * via AuthorizedAdminSetting(DocuDeskAdmin::class) + explicit isAdmin
     * recheck — the generic SettingsController reproduces none of that), the
     * DocuDeskAdmin AdminSettings, and the GDPR/consent, anonymisation,
     * PDF/print, signing and dossier domain controllers.
     *
     * The /api/preferences/{key} and / + /{path} routes resolve to
     * leaf-namespaced AppHost controller class names
     * (OCA\DocuDesk\AppHost\Controller\Generic{Dashboard,Preferences}Controller)
     * that do not physically exist in this app — same pattern as the
     * Health/Metrics adoption. Register them as services that construct the
     * OpenRegister generics with docudesk's id injected as $appName, so
     * templates/index.php and the `pref_` user-value namespace are scoped to
     * docudesk, never OpenRegister.
     *
     * @param IRegistrationContext $context The registration context.
     * @param string               $appName The docudesk app id.
     *
     * @return void
     *
     * @spec openspec/specs/adopt-apphost/spec.md
     */
    public function register(IRegistrationContext $context, string $appName): void
    {
        $context->registerService(
            'OCA\\DocuDesk\\AppHost\\Controller\\GenericDashboardController',
            static function (ContainerInterface $container) use ($appName): GenericDashboardController {
                return new GenericDashboardController(
                    appName: $appName,
                    request: $container->get(IRequest::class)
                );
            }
        );

        // The conventional `dashboard#page` route resolves to this real
        // DashboardController subclass; register it explicitly (mirroring
        // procest) so NC's DI constructs it from IRequest — the AppHost engine
        // base otherwise expects an injected `string $appName`.
        $context->registerService(
            DashboardController::class,
            static function (ContainerInterface $container): DashboardController {
                return new DashboardController(
                    request: $container->get(IRequest::class)
                );
            }
        );

        $context->registerService(
            'OCA\\DocuDesk\\AppHost\\Controller\\GenericPreferencesController',
            static function (ContainerInterface $container) use ($appName): GenericPreferencesController {
                return new GenericPreferencesController(
                    appName: $appName,
                    request: $container->get(IRequest::class),
                    config: $container->get(IConfig::class),
                    userSession: $container->get(IUserSession::class)
                );
            }
        );

    }//end register()
}//end class
