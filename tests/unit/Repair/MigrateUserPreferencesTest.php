<?php

/**
 * Tests for the docudesk -> filinq per-user preference migration.
 *
 * WHY THIS EXISTS. `oc_preferences` is namespaced by app id just like
 * `oc_appconfig`, and every read of a per-user value in this app carries a
 * DEFAULT. So a lost row does not surface as an error — it surfaces as a
 * dismissed banner coming back for every user who had already dismissed it.
 * That is precisely the class of regression a test suite has to catch, because
 * nothing else will.
 *
 * The double is a FAKE STORE, for the same reason as MigrateAppConfigKeysTest:
 * asserting on call counts would only prove the test and the code agree on a
 * method name, whereas asking what the store HOLDS afterwards tests the
 * decision the step makes.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Repair
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Repair;

use OCA\Filinq\Repair\MigrateUserPreferences;
use OCP\IConfig;
use OCP\Migration\IOutput;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * A three-level preference store: app id => user id => key => value.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Repair
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
final class UserPreferenceStore {
	/**
	 * Constructor.
	 *
	 * @param array<string, array<string, array<string, string>>> $rows Initial app => user => key => value.
	 * @param string|null $throwOnUser A user whose write blows up, or null.
	 *
	 * @return void
	 */
	public function __construct(
		private array $rows = [],
		private readonly ?string $throwOnUser = null,
	) {
	}//end __construct()

	/**
	 * Every user holding one exact value for one key under one app.
	 *
	 * Mirrors `IConfig::getUsersForUserValue()`, which is the only enumeration
	 * the interface offers.
	 *
	 * @param string $app The app id.
	 * @param string $key The preference key.
	 * @param string $value The exact value to match.
	 *
	 * @return array<int, string>
	 */
	public function usersFor(string $app, string $key, string $value): array {
		$users = [];
		foreach (($this->rows[$app] ?? []) as $userId => $prefs) {
			if (($prefs[$key] ?? null) === $value) {
				$users[] = (string)$userId;
			}
		}

		return $users;
	}//end usersFor()

	/**
	 * Read one preference.
	 *
	 * @param string $userId The user id.
	 * @param string $app The app id.
	 * @param string $key The preference key.
	 * @param string $default Returned when nothing is stored.
	 *
	 * @return string
	 */
	public function get(string $userId, string $app, string $key, string $default = ''): string {
		return $this->rows[$app][$userId][$key] ?? $default;
	}//end get()

	/**
	 * Write one preference.
	 *
	 * @param string $userId The user id.
	 * @param string $app The app id.
	 * @param string $key The preference key.
	 * @param string $value The value.
	 *
	 * @return void
	 */
	public function set(string $userId, string $app, string $key, string $value): void {
		if ($this->throwOnUser !== null && $userId === $this->throwOnUser) {
			throw new RuntimeException('write refused');
		}

		$this->rows[$app][$userId][$key] = $value;
	}//end set()

	/**
	 * The whole store, for comparing two runs.
	 *
	 * @return array<string, array<string, array<string, string>>>
	 */
	public function snapshot(): array {
		return $this->rows;
	}//end snapshot()
}//end class

/**
 * Unit tests for MigrateUserPreferences.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Repair
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
final class MigrateUserPreferencesTest extends TestCase {
	/**
	 * Wire a store into an IConfig double.
	 *
	 * @param UserPreferenceStore $store The backing store.
	 *
	 * @return IConfig
	 */
	private function configOver(UserPreferenceStore $store): IConfig {
		$mock = $this->createMock(IConfig::class);
		$mock->method('getUsersForUserValue')->willReturnCallback(
			static fn ($app, $key, $value): array => $store->usersFor((string)$app, (string)$key, (string)$value)
		);
		$mock->method('getUserValue')->willReturnCallback(
			static fn ($userId, $app, $key, $default = ''): string
				=> $store->get((string)$userId, (string)$app, (string)$key, (string)$default)
		);
		$mock->method('setUserValue')->willReturnCallback(
			static function ($userId, $app, $key, $value, $preCondition = null) use ($store) {
				$store->set((string)$userId, (string)$app, (string)$key, (string)$value);
				return null;
			}
		);

		return $mock;
	}//end configOver()

	/**
	 * Build the step over a fresh store.
	 *
	 * @param array<string, array<string, array<string, string>>> $rows Initial app => user => key => value.
	 *
	 * @return array{0: MigrateUserPreferences, 1: UserPreferenceStore}
	 */
	private function stepOver(array $rows): array {
		$store = new UserPreferenceStore(rows: $rows);
		$step = new MigrateUserPreferences(
			$this->configOver(store: $store),
			$this->createMock(LoggerInterface::class)
		);

		return [$step, $store];
	}//end stepOver()

	/**
	 * Every migrated key is carried across for every user holding it.
	 *
	 * Both keys are dismissals: losing either un-dismisses a nag the user has
	 * already told the app to stop showing, with no error anywhere.
	 *
	 * @return void
	 */
	public function testDismissalsAreCarriedAcross(): void {
		[$step, $store] = $this->stepOver([
			'docudesk' => [
				'alice' => ['anonymiser_warning_dismissed' => '1'],
				'bob' => ['pref_support-dialog-seen' => '1'],
				'carol' => [
					'anonymiser_warning_dismissed' => '1',
					'pref_support-dialog-seen' => '1',
				],
			],
		]);

		$step->run($this->createMock(IOutput::class));

		$this->assertSame('1', $store->get('alice', 'filinq', 'anonymiser_warning_dismissed'));
		$this->assertSame('1', $store->get('bob', 'filinq', 'pref_support-dialog-seen'));
		$this->assertSame('1', $store->get('carol', 'filinq', 'anonymiser_warning_dismissed'));
		$this->assertSame('1', $store->get('carol', 'filinq', 'pref_support-dialog-seen'));
	}//end testDismissalsAreCarriedAcross()

	/**
	 * A user with nothing stored gets nothing written.
	 *
	 * The default already applies for them, so a row would be noise.
	 *
	 * @return void
	 */
	public function testUsersWithoutAPreferenceAreLeftAlone(): void {
		[$step, $store] = $this->stepOver([
			'docudesk' => ['alice' => ['anonymiser_warning_dismissed' => '1']],
		]);

		$step->run($this->createMock(IOutput::class));

		$this->assertSame('', $store->get('dave', 'filinq', 'anonymiser_warning_dismissed'));
	}//end testUsersWithoutAPreferenceAreLeftAlone()

	/**
	 * A preference already set under the new app id is never clobbered.
	 *
	 * @return void
	 */
	public function testExistingDestinationIsNeverClobbered(): void {
		[$step, $store] = $this->stepOver([
			'docudesk' => ['alice' => ['anonymiser_warning_dismissed' => '1']],
			'filinq' => ['alice' => ['anonymiser_warning_dismissed' => '0']],
		]);

		$step->run($this->createMock(IOutput::class));

		$this->assertSame('0', $store->get('alice', 'filinq', 'anonymiser_warning_dismissed'));
	}//end testExistingDestinationIsNeverClobbered()

	/**
	 * The old rows survive, so a rollback to docudesk still finds them.
	 *
	 * @return void
	 */
	public function testOldRowsArePreserved(): void {
		[$step, $store] = $this->stepOver([
			'docudesk' => ['alice' => ['anonymiser_warning_dismissed' => '1']],
		]);

		$step->run($this->createMock(IOutput::class));

		$this->assertSame('1', $store->get('alice', 'docudesk', 'anonymiser_warning_dismissed'));
	}//end testOldRowsArePreserved()

	/**
	 * Running twice changes nothing the first run did not already do.
	 *
	 * @return void
	 */
	public function testStepIsIdempotent(): void {
		[$step, $store] = $this->stepOver([
			'docudesk' => ['alice' => ['pref_support-dialog-seen' => '1']],
		]);

		$step->run($this->createMock(IOutput::class));
		$after = $store->snapshot();
		$step->run($this->createMock(IOutput::class));

		$this->assertSame($after, $store->snapshot());
	}//end testStepIsIdempotent()

	/**
	 * A fresh install that never had docudesk is a clean no-op.
	 *
	 * @return void
	 */
	public function testNoOldPreferencesIsANoOp(): void {
		[$step, $store] = $this->stepOver([]);

		$output = $this->createMock(IOutput::class);
		$output->expects($this->once())
			->method('info')
			->with($this->stringContains('nothing to do'));

		$step->run($output);

		$this->assertSame([], $store->snapshot());
	}//end testNoOldPreferencesIsANoOp()

	/**
	 * One unwritable preference is logged; the rest still migrate.
	 *
	 * A repair step that throws aborts the install.
	 *
	 * @return void
	 */
	public function testFailedWriteIsLoggedAndDoesNotStopTheLoop(): void {
		$store = new UserPreferenceStore(
			rows: [
				'docudesk' => [
					'alice' => ['anonymiser_warning_dismissed' => '1'],
					'boom' => ['anonymiser_warning_dismissed' => '1'],
					'carol' => ['anonymiser_warning_dismissed' => '1'],
				],
			],
			throwOnUser: 'boom'
		);
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->once())->method('warning');

		$step = new MigrateUserPreferences($this->configOver(store: $store), $logger);
		$step->run($this->createMock(IOutput::class));

		$this->assertSame('1', $store->get('alice', 'filinq', 'anonymiser_warning_dismissed'));
		$this->assertSame('1', $store->get('carol', 'filinq', 'anonymiser_warning_dismissed'));
		$this->assertSame('', $store->get('boom', 'filinq', 'anonymiser_warning_dismissed'));
	}//end testFailedWriteIsLoggedAndDoesNotStopTheLoop()

	/**
	 * The step still says `docudesk` where it is supposed to.
	 *
	 * @return void
	 */
	public function testNameNamesBothAppIds(): void {
		[$step] = $this->stepOver([]);

		$this->assertStringContainsString('docudesk', $step->getName());
		$this->assertStringContainsString('filinq', $step->getName());
	}//end testNameNamesBothAppIds()
}//end class
