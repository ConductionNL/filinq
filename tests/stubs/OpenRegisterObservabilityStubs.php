<?php

/**
 * OpenRegister's AppHost metrics engine and its two parameter bundles, for the unit tests.
 *
 * MetricsEngine, MetricSources and MetricsRuntime are copied from OpenRegister
 * main (lib/AppHost/Observability, 82131bd5f6) with their constructor parameter
 * names unchanged, so a factory that passes a name OpenRegister no longer takes
 * fails here as it fails on a live instance.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\OpenRegister\AppHost\Observability;

/**
 * The four metric sources the engine reads.
 *
 * @category AppHost
 * @package  OCA\OpenRegister\AppHost\Observability
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://OpenRegister.app
 */
class MetricSources {
	/**
	 * Constructor.
	 *
	 * @param object $object    The object metric source.
	 * @param object $table     The table metric source.
	 * @param object $appConfig The app-config metric source.
	 * @param object $provider  The provider metric source.
	 */
	public function __construct(
		public object $object,
		public object $table,
		public object $appConfig,
		public object $provider,
	) {
	}//end __construct()
}//end class

/**
 * The manifest loader, cache and config the engine runs with.
 *
 * @category AppHost
 * @package  OCA\OpenRegister\AppHost\Observability
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://OpenRegister.app
 */
class MetricsRuntime {
	/**
	 * Constructor.
	 *
	 * @param object $manifestLoader The manifest loader.
	 * @param object $cacheFactory   The cache factory.
	 * @param object $config         The system config.
	 */
	public function __construct(
		public object $manifestLoader,
		public object $cacheFactory,
		public object $config,
	) {
	}//end __construct()
}//end class

/**
 * The Prometheus metrics engine.
 *
 * @category AppHost
 * @package  OCA\OpenRegister\AppHost\Observability
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://OpenRegister.app
 */
class MetricsEngine {
	/**
	 * Constructor.
	 *
	 * @param MetricSources  $sources  The metric sources.
	 * @param object         $renderer The Prometheus renderer.
	 * @param MetricsRuntime $runtime  The manifest loader, cache and config.
	 * @param object         $logger   The logger.
	 */
	public function __construct(
		public readonly MetricSources $sources,
		public readonly object $renderer,
		public readonly MetricsRuntime $runtime,
		public readonly object $logger,
	) {
	}//end __construct()
}//end class
