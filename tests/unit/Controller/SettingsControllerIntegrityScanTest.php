<?php

/**
 * Unit tests for the integrity scan endpoints of SettingsController.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/whole-collection-integrity-scan/tasks.md#task-3-the-admin-section-reads-and-starts-a-scan
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\BackgroundJob\IntegrityScanJob;
use OCA\Filinq\Controller\SettingsController;
use OCA\Filinq\Controller\Support\AppConfiguration;
use OCA\Filinq\Controller\Support\SettingsBackends;
use OCA\Filinq\Kernel\Contracts\AnonymiserBackendStateClientInterface;
use OCA\Filinq\Kernel\Contracts\SettingsServiceInterface;
use OCA\Filinq\Service\IntegrityScanService;
use OCP\App\IAppManager;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;

/**
 * @covers \OCA\Filinq\Controller\SettingsController
 * @uses   \OCA\Filinq\Controller\Support\AppConfiguration
 * @uses   \OCA\Filinq\Controller\Support\SettingsBackends
 */
class SettingsControllerIntegrityScanTest extends TestCase {

	/**
	 * The scan double.
	 *
	 * @var IntegrityScanService&MockObject
	 */
	private IntegrityScanService $scan;

	/**
	 * The notifier double.
	 *
	 * @var IntegrityScanJob&MockObject
	 */
	private IntegrityScanJob $notices;

	/**
	 * Fresh doubles per test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->scan    = $this->createMock(IntegrityScanService::class);
		$this->notices = $this->createMock(IntegrityScanJob::class);
	}//end setUp()

	/**
	 * The controller for a session user, or for no session.
	 *
	 * @param string|null $user    The session user's uid, or null for no session.
	 * @param bool        $isAdmin Whether that user is an administrator.
	 *
	 * @return SettingsController
	 */
	private function controller(?string $user, bool $isAdmin): SettingsController {
		$session = $this->createMock(IUserSession::class);
		if ($user === null) {
			$session->method('getUser')->willReturn(null);
		} else {
			$acting = $this->createMock(IUser::class);
			$acting->method('getUID')->willReturn($user);
			$session->method('getUser')->willReturn($acting);
		}

		$groups = $this->createMock(IGroupManager::class);
		$groups->method('isAdmin')->willReturnCallback(static fn (string $uid): bool => $isAdmin && $uid === $user);

		return new SettingsController(
			$this->createMock(IRequest::class),
			new AppConfiguration(apps: $this->createMock(IAppManager::class), config: $this->createMock(IConfig::class)),
			$groups,
			$session,
			new NullLogger(),
			new SettingsBackends(
				settings: $this->createMock(SettingsServiceInterface::class),
				anonymiser: $this->createMock(AnonymiserBackendStateClientInterface::class),
				integrityScan: $this->scan,
				integrityNotices: $this->notices
			)
		);
	}//end controller()

	/**
	 * A run that found two missing files.
	 *
	 * @return array<string, mixed>
	 */
	private static function twoMissing(): array {
		return [
			'trigger' => 'nightly',
			'startedAt' => '2026-11-11T02:00:00+00:00',
			'finishedAt' => '2026-11-11T02:00:44+00:00',
			'checked' => 415,
			'missing' => 2,
			'changed' => 0,
			'orphaned' => 0,
			'findings' => [
				['kind' => 'missing', 'schema' => 'dossier', 'record' => 'd-301', 'name' => 'Lindelaan 12', 'fileId' => 9301],
				['kind' => 'missing', 'schema' => 'dossier', 'record' => 'd-301', 'name' => 'Lindelaan 12', 'fileId' => 9302],
			],
		];
	}//end twoMissing()

	/**
	 * A non-administrator who posts to the start endpoint gets 403 and no scan runs.
	 *
	 * @return void
	 */
	public function testANonAdministratorCannotStartAScan(): void {
		$this->scan->expects($this->never())->method('run');
		$this->notices->expects($this->never())->method('notifyAdministrators');

		$response = $this->controller(user: 'bob', isAdmin: false)->integrityScanStart();

		$this->assertSame(403, $response->getStatus());
		$this->assertSame(['error' => 'Admin privileges required'], $response->getData());
	}//end testANonAdministratorCannotStartAScan()

	/**
	 * Without a session neither endpoint scans or reads: 401.
	 *
	 * @return void
	 */
	public function testNoSessionIsRefusedOnBothEndpoints(): void {
		$this->scan->expects($this->never())->method('run');
		$this->scan->expects($this->never())->method('last');

		$controller = $this->controller(user: null, isAdmin: false);

		$this->assertSame(401, $controller->integrityScanStart()->getStatus());
		$this->assertSame(401, $controller->integrityScanLast()->getStatus());
	}//end testNoSessionIsRefusedOnBothEndpoints()

	/**
	 * A non-administrator cannot read the last run either.
	 *
	 * @return void
	 */
	public function testANonAdministratorCannotReadTheLastRun(): void {
		$this->scan->expects($this->never())->method('last');

		$this->assertSame(403, $this->controller(user: 'bob', isAdmin: false)->integrityScanLast()->getStatus());
	}//end testANonAdministratorCannotReadTheLastRun()

	/**
	 * An administrator reads the stored run as it is.
	 *
	 * @return void
	 */
	public function testAnAdministratorReadsTheLastRun(): void {
		$this->scan->expects($this->once())->method('last')->willReturn(self::twoMissing());

		$response = $this->controller(user: 'admin', isAdmin: true)->integrityScanLast();

		$this->assertSame(200, $response->getStatus());
		$this->assertSame(['run' => self::twoMissing()], $response->getData());
	}//end testAnAdministratorReadsTheLastRun()

	/**
	 * Before the first scan the answer is a null run, not an error.
	 *
	 * @return void
	 */
	public function testNoStoredRunAnswersNull(): void {
		$this->scan->method('last')->willReturn(null);

		$response = $this->controller(user: 'admin', isAdmin: true)->integrityScanLast();

		$this->assertSame(200, $response->getStatus());
		$this->assertSame(['run' => null], $response->getData());
	}//end testNoStoredRunAnswersNull()

	/**
	 * An administrator starts a scan: it runs as `admin`, the run is passed to the notifier and answered.
	 *
	 * @return void
	 */
	public function testAnAdministratorStartsAScanAndTheRunIsNotified(): void {
		$run = self::twoMissing();
		$run['trigger'] = 'admin';
		$this->scan->expects($this->once())->method('run')->with('admin')->willReturn($run);
		$this->notices->expects($this->once())->method('notifyAdministrators')->with($run)->willReturn(1);

		$response = $this->controller(user: 'admin', isAdmin: true)->integrityScanStart();

		$this->assertSame(200, $response->getStatus());
		$this->assertSame(['run' => $run], $response->getData());
	}//end testAnAdministratorStartsAScanAndTheRunIsNotified()

	/**
	 * A failing scan answers 500 with its message and notifies nobody.
	 *
	 * @return void
	 */
	public function testAFailingScanAnswers500(): void {
		$this->scan->method('run')->willThrowException(new RuntimeException('register unavailable'));
		$this->notices->expects($this->never())->method('notifyAdministrators');

		$response = $this->controller(user: 'admin', isAdmin: true)->integrityScanStart();

		$this->assertSame(500, $response->getStatus());
		$this->assertSame(['error' => 'register unavailable'], $response->getData());
	}//end testAFailingScanAnswers500()
}//end class
