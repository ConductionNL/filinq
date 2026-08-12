<?php

/**
 * Unit tests for SettingsInitializer
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2025 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.DocuDesk.app
 */

namespace OCA\DocuDesk\Tests\Unit\Service;

use OCA\DocuDesk\Service\SettingsInitializer;
use OCP\App\IAppManager;
use OCP\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for SettingsInitializer
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class SettingsInitializerTest extends TestCase {

	/**
	 * @var SettingsInitializer
	 */
	private SettingsInitializer $initializer;

	/**
	 * @var IAppConfig|MockObject
	 */
	private IAppConfig|MockObject $mockConfig;

	/**
	 * @var ContainerInterface|MockObject
	 */
	private ContainerInterface|MockObject $mockContainer;

	/**
	 * @var IAppManager|MockObject
	 */
	private IAppManager|MockObject $mockAppManager;

	/**
	 * @var LoggerInterface|MockObject
	 */
	private LoggerInterface|MockObject $mockLogger;

	/**
	 * Set up test environment
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->mockConfig = $this->createMock(IAppConfig::class);
		$this->mockContainer = $this->createMock(ContainerInterface::class);
		$this->mockAppManager = $this->createMock(IAppManager::class);
		$this->mockLogger = $this->createMock(LoggerInterface::class);

		$this->initializer = new SettingsInitializer(
			$this->mockConfig,
			$this->mockContainer,
			$this->mockAppManager,
			$this->mockLogger
		);

	}//end setUp()

	/**
	 * Test initialize returns error when OpenRegister not installed
	 *
	 * @return void
	 */
	public function testInitializeReturnsErrorWhenNotInstalled(): void {
		$this->mockAppManager->method('isInstalled')
			->willReturn(false);

		$result = $this->initializer->initialize();

		$this->assertFalse($result['configuration']);
		$this->assertNotEmpty($result['errors']);

	}//end testInitializeReturnsErrorWhenNotInstalled()

	/**
	 * Test initialize returns error when OpenRegister not enabled
	 *
	 * @return void
	 */
	public function testInitializeReturnsErrorWhenNotEnabled(): void {
		$this->mockAppManager->method('isInstalled')
			->willReturn(true);
		$this->mockAppManager->method('getAppVersion')
			->willReturn('1.0.0');
		$this->mockAppManager->method('isEnabledForUser')
			->willReturn(false);

		$result = $this->initializer->initialize();

		$this->assertFalse($result['configuration']);
		$this->assertNotEmpty($result['errors']);

	}//end testInitializeReturnsErrorWhenNotEnabled()

	/**
	 * Test initialize has expected result structure
	 *
	 * @return void
	 */
	public function testInitializeHasExpectedResultStructure(): void {
		$this->mockAppManager->method('isInstalled')
			->willReturn(false);

		$result = $this->initializer->initialize();

		$this->assertArrayHasKey('configuration', $result);
		$this->assertArrayHasKey('errors', $result);
		$this->assertArrayHasKey('info', $result);

	}//end testInitializeHasExpectedResultStructure()

}//end class
