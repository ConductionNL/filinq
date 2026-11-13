<?php

/**
 * Unit tests for SigningController — finding #288 regression coverage.
 *
 * Asserts that `listRequests()` no longer swallows arbitrary
 * `\Throwable` (and in particular `\Exception`) as an empty-list
 * `notConfigured: true` success — only the narrow `\Error` /
 * missing-method case is treated as deployment drift, real
 * infrastructure failures propagate to the framework's 500 handler
 * so monitoring sees them.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\SigningController;
use OCA\Filinq\Controller\Support\SigningServices;
use OCA\Filinq\Exception\RegisterNotConfiguredException;
use OCA\Filinq\Service\SigningAuditService;
use OCA\Filinq\Service\SigningService;
use OCA\Filinq\Service\SigningVerificationService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;
use OCA\Filinq\Exception\SigningFieldsRefusedException;
use OCA\Filinq\Exception\StepUpRequiredException;

/**
 * Tests for SigningController::listRequests() error handling
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class SigningControllerTest extends TestCase {

	/**
	 * @var SigningController
	 */
	private SigningController $controller;

	/**
	 * @var SigningService|MockObject
	 */
	private SigningService|MockObject $signingService;

	/**
	 * @var IUserSession|MockObject
	 */
	private IUserSession|MockObject $userSession;

	/**
	 * @var LoggerInterface|MockObject
	 */
	private LoggerInterface|MockObject $logger;

	/**
	 * Set up test environment
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$request = $this->createMock(IRequest::class);
		$request->method('getParam')->willReturnCallback(fn (string $key, mixed $default = null): mixed => $default);
		$this->signingService = $this->createMock(SigningService::class);
		$auditService = $this->createMock(SigningAuditService::class);
		$verificationService = $this->createMock(SigningVerificationService::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnCallback(
			function ($text, $params = []) {
				return is_array($params) === true ? vsprintf($text, $params) : $text;
			}
		);
		$groupManager = $this->createMock(IGroupManager::class);
		$groupManager->method('isAdmin')->willReturn(false);

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');
		$this->userSession->method('getUser')->willReturn($user);

		$this->controller = new SigningController(
			$request,
			new SigningServices(signing: $this->signingService, audit: $auditService, verification: $verificationService),
			$this->userSession,
			$this->logger,
			$l10n,
			$groupManager
		);

	}//end setUp()

	/**
	 * `RegisterNotConfiguredException` continues to return an empty list
	 * with `notConfigured: true` — that's the genuine "not configured yet"
	 * UI state we explicitly keep calm.
	 *
	 * @return void
	 */
	public function testListRequestsReturnsEmptyOnRegisterNotConfigured(): void {
		$this->signingService->method('listRequests')
			->willThrowException(new RegisterNotConfiguredException('signing register/schema not configured'));

		$response = $this->controller->listRequests();

		$this->assertInstanceOf(JSONResponse::class, $response);
		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$data = $response->getData();
		$this->assertSame([], $data['results']);
		$this->assertTrue($data['notConfigured']);

	}//end testListRequestsReturnsEmptyOnRegisterNotConfigured()

	/**
	 * A real `\Exception` from a runtime failure (e.g. a DB outage rethrown
	 * as `RuntimeException`) MUST propagate — finding #288 was that the
	 * previous broad `catch (\Throwable)` masked these as "no requests".
	 *
	 * @return void
	 */
	public function testListRequestsLetsRuntimeExceptionPropagate(): void {
		$this->signingService->method('listRequests')
			->willThrowException(new RuntimeException('OpenRegister DB outage'));

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('OpenRegister DB outage');

		$this->controller->listRequests();

	}//end testListRequestsLetsRuntimeExceptionPropagate()

	/**
	 * A PHP `\Error` (e.g. call to undefined method on a mock) now propagates
	 * as-is since the `\Error` fallback was removed when SigningService was
	 * migrated to the canonical OR API (findAll / find / saveObject).
	 *
	 * @return void
	 */
	public function testListRequestsLetsErrorPropagate(): void {
		$this->signingService->method('listRequests')
			->willThrowException(new \Error('Call to undefined method'));

		$this->expectException(\Error::class);

		$this->controller->listRequests();

	}//end testListRequestsLetsErrorPropagate()

	/**
	 * Successful list passes through unchanged.
	 *
	 * @return void
	 */
	public function testListRequestsReturnsServiceResultOnSuccess(): void {
		$expected = [['id' => 'req-1', 'status' => 'PENDING']];
		$this->signingService->method('listRequests')->willReturn($expected);

		$response = $this->controller->listRequests();

		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertSame($expected, $response->getData());

	}//end testListRequestsReturnsServiceResultOnSuccess()

	/**
	 * A step-up refusal answers 403 with the hint the sign dialog needs (REQ-DDSIR-003).
	 *
	 * @return void
	 */
	public function testAStepUpRefusalAnswers403WithTheHint(): void {
		$this->signingService->method('sign')->willThrowException(
			new StepUpRequiredException(reason: 'insufficient', requiredAssurance: 'substantial', heldAssurance: 'low', provider: 'oidc-broker')
		);

		$response = $this->controller->sign('req-1');

		$this->assertSame(403, $response->getStatus());
		$this->assertSame(
			['required' => true, 'reason' => 'insufficient', 'requiredAssurance' => 'substantial', 'heldAssurance' => 'low', 'provider' => 'oidc-broker'],
			$response->getData()['stepUp'] ?? null
		);

	}//end testAStepUpRefusalAnswers403WithTheHint()

	/**
	 * Any other failure carries no hint.
	 *
	 * @return void
	 */
	public function testAnOrdinaryRefusalCarriesNoHint(): void {
		$this->signingService->method('decline')->willThrowException(new \RuntimeException('Not authorized to decline as this signer', 403));

		$response = $this->controller->decline('req-1');

		$this->assertSame(403, $response->getStatus());
		$this->assertArrayNotHasKey('stepUp', $response->getData());

	}//end testAnOrdinaryRefusalCarriesNoHint()

	/**
	 * The sign route passes the typed fields on, and a refused value answers 422 with a message per field.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/signing-field-validation/tasks.md#task-2.3
	 */
	public function testARefusedFieldAnswers422WithTheMessageOnThatField(): void {
		$request = $this->createMock(IRequest::class);
		$request->method('getParam')->willReturnCallback(
			fn (string $key, mixed $default = null): mixed => match ($key) {
				'signerId' => 'signer-1',
				'fields' => ['0' => 'NL91 ABNA 0417 1643 01'],
				default => $default,
			}
		);
		$user = $this->createMock(IUser::class);
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnCallback(fn (string $text): string => '['.$text.']');
		$controller = new SigningController(
			$request,
			new SigningServices(signing: $this->signingService, audit: $this->createMock(SigningAuditService::class), verification: $this->createMock(SigningVerificationService::class)),
			$session,
			$this->logger,
			$l10n,
			$this->createMock(IGroupManager::class)
		);

		$this->signingService->expects($this->once())->method('sign')
			->with('req-1', 'signer-1', null, null, ['0' => 'NL91 ABNA 0417 1643 01'])
			->willThrowException(new SigningFieldsRefusedException(fieldErrors: [0 => 'Type a valid Dutch IBAN, for example NL91 ABNA 0417 1643 00']));

		$response = $controller->sign('req-1');

		$this->assertSame(422, $response->getStatus());
		$this->assertSame([0 => '[Type a valid Dutch IBAN, for example NL91 ABNA 0417 1643 00]'], $response->getData()['fieldErrors'] ?? null);
		$this->assertStringNotContainsString('1643 01', (string) json_encode($response->getData()));

	}//end testARefusedFieldAnswers422WithTheMessageOnThatField()

	/**
	 * Without a `fields` object the act carries none, and the answer has no field messages.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/signing-field-validation/tasks.md#task-2.3
	 */
	public function testWithoutFieldsTheActCarriesNone(): void {
		$this->signingService->expects($this->once())->method('sign')
			->with('req-1', '', null, null, null)
			->willReturn(['status' => 'SIGNED']);

		$response = $this->controller->sign('req-1');

		$this->assertSame(200, $response->getStatus());
		$this->assertArrayNotHasKey('fieldErrors', $response->getData());

	}//end testWithoutFieldsTheActCarriesNone()


	/**
	 * A controller whose request names another user, over the given verifier and session user.
	 *
	 * @param SigningVerificationService|MockObject $verification The verifier.
	 * @param string|null                           $uid          The session user, null for nobody.
	 *
	 * @return SigningController The controller.
	 */
	private function forgedController(SigningVerificationService|MockObject $verification, ?string $uid): SigningController {
		$request = $this->createMock(IRequest::class);
		$request->method('getParam')->willReturnCallback(fn (string $key, mixed $default = null): mixed => ($key === 'userId') ? 'mallory' : $default);
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn((string) $uid);
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn(($uid === null) ? null : $user);
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnArgument(0);

		return new SigningController(
			$request,
			new SigningServices(signing: $this->signingService, audit: $this->createMock(SigningAuditService::class), verification: $verification),
			$session,
			$this->logger,
			$l10n,
			$this->createMock(IGroupManager::class)
		);

	}//end forgedController()

	/**
	 * A document is verified for the session user, not for a userId the request names.
	 *
	 * @return void
	 */
	public function testVerifyRunsForTheSessionUserNotAForgedUserId(): void {
		$verification = $this->createMock(SigningVerificationService::class);
		$verification->expects($this->once())
			->method('verifyDocument')
			->with(17, 'alice')
			->willReturn(['valid' => true]);

		$response = $this->forgedController(verification: $verification, uid: 'alice')->verify(fileId: 17);

		$this->assertSame(Http::STATUS_OK, $response->getStatus());

	}//end testVerifyRunsForTheSessionUserNotAForgedUserId()

	/**
	 * With nobody signed in nothing is verified, not even for a userId the request names.
	 *
	 * @return void
	 */
	public function testVerifyAsksForASessionUser(): void {
		$verification = $this->createMock(SigningVerificationService::class);
		$verification->expects($this->never())->method('verifyDocument');

		$response = $this->forgedController(verification: $verification, uid: null)->verify(fileId: 17);

		$this->assertSame(Http::STATUS_UNAUTHORIZED, $response->getStatus());

	}//end testVerifyAsksForASessionUser()
}//end class
