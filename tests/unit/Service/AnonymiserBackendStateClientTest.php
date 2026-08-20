<?php

/**
 * Unit tests for AnonymiserBackendStateClient
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/anonymiser-backend-warning/tasks.md#task-4
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Tests\Unit\Service;

use OCA\DocuDesk\Service\AnonymiserBackendStateClient;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Unit tests for AnonymiserBackendStateClient
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://conduction.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class AnonymiserBackendStateClientTest extends TestCase {

	/**
	 * @var ContainerInterface|MockObject
	 */
	private ContainerInterface|MockObject $mockContainer;

	/**
	 * @var LoggerInterface|MockObject
	 */
	private LoggerInterface|MockObject $mockLogger;

	/**
	 * @var AnonymiserBackendStateClient
	 */
	private AnonymiserBackendStateClient $client;

	/**
	 * Set up test environment.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->mockContainer = $this->createMock(ContainerInterface::class);
		$this->mockLogger = $this->createMock(LoggerInterface::class);

		$this->client = new AnonymiserBackendStateClient(
			container: $this->mockContainer,
			logger: $this->mockLogger,
		);

	}//end setUp()

	/**
	 * Test that getState() returns `regex` defaults when OR service is unavailable.
	 *
	 * Covers: spec scenario "state query returns regex when OR companion not deployed".
	 *
	 * @return void
	 *
	 * @spec openspec/changes/anonymiser-backend-warning/tasks.md#task-4
	 */
	public function testGetStateReturnsDefaultsWhenServiceUnavailable(): void {
		$this->mockContainer
			->method('get')
			->willThrowException(new RuntimeException('Service not found'));

		$this->mockLogger->expects($this->once())
			->method('debug');

		$state = $this->client->getState();

		$this->assertSame('regex', $state['method']);
		$this->assertFalse($state['appApiInstalled']);

	}//end testGetStateReturnsDefaultsWhenServiceUnavailable()

	/**
	 * Test that getState() delegates to the OR service when available.
	 *
	 * Covers: spec scenario "state query returns openanonymiser → banner suppressed".
	 *
	 * @return void
	 *
	 * @spec openspec/changes/anonymiser-backend-warning/tasks.md#task-4
	 */
	public function testGetStateDelegatesToOpenRegisterService(): void {
		$expected = ['method' => 'openanonymiser', 'appApiInstalled' => true];

		$orService = $this->createMock(\stdClass::class);
		// PHPUnit stdClass mock does not allow arbitrary method expectations;
		// use an anonymous class that behaves like the OR service instead.
		$orServiceLike = new class($expected) {
			public function __construct(
				private readonly array $state,
			) {
			}

			public function getState(): array {
				return $this->state;
			}
		};

		$this->mockContainer
			->expects($this->once())
			->method('get')
			->with('OCA\OpenRegister\Service\AnonymisationBackendService')
			->willReturn($orServiceLike);

		$state = $this->client->getState();

		$this->assertSame('openanonymiser', $state['method']);
		$this->assertTrue($state['appApiInstalled']);

	}//end testGetStateDelegatesToOpenRegisterService()

	/**
	 * Test that getState() returns `regex` defaults when method key is absent.
	 *
	 * Defensive: OR service exists but returns unexpected shape.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/anonymiser-backend-warning/tasks.md#task-4
	 */
	public function testGetStateHandlesRegexMethodFromOrService(): void {
		$orServiceLike = new class {
			public function getState(): array {
				return ['method' => 'regex', 'appApiInstalled' => false];
			}
		};

		$this->mockContainer
			->method('get')
			->willReturn($orServiceLike);

		$state = $this->client->getState();

		$this->assertSame('regex', $state['method']);
		$this->assertFalse($state['appApiInstalled']);

	}//end testGetStateHandlesRegexMethodFromOrService()

	/**
	 * Test source file exists.
	 *
	 * @return void
	 */
	public function testSourceFileExists(): void {
		$this->assertFileExists(
			__DIR__ . '/../../../lib/Service/AnonymiserBackendStateClient.php'
		);

	}//end testSourceFileExists()
}//end class
