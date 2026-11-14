<?php

/**
 * The public health endpoint, asserted from the caller.
 *
 * With OpenRegister's observability engine available the endpoint answers
 * what the engine reports, with the manifest's CORS choice. Without it, or
 * when the engine fails, it answers a degraded 200 rather than an error, so a
 * monitor can tell "running without OpenRegister" from "down".
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\HealthController;
use OCP\IConfig;
use OCP\IRequest;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;

/**
 * `HealthController`.
 *
 * @covers \OCA\Filinq\Controller\HealthController
 */
class HealthControllerTest extends TestCase {

	private const LOADER = 'OCA\\OpenRegister\\AppHost\\Observability\\ManifestLoader';

	private const EXECUTOR = 'OCA\\OpenRegister\\AppHost\\Observability\\HealthCheckExecutor';

	/**
	 * The controller over a container answering the given services.
	 *
	 * @param array<string, object>|\Throwable $services The services by id, or what `get` throws.
	 * @param string                           $installed The installed version app config holds.
	 *
	 * @return HealthController The controller.
	 */
	private function controller(array|\Throwable $services, string $installed = '1.4.0'): HealthController {
		$container = $this->createMock(ContainerInterface::class);
		if ($services instanceof \Throwable) {
			$container->method('get')->willThrowException($services);
		} else {
			$container->method('get')->willReturnCallback(
				static fn (string $id): object => $services[$id] ?? throw new RuntimeException('no ' . $id)
			);
		}

		$config = $this->createMock(IConfig::class);
		$config->method('getAppValue')->willReturnMap([['filinq', 'installed_version', '', $installed]]);

		return new HealthController(
			request: $this->createMock(IRequest::class),
			config: $config,
			container: $container,
		);
	}//end controller()

	/**
	 * A manifest loader double.
	 *
	 * @param bool $cors Whether the manifest allows cross-origin reads.
	 *
	 * @return object The loader.
	 */
	private function loader(bool $cors): object {
		return new class($cors) {

			/**
			 * The app ids asked for.
			 *
			 * @var array<int, string>
			 */
			public array $asked = [];

			/**
			 * Build the loader.
			 *
			 * @param bool $cors Whether the manifest allows cross-origin reads.
			 */
			public function __construct(private readonly bool $cors) {
			}

			/**
			 * The manifest of an app.
			 *
			 * @param string $appId The app id.
			 *
			 * @return object The manifest.
			 */
			public function load(string $appId): object {
				$this->asked[] = $appId;
				return (object)['cors' => $this->cors];
			}

			/**
			 * The app's version.
			 *
			 * @param string $appId The app id.
			 *
			 * @return string The version.
			 */
			public function appVersion(string $appId): string {
				return '2.0.1';
			}
		};
	}//end loader()

	/**
	 * An executor double answering the given result.
	 *
	 * @param object|null $result What `execute` answers; null makes it throw.
	 *
	 * @return object The executor.
	 */
	private function executor(?object $result): object {
		return new class($result) {

			/**
			 * Build the executor.
			 *
			 * @param object|null $result What `execute` answers.
			 */
			public function __construct(private readonly ?object $result) {
			}

			/**
			 * Run the checks.
			 *
			 * @param object $manifest The manifest.
			 *
			 * @return object The result.
			 */
			public function execute(object $manifest): object {
				return $this->result ?? throw new RuntimeException('check crashed');
			}
		};
	}//end executor()

	/**
	 * An engine result.
	 *
	 * @param string $status The overall status.
	 * @param int    $http   The HTTP status to answer with.
	 *
	 * @return object The result.
	 */
	private static function engineResult(string $status, int $http): object {
		return (object)[
			'status' => $status,
			'checks' => ['database' => 'ok', 'openregister' => 'ok'],
			'httpStatusCode' => $http,
		];
	}//end engineResult()

	/**
	 * The engine's answer is passed through, with CORS when the manifest allows it.
	 *
	 * @return void
	 */
	public function testTheEngineAnswerIsPassedThroughWithCors(): void {
		$loader   = $this->loader(cors: true);
		$response = $this->controller([
			self::LOADER => $loader,
			self::EXECUTOR => $this->executor(self::engineResult('ok', 200)),
		])->index();

		$this->assertSame(200, $response->getStatus());
		$this->assertSame(
			['status' => 'ok', 'app' => 'filinq', 'version' => '2.0.1', 'checks' => ['database' => 'ok', 'openregister' => 'ok']],
			$response->getData()
		);
		$this->assertSame(['filinq'], $loader->asked);
		$headers = $response->getHeaders();
		$this->assertSame('*', $headers['Access-Control-Allow-Origin']);
		$this->assertSame('GET, OPTIONS', $headers['Access-Control-Allow-Methods']);
	}//end testTheEngineAnswerIsPassedThroughWithCors()

	/**
	 * An unhealthy engine answer keeps its status code, and no CORS without the manifest's consent.
	 *
	 * @return void
	 */
	public function testAnUnhealthyAnswerKeepsItsStatusAndNoCors(): void {
		$response = $this->controller([
			self::LOADER => $this->loader(cors: false),
			self::EXECUTOR => $this->executor(self::engineResult('error', 503)),
		])->index();

		$this->assertSame(503, $response->getStatus());
		$this->assertSame('error', $response->getData()['status']);
		$this->assertArrayNotHasKey('Access-Control-Allow-Origin', $response->getHeaders());
	}//end testAnUnhealthyAnswerKeepsItsStatusAndNoCors()

	/**
	 * Without OpenRegister the endpoint answers degraded with the installed version.
	 *
	 * @return void
	 */
	public function testWithoutTheEngineItAnswersDegraded(): void {
		$response = $this->controller(new RuntimeException('not installed'), installed: '1.4.0')->index();

		$this->assertSame(200, $response->getStatus());
		$this->assertSame(
			['status' => 'degraded', 'app' => 'filinq', 'version' => '1.4.0', 'checks' => ['openregister' => 'unavailable']],
			$response->getData()
		);
		$this->assertArrayNotHasKey('Access-Control-Allow-Origin', $response->getHeaders());
	}//end testWithoutTheEngineItAnswersDegraded()

	/**
	 * A check that crashes is answered as degraded, not as an error.
	 *
	 * @return void
	 */
	public function testACrashingCheckAnswersDegraded(): void {
		$response = $this->controller([
			self::LOADER => $this->loader(cors: true),
			self::EXECUTOR => $this->executor(null),
		], installed: '')->index();

		$this->assertSame(200, $response->getStatus());
		$this->assertSame('degraded', $response->getData()['status']);
		$this->assertSame('', $response->getData()['version']);
		$this->assertArrayNotHasKey('Access-Control-Allow-Origin', $response->getHeaders());
	}//end testACrashingCheckAnswersDegraded()
}//end class
