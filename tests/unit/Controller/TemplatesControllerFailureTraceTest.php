<?php

/**
 * Characterization tests for TemplatesController error paths
 *
 * Pins the not-configured empty list, the error mapping of show/create/update/destroy, and a non-409 lock failure.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-10-09-case-documents-and-the-flat-list/specs/document-register/spec.md
 * @spec openspec/specs/template-management/spec.md
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use Exception;
use OCA\Filinq\Controller\TemplateRequestHandler;
use OCA\Filinq\Controller\TemplatesController;
use OCA\Filinq\Exception\RegisterNotConfiguredException;
use OCA\Filinq\Kernel\Contracts\TemplateServiceInterface;
use OCP\AppFramework\Http;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Characterization of the template CRUD endpoints' failure answers.
 *
 * @covers \OCA\Filinq\Controller\TemplatesController
 * @uses   \OCA\Filinq\Controller\TemplateRequestHandler
 */
class TemplatesControllerFailureTraceTest extends TestCase {

	private TemplateServiceInterface&MockObject $templates;

	private IRequest&MockObject $request;

	private LoggerInterface&MockObject $handlerLogger;

	private LoggerInterface&MockObject $logger;

	private TemplatesController $controller;

	/**
	 * A controller with a signed-in session and a real request handler.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->templates = $this->createMock(TemplateServiceInterface::class);
		$this->request = $this->createMock(IRequest::class);
		$this->handlerLogger = $this->createMock(LoggerInterface::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('anna');
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);

		$this->controller = new TemplatesController(
			'filinq',
			$this->request,
			$this->templates,
			new TemplateRequestHandler($this->handlerLogger),
			$session,
			$this->logger
		);

	}//end setUp()

	/**
	 * An unconfigured register is an empty 200 list flagged notConfigured, logged at info.
	 *
	 * @return void
	 */
	public function testAnUnconfiguredRegisterIsAnEmptyList(): void {
		$this->request->method('getParam')->willReturnArgument(1);
		$this->templates->method('getTemplates')->willThrowException(new RegisterNotConfiguredException('no schema'));
		$this->logger->expects($this->once())
			->method('info')
			->with('Templates list requested but register/schema is not configured: no schema');
		$this->handlerLogger->expects($this->never())->method('error');

		$response = $this->controller->index();

		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertSame(['results' => [], 'total' => 0, 'notConfigured' => true], $response->getData());

	}//end testAnUnconfiguredRegisterIsAnEmptyList()

	/**
	 * A failing show keeps the exception's HTTP code and is logged with its prefix.
	 *
	 * @return void
	 */
	public function testAFailingShowIsMapped(): void {
		$this->templates->method('getTemplate')->with('tpl-1')->willThrowException(new Exception('Template not found', 404));
		$this->handlerLogger->expects($this->once())
			->method('error')
			->with('Failed to get template: Template not found', $this->anything());

		$response = $this->controller->show(id: 'tpl-1');

		$this->assertSame(Http::STATUS_NOT_FOUND, $response->getStatus());
		$this->assertSame(['error' => 'Template not found'], $response->getData());

	}//end testAFailingShowIsMapped()

	/**
	 * A failing create is mapped; the framework _route param never reaches the service.
	 *
	 * @return void
	 */
	public function testAFailingCreateIsMapped(): void {
		$this->request->method('getParams')->willReturn(['_route' => 'x', 'name' => 'Brief']);
		$this->templates->expects($this->once())
			->method('createTemplate')
			->with(['name' => 'Brief'])
			->willThrowException(new Exception('name taken', 409));
		$this->handlerLogger->expects($this->once())
			->method('error')
			->with('Failed to create template: name taken', $this->anything());

		$response = $this->controller->create();

		$this->assertSame(Http::STATUS_CONFLICT, $response->getStatus());
		$this->assertSame(['error' => 'name taken'], $response->getData());

	}//end testAFailingCreateIsMapped()

	/**
	 * An update strips id and _route from the body before handing it over.
	 *
	 * @return void
	 */
	public function testUpdateStripsIdFromTheBody(): void {
		$this->request->method('getParams')->willReturn(['_route' => 'x', 'id' => 'evil', 'name' => 'Brief 2']);
		$this->templates->expects($this->once())
			->method('updateTemplate')
			->with('tpl-1', ['name' => 'Brief 2'])
			->willReturn(['uuid' => 'tpl-1', 'name' => 'Brief 2']);

		$response = $this->controller->update(id: 'tpl-1');

		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertSame(['uuid' => 'tpl-1', 'name' => 'Brief 2'], $response->getData());

	}//end testUpdateStripsIdFromTheBody()

	/**
	 * A failing update without an HTTP code is a 500.
	 *
	 * @return void
	 */
	public function testAFailingUpdateIsA500(): void {
		$this->request->method('getParams')->willReturn([]);
		$this->templates->method('updateTemplate')->willThrowException(new Exception('db', 0));
		$this->handlerLogger->expects($this->once())
			->method('error')
			->with('Failed to update template: db', $this->anything());

		$response = $this->controller->update(id: 'tpl-1');

		$this->assertSame(Http::STATUS_INTERNAL_SERVER_ERROR, $response->getStatus());
		$this->assertSame(['error' => 'db'], $response->getData());

	}//end testAFailingUpdateIsA500()

	/**
	 * A failing delete keeps a 403 from the service.
	 *
	 * @return void
	 */
	public function testAFailingDestroyIsMapped(): void {
		$this->templates->method('deleteTemplate')->with('tpl-1')->willThrowException(new Exception('forbidden', 403));
		$this->handlerLogger->expects($this->once())
			->method('error')
			->with('Failed to delete template: forbidden', $this->anything());

		$response = $this->controller->destroy(id: 'tpl-1');

		$this->assertSame(Http::STATUS_FORBIDDEN, $response->getStatus());
		$this->assertSame(['error' => 'forbidden'], $response->getData());

	}//end testAFailingDestroyIsMapped()

	/**
	 * A lock failure other than 409 goes through the request handler, not the conflict answer.
	 *
	 * @return void
	 */
	public function testANonConflictLockFailureIsMapped(): void {
		$this->templates->method('acquireLock')->with('tpl-1', 'anna')->willThrowException(new Exception('{"lockedBy":"bob"}', 500));
		$this->handlerLogger->expects($this->once())
			->method('error')
			->with('Failed to lock template: {"lockedBy":"bob"}', $this->anything());

		$response = $this->controller->lock(id: 'tpl-1');

		$this->assertSame(Http::STATUS_INTERNAL_SERVER_ERROR, $response->getStatus());
		$this->assertSame(['error' => '{"lockedBy":"bob"}'], $response->getData());

	}//end testANonConflictLockFailureIsMapped()
}//end class
