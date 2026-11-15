<?php

/**
 * Characterization tests for CorrespondenceController::generate() and jobStatus()
 *
 * Pins the download/JSON split per output format, the filename extension
 * rewrite, the session user in the options, job ownership and the
 * exception-to-status mapping.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-correspondence-rest-endpoint
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\CorrespondenceController;
use OCA\Filinq\Kernel\Contracts\CorrespondenceServiceInterface;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Characterization of single correspondence generation and job polling.
 *
 * @covers \OCA\Filinq\Controller\CorrespondenceController
 * @uses   \OCA\Filinq\Controller\AnswersNotAuthenticated
 * @uses   \OCA\Filinq\Service\ReadsCurrentUserId
 */
class CorrespondenceControllerGenerateTraceTest extends TestCase {

	private CorrespondenceServiceInterface&MockObject $corr;

	private LoggerInterface&MockObject $logger;

	private IRequest&MockObject $request;

	/**
	 * Fresh doubles.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->corr = $this->createMock(CorrespondenceServiceInterface::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->request = $this->createMock(IRequest::class);

	}//end setUp()

	/**
	 * A controller whose session is the given user (or nobody).
	 *
	 * @param string|null $uid The session user id, or null for no session.
	 *
	 * @return CorrespondenceController The controller.
	 */
	private function controller(?string $uid = 'anna'): CorrespondenceController {
		$session = $this->createMock(IUserSession::class);
		if ($uid === null) {
			$session->method('getUser')->willReturn(null);
		} else {
			$user = $this->createMock(IUser::class);
			$user->method('getUID')->willReturn($uid);
			$session->method('getUser')->willReturn($user);
		}

		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnArgument(0);

		return new CorrespondenceController(
			appName: 'filinq',
			request: $this->request,
			corrSvc: $this->corr,
			userSession: $session,
			logger: $this->logger,
			l10n: $l10n,
		);

	}//end controller()

	/**
	 * Feed the request these params; anything else answers its default.
	 *
	 * @param array<string, mixed> $params The request params.
	 *
	 * @return void
	 */
	private function params(array $params): void {
		$this->request->method('getParam')->willReturnCallback(
			static fn (string $key, mixed $default = null): mixed => (array_key_exists($key, $params) === true ? $params[$key] : $default)
		);

	}//end params()

	/**
	 * A PDF result is a download; a filename without the extension gets it.
	 *
	 * @return void
	 */
	public function testAPdfIsADownloadWithThePdfExtension(): void {
		$this->params(
			[
				'templateId' => 'tpl-1',
				'dataRefs' => [['register' => 'r', 'schema' => 's', 'id' => '1']],
				'options' => ['format' => 'pdf', 'userId' => 'mallory'],
				'filename' => 'brief.txt',
			]
		);
		$this->corr->expects($this->once())
			->method('generate')
			->with(
				'tpl-1',
				[['register' => 'r', 'schema' => 's', 'id' => '1']],
				['format' => 'pdf', 'userId' => 'anna']
			)
			->willReturn(['format' => 'pdf', 'content' => '%PDF-1.7', 'warnings' => []]);

		$response = $this->controller()->generate();

		$this->assertInstanceOf(DataDownloadResponse::class, $response);
		$this->assertSame('%PDF-1.7', $response->getData());
		$this->assertSame('application/pdf', $response->getHeaders()['Content-Type']);
		$this->assertStringContainsString('filename="brief.pdf"', $response->getHeaders()['Content-Disposition']);

	}//end testAPdfIsADownloadWithThePdfExtension()

	/**
	 * A DOCX result keeps a filename already ending in .docx and sends the Word content type.
	 *
	 * @return void
	 */
	public function testADocxKeepsItsFilenameAndContentType(): void {
		$this->params(
			[
				'templateId' => 'tpl-1',
				'dataRefs' => ['x'],
				'options' => 'not-an-array',
				'filename' => 'brief.docx',
			]
		);
		$this->corr->expects($this->once())
			->method('generate')
			->with('tpl-1', ['x'], ['userId' => 'anna'])
			->willReturn(['format' => 'docx', 'content' => 'PK', 'warnings' => []]);

		$response = $this->controller()->generate();

		$this->assertInstanceOf(DataDownloadResponse::class, $response);
		$this->assertSame(
			'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			$response->getHeaders()['Content-Type']
		);
		$this->assertStringContainsString('filename="brief.docx"', $response->getHeaders()['Content-Disposition']);

	}//end testADocxKeepsItsFilenameAndContentType()

	/**
	 * The default filename is rewritten to .docx when the result is a DOCX.
	 *
	 * @return void
	 */
	public function testTheDefaultFilenameFollowsTheFormat(): void {
		$this->params(['templateId' => 'tpl-1', 'dataRefs' => ['x']]);
		$this->corr->method('generate')->willReturn(['format' => 'docx', 'content' => 'PK', 'warnings' => []]);

		$response = $this->controller()->generate();

		$this->assertInstanceOf(DataDownloadResponse::class, $response);
		$this->assertStringContainsString('filename="correspondence.docx"', $response->getHeaders()['Content-Disposition']);

	}//end testTheDefaultFilenameFollowsTheFormat()

	/**
	 * An HTML result is JSON carrying the content, the format and the warnings.
	 *
	 * @return void
	 */
	public function testHtmlIsAnsweredAsJson(): void {
		$this->params(['templateId' => 'tpl-1', 'dataRefs' => ['x']]);
		$this->corr->method('generate')->willReturn(
			['format' => 'html', 'content' => '<p>Hi</p>', 'warnings' => ['missing field']]
		);

		$response = $this->controller()->generate();

		$this->assertInstanceOf(JSONResponse::class, $response);
		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertSame(
			['content' => '<p>Hi</p>', 'format' => 'html', 'warnings' => ['missing field']],
			$response->getData()
		);

	}//end testHtmlIsAnsweredAsJson()

	/**
	 * A service failure carrying a 4xx code keeps it, and is logged.
	 *
	 * @return void
	 */
	public function testAFailureWithAClientCodeKeepsIt(): void {
		$this->params(['templateId' => 'tpl-1', 'dataRefs' => ['x']]);
		$this->corr->method('generate')->willThrowException(new RuntimeException('Template not found', 404));
		$this->logger->expects($this->once())
			->method('error')
			->with('Correspondence generation failed: Template not found', $this->anything());

		$response = $this->controller()->generate();

		$this->assertSame(Http::STATUS_NOT_FOUND, $response->getStatus());
		$this->assertSame(['error' => 'Template not found'], $response->getData());

	}//end testAFailureWithAClientCodeKeepsIt()

	/**
	 * A service failure without an HTTP code is a 500.
	 *
	 * @return void
	 */
	public function testAFailureWithoutACodeIsA500(): void {
		$this->params(['templateId' => 'tpl-1', 'dataRefs' => ['x']]);
		$this->corr->method('generate')->willThrowException(new RuntimeException('boom', 7));

		$response = $this->controller()->generate();

		$this->assertSame(Http::STATUS_INTERNAL_SERVER_ERROR, $response->getStatus());
		$this->assertSame(['error' => 'boom'], $response->getData());

	}//end testAFailureWithoutACodeIsA500()

	/**
	 * The owner polling their own job sees the status with the job id added.
	 *
	 * @return void
	 */
	public function testTheOwnerSeesTheirJob(): void {
		$this->corr->expects($this->once())
			->method('getJobStatus')
			->with('job-1')
			->willReturn(['ownerUserId' => 'anna', 'status' => 'done']);

		$response = $this->controller()->jobStatus(jobId: 'job-1');

		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertSame(['ownerUserId' => 'anna', 'status' => 'done', 'jobId' => 'job-1'], $response->getData());

	}//end testTheOwnerSeesTheirJob()

	/**
	 * Another user's job is refused with 403.
	 *
	 * @return void
	 */
	public function testAnotherUsersJobIsForbidden(): void {
		$this->corr->method('getJobStatus')->willReturn(['ownerUserId' => 'bob', 'status' => 'done']);

		$response = $this->controller()->jobStatus(jobId: 'job-1');

		$this->assertSame(Http::STATUS_FORBIDDEN, $response->getStatus());
		$this->assertSame(['error' => 'This job belongs to another user'], $response->getData());

	}//end testAnotherUsersJobIsForbidden()

	/**
	 * A job status carrying no owner is refused too: the check fails closed.
	 *
	 * @return void
	 */
	public function testAnOwnerlessJobIsForbidden(): void {
		$this->corr->method('getJobStatus')->willReturn(['status' => 'done']);

		$response = $this->controller()->jobStatus(jobId: 'job-1');

		$this->assertSame(Http::STATUS_FORBIDDEN, $response->getStatus());

	}//end testAnOwnerlessJobIsForbidden()

	/**
	 * No session: 401 before the service is asked.
	 *
	 * @return void
	 */
	public function testJobStatusWithoutASessionIs401(): void {
		$this->corr->expects($this->never())->method('getJobStatus');

		$response = $this->controller(uid: null)->jobStatus(jobId: 'job-1');

		$this->assertSame(Http::STATUS_UNAUTHORIZED, $response->getStatus());
		$this->assertSame(['error' => 'Not authenticated'], $response->getData());

	}//end testJobStatusWithoutASessionIs401()

	/**
	 * A failing status lookup is mapped through the exception handler.
	 *
	 * @return void
	 */
	public function testAFailingStatusLookupIsMapped(): void {
		$this->corr->method('getJobStatus')->willThrowException(new RuntimeException('cache down', 503));

		$response = $this->controller()->jobStatus(jobId: 'job-1');

		$this->assertSame(Http::STATUS_SERVICE_UNAVAILABLE, $response->getStatus());
		$this->assertSame(['error' => 'cache down'], $response->getData());

	}//end testAFailingStatusLookupIsMapped()
}//end class
