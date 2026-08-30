<?php

/**
 * Wire-contract tests for the CustomDictionaryController term endpoints
 *
 * Covers `customDictionary#indexTerms` (GET api/custom-dictionaries/{id}/terms),
 * `customDictionary#createTerm` (POST, same path),
 * `customDictionary#deleteTerm` (DELETE .../terms/{termId}) and
 * `customDictionary#import` (POST .../import). Each is asserted on its
 * documented status code and body, on the shared guard order
 * (401 unauthenticated before 503 OpenRegister-absent), and on the
 * exception-to-status mapping the controller promises: 404 for a missing
 * record, 403 for the organisation gate, 400 for bad input.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\CustomDictionaryController;
use OCA\Filinq\Service\CustomDictionaryService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
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
 * Tests for the custom-dictionary term endpoints.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress                              PropertyNotSetInConstructor
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CustomDictionaryControllerTest extends TestCase {

	/**
	 * Mocked request.
	 *
	 * @var IRequest|MockObject
	 */
	private IRequest|MockObject $request;

	/**
	 * Mocked dictionary service.
	 *
	 * @var CustomDictionaryService|MockObject
	 */
	private CustomDictionaryService|MockObject $service;

	/**
	 * Mocked localisation.
	 *
	 * @var IL10N|MockObject
	 */
	private IL10N|MockObject $l10n;

	/**
	 * Controller under test: authenticated caller, OpenRegister available.
	 *
	 * @var CustomDictionaryController
	 */
	private CustomDictionaryController $controller;

	/**
	 * Set up an authenticated controller with an available backend.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->service = $this->createMock(CustomDictionaryService::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->l10n->method('t')->willReturnCallback(
			static function (string $text, array $params = []): string {
				if ($params === []) {
					return $text;
				}

				return vsprintf($text, $params);
			}
		);

		$this->service->method('isAvailable')->willReturn(true);

		$this->controller = $this->buildController($this->authenticatedSession());

	}//end setUp()

	/**
	 * Build the controller for a given session.
	 *
	 * @param IUserSession $session The session the controller should see.
	 *
	 * @return CustomDictionaryController The controller under test.
	 */
	private function buildController(IUserSession $session): CustomDictionaryController {
		return new CustomDictionaryController(
			'filinq',
			$this->request,
			$this->createMock(LoggerInterface::class),
			$this->service,
			$this->l10n,
			$session
		);

	}//end buildController()

	/**
	 * Build a session with a logged-in user.
	 *
	 * @return IUserSession The authenticated session.
	 */
	private function authenticatedSession(): IUserSession {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);

		return $session;
	}//end authenticatedSession()

	/**
	 * Build a session with no logged-in user.
	 *
	 * @return IUserSession The anonymous session.
	 */
	private function anonymousSession(): IUserSession {
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn(null);

		return $session;
	}//end anonymousSession()

	/**
	 * GET .../terms answers 200 with the dictionary's term list.
	 *
	 * @return void
	 */
	public function testIndexTermsReturnsTermList(): void {
		$terms = [
			['id' => 'term-1', 'value' => 'Zaaknummer'],
			['id' => 'term-2', 'value' => 'Kenmerk'],
		];

		$this->service->expects($this->once())
			->method('listTerms')
			->with('dict-1')
			->willReturn($terms);

		$response = $this->controller->indexTerms('dict-1');

		$this->assertInstanceOf(JSONResponse::class, $response);
		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertSame($terms, $response->getData());

	}//end testIndexTermsReturnsTermList()

	/**
	 * An anonymous caller is refused with 401 before the backend is consulted.
	 *
	 * @return void
	 */
	public function testIndexTermsRejectsAnonymousCaller(): void {
		$this->service->expects($this->never())->method('listTerms');

		$response = $this->buildController($this->anonymousSession())->indexTerms('dict-1');

		$this->assertSame(Http::STATUS_UNAUTHORIZED, $response->getStatus());
		$this->assertSame(['error' => 'Not authenticated'], $response->getData());

	}//end testIndexTermsRejectsAnonymousCaller()

	/**
	 * With OpenRegister absent the endpoint answers 503 with an explanatory
	 * body rather than a 500 stack trace.
	 *
	 * @return void
	 */
	public function testIndexTermsReportsBackendUnavailable(): void {
		$service = $this->createMock(CustomDictionaryService::class);
		$service->method('isAvailable')->willReturn(false);
		$service->expects($this->never())->method('listTerms');

		$controller = new CustomDictionaryController(
			'filinq',
			$this->request,
			$this->createMock(LoggerInterface::class),
			$service,
			$this->l10n,
			$this->authenticatedSession()
		);

		$response = $controller->indexTerms('dict-1');

		$this->assertSame(Http::STATUS_SERVICE_UNAVAILABLE, $response->getStatus());
		$this->assertStringContainsString('OpenRegister', $response->getData()['error']);

	}//end testIndexTermsReportsBackendUnavailable()

	/**
	 * An unknown dictionary answers 404 with the localised not-found message.
	 *
	 * @return void
	 */
	public function testIndexTermsReturnsNotFoundForUnknownDictionary(): void {
		$this->service->method('listTerms')
			->willThrowException(new DoesNotExistException('no such dictionary'));

		$response = $this->controller->indexTerms('missing');

		$this->assertSame(Http::STATUS_NOT_FOUND, $response->getStatus());
		$this->assertSame(['error' => 'Custom dictionary not found'], $response->getData());

	}//end testIndexTermsReturnsNotFoundForUnknownDictionary()

	/**
	 * POST .../terms answers 201 with the created term and forwards the body.
	 *
	 * @return void
	 */
	public function testCreateTermReturnsCreated(): void {
		$body = ['value' => 'Zaaknummer', 'entityType' => 'CASE_NUMBER'];
		$created = ['id' => 'term-new'] + $body;

		$this->request->method('getParams')->willReturn($body);
		$this->service->expects($this->once())
			->method('createTerm')
			->with('dict-1', $body)
			->willReturn($created);

		$response = $this->controller->createTerm('dict-1');

		$this->assertSame(Http::STATUS_CREATED, $response->getStatus());
		$this->assertSame($created, $response->getData());

	}//end testCreateTermReturnsCreated()

	/**
	 * A dictionary outside the caller's organisations answers 403 — the
	 * organisation gate, surfaced as its own status rather than a 500.
	 *
	 * @return void
	 */
	public function testCreateTermIsRefusedByOrganisationGate(): void {
		$this->request->method('getParams')->willReturn(['value' => 'Zaaknummer']);
		$this->service->method('createTerm')
			->willThrowException(new RuntimeException('organisation gate'));

		$response = $this->controller->createTerm('dict-of-another-org');

		$this->assertSame(Http::STATUS_FORBIDDEN, $response->getStatus());
		$this->assertArrayHasKey('error', $response->getData());

	}//end testCreateTermIsRefusedByOrganisationGate()

	/**
	 * An anonymous caller cannot add a term.
	 *
	 * @return void
	 */
	public function testCreateTermRejectsAnonymousCaller(): void {
		$this->service->expects($this->never())->method('createTerm');

		$response = $this->buildController($this->anonymousSession())->createTerm('dict-1');

		$this->assertSame(Http::STATUS_UNAUTHORIZED, $response->getStatus());

	}//end testCreateTermRejectsAnonymousCaller()

	/**
	 * DELETE .../terms/{termId} answers 200 with `{deleted: <termId>}` and
	 * passes both path segments to the service.
	 *
	 * @return void
	 */
	public function testDeleteTermReturnsDeletedTermId(): void {
		$this->service->expects($this->once())
			->method('deleteTerm')
			->with('dict-1', 'term-7');

		$response = $this->controller->deleteTerm('dict-1', 'term-7');

		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertSame(['deleted' => 'term-7'], $response->getData());

	}//end testDeleteTermReturnsDeletedTermId()

	/**
	 * An unknown term answers 404 with the term-specific message — and never a
	 * success body that would make the UI drop a row it did not delete.
	 *
	 * @return void
	 */
	public function testDeleteTermReturnsNotFoundForUnknownTerm(): void {
		$this->service->method('deleteTerm')
			->willThrowException(new DoesNotExistException('no such term'));

		$response = $this->controller->deleteTerm('dict-1', 'missing');

		$this->assertSame(Http::STATUS_NOT_FOUND, $response->getStatus());
		$this->assertSame(['error' => 'Term not found'], $response->getData());

	}//end testDeleteTermReturnsNotFoundForUnknownTerm()

	/**
	 * An anonymous caller cannot delete a term.
	 *
	 * @return void
	 */
	public function testDeleteTermRejectsAnonymousCaller(): void {
		$this->service->expects($this->never())->method('deleteTerm');

		$response = $this->buildController($this->anonymousSession())->deleteTerm('dict-1', 'term-7');

		$this->assertSame(Http::STATUS_UNAUTHORIZED, $response->getStatus());

	}//end testDeleteTermRejectsAnonymousCaller()

	/**
	 * POST .../import with a pasted newline list answers 200 with the import
	 * summary, and the content is parsed as a newline list by default.
	 *
	 * @return void
	 */
	public function testImportAcceptsPastedNewlineList(): void {
		$this->request->method('getUploadedFile')->willReturn(null);
		$this->request->method('getParam')->willReturnMap(
			[
				['content', null, "Zaaknummer\nKenmerk"],
				['format', 'newline', 'newline'],
			]
		);

		$this->service->expects($this->once())
			->method('importTerms')
			->with('dict-1', "Zaaknummer\nKenmerk", false)
			->willReturn(['imported' => 2, 'skipped' => 0]);

		$response = $this->controller->import('dict-1');

		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertSame(['imported' => 2, 'skipped' => 0], $response->getData());

	}//end testImportAcceptsPastedNewlineList()

	/**
	 * `format=csv` on pasted content flips the parser to CSV.
	 *
	 * @return void
	 */
	public function testImportHonoursCsvFormatParam(): void {
		$this->request->method('getUploadedFile')->willReturn(null);
		$this->request->method('getParam')->willReturnMap(
			[
				['content', null, "value,type\nZaaknummer,CASE"],
				['format', 'newline', 'CSV'],
			]
		);

		$this->service->expects($this->once())
			->method('importTerms')
			->with('dict-1', "value,type\nZaaknummer,CASE", true)
			->willReturn(['imported' => 1, 'skipped' => 0]);

		$response = $this->controller->import('dict-1');

		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertSame(1, $response->getData()['imported']);

	}//end testImportHonoursCsvFormatParam()

	/**
	 * A real multipart `.csv` upload is read from disk and imported as CSV —
	 * the uploaded filename, not a request param, decides the parser.
	 *
	 * @return void
	 */
	public function testImportReadsUploadedCsvFile(): void {
		$tmp = tempnam(sys_get_temp_dir(), 'dd-import-');
		$this->assertIsString($tmp);
		file_put_contents($tmp, "value,type\nKenmerk,CASE");

		$this->request->method('getUploadedFile')->willReturn(
			[
				'name' => 'terms.csv',
				'type' => 'text/csv',
				'tmp_name' => $tmp,
				'error' => UPLOAD_ERR_OK,
				'size' => filesize($tmp),
			]
		);

		$this->service->expects($this->once())
			->method('importTerms')
			->with('dict-1', "value,type\nKenmerk,CASE", true)
			->willReturn(['imported' => 1, 'skipped' => 0]);

		$response = $this->controller->import('dict-1');

		unlink($tmp);

		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertSame(1, $response->getData()['imported']);

	}//end testImportReadsUploadedCsvFile()

	/**
	 * An import with neither an upload nor content answers 400 with the
	 * documented message, and nothing is imported.
	 *
	 * @return void
	 */
	public function testImportRejectsEmptyPayload(): void {
		$this->request->method('getUploadedFile')->willReturn(null);
		$this->request->method('getParam')->willReturn(null);

		$this->service->expects($this->never())->method('importTerms');

		$response = $this->controller->import('dict-1');

		$this->assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());
		$this->assertSame(['error' => 'No import content provided'], $response->getData());

	}//end testImportRejectsEmptyPayload()

	/**
	 * An anonymous caller cannot import terms.
	 *
	 * @return void
	 */
	public function testImportRejectsAnonymousCaller(): void {
		$this->service->expects($this->never())->method('importTerms');

		$response = $this->buildController($this->anonymousSession())->import('dict-1');

		$this->assertSame(Http::STATUS_UNAUTHORIZED, $response->getStatus());

	}//end testImportRejectsAnonymousCaller()

}//end class
