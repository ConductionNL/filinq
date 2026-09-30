<?php

/**
 * EmailIngestionSettings tests
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service\EmailIngestion
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/email-ingestion/tasks.md#2-5
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\EmailIngestion;

use InvalidArgumentException;
use OCA\Filinq\Service\EmailIngestion\EmailIngestionSettings;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;

/**
 * The inbox to dossier mapping and the per-tick budget.
 */
class EmailIngestionSettingsTest extends TestCase {

	/**
	 * Stored values.
	 *
	 * @var array<string, string>
	 */
	private array $stored = [];

	/**
	 * A valid mapping is stored and read back; the budget is clamped to a sane range.
	 *
	 * @return void
	 */
	public function testAMappingRoundTrips(): void {
		$settings = $this->settings();

		$saved = $settings->update(
			inboxes: [['folderId' => '42', 'dossierRef' => 'dossier-017'], ['folderId' => 43, 'dossierRef' => 'dossier-018']],
			filesPerTick: 5000
		);

		$this->assertSame(
			['inboxes' => [['folderId' => 42, 'dossierRef' => 'dossier-017'], ['folderId' => 43, 'dossierRef' => 'dossier-018']], 'filesPerTick' => EmailIngestionSettings::MAX_FILES_PER_TICK],
			$saved
		);
		$this->assertSame($saved, $this->settings()->toArray());

	}//end testAMappingRoundTrips()

	/**
	 * A mapping without a folder or a dossier, or one folder mapped twice, is refused.
	 *
	 * @return void
	 */
	public function testABrokenMappingIsRefused(): void {
		foreach ([
			[['folderId' => 0, 'dossierRef' => 'd']],
			[['folderId' => 4, 'dossierRef' => '']],
			[['folderId' => 4, 'dossierRef' => 'd'], ['folderId' => 4, 'dossierRef' => 'e']],
			['not a row'],
		] as $inboxes) {
			try {
				$this->settings()->update(inboxes: $inboxes, filesPerTick: 25);
				$this->fail('accepted ' . json_encode($inboxes));
			} catch (InvalidArgumentException $e) {
				$this->assertSame(400, $e->getCode());
			}
		}

		$this->assertSame([], $this->stored);

	}//end testABrokenMappingIsRefused()

	/**
	 * Unreadable stored JSON reads as no inboxes, not as an error.
	 *
	 * @return void
	 */
	public function testUnreadableStoredMappingReadsAsNone(): void {
		$this->stored[EmailIngestionSettings::KEY_INBOXES] = '{broken';

		$this->assertSame([], $this->settings()->inboxes());

	}//end testUnreadableStoredMappingReadsAsNone()

	/**
	 * The settings over an in-memory app config.
	 *
	 * @return EmailIngestionSettings
	 */
	private function settings(): EmailIngestionSettings {
		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturnCallback(fn (string $app, string $key, string $default = ''): string => ($this->stored[$key] ?? $default));
		$config->method('setValueString')->willReturnCallback(
			function (string $app, string $key, string $value): bool {
				$this->stored[$key] = $value;

				return true;
			}
		);

		return new EmailIngestionSettings(appConfig: $config);

	}//end settings()
}//end class
