<?php

/**
 * Characterization tests for SettingsController::index() and the failure answers of create() and load()
 *
 * Pins the settings payload, that isAdmin and the warning dismissal are read
 * for the session user only, the dismissal rule, and the logged 500 of every
 * endpoint. Request values naming another user change nothing.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/admin-settings/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use Exception;
use OCA\Filinq\Controller\SettingsController;
use OCA\Filinq\Kernel\Contracts\AnonymiserBackendStateClientInterface;
use OCA\Filinq\Kernel\Contracts\SettingsServiceInterface;
use OCP\App\IAppManager;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Characterization of the settings read endpoint.
 *
 * @covers \OCA\Filinq\Controller\SettingsController
 */
class SettingsControllerIndexTraceTest extends TestCase {

	private const FORGED = ['userId' => 'root', 'requestedBy' => 'root', 'owner' => 'root'];

	private SettingsServiceInterface&MockObject $settings;

	private LoggerInterface&MockObject $logger;

	/**
	 * @var array<string, string> The dismissal stored per user.
	 */
	private array $dismissedBy = ['alice' => '1', 'bob' => '0', 'root' => '1'];

	/**
	 * @var array<int, string> The users isAdmin() and getUserValue() were asked about.
	 */
	private array $asked = [];

	private ?string $warning = 'regex';

	private bool $libresign = true;

	/**
	 * Fresh doubles: only 'root' is an admin; alice dismissed the warning, bob did not.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->settings = $this->createMock(SettingsServiceInterface::class);
		$this->settings->method('getAllSettings')->willReturn(['register' => 'r-1', 'isAdmin' => 'from-store']);
		$this->logger = $this->createMock(LoggerInterface::class);

	}//end setUp()

	/**
	 * The payload for a non-admin who dismissed the regex warning, with forged identity values.
	 *
	 * @return void
	 */
	public function testPayloadForASignedInUser(): void {
		$response = $this->controller(uid: 'alice')->index();

		$this->assertSame(200, $response->getStatus());
		$this->assertSame(
			[
				'register' => 'r-1',
				'isAdmin' => false,
				'openRegisters' => true,
				'libresignAvailable' => true,
				'anonymiserBackend' => [
					'state' => 'enabled',
					'warning' => 'regex',
					'warningDismissed' => true,
					'showWarning' => false,
					'appApiInstalled' => false,
				],
			],
			$response->getData()
		);
		$this->assertSame(['admin:alice', 'value:alice'], $this->asked);

	}//end testPayloadForASignedInUser()

	/**
	 * The admin flag and the dismissal follow the session user, whatever the request names.
	 *
	 * @return void
	 */
	public function testFlagsFollowTheSessionUser(): void {
		$root = $this->controller(uid: 'root')->index()->getData();
		$this->assertTrue($root['isAdmin']);

		$bob = $this->controller(uid: 'bob')->index()->getData();
		$this->assertFalse($bob['isAdmin']);
		$this->assertFalse($bob['anonymiserBackend']['warningDismissed']);
		$this->assertTrue($bob['anonymiserBackend']['showWarning']);

		$this->assertSame(['admin:root', 'value:root', 'admin:bob', 'value:bob'], $this->asked);

	}//end testFlagsFollowTheSessionUser()

	/**
	 * A dismissal hides only the regex warning; no warning is never shown.
	 *
	 * @return void
	 */
	public function testDismissalHidesOnlyTheRegexWarning(): void {
		$this->warning = 'disabled';
		$data = $this->controller(uid: 'alice')->index()->getData();
		$this->assertSame('disabled', $data['anonymiserBackend']['warning']);
		$this->assertTrue($data['anonymiserBackend']['showWarning']);

		$this->warning = null;
		$data = $this->controller(uid: 'bob')->index()->getData();
		$this->assertNull($data['anonymiserBackend']['warning']);
		$this->assertFalse($data['anonymiserBackend']['showWarning']);

	}//end testDismissalHidesOnlyTheRegexWarning()

	/**
	 * A user with no stored dismissal sees the warning, and LibreSign follows the app manager.
	 *
	 * @return void
	 */
	public function testNoStoredDismissalAndNoLibreSign(): void {
		$this->libresign = false;
		$data = $this->controller(uid: 'carol')->index()->getData();

		$this->assertFalse($data['libresignAvailable']);
		$this->assertFalse($data['anonymiserBackend']['warningDismissed']);
		$this->assertTrue($data['anonymiserBackend']['showWarning']);

	}//end testNoStoredDismissalAndNoLibreSign()

	/**
	 * Without a session user nobody is asked about and nothing is dismissed.
	 *
	 * @return void
	 */
	public function testAnonymousReader(): void {
		$data = $this->controller(uid: null, installed: ['files'])->index()->getData();

		$this->assertFalse($data['isAdmin']);
		$this->assertFalse($data['openRegisters']);
		$this->assertFalse($data['anonymiserBackend']['warningDismissed']);
		$this->assertTrue($data['anonymiserBackend']['showWarning']);
		$this->assertSame([], $this->asked);

	}//end testAnonymousReader()

	/**
	 * A failing read is logged with its message and answered 500 with it.
	 *
	 * @return void
	 */
	public function testIndexFailureAnswers500(): void {
		$settings = $this->createMock(SettingsServiceInterface::class);
		$settings->method('getAllSettings')->willThrowException(new Exception('store down'));
		$this->settings = $settings;
		$this->logger->expects($this->once())->method('error')->with(
			$this->identicalTo('Failed to retrieve settings'),
			$this->identicalTo(['exception' => 'store down'])
		);

		$response = $this->controller(uid: 'alice')->index();

		$this->assertSame(500, $response->getStatus());
		$this->assertSame(['error' => 'store down'], $response->getData());

	}//end testIndexFailureAnswers500()

	/**
	 * A failing write is logged and answered 500 with its message.
	 *
	 * @return void
	 */
	public function testCreateFailureAnswers500(): void {
		$this->settings->method('updateSettings')->willThrowException(new Exception('write refused'));
		$this->logger->expects($this->once())->method('error')->with(
			$this->identicalTo('Failed to update settings'),
			$this->identicalTo(['exception' => 'write refused'])
		);

		$response = $this->controller(uid: 'root')->create();

		$this->assertSame(500, $response->getStatus());
		$this->assertSame(['error' => 'write refused'], $response->getData());

	}//end testCreateFailureAnswers500()

	/**
	 * A failing load is logged and answered 500 with its message.
	 *
	 * @return void
	 */
	public function testLoadFailureAnswers500(): void {
		$this->settings->method('initialize')->willThrowException(new Exception('json broken'));
		$this->logger->expects($this->once())->method('error')->with(
			$this->identicalTo('Failed to load register configuration'),
			$this->identicalTo(['exception' => 'json broken'])
		);

		$response = $this->controller(uid: 'root')->load();

		$this->assertSame(500, $response->getStatus());
		$this->assertSame(['error' => 'json broken'], $response->getData());

	}//end testLoadFailureAnswers500()

	/**
	 * The controller over a request carrying forged identity values through getParams() and getParam().
	 *
	 * @param string|null        $uid       The session user, or null for none.
	 * @param array<int, string> $installed The installed apps.
	 *
	 * @return SettingsController The controller.
	 */
	private function controller(?string $uid, array $installed = ['files', 'openregister']): SettingsController {
		$request = $this->createMock(IRequest::class);
		$request->method('getParams')->willReturn(self::FORGED);
		$request->method('getParam')->willReturnCallback(
			fn (string $key, mixed $default = null): mixed => (self::FORGED[$key] ?? $default)
		);

		$session = $this->createMock(IUserSession::class);
		$user = null;
		if ($uid !== null) {
			$user = $this->createMock(IUser::class);
			$user->method('getUID')->willReturn($uid);
		}

		$session->method('getUser')->willReturn($user);

		$groups = $this->createMock(IGroupManager::class);
		$groups->method('isAdmin')->willReturnCallback(
			function (string $userId): bool {
				$this->asked[] = 'admin:' . $userId;
				return $userId === 'root';
			}
		);

		$config = $this->createMock(IConfig::class);
		$config->method('getUserValue')->willReturnCallback(
			function (string $userId, string $appName, string $key, mixed $default = ''): string {
				$this->asked[] = 'value:' . $userId;
				if ($appName !== 'filinq' || $key !== 'anonymiser_warning_dismissed' || $default !== '') {
					return 'wrong lookup';
				}

				return $this->dismissedBy[$userId] ?? '';
			}
		);

		$apps = $this->createMock(IAppManager::class);
		$apps->method('getInstalledApps')->willReturn($installed);
		$apps->method('isEnabledForAnyone')->willReturnCallback(fn (string $app): bool => ($app === 'libresign' && $this->libresign));
		$apps->method('isInstalled')->willReturnCallback(fn (string $app): bool => $app === 'other');

		$client = $this->createMock(AnonymiserBackendStateClientInterface::class);
		$client->method('getState')->willReturn(['state' => 'enabled']);
		$client->method('warningFor')->willReturnCallback(fn (array $state): ?string => ($state === ['state' => 'enabled'] ? $this->warning : 'wrong state'));

		return new SettingsController(
			appName: 'filinq',
			request: $request,
			appManager: $apps,
			groupManager: $groups,
			userSession: $session,
			logger: $this->logger,
			settingsService: $this->settings,
			anonymiserClient: $client,
			config: $config,
		);
	}//end controller()
}//end class
