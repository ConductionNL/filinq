<?php

/**
 * Wire-contract tests for PolicyController
 *
 * Covers the five `api/policy/prohibitions` endpoints —
 * `policy#indexProhibitions` (GET), `policy#createProhibition` (POST),
 * `policy#showProhibition` (GET /{id}), `policy#updateProhibition` (PUT /{id})
 * and `policy#deleteProhibition` (DELETE /{id}). Each is asserted on its
 * documented status code and body shape, on the 401 anonymous rejection, and on
 * the permission check running BEFORE the record is touched.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Tests\Unit\Controller;

use InvalidArgumentException;
use OCA\DocuDesk\Controller\PolicyController;
use OCA\DocuDesk\Service\PolicyCrudService;
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
 * Tests for the prohibition policy-surface CRUD endpoints.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @psalm-suppress                              PropertyNotSetInConstructor
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class PolicyControllerTest extends TestCase {

	/**
	 * Mocked request.
	 *
	 * @var IRequest|MockObject
	 */
	private IRequest|MockObject $request;

	/**
	 * Mocked CRUD service.
	 *
	 * @var PolicyCrudService|MockObject
	 */
	private PolicyCrudService|MockObject $crudService;

	/**
	 * Mocked localisation.
	 *
	 * @var IL10N|MockObject
	 */
	private IL10N|MockObject $l10n;

	/**
	 * Controller under test, with an authenticated session.
	 *
	 * @var PolicyController
	 */
	private PolicyController $controller;

	/**
	 * Set up an authenticated controller.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->crudService = $this->createMock(PolicyCrudService::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->l10n->method('t')->willReturnCallback(
			static function (string $text, array $params = []): string {
				if ($params === []) {
					return $text;
				}

				return vsprintf($text, $params);
			}
		);

		$user = $this->createMock(IUser::class);
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);

		$this->controller = new PolicyController(
			'docudesk',
			$this->request,
			$this->createMock(LoggerInterface::class),
			$this->crudService,
			$this->l10n,
			$session
		);

	}//end setUp()

	/**
	 * Build a controller whose session has no logged-in user.
	 *
	 * @return PolicyController The anonymous-session controller.
	 */
	private function anonymousController(): PolicyController {
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn(null);

		return new PolicyController(
			'docudesk',
			$this->request,
			$this->createMock(LoggerInterface::class),
			$this->crudService,
			$this->l10n,
			$session
		);

	}//end anonymousController()

	/**
	 * GET api/policy/prohibitions returns 200 with the service's list verbatim.
	 *
	 * @return void
	 */
	public function testIndexProhibitionsReturnsList(): void {
		$records = [
			['id' => 'uuid-1', 'name' => 'Board members'],
			['id' => 'uuid-2', 'name' => 'Whistleblowers'],
		];

		$this->crudService->expects($this->once())
			->method('requirePolicyPermission')
			->with(PolicyCrudService::SURFACE_PROHIBITION, 'read');
		$this->crudService->method('listProhibitions')->willReturn($records);

		$response = $this->controller->indexProhibitions();

		$this->assertInstanceOf(JSONResponse::class, $response);
		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertSame($records, $response->getData());

	}//end testIndexProhibitionsReturnsList()

	/**
	 * An anonymous caller is refused with 401 before any permission check runs.
	 *
	 * @return void
	 */
	public function testIndexProhibitionsRejectsAnonymousCaller(): void {
		$this->crudService->expects($this->never())->method('requirePolicyPermission');
		$this->crudService->expects($this->never())->method('listProhibitions');

		$response = $this->anonymousController()->indexProhibitions();

		$this->assertSame(Http::STATUS_UNAUTHORIZED, $response->getStatus());
		$this->assertSame(['error' => 'Not authenticated'], $response->getData());

	}//end testIndexProhibitionsRejectsAnonymousCaller()

	/**
	 * A denied read permission is mapped to the 500 error envelope rather than
	 * leaking the raw exception — and the list is never read.
	 *
	 * @return void
	 */
	public function testIndexProhibitionsSurfacesPermissionFailureAsError(): void {
		$this->crudService->method('requirePolicyPermission')
			->willThrowException(new RuntimeException('Not permitted'));
		$this->crudService->expects($this->never())->method('listProhibitions');

		$response = $this->controller->indexProhibitions();

		$this->assertSame(Http::STATUS_INTERNAL_SERVER_ERROR, $response->getStatus());
		$this->assertArrayHasKey('error', $response->getData());

	}//end testIndexProhibitionsSurfacesPermissionFailureAsError()

	/**
	 * GET api/policy/prohibitions/{id} returns 200 with the record.
	 *
	 * @return void
	 */
	public function testShowProhibitionReturnsRecord(): void {
		$record = ['id' => 'uuid-1', 'name' => 'Board members'];

		$this->crudService->expects($this->once())
			->method('getProhibition')
			->with('uuid-1')
			->willReturn($record);

		$response = $this->controller->showProhibition('uuid-1');

		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertSame($record, $response->getData());

	}//end testShowProhibitionReturnsRecord()

	/**
	 * An unknown UUID yields 404 with an `error` body, not an empty 200.
	 *
	 * @return void
	 */
	public function testShowProhibitionReturnsNotFound(): void {
		$this->crudService->method('getProhibition')->willReturn(null);

		$response = $this->controller->showProhibition('missing');

		$this->assertSame(Http::STATUS_NOT_FOUND, $response->getStatus());
		$this->assertSame(['error' => 'Prohibition not found'], $response->getData());

	}//end testShowProhibitionReturnsNotFound()

	/**
	 * An anonymous caller cannot read a single prohibition either.
	 *
	 * @return void
	 */
	public function testShowProhibitionRejectsAnonymousCaller(): void {
		$this->crudService->expects($this->never())->method('getProhibition');

		$response = $this->anonymousController()->showProhibition('uuid-1');

		$this->assertSame(Http::STATUS_UNAUTHORIZED, $response->getStatus());

	}//end testShowProhibitionRejectsAnonymousCaller()

	/**
	 * POST api/policy/prohibitions answers 201 with the created record, and
	 * forwards the request body to the service.
	 *
	 * @return void
	 */
	public function testCreateProhibitionReturnsCreated(): void {
		$body = ['name' => 'Board members', 'scope' => 'entity'];
		$created = ['id' => 'uuid-new'] + $body;

		$this->request->method('getParams')->willReturn($body);
		$this->crudService->expects($this->once())
			->method('requirePolicyPermission')
			->with(PolicyCrudService::SURFACE_PROHIBITION, 'create');
		$this->crudService->expects($this->once())
			->method('createProhibition')
			->with($body)
			->willReturn($created);

		$response = $this->controller->createProhibition();

		$this->assertSame(Http::STATUS_CREATED, $response->getStatus());
		$this->assertSame($created, $response->getData());

	}//end testCreateProhibitionReturnsCreated()

	/**
	 * A malformed body is rejected with 400 carrying the validation message —
	 * not the generic 500 envelope.
	 *
	 * @return void
	 */
	public function testCreateProhibitionRejectsInvalidBody(): void {
		$this->request->method('getParams')->willReturn(['name' => '']);
		$this->crudService->method('createProhibition')
			->willThrowException(new InvalidArgumentException('name is required'));

		$response = $this->controller->createProhibition();

		$this->assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());
		$this->assertSame(['error' => 'name is required'], $response->getData());

	}//end testCreateProhibitionRejectsInvalidBody()

	/**
	 * An anonymous caller cannot create a prohibition.
	 *
	 * @return void
	 */
	public function testCreateProhibitionRejectsAnonymousCaller(): void {
		$this->crudService->expects($this->never())->method('createProhibition');

		$response = $this->anonymousController()->createProhibition();

		$this->assertSame(Http::STATUS_UNAUTHORIZED, $response->getStatus());

	}//end testCreateProhibitionRejectsAnonymousCaller()

	/**
	 * PUT api/policy/prohibitions/{id} answers 200 with the updated record and
	 * passes both the UUID and the body through.
	 *
	 * @return void
	 */
	public function testUpdateProhibitionReturnsUpdatedRecord(): void {
		$body = ['name' => 'Renamed'];
		$updated = ['id' => 'uuid-1', 'name' => 'Renamed'];

		$this->request->method('getParams')->willReturn($body);
		$this->crudService->expects($this->once())
			->method('requirePolicyPermission')
			->with(PolicyCrudService::SURFACE_PROHIBITION, 'update');
		$this->crudService->expects($this->once())
			->method('updateProhibition')
			->with('uuid-1', $body)
			->willReturn($updated);

		$response = $this->controller->updateProhibition('uuid-1');

		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertSame($updated, $response->getData());

	}//end testUpdateProhibitionReturnsUpdatedRecord()

	/**
	 * A rejected update payload answers 400 with the validation message.
	 *
	 * @return void
	 */
	public function testUpdateProhibitionRejectsInvalidBody(): void {
		$this->request->method('getParams')->willReturn(['scope' => 'nonsense']);
		$this->crudService->method('updateProhibition')
			->willThrowException(new InvalidArgumentException('scope is invalid'));

		$response = $this->controller->updateProhibition('uuid-1');

		$this->assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());
		$this->assertSame(['error' => 'scope is invalid'], $response->getData());

	}//end testUpdateProhibitionRejectsInvalidBody()

	/**
	 * An anonymous caller cannot update a prohibition.
	 *
	 * @return void
	 */
	public function testUpdateProhibitionRejectsAnonymousCaller(): void {
		$this->crudService->expects($this->never())->method('updateProhibition');

		$response = $this->anonymousController()->updateProhibition('uuid-1');

		$this->assertSame(Http::STATUS_UNAUTHORIZED, $response->getStatus());

	}//end testUpdateProhibitionRejectsAnonymousCaller()

	/**
	 * DELETE api/policy/prohibitions/{id} answers 200 with `{deleted: <id>}`.
	 *
	 * @return void
	 */
	public function testDeleteProhibitionReturnsDeletedId(): void {
		$this->crudService->expects($this->once())
			->method('requirePolicyPermission')
			->with(PolicyCrudService::SURFACE_PROHIBITION, 'delete');
		$this->crudService->expects($this->once())
			->method('deleteProhibition')
			->with('uuid-1');

		$response = $this->controller->deleteProhibition('uuid-1');

		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertSame(['deleted' => 'uuid-1'], $response->getData());

	}//end testDeleteProhibitionReturnsDeletedId()

	/**
	 * A failing delete is reported as a 500 error envelope, and never as a
	 * success body that would make the UI drop the row.
	 *
	 * @return void
	 */
	public function testDeleteProhibitionSurfacesFailureAsError(): void {
		$this->crudService->method('deleteProhibition')
			->willThrowException(new RuntimeException('register unavailable'));

		$response = $this->controller->deleteProhibition('uuid-1');

		$this->assertSame(Http::STATUS_INTERNAL_SERVER_ERROR, $response->getStatus());
		$this->assertArrayHasKey('error', $response->getData());
		$this->assertArrayNotHasKey('deleted', $response->getData());

	}//end testDeleteProhibitionSurfacesFailureAsError()

	/**
	 * An anonymous caller cannot delete a prohibition.
	 *
	 * @return void
	 */
	public function testDeleteProhibitionRejectsAnonymousCaller(): void {
		$this->crudService->expects($this->never())->method('deleteProhibition');

		$response = $this->anonymousController()->deleteProhibition('uuid-1');

		$this->assertSame(Http::STATUS_UNAUTHORIZED, $response->getStatus());

	}//end testDeleteProhibitionRejectsAnonymousCaller()

}//end class
