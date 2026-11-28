<?php

/**
 * Unit tests for the delegation endpoints of SigningFolderController.
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

use Exception;
use InvalidArgumentException;
use OCA\Filinq\Controller\SigningFolderController;
use OCA\Filinq\Kernel\Contracts\SigningFolderServiceInterface;
use OCA\Filinq\Kernel\Contracts\SigningMandateServiceInterface;
use OCA\Filinq\Service\Signing\SigningDelegationService;
use OCP\AppFramework\Http;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * A delegation is declared and withdrawn for the calling signer only: the
 * delegator is always the session user, a refusal about the caller's input is
 * a 400, a delegation the caller did not give is a 404, and without a session
 * or a delegation store nothing is asked of the store.
 *
 * @covers \OCA\Filinq\Controller\SigningFolderController
 */
class SigningFolderControllerDelegationsTest extends TestCase {

	/**
	 * @var SigningDelegationService&MockObject
	 */
	private SigningDelegationService $delegations;

	/**
	 * @var LoggerInterface&MockObject
	 */
	private LoggerInterface $logger;

	/**
	 * Build the collaborators.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->delegations = $this->createMock(SigningDelegationService::class);
		$this->logger = $this->createMock(LoggerInterface::class);

	}//end setUp()

	/**
	 * The controller for the given user ('' = no session), with request parameters.
	 *
	 * @param array<string, mixed> $params    Request parameters.
	 * @param string               $userId    The session user, '' for none.
	 * @param bool                 $withStore Whether the delegation store is wired.
	 *
	 * @return SigningFolderController
	 */
	private function controller(array $params = [], string $userId = 'ondertekenaar', bool $withStore = true): SigningFolderController {
		$session = $this->createMock(IUserSession::class);
		if ($userId === '') {
			$session->method('getUser')->willReturn(null);
		} else {
			$user = $this->createMock(IUser::class);
			$user->method('getUID')->willReturn($userId);
			$session->method('getUser')->willReturn($user);
		}

		$request = $this->createMock(IRequest::class);
		$request->method('getParam')->willReturnCallback(
			static fn (string $key, $default = null) => ($params[$key] ?? $default)
		);
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnArgument(0);

		return new SigningFolderController(
			request: $request,
			folderService: $this->createMock(SigningFolderServiceInterface::class),
			mandateService: $this->createMock(SigningMandateServiceInterface::class),
			userSession: $session,
			logger: $this->logger,
			l10n: $l10n,
			delegations: ($withStore === true) ? $this->delegations : null
		);

	}//end controller()

	/**
	 * A declaration is stored for the session user with the posted period and types, as a 201.
	 *
	 * @return void
	 */
	public function testADelegationIsDeclaredForTheCaller(): void {
		$stored = ['id' => 'del-1', 'delegator' => 'ondertekenaar', 'delegate' => 'collega'];
		$this->delegations->expects($this->once())->method('declare')->with(
			'ondertekenaar',
			'collega',
			'2026-12-01',
			'2026-12-31',
			['contract']
		)->willReturn($stored);

		$response = $this->controller(
			[
				'delegate'  => 'collega',
				'from'      => '2026-12-01',
				'until'     => '2026-12-31',
				'types'     => ['contract'],
				'delegator' => 'iemand-anders',
			]
		)->declareDelegation();

		$this->assertSame(Http::STATUS_CREATED, $response->getStatus());
		$this->assertSame($stored, $response->getData());

	}//end testADelegationIsDeclaredForTheCaller()

	/**
	 * A refusal of the caller's own input is a 400 in its own words.
	 *
	 * @return void
	 */
	public function testARefusedDeclarationIsA400(): void {
		$this->delegations->method('declare')->willThrowException(new InvalidArgumentException('The period ends before it starts'));

		$response = $this->controller(['delegate' => 'collega'])->declareDelegation();

		$this->assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());
		$this->assertSame(['error' => 'The period ends before it starts'], $response->getData());

	}//end testARefusedDeclarationIsA400()

	/**
	 * Any other failure is a logged 500 that names no detail.
	 *
	 * @return void
	 */
	public function testAFailingDeclarationIsA500(): void {
		$this->delegations->method('declare')->willThrowException(new Exception('boom'));
		$this->logger->expects($this->once())->method('error')->with(
			'Failed to record the delegation: boom',
			$this->callback(static fn (array $context): bool => $context['exception'] instanceof Exception)
		);

		$response = $this->controller(['delegate' => 'collega'])->declareDelegation();

		$this->assertSame(Http::STATUS_INTERNAL_SERVER_ERROR, $response->getStatus());
		$this->assertSame(['error' => 'Failed to record the delegation'], $response->getData());

	}//end testAFailingDeclarationIsA500()

	/**
	 * Without a session nothing is declared or withdrawn: 401.
	 *
	 * @return void
	 */
	public function testWithoutASessionNothingIsAskedOfTheStore(): void {
		$this->delegations->expects($this->never())->method('declare');
		$this->delegations->expects($this->never())->method('withdraw');

		$controller = $this->controller(['delegate' => 'collega'], userId: '');

		$this->assertSame(Http::STATUS_UNAUTHORIZED, $controller->declareDelegation()->getStatus());
		$this->assertSame(Http::STATUS_UNAUTHORIZED, $controller->withdrawDelegation(id: 'del-1')->getStatus());

	}//end testWithoutASessionNothingIsAskedOfTheStore()

	/**
	 * Without the delegation store both endpoints answer 503.
	 *
	 * @return void
	 */
	public function testWithoutTheStoreBothEndpointsAre503(): void {
		$controller = $this->controller(['delegate' => 'collega'], withStore: false);

		$declared = $controller->declareDelegation();
		$withdrawn = $controller->withdrawDelegation(id: 'del-1');

		$this->assertSame(Http::STATUS_SERVICE_UNAVAILABLE, $declared->getStatus());
		$this->assertSame(['error' => 'Delegated signing is not available'], $declared->getData());
		$this->assertSame(Http::STATUS_SERVICE_UNAVAILABLE, $withdrawn->getStatus());

	}//end testWithoutTheStoreBothEndpointsAre503()

	/**
	 * A delegation the caller gave is withdrawn, asked as the caller.
	 *
	 * @return void
	 */
	public function testTheCallersDelegationIsWithdrawn(): void {
		$this->delegations->expects($this->once())->method('withdraw')->with('del-1', 'ondertekenaar')->willReturn(true);

		$response = $this->controller()->withdrawDelegation(id: 'del-1');

		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertSame(['withdrawn' => true], $response->getData());

	}//end testTheCallersDelegationIsWithdrawn()

	/**
	 * A delegation the caller did not give is a 404.
	 *
	 * @return void
	 */
	public function testSomeoneElsesDelegationIsA404(): void {
		$this->delegations->method('withdraw')->with('del-2', 'ondertekenaar')->willReturn(false);

		$response = $this->controller()->withdrawDelegation(id: 'del-2');

		$this->assertSame(Http::STATUS_NOT_FOUND, $response->getStatus());
		$this->assertSame(['error' => 'Delegation not found'], $response->getData());

	}//end testSomeoneElsesDelegationIsA404()

	/**
	 * A failing withdrawal is a logged 500 that names no detail.
	 *
	 * @return void
	 */
	public function testAFailingWithdrawalIsA500(): void {
		$this->delegations->method('withdraw')->willThrowException(new Exception('boom'));
		$this->logger->expects($this->once())->method('error')->with(
			'Failed to withdraw the delegation: boom',
			$this->callback(static fn (array $context): bool => $context['exception'] instanceof Exception)
		);

		$response = $this->controller()->withdrawDelegation(id: 'del-1');

		$this->assertSame(Http::STATUS_INTERNAL_SERVER_ERROR, $response->getStatus());
		$this->assertSame(['error' => 'Failed to withdraw the delegation'], $response->getData());

	}//end testAFailingWithdrawalIsA500()
}//end class
