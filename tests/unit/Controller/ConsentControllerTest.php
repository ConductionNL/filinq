<?php

/**
 * Unit tests for ConsentController
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2025 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\ConsentController;
use OCA\Filinq\Exception\PolicyRejectedException;
use OCA\Filinq\Service\ConsentCrudService;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for ConsentController
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class ConsentControllerTest extends TestCase {

	/**
	 * @var ConsentController
	 */
	private ConsentController $controller;

	/**
	 * @var IRequest|MockObject
	 */
	private IRequest|MockObject $mockRequest;

	/**
	 * @var LoggerInterface|MockObject
	 */
	private LoggerInterface|MockObject $mockLogger;

	/**
	 * @var ConsentCrudService|MockObject
	 */
	private ConsentCrudService|MockObject $mockCrudService;

	/**
	 * @var IL10N|MockObject
	 */
	private IL10N|MockObject $mockL10n;

	/**
	 * @var IUserSession|MockObject
	 */
	private IUserSession|MockObject $mockUserSession;

	/**
	 * @var IGroupManager|MockObject
	 */
	private IGroupManager|MockObject $mockGroupManager;

	/**
	 * Set up test environment
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->mockRequest = $this->createMock(IRequest::class);
		$this->mockLogger = $this->createMock(LoggerInterface::class);
		$this->mockCrudService = $this->createMock(ConsentCrudService::class);
		$this->mockL10n = $this->createMock(IL10N::class);
		$this->mockUserSession = $this->createMock(IUserSession::class);
		$this->mockGroupManager = $this->createMock(IGroupManager::class);
		$this->mockL10n->method('t')->willReturnCallback(
			function ($text, $params = []) {
				return vsprintf($text, $params);
			}
		);

		// Default: an authenticated, non-admin user named "owner".
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('owner');
		$this->mockUserSession->method('getUser')->willReturn($user);
		$this->mockGroupManager->method('isAdmin')->willReturn(false);

		$this->controller = new ConsentController(
			'filinq',
			$this->mockRequest,
			$this->mockLogger,
			$this->mockCrudService,
			$this->mockL10n,
			$this->mockUserSession,
			$this->mockGroupManager
		);

	}//end setUp()

	/**
	 * Test index returns 400 when not configured
	 *
	 * @return void
	 */
	public function testIndexReturns400WhenNotConfigured(): void {
		$this->mockCrudService->method('getConsentConfig')
			->willReturn(null);

		$result = $this->controller->index();

		$this->assertInstanceOf(JSONResponse::class, $result);
		$this->assertEquals(400, $result->getStatus());

	}//end testIndexReturns400WhenNotConfigured()

	/**
	 * Test index returns consent list when configured
	 *
	 * @return void
	 */
	public function testIndexReturnsConsentList(): void {
		$this->mockCrudService->method('getConsentConfig')
			->willReturn(['register' => 'reg-1', 'schema' => 'sch-1']);
		$this->mockCrudService->method('listConsents')
			->willReturn([['id' => 'uuid-1']]);

		$result = $this->controller->index();

		$this->assertInstanceOf(JSONResponse::class, $result);
		$this->assertEquals(200, $result->getStatus());

	}//end testIndexReturnsConsentList()

	/**
	 * Test create returns 400 when missing required fields
	 *
	 * @return void
	 */
	public function testCreateReturns400WhenMissingFields(): void {
		$this->mockRequest->method('getParams')
			->willReturn([]);

		$result = $this->controller->create();

		$this->assertInstanceOf(JSONResponse::class, $result);
		$this->assertEquals(400, $result->getStatus());

	}//end testCreateReturns400WhenMissingFields()

	/**
	 * A brand-new consent record answers HTTP 201.
	 *
	 * Positive control for the 200-on-update test below: without it, a
	 * controller that always answered 200 would pass that test for the wrong
	 * reason.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/consent-management/spec.md
	 */
	public function testCreateReturns201WhenANewRecordWasCreated(): void {
		$this->mockRequest->method('getParams')->willReturn(
			[
				'documentId' => 'doc-1',
				'entityType' => 'PERSON',
				'entityText' => 'Anneke Jansen',
			]
		);
		$this->mockCrudService->method('getConsentConfig')
			->willReturn(['register' => 'reg-1', 'schema' => 'sch-1']);
		$this->mockCrudService->method('createFromRequest')
			->willReturn(['id' => 'consent-1', 'wasUpdated' => false]);

		$result = $this->controller->create();

		$this->assertInstanceOf(JSONResponse::class, $result);
		$this->assertEquals(201, $result->getStatus());

	}//end testCreateReturns201WhenANewRecordWasCreated()

	/**
	 * An idempotent re-submit answers HTTP 200, not 201.
	 *
	 * The service reports the branch it took through `wasUpdated`
	 * (`consent-management`: "The method's return shape MUST include a
	 * `wasUpdated` boolean"). HTTP 201 means a new resource was created
	 * (RFC 9110 §15.3.2); on the update branch nothing is created. The
	 * controller previously hardcoded 201, so the status line contradicted the
	 * `wasUpdated: true` in its own body.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/consent-management/spec.md
	 */
	public function testCreateReturns200WhenAnExistingRecordWasUpdated(): void {
		$this->mockRequest->method('getParams')->willReturn(
			[
				'documentId' => 'doc-1',
				'entityType' => 'PERSON',
				'entityText' => 'Anneke Jansen',
			]
		);
		$this->mockCrudService->method('getConsentConfig')
			->willReturn(['register' => 'reg-1', 'schema' => 'sch-1']);
		$this->mockCrudService->method('createFromRequest')
			->willReturn(['id' => 'consent-1', 'wasUpdated' => true]);

		$result = $this->controller->create();

		$this->assertInstanceOf(JSONResponse::class, $result);
		$this->assertEquals(200, $result->getStatus());

	}//end testCreateReturns200WhenAnExistingRecordWasUpdated()

	/**
	 * A prohibition match answers HTTP 403 carrying the rule identity.
	 *
	 * `PolicyRejectedException` is constructed with `code = 0`, so before this
	 * fix it fell through to `errorResponse()` and was reported as HTTP 500 —
	 * a deliberate business-rule rejection presented as a server crash — while
	 * the rule UUID and name the exception exists to carry were discarded.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/consent-management/spec.md
	 */
	public function testCreateReturns403WithRuleIdentityOnProhibitionMatch(): void {
		$this->mockRequest->method('getParams')->willReturn(
			[
				'documentId' => 'doc-1',
				'entityType' => 'PERSON',
				'entityText' => 'Beschermde Getuige A',
			]
		);
		$this->mockCrudService->method('getConsentConfig')
			->willReturn(['register' => 'reg-1', 'schema' => 'sch-1']);
		$this->mockCrudService->method('createFromRequest')
			->willThrowException(
				new PolicyRejectedException(
					ruleUuid: 'rule-uuid-99',
					ruleName: 'Witness Protection Rule'
				)
			);

		$result = $this->controller->create();

		$this->assertInstanceOf(JSONResponse::class, $result);
		$this->assertEquals(403, $result->getStatus());

		$body = $result->getData();
		$this->assertSame('prohibition', $body['matchKind'] ?? null);
		$this->assertSame('rule-uuid-99', $body['ruleUuid'] ?? null);
		$this->assertSame('Witness Protection Rule', $body['ruleName'] ?? null);

	}//end testCreateReturns403WithRuleIdentityOnProhibitionMatch()

	/**
	 * Test show returns 404 when not found
	 *
	 * @return void
	 */
	public function testShowReturns404WhenNotFound(): void {
		$this->mockCrudService->method('getConsentConfig')
			->willReturn(['register' => 'reg-1', 'schema' => 'sch-1']);
		$this->mockCrudService->method('getConsent')
			->willReturn(null);

		$result = $this->controller->show('uuid-1');

		$this->assertInstanceOf(JSONResponse::class, $result);
		$this->assertEquals(404, $result->getStatus());

	}//end testShowReturns404WhenNotFound()

	/**
	 * Test show returns consent record when found
	 *
	 * @return void
	 */
	public function testShowReturnsConsentWhenFound(): void {
		$this->mockCrudService->method('getConsentConfig')
			->willReturn(['register' => 'reg-1', 'schema' => 'sch-1']);
		$this->mockCrudService->method('getConsent')
			->willReturn(
				[
					'id' => 'uuid-1',
					'consentStatus' => 'pending',
					'@self' => ['owner' => 'owner'],
				]
			);

		$result = $this->controller->show('uuid-1');

		$this->assertInstanceOf(JSONResponse::class, $result);
		$this->assertEquals(200, $result->getStatus());

	}//end testShowReturnsConsentWhenFound()

	/**
	 * Test show returns 404 when the record belongs to another user
	 *
	 * Security finding #283: a non-owner must not be able to read another
	 * user's consent record.
	 *
	 * @return void
	 */
	public function testShowReturns404ForNonOwner(): void {
		$this->mockCrudService->method('getConsentConfig')
			->willReturn(['register' => 'reg-1', 'schema' => 'sch-1']);
		$this->mockCrudService->method('getConsent')
			->willReturn(
				[
					'id' => 'uuid-1',
					'consentStatus' => 'pending',
					'@self' => ['owner' => 'someone-else'],
				]
			);

		$result = $this->controller->show('uuid-1');

		$this->assertInstanceOf(JSONResponse::class, $result);
		$this->assertEquals(404, $result->getStatus());

	}//end testShowReturns404ForNonOwner()

	/**
	 * Test update returns 404 when the record belongs to another user
	 *
	 * Security finding #283: a non-owner must not be able to overwrite
	 * another user's consent record.
	 *
	 * @return void
	 */
	public function testUpdateReturns404ForNonOwner(): void {
		$this->mockCrudService->method('getConsentConfig')
			->willReturn(['register' => 'reg-1', 'schema' => 'sch-1']);
		$this->mockCrudService->method('getConsent')
			->willReturn(
				[
					'id' => 'uuid-1',
					'@self' => ['owner' => 'someone-else'],
				]
			);
		$this->mockRequest->method('getParams')
			->willReturn(['consentStatus' => 'granted']);

		$result = $this->controller->update('uuid-1');

		$this->assertInstanceOf(JSONResponse::class, $result);
		$this->assertEquals(404, $result->getStatus());

	}//end testUpdateReturns404ForNonOwner()

	/**
	 * Test update succeeds for the record owner
	 *
	 * @return void
	 */
	public function testUpdateSucceedsForOwner(): void {
		$this->mockCrudService->method('getConsentConfig')
			->willReturn(['register' => 'reg-1', 'schema' => 'sch-1']);
		$this->mockCrudService->method('getConsent')
			->willReturn(
				[
					'id' => 'uuid-1',
					'@self' => ['owner' => 'owner'],
				]
			);
		$this->mockCrudService->method('updateConsentStatus')
			->willReturn(['id' => 'uuid-1', 'consentStatus' => 'granted']);
		$this->mockRequest->method('getParams')
			->willReturn(['consentStatus' => 'granted']);

		$result = $this->controller->update('uuid-1');

		$this->assertInstanceOf(JSONResponse::class, $result);
		$this->assertEquals(200, $result->getStatus());

	}//end testUpdateSucceedsForOwner()

	/**
	 * Oracle-free error response (signing-trust-rebuild REQ-DDSTR-009, closing
	 * the #283 residual): exception text carrying a record UUID must NEVER
	 * reach the response body — only the generic translated message. Full
	 * detail goes to the logger only. Mirrors the proven
	 * SigningController::errorResponse() fix (filinq#100 / Wilco #6).
	 *
	 * @return void
	 */
	public function testErrorResponseNeverLeaksExceptionText(): void {
		$sensitiveUuid = '11111111-2222-3333-4444-555555555555';
		$this->mockCrudService->method('getConsentConfig')
			->willThrowException(new \Exception('Consent record ' . $sensitiveUuid . ' violates constraint xyz'));

		$result = $this->controller->index();

		$this->assertInstanceOf(JSONResponse::class, $result);
		$this->assertEquals(500, $result->getStatus());

		$body = json_encode($result->getData());
		$this->assertStringNotContainsString($sensitiveUuid, $body, 'The response body must never contain exception text/identifiers.');
		$this->assertStringNotContainsString('violates constraint', $body);

	}//end testErrorResponseNeverLeaksExceptionText()

	/**
	 * A non-owner probe (existing record, not theirs) and a genuinely
	 * not-found record collapse to a BYTE-IDENTICAL 404 body (no
	 * existence-probing oracle) — signing-trust-rebuild REQ-DDSTR-009.
	 *
	 * @return void
	 */
	public function testNonOwnerProbeAndNotFoundAreByteIdentical(): void {
		$this->mockCrudService->method('getConsentConfig')
			->willReturn(['register' => 'reg-1', 'schema' => 'sch-1']);

		// Case A: record exists but belongs to someone else.
		$this->mockCrudService->method('getConsent')
			->willReturn(
				[
					'id' => 'uuid-owned-by-other',
					'@self' => ['owner' => 'someone-else'],
				]
			);
		$nonOwnerResult = $this->controller->show('uuid-owned-by-other');

		// Case B: record genuinely does not exist — fresh controller/mock so
		// getConsent() returns null instead.
		$notFoundCrudService = $this->createMock(ConsentCrudService::class);
		$notFoundCrudService->method('getConsentConfig')->willReturn(['register' => 'reg-1', 'schema' => 'sch-1']);
		$notFoundCrudService->method('getConsent')->willReturn(null);

		$notFoundController = new ConsentController(
			'filinq',
			$this->mockRequest,
			$this->mockLogger,
			$notFoundCrudService,
			$this->mockL10n,
			$this->mockUserSession,
			$this->mockGroupManager
		);
		$notFoundResult = $notFoundController->show('does-not-exist');

		$this->assertEquals(404, $nonOwnerResult->getStatus());
		$this->assertEquals(404, $notFoundResult->getStatus());
		$this->assertSame(
			json_encode($notFoundResult->getData()),
			json_encode($nonOwnerResult->getData()),
			'Non-owner and not-found responses must be byte-identical (no existence oracle).'
		);

	}//end testNonOwnerProbeAndNotFoundAreByteIdentical()

	/**
	 * A legitimate 4xx status carried on the exception code is still honoured
	 * — client errors are not masked as a generic 500 (REQ-DDSTR-009).
	 *
	 * @return void
	 */
	public function testErrorResponseHonoursExceptionStatusCode(): void {
		$this->mockCrudService->method('getConsentConfig')
			->willThrowException(new \Exception('Invalid input', 400));

		$result = $this->controller->index();

		$this->assertEquals(400, $result->getStatus());

	}//end testErrorResponseHonoursExceptionStatusCode()
}//end class
