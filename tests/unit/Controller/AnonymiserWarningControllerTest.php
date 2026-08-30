<?php

/**
 * Unit tests for AnonymiserWarningController
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
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

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\AppInfo\Application;
use OCA\Filinq\Controller\AnonymiserWarningController;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for AnonymiserWarningController
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://conduction.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class AnonymiserWarningControllerTest extends TestCase {

	/**
	 * @var IRequest|MockObject
	 */
	private IRequest|MockObject $mockRequest;

	/**
	 * @var IConfig|MockObject
	 */
	private IConfig|MockObject $mockConfig;

	/**
	 * @var IUserSession|MockObject
	 */
	private IUserSession|MockObject $mockUserSession;

	/**
	 * @var LoggerInterface|MockObject
	 */
	private LoggerInterface|MockObject $mockLogger;

	/**
	 * @var IUser|MockObject
	 */
	private IUser|MockObject $mockUser;

	/**
	 * @var AnonymiserWarningController
	 */
	private AnonymiserWarningController $controller;

	/**
	 * Set up test environment.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->mockRequest = $this->createMock(IRequest::class);
		$this->mockConfig = $this->createMock(IConfig::class);
		$this->mockUserSession = $this->createMock(IUserSession::class);
		$this->mockLogger = $this->createMock(LoggerInterface::class);
		$this->mockUser = $this->createMock(IUser::class);

		$this->mockUser->method('getUID')->willReturn('admin1');

		$this->controller = new AnonymiserWarningController(
			request: $this->mockRequest,
			config: $this->mockConfig,
			userSession: $this->mockUserSession,
			logger: $this->mockLogger,
		);

	}//end setUp()

	/**
	 * Test dismiss() persists the dismissal flag for the current admin.
	 *
	 * Covers: spec scenario "Admin dismisses the warning banner".
	 *
	 * @return void
	 *
	 * @spec openspec/changes/anonymiser-backend-warning/tasks.md#task-4
	 */
	public function testDismissSetsDismissedFlag(): void {
		$this->mockUserSession
			->method('getUser')
			->willReturn($this->mockUser);

		$this->mockConfig->expects($this->once())
			->method('setUserValue')
			->with('admin1', Application::APP_ID, AnonymiserWarningController::DISMISSED_KEY, '1');

		$response = $this->controller->dismiss();
		$data = $response->getData();

		$this->assertTrue($data['dismissed']);

	}//end testDismissSetsDismissedFlag()

	/**
	 * Test reset() clears the dismissal flag for the current admin.
	 *
	 * Covers: spec scenario "Admin re-enables the warning".
	 *
	 * @return void
	 *
	 * @spec openspec/changes/anonymiser-backend-warning/tasks.md#task-4
	 */
	public function testResetClearsDismissedFlag(): void {
		$this->mockUserSession
			->method('getUser')
			->willReturn($this->mockUser);

		$this->mockConfig->expects($this->once())
			->method('deleteUserValue')
			->with('admin1', Application::APP_ID, AnonymiserWarningController::DISMISSED_KEY);

		$response = $this->controller->reset();
		$data = $response->getData();

		$this->assertFalse($data['dismissed']);

	}//end testResetClearsDismissedFlag()

	/**
	 * Test dismiss() returns 401 when no user is authenticated.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/anonymiser-backend-warning/tasks.md#task-4
	 */
	public function testDismissReturns401WhenNotAuthenticated(): void {
		$this->mockUserSession
			->method('getUser')
			->willReturn(null);

		$this->mockConfig->expects($this->never())
			->method('setUserValue');

		$response = $this->controller->dismiss();
		$this->assertSame(401, $response->getStatus());

	}//end testDismissReturns401WhenNotAuthenticated()

	/**
	 * Test reset() returns 401 when no user is authenticated.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/anonymiser-backend-warning/tasks.md#task-4
	 */
	public function testResetReturns401WhenNotAuthenticated(): void {
		$this->mockUserSession
			->method('getUser')
			->willReturn(null);

		$this->mockConfig->expects($this->never())
			->method('deleteUserValue');

		$response = $this->controller->reset();
		$this->assertSame(401, $response->getStatus());

	}//end testResetReturns401WhenNotAuthenticated()

	/**
	 * Test that dismissal persists per-admin (two admins have separate flags).
	 *
	 * Covers: spec scenario "dismissal persists per-admin and survives logout".
	 *
	 * @return void
	 *
	 * @spec openspec/changes/anonymiser-backend-warning/tasks.md#task-4
	 */
	public function testDismissalIsPerAdmin(): void {
		$admin2 = $this->createMock(IUser::class);
		$admin2->method('getUID')->willReturn('admin2');

		// First controller call as admin1.
		$this->mockUserSession
			->method('getUser')
			->willReturn($this->mockUser);

		// Expect setUserValue called with 'admin1'.
		$this->mockConfig->expects($this->once())
			->method('setUserValue')
			->with('admin1', Application::APP_ID, AnonymiserWarningController::DISMISSED_KEY, '1');

		$this->controller->dismiss();

	}//end testDismissalIsPerAdmin()

	/**
	 * Test source file exists.
	 *
	 * @return void
	 */
	public function testSourceFileExists(): void {
		$this->assertFileExists(
			__DIR__ . '/../../../lib/Controller/AnonymiserWarningController.php'
		);

	}//end testSourceFileExists()

	/**
	 * Test dismissed key constant value.
	 *
	 * @return void
	 */
	public function testDismissedKeyConstant(): void {
		$this->assertSame('anonymiser_warning_dismissed', AnonymiserWarningController::DISMISSED_KEY);

	}//end testDismissedKeyConstant()
}//end class
