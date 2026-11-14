<?php

/**
 * Characterization tests for MigrateUserPreferences::run() tallies
 *
 * Pins the report line and the warnings when one key's enumeration or one
 * user's copy fails.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Repair
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec exclude No canonical spec covers the docudesk -> filinq app-id rename.
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
 * Tallies and warnings of the preference copy.
 *
 * @covers \OCA\Filinq\Repair\MigrateUserPreferences
 */
class MigrateUserPreferencesTraceTest extends TestCase {

	/**
	 * One key that cannot be enumerated is warned about; the other key still migrates.
	 *
	 * @return void
	 */
	public function testUnreadableKeyIsSkippedAndTheOtherMigrates(): void {
		$set = [];
		$config = $this->config(
			users: ['pref_support-dialog-seen' => ['ann', 'ben']],
			existing: ['ben' => '1'],
			set: $set,
			failUser: null
		);
		$warnings = [];
		$messages = [];

		(new MigrateUserPreferences($config, $this->logger(warnings: $warnings)))->run($this->recordingOutput(messages: $messages));

		$this->assertSame([['ann', 'filinq', 'pref_support-dialog-seen', '1']], $set);
		$this->assertSame(['MigrateUserPreferences: migrated 1 preference(s); 1 already set under filinq.'], $messages);
		$this->assertSame(
			[[
				'MigrateUserPreferences: could not enumerate users for one preference; leaving it under the old app id.',
				['key' => 'anonymiser_warning_dismissed', 'value' => '1', 'exception' => 'store down'],
			]],
			$warnings
		);

	}//end testUnreadableKeyIsSkippedAndTheOtherMigrates()

	/**
	 * A user whose copy fails is counted neither as migrated nor as present.
	 *
	 * @return void
	 */
	public function testFailedCopyIsNotCounted(): void {
		$set = [];
		$config = $this->config(
			users: ['anonymiser_warning_dismissed' => ['ann', 'boom', 'cee'], 'pref_support-dialog-seen' => []],
			existing: [],
			set: $set,
			failUser: 'boom'
		);
		$warnings = [];
		$messages = [];

		(new MigrateUserPreferences($config, $this->logger(warnings: $warnings)))->run($this->recordingOutput(messages: $messages));

		$this->assertSame(['ann', 'cee'], array_column($set, 0));
		$this->assertSame(['MigrateUserPreferences: migrated 2 preference(s); 0 already set under filinq.'], $messages);
		$this->assertSame(
			[[
				'MigrateUserPreferences: could not migrate one preference; leaving it under the old app id.',
				['exception' => 'write refused', 'key' => 'anonymiser_warning_dismissed', 'app' => 'filinq'],
			]],
			$warnings
		);

	}//end testFailedCopyIsNotCounted()

	/**
	 * An IConfig double: enumeration per key (a missing key throws), existing filinq values, recorded writes.
	 *
	 * @param array<string, array<int, string>> $users Users per key under the old app id.
	 * @param array<string, string> $existing Existing filinq value per user.
	 * @param array<int, array<int, string>> $set Recorded writes, by reference.
	 * @param string|null $failUser The user whose write throws.
	 *
	 * @return IConfig The double.
	 */
	private function config(array $users, array $existing, array &$set, ?string $failUser): IConfig {
		$config = $this->createMock(IConfig::class);
		$config->method('getUsersForUserValue')->willReturnCallback(
			static function (string $app, string $key, string $value) use ($users): array {
				if ($app !== 'docudesk' || $value !== '1' || array_key_exists($key, $users) === false) {
					throw new RuntimeException('store down');
				}

				return $users[$key];
			}
		);
		$config->method('getUserValue')->willReturnCallback(
			static fn (string $userId, string $app, string $key, mixed $default = ''): string => ($app === 'filinq' ? ($existing[$userId] ?? '') : 'wrong-app')
		);
		$config->method('setUserValue')->willReturnCallback(
			static function (string $userId, string $app, string $key, string $value) use (&$set, $failUser): void {
				if ($userId === $failUser) {
					throw new RuntimeException('write refused');
				}

				$set[] = [$userId, $app, $key, $value];
			}
		);

		return $config;
	}//end config()

	/**
	 * A logger recording warnings.
	 *
	 * @param array<int, array<int, mixed>> $warnings Recorded warnings, by reference.
	 *
	 * @return LoggerInterface The double.
	 */
	private function logger(array &$warnings): LoggerInterface {
		$logger = $this->createMock(LoggerInterface::class);
		$logger->method('warning')->willReturnCallback(
			static function (string|\Stringable $message, array $context = []) use (&$warnings): void {
				$warnings[] = [(string)$message, $context];
			}
		);

		return $logger;
	}//end logger()

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
