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
use OCA\Filinq\Kernel\Contracts\SigningAuditServiceInterface;
use OCA\Filinq\Kernel\Contracts\SigningServiceInterface;
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
 */
class SigningControllerCancelTraceTest extends TestCase {

	private const FORGED = ['userId' => 'initiator', 'requestedBy' => 'initiator', 'initiatorUserId' => 'mallory'];

	private SigningServiceInterface&MockObject $signing;

	private LoggerInterface&MockObject $logger;

	/**
	 * Fresh doubles: request r-1 was started by `initiator`.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->signing = $this->createMock(SigningServiceInterface::class);
		$this->logger = $this->createMock(LoggerInterface::class);

	}//end setUp()

	/**
	 * The initiator may cancel; the record is read without caller scoping.
	 *
	 * @return void
	 */
	public function testTheInitiatorCancels(): void {
		$this->signing->expects($this->once())->method('getRequest')->with('r-1', '')->willReturn(['id' => 'r-1', 'initiatorUserId' => 'initiator']);
		$this->signing->expects($this->once())->method('cancelRequest')->with('r-1')->willReturn(['id' => 'r-1', 'status' => 'cancelled']);

		$response = $this->controller(uid: 'initiator', params: [])->cancelRequest('r-1');

		$this->assertSame(200, $response->getStatus());
		$this->assertSame(['id' => 'r-1', 'status' => 'cancelled'], $response->getData());

	}//end testTheInitiatorCancels()

	/**
	 * Another user is refused even when the body names the initiator.
	 *
	 * @return void
	 */
	public function testAForgedBodyDoesNotMakeAnotherUserTheInitiator(): void {
		$this->signing->method('getRequest')->willReturn(['id' => 'r-1', 'initiatorUserId' => 'initiator']);
		$this->signing->expects($this->never())->method('cancelRequest');

		$response = $this->controller(uid: 'mallory', params: self::FORGED)->cancelRequest('r-1');

		$this->assertSame(403, $response->getStatus());
		$this->assertSame(['error' => 'You are not allowed to cancel this signing request'], $response->getData());

	}//end testAForgedBodyDoesNotMakeAnotherUserTheInitiator()

	/**
	 * An admin cancels without the initiator lookup; only the session user is checked for admin.
	 *
	 * @return void
	 */
	public function testAnAdminSkipsTheInitiatorGate(): void {
		$this->signing->expects($this->never())->method('getRequest');
		$this->signing->expects($this->once())->method('cancelRequest')->with('r-1')->willReturn(['id' => 'r-1']);

		$this->assertSame(200, $this->controller(uid: 'admin', params: self::FORGED)->cancelRequest('r-1')->getStatus());

		$this->setUp();
		$this->signing->method('getRequest')->willReturn(['id' => 'r-1', 'initiatorUserId' => 'initiator']);
		$this->signing->expects($this->never())->method('cancelRequest');
		$this->assertSame(403, $this->controller(uid: 'mallory', params: ['userId' => 'admin', 'requestedBy' => 'admin'])->cancelRequest('r-1')->getStatus());

	}//end testAnAdminSkipsTheInitiatorGate()

	/**
	 * A thrown or null lookup and a vanished cancel answer the same 404; a record without initiator is refused.
	 *
	 * @return void
	 */
	public function testEveryNotFoundShapeAnswersTheSame(): void {
		$notFound = ['error' => 'Signing request not found'];

		$this->signing->method('getRequest')->willThrowException(new RuntimeException('Signing request not found: r-1'));
		$this->signing->expects($this->never())->method('cancelRequest');
		$response = $this->controller(uid: 'initiator', params: [])->cancelRequest('r-1');
		$this->assertSame([404, $notFound], [$response->getStatus(), $response->getData()]);

		$this->setUp();
		$this->signing->method('getRequest')->willReturn(null);
		$this->signing->expects($this->never())->method('cancelRequest');
		$response = $this->controller(uid: 'initiator', params: [])->cancelRequest('r-1');
		$this->assertSame([404, $notFound], [$response->getStatus(), $response->getData()]);

		$this->setUp();
		$this->signing->method('getRequest')->willReturn(['id' => 'r-1']);
		$this->signing->expects($this->never())->method('cancelRequest');
		$this->assertSame(403, $this->controller(uid: 'initiator', params: [])->cancelRequest('r-1')->getStatus());

		$this->setUp();
		$this->signing->method('cancelRequest')->willReturn(null);
		$response = $this->controller(uid: 'admin', params: [])->cancelRequest('r-1');
		$this->assertSame([404, $notFound], [$response->getStatus(), $response->getData()]);

	}//end testEveryNotFoundShapeAnswersTheSame()

	/**
	 * Without a session nothing is read; a failing cancel answers the generic error.
	 *
	 * @return void
	 */
	public function testNoSessionAndFailure(): void {
		$this->signing->expects($this->never())->method('getRequest');
		$this->assertSame(401, $this->controller(uid: null, params: [])->cancelRequest('r-1')->getStatus());

		$this->setUp();
		$this->signing->method('cancelRequest')->willThrowException(new RuntimeException('provider down', 502));
		$this->logger->expects($this->once())->method('error')->with('Failed to cancel signing request: provider down', $this->anything());
		$response = $this->controller(uid: 'admin', params: [])->cancelRequest('r-1');
		$this->assertSame(502, $response->getStatus());
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

		return new SigningController(
			appName: 'filinq',
			request: $request,
			signingService: $this->signing,
			auditService: $this->createMock(SigningAuditServiceInterface::class),
			verificationService: $this->createMock(SigningVerificationServiceInterface::class),
			userSession: $session,
			logger: $this->logger,
			l10n: $l10n,
			groupManager: $groups,
		);

	}//end controller()
}//end class
