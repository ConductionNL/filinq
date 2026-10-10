<?php

/**
 * Unit tests for ReportRenderController
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\Filinq\Tests\Unit\Controller;

use Exception;
use OCA\Filinq\Controller\ReportRenderController;
use OCA\Filinq\Service\ReportRenderService;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IAppConfig;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for ReportRenderController::render() and ::renderBatch(),
 * added for openspec/changes/filinq-configurable-report-templates.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/filinq-configurable-report-templates/specs/report-render-api/spec.md
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class ReportRenderControllerTest extends TestCase {

	/**
	 * Mocked request.
	 *
	 * @var IRequest&MockObject
	 */
	private IRequest $request;

	/**
	 * Mocked render service.
	 *
	 * @var ReportRenderService&MockObject
	 */
	private ReportRenderService $renderService;

	/**
	 * Mocked app config.
	 *
	 * @var IAppConfig&MockObject
	 */
	private IAppConfig $appConfig;

	/**
	 * The controller under test.
	 *
	 * @var ReportRenderController
	 */
	private ReportRenderController $controller;

	/**
	 * Set up mocks and the controller under test, pre-authorised with a
	 * matching shared-secret token unless a test overrides it.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->renderService = $this->createMock(ReportRenderService::class);
		$this->appConfig = $this->createMock(IAppConfig::class);

		$this->appConfig->method('getValueString')
			->willReturn('correct-token');
		$this->request->method('getHeader')
			->with('Authorization')
			->willReturn('Bearer correct-token');

		$this->controller = new ReportRenderController(
			'filinq',
			$this->request,
			$this->renderService,
			$this->appConfig,
			$this->createMock(LoggerInterface::class)
		);

	}//end setUp()

	/**
	 * A missing/mismatched bearer token is rejected with 401, before the
	 * render service is ever called.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/filinq-configurable-report-templates/specs/report-render-api/spec.md#scenario-missing-or-mismatched-token-is-rejected
	 */
	public function testRenderRejectsInvalidToken(): void {
		$this->request = $this->createMock(IRequest::class);
		$this->request->method('getHeader')->with('Authorization')->willReturn('Bearer wrong-token');

		$this->renderService->expects($this->never())->method('render');

		$controller = new ReportRenderController(
			'filinq',
			$this->request,
			$this->renderService,
			$this->appConfig,
			$this->createMock(LoggerInterface::class)
		);

		$result = $controller->render();

		$this->assertEquals(401, $result->getStatus());

	}//end testRenderRejectsInvalidToken()

	/**
	 * An unconfigured token rejects every call (fails closed).
	 *
	 * @return void
	 *
	 * @spec openspec/changes/filinq-configurable-report-templates/specs/report-render-api/spec.md#scenario-no-token-configured-refuses-every-call
	 */
	public function testRenderRejectsWhenNoTokenConfigured(): void {
		$appConfig = $this->createMock(IAppConfig::class);
		$appConfig->method('getValueString')->willReturn('');

		$this->renderService->expects($this->never())->method('render');

		$controller = new ReportRenderController(
			'filinq',
			$this->request,
			$this->renderService,
			$appConfig,
			$this->createMock(LoggerInterface::class)
		);

		$result = $controller->render();

		$this->assertEquals(401, $result->getStatus());

	}//end testRenderRejectsWhenNoTokenConfigured()

	/**
	 * Missing templateSlug is a 400, checked after auth.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/filinq-configurable-report-templates/specs/report-render-api/spec.md#scenario-missing-templateslug-is-rejected
	 */
	public function testRenderRejectsMissingTemplateSlug(): void {
		$this->request->method('getParam')
			->willReturnCallback(function (string $key, $default = null) {
				return $default;
			});

		$result = $this->controller->render();

		$this->assertEquals(400, $result->getStatus());
		$this->assertStringContainsString('templateSlug', $result->getData()['error']);

	}//end testRenderRejectsMissingTemplateSlug()

	/**
	 * A successful render returns 200 with the service's result payload.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/filinq-configurable-report-templates/specs/report-render-api/spec.md#scenario-successful-render-returns-a-document-reference
	 */
	public function testRenderReturnsDocumentRefOnSuccess(): void {
		$this->request->method('getParam')
			->willReturnCallback(function (string $key, $default = null) {
				$values = [
					'templateSlug' => 'report-card',
					'tenantId' => 'school-a',
					'data' => ['leerling' => ['naam' => 'Test']],
					'options' => ['userId' => 'admin'],
					'userId' => 'admin',
				];

				return $values[$key] ?? $default;
			});

		$this->renderService->expects($this->once())
			->method('render')
			->with(
				'report-card',
				'school-a',
				['leerling' => ['naam' => 'Test']],
				['userId' => 'admin']
			)
			->willReturn([
				'documentRef' => '123',
				'templateSlug' => 'report-card',
				'renderedAt' => '2026-09-25T00:00:00+00:00',
			]);

		$result = $this->controller->render();

		$this->assertEquals(200, $result->getStatus());
		$this->assertEquals('123', $result->getData()['documentRef']);

	}//end testRenderReturnsDocumentRefOnSuccess()

	/**
	 * A 404 from the service (unknown slug) is propagated as a 404 JSON error.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/filinq-configurable-report-templates/specs/report-render-api/spec.md#scenario-unknown-slug-returns-404
	 */
	public function testRenderPropagates404FromService(): void {
		$this->request->method('getParam')
			->willReturnCallback(function (string $key, $default = null) {
				$values = ['templateSlug' => 'does-not-exist'];

				return $values[$key] ?? $default;
			});

		$this->renderService->method('render')
			->willThrowException(new Exception('No template found for namespace "learniq" and slug "does-not-exist".', 404));

		$result = $this->controller->render();

		$this->assertEquals(404, $result->getStatus());

	}//end testRenderPropagates404FromService()

	/**
	 * Empty items on the batch endpoint is a 400.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/filinq-configurable-report-templates/specs/report-render-api/spec.md#scenario-empty-items-array-is-rejected
	 */
	public function testRenderBatchRejectsEmptyItems(): void {
		$this->request->method('getParam')
			->willReturnCallback(function (string $key, $default = null) {
				$values = ['templateSlug' => 'report-card', 'items' => []];

				return $values[$key] ?? $default;
			});

		$this->renderService->expects($this->never())->method('renderBatch');

		$result = $this->controller->renderBatch();

		$this->assertEquals(400, $result->getStatus());

	}//end testRenderBatchRejectsEmptyItems()

	/**
	 * A successful batch render returns 200 with documentRef and count.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/filinq-configurable-report-templates/specs/report-render-api/spec.md#scenario-batch-render-produces-one-zip
	 */
	public function testRenderBatchReturnsDocumentRefAndCount(): void {
		$this->request->method('getParam')
			->willReturnCallback(function (string $key, $default = null) {
				$values = [
					'templateSlug' => 'report-card',
					'tenantId' => 'school-a',
					'items' => [['data' => ['a' => 1]], ['data' => ['a' => 2]]],
					'options' => [],
					'userId' => null,
				];

				return array_key_exists($key, $values) ? $values[$key] : $default;
			});

		$this->renderService->expects($this->once())
			->method('renderBatch')
			->willReturn(['documentRef' => '456', 'count' => 2, 'failures' => []]);

		$result = $this->controller->renderBatch();

		$this->assertEquals(200, $result->getStatus());
		$this->assertEquals(2, $result->getData()['count']);

	}//end testRenderBatchReturnsDocumentRefAndCount()
}//end class
