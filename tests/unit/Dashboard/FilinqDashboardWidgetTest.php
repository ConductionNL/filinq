<?php

/**
 * The shared dashboard widget base: loading a widget asks for the shared
 * vendor chunks first and the dashboard bundle last, all for this app.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Dashboard
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Dashboard;

use OCA\Filinq\Dashboard\AnonymizationWidget;
use OCA\Filinq\Dashboard\FileEntitiesWidget;
use OCP\IURLGenerator;
use OCP\Util;
use PHPUnit\Framework\TestCase;

foreach (['IWidget', 'IIconWidget'] as $ocpDashboardInterface) {
	if (interface_exists('OCP\\Dashboard\\' . $ocpDashboardInterface) === false) {
		require_once __DIR__ . '/../../../vendor/nextcloud/ocp/OCP/Dashboard/' . $ocpDashboardInterface . '.php';
	}
}

require_once __DIR__ . '/Stubs/RecordingOcpUtil.php';

/**
 * @covers \OCA\Filinq\Dashboard\FilinqDashboardWidget
 * @uses   \OCA\Filinq\Dashboard\AnonymizationWidget
 * @uses   \OCA\Filinq\Dashboard\FileEntitiesWidget
 */
class FilinqDashboardWidgetTest extends TestCase {

	/**
	 * Start every test with nothing recorded.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		if (property_exists(Util::class, 'recordedScripts') === false) {
			$this->markTestSkipped('A real OCP\Util is loaded; the recording stub is not in use.');
		}

		Util::$recordedScripts = [];

	}//end setUp()

	/**
	 * Leave nothing recorded behind.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		if (property_exists(Util::class, 'recordedScripts') === true) {
			Util::$recordedScripts = [];
		}

	}//end tearDown()

	/**
	 * Loading a widget asks for the two shared chunks, then the dashboard
	 * bundle, each for this app and after core.
	 *
	 * @return void
	 */
	public function testLoadAsksForTheSharedChunksThenTheDashboardBundle(): void {
		(new AnonymizationWidget($this->createMock(IURLGenerator::class)))->load();

		$this->assertSame(
			[
				['filinq', 'filinq-shared-vendor', 'core', false],
				['filinq', 'filinq-shared-nc-vue', 'core', false],
				['filinq', 'filinq-dashboard', 'core', false],
			],
			Util::$recordedScripts
		);

	}//end testLoadAsksForTheSharedChunksThenTheDashboardBundle()

	/**
	 * Every filinq widget loads the same bundle, so two widgets on one
	 * dashboard ask for it twice and the page deduplicates.
	 *
	 * @return void
	 */
	public function testEveryWidgetLoadsTheSameBundle(): void {
		$urls = $this->createMock(IURLGenerator::class);
		(new AnonymizationWidget($urls))->load();
		$first = Util::$recordedScripts;
		Util::$recordedScripts = [];
		(new FileEntitiesWidget($urls))->load();

		$this->assertSame($first, Util::$recordedScripts);
		$this->assertCount(3, $first);

	}//end testEveryWidgetLoadsTheSameBundle()

	/**
	 * Loading registers scripts only; it builds no URL.
	 *
	 * @return void
	 */
	public function testLoadBuildsNoUrl(): void {
		$urls = $this->createMock(IURLGenerator::class);
		$urls->expects($this->never())->method($this->anything());

		(new FileEntitiesWidget($urls))->load();

		$this->assertCount(3, Util::$recordedScripts);

	}//end testLoadBuildsNoUrl()
}//end class
