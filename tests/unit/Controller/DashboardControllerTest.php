<?php

/**
 * Wire-contract tests for DashboardController
 *
 * Covers `GET /` (dashboard#page) and the Vue history-mode catch-all
 * `GET /{path}` (dashboard#catchAll). Both must answer HTTP 200 with a
 * TemplateResponse that renders the `index` template of the `filinq` app —
 * the SPA host that info.xml navigation and every dashboard widget link to.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\DashboardController;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the SPA host endpoints.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class DashboardControllerTest extends TestCase {

	/**
	 * Controller under test.
	 *
	 * @var DashboardController
	 */
	private DashboardController $controller;

	/**
	 * Set up the controller.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->controller = new DashboardController($this->createMock(IRequest::class));

	}//end setUp()

	/**
	 * `dashboard#page` renders the filinq `index` template with HTTP 200.
	 *
	 * @return void
	 */
	public function testPageRendersIndexTemplate(): void {
		$response = $this->controller->page();

		$this->assertInstanceOf(TemplateResponse::class, $response);
		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertSame('index', $response->getTemplateName());
		$this->assertSame('filinq', $response->getApp());

	}//end testPageRendersIndexTemplate()

	/**
	 * `dashboard#catchAll` serves the same SPA host, so a deep link such as
	 * `/apps/filinq/templates/abc` boots the Vue router instead of 404ing.
	 *
	 * @return void
	 */
	public function testCatchAllServesTheSameSpaHost(): void {
		$response = $this->controller->catchAll();

		$this->assertInstanceOf(TemplateResponse::class, $response);
		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertSame('index', $response->getTemplateName());
		$this->assertSame('filinq', $response->getApp());

	}//end testCatchAllServesTheSameSpaHost()

	/**
	 * The catch-all is defined as a delegate of `page()`, so the two responses
	 * must be interchangeable — a divergence here means a deep link renders a
	 * different document than the app root.
	 *
	 * @return void
	 */
	public function testCatchAllMatchesPageResponse(): void {
		$page = $this->controller->page();
		$catchAll = $this->controller->catchAll();

		$this->assertSame($page->getTemplateName(), $catchAll->getTemplateName());
		$this->assertSame($page->getApp(), $catchAll->getApp());
		$this->assertSame($page->getStatus(), $catchAll->getStatus());
		$this->assertSame($page->getRenderAs(), $catchAll->getRenderAs());

	}//end testCatchAllMatchesPageResponse()

}//end class
