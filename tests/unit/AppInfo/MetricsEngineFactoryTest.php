<?php

/**
 * The metrics engine factory builds OpenRegister's engine with its current constructor.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\AppInfo
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/specs/adopt-apphost/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\AppInfo;

use OCA\Filinq\AppInfo\MetricsEngineFactory;
use OCA\OpenRegister\AppHost\Observability\MetricsEngine;
use OCP\ICacheFactory;
use OCP\IConfig;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

require_once __DIR__ . '/../../stubs/OpenRegisterObservabilityStubs.php';

/**
 * A factory passing a parameter name the engine no longer takes throws
 * "Unknown named parameter", the controller catches it, and /api/metrics
 * answers 503 on every instance.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\AppInfo
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class MetricsEngineFactoryTest extends TestCase {

	/**
	 * Each dependency reaches the engine in the bundle OpenRegister reads it from.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/adopt-apphost/spec.md
	 */
	public function testBuildsTheEngineFromItsSourceAndRuntimeBundles(): void {
		$namespace = 'OCA\\OpenRegister\\AppHost\\Observability\\';
		$services = [
			$namespace . 'Source\\ObjectMetricSource' => new \stdClass(),
			$namespace . 'Source\\TableMetricSource' => new \stdClass(),
			$namespace . 'Source\\AppConfigMetricSource' => new \stdClass(),
			$namespace . 'Source\\ProviderMetricSource' => new \stdClass(),
			$namespace . 'PrometheusRenderer' => new \stdClass(),
			$namespace . 'ManifestLoader' => new \stdClass(),
			ICacheFactory::class => $this->createMock(ICacheFactory::class),
			IConfig::class => $this->createMock(IConfig::class),
			LoggerInterface::class => $this->createMock(LoggerInterface::class),
		];

		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturnCallback(
			static fn (string $id): object => $services[ltrim($id, '\\')]
		);

		$engine = (new MetricsEngineFactory())->build(container: $container);

		$this->assertInstanceOf(MetricsEngine::class, $engine);
		$this->assertSame($services[$namespace . 'Source\\ObjectMetricSource'], $engine->sources->object);
		$this->assertSame($services[$namespace . 'Source\\TableMetricSource'], $engine->sources->table);
		$this->assertSame($services[$namespace . 'Source\\AppConfigMetricSource'], $engine->sources->appConfig);
		$this->assertSame($services[$namespace . 'Source\\ProviderMetricSource'], $engine->sources->provider);
		$this->assertSame($services[$namespace . 'PrometheusRenderer'], $engine->renderer);
		$this->assertSame($services[$namespace . 'ManifestLoader'], $engine->runtime->manifestLoader);
		$this->assertSame($services[ICacheFactory::class], $engine->runtime->cacheFactory);
		$this->assertSame($services[IConfig::class], $engine->runtime->config);
		$this->assertSame($services[LoggerInterface::class], $engine->logger);

	}//end testBuildsTheEngineFromItsSourceAndRuntimeBundles()
}//end class
