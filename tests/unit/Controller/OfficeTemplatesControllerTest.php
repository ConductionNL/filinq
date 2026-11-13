<?php

/**
 * `POST /api/templates/office` answers 201 with the created template, 422
 * with the reason for a refused file, 400 without an upload and 401 without
 * a session.
 *
 * @category  Test
 * @package   OCA\Filinq\Tests\Unit\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use Exception;
use OCA\Filinq\Controller\OfficeTemplatesController;
use OCA\Filinq\Controller\TemplateRequestHandler;
use OCA\Filinq\Exception\OfficeSourceRefusedException;
use OCA\Filinq\Service\OfficeTemplate\OfficeTemplateService;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \OCA\Filinq\Controller\OfficeTemplatesController
 * @uses   \OCA\Filinq\Controller\TemplateRequestHandler
 * @uses   \OCA\Filinq\Exception\OfficeSourceRefusedException
 */
class OfficeTemplatesControllerTest extends TestCase {

	private IRequest&MockObject $request;

	private OfficeTemplateService&MockObject $service;

	private IUserSession&MockObject $session;

	private string $upload;

	/**
	 * A signed-in request whose `file` is a readable upload.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->request = $this->createMock(IRequest::class);
		$this->service = $this->createMock(OfficeTemplateService::class);
		$this->session = $this->createMock(IUserSession::class);
		$this->session->method('getUser')->willReturn($this->createMock(IUser::class));
		$this->upload = (string) tempnam(sys_get_temp_dir(), 'filinq-upload-');
		file_put_contents($this->upload, 'docx-bytes');
	}//end setUp()

	/**
	 * Remove the upload.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		@unlink($this->upload);
	}//end tearDown()

	/**
	 * The controller.
	 *
	 * @return OfficeTemplatesController
	 */
	private function controller(): OfficeTemplatesController {
		return new OfficeTemplatesController('filinq', $this->request, $this->service, new TemplateRequestHandler($this->createMock(LoggerInterface::class)), $this->session);
	}//end controller()

	/**
	 * Hand the upload over as `file`.
	 *
	 * @return void
	 */
	private function withUpload(): void {
		$this->request->method('getUploadedFile')->with('file')->willReturn(['name' => 'brief.docx', 'tmp_name' => $this->upload, 'error' => UPLOAD_ERR_OK]);
		$this->request->method('getParams')->willReturn(['name' => 'Brief', 'namespace' => 'filinq', '_route' => 'filinq.officeTemplates.create']);
	}//end withUpload()

	/**
	 * The file and the form fields reach the service; its result answers 201.
	 *
	 * @return void
	 */
	public function testAnUploadCreatesATemplate(): void {
		$this->withUpload();
		$this->service->expects($this->once())->method('create')
			->with(['name' => 'Brief', 'namespace' => 'filinq'], 'brief.docx', 'docx-bytes')
			->willReturn(['template' => ['id' => 'u1'], 'converted' => false, 'tagReport' => []]);

		$response = $this->controller()->create();

		$this->assertSame(201, $response->getStatus());
		$this->assertSame(['template' => ['id' => 'u1'], 'converted' => false, 'tagReport' => []], $response->getData());
	}//end testAnUploadCreatesATemplate()

	/**
	 * A refused file answers 422 with the reason and the unknown tags.
	 *
	 * @return void
	 */
	public function testARefusedFileAnswers422WithTheReason(): void {
		$this->withUpload();
		$this->service->method('create')->willThrowException(new OfficeSourceRefusedException('Unknown tags: x.', OfficeSourceRefusedException::REASON_UNKNOWN_TAGS, ['x']));

		$response = $this->controller()->create();

		$this->assertSame(422, $response->getStatus());
		$this->assertSame(['error' => 'Unknown tags: x.', 'reason' => 'unknownTags', 'unknownTags' => ['x']], $response->getData());
	}//end testARefusedFileAnswers422WithTheReason()

	/**
	 * Another failure keeps its 4xx code.
	 *
	 * @return void
	 */
	public function testAMissingNameAnswers400(): void {
		$this->withUpload();
		$this->service->method('create')->willThrowException(new Exception('Name is required', 400));

		$response = $this->controller()->create();

		$this->assertSame(400, $response->getStatus());
		$this->assertSame(['error' => 'Name is required'], $response->getData());
	}//end testAMissingNameAnswers400()

	/**
	 * Without an intact upload the service is not called and the answer is 400.
	 *
	 * @return void
	 */
	public function testWithoutAnUploadTheAnswerIs400(): void {
		$this->request->method('getUploadedFile')->willReturnOnConsecutiveCalls(null, ['name' => 'b.docx', 'tmp_name' => $this->upload, 'error' => UPLOAD_ERR_PARTIAL]);
		$this->service->expects($this->never())->method('create');

		$this->assertSame(400, $this->controller()->create()->getStatus());
		$this->assertSame(400, $this->controller()->create()->getStatus());
	}//end testWithoutAnUploadTheAnswerIs400()

	/**
	 * Without a session the answer is 401 and nothing is read.
	 *
	 * @return void
	 */
	public function testWithoutASessionTheAnswerIs401(): void {
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn(null);
		$this->request->expects($this->never())->method('getUploadedFile');
		$controller = new OfficeTemplatesController('filinq', $this->request, $this->service, new TemplateRequestHandler($this->createMock(LoggerInterface::class)), $session);

		$this->assertSame(401, $controller->create()->getStatus());
	}//end testWithoutASessionTheAnswerIs401()
}//end class
