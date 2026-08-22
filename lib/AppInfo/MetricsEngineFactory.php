<?php

/**
 * Filinq Metrics Engine Factory
 *
 * Builds OpenRegister's AppHost `MetricsEngine` explicitly from the server
 * container. OpenRegister's own MetricsEngine factory is registered under the
 * `openregister` app container and is not visible here, so the engine and each
 * of its metric sources are resolved by hand. Extracted from `Application`.
 *
 * ⚠️ Every OpenRegister class name in this file is a STRING resolved inside the
 * method body, and `build()` returns `object` rather than `MetricsEngine`. That
 * is deliberate. Filinq does not declare `<app>openregister</app>`, so an
 * admin can run it with OpenRegister absent; a return type is resolved when the
 * method is invoked, and an `use` import is one refactor away from becoming a
 * class-declaration-time reference. Keeping the file free of both means nothing
 * here can fatal while OpenRegister is missing — the caller simply never gets
 * an engine and MetricsController degrades to 503. See filinq#369 /
 * decidesk#377.
 *
 * @category  AppInfo
 * @package   OCA\Filinq\AppInfo
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/adopt-apphost/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\AppInfo;

use OCP\ICacheFactory;
use OCP\IConfig;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Constructs the AppHost MetricsEngine from the server container.
 *
 * @category AppInfo
 * @package  OCA\Filinq\AppInfo
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/adopt-apphost/spec.md
 */
class MetricsEngineFactory {
	/**
	 * Build the metrics engine.
	 *
	 * Returns `object`, not the engine's own type — see the file docblock.
	 *
	 * @param ContainerInterface $container The server container.
	 *
	 * @return object The constructed OpenRegister AppHost MetricsEngine.
	 *
	 * @spec openspec/specs/adopt-apphost/spec.md
	 */
	public function build(ContainerInterface $container): object {
		$namespace = '\\OCA\\OpenRegister\\AppHost\\Observability\\';
		$engine = $namespace . 'MetricsEngine';

		return new $engine(
			objectSource: $container->get($namespace . 'Source\\ObjectMetricSource'),
			tableSource: $container->get($namespace . 'Source\\TableMetricSource'),
			appConfigSource: $container->get($namespace . 'Source\\AppConfigMetricSource'),
			providerSource: $container->get($namespace . 'Source\\ProviderMetricSource'),
			renderer: $container->get($namespace . 'PrometheusRenderer'),
			manifestLoader: $container->get($namespace . 'ManifestLoader'),
			cacheFactory: $container->get(ICacheFactory::class),
			config: $container->get(IConfig::class),
			logger: $container->get(LoggerInterface::class)
		);

	}//end build()
}//end class
