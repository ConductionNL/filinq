<?php

/**
 * Unit tests for MetricsController.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\MetricsController;
use OCP\AppFramework\Http;
use OCP\IRequest;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;

/**
 * The metrics endpoint renders this app's manifest through OpenRegister's
 * engine, and answers 503 with a comment line when that engine is absent.
 *
 * @covers \OCA\Filinq\Controller\MetricsController
 */
class MetricsControllerTest extends TestCase {

	/**
	 * The controller over the given container.
	 *
	 * @param ContainerInterface $container The container.
	 *
	 * @return MetricsController
	 */
	private function controller(ContainerInterface $container): MetricsController {
		return new MetricsController(request: $this->createMock(IRequest::class), container: $container);

	}//end controller()

	/**
	 * The engine's rendering of filinq's manifest is served as Prometheus text.
	 *
	 * @return void
	 */
	public function testTheManifestIsRenderedForThisApp(): void {
		$loader = new class {
			/**
			 * @var array<int, string>
			 */
			public array $asked = [];

			/**
			 * Load an app's manifest.
			 *
			 * @param string $appId The app.
			 *
			 * @return array<string, string>
			 */
			public function load(string $appId): array {
				$this->asked[] = $appId;
				return ['app' => $appId];
			}
		};
		$engine = new class {
			/**
			 * Render a manifest.
			 *
			 * @param array<string, string> $manifest The manifest.
			 *
			 * @return string
			 */
			public function render(array $manifest): string {
				return '# HELP filinq_up up' . "\n" . 'filinq_up{app="' . $manifest['app'] . '"} 1' . "\n";
			}
		};
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturnMap(
			[
				['OCA\\OpenRegister\\AppHost\\Observability\\ManifestLoader', $loader],
				['OCA\\OpenRegister\\AppHost\\Observability\\MetricsEngine', $engine],
			]
		);

		$response = $this->controller($container)->index();

		$this->assertSame(['filinq'], $loader->asked);
		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertSame("# HELP filinq_up up\nfilinq_up{app=\"filinq\"} 1\n", $response->getData());

	}//end testTheManifestIsRenderedForThisApp()

	/**
	 * Without OpenRegister's observability engine the endpoint says so, with a 503.
	 *
	 * @return void
	 */
	public function testWithoutTheEngineTheEndpointIsUnavailable(): void {
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willThrowException(new RuntimeException('no such service'));

		$response = $this->controller($container)->index();

		$this->assertSame(Http::STATUS_SERVICE_UNAVAILABLE, $response->getStatus());
		$this->assertSame("# metrics unavailable: the OpenRegister AppHost observability engine is not installed\n", $response->getData());

	}//end testWithoutTheEngineTheEndpointIsUnavailable()
}//end class
