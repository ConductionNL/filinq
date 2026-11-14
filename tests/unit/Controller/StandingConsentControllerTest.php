<?php

/**
 * Wire-contract tests for StandingConsentController
 *
 * Covers the five standing-consent endpoints that moved off PolicyController
 * when it was split — `index`, `show`, `create`, `update` and `destroy`. Each
 * is asserted on its success status and body, on the 401 anonymous rejection
 * (before the service is touched), on the permission check running first with
 * the right surface and action, and on how a refusal or failure is answered.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/filinq-policy-controller-test-coverage/tasks.md#b-2-policycontrollertestphp--standing-consents-s
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use Exception;
use InvalidArgumentException;
use OCA\Filinq\Controller\StandingConsentController;
use OCA\Filinq\Service\PolicyCrudService;
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
 * Tests for the standing-consent CRUD endpoints.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @covers \OCA\Filinq\Controller\StandingConsentController
 *
 * @psalm-suppress                              PropertyNotSetInConstructor
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class StandingConsentControllerTest extends TestCase {

	/**
	 * Mocked request.
	 *
	 * @var IRequest&MockObject
	 */
	private IRequest&MockObject $request;

	/**
	 * Mocked CRUD service.
	 *
	 * @var PolicyCrudService&MockObject
	 */
	private PolicyCrudService&MockObject $crudService;

	/**
	 * Mocked localisation.
	 *
	 * @var IL10N&MockObject
	 */
	private IL10N&MockObject $l10n;

	/**
	 * Mocked logger.
	 *
	 * @var LoggerInterface&MockObject
	 */
	private LoggerInterface&MockObject $logger;

	/**
	 * Set up the boundary mocks.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->request     = $this->createMock(IRequest::class);
		$this->crudService = $this->createMock(PolicyCrudService::class);
		$this->logger      = $this->createMock(LoggerInterface::class);
		$this->l10n        = $this->createMock(IL10N::class);
		$this->l10n->method('t')->willReturnCallback(
			static function (string $text, array $params=[]): string {
				if ($params === []) {
					return $text;
				}

				return vsprintf($text, $params);
			}
		);

	}//end setUp()

	/**
	 * Build the controller with a signed-in or anonymous session.
	 *
	 * @param bool $signedIn Whether a user is signed in.
	 *
	 * @return StandingConsentController The controller.
	 */
	private function controller(bool $signedIn=true): StandingConsentController {
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($signedIn === true ? $this->createMock(IUser::class) : null);

		return new StandingConsentController(
			'filinq',
			$this->request,
			$this->logger,
			$this->crudService,
			$this->l10n,
			$session
		);

	}//end controller()

	/**
	 * Expect exactly one standing-consent permission check for the action.
	 *
	 * @param string $action The expected action.
	 *
	 * @return void
	 */
	private function expectPermission(string $action): void {
		$this->crudService->expects($this->once())
			->method('requirePolicyPermission')
			->with(PolicyCrudService::SURFACE_STANDING_CONSENT, $action);

	}//end expectPermission()

	/**
	 * Refuse the permission check with the given message.
	 *
	 * @param string $message The refusal.
	 *
	 * @return void
	 */
	private function refusePermission(string $message='Not permitted'): void {
		$this->crudService->method('requirePolicyPermission')
			->willThrowException(new RuntimeException($message));

	}//end refusePermission()

	/**
	 * Each endpoint, invoked on a controller.
	 *
	 * @return array<string, array{0: callable(StandingConsentController): \OCP\AppFramework\Http\JSONResponse, 1: string}>
	 */
	public static function endpointProvider(): array {
		return [
			'index'   => [static fn (StandingConsentController $c) => $c->index(), 'listStandingConsents'],
			'show'    => [static fn (StandingConsentController $c) => $c->show('sc-1'), 'getStandingConsent'],
			'create'  => [static fn (StandingConsentController $c) => $c->create(), 'createStandingConsent'],
			'update'  => [static fn (StandingConsentController $c) => $c->update('sc-1'), 'updateStandingConsent'],
			'destroy' => [static fn (StandingConsentController $c) => $c->destroy('sc-1'), 'deleteStandingConsent'],
		];

	}//end endpointProvider()

	/**
	 * Every endpoint refuses an anonymous caller with 401 before the service runs.
	 *
	 * @param callable $call   Invokes the endpoint.
	 * @param string   $method The service method the endpoint would reach.
	 *
	 * @return void
	 *
	 * @dataProvider endpointProvider
	 */
	public function testAnonymousCallerIsRefusedBeforeTheService(callable $call, string $method): void {
		$this->crudService->expects($this->never())->method('requirePolicyPermission');
		$this->crudService->expects($this->never())->method($method);

		$response = $call($this->controller(signedIn: false));

		$this->assertSame(Http::STATUS_UNAUTHORIZED, $response->getStatus());
		$this->assertSame(['error' => 'Not authenticated'], $response->getData());

	}//end testAnonymousCallerIsRefusedBeforeTheService()

	/**
	 * GET returns 200 with the service's list verbatim, after a read check.
	 *
	 * @return void
	 */
	public function testIndexReturnsTheList(): void {
		$records = [['id' => 'sc-1', 'scope' => 'entity'], ['id' => 'sc-2', 'scope' => 'entity']];
		$this->expectPermission(action: 'read');
		$this->crudService->method('listStandingConsents')->willReturn($records);

		$response = $this->controller()->index();

		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertSame($records, $response->getData());

	}//end testIndexReturnsTheList()

	/**
	 * A refused read answers 500 with the refusal and never reads the list.
	 *
	 * @return void
	 */
	public function testIndexRefusalIsAnsweredAsAnError(): void {
		$this->refusePermission(message: 'Standing-consent read requires membership.');
		$this->crudService->expects($this->never())->method('listStandingConsents');
		$this->logger->expects($this->once())->method('error');

		$response = $this->controller()->index();

		$this->assertSame(Http::STATUS_INTERNAL_SERVER_ERROR, $response->getStatus());
		$this->assertSame(
			['error' => 'Failed to list standing consents: Standing-consent read requires membership.'],
			$response->getData()
		);

	}//end testIndexRefusalIsAnsweredAsAnError()

	/**
	 * GET /{id} returns 200 with the record.
	 *
	 * @return void
	 */
	public function testShowReturnsTheRecord(): void {
		$record = ['id' => 'sc-1', 'scope' => 'entity'];
		$this->expectPermission(action: 'read');
		$this->crudService->expects($this->once())
			->method('getStandingConsent')
			->with('sc-1')
			->willReturn($record);

		$response = $this->controller()->show('sc-1');

		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertSame($record, $response->getData());

	}//end testShowReturnsTheRecord()

	/**
	 * GET /{id} for an unknown record returns 404.
	 *
	 * @return void
	 */
	public function testShowUnknownRecordIsNotFound(): void {
		$this->crudService->method('getStandingConsent')->willReturn(null);

		$response = $this->controller()->show('missing');

		$this->assertSame(Http::STATUS_NOT_FOUND, $response->getStatus());
		$this->assertSame(['error' => 'Standing consent not found'], $response->getData());

	}//end testShowUnknownRecordIsNotFound()

	/**
	 * A refused read on show never loads the record.
	 *
	 * @return void
	 */
	public function testShowRefusalNeverLoadsTheRecord(): void {
		$this->refusePermission();
		$this->crudService->expects($this->never())->method('getStandingConsent');

		$response = $this->controller()->show('sc-1');

		$this->assertSame(Http::STATUS_INTERNAL_SERVER_ERROR, $response->getStatus());
		$this->assertSame(['error' => 'Failed to load standing consent: Not permitted'], $response->getData());

	}//end testShowRefusalNeverLoadsTheRecord()

	/**
	 * POST returns 201 with the created record, built from the request params.
	 *
	 * @return void
	 */
	public function testCreateReturnsCreated(): void {
		$params  = ['entityName' => 'Board', 'scope' => 'entity'];
		$created = ['id' => 'sc-9'] + $params;
		$this->request->method('getParams')->willReturn($params);
		$this->expectPermission(action: 'create');
		$this->crudService->expects($this->once())
			->method('createStandingConsent')
			->with($params)
			->willReturn($created);

		$response = $this->controller()->create();

		$this->assertSame(Http::STATUS_CREATED, $response->getStatus());
		$this->assertSame($created, $response->getData());

	}//end testCreateReturnsCreated()

	/**
	 * A refused create answers 403 with the refusal and creates nothing.
	 *
	 * @return void
	 */
	public function testCreateRefusalIsForbidden(): void {
		$this->refusePermission(message: 'Standing-consent create requires membership.');
		$this->crudService->expects($this->never())->method('createStandingConsent');

		$response = $this->controller()->create();

		$this->assertSame(Http::STATUS_FORBIDDEN, $response->getStatus());
		$this->assertSame(['error' => 'Standing-consent create requires membership.'], $response->getData());

	}//end testCreateRefusalIsForbidden()

	/**
	 * Invalid input on create answers 400 with the validation message.
	 *
	 * @return void
	 */
	public function testCreateInvalidInputIsBadRequest(): void {
		$this->request->method('getParams')->willReturn([]);
		$this->crudService->method('createStandingConsent')
			->willThrowException(new InvalidArgumentException('entityName is required'));

		$response = $this->controller()->create();

		$this->assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());
		$this->assertSame(['error' => 'entityName is required'], $response->getData());

	}//end testCreateInvalidInputIsBadRequest()

	/**
	 * Any other failure on create answers 500 and is logged.
	 *
	 * @return void
	 */
	public function testCreateOtherFailureIsAServerError(): void {
		$this->request->method('getParams')->willReturn([]);
		$this->crudService->method('createStandingConsent')->willThrowException(new Exception('store down'));
		$this->logger->expects($this->once())->method('error');

		$response = $this->controller()->create();

		$this->assertSame(Http::STATUS_INTERNAL_SERVER_ERROR, $response->getStatus());
		$this->assertSame(['error' => 'Failed to create standing consent: store down'], $response->getData());

	}//end testCreateOtherFailureIsAServerError()

	/**
	 * PUT /{id} returns 200 with the updated record.
	 *
	 * @return void
	 */
	public function testUpdateReturnsTheUpdatedRecord(): void {
		$params  = ['active' => false];
		$updated = ['id' => 'sc-1', 'active' => false];
		$this->request->method('getParams')->willReturn($params);
		$this->expectPermission(action: 'update');
		$this->crudService->expects($this->once())
			->method('updateStandingConsent')
			->with('sc-1', $params)
			->willReturn($updated);

		$response = $this->controller()->update('sc-1');

		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertSame($updated, $response->getData());

	}//end testUpdateReturnsTheUpdatedRecord()

	/**
	 * A refused update answers 403 and changes nothing.
	 *
	 * @return void
	 */
	public function testUpdateRefusalIsForbidden(): void {
		$this->refusePermission();
		$this->crudService->expects($this->never())->method('updateStandingConsent');

		$response = $this->controller()->update('sc-1');

		$this->assertSame(Http::STATUS_FORBIDDEN, $response->getStatus());
		$this->assertSame(['error' => 'Not permitted'], $response->getData());

	}//end testUpdateRefusalIsForbidden()

	/**
	 * Invalid input on update answers 400.
	 *
	 * @return void
	 */
	public function testUpdateInvalidInputIsBadRequest(): void {
		$this->request->method('getParams')->willReturn(['scope' => 'document']);
		$this->crudService->method('updateStandingConsent')
			->willThrowException(new InvalidArgumentException('scope must be entity'));

		$response = $this->controller()->update('sc-1');

		$this->assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());
		$this->assertSame(['error' => 'scope must be entity'], $response->getData());

	}//end testUpdateInvalidInputIsBadRequest()

	/**
	 * DELETE /{id} returns 200 with the deleted id.
	 *
	 * @return void
	 */
	public function testDestroyReturnsTheDeletedId(): void {
		$this->expectPermission(action: 'delete');
		$this->crudService->expects($this->once())->method('deleteStandingConsent')->with('sc-1');

		$response = $this->controller()->destroy('sc-1');

		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertSame(['deleted' => 'sc-1'], $response->getData());

	}//end testDestroyReturnsTheDeletedId()

	/**
	 * A refused delete answers 403 and deletes nothing.
	 *
	 * @return void
	 */
	public function testDestroyRefusalIsForbidden(): void {
		$this->refusePermission();
		$this->crudService->expects($this->never())->method('deleteStandingConsent');

		$response = $this->controller()->destroy('sc-1');

		$this->assertSame(Http::STATUS_FORBIDDEN, $response->getStatus());
		$this->assertSame(['error' => 'Not permitted'], $response->getData());

	}//end testDestroyRefusalIsForbidden()

	/**
	 * Any other failure on delete answers 500.
	 *
	 * @return void
	 */
	public function testDestroyOtherFailureIsAServerError(): void {
		$this->crudService->method('deleteStandingConsent')->willThrowException(new Exception('locked'));

		$response = $this->controller()->destroy('sc-1');

		$this->assertSame(Http::STATUS_INTERNAL_SERVER_ERROR, $response->getStatus());
		$this->assertSame(['error' => 'Failed to delete standing consent: locked'], $response->getData());

	}//end testDestroyOtherFailureIsAServerError()
}//end class
