<?php

/**
 * Tests for the docudesk -> filinq app-config migration.
 *
 * WHY THIS EXISTS. `IAppConfig` is namespaced by app id in `oc_appconfig`, so
 * the rename does not move a single row — it makes every stored value
 * unreachable. The step that copies them across is therefore a DATA MIGRATION,
 * and a migration that is never exercised is the worst thing in the repo to
 * leave untested: it fails silently, and the app just serves its defaults.
 *
 * The double is a FAKE STORE rather than a set of expected calls. Asserting
 * "setValueString was called" against a mock only proves the test and the code
 * agree on a method name; running the step against a described store and then
 * asking what the store HOLDS afterwards tests the decision the step actually
 * makes — including the two it is most likely to get wrong, which are the
 * key-name prefix rewrite and the refusal to clobber a destination value.
 *
 * The store is a plain object wired into a generated `IAppConfig` double rather
 * than a hand-written implementation of the interface: `IAppConfig` declares
 * some thirty methods this step never touches, and hand-writing them would be
 * thirty more places for the fixture to disagree with the real thing.
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

use OCA\Filinq\Repair\MigrateAppConfigKeys;
use OCP\IAppConfig;
use OCP\Migration\IOutput;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * A two-level app-config store: app id => key => value.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Repair
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
final class AppConfigStore {
	/**
	 * Constructor.
	 *
	 * @param array<string, array<string, string>> $rows Initial appid => key => value.
	 * @param bool $throwOnGetKeys Whether enumerating keys blows up.
	 * @param string|null $throwOnKey A key whose write blows up, or null.
	 *
	 * @return void
	 */
	public function __construct(
		private array $rows = [],
		private readonly bool $throwOnGetKeys = false,
		private readonly ?string $throwOnKey = null,
	) {
	}//end __construct()

	/**
	 * Every key stored under one app id.
	 *
	 * @param string $app The app id.
	 *
	 * @return array<int, string>
	 */
	public function keysFor(string $app): array {
		if ($this->throwOnGetKeys === true) {
			throw new RuntimeException('appconfig unavailable');
		}

		return array_keys($this->rows[$app] ?? []);
	}//end keysFor()

	/**
	 * Read one value.
	 *
	 * @param string $app The app id.
	 * @param string $key The key.
	 * @param string $default Returned when nothing is stored.
	 *
	 * @return string
	 */
	public function get(string $app, string $key, string $default = ''): string {
		return $this->rows[$app][$key] ?? $default;
	}//end get()

	/**
	 * Write one value.
	 *
	 * @param string $app The app id.
	 * @param string $key The key.
	 * @param string $value The value.
	 *
	 * @return bool
	 */
	public function set(string $app, string $key, string $value): bool {
		if ($this->throwOnKey !== null && $key === $this->throwOnKey) {
			throw new RuntimeException('write refused');
		}

		$this->rows[$app][$key] = $value;
		return true;
	}//end set()

	/**
	 * The whole store, for comparing two runs.
	 *
	 * @return array<string, array<string, string>>
	 */
	public function snapshot(): array {
		return $this->rows;
	}//end snapshot()
}//end class

/**
 * Unit tests for MigrateAppConfigKeys.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Repair
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
final class MigrateAppConfigKeysTest extends TestCase {
	/**
	 * Wire a store into an IAppConfig double.
	 *
	 * @param AppConfigStore $store The backing store.
	 *
	 * @return IAppConfig
	 */
	private function appConfigOver(AppConfigStore $store): IAppConfig {
		$mock = $this->createMock(IAppConfig::class);
		$mock->method('getKeys')->willReturnCallback(
			static fn (string $app): array => $store->keysFor($app)
		);
		$mock->method('getValueString')->willReturnCallback(
			static fn (string $app, string $key, string $default = '', bool $lazy = false): string
				=> $store->get($app, $key, $default)
		);
		$mock->method('setValueString')->willReturnCallback(
			static fn (
				string $app,
				string $key,
				string $value,
				bool $lazy = false,
				bool $sensitive = false
			): bool => $store->set($app, $key, $value)
		);

		return $mock;
	}//end appConfigOver()

	/**
	 * Build the step over a fresh store.
	 *
	 * @param array<string, array<string, string>> $rows Initial appid => key => value.
	 *
	 * @return array{0: MigrateAppConfigKeys, 1: AppConfigStore}
	 */
	private function stepOver(array $rows): array {
		$store = new AppConfigStore(rows: $rows);
		$step = new MigrateAppConfigKeys(
			$this->appConfigOver(store: $store),
			$this->createMock(LoggerInterface::class)
		);

		return [$step, $store];
	}//end stepOver()

	/**
	 * An app-id-prefixed key is copied AND its prefix rewritten.
	 *
	 * This is the case a straight key-for-key copy gets wrong: it would write
	 * `docudesk.pdfa3.enabled` into the `filinq` namespace, where nothing reads
	 * it, and the setting would silently revert to its default.
	 *
	 * @return void
	 */
	public function testPrefixedKeysAreRewrittenWhileCopied(): void {
		[$step, $store] = $this->stepOver([
			'docudesk' => [
				'docudesk.pdfa3.enabled' => '1',
				'docudesk_batch_max_files' => '250',
			],
		]);

		$step->run($this->createMock(IOutput::class));

		$this->assertSame('1', $store->get('filinq', 'filinq.pdfa3.enabled'));
		$this->assertSame('250', $store->get('filinq', 'filinq_batch_max_files'));
		$this->assertSame('', $store->get('filinq', 'docudesk.pdfa3.enabled'));
	}//end testPrefixedKeysAreRewrittenWhileCopied()

	/**
	 * An unprefixed key keeps its exact name.
	 *
	 * Most of this app's keys are NOT app-id prefixed. Rewriting them would
	 * invent names nothing reads.
	 *
	 * @return void
	 */
	public function testUnprefixedKeysKeepTheirName(): void {
		[$step, $store] = $this->stepOver([
			'docudesk' => [
				'ocr_enabled' => '1',
				'configuration_version' => '7.11.0',
			],
		]);

		$step->run($this->createMock(IOutput::class));

		$this->assertSame('1', $store->get('filinq', 'ocr_enabled'));
		$this->assertSame('7.11.0', $store->get('filinq', 'configuration_version'));
	}//end testUnprefixedKeysKeepTheirName()

	/**
	 * A key whose destination already holds a value is left alone.
	 *
	 * An admin edit made after the rename must survive a re-run.
	 *
	 * @return void
	 */
	public function testExistingDestinationIsNeverClobbered(): void {
		[$step, $store] = $this->stepOver([
			'docudesk' => ['ocr_enabled' => '0'],
			'filinq' => ['ocr_enabled' => '1'],
		]);

		$step->run($this->createMock(IOutput::class));

		$this->assertSame('1', $store->get('filinq', 'ocr_enabled'));
	}//end testExistingDestinationIsNeverClobbered()

	/**
	 * Nextcloud's own per-app bookkeeping is never copied.
	 *
	 * Copying `enabled` with `setValueString()` stores type STRING over the
	 * MIXED value `AppManager::enableApp()` wrote, and the next `app:enable`
	 * then fails permanently with AppConfigTypeConflictException.
	 *
	 * @return void
	 */
	public function testReservedKeysAreSkipped(): void {
		[$step, $store] = $this->stepOver([
			'docudesk' => [
				'enabled' => 'yes',
				'installed_version' => '0.0.38',
				'types' => 'filesystem',
			],
		]);

		$step->run($this->createMock(IOutput::class));

		$this->assertSame([], $store->keysFor('filinq'));
	}//end testReservedKeysAreSkipped()

	/**
	 * An empty source value is not worth a row in the new namespace.
	 *
	 * @return void
	 */
	public function testEmptySourceValuesAreSkipped(): void {
		[$step, $store] = $this->stepOver([
			'docudesk' => ['ocr_languages' => ''],
		]);

		$step->run($this->createMock(IOutput::class));

		$this->assertSame([], $store->keysFor('filinq'));
	}//end testEmptySourceValuesAreSkipped()

	/**
	 * The old namespace is never emptied, so a rollback still finds it.
	 *
	 * @return void
	 */
	public function testOldNamespaceIsPreserved(): void {
		[$step, $store] = $this->stepOver([
			'docudesk' => ['docudesk.pdfa3.enabled' => '1'],
		]);

		$step->run($this->createMock(IOutput::class));

		$this->assertSame('1', $store->get('docudesk', 'docudesk.pdfa3.enabled'));
	}//end testOldNamespaceIsPreserved()

	/**
	 * Running twice changes nothing the first run did not already do.
	 *
	 * @return void
	 */
	public function testStepIsIdempotent(): void {
		[$step, $store] = $this->stepOver([
			'docudesk' => ['docudesk.pdfa3.enabled' => '1'],
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
	public function testNoOldConfigurationIsANoOp(): void {
		[$step, $store] = $this->stepOver([]);

		$output = $this->createMock(IOutput::class);
		$output->expects($this->once())
			->method('info')
			->with($this->stringContains('nothing to do'));

		$step->run($output);

		$this->assertSame([], $store->keysFor('filinq'));
	}//end testNoOldConfigurationIsANoOp()

	/**
	 * An unreadable key list is logged and skipped, not fatal.
	 *
	 * A repair step that throws aborts the install.
	 *
	 * @return void
	 */
	public function testUnreadableKeyListDoesNotThrow(): void {
		$store = new AppConfigStore(rows: [], throwOnGetKeys: true);
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->once())->method('warning');

		$step = new MigrateAppConfigKeys($this->appConfigOver(store: $store), $logger);
		$step->run($this->createMock(IOutput::class));

		$this->assertSame([], $store->snapshot());
	}//end testUnreadableKeyListDoesNotThrow()

	/**
	 * A destination write that fails is logged and the loop continues.
	 *
	 * @return void
	 */
	public function testFailedWriteIsLoggedAndDoesNotStopTheLoop(): void {
		$store = new AppConfigStore(
			rows: ['docudesk' => ['a_key' => 'a', 'boom' => 'b', 'c_key' => 'c']],
			throwOnKey: 'boom'
		);
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->once())->method('warning');

		$step = new MigrateAppConfigKeys($this->appConfigOver(store: $store), $logger);
		$step->run($this->createMock(IOutput::class));

		$this->assertSame('a', $store->get('filinq', 'a_key'));
		$this->assertSame('c', $store->get('filinq', 'c_key'));
		$this->assertSame('', $store->get('filinq', 'boom'));
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
