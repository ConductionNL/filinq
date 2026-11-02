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
 * @spec openspec/changes/archive/2026-09-29-anonymisation-fails-closed-without-a-detector/tasks.md#task-1
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\AnonymiserBackendStateClient;
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
	 * The state a caller reads is the state OpenRegister holds.
	 *
	 * @return void
	 */
	public function testReadsTheStateOpenRegisterHolds(): void {
		$client = DetectionStates::clientOver(
			DetectionStates::orState(enabled: true, active: 'presidio', effective: 'presidio', available: ['presidio' => true])
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
		$state = DetectionStates::clientOver(null)->getState();

		$this->assertFalse($state['known']);
		$this->assertNull($state['effectiveMethod']);
		$this->assertNotContains('regex', [$state['activeMethod'], $state['effectiveMethod']]);
		$this->assertSame(
			AnonymiserBackendStateClient::REFUSE_UNKNOWN,
			DetectionStates::clientOver(null)->refusalReason($state)
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
		$client = DetectionStates::clientOver(DetectionStates::orState(enabled: false, active: 'regex', effective: 'regex'));

		$this->assertSame(AnonymiserBackendStateClient::REFUSE_DISABLED, $client->refusalReason($client->getState()));

	}//end testDisabledRecognitionIsRefused()

	/**
	 * An effective backend whose probe says unavailable is refused.
	 *
	 * @return void
	 */
	public function testAnUnavailableEffectiveBackendIsRefused(): void {
		$client = DetectionStates::clientOver(
			DetectionStates::orState(enabled: true, active: 'hybrid', effective: 'hybrid', available: ['regex' => true, 'hybrid' => false])
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
		$client = DetectionStates::clientOver(DetectionStates::orState(enabled: true, active: 'presidio', effective: 'regex'));
		$state = $client->getState();

		$this->assertSame('regex', $state['effectiveMethod']);
		$this->assertNull($client->refusalReason($state));

	}//end testARegexFallbackIsALiveDetector()
}//end class
