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

}//end class
