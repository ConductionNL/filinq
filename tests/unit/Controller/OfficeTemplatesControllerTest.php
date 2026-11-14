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
use OCA\Filinq\Kernel\Contracts\TemplateServiceInterface;
use OCA\Filinq\Service\DataResolverService;
use OCA\Filinq\Service\OfficeTemplate\OfficeSourceStore;
use OCA\Filinq\Service\OfficeTemplate\OfficeTemplateRenderer;
use OCA\Filinq\Service\OfficeTemplate\OfficeTemplateService;
use OCP\AppFramework\Http\DataDownloadResponse;
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

	private TemplateServiceInterface&MockObject $templates;

	private OfficeTemplateRenderer&MockObject $renderer;

	private OfficeSourceStore&MockObject $store;

	private DataResolverService&MockObject $data;

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
		$this->templates = $this->createMock(TemplateServiceInterface::class);
		$this->renderer = $this->createMock(OfficeTemplateRenderer::class);
		$this->renderer->method('isOffice')->willReturnCallback(static fn (array $template): bool => ($template['templateType'] ?? 'twig') === 'office');
		$this->store = $this->createMock(OfficeSourceStore::class);
		$this->data = $this->createMock(DataResolverService::class);
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
		return new OfficeTemplatesController(
			'filinq',
			$this->request,
			$this->service,
			new TemplateRequestHandler($this->createMock(LoggerInterface::class)),
			$this->session,
			$this->templates,
			$this->renderer,
			$this->store,
			$this->data
		);
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
		$controller = new OfficeTemplatesController(
			'filinq',
			$this->request,
			$this->service,
			new TemplateRequestHandler($this->createMock(LoggerInterface::class)),
			$session,
			$this->templates,
			$this->renderer,
			$this->store,
			$this->data
		);

		$this->assertSame(401, $controller->create()->getStatus());
	}//end testWithoutASessionTheAnswerIs401()
	/**
	 * A new source reaches the service with its changelog and answers 200.
	 *
	 * @return void
	 */
	public function testANewSourceIsSavedAsAVersion(): void {
		$this->withUpload();
		$this->request->method('getParam')->with('changelog')->willReturn('Nieuw logo');
		$this->service->expects($this->once())->method('replaceSource')->with('t-1', 'brief.docx', 'docx-bytes', 'Nieuw logo')
			->willReturn(['template' => ['id' => 't-1'], 'converted' => false, 'tagReport' => []]);

		$response = $this->controller()->newSource(id: 't-1');

		$this->assertSame(200, $response->getStatus());
		$this->assertSame(['template' => ['id' => 't-1'], 'converted' => false, 'tagReport' => []], $response->getData());
	}//end testANewSourceIsSavedAsAVersion()

	/**
	 * A locked template answers the service's 409.
	 *
	 * @return void
	 */
	public function testALockedTemplateRefusesANewSource(): void {
		$this->withUpload();
		$this->service->method('replaceSource')->willThrowException(new Exception('Template is locked by another user', 409));

		$response = $this->controller()->newSource(id: 't-1');

		$this->assertSame([409, ['error' => 'Template is locked by another user']], [$response->getStatus(), $response->getData()]);
	}//end testALockedTemplateRefusesANewSource()

	/**
	 * The source downloads as a DOCX after the template is read with the
	 * caller's access; a Twig template has none.
	 *
	 * @return void
	 */
	public function testTheSourceDownloadsOnlyForAnOfficeTemplate(): void {
		$this->templates->method('getTemplate')->willReturnMap([
			['t-1', ['id' => 't-1', 'name' => 'Brief: aan/u', 'templateType' => 'office', 'sourceFileId' => 8]],
			['t-2', ['id' => 't-2', 'content' => '{{ x }}']],
		]);
		$this->store->expects($this->once())->method('read')->with(8)->willReturn('docx-bytes');

		$download = $this->controller()->source(id: 't-1');
		$this->assertInstanceOf(DataDownloadResponse::class, $download);
		$this->assertSame('docx-bytes', $download->getData());
		$this->assertStringContainsString('Brief aanu.docx', $download->getHeaders()['Content-Disposition']);

		$twig = $this->controller()->source(id: 't-2');
		$this->assertSame([409, ['error' => 'This is not an office template']], [$twig->getStatus(), $twig->getData()]);
	}//end testTheSourceDownloadsOnlyForAnOfficeTemplate()

	/**
	 * A template the caller may not read gives no file.
	 *
	 * @return void
	 */
	public function testAnUnreadableTemplateGivesNoSource(): void {
		$this->templates->method('getTemplate')->willThrowException(new Exception('Template not found', 404));
		$this->store->expects($this->never())->method('read');

		$this->assertSame(404, $this->controller()->source(id: 't-9')->getStatus());
	}//end testAnUnreadableTemplateGivesNoSource()

	/**
	 * The preview is the cascade's PDF of the template filled with the
	 * resolved data, with the warnings in a header.
	 *
	 * @return void
	 */
	public function testThePreviewIsTheFilledPdf(): void {
		$template = ['id' => 't-1', 'templateType' => 'office', 'sourceFileId' => 8];
		$this->templates->method('getTemplate')->willReturn($template);
		$this->data->expects($this->once())->method('resolve')->with([['register' => 'r', 'schema' => 's', 'id' => '1']], [], ['x' => 1])
			->willReturn(['data' => ['naam' => 'Jan'], 'errors' => [], 'warnings' => ['w1']]);
		$this->renderer->expects($this->once())->method('render')->with($template, ['naam' => 'Jan'], 'pdf')
			->willReturn(['html' => '', 'content' => '%PDF', 'warnings' => ['w2'], 'fragments' => []]);

		$response = $this->controller()->preview(id: 't-1', dataRefs: [['register' => 'r', 'schema' => 's', 'id' => '1']], adHocData: ['x' => 1]);

		$this->assertInstanceOf(DataDownloadResponse::class, $response);
		$this->assertSame('%PDF', $response->getData());
		$this->assertSame('application/pdf', $response->getHeaders()['Content-Type']);
		$this->assertStringStartsWith('inline;', $response->getHeaders()['Content-Disposition']);
		$this->assertSame('["w1","w2"]', $response->getHeaders()['X-Filinq-Warnings']);
	}//end testThePreviewIsTheFilledPdf()

	/**
	 * A preview without data resolves nothing.
	 *
	 * @return void
	 */
	public function testAPreviewWithoutDataResolvesNothing(): void {
		$this->templates->method('getTemplate')->willReturn(['id' => 't-1', 'templateType' => 'office']);
		$this->data->expects($this->never())->method('resolve');
		$this->renderer->expects($this->once())->method('render')->with($this->anything(), [], 'pdf')
			->willReturn(['html' => '', 'content' => '%PDF', 'warnings' => [], 'fragments' => []]);

		$this->assertSame('%PDF', $this->controller()->preview(id: 't-1')->getData());
	}//end testAPreviewWithoutDataResolvesNothing()

	/**
	 * The field mapping reaches the service; a refusal answers its code.
	 *
	 * @return void
	 */
	public function testTheFieldMapIsSet(): void {
		$this->service->expects($this->exactly(2))->method('setFieldMap')
			->willReturnCallback(static function (string $id, array $fieldMap): array {
				if ($fieldMap === ['x' => 'y']) {
					throw new Exception('The template has no merge tag "x"', 400);
				}

				return ['id' => $id, 'fieldMap' => $fieldMap];
			});

		$this->assertSame(['id' => 't-1', 'fieldMap' => ['naam_aanvrager' => 'aanvrager.naam']], $this->controller()->fieldMap(id: 't-1', fieldMap: ['naam_aanvrager' => 'aanvrager.naam'])->getData());
		$this->assertSame(400, $this->controller()->fieldMap(id: 't-1', fieldMap: ['x' => 'y'])->getStatus());
	}//end testTheFieldMapIsSet()
}//end class
