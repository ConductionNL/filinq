<?php

/**
 * Characterization tests for MigrateAppConfigKeys::run() tallies
 *
 * Pins each outcome counter with a distinct count, and the warning context
 * for a failed read and a failed write.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Repair
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec exclude Covers the one-off docudesk -> filinq rename plumbing the class itself excludes.
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
 * Tallies and warnings of the app config copy.
 *
 * @covers \OCA\Filinq\Repair\MigrateAppConfigKeys
 */
class MigrateAppConfigKeysTraceTest extends TestCase {

	/**
	 * Every counter gets its own count, so a swapped counter shows.
	 *
	 * @return void
	 */
	public function testEachCounterHasItsOwnCount(): void {
		$old = [
			'm1' => 'a', 'm2' => 'b', 'm3' => 'c', 'm4' => 'd',
			'p1' => 'x', 'p2' => 'y', 'p3' => 'z',
			'e1' => '', 'e2' => '',
			'enabled' => 'yes',
		];
		$new = ['p1' => 'kept', 'p2' => 'kept', 'p3' => 'kept'];
		$warnings = [];
		$messages = [];

		$this->step(old: $old, new: $new, warnings: $warnings)->run($this->recordingOutput(messages: $messages));

		$this->assertSame(
			['MigrateAppConfigKeys: 4 key(s) migrated, 3 already present, 2 had no value to migrate, 1 skipped as Nextcloud-reserved.'],
			$messages
		);
		$this->assertSame([], $warnings);

	}//end testEachCounterHasItsOwnCount()

	/**
	 * A failed read names the old key twice; a failed write names the rewritten key.
	 *
	 * @return void
	 */
	public function testWarningsNameTheKeyAndItsDestination(): void {
		$old = ['docudesk.unreadable' => 'a', 'docudesk.unwritable' => 'b', 'docudesk.fine' => 'c'];
		$warnings = [];
		$messages = [];

		$this->step(old: $old, new: [], warnings: $warnings)->run($this->recordingOutput(messages: $messages));

		$this->assertSame(
			[
				[
					'Filinq: could not migrate one app config key; leaving it under the old namespace',
					['key' => 'docudesk.unreadable', 'newKey' => 'docudesk.unreadable', 'exception' => 'read refused'],
				],
				[
					'Filinq: could not migrate one app config key; leaving it under the old namespace',
					['key' => 'docudesk.unwritable', 'newKey' => 'filinq.unwritable', 'exception' => 'write refused'],
				],
			],
			$warnings
		);
		$this->assertSame(
			['MigrateAppConfigKeys: 1 key(s) migrated, 0 already present, 0 had no value to migrate, 0 skipped as Nextcloud-reserved.'],
			$messages
		);

	}//end testWarningsNameTheKeyAndItsDestination()

	/**
	 * The step over an IAppConfig double: `docudesk.unreadable` throws on read,
	 * any key ending in `unwritable` throws on write.
	 *
	 * @param array<string, string> $old Values under docudesk.
	 * @param array<string, string> $new Values already under filinq.
	 * @param array<int, array<int, mixed>> $warnings Recorded warnings, by reference.
	 *
	 * @return MigrateAppConfigKeys The step.
	 */
	private function step(array $old, array $new, array &$warnings): MigrateAppConfigKeys {
		$config = $this->createMock(IAppConfig::class);
		$config->method('getKeys')->willReturnCallback(static fn (string $app): array => ($app === 'docudesk' ? array_keys($old) : []));
		$config->method('getValueString')->willReturnCallback(
			static function (string $app, string $key, string $default = '') use ($old, $new): string {
				if ($key === 'docudesk.unreadable') {
					throw new RuntimeException('read refused');
				}

				$rows = ($app === 'docudesk' ? $old : $new);
				return ($rows[$key] ?? $default);
			}
		);
		$config->method('setValueString')->willReturnCallback(
			static function (string $app, string $key, string $value): bool {
				if (str_ends_with($key, 'unwritable') === true) {
					throw new RuntimeException('write refused');
				}

				return true;
			}
		);

		$logger = $this->createMock(LoggerInterface::class);
		$logger->method('warning')->willReturnCallback(
			static function (string|\Stringable $message, array $context = []) use (&$warnings): void {
				$warnings[] = [(string)$message, $context];
			}
		);

		return new MigrateAppConfigKeys($config, $logger);
	}//end step()

	/**
	 * An output recording info lines.
	 *
	 * @param array<int, string> $messages Recorded lines, by reference.
	 *
	 * @return IOutput The double.
	 */
	private function recordingOutput(array &$messages): IOutput {
		$output = $this->createMock(IOutput::class);
		$output->method('info')->willReturnCallback(
			static function (string $message) use (&$messages): void {
				$messages[] = $message;
			}
		);

		return $output;
	}//end recordingOutput()
}//end class
