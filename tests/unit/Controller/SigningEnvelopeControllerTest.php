<?php

/**
 * Unit tests for the signing envelope endpoints
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
 * @spec openspec/specs/bulk-signing-field-builder/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\Filinq\Tests\Unit\Controller;

use InvalidArgumentException;
use OCA\Filinq\Controller\SigningEnvelopeController;
use OCA\Filinq\Kernel\Contracts\SigningEnvelopeServiceInterface;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * The endpoints pass the caller and the admin flag through and map outcomes to statuses.
 */
class SigningEnvelopeControllerTest extends TestCase {

	/**
	 * The service.
	 *
	 * @var SigningEnvelopeServiceInterface&MockObject
	 */
	private SigningEnvelopeServiceInterface $service;

	/**
	 * The logger.
	 *
	 * @var LoggerInterface&MockObject
	 */
	private LoggerInterface $logger;

	/**
	 * Build the controller for caller 'bob'.
	 *
	 * @param bool  $isAdmin Whether bob is an admin
	 * @param array $params  The request parameters
	 *
	 * @return SigningEnvelopeController
	 */
	private function controller(bool $isAdmin=false, array $params=[]): SigningEnvelopeController {
		$this->service ??= $this->createMock(SigningEnvelopeServiceInterface::class);
		$this->logger ??= $this->createMock(LoggerInterface::class);
		$request = $this->createMock(IRequest::class);
		$request->method('getParam')->willReturnCallback(static fn (string $key): mixed => $params[$key] ?? null);
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('bob');
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);
		$groups = $this->createMock(IGroupManager::class);
		$groups->method('isAdmin')->with('bob')->willReturn($isAdmin);

		return new SigningEnvelopeController('filinq', $request, $this->service, $session, $groups, $this->logger);

	}//end controller()

	/**
	 * Create passes only the non-empty known parameters and answers 201.
	 *
	 * @return void
	 */
	public function testCreatePassesTheKnownNonEmptyParameters(): void {
		$this->service = $this->createMock(SigningEnvelopeServiceInterface::class);
		$this->service->expects($this->once())->method('create')
			->with(['title' => 'Contracts', 'documents' => [['documentFileId' => 7]], 'signers' => ['ann']], 'bob')
			->willReturn(['uuid' => 'e1']);

		$response = $this->controller(params: ['title' => 'Contracts', 'documents' => [['documentFileId' => 7]], 'signers' => ['ann'], 'provider' => '', 'unknown' => 'x'])->create();

		$this->assertSame(201, $response->getStatus());
		$this->assertSame(['uuid' => 'e1'], $response->getData());

	}//end testCreatePassesTheKnownNonEmptyParameters()

	/**
	 * The listing passes the admin flag and wraps the rows.
	 *
	 * @return void
	 */
	public function testIndexWrapsTheRowsForTheCaller(): void {
		$this->service = $this->createMock(SigningEnvelopeServiceInterface::class);
		$this->service->expects($this->once())->method('listFor')->with('bob', true)->willReturn([['uuid' => 'e1']]);

		$response = $this->controller(isAdmin: true)->index();

		$this->assertSame(200, $response->getStatus());
		$this->assertSame(['results' => [['uuid' => 'e1']]], $response->getData());

	}//end testIndexWrapsTheRowsForTheCaller()

	/**
	 * A readable envelope is a 200; one the caller may not read is a 404, never a 403.
	 *
	 * @return void
	 */
	public function testShowAnswersTheEnvelopeOrNotFound(): void {
		$this->service = $this->createMock(SigningEnvelopeServiceInterface::class);
		$this->service->method('get')->willReturnCallback(static fn (string $id, string $uid, bool $isAdmin): ?array => $id === 'mine' ? ['uuid' => $id, 'admin' => $isAdmin] : null);
		$controller = $this->controller();

		$allowed = $controller->show(id: 'mine');
		$refused = $controller->show(id: 'theirs');

		$this->assertSame(200, $allowed->getStatus());
		$this->assertSame(['uuid' => 'mine', 'admin' => false], $allowed->getData());
		$this->assertSame(404, $refused->getStatus());
		$this->assertSame(['error' => 'Envelope not found'], $refused->getData());

	}//end testShowAnswersTheEnvelopeOrNotFound()

	/**
	 * Signing is for a named signer: anyone else gets a 404.
	 *
	 * @return void
	 */
	public function testSignAllForANonSignerIsNotFound(): void {
		$this->service = $this->createMock(SigningEnvelopeServiceInterface::class);
		$this->service->expects($this->once())->method('signAll')->with('e1', 'bob')->willReturn(null);

		$this->assertSame(404, $this->controller()->signAll(id: 'e1')->getStatus());

	}//end testSignAllForANonSignerIsNotFound()

	/**
	 * A 400 and a 409 carry their message.
	 *
	 * @return void
	 */
	public function testRefusalsCarryTheirMessage(): void {
		$this->service = $this->createMock(SigningEnvelopeServiceInterface::class);
		$this->service->method('cancel')->willThrowException(new RuntimeException('Already signed', 409));
		$this->service->method('create')->willThrowException(new InvalidArgumentException('No documents', 400));
		$controller = $this->controller();

		$conflict = $controller->cancel(id: 'e1');
		$invalid = $controller->create();

		$this->assertSame([409, ['error' => 'Already signed']], [$conflict->getStatus(), $conflict->getData()]);
		$this->assertSame([400, ['error' => 'No documents']], [$invalid->getStatus(), $invalid->getData()]);

	}//end testRefusalsCarryTheirMessage()

	/**
	 * An unexpected failure is logged and answers a 500 without the internal message.
	 *
	 * @return void
	 */
	public function testAnUnexpectedFailureIsLoggedAndHidden(): void {
		$failure = new RuntimeException('SQLSTATE secret', 3);
		$this->service = $this->createMock(SigningEnvelopeServiceInterface::class);
		$this->service->method('listFor')->willThrowException($failure);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->logger->expects($this->once())->method('error')->with('Envelope request failed: SQLSTATE secret', ['exception' => $failure]);

		$response = $this->controller()->index();

		$this->assertSame(500, $response->getStatus());
		$this->assertSame(['error' => 'Envelope request failed'], $response->getData());

	}//end testAnUnexpectedFailureIsLoggedAndHidden()
}//end class
