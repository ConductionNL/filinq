<?php

/**
 * Characterization tests for SigningController::cancelRequest()
 *
 * Pins the initiator gate: which user it runs for, that a forged body
 * identity changes nothing, and that every not-found shape answers the same.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/document-signing/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\SigningController;
use OCA\Filinq\Controller\Support\SigningServices;
use OCA\Filinq\Kernel\Contracts\SigningAuditServiceInterface;
use OCA\Filinq\Kernel\Contracts\SigningServiceInterface;
use Psr\Log\NullLogger;
use OCA\Filinq\Service\SigningService;
use OCA\Filinq\Service\Signing\SigningProviderInterface;
use OCA\Filinq\Service\Signing\SigningProviderFactory;
use OCA\Filinq\Service\Signing\SigningCancellationService;
use OCA\Filinq\Exception\SigningCancellationNotSupportedException;
use OCA\Filinq\Kernel\Contracts\SigningVerificationServiceInterface;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Characterization of the cancel endpoint.
 *
 * @covers \OCA\Filinq\Controller\SigningController
 * @covers \OCA\Filinq\Controller\Support\SigningServices
 * @covers \OCA\Filinq\Service\Signing\SigningCancellationService
 * @uses   \OCA\Filinq\Exception\SigningCancellationNotSupportedException
 */
class SigningControllerCancelTraceTest extends TestCase {

	private const FORGED = ['userId' => 'initiator', 'requestedBy' => 'initiator', 'initiatorUserId' => 'mallory'];

	private SigningServiceInterface&MockObject $signing;

	private LoggerInterface&MockObject $logger;

	private SigningService&MockObject $requests;

	private SigningProviderInterface&MockObject $provider;

	/**
	 * Fresh doubles: request r-1 was started by `initiator`.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->signing = $this->createMock(SigningServiceInterface::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->requests = $this->createMock(SigningService::class);
		$this->requests->method('isValidTransition')->willReturnCallback(
			static fn (string $currentStatus, string $newStatus): bool => in_array($currentStatus, ['DRAFT', 'PENDING', 'IN_PROGRESS'], true) && $newStatus === 'CANCELLED'
		);
		$this->provider = $this->createMock(SigningProviderInterface::class);

	}//end setUp()

	/**
	 * Request r-1 as the lookup returns it.
	 *
	 * @param string $status The status.
	 *
	 * @return array<string, string>
	 */
	private static function request(string $status = 'PENDING'): array {
		return ['id' => 'r-1', 'initiatorUserId' => 'initiator', 'externalId' => 'ext-1', 'status' => $status];
	}//end request()

	/**
	 * The creator withdraws it: the lookup is scoped to the session user, the
	 * provider withdraws it and the request moves to CANCELLED.
	 *
	 * @return void
	 */
	public function testTheInitiatorCancels(): void {
		$this->requests->expects($this->once())->method('getRequest')->with('r-1', 'initiator')->willReturn(self::request());
		$this->provider->expects($this->once())->method('cancelSigning')->with('ext-1');
		$this->requests->expects($this->once())->method('cancelRequest')->with('r-1')->willReturn(['id' => 'r-1', 'status' => 'CANCELLED']);

		$response = $this->controller(uid: 'initiator', params: [])->cancelRequest('r-1');

		$this->assertSame(200, $response->getStatus());
		$this->assertSame(['requestId' => 'r-1', 'status' => 'cancelled', 'alreadyCancelled' => false], $response->getData());

	}//end testTheInitiatorCancels()

	/**
	 * Another user is refused even when the body names the initiator, and the refusal names the creator.
	 *
	 * @return void
	 */
	public function testAForgedBodyDoesNotMakeAnotherUserTheInitiator(): void {
		$this->requests->method('getRequest')->with('r-1', 'mallory')->willReturn(self::request());
		$this->provider->expects($this->never())->method('cancelSigning');
		$this->requests->expects($this->never())->method('cancelRequest');

		$response = $this->controller(uid: 'mallory', params: self::FORGED)->cancelRequest('r-1');

		$this->assertSame(403, $response->getStatus());
		$this->assertSame(
			['error' => 'Only the person who created this signing request can withdraw it: initiator. Administrators cannot withdraw it on their behalf.'],
			$response->getData()
		);

	}//end testAForgedBodyDoesNotMakeAnotherUserTheInitiator()

	/**
	 * An administrator is refused like anybody else who did not create the request (decided 2026-08-16).
	 *
	 * @return void
	 */
	public function testAnAdminIsRefusedLikeAnyoneElse(): void {
		$this->requests->method('getRequest')->willReturn(self::request());
		$this->provider->expects($this->never())->method('cancelSigning');
		$this->requests->expects($this->never())->method('cancelRequest');

		$this->assertSame(403, $this->controller(uid: 'admin', params: self::FORGED)->cancelRequest('r-1')->getStatus());

	}//end testAnAdminIsRefusedLikeAnyoneElse()

	/**
	 * A thrown lookup, a null lookup and a record without a creator answer one
	 * and the same 403, so request ids cannot be probed.
	 *
	 * @return void
	 */
	public function testEveryNotFoundShapeAnswersTheSame(): void {
		$refused = [403, ['error' => 'Only the person who created this signing request can withdraw it.']];

		$this->requests->method('getRequest')->willThrowException(new RuntimeException('Signing request not found: r-1'));
		$response = $this->controller(uid: 'initiator', params: [])->cancelRequest('r-1');
		$this->assertSame($refused, [$response->getStatus(), $response->getData()]);

		$this->setUp();
		$this->requests->method('getRequest')->willReturn(null);
		$response = $this->controller(uid: 'initiator', params: [])->cancelRequest('r-1');
		$this->assertSame($refused, [$response->getStatus(), $response->getData()]);

		$this->setUp();
		$this->requests->method('getRequest')->willReturn(['id' => 'r-1', 'externalId' => 'ext-1', 'status' => 'PENDING']);
		$this->provider->expects($this->never())->method('cancelSigning');
		$response = $this->controller(uid: 'initiator', params: [])->cancelRequest('r-1');
		$this->assertSame($refused, [$response->getStatus(), $response->getData()]);

	}//end testEveryNotFoundShapeAnswersTheSame()

	/**
	 * A completed request and a provider that cannot withdraw answer 409; the request stays as it was.
	 *
	 * @return void
	 */
	public function testAFinalRequestAndAnUnsupportedProviderAnswerConflict(): void {
		$this->requests->method('getRequest')->willReturn(self::request(status: 'COMPLETED'));
		$this->requests->expects($this->never())->method('cancelRequest');
		$response = $this->controller(uid: 'initiator', params: [])->cancelRequest('r-1');
		$this->assertSame([409, ['error' => 'This signing request is completed and can no longer be withdrawn.']], [$response->getStatus(), $response->getData()]);

		$this->setUp();
		$this->requests->method('getRequest')->willReturn(self::request());
		$this->provider->method('cancelSigning')->willThrowException(new SigningCancellationNotSupportedException('ValidSign'));
		$this->requests->expects($this->never())->method('cancelRequest');
		$response = $this->controller(uid: 'initiator', params: [])->cancelRequest('r-1');
		$this->assertSame(409, $response->getStatus());
		$this->assertStringContainsString('ValidSign', $response->getData()['error']);

	}//end testAFinalRequestAndAnUnsupportedProviderAnswerConflict()

	/**
	 * Without a session nothing is read; a failing provider answers the generic error.
	 *
	 * @return void
	 */
	public function testNoSessionAndFailure(): void {
		$this->requests->expects($this->never())->method('getRequest');
		$this->assertSame(401, $this->controller(uid: null, params: [])->cancelRequest('r-1')->getStatus());

		$this->setUp();
		$this->requests->method('getRequest')->willReturn(self::request());
		$this->provider->method('cancelSigning')->willThrowException(new RuntimeException('provider down'));
		$this->requests->expects($this->never())->method('cancelRequest');
		$this->logger->expects($this->once())->method('error')->with($this->stringStartsWith('Failed to cancel signing request: The signing request could not be withdrawn: provider down'), $this->anything());
		$response = $this->controller(uid: 'initiator', params: [])->cancelRequest('r-1');
		$this->assertSame(500, $response->getStatus());
		$this->assertSame('Failed to cancel signing request', $response->getData()['error']);

	}//end testNoSessionAndFailure()

	/**
	 * The controller for session user $uid; only `admin` is an admin.
	 *
	 * @param string|null $uid The session user, or null for none.
	 * @param array<string, mixed> $params The request body.
	 *
	 * @return SigningController The controller.
	 */
	private function controller(?string $uid, array $params): SigningController {
		$request = $this->createMock(IRequest::class);
		$request->method('getParams')->willReturn($params);
		$request->method('getParam')->willReturnCallback(
			static fn (string $key, mixed $default = null): mixed => (array_key_exists($key, $params) === true ? $params[$key] : $default)
		);

		$session = $this->createMock(IUserSession::class);
		$user = null;
		if ($uid !== null) {
			$user = $this->createMock(IUser::class);
			$user->method('getUID')->willReturn($uid);
		}

		$session->method('getUser')->willReturn($user);

		$groups = $this->createMock(IGroupManager::class);
		$groups->method('isAdmin')->willReturnCallback(static fn (string $who): bool => $who === 'admin');

		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnArgument(0);

		$factory = $this->createMock(SigningProviderFactory::class);
		$factory->method('getActiveProvider')->willReturn($this->provider);

		return new SigningController(
			services: new SigningServices(
				signing: $this->signing,
				audit: $this->createMock(SigningAuditServiceInterface::class),
				verification: $this->createMock(SigningVerificationServiceInterface::class),
				cancellation: new SigningCancellationService(providers: $factory, requests: $this->requests, logger: new NullLogger())
			),
			request: $request,
			userSession: $session,
			logger: $this->logger,
			l10n: $l10n,
			groupManager: $groups,
		);

	}//end controller()
}//end class
