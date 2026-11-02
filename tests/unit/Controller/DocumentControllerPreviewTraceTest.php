<?php

/**
 * Characterization tests for DocumentController::preview() and mergePreview()
 *
 * Pins the request reading, the arguments handed to the document service,
 * both responses and every error answer of the two preview endpoints.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/document-creatie-sjablonen/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use Exception;
use OCA\Filinq\Controller\DocumentController;
use OCA\Filinq\Exception\ErrorDetailsInterface;
use OCA\Filinq\Kernel\Contracts\DocumentServiceInterface;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Characterization of the two preview endpoints.
 *
 * @covers \OCA\Filinq\Controller\DocumentController
 */
class DocumentControllerPreviewTraceTest extends TestCase {

	private DocumentServiceInterface&MockObject $documents;

	private LoggerInterface&MockObject $logger;

	/**
	 * Fresh doubles.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->documents = $this->createMock(DocumentServiceInterface::class);
		$this->logger = $this->createMock(LoggerInterface::class);

	}//end setUp()

	/**
	 * Both endpoints answer 401 without a session user.
	 *
	 * @return void
	 */
	public function testNotAuthenticated(): void {
		$this->documents->expects($this->never())->method('generatePreview');
		$this->documents->expects($this->never())->method('generateMergePreview');
		$controller = $this->controller(params: ['templateId' => 't'], signedIn: false);

		foreach ([$controller->preview(), $controller->mergePreview()] as $response) {
			$this->assertSame(401, $response->getStatus());
			$this->assertSame(['error' => 'Not authenticated'], $response->getData());
		}

	}//end testNotAuthenticated()

	/**
	 * Without a template id both answer 400.
	 *
	 * @return void
	 */
	public function testMissingTemplateIdAnswers400(): void {
		$this->documents->expects($this->never())->method('generatePreview');
		$this->documents->expects($this->never())->method('generateMergePreview');
		$controller = $this->controller(params: ['templateId' => '', 'dataRefs' => [['id' => 1]]]);

		foreach ([$controller->preview(), $controller->mergePreview()] as $response) {
			$this->assertSame(400, $response->getStatus());
			$this->assertSame(['error' => 'templateId is required'], $response->getData());
		}

	}//end testMissingTemplateIdAnswers400()

	/**
	 * The preview hands the request through and answers its html and warnings only.
	 *
	 * @return void
	 */
	public function testPreviewAnswersHtmlAndWarnings(): void {
		$this->documents->expects($this->once())->method('generatePreview')
			->with('t1', [['register' => 'r', 'id' => 1]], ['huisstijlId' => 'h'])
			->willReturn(['html' => '<p>1</p>', 'warnings' => ['w'], 'extra' => 'dropped']);

		$response = $this->controller(params: ['templateId' => 't1', 'dataRefs' => [['register' => 'r', 'id' => 1]], 'options' => ['huisstijlId' => 'h']])->preview();

		$this->assertInstanceOf(JSONResponse::class, $response);
		$this->assertSame(200, $response->getStatus());
		$this->assertSame(['html' => '<p>1</p>', 'warnings' => ['w']], $response->getData());

	}//end testPreviewAnswersHtmlAndWarnings()

	/**
	 * The merge preview answers the service's whole result; non-array refs and options read as empty.
	 *
	 * @return void
	 */
	public function testMergePreviewAnswersTheWholeResult(): void {
		$this->documents->expects($this->once())->method('generateMergePreview')
			->with('t2', [], [])
			->willReturn(['html' => '<p/>', 'fields' => [['path' => 'a']], 'allFilled' => false]);

		$response = $this->controller(params: ['templateId' => 't2', 'dataRefs' => 'x', 'options' => 5])->mergePreview();

		$this->assertSame(200, $response->getStatus());
		$this->assertSame(['html' => '<p/>', 'fields' => [['path' => 'a']], 'allFilled' => false], $response->getData());

	}//end testMergePreviewAnswersTheWholeResult()

	/**
	 * The merge preview hands the request's refs and options through.
	 *
	 * @return void
	 */
	public function testMergePreviewHandsTheRequestThrough(): void {
		$this->documents->expects($this->once())->method('generateMergePreview')
			->with('t3', [['id' => 9]], ['listRefs' => []])
			->willReturn([]);

		$this->controller(params: ['templateId' => 't3', 'dataRefs' => [['id' => 9]], 'options' => ['listRefs' => []]])->mergePreview();

	}//end testMergePreviewHandsTheRequestThrough()

	/**
	 * Non-array refs and options also read as empty for the preview.
	 *
	 * @return void
	 */
	public function testPreviewEmptiesNonArrays(): void {
		$this->documents->expects($this->once())->method('generatePreview')
			->with('t1', [], [])
			->willReturn(['html' => '', 'warnings' => []]);

		$this->controller(params: ['templateId' => 't1', 'dataRefs' => 'x', 'options' => 'y'])->preview();

	}//end testPreviewEmptiesNonArrays()

	/**
	 * Failures keep an HTTP-range code, else 500, log, and add the error details.
	 *
	 * @return void
	 */
	public function testFailuresAreAnsweredThroughTheSharedHandler(): void {
		$detailed = new class('wizard refused', 422) extends Exception implements ErrorDetailsInterface {
			/**
			 * The details.
			 *
			 * @return array<string, mixed>
			 */
			public function getErrorDetails(): array {
				return ['unanswered' => ['q1']];
			}
		};
		$this->documents->method('generatePreview')->willThrowException($detailed);
		$this->documents->method('generateMergePreview')->willThrowException(new Exception('boom', 600));
		$this->logger->expects($this->exactly(2))->method('error');
		$controller = $this->controller(params: ['templateId' => 't1']);

		$preview = $controller->preview();
		$this->assertSame(422, $preview->getStatus());
		$this->assertSame(['error' => 'wizard refused', 'unanswered' => ['q1']], $preview->getData());

		$merge = $controller->mergePreview();
		$this->assertSame(500, $merge->getStatus());
		$this->assertSame(['error' => 'boom'], $merge->getData());

	}//end testFailuresAreAnsweredThroughTheSharedHandler()

	/**
	 * The controller over a request with $params.
	 *
	 * @param array<string, mixed> $params The request parameters.
	 * @param bool $signedIn Whether a session user exists.
	 *
	 * @return DocumentController The controller.
	 */
	private function controller(array $params, bool $signedIn = true): DocumentController {
		$request = $this->createMock(IRequest::class);
		$request->method('getParam')->willReturnCallback(
			fn (string $key, mixed $default = null): mixed => (array_key_exists($key, $params) === true ? $params[$key] : $default)
		);

		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnArgument(0);

		$session = $this->createMock(IUserSession::class);
		$user = null;
		if ($signedIn === true) {
			$user = $this->createMock(IUser::class);
		}

		$session->method('getUser')->willReturn($user);

		return new DocumentController(
			appName: 'filinq',
			request: $request,
			documentSvc: $this->documents,
			userSession: $session,
			logger: $this->logger,
			l10n: $l10n,
		);
	}//end controller()
}//end class
