<?php

/**
 * Unit tests for MailArchiveSettings
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\MailArchive
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\MailArchive;

use OCA\Filinq\Service\MailArchive\MailArchiveSettings;
use OCA\Filinq\Service\MailArchive\ReadpstCommand;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;

/**
 * The per-run limit defaults to 200, stays within bounds, and the admin page sees readpst's status.
 *
 * @spec openspec/changes/archive/2026-11-25-intake-mailbox-archive-import/tasks.md#3-1
 */
class MailArchiveSettingsTest extends TestCase {

	/**
	 * Settings over one stored value.
	 *
	 * @param string|null $stored    The stored limit; null when unset.
	 * @param bool        $installed Whether readpst answers.
	 * @param array       $writes    Receives every write.
	 *
	 * @return MailArchiveSettings The settings.
	 */
	private function settings(?string $stored, bool $installed = false, array &$writes = []): MailArchiveSettings {
		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturnCallback(
			static function (string $app, string $key, string $default = '') use ($stored, &$writes): string {
				if ($app !== 'filinq' || $key !== MailArchiveSettings::KEY_MESSAGES_PER_RUN) {
					return $default;
				}

				return ($writes[$key] ?? $stored ?? $default);
			}
		);
		$config->method('setValueString')->willReturnCallback(
			static function (string $app, string $key, string $value) use (&$writes): bool {
				$writes[$key] = $value;

				return true;
			}
		);
		$readpst = $this->createMock(ReadpstCommand::class);
		$readpst->method('isAvailable')->willReturn($installed);

		return new MailArchiveSettings(appConfig: $config, readpst: $readpst);

	}//end settings()

	/**
	 * Unset or not a number is 200; a stored number is used within 1 and 2000.
	 *
	 * @return void
	 */
	public function testTheLimitDefaultsTo200AndStaysInBounds(): void {
		$this->assertSame(200, $this->settings(stored: null)->messagesPerRun());
		$this->assertSame(200, $this->settings(stored: 'lots')->messagesPerRun());
		$this->assertSame(200, $this->settings(stored: '-5')->messagesPerRun());
		$this->assertSame(50, $this->settings(stored: ' 50 ')->messagesPerRun());
		$this->assertSame(1, $this->settings(stored: '0')->messagesPerRun());
		$this->assertSame(2000, $this->settings(stored: '99999')->messagesPerRun());

	}//end testTheLimitDefaultsTo200AndStaysInBounds()

	/**
	 * An update stores the clamped limit and answers it with readpst's status.
	 *
	 * @return void
	 */
	public function testAnUpdateStoresTheClampedLimit(): void {
		$writes = [];
		$settings = $this->settings(stored: null, installed: true, writes: $writes);

		$this->assertSame(['messagesPerRun' => 2000, 'readpstInstalled' => true], $settings->update(messagesPerRun: 5000));
		$this->assertSame([MailArchiveSettings::KEY_MESSAGES_PER_RUN => '2000'], $writes);
		$this->assertSame(['messagesPerRun' => 200, 'readpstInstalled' => false], $this->settings(stored: null)->toArray());

	}//end testAnUpdateStoresTheClampedLimit()
}//end class
