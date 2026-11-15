<?php

/**
 * Golden tests for the caller scope of SigningController's read actions
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
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

use OCA\Filinq\Controller\SigningController;
use OCA\Filinq\Controller\Support\SigningServices;
use OCA\Filinq\Service\SigningAuditService;
use OCA\Filinq\Service\SigningService;
use OCA\Filinq\Service\SigningVerificationService;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Pins the scope listRequests() and showRequest() hand to the signing
 * service: a caller is scoped to themselves, an administrator reads unscoped
 * (''), and nobody signed in is refused before the service is asked.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class SigningControllerScopeGoldenTest extends TestCase {

	/**
	 * The signing service.
	 *
	 * @var SigningService|MockObject
	 */
	private SigningService|MockObject $signing;

	/**
	 * Set up the signing service.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->signing = $this->createMock(SigningService::class);

	}//end setUp()

	/**
	 * Allowed, scoped: a caller who is no administrator reads as themselves.
	 *
	 * @return void
	 */
	public function testCallerIsScopedToThemselves(): void {
		$this->signing->expects($this->once())->method('listRequests')->with(callerUserId: 'alice')->willReturn([['id' => 'r1']]);
		$this->signing->expects($this->once())->method('getRequest')->with(requestId: 'r1', callerUserId: 'alice')->willReturn(['id' => 'r1']);

		$controller = $this->controller(['alice', false]);
		$this->assertSame([['id' => 'r1']], $controller->listRequests()->getData());
		$this->assertSame(200, $controller->showRequest('r1')->getStatus());

	}//end testCallerIsScopedToThemselves()

	/**
	 * Allowed, unscoped: an administrator reads every request.
	 *
	 * @return void
	 */
	public function testAdministratorReadsUnscoped(): void {
		$this->signing->expects($this->once())->method('listRequests')->with(callerUserId: '')->willReturn([]);
		$this->signing->expects($this->once())->method('getRequest')->with(requestId: 'r1', callerUserId: '')->willReturn(['id' => 'r1']);

		$controller = $this->controller(['root', true]);
		$this->assertSame(200, $controller->listRequests()->getStatus());
		$this->assertSame(200, $controller->showRequest('r1')->getStatus());

	}//end testAdministratorReadsUnscoped()

	/**
	 * Refused: a request out of the caller's scope is a 404, never a 403.
	 *
	 * @return void
	 */
	public function testOutOfScopeRequestIsNotFound(): void {
		$this->signing->method('getRequest')->with(requestId: 'r2', callerUserId: 'alice')->willReturn(null);

		$this->assertSame(404, $this->controller(['alice', false])->showRequest('r2')->getStatus());

	}//end testOutOfScopeRequestIsNotFound()

	/**
	 * Refused: nobody signed in answers 401 and the service is never asked.
	 *
	 * @return void
	 */
	public function testNobodySignedInIsRefused(): void {
		$this->signing->expects($this->never())->method('listRequests');
		$this->signing->expects($this->never())->method('getRequest');

		$controller = $this->controller(null);
		$this->assertSame(401, $controller->listRequests()->getStatus());
		$this->assertSame(401, $controller->showRequest('r1')->getStatus());

	}//end testNobodySignedInIsRefused()

	/**
	 * The controller with this caller signed in, or nobody.
	 *
	 * @param array{0:string,1:bool}|null $caller The uid and whether it is an administrator, or null.
	 *
	 * @return SigningController
	 */
	private function controller(?array $caller): SigningController {
		$session = $this->createMock(IUserSession::class);
		$groups  = $this->createMock(IGroupManager::class);
		if ($caller !== null) {
			$user = $this->createMock(IUser::class);
			$user->method('getUID')->willReturn($caller[0]);
			$session->method('getUser')->willReturn($user);
			$groups->method('isAdmin')->with($caller[0])->willReturn($caller[1]);
		}

		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnArgument(0);

		return new SigningController(
			$this->createMock(IRequest::class),
			new SigningServices(signing: $this->signing, audit: $this->createMock(SigningAuditService::class), verification: $this->createMock(SigningVerificationService::class)),
			$session,
			$this->createMock(LoggerInterface::class),
			$l10n,
			$groups
		);

	}//end controller()

}//end class
