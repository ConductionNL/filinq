<?php

/**
 * Tests for DocumentDraftController.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use Exception;
use OCA\Filinq\Controller\DocumentDraftController;
use OCA\Filinq\Controller\Support\ControllerFeedback;
use OCA\Filinq\Service\Draft\DocumentDraftService;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * The two draft routes: their parameters, their answers and their failures.
 *
 * @covers \OCA\Filinq\Controller\DocumentDraftController
 * @uses   \OCA\Filinq\Controller\Support\ControllerFeedback
 */
class DocumentDraftControllerTest extends TestCase {

	private IRequest&MockObject $request;

	private DocumentDraftService&MockObject $drafts;

	private IUserSession&MockObject $session;

	/**
	 * Request parameters by name.
	 *
	 * @var array<string, mixed>
	 */
	private array $params = [];

	protected function setUp(): void {
		$this->request = $this->createMock(IRequest::class);
		$this->request->method('getParam')->willReturnCallback(fn (string $name, mixed $default = null): mixed => ($this->params[$name] ?? $default));
		$this->drafts  = $this->createMock(DocumentDraftService::class);
		$this->session = $this->createMock(IUserSession::class);
	}//end setUp()

	private function controller(bool $signedIn = true): DocumentDraftController {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');
		$this->session->method('getUser')->willReturn($signedIn === true ? $user : null);
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnCallback(static fn (string $text): string => $text);

		return new DocumentDraftController(
			'filinq',
			$this->request,
			$this->drafts,
			$this->session,
			new ControllerFeedback(l10n: $l10n, logger: $this->createMock(LoggerInterface::class))
		);
	}//end controller()

	public function testCreatePassesTheRequestAndAnswers201(): void {
		$this->params = [
			'templateId' => 'template-1',
			'dataRefs' => [['register' => 'r', 'schema' => 's', 'id' => 'o']],
			'options' => ['format' => 'pdf'],
			'origin' => 'correspondence',
		];
		$this->drafts->expects($this->once())->method('create')
			->with('template-1', [['register' => 'r', 'schema' => 's', 'id' => 'o']], ['format' => 'pdf'], 'correspondence')
			->willReturn(['id' => 'draft-1', 'stage' => 'editing']);

		$response = $this->controller()->create();

		$this->assertSame(201, $response->getStatus());
		$this->assertSame(['id' => 'draft-1', 'stage' => 'editing'], $response->getData());
	}//end testCreatePassesTheRequestAndAnswers201()

	public function testCreateDefaultsToADocumentDraftAndIgnoresNonArrayParameters(): void {
		$this->params = ['templateId' => 'template-1', 'dataRefs' => 'nope', 'options' => 5];
		$this->drafts->expects($this->once())->method('create')->with('template-1', [], [], 'document')->willReturn(['id' => 'd']);

		$this->assertSame(201, $this->controller()->create()->getStatus());
	}//end testCreateDefaultsToADocumentDraftAndIgnoresNonArrayParameters()

	public function testCreateWithoutTemplateIsA400(): void {
		$this->drafts->expects($this->never())->method('create');

		$response = $this->controller()->create();

		$this->assertSame(400, $response->getStatus());
		$this->assertSame(['error' => 'templateId is required'], $response->getData());
	}//end testCreateWithoutTemplateIsA400()

	public function testCreatePassesAPlainLanguageRefusalOnAs409(): void {
		$this->params = ['templateId' => 'template-1'];
		$this->drafts->method('create')->willThrowException(new Exception(DocumentDraftService::PLAIN_LANGUAGE_REFUSAL, 409));

		$response = $this->controller()->create();

		$this->assertSame(409, $response->getStatus());
		$this->assertSame(['error' => 'This template has a plain-language version. Generate it directly.'], $response->getData());
	}//end testCreatePassesAPlainLanguageRefusalOnAs409()

	public function testAnUnexpectedFailureIsA500WithoutItsMessage(): void {
		$this->params = ['templateId' => 'template-1'];
		$this->drafts->method('create')->willThrowException(new Exception('SQLSTATE secret detail'));

		$response = $this->controller()->create();

		$this->assertSame(500, $response->getStatus());
		$this->assertSame(['error' => 'The draft could not be processed'], $response->getData());
	}//end testAnUnexpectedFailureIsA500WithoutItsMessage()

	public function testSignedOutCallersAreRefused(): void {
		$this->drafts->expects($this->never())->method('create');
		$this->drafts->expects($this->never())->method('finish');
		$controller = $this->controller(signedIn: false);

		$this->assertSame(401, $controller->create()->getStatus());
		$this->assertSame(401, $controller->finish(id: 'draft-1')->getStatus());
	}//end testSignedOutCallersAreRefused()

	public function testFinishAnswersThePdfAsADownloadForTheSessionUser(): void {
		$this->drafts->expects($this->once())->method('finish')->with('draft-1', 'alice')
			->willReturn(['content' => '%PDF-1.7', 'format' => 'pdf', 'output' => ['mode' => 'return'], 'warnings' => [], 'draftId' => 'draft-1', 'recordId' => 'rec-1']);

		$response = $this->controller()->finish(id: 'draft-1');

		$this->assertInstanceOf(DataDownloadResponse::class, $response);
		$this->assertSame('%PDF-1.7', $response->getData());
		$headers = $response->getHeaders();
		$this->assertSame('draft-1', $headers['X-Filinq-Draft-Id']);
		$this->assertSame('rec-1', $headers['X-Filinq-Record-Id']);
	}//end testFinishAnswersThePdfAsADownloadForTheSessionUser()

	public function testFinishAnswersStoredFileRefsInFilesMode(): void {
		$this->drafts->method('finish')
			->willReturn(['content' => '%PDF', 'format' => 'pdf', 'output' => ['mode' => 'files', 'fileId' => 7, 'path' => '/a.pdf', 'name' => 'a.pdf'], 'warnings' => [], 'draftId' => 'draft-1', 'recordId' => 'rec-1']);

		$response = $this->controller()->finish(id: 'draft-1');

		$this->assertInstanceOf(JSONResponse::class, $response);
		$this->assertSame(
			['draftId' => 'draft-1', 'recordId' => 'rec-1', 'format' => 'pdf', 'warnings' => [], 'fileId' => 7, 'path' => '/a.pdf', 'name' => 'a.pdf'],
			$response->getData()
		);
	}//end testFinishAnswersStoredFileRefsInFilesMode()

	public function testFinishAnswersHtmlAsJson(): void {
		$this->drafts->method('finish')
			->willReturn(['content' => '<p>x</p>', 'format' => 'html', 'output' => ['mode' => 'return'], 'warnings' => ['w'], 'draftId' => 'draft-1', 'recordId' => '']);

		$response = $this->controller()->finish(id: 'draft-1');

		$this->assertInstanceOf(JSONResponse::class, $response);
		$this->assertSame('<p>x</p>', $response->getData()['content']);
		$this->assertSame(['w'], $response->getData()['warnings']);
	}//end testFinishAnswersHtmlAsJson()

	public function testFinishOfAnotherUsersDraftIs404(): void {
		$this->drafts->method('finish')->willThrowException(new Exception('Draft not found', 404));

		$response = $this->controller()->finish(id: 'draft-1');

		$this->assertSame(404, $response->getStatus());
		$this->assertSame(['error' => 'Draft not found'], $response->getData());
	}//end testFinishOfAnotherUsersDraftIs404()
}//end class
