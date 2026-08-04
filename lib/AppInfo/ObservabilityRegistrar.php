<?php

/**
 * DocuDesk Observability Registrar
 *
 * Wires the AppHost-backed health + metrics controllers. Extracted from
 * `Application`.
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

use OCA\DocuDesk\Controller\HealthController;
use OCA\DocuDesk\Controller\MetricsController;
use OCA\OpenRegister\AppHost\Observability\HealthCheckExecutor;
use OCA\OpenRegister\AppHost\Observability\ManifestLoader;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\IRequest;
use Psr\Container\ContainerInterface;

/**
 * Registers DocuDesk's AppHost health and metrics controllers.
 *
 * @category AppInfo
 * @package  OCA\DocuDesk\AppInfo
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class ObservabilityRegistrar
{
    /**
     * Wire the AppHost-backed health + metrics controllers.
     *
     * Registers DocuDesk's HealthController / MetricsController (thin subclasses
     * of OpenRegister's GenericHealthController / GenericMetricsController) with
     * `appName = docudesk`, so the engine loads docudesk's src/manifest.json
     * `observability` block. ManifestLoader + HealthCheckExecutor resolve
     * through the server container; MetricsEngine is built explicitly by
     * {@see MetricsEngineFactory} because OpenRegister's own MetricsEngine
     * factory is registered under the `openregister` app container and is not
     * visible here.
     *
     * @param IRegistrationContext $context The registration context.
     * @param string               $appName The docudesk app id.
     *
     * @return void
     *
     * @spec openspec/specs/adopt-apphost/spec.md
     *
     * Health/MetricsController extend OpenRegister AppHost base classes that
     * are absent during static analysis, so Psalm sees no constructor on them
     * (TooManyArguments) and cannot see $container used inside the flagged
     * construction (UnusedClosureParam). Both resolve at runtime.
     *
     * @psalm-suppress TooManyArguments, UnusedClosureParam
     */
    public function register(IRegistrationContext $context, string $appName): void
    {
        $context->registerService(
            HealthController::class,
            static function (ContainerInterface $container) use ($appName): HealthController {
                return new HealthController(
                    appName: $appName,
                    request: $container->get(IRequest::class),
                    manifestLoader: $container->get(ManifestLoader::class),
                    executor: $container->get(HealthCheckExecutor::class)
                );
            }
        );

        $engineFactory = new MetricsEngineFactory();

        $context->registerService(
            MetricsController::class,
            static function (ContainerInterface $container) use ($appName, $engineFactory): MetricsController {
                return new MetricsController(
                    appName: $appName,
                    request: $container->get(IRequest::class),
                    manifestLoader: $container->get(ManifestLoader::class),
                    engine: $engineFactory->build(container: $container)
                );
            }
        );

    }//end register()
}//end class
