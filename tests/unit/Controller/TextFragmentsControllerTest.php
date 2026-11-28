<?php

/**
 * The text fragment endpoints pass the request to the fragment service and
 * answer its result or its refusal.
 *
 * @category  Test
 * @package   OCA\Filinq\Tests\Unit\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-11-25-office-template-authoring/specs/office-template-authoring/spec.md#requirement-reusable-text-fragments-bouwstenen-resolve-in-templates-req-ddota-004
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use Exception;
use OCA\Filinq\Controller\TemplateRequestHandler;
use OCA\Filinq\Controller\TextFragmentsController;
use OCA\Filinq\Service\OfficeTemplate\TextFragmentService;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \OCA\Filinq\Controller\TextFragmentsController
 * @uses   \OCA\Filinq\Controller\TemplateRequestHandler
 */
class TextFragmentsControllerTest extends TestCase {

	private IRequest&MockObject $request;

	private TextFragmentService&MockObject $fragments;

	private ?IUser $user;

	/**
	 * A signed-in request.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->request = $this->createMock(IRequest::class);
		$this->fragments = $this->createMock(TextFragmentService::class);
		$this->user = $this->createMock(IUser::class);
	}//end setUp()

	/**
	 * A list passes the known filters and a bounded page.
	 *
	 * @return void
	 */
	public function testAListPassesFiltersAndABoundedPage(): void {
		$this->request->method('getParam')->willReturnCallback(static fn (string $key): mixed => ['namespace' => 'filinq', 'category' => '', 'slug' => 'kop'][$key] ?? null);
		$this->fragments->expects($this->once())->method('list')->with(['namespace' => 'filinq', 'slug' => 'kop'], 200, 0)->willReturn(['results' => [], 'total' => 0]);

		$response = $this->controller()->index(_limit: 5000, _offset: -3);

		$this->assertSame([200, ['results' => [], 'total' => 0]], [$response->getStatus(), $response->getData()]);
	}//end testAListPassesFiltersAndABoundedPage()

	/**
	 * Create answers 201 with the body as data; a taken slug answers 409.
	 *
	 * @return void
	 */
	public function testCreateAnswersCreatedOrTheRefusal(): void {
		$this->request->method('getParams')->willReturn(['name' => 'Kop', 'slug' => 'kop', '_route' => 'x']);
		$this->fragments->expects($this->exactly(2))->method('create')->with(['name' => 'Kop', 'slug' => 'kop'])
			->willReturnOnConsecutiveCalls(['id' => 'f-1'], $this->throwException(new Exception('taken', 409)));

		$created = $this->controller()->create();
		$refused = $this->controller()->create();

		$this->assertSame([201, ['id' => 'f-1']], [$created->getStatus(), $created->getData()]);
		$this->assertSame([409, ['error' => 'taken']], [$refused->getStatus(), $refused->getData()]);
	}//end testCreateAnswersCreatedOrTheRefusal()

	/**
	 * Show, update and delete reach the service with the id; update drops
	 * an id in the body.
	 *
	 * @return void
	 */
	public function testShowUpdateAndDeleteUseTheId(): void {
		$this->request->method('getParams')->willReturn(['id' => 'other', 'content' => 'Nieuw']);
		$this->fragments->expects($this->once())->method('get')->with('f-1')->willReturn(['id' => 'f-1']);
		$this->fragments->expects($this->once())->method('update')->with('f-1', ['content' => 'Nieuw'])->willReturn(['id' => 'f-1', 'content' => 'Nieuw']);
		$this->fragments->expects($this->once())->method('delete')->with('f-1');

		$this->assertSame(['id' => 'f-1'], $this->controller()->show(id: 'f-1')->getData());
		$this->assertSame(['id' => 'f-1', 'content' => 'Nieuw'], $this->controller()->update(id: 'f-1')->getData());
		$this->assertSame(['deleted' => true], $this->controller()->destroy(id: 'f-1')->getData());
	}//end testShowUpdateAndDeleteUseTheId()

	/**
	 * Nobody signed in is a 401 and reaches no service.
	 *
	 * @return void
	 */
	public function testNobodySignedInIsRefused(): void {
		$this->user = null;
		$this->fragments->expects($this->never())->method('delete');

		$this->assertSame(401, $this->controller()->destroy(id: 'f-1')->getStatus());
	}//end testNobodySignedInIsRefused()

	/**
	 * The controller.
	 *
	 * @return TextFragmentsController
	 */
	private function controller(): TextFragmentsController {
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($this->user);

		return new TextFragmentsController('filinq', $this->request, $this->fragments, new TemplateRequestHandler($this->createMock(LoggerInterface::class)), $session);
	}//end controller()
}//end class
