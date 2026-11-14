<?php

/**
 * Unit tests for the mandate endpoints and failure answers of SigningFolderController.
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
use OCP\AppFramework\Http;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Mandates are listed, declared and withdrawn as asked; a refusal about the
 * caller's own input is a 400 in its own words, anything else a logged 500
 * that names no detail.
 *
 * @covers \OCA\Filinq\Controller\SigningFolderController
 */
class SigningFolderControllerMandatesTest extends TestCase {

	/**
	 * @var SigningFolderServiceInterface&MockObject
	 */
	private SigningFolderServiceInterface $folders;

	/**
	 * @var SigningMandateServiceInterface&MockObject
	 */
	private SigningMandateServiceInterface $mandates;

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
		$this->folders = $this->createMock(SigningFolderServiceInterface::class);
		$this->mandates = $this->createMock(SigningMandateServiceInterface::class);
		$this->logger = $this->createMock(LoggerInterface::class);

	}//end setUp()

	/**
	 * The controller for a logged-in user, with the given request parameters.
	 *
	 * @param array<string, mixed> $params Request parameters.
	 *
	 * @return SigningFolderController
	 */
	private function controller(array $params = []): SigningFolderController {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('ondertekenaar');
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);
		$request = $this->createMock(IRequest::class);
		$request->method('getParam')->willReturnCallback(
			static fn (string $key, $default = null) => ($params[$key] ?? $default)
		);
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnArgument(0);

		return new SigningFolderController(
			request: $request,
			folderService: $this->folders,
			mandateService: $this->mandates,
			userSession: $session,
			logger: $this->logger,
			l10n: $l10n
		);

	}//end controller()

	/**
	 * Expect one logged error starting with the given message.
	 *
	 * @param string $message The message.
	 *
	 * @return void
	 */
	private function expectLoggedFailure(string $message): void {
		$this->logger->expects($this->once())->method('error')->with(
			$message . ': boom',
			$this->callback(static fn (array $context): bool => $context['exception'] instanceof Exception)
		);

	}//end expectLoggedFailure()

	/**
	 * A failing folder read is a 500 with a fixed message, logged.
	 *
	 * @return void
	 */
	public function testAFailingFolderReadIsA500(): void {
		$this->folders->method('folder')->with('ondertekenaar', 10, 20)->willThrowException(new Exception('boom'));
		$this->expectLoggedFailure('Failed to read the signing folder');

		$response = $this->controller()->folder(limit: 10, offset: 20);

		$this->assertSame(Http::STATUS_INTERNAL_SERVER_ERROR, $response->getStatus());
		$this->assertSame(['error' => 'Failed to read the signing folder'], $response->getData());

	}//end testAFailingFolderReadIsA500()

	/**
	 * A failing signature run is a 500 with a fixed message, logged.
	 *
	 * @return void
	 */
	public function testAFailingSignatureRunIsA500(): void {
		$this->folders->method('signSelection')->willThrowException(new Exception('boom'));
		$this->expectLoggedFailure('Failed to sign the selection');

		$response = $this->controller(['requestIds' => ['req-1']])->signFolder();

		$this->assertSame(Http::STATUS_INTERNAL_SERVER_ERROR, $response->getStatus());
		$this->assertSame(['error' => 'Failed to sign the selection'], $response->getData());

	}//end testAFailingSignatureRunIsA500()

	/**
	 * The declarations are listed, and a failure to read them is a 500.
	 *
	 * @return void
	 */
	public function testMandatesAreListed(): void {
		$this->mandates->method('declarations')->willReturnOnConsecutiveCalls(
			['zaak/besluit' => ['groups' => ['bestuur']]],
			$this->throwException(new Exception('boom'))
		);
		$this->expectLoggedFailure('Failed to read the mandate declarations');
		$controller = $this->controller();

		$this->assertSame(['mandates' => ['zaak/besluit' => ['groups' => ['bestuur']]]], $controller->mandates()->getData());
		$this->assertSame(Http::STATUS_INTERNAL_SERVER_ERROR, $controller->mandates()->getStatus());

	}//end testMandatesAreListed()

	/**
	 * A declaration passes the type, groups and rule on and answers the result.
	 *
	 * @return void
	 */
	public function testAMandateIsDeclaredAsAsked(): void {
		$this->mandates->expects($this->once())->method('declareMandate')
			->with('zaak/besluit', ['bestuur', 'secretaris'], 'all')
			->willReturn(['groups' => ['bestuur', 'secretaris'], 'rule' => 'all']);

		$response = $this->controller(['typeReference' => 'zaak/besluit', 'groups' => ['bestuur', 'secretaris'], 'rule' => 'all'])->declareMandate();

		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertSame(
			['typeReference' => 'zaak/besluit', 'mandate' => ['groups' => ['bestuur', 'secretaris'], 'rule' => 'all']],
			$response->getData()
		);

	}//end testAMandateIsDeclaredAsAsked()

	/**
	 * Invalid input is a 400 in the service's own words; any other failure a logged 500.
	 *
	 * @return void
	 */
	public function testARefusedDeclarationIsA400AndAFailureA500(): void {
		$this->mandates->method('declareMandate')->willReturnOnConsecutiveCalls(
			$this->throwException(new InvalidArgumentException('A rule is any or all, not "most"')),
			$this->throwException(new Exception('boom'))
		);
		$this->expectLoggedFailure('Failed to record the mandate');
		$controller = $this->controller(['typeReference' => 'zaak/besluit', 'rule' => 'most']);

		$refused = $controller->declareMandate();
		$this->assertSame(Http::STATUS_BAD_REQUEST, $refused->getStatus());
		$this->assertSame(['error' => 'A rule is any or all, not "most"'], $refused->getData());

		$failed = $controller->declareMandate();
		$this->assertSame(Http::STATUS_INTERNAL_SERVER_ERROR, $failed->getStatus());
		$this->assertSame(['error' => 'Failed to record the mandate'], $failed->getData());

	}//end testARefusedDeclarationIsA400AndAFailureA500()

	/**
	 * A withdrawal names the type as app/schema; a failure is a logged 500.
	 *
	 * @return void
	 */
	public function testAMandateIsWithdrawnByAppAndSchema(): void {
		$this->mandates->expects($this->exactly(2))->method('withdrawMandate')->with('zaak/besluit')
			->willReturnOnConsecutiveCalls(true, $this->throwException(new Exception('boom')));
		$this->expectLoggedFailure('Failed to withdraw the mandate');
		$controller = $this->controller();

		$this->assertSame(['withdrawn' => true], $controller->withdrawMandate(typeApp: 'zaak', typeSchema: 'besluit')->getData());
		$this->assertSame(Http::STATUS_INTERNAL_SERVER_ERROR, $controller->withdrawMandate(typeApp: 'zaak', typeSchema: 'besluit')->getStatus());

	}//end testAMandateIsWithdrawnByAppAndSchema()
}//end class
