<?php

/**
 * Unit tests for BatchRouting: the synchronous and background caps and the engine switch.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#requirement-resource-guards-on-extractconvert-backends-req-ddras-008
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use Exception;
use OCA\Filinq\Service\BatchRouting;
use OCA\Filinq\Service\BatchStateService;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;

/**
 * Which path a batch of a given size takes.
 *
 * @covers \OCA\Filinq\Service\BatchRouting
 */
class BatchRoutingTest extends TestCase {

	/**
	 * A routing over a synchronous cap and app config values.
	 *
	 * @param int                   $syncCap The synchronous cap.
	 * @param array<string, string> $config  App config key => value.
	 *
	 * @return BatchRouting
	 */
	private function routing(int $syncCap, array $config=[]): BatchRouting {
		$state = $this->createMock(BatchStateService::class);
		$state->method('getMaxFiles')->willReturn($syncCap);
		$appConfig = $this->createMock(IAppConfig::class);
		$appConfig->method('getValueString')->willReturnCallback(
			static fn (string $app, string $key, string $default): string => ($config[$key] ?? $default)
		);
		return new BatchRouting(stateService: $state, appConfig: $appConfig);

	}//end routing()

	/**
	 * At or below the synchronous cap a batch stays in-request.
	 *
	 * @return void
	 */
	public function testASmallBatchStaysSynchronous(): void {
		$routing = $this->routing(syncCap: 100);

		$this->assertSame(BatchRouting::SYNC, $routing->route(fileCount: 20));
		$this->assertSame(BatchRouting::SYNC, $routing->route(fileCount: 100));
		$this->assertFalse($routing->anonymizesInBackground(fileCount: 100));

	}//end testASmallBatchStaysSynchronous()

	/**
	 * Between the two caps a batch goes to the background instead of being refused.
	 *
	 * @return void
	 */
	public function testABatchBetweenTheCapsGoesToTheBackground(): void {
		$routing = $this->routing(syncCap: 100);

		$this->assertSame(BatchRouting::BACKGROUND, $routing->route(fileCount: 480));
		$this->assertSame(BatchRouting::BACKGROUND, $routing->route(fileCount: 1000));
		$this->assertTrue($routing->anonymizesInBackground(fileCount: 480));

	}//end testABatchBetweenTheCapsGoesToTheBackground()

	/**
	 * Above the background cap the batch is refused with a 400 naming the maximum.
	 *
	 * @return void
	 */
	public function testABatchAboveTheBackgroundCapIsRefused(): void {
		$routing = $this->routing(syncCap: 100);

		$this->expectException(Exception::class);
		$this->expectExceptionCode(400);
		$this->expectExceptionMessage('(found: 1200, maximum: 1000)');
		$routing->route(fileCount: 1200);

	}//end testABatchAboveTheBackgroundCapIsRefused()

	/**
	 * The background cap is read from app config, and never sits below the synchronous cap.
	 *
	 * @return void
	 */
	public function testTheBackgroundCapIsConfigurableButNotBelowTheSyncCap(): void {
		$this->assertSame(500, $this->routing(syncCap: 100, config: ['batch.max_files_background' => '500'])->backgroundCap());
		$this->assertSame(100, $this->routing(syncCap: 100, config: ['batch.max_files_background' => '50'])->backgroundCap());
		$this->assertSame(1000, $this->routing(syncCap: 100, config: ['batch.max_files_background' => '0'])->backgroundCap());

	}//end testTheBackgroundCapIsConfigurableButNotBelowTheSyncCap()

	/**
	 * The legacy-sync kill switch makes the synchronous cap the only cap again.
	 *
	 * @return void
	 */
	public function testTheLegacyEngineRefusesAboveTheSyncCap(): void {
		$routing = $this->routing(syncCap: 100, config: ['batch.engine' => 'legacy-sync']);

		$this->assertFalse($routing->backgroundEnabled());
		$this->assertFalse($routing->anonymizesInBackground(fileCount: 480));
		$this->expectExceptionMessage('(found: 101, maximum: 100)');
		$routing->route(fileCount: 101);

	}//end testTheLegacyEngineRefusesAboveTheSyncCap()
}//end class
