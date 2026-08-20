<?php

/**
 * Unit tests for SettingsController
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Controller
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2025 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.DocuDesk.app
 */

namespace OCA\DocuDesk\Tests\Unit\Controller;

use OCA\DocuDesk\Controller\SettingsController;
use OCA\DocuDesk\Service\AnonymiserBackendStateClient;
use OCA\DocuDesk\Service\SettingsService;
use OCP\App\IAppManager;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Unit tests for SettingsController
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class SettingsControllerTest extends TestCase {

	/**
	 * Test source file exists
	 *
	 * @return void
	 */
	public function testSourceFileExists(): void {
		$this->assertFileExists(
			__DIR__ . '/../../../lib/Controller/SettingsController.php'
		);

	}//end testSourceFileExists()

	/**
	 * Test file contains class declaration
	 *
	 * @return void
	 */
	public function testFileContainsClassDeclaration(): void {
		$content = file_get_contents(__DIR__ . '/../../../lib/Controller/SettingsController.php');
		$this->assertStringContainsString('class SettingsController', $content);

	}//end testFileContainsClassDeclaration()

	/**
	 * Test file contains index method
	 *
	 * @return void
	 */
	public function testFileContainsIndexMethod(): void {
		$content = file_get_contents(__DIR__ . '/../../../lib/Controller/SettingsController.php');
		$this->assertStringContainsString('function index()', $content);

	}//end testFileContainsIndexMethod()

	/**
	 * Test file contains create method
	 *
	 * @return void
	 */
	public function testFileContainsCreateMethod(): void {
		$content = file_get_contents(__DIR__ . '/../../../lib/Controller/SettingsController.php');
		$this->assertStringContainsString('function create()', $content);

	}//end testFileContainsCreateMethod()

	/**
	 * `settings#update` (PUT /api/settings) is part of the canonical AppHost
	 * route table that `Routes::standard()` ships for EVERY app. This class had
	 * no `update()`, so the route resolved here and blew up on dispatch. It is
	 * the same admin-guarded write path as `create()`.
	 *
	 * @return void
	 */
	public function testUpdateWritesSettingsForAnAdmin(): void {
		$settingsService = $this->createMock(SettingsService::class);
		$settingsService->expects($this->once())
			->method('updateSettings')
			->willReturn(['grondslag' => 'AVG-6.1.c']);

		$response = $this->controller(settingsService: $settingsService)->update();

		$this->assertSame(200, $response->getStatus());
		$this->assertSame(['grondslag' => 'AVG-6.1.c'], $response->getData());

	}//end testUpdateWritesSettingsForAnAdmin()

	/**
	 * `settings#load` (POST /api/settings/load) — likewise routed for every app
	 * and likewise missing here. Re-runs register/schema initialisation.
	 *
	 * @return void
	 */
	public function testLoadReinitialisesTheRegisterForAnAdmin(): void {
		$settingsService = $this->createMock(SettingsService::class);
		$settingsService->expects($this->once())
			->method('initialize')
			->willReturn(['success' => true]);

		$response = $this->controller(settingsService: $settingsService)->load();

		$this->assertSame(200, $response->getStatus());
		$this->assertSame(['success' => true], $response->getData());

	}//end testLoadReinitialisesTheRegisterForAnAdmin()

	/**
	 * A non-admin gets 403 and NOTHING is initialised — the body gate, not just
	 * the attribute, has to hold.
	 *
	 * @return void
	 */
	public function testLoadIsRefusedForANonAdmin(): void {
		$settingsService = $this->createMock(SettingsService::class);
		$settingsService->expects($this->never())->method('initialize');

		$response = $this->controller(settingsService: $settingsService, isAdmin: false)->load();

		$this->assertSame(403, $response->getStatus());

	}//end testLoadIsRefusedForANonAdmin()

	/**
	 * With no session at all the answer is 401, and nothing is initialised.
	 *
	 * @return void
	 */
	public function testLoadIsRefusedWithoutASession(): void {
		$settingsService = $this->createMock(SettingsService::class);
		$settingsService->expects($this->never())->method('initialize');

		$response = $this->controller(settingsService: $settingsService, user: null)->load();

		$this->assertSame(401, $response->getStatus());

	}//end testLoadIsRefusedWithoutASession()

	/**
	 * Build a SettingsController over doubles.
	 *
	 * @param SettingsService $settingsService The settings service double.
	 * @param bool $isAdmin Whether the acting user is an admin.
	 * @param string|null $user UID of the acting user, or null for no session.
	 *
	 * @return SettingsController The controller under test.
	 */
	private function controller(
		SettingsService $settingsService,
		bool $isAdmin = true,
		?string $user = 'alice',
	): SettingsController {
		$userSession = $this->createMock(IUserSession::class);
		if ($user === null) {
			$userSession->method('getUser')->willReturn(null);
		} else {
			$actingUser = $this->createMock(IUser::class);
			$actingUser->method('getUID')->willReturn($user);
			$userSession->method('getUser')->willReturn($actingUser);
		}

		$groupManager = $this->createMock(IGroupManager::class);
		$groupManager->method('isAdmin')->willReturn($isAdmin);

		$request = $this->createMock(IRequest::class);
		$request->method('getParams')->willReturn(['grondslag' => 'AVG-6.1.c']);

		return new SettingsController(
			'docudesk',
			$request,
			$this->createMock(IAppManager::class),
			$groupManager,
			$userSession,
			new NullLogger(),
			$settingsService,
			$this->createMock(AnonymiserBackendStateClient::class),
			$this->createMock(IConfig::class)
		);

	}//end controller()

}//end class
