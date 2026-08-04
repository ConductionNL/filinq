<?php

/**
 * DocuDesk Metrics Engine Factory
 *
 * Builds OpenRegister's AppHost `MetricsEngine` explicitly from the server
 * container. OpenRegister's own MetricsEngine factory is registered under the
 * `openregister` app container and is not visible here, so the engine and each
 * of its metric sources are resolved by hand. Extracted from `Application`.
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

use OCA\OpenRegister\AppHost\Observability\ManifestLoader;
use OCA\OpenRegister\AppHost\Observability\MetricsEngine;
use OCA\OpenRegister\AppHost\Observability\PrometheusRenderer;
use OCA\OpenRegister\AppHost\Observability\Source\AppConfigMetricSource;
use OCA\OpenRegister\AppHost\Observability\Source\ObjectMetricSource;
use OCA\OpenRegister\AppHost\Observability\Source\ProviderMetricSource;
use OCA\OpenRegister\AppHost\Observability\Source\TableMetricSource;
use OCP\ICacheFactory;
use OCP\IConfig;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Constructs the AppHost MetricsEngine from the server container.
 *
 * @category AppInfo
 * @package  OCA\DocuDesk\AppInfo
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class MetricsEngineFactory
{
    /**
     * Build the metrics engine.
     *
     * @param ContainerInterface $container The server container.
     *
     * @return MetricsEngine The constructed engine.
     *
     * @spec openspec/specs/adopt-apphost/spec.md
     */
    public function build(ContainerInterface $container): MetricsEngine
    {
        return new MetricsEngine(
            objectSource: $container->get(ObjectMetricSource::class),
            tableSource: $container->get(TableMetricSource::class),
            appConfigSource: $container->get(AppConfigMetricSource::class),
            providerSource: $container->get(ProviderMetricSource::class),
            renderer: $container->get(PrometheusRenderer::class),
            manifestLoader: $container->get(ManifestLoader::class),
            cacheFactory: $container->get(ICacheFactory::class),
            config: $container->get(IConfig::class),
            logger: $container->get(LoggerInterface::class)
        );

    }//end build()
}//end class
