<?php

/**
 * Unit tests for the Filinq dashboard widgets
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Dashboard
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Dashboard;

use OCA\Filinq\Dashboard\AnonymizationWidget;
use OCA\Filinq\Dashboard\FileEntitiesWidget;
use OCP\Dashboard\IIconWidget;
use OCP\Dashboard\IWidget;
use OCP\IURLGenerator;
use PHPUnit\Framework\TestCase;

// The unit bootstrap does not autoload OCP's dashboard contracts; load the
// two the widgets implement from the OCP package.
foreach (['IWidget', 'IIconWidget'] as $ocpDashboardInterface) {
	if (interface_exists('OCP\\Dashboard\\' . $ocpDashboardInterface) === false) {
		require_once __DIR__ . '/../../../vendor/nextcloud/ocp/OCP/Dashboard/' . $ocpDashboardInterface . '.php';
	}
}

/**
 * Pins what each dashboard widget reports to the Dashboard app.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Dashboard
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class DashboardWidgetsTest extends TestCase {

	/**
	 * A URL generator that echoes what it was asked for.
	 *
	 * @return IURLGenerator The stub.
	 */
	private function urlGenerator(): IURLGenerator {
		$urls = $this->createMock(IURLGenerator::class);
		$urls->method('imagePath')->willReturnCallback(
			static fn (string $app, string $image): string => '/apps/' . $app . '/img/' . $image
		);
		$urls->method('getAbsoluteURL')->willReturnCallback(
			static fn (string $url): string => 'https://cloud.example' . $url
		);
		$urls->method('linkToRouteAbsolute')->willReturnCallback(
			static fn (string $route): string => 'https://cloud.example/route/' . $route
		);
		return $urls;

	}//end urlGenerator()

	/**
	 * The anonymization widget keeps its frozen id, title, order and links.
	 *
	 * @return void
	 */
	public function testAnonymizationWidget(): void {
		$widget = new AnonymizationWidget($this->urlGenerator());

		$this->assertInstanceOf(IWidget::class, $widget);
		$this->assertInstanceOf(IIconWidget::class, $widget);
		$this->assertSame('docudesk-anonymization', $widget->getId());
		$this->assertSame('Document Anonymization', $widget->getTitle());
		$this->assertSame(20, $widget->getOrder());
		$this->assertSame('icon-filinq', $widget->getIconClass());
		$this->assertSame('https://cloud.example/apps/filinq/img/app-dark.svg', $widget->getIconUrl());
		$this->assertSame('https://cloud.example/route/filinq.dashboard.page', $widget->getUrl());

	}//end testAnonymizationWidget()

	/**
	 * The file-entities widget keeps its frozen id, title, order and links.
	 *
	 * @return void
	 */
	public function testFileEntitiesWidget(): void {
		$widget = new FileEntitiesWidget($this->urlGenerator());

		$this->assertInstanceOf(IWidget::class, $widget);
		$this->assertInstanceOf(IIconWidget::class, $widget);
		$this->assertSame('docudesk-file-entities', $widget->getId());
		$this->assertSame('File Entities', $widget->getTitle());
		$this->assertSame(21, $widget->getOrder());
		$this->assertSame('icon-filinq', $widget->getIconClass());
		$this->assertSame('https://cloud.example/apps/filinq/img/app-dark.svg', $widget->getIconUrl());
		$this->assertSame('https://cloud.example/route/filinq.dashboard.page', $widget->getUrl());

	}//end testFileEntitiesWidget()
}//end class
