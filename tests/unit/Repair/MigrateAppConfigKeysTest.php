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
	/**
	 * An unreadable SOURCE value is logged, and the next key still migrates.
	 *
	 * The two reads used to sit outside the try, so a throwing read escaped
	 * run() and aborted `occ upgrade` — the one outcome this class's docblock
	 * promises cannot happen. One unreadable key must cost that key its
	 * value, not cost the admin their upgrade.
	 *
	 * @return void
	 *
	 * @spec exclude Covers the same one-off docudesk -> filinq rename
	 *       plumbing the class itself excludes: the step moves IAppConfig
	 *       rows between namespaces and adds no behaviour of its own, so
	 *       there is no capability spec to point at. What it pins is the
	 *       step's own safety contract - that it survives an unreadable key
	 *       rather than aborting the install.
	 */
	public function testAThrowingReadIsLoggedAndTheNextKeyStillMigrates(): void {
		/* The store's own throwOnKey only refuses WRITES, which is exactly
		   why an unguarded READ went unnoticed — so the read has to be made
		   to throw here at the IAppConfig seam. */
		$store = new AppConfigStore(rows: [
			'docudesk' => [
				'docudesk.boom' => 'unreadable',
				'docudesk.solr_url' => 'https://solr.example',
			],
		]);

		$mock = $this->createMock(IAppConfig::class);
		$mock->method('getKeys')->willReturnCallback(
			static fn (string $app): array => $store->keysFor($app)
		);
		$mock->method('getValueString')->willReturnCallback(
			static function (string $app, string $key, string $default = '', bool $lazy = false) use ($store): string {
				if ($key === 'docudesk.boom') {
					throw new \RuntimeException('config store unavailable');
				}

				return $store->get($app, $key, $default);
			}
		);
		$mock->method('setValueString')->willReturnCallback(
			static fn (string $app, string $key, string $value, bool $lazy = false, bool $sensitive = false): bool
				=> $store->set($app, $key, $value)
		);

		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->atLeastOnce())->method('warning');
		$step = new MigrateAppConfigKeys($mock, $logger);

		// The assertion that matters: run() RETURNS rather than throwing.
		$step->run($this->createMock(IOutput::class));

		$this->assertSame(
			'https://solr.example',
			$store->get('filinq', 'filinq.solr_url'),
			'the key after the unreadable one must still migrate'
		);
	}//end testAThrowingReadIsLoggedAndTheNextKeyStillMigrates()

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

	/**
	 * An IOutput double that records every `info()` line it is handed.
	 *
	 * The summary line is the only thing an admin running `occ upgrade` ever
	 * sees from this step, so it is worth asserting verbatim rather than
	 * through a substring: the four counters are the step's own account of
	 * what it decided, and a counter incremented in the wrong branch is a
	 * report that lies about a migration that did not happen.
	 *
	 * @param array<int, string> $messages Collected lines, by reference.
	 *
	 * @return IOutput
	 */
	private function recordingOutput(array &$messages): IOutput {
		$output = $this->createMock(IOutput::class);
		$output->method('info')->willReturnCallback(
			static function ($message) use (&$messages): void {
				$messages[] = (string)$message;
			}
		);

		return $output;
	}//end recordingOutput()

	/**
	 * The rewrite fires on a LEADING prefix only, never on a substring.
	 *
	 * A key that merely CONTAINS the old app id is not a name this app ever
	 * generated. Rewriting it would invent a key nothing reads — the value
	 * would survive the migration and still be lost, which is the same
	 * user-visible outcome as not migrating it at all.
	 *
	 * @return void
	 */
	public function testPrefixRewriteIsAnchoredAndNotASubstringMatch(): void {
		[$step, $store] = $this->stepOver([
			'docudesk' => [
				'legacy_docudesk.retention' => 'P5Y',
				'export_docudesk_format' => 'pdfa3',
			],
		]);

		$step->run($this->createMock(IOutput::class));

		$this->assertSame('P5Y', $store->get('filinq', 'legacy_docudesk.retention'));
		$this->assertSame('pdfa3', $store->get('filinq', 'export_docudesk_format'));
		$this->assertSame('', $store->get('filinq', 'legacy_filinq.retention'));
		$this->assertSame('', $store->get('filinq', 'export_filinq_format'));
	}//end testPrefixRewriteIsAnchoredAndNotASubstringMatch()

	/**
	 * The bare old app id, with no separator after it, is not a prefix match.
	 *
	 * `KEY_PREFIX_MAP` keys carry their separator (`docudesk.`, `docudesk_`)
	 * precisely so that a key literally named `docudesk` is copied verbatim
	 * rather than rewritten to the empty-named `filinq`.
	 *
	 * @return void
	 */
	public function testBareOldAppIdKeyIsCopiedVerbatim(): void {
		[$step, $store] = $this->stepOver([
			'docudesk' => ['docudesk' => 'sentinel'],
		]);

		$step->run($this->createMock(IOutput::class));

		$this->assertSame('sentinel', $store->get('filinq', 'docudesk'));
		$this->assertSame('', $store->get('filinq', 'filinq'));
	}//end testBareOldAppIdKeyIsCopiedVerbatim()

	/**
	 * The never-overwrite guard is checked against the REWRITTEN key name.
	 *
	 * This is the interaction of the two features most likely to be got wrong.
	 * A guard that looked up the OLD key name in the new namespace would find
	 * nothing, conclude the destination was free, and overwrite the admin's
	 * post-rename edit under the NEW name — a data loss no read would report,
	 * because both names resolve to a string either way.
	 *
	 * @return void
	 */
	public function testNeverOverwriteIsCheckedAgainstTheRewrittenKeyName(): void {
		[$step, $store] = $this->stepOver([
			'docudesk' => ['docudesk.pdfa3.enabled' => '0'],
			'filinq' => ['filinq.pdfa3.enabled' => '1'],
		]);

		$step->run($this->createMock(IOutput::class));

		$this->assertSame('1', $store->get('filinq', 'filinq.pdfa3.enabled'));
	}//end testNeverOverwriteIsCheckedAgainstTheRewrittenKeyName()

	/**
	 * A stale OLD-named row in the new namespace does not block the rewrite.
	 *
	 * The mirror image of the test above: an earlier straight-copy attempt may
	 * have left `docudesk.*` rows sitting in the `filinq` namespace, where
	 * nothing reads them. They must not be mistaken for a destination value.
	 *
	 * @return void
	 */
	public function testStaleOldNamedRowInNewNamespaceDoesNotBlockTheRewrite(): void {
		[$step, $store] = $this->stepOver([
			'docudesk' => ['docudesk.pdfa3.enabled' => '1'],
			'filinq' => ['docudesk.pdfa3.enabled' => 'stale'],
		]);

		$step->run($this->createMock(IOutput::class));

		$this->assertSame('1', $store->get('filinq', 'filinq.pdfa3.enabled'));
	}//end testStaleOldNamedRowInNewNamespaceDoesNotBlockTheRewrite()

	/**
	 * A stored `'0'` is a value, not an absence.
	 *
	 * The source guard is `$old === ''`, not `empty($old)`. Every boolean
	 * setting this app stores writes `'0'` when switched OFF, so an `empty()`
	 * test here would silently drop exactly the disabled settings — and each
	 * one would come back defaulted to ON.
	 *
	 * @return void
	 */
	public function testExplicitZeroValueIsMigratedRatherThanTreatedAsEmpty(): void {
		[$step, $store] = $this->stepOver([
			'docudesk' => [
				'ocr_enabled' => '0',
				'docudesk.pdfa3.enabled' => '0',
			],
		]);

		$step->run($this->createMock(IOutput::class));

		$this->assertSame('0', $store->get('filinq', 'ocr_enabled', 'MISSING'));
		$this->assertSame('0', $store->get('filinq', 'filinq.pdfa3.enabled', 'MISSING'));
	}//end testExplicitZeroValueIsMigratedRatherThanTreatedAsEmpty()

	/**
	 * The rewrite touches key NAMES only; values round-trip byte for byte.
	 *
	 * Several stored values legitimately contain the old app id — filesystem
	 * paths, JSON blobs of imported register ids. Rewriting inside a value
	 * would corrupt a path that still resolves on disk.
	 *
	 * @return void
	 */
	public function testValuesContainingTheOldAppIdAreCopiedUnchanged(): void {
		[$step, $store] = $this->stepOver([
			'docudesk' => [
				'docudesk.template_path' => '/apps/docudesk/templates',
				'imported_registers' => '{"docudesk":["docudesk.reports"]}',
			],
		]);

		$step->run($this->createMock(IOutput::class));

		$this->assertSame('/apps/docudesk/templates', $store->get('filinq', 'filinq.template_path'));
		$this->assertSame('{"docudesk":["docudesk.reports"]}', $store->get('filinq', 'imported_registers'));
	}//end testValuesContainingTheOldAppIdAreCopiedUnchanged()

	/**
	 * An empty string in the destination is an absence, so the copy proceeds.
	 *
	 * `IAppConfig` has no "is set" call the step can use; `''` is both the
	 * default and a storable value. The step treats it as free space, which is
	 * what makes a partially-completed earlier run recoverable.
	 *
	 * @return void
	 */
	public function testEmptyDestinationValueIsTreatedAsFreeSpace(): void {
		[$step, $store] = $this->stepOver([
			'docudesk' => ['ocr_enabled' => '1'],
			'filinq' => ['ocr_enabled' => ''],
		]);

		$step->run($this->createMock(IOutput::class));

		$this->assertSame('1', $store->get('filinq', 'ocr_enabled'));
	}//end testEmptyDestinationValueIsTreatedAsFreeSpace()

	/**
	 * A reserved key is skipped while its non-reserved neighbours migrate.
	 *
	 * The skip is a `continue`, not an abort: `enabled` sorts first in most
	 * real stores, so a skip implemented as a `break` or a `return` would
	 * strand every key after it while still passing a test that only looked
	 * at the reserved keys themselves.
	 *
	 * @return void
	 */
	public function testReservedKeysAreSkippedWhileNeighboursStillMigrate(): void {
		[$step, $store] = $this->stepOver([
			'docudesk' => [
				'enabled' => 'yes',
				'installed_version' => '0.0.38',
				'ocr_enabled' => '1',
				'types' => 'filesystem',
				'docudesk.pdfa3.enabled' => '1',
			],
		]);

		$step->run($this->createMock(IOutput::class));

		$this->assertSame('1', $store->get('filinq', 'ocr_enabled'));
		$this->assertSame('1', $store->get('filinq', 'filinq.pdfa3.enabled'));
		$this->assertSame(
			['ocr_enabled', 'filinq.pdfa3.enabled'],
			$store->keysFor('filinq')
		);
	}//end testReservedKeysAreSkippedWhileNeighboursStillMigrate()

	/**
	 * Enumeration is exhaustive: a key this app no longer reads still moves.
	 *
	 * The step walks `getKeys()` rather than a hardcoded list precisely so a
	 * key written by a past release, or by `SettingsInitializer`, cannot be
	 * left behind by an author who forgot to list it.
	 *
	 * @return void
	 */
	public function testKeysTheAppNoLongerReadsAreStillMigrated(): void {
		[$step, $store] = $this->stepOver([
			'docudesk' => [
				'a_key_no_source_file_mentions' => 'kept',
				'configuration_version' => '7.11.0',
			],
		]);

		$step->run($this->createMock(IOutput::class));

		$this->assertSame('kept', $store->get('filinq', 'a_key_no_source_file_mentions'));
		$this->assertSame('7.11.0', $store->get('filinq', 'configuration_version'));
	}//end testKeysTheAppNoLongerReadsAreStillMigrated()

	/**
	 * The summary counts each outcome in its own bucket.
	 *
	 * One key per branch, so a counter incremented under the wrong `continue`
	 * shows up as two wrong numbers rather than none.
	 *
	 * @return void
	 */
	public function testSummaryReportsEveryOutcomeCount(): void {
		[$step, $store] = $this->stepOver([
			'docudesk' => [
				'ocr_enabled' => '1',
				'docudesk.pdfa3.enabled' => '1',
				'ocr_languages' => '',
				'enabled' => 'yes',
			],
			'filinq' => ['filinq.pdfa3.enabled' => 'kept'],
		]);

		$messages = [];
		$step->run($this->recordingOutput($messages));

		$this->assertSame(
			[
				'MigrateAppConfigKeys: 1 key(s) migrated, 1 already present, '
				. '1 had no value to migrate, 1 skipped as Nextcloud-reserved.',
			],
			$messages
		);
		$this->assertSame('kept', $store->get('filinq', 'filinq.pdfa3.enabled'));
	}//end testSummaryReportsEveryOutcomeCount()

	/**
	 * A key whose write threw is not counted as migrated.
	 *
	 * The summary is the only signal an admin gets. Counting a failed write as
	 * a success would report a complete migration over a partial one, and the
	 * whole point of the logged warning is that somebody can go and look.
	 *
	 * @return void
	 */
	public function testFailedWriteIsNotCountedAsMigrated(): void {
		$store = new AppConfigStore(
			rows: ['docudesk' => ['a_key' => 'a', 'boom' => 'b', 'c_key' => 'c']],
			throwOnKey: 'boom'
		);
		$step = new MigrateAppConfigKeys(
			$this->appConfigOver(store: $store),
			$this->createMock(LoggerInterface::class)
		);

		$messages = [];
		$step->run($this->recordingOutput($messages));

		$this->assertSame(
			[
				'MigrateAppConfigKeys: 2 key(s) migrated, 0 already present, '
				. '0 had no value to migrate, 0 skipped as Nextcloud-reserved.',
			],
			$messages
		);
	}//end testFailedWriteIsNotCountedAsMigrated()

	/**
	 * An unreadable key list reports the no-op summary, not a false success.
	 *
	 * `oldKeys()` swallows the failure and returns `[]`, which is
	 * indistinguishable from a clean install downstream — so the step must at
	 * least say out loud that it did nothing, and must not claim it migrated.
	 *
	 * @return void
	 */
	public function testUnreadableKeyListReportsNothingToDo(): void {
		$store = new AppConfigStore(rows: [], throwOnGetKeys: true);
		$step = new MigrateAppConfigKeys(
			$this->appConfigOver(store: $store),
			$this->createMock(LoggerInterface::class)
		);

		$messages = [];
		$step->run($this->recordingOutput($messages));

		$this->assertSame(
			[
				'MigrateAppConfigKeys: no stored docudesk configuration on this install; '
				. 'nothing to do.',
			],
			$messages
		);
	}//end testUnreadableKeyListReportsNothingToDo()

	/**
	 * A second run over a store an admin has since edited keeps the edit.
	 *
	 * Idempotence is not enough on a live instance: the step is registered
	 * under `<post-migration>` and so runs on EVERY upgrade, long after admins
	 * have started changing settings under the new app id.
	 *
	 * @return void
	 */
	public function testRerunAfterAnAdminEditKeepsTheEdit(): void {
		[$step, $store] = $this->stepOver([
			'docudesk' => ['docudesk_batch_max_files' => '250'],
		]);

		$step->run($this->createMock(IOutput::class));
		$this->assertSame('250', $store->get('filinq', 'filinq_batch_max_files'));

		$store->set('filinq', 'filinq_batch_max_files', '10');
		$step->run($this->createMock(IOutput::class));

		$this->assertSame('10', $store->get('filinq', 'filinq_batch_max_files'));
	}//end testRerunAfterAnAdminEditKeepsTheEdit()
}//end class
