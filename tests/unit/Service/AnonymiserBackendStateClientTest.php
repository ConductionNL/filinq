<?php

/**
 * Unit tests for AnonymiserBackendStateClient
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/anonymisation-fails-closed-without-a-detector/tasks.md#task-1
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\AnonymiserBackendStateClient;
use OCA\OpenRegister\Service\Anonymisation\BackendInfo;
use OCA\OpenRegister\Service\Anonymisation\BackendState;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;
use RuntimeException;

/**
 * The client reads OpenRegister's real backend state, at the class name
 * OpenRegister declares, in the shape OpenRegister returns, and says "unknown"
 * when it cannot, never "regex".
 */
class AnonymiserBackendStateClientTest extends TestCase {

	/**
	 * Build a client over a container that answers with the given OR service.
	 *
	 * @param BackendState|null $state The state OpenRegister holds, null for a
	 *                                 container that cannot resolve the service.
	 *
	 * @return AnonymiserBackendStateClient The client.
	 */
	public static function clientOver(?BackendState $state): AnonymiserBackendStateClient {
		$container = new class($state) implements ContainerInterface {
			public function __construct(
				private readonly ?BackendState $state,
			) {
			}

			public function get(string $id) {
				if ($id !== 'OCA\OpenRegister\Service\Anonymisation\AnonymisationBackendService' || $this->state === null) {
					throw new RuntimeException('No service registered under ' . $id);
				}

				return new class($this->state) {
					public function __construct(
						private readonly BackendState $state,
					) {
					}

					public function getState(): BackendState {
						return $this->state;
					}
				};
			}

			public function has(string $id): bool {
				return $this->state !== null;
			}
		};

		return new AnonymiserBackendStateClient(container: $container, logger: new NullLogger());

	}//end clientOver()

	/**
	 * A state as OpenRegister's AnonymisationBackendService builds it.
	 *
	 * @param bool $enabled Whether entity recognition is on.
	 * @param string $active The active method.
	 * @param string $effective The effective method.
	 * @param array<string, bool> $available Availability per method; regex is always true.
	 *
	 * @return BackendState The state.
	 */
	public static function orState(bool $enabled, string $active, string $effective, array $available = []): BackendState {
		$backends = [];
		foreach (['regex', 'presidio', 'openanonymiser', 'llm', 'hybrid'] as $method) {
			$isUp = ($available[$method] ?? ($method === 'regex'));
			$backends[$method] = new BackendInfo(
				name: $method,
				available: $isUp,
				configured: $isUp,
				lastProbedAt: '2026-09-29T08:00:00+00:00',
				latencyMs: 0
			);
		}

		return new BackendState(
			entityRecognitionEnabled: $enabled,
			activeMethod: $active,
			effectiveMethod: $effective,
			backends: $backends
		);

	}//end orState()

	/**
	 * The state a caller reads is the state OpenRegister holds.
	 *
	 * @return void
	 */
	public function testReadsTheStateOpenRegisterHolds(): void {
		$client = self::clientOver(
			self::orState(enabled: true, active: 'presidio', effective: 'presidio', available: ['presidio' => true])
		);

		$state = $client->getState();

		$this->assertTrue($state['known']);
		$this->assertTrue($state['entityRecognitionEnabled']);
		$this->assertSame('presidio', $state['activeMethod']);
		$this->assertSame('presidio', $state['effectiveMethod']);
		$this->assertTrue($state['effectiveAvailable']);
		$this->assertTrue($state['backends']['presidio']['available']);
		$this->assertNull($client->refusalReason($state));

	}//end testReadsTheStateOpenRegisterHolds()

	/**
	 * An unreachable service is unknown, not regex.
	 *
	 * @return void
	 */
	public function testAnUnreachableServiceIsUnknownNotRegex(): void {
		$state = self::clientOver(null)->getState();

		$this->assertFalse($state['known']);
		$this->assertNull($state['effectiveMethod']);
		$this->assertNotContains('regex', [$state['activeMethod'], $state['effectiveMethod']]);
		$this->assertSame(
			AnonymiserBackendStateClient::REFUSE_UNKNOWN,
			self::clientOver(null)->refusalReason($state)
		);

	}//end testAnUnreachableServiceIsUnknownNotRegex()

	/**
	 * A service that throws is unknown too.
	 *
	 * @return void
	 */
	public function testAServiceThatThrowsIsUnknown(): void {
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturn(
			new class {
				public function getState(): BackendState {
					throw new RuntimeException('settings table missing');
				}
			}
		);

		$client = new AnonymiserBackendStateClient(container: $container, logger: new NullLogger());

		$this->assertFalse($client->getState()['known']);

	}//end testAServiceThatThrowsIsUnknown()

	/**
	 * Recognition switched off is refused by name, although OpenRegister still
	 * reports regex as the effective method in that case.
	 *
	 * @return void
	 */
	public function testDisabledRecognitionIsRefused(): void {
		$client = self::clientOver(self::orState(enabled: false, active: 'regex', effective: 'regex'));

		$this->assertSame(AnonymiserBackendStateClient::REFUSE_DISABLED, $client->refusalReason($client->getState()));

	}//end testDisabledRecognitionIsRefused()

	/**
	 * An effective backend whose probe says unavailable is refused.
	 *
	 * @return void
	 */
	public function testAnUnavailableEffectiveBackendIsRefused(): void {
		$client = self::clientOver(
			self::orState(enabled: true, active: 'hybrid', effective: 'hybrid', available: ['regex' => true, 'hybrid' => false])
		);

		$this->assertSame(
			AnonymiserBackendStateClient::REFUSE_UNAVAILABLE,
			$client->refusalReason($client->getState())
		);

	}//end testAnUnavailableEffectiveBackendIsRefused()

	/**
	 * Regex that OpenRegister fell back to is a live detector: it runs.
	 *
	 * @return void
	 */
	public function testARegexFallbackIsALiveDetector(): void {
		$client = self::clientOver(self::orState(enabled: true, active: 'presidio', effective: 'regex'));
		$state = $client->getState();

		$this->assertSame('regex', $state['effectiveMethod']);
		$this->assertNull($client->refusalReason($state));

	}//end testARegexFallbackIsALiveDetector()
}//end class
