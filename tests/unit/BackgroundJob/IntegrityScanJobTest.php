<?php

/**
 * Unit tests for IntegrityScanJob.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\BackgroundJob
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/whole-collection-integrity-scan/tasks.md#task-2-nightly-job-occ-command-and-notification
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\BackgroundJob;

use OCA\Filinq\BackgroundJob\IntegrityScanJob;
use OCA\Filinq\Notification\IntegrityScanNotifier;
use OCA\Filinq\Service\IntegrityScanService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\Notification\IManager as INotificationManager;
use OCP\Notification\INotification;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use ReflectionMethod;
use RuntimeException;

/**
 * A run with findings tells every administrator once; a clean run tells nobody.
 *
 * @spec openspec/changes/whole-collection-integrity-scan/specs/document-versions/spec.md#requirement-the-scan-runs-nightly-and-on-demand-and-administrators-see-the-result-req-dis-02
 */
#[CoversClass(IntegrityScanJob::class)]
class IntegrityScanJobTest extends TestCase {

	/**
	 * Each notification sent: [user, subject, parameters].
	 *
	 * @var array<int, array{0: string, 1: string, 2: array<string, int>}>
	 */
	private array $sent = [];

	/**
	 * The triggers the scan was run with.
	 *
	 * @var list<string>
	 */
	private array $triggers = [];

	/**
	 * The job over a scan that answers this run, with two administrators.
	 *
	 * @param array<string, mixed>|null $run The run, or null for a scan that throws.
	 *
	 * @return IntegrityScanJob
	 */
	private function job(?array $run): IntegrityScanJob {
		$scan = $this->createMock(IntegrityScanService::class);
		$scan->method('run')->willReturnCallback(
			function (string $trigger) use ($run): array {
				$this->triggers[] = $trigger;
				if ($run === null) {
					throw new RuntimeException('register down');
				}

				return $run;
			}
		);

		$admins = [];
		foreach (['admin', 'beheer'] as $uid) {
			$user = $this->createMock(IUser::class);
			$user->method('getUID')->willReturn($uid);
			$admins[] = $user;
		}

		$group = $this->createMock(IGroup::class);
		$group->method('getUsers')->willReturn($admins);
		$groups = $this->createMock(IGroupManager::class);
		$groups->method('get')->with('admin')->willReturn($group);

		$manager = $this->createMock(INotificationManager::class);
		$manager->method('createNotification')->willReturnCallback(
			function (): INotification {
				$state        = new \ArrayObject();
				$notification = $this->createMock(INotification::class);
				foreach (['setApp', 'setDateTime', 'setObject'] as $setter) {
					$notification->method($setter)->willReturnSelf();
				}

				$notification->method('setUser')->willReturnCallback(
					function (string $uid) use ($state, $notification): INotification {
						$state['user'] = $uid;
						return $notification;
					}
				);
				$notification->method('setSubject')->willReturnCallback(
					function (string $subject, array $parameters) use ($state, $notification): INotification {
						$state['subject'] = [$subject, $parameters];
						return $notification;
					}
				);
				$notification->method('getUser')->willReturnCallback(static fn (): string => (string) ($state['user'] ?? ''));
				$notification->method('getSubjectParameters')->willReturnCallback(static fn (): array => ($state['subject'][1] ?? []));
				$notification->method('getSubject')->willReturnCallback(static fn (): string => (string) ($state['subject'][0] ?? ''));

				return $notification;
			}
		);
		$manager->method('notify')->willReturnCallback(
			function (INotification $notification): void {
				$this->sent[] = [$notification->getUser(), $notification->getSubject(), $notification->getSubjectParameters()];
			}
		);

		return new IntegrityScanJob($this->createMock(ITimeFactory::class), $scan, $groups, $manager, new NullLogger());
	}//end job()

	/**
	 * Run the job once.
	 *
	 * @param IntegrityScanJob $job The job.
	 *
	 * @return void
	 */
	private static function runOnce(IntegrityScanJob $job): void {
		(new ReflectionMethod($job, 'run'))->invoke($job, null);
	}//end runOnce()

	/**
	 * Three changed files: each administrator gets one notification with the counts.
	 *
	 * @return void
	 */
	public function testARunWithFindingsNotifiesEachAdministratorOnce(): void {
		self::runOnce(job: $this->job(run: ['startedAt' => '2026-11-14T02:00:00+00:00', 'missing' => 0, 'changed' => 3, 'orphaned' => 0, 'findings' => [[], [], []]]));

		$this->assertSame(['nightly'], $this->triggers);
		$this->assertSame(
			[
				['admin', IntegrityScanNotifier::SUBJECT, ['missing' => 0, 'changed' => 3, 'orphaned' => 0]],
				['beheer', IntegrityScanNotifier::SUBJECT, ['missing' => 0, 'changed' => 3, 'orphaned' => 0]],
			],
			$this->sent
		);
	}//end testARunWithFindingsNotifiesEachAdministratorOnce()

	/**
	 * A clean run, or a scan that fails, notifies nobody.
	 *
	 * @return void
	 */
	public function testACleanOrFailedRunNotifiesNobody(): void {
		self::runOnce(job: $this->job(run: ['missing' => 0, 'changed' => 0, 'orphaned' => 0, 'findings' => []]));
		self::runOnce(job: $this->job(run: null));

		$this->assertSame(['nightly', 'nightly'], $this->triggers);
		$this->assertSame([], $this->sent);
	}//end testACleanOrFailedRunNotifiesNobody()

	/**
	 * Only orphaned copies: the run is stored, but no administrator is called for it.
	 *
	 * @return void
	 */
	public function testAnOrphanedOnlyRunNotifiesNobody(): void {
		self::runOnce(job: $this->job(run: ['missing' => 0, 'changed' => 0, 'orphaned' => 2, 'findings' => [[], []]]));

		$this->assertSame(['nightly'], $this->triggers);
		$this->assertSame([], $this->sent);
	}//end testAnOrphanedOnlyRunNotifiesNobody()

	/**
	 * The job and the notifier are registered.
	 *
	 * @return void
	 */
	public function testTheJobAndTheNotifierAreRegistered(): void {
		$root = dirname(__DIR__, 3);
		$this->assertStringContainsString('<job>OCA\Filinq\BackgroundJob\IntegrityScanJob</job>', (string) file_get_contents($root . '/appinfo/info.xml'));
		$this->assertStringContainsString(
			"registerNotifierService('OCA\\\\Filinq\\\\Notification\\\\IntegrityScanNotifier')",
			(string) file_get_contents($root . '/lib/AppInfo/RegistrationBootstrap.php')
		);
	}//end testTheJobAndTheNotifierAreRegistered()
}//end class
