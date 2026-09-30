<?php

/**
 * EmailIngestionJob tests
 *
 * The cron job drains a bulk drop in bounded ticks: the real job, service and
 * settings over the email-ingestion test world.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\BackgroundJob
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/email-ingestion/tasks.md#2-4
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\BackgroundJob;

require_once __DIR__ . '/../Service/EmailIngestion/EmailIngestionDoubles.php';

use OCA\Filinq\BackgroundJob\EmailIngestionJob;
use OCA\Filinq\Service\EmailIngestion\EmailIngestionSettings;
use OCA\Filinq\Tests\Unit\Service\EmailIngestion\EmailInboxWorld;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionMethod;

/**
 * Bounded work per tick.
 */
class EmailIngestionJobTest extends TestCase {
	use EmailInboxWorld;

	/**
	 * Build the world.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->setUpWorld();

	}//end setUp()

	/**
	 * Sixty emails and a budget of 25 drain as 25, 25 and 10.
	 *
	 * @return void
	 */
	public function testBulkDropDrainsInBoundedTicks(): void {
		for ($i = 0; $i < 60; $i++) {
			$this->drop(folderId: 100, name: sprintf('bulk-%02d.eml', $i), content: self::eml(messageId: 'bulk-' . $i . '@example.org'));
		}

		$job = $this->job(perTick: '25');
		$run = new ReflectionMethod($job, 'run');
		$processed = [];
		for ($tick = 0; $tick < 3; $tick++) {
			$before = count($this->namesIn(folderId: 100));
			$run->invoke($job, null);
			$processed[] = $before - count($this->namesIn(folderId: 100));
		}

		$this->assertSame([25, 25, 10], $processed);
		$this->assertCount(60, array_filter($this->namesIn(folderId: 200), static fn (string $n): bool => str_ends_with($n, '.eml')));

	}//end testBulkDropDrainsInBoundedTicks()

	/**
	 * Without a setting the budget is 25, and a nonsense setting falls back to it.
	 *
	 * @return void
	 */
	public function testTheBudgetDefaultsTo25(): void {
		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturnCallback(static fn (string $app, string $key, string $default = ''): string => ($key === EmailIngestionSettings::KEY_FILES_PER_TICK ? 'lots' : $default));

		$this->assertSame(25, (new EmailIngestionSettings(appConfig: $config))->filesPerTick());

	}//end testTheBudgetDefaultsTo25()

	/**
	 * The job with a per-tick setting.
	 *
	 * @param string $perTick The setting.
	 *
	 * @return EmailIngestionJob
	 */
	private function job(string $perTick): EmailIngestionJob {
		$this->config[EmailIngestionSettings::KEY_FILES_PER_TICK] = $perTick;
		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturnCallback(fn (string $app, string $key, string $default = ''): string => ($this->config[$key] ?? $default));

		return new EmailIngestionJob(
			clock: $this->createMock(ITimeFactory::class),
			ingestion: $this->service(),
			settings: new EmailIngestionSettings(appConfig: $config),
			logger: $this->createMock(LoggerInterface::class),
		);

	}//end job()
}//end class
