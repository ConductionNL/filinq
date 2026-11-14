<?php

/**
 * Characterization tests for the dictionary CRUD endpoints of CustomDictionaryController
 *
 * Pins the shared guard (401, then 503), the exception-to-status mapping
 * (404, 403, 400, 500) and the upload edge cases of the import endpoint.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use InvalidArgumentException;
use LogicException;
use OCA\Filinq\Controller\CustomDictionaryController;
use OCA\Filinq\Kernel\Contracts\CustomDictionaryServiceInterface;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Characterization of the custom dictionary CRUD endpoints.
 *
 * @covers \OCA\Filinq\Controller\CustomDictionaryController
 * @uses   \OCA\Filinq\Controller\AnswersNotAuthenticated
 * @uses   \OCA\Filinq\Controller\Support\ServerErrorAnswer
 */
class CustomDictionaryControllerCrudTraceTest extends TestCase {

	private CustomDictionaryServiceInterface&MockObject $service;

	private IRequest&MockObject $request;

	private LoggerInterface&MockObject $logger;

	/**
	 * Fresh doubles; OpenRegister is available unless a test says otherwise.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->service = $this->createMock(CustomDictionaryServiceInterface::class);
		$this->request = $this->createMock(IRequest::class);
		$this->logger = $this->createMock(LoggerInterface::class);

	}//end setUp()

	/**
	 * The controller, with or without a session user.
	 *
	 * @param bool $signedIn Whether somebody is logged in.
	 *
	 * @return CustomDictionaryController The controller.
	 */
	private function controller(bool $signedIn = true): CustomDictionaryController {
		$session = $this->createMock(IUserSession::class);
		if ($signedIn === true) {
			$session->method('getUser')->willReturn($this->createMock(IUser::class));
		} else {
			$session->method('getUser')->willReturn(null);
		}

		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnCallback(
			static fn (string $text, array $params = []): string => vsprintf($text, $params)
		);

		return new CustomDictionaryController(
			'filinq',
			$this->request,
			$this->logger,
			$this->service,
			$l10n,
			$session
		);

	}//end controller()

	/**
	 * Listing answers the service's list with 200.
	 *
	 * @return void
	 */
	public function testIndexAnswersTheList(): void {
		$this->service->method('isAvailable')->willReturn(true);
		$this->service->expects($this->once())->method('listDictionaries')->willReturn([['uuid' => 'd1']]);

		$response = $this->controller()->index();

		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertSame([['uuid' => 'd1']], $response->getData());

	}//end testIndexAnswersTheList()

	/**
	 * Without OpenRegister every endpoint answers 503 and the operation never runs.
	 *
	 * @return void
	 */
	public function testIndexWithoutOpenRegisterIs503(): void {
		$this->service->method('isAvailable')->willReturn(false);
		$this->service->expects($this->never())->method('listDictionaries');

		$response = $this->controller()->index();

		$this->assertSame(Http::STATUS_SERVICE_UNAVAILABLE, $response->getStatus());
		$this->assertSame(
			['error' => 'Custom dictionaries require OpenRegister, which is not currently available.'],
			$response->getData()
		);

	}//end testIndexWithoutOpenRegisterIs503()

	/**
	 * The session is checked before availability.
	 *
	 * @return void
	 */
	public function testAnAnonymousCallerIs401BeforeAvailability(): void {
		$this->service->expects($this->never())->method('isAvailable');

		$response = $this->controller(signedIn: false)->show(id: 'd1');

		$this->assertSame(Http::STATUS_UNAUTHORIZED, $response->getStatus());
		$this->assertSame(['error' => 'Not authenticated'], $response->getData());

	}//end testAnAnonymousCallerIs401BeforeAvailability()

	/**
	 * Showing passes the id through and answers the record.
	 *
	 * @return void
	 */
	public function testShowAnswersTheRecord(): void {
		$this->service->method('isAvailable')->willReturn(true);
		$this->service->expects($this->once())->method('getDictionary')->with('d1')->willReturn(['uuid' => 'd1']);

		$response = $this->controller()->show(id: 'd1');

		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertSame(['uuid' => 'd1'], $response->getData());

	}//end testShowAnswersTheRecord()

	/**
	 * An unknown dictionary is 404 with the dictionary message.
	 *
	 * @return void
	 */
	public function testShowOfAnUnknownDictionaryIs404(): void {
		$this->service->method('isAvailable')->willReturn(true);
		$this->service->method('getDictionary')->willThrowException(new DoesNotExistException('gone'));

		$response = $this->controller()->show(id: 'd1');

		$this->assertSame(Http::STATUS_NOT_FOUND, $response->getStatus());
		$this->assertSame(['error' => 'Custom dictionary not found'], $response->getData());

	}//end testShowOfAnUnknownDictionaryIs404()

	/**
	 * Creating hands the request body to the service and answers 201.
	 *
	 * @return void
	 */
	public function testCreateAnswers201(): void {
		$this->service->method('isAvailable')->willReturn(true);
		$this->request->method('getParams')->willReturn(['name' => 'Namen']);
		$this->service->expects($this->once())
			->method('createDictionary')
			->with(['name' => 'Namen'])
			->willReturn(['uuid' => 'd2', 'name' => 'Namen']);

		$response = $this->controller()->create();

		$this->assertSame(Http::STATUS_CREATED, $response->getStatus());
		$this->assertSame(['uuid' => 'd2', 'name' => 'Namen'], $response->getData());

	}//end testCreateAnswers201()

	/**
	 * An invalid body is 400 carrying the service's message.
	 *
	 * @return void
	 */
	public function testCreateWithAnInvalidBodyIs400(): void {
		$this->service->method('isAvailable')->willReturn(true);
		$this->request->method('getParams')->willReturn([]);
		$this->service->method('createDictionary')->willThrowException(new InvalidArgumentException('name is required'));

		$response = $this->controller()->create();

		$this->assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());
		$this->assertSame(['error' => 'name is required'], $response->getData());

	}//end testCreateWithAnInvalidBodyIs400()

	/**
	 * Updating passes the id and body through and answers 200.
	 *
	 * @return void
	 */
	public function testUpdatePassesIdAndBody(): void {
		$this->service->method('isAvailable')->willReturn(true);
		$this->request->method('getParams')->willReturn(['name' => 'Nieuw']);
		$this->service->expects($this->once())
			->method('updateDictionary')
			->with('d1', ['name' => 'Nieuw'])
			->willReturn(['uuid' => 'd1', 'name' => 'Nieuw']);

		$response = $this->controller()->update(id: 'd1');

		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertSame(['uuid' => 'd1', 'name' => 'Nieuw'], $response->getData());

	}//end testUpdatePassesIdAndBody()

	/**
	 * The organisation gate (a RuntimeException) is a 403 that names no detail.
	 *
	 * @return void
	 */
	public function testUpdateRefusedByTheOrganisationGateIs403(): void {
		$this->service->method('isAvailable')->willReturn(true);
		$this->request->method('getParams')->willReturn([]);
		$this->service->method('updateDictionary')->willThrowException(new RuntimeException('org mismatch'));

		$response = $this->controller()->update(id: 'd1');

		$this->assertSame(Http::STATUS_FORBIDDEN, $response->getStatus());
		$this->assertSame(['error' => 'You do not have access to this custom dictionary'], $response->getData());

	}//end testUpdateRefusedByTheOrganisationGateIs403()

	/**
	 * Deleting answers the deleted id.
	 *
	 * @return void
	 */
	public function testDestroyAnswersTheDeletedId(): void {
		$this->service->method('isAvailable')->willReturn(true);
		$this->service->expects($this->once())->method('deleteDictionary')->with('d1');

		$response = $this->controller()->destroy(id: 'd1');

		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertSame(['deleted' => 'd1'], $response->getData());

	}//end testDestroyAnswersTheDeletedId()

	/**
	 * Any other failure is a logged 500 with the operation's prefix.
	 *
	 * @return void
	 */
	public function testAnUnexpectedFailureIsALogged500(): void {
		$this->service->method('isAvailable')->willReturn(true);
		$this->service->method('deleteDictionary')->willThrowException(new LogicException('db down'));
		$this->logger->expects($this->once())
			->method('error')
			->with('Failed to delete custom dictionary: db down', $this->anything());

		$response = $this->controller()->destroy(id: 'd1');

		$this->assertSame(Http::STATUS_INTERNAL_SERVER_ERROR, $response->getStatus());
		$this->assertSame(['error' => 'Failed to delete custom dictionary: db down'], $response->getData());

	}//end testAnUnexpectedFailureIsALogged500()

	/**
	 * An upload that failed at the PHP layer counts as no content: 400.
	 *
	 * @return void
	 */
	public function testAFailedUploadIsNoContent(): void {
		$this->service->method('isAvailable')->willReturn(true);
		$this->request->method('getUploadedFile')->with('file')->willReturn(
			['tmp_name' => '/nonexistent', 'name' => 'terms.csv', 'error' => UPLOAD_ERR_PARTIAL]
		);
		$this->service->expects($this->never())->method('importTerms');

		$response = $this->controller()->import(id: 'd1');

		$this->assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());
		$this->assertSame(['error' => 'No import content provided'], $response->getData());

	}//end testAFailedUploadIsNoContent()

	/**
	 * An upload whose temporary file cannot be read counts as no content: 400.
	 *
	 * @return void
	 */
	public function testAnUnreadableUploadIsNoContent(): void {
		$this->service->method('isAvailable')->willReturn(true);
		$missing = sys_get_temp_dir() . '/filinq-dict-missing-' . uniqid();
		$this->request->method('getUploadedFile')->with('file')->willReturn(
			['tmp_name' => $missing, 'name' => 'terms.txt', 'error' => UPLOAD_ERR_OK]
		);
		$this->service->expects($this->never())->method('importTerms');

		$response = @$this->controller()->import(id: 'd1');

		$this->assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());
		$this->assertSame(['error' => 'No import content provided'], $response->getData());

	}//end testAnUnreadableUploadIsNoContent()

	/**
	 * An upload with a CSV mime type is imported as CSV whatever its name.
	 *
	 * @return void
	 */
	public function testACsvMimeTypeMarksTheUploadAsCsv(): void {
		$this->service->method('isAvailable')->willReturn(true);
		$file = tempnam(sys_get_temp_dir(), 'filinq-dict-');
		file_put_contents($file, "term\nJansen\n");
		$this->request->method('getUploadedFile')->with('file')->willReturn(
			['tmp_name' => $file, 'name' => 'terms', 'type' => 'text/CSV']
		);
		$this->service->expects($this->once())
			->method('importTerms')
			->with('d1', "term\nJansen\n", true)
			->willReturn(['imported' => 1]);

		$response = $this->controller()->import(id: 'd1');
		unlink($file);

		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertSame(['imported' => 1], $response->getData());

	}//end testACsvMimeTypeMarksTheUploadAsCsv()
}//end class
