<?php

/**
 * Unit tests for OpenRegisterAvailabilityService
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2025 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\OpenRegisterAvailabilityService;
use OCP\App\IAppManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;

/**
 * Unit tests for OpenRegisterAvailabilityService
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class OpenRegisterAvailabilityServiceTest extends TestCase {

	/**
	 * @var OpenRegisterAvailabilityService
	 */
	private OpenRegisterAvailabilityService $service;

	/**
	 * @var IAppManager|MockObject
	 */
	private IAppManager|MockObject $mockAppManager;

	/**
	 * @var ContainerInterface|MockObject
	 */
	private ContainerInterface|MockObject $mockContainer;

	/**
	 * Set up test environment
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->mockAppManager = $this->createMock(IAppManager::class);
		$this->mockContainer = $this->createMock(ContainerInterface::class);

		$this->service = new OpenRegisterAvailabilityService(
			$this->mockAppManager,
			$this->mockContainer
		);

	}//end setUp()

	/**
	 * Test isInstalled returns false when the app is absent
	 *
	 * @return void
	 *
	 * @spec openspec/specs/admin-settings/spec.md
	 */
	public function testIsInstalledReturnsFalseWhenAppAbsent(): void {
		$this->mockAppManager->method('isInstalled')
			->willReturn(false);

		$this->assertFalse($this->service->isInstalled());

	}//end testIsInstalledReturnsFalseWhenAppAbsent()

	/**
	 * Test isInstalled returns false when the installed version is too old
	 *
	 * @return void
	 *
	 * @spec openspec/specs/admin-settings/spec.md
	 */
	public function testIsInstalledReturnsFalseWhenVersionTooOld(): void {
		$this->mockAppManager->method('isInstalled')
			->willReturn(true);
		$this->mockAppManager->method('getAppVersion')
			->willReturn('0.0.1');

		$this->assertFalse($this->service->isInstalled());

	}//end testIsInstalledReturnsFalseWhenVersionTooOld()

	/**
	 * Test isInstalled returns true when the installed version is new enough
	 *
	 * @return void
	 *
	 * @spec openspec/specs/admin-settings/spec.md
	 */
	public function testIsInstalledReturnsTrueWhenVersionSatisfied(): void {
		$this->mockAppManager->method('isInstalled')
			->willReturn(true);
		$this->mockAppManager->method('getAppVersion')
			->willReturn('999.0.0');

		$this->assertTrue($this->service->isInstalled());

	}//end testIsInstalledReturnsTrueWhenVersionSatisfied()

	/**
	 * Test getMinVersion resolves a non-empty semantic version
	 *
	 * @return void
	 *
	 * @spec openspec/specs/admin-settings/spec.md
	 */
	public function testGetMinVersionResolvesAVersion(): void {
		$first = $this->service->getMinVersion();

		$this->assertNotSame('', $first);
		// Memoised: a second call returns the identical value.
		$this->assertSame($first, $this->service->getMinVersion());

	}//end testGetMinVersionResolvesAVersion()

	/**
	 * Test getObjectService throws when OpenRegister is not installed
	 *
	 * @return void
	 *
	 * @spec openspec/specs/admin-settings/spec.md
	 */
	public function testGetObjectServiceThrowsWhenNotInstalled(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('OpenRegister service is not available');

		$this->mockAppManager->method('getInstalledApps')
			->willReturn([]);

		$this->service->getObjectService();

	}//end testGetObjectServiceThrowsWhenNotInstalled()


	/**
	 * The transition engine is answered only when OpenRegister is installed and can transition on an app's behalf.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/signing-cancellation/specs/signing-cancellation/spec.md#requirement-a-cancelled-request-must-be-visibly-cancelled-and-must-not-be-re-signable
	 */
	public function testGetTransitionEngineAnswersOnlyAUsableEngine(): void {
		$engine = new class {
			/**
			 * @param string $objectId Unused.
			 * @param string $action   Unused.
			 * @param string $app      Unused.
			 *
			 * @return null
			 */
			public function transitionAsSystem(string $objectId, string $action, string $app) {
				return null;
			}
		};

		$this->mockAppManager->method('getInstalledApps')->willReturn(['openregister']);
		$this->mockContainer->expects($this->once())
			->method('get')
			->with(OpenRegisterAvailabilityService::TRANSITION_ENGINE)
			->willReturn($engine);

		$this->assertSame($engine, $this->service->getTransitionEngine());

	}//end testGetTransitionEngineAnswersOnlyAUsableEngine()

	/**
	 * Without OpenRegister, or with one that has no usable engine, there is none.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/signing-cancellation/specs/signing-cancellation/spec.md#requirement-a-cancelled-request-must-be-visibly-cancelled-and-must-not-be-re-signable
	 */
	public function testGetTransitionEngineIsNullWithoutAUsableEngine(): void {
		$appManager = $this->createMock(IAppManager::class);
		$appManager->method('getInstalledApps')->willReturn(['files']);
		$container = $this->createMock(ContainerInterface::class);
		$container->expects($this->never())->method('get');
		$this->assertNull((new OpenRegisterAvailabilityService($appManager, $container))->getTransitionEngine());

		$this->mockAppManager->method('getInstalledApps')->willReturn(['openregister']);
		$this->mockContainer->method('get')->willReturnOnConsecutiveCalls(new \stdClass());
		$this->assertNull($this->service->getTransitionEngine(), 'an engine without transitionAsSystem is not used');

		$failing = $this->createMock(ContainerInterface::class);
		$failing->method('get')->willThrowException(new \RuntimeException('not registered'));
		$this->assertNull((new OpenRegisterAvailabilityService($this->mockAppManager, $failing))->getTransitionEngine());

	}//end testGetTransitionEngineIsNullWithoutAUsableEngine()
}//end class
