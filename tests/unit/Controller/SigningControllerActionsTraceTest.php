<?php

/**
 * Characterization tests for SigningController request, sign, decline, bulk and verify paths
 *
 * Pins what reaches the signing service, the status codes, and that failure bodies never carry exception text.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-10-09-case-documents-and-the-flat-list/specs/document-register/spec.md
 * @spec openspec/changes/digital-signing-integration/tasks.md#6-1
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use Exception;
use OCA\Filinq\Controller\SigningController;
use OCA\Filinq\Controller\Support\SigningServices;
use OCA\Filinq\Kernel\Contracts\SigningAuditServiceInterface;
use OCA\Filinq\Kernel\Contracts\SigningServiceInterface;
use OCA\Filinq\Kernel\Contracts\SigningVerificationServiceInterface;
use OCP\AppFramework\Http;
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
 * Characterization of the signing endpoints' success and failure answers.
 *
 * @covers \OCA\Filinq\Controller\SigningController
 * @uses   \OCA\Filinq\Controller\Support\SigningServices
 * @uses   \OCA\Filinq\Controller\AnswersNotAuthenticated
 */
class SigningControllerActionsTraceTest extends TestCase {

	private SigningServiceInterface&MockObject $signing;

	private SigningVerificationServiceInterface&MockObject $verification;

	private IRequest&MockObject $request;

	private LoggerInterface&MockObject $logger;

	/**
	 * Fresh doubles.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->signing = $this->createMock(SigningServiceInterface::class);
		$this->verification = $this->createMock(SigningVerificationServiceInterface::class);
		$this->request = $this->createMock(IRequest::class);
		$this->logger = $this->createMock(LoggerInterface::class);

	}//end setUp()

	/**
	 * The controller, with or without a session user.
	 *
	 * @param bool $signedIn Whether somebody is logged in.
	 *
	 * @return SigningController The controller.
	 */
	private function controller(bool $signedIn = true): SigningController {
		$session = $this->createMock(IUserSession::class);
		if ($signedIn === true) {
			$user = $this->createMock(IUser::class);
			$user->method('getUID')->willReturn('anna');
			$session->method('getUser')->willReturn($user);
		} else {
			$session->method('getUser')->willReturn(null);
		}

		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnArgument(0);

		return new SigningController(
			$this->request,
			new SigningServices(
				signing: $this->signing,
				audit: $this->createMock(SigningAuditServiceInterface::class),
				verification: $this->verification
			),
			$session,
			$this->logger,
			$l10n,
			$this->createMock(IGroupManager::class)
		);

	}//end controller()

	/**
	 * Creating a request hands the whole body to the service and answers 201.
	 *
	 * @return void
	 */
	public function testCreateRequestAnswers201(): void {
		$this->request->method('getParams')->willReturn(['documentFileId' => 9, 'signers' => [['userId' => 'bob']]]);
		$this->signing->expects($this->once())
			->method('createRequest')
			->with(['documentFileId' => 9, 'signers' => [['userId' => 'bob']]])
			->willReturn(['id' => 'req-1', 'status' => 'pending']);

		$response = $this->controller()->createRequest();

		$this->assertSame(Http::STATUS_CREATED, $response->getStatus());
		$this->assertSame(['id' => 'req-1', 'status' => 'pending'], $response->getData());

	}//end testCreateRequestAnswers201()

	/**
	 * No session: 401 before the service is asked.
	 *
	 * @return void
	 */
	public function testCreateRequestWithoutASessionIs401(): void {
		$this->signing->expects($this->never())->method('createRequest');

		$response = $this->controller(signedIn: false)->createRequest();

		$this->assertSame(Http::STATUS_UNAUTHORIZED, $response->getStatus());
		$this->assertSame(['error' => 'Not authenticated'], $response->getData());

	}//end testCreateRequestWithoutASessionIs401()

	/**
	 * An invalid request keeps its 400 but the body carries only the generic message.
	 *
	 * @return void
	 */
	public function testAnInvalidCreateKeepsItsCodeAndHidesTheDetail(): void {
		$this->request->method('getParams')->willReturn([]);
		$this->signing->method('createRequest')->willThrowException(new Exception('documentFileId missing', 400));
		$this->logger->expects($this->once())
			->method('error')
			->with('Failed to create signing request: documentFileId missing', $this->anything());

		$response = $this->controller()->createRequest();

		$this->assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());
		$this->assertSame(['error' => 'Failed to create signing request'], $response->getData());

	}//end testAnInvalidCreateKeepsItsCodeAndHidesTheDetail()

	/**
	 * A failing sign is a 500 whose body does not reveal the request's existence.
	 *
	 * @return void
	 */
	public function testAFailingSignHidesTheDetail(): void {
		$this->request->method('getParam')->willReturnMap([['signerId', '', 's-1']]);
		$this->signing->expects($this->once())
			->method('sign')
			->with('req-1', 's-1')
			->willThrowException(new RuntimeException('Signing request not found: req-1'));

		$response = $this->controller()->sign(id: 'req-1');

		$this->assertSame(Http::STATUS_INTERNAL_SERVER_ERROR, $response->getStatus());
		$this->assertSame(['error' => 'Failed to sign document'], $response->getData());

	}//end testAFailingSignHidesTheDetail()

	/**
	 * A failing decline is a 500 with the generic decline message.
	 *
	 * @return void
	 */
	public function testAFailingDeclineHidesTheDetail(): void {
		$this->request->method('getParam')->willReturnMap(
			[
				['signerId', '', 's-1'],
				['reason', '', 'wrong document'],
			]
		);
		$this->signing->expects($this->once())
			->method('decline')
			->with('req-1', 's-1', 'wrong document')
			->willThrowException(new RuntimeException('Access denied: signing request belongs to another user'));

		$response = $this->controller()->decline(id: 'req-1');

		$this->assertSame(Http::STATUS_INTERNAL_SERVER_ERROR, $response->getStatus());
		$this->assertSame(['error' => 'Failed to decline signing request'], $response->getData());

	}//end testAFailingDeclineHidesTheDetail()

	/**
	 * Bulk signing passes the id list through and answers the per-request results.
	 *
	 * @return void
	 */
	public function testBulkSignPassesTheIds(): void {
		$this->request->method('getParam')->willReturnMap([['requestIds', [], ['req-1', 'req-2']]]);
		$this->signing->expects($this->once())
			->method('bulkSign')
			->with(['req-1', 'req-2'])
			->willReturn(['req-1' => 'signed', 'req-2' => 'failed']);

		$response = $this->controller()->bulkSign();

		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertSame(['req-1' => 'signed', 'req-2' => 'failed'], $response->getData());

	}//end testBulkSignPassesTheIds()

	/**
	 * A non-array id list is treated as empty.
	 *
	 * @return void
	 */
	public function testBulkSignWithANonArraySignsNothing(): void {
		$this->request->method('getParam')->willReturnMap([['requestIds', [], 'req-1']]);
		$this->signing->expects($this->once())->method('bulkSign')->with([])->willReturn([]);

		$response = $this->controller()->bulkSign();

		$this->assertSame([], $response->getData());

	}//end testBulkSignWithANonArraySignsNothing()

	/**
	 * No session: 401 before the service is asked.
	 *
	 * @return void
	 */
	public function testBulkSignWithoutASessionIs401(): void {
		$this->signing->expects($this->never())->method('bulkSign');

		$response = $this->controller(signedIn: false)->bulkSign();

		$this->assertSame(Http::STATUS_UNAUTHORIZED, $response->getStatus());

	}//end testBulkSignWithoutASessionIs401()

	/**
	 * A failing bulk sign is a 500 with the generic message.
	 *
	 * @return void
	 */
	public function testAFailingBulkSignIsA500(): void {
		$this->request->method('getParam')->willReturnMap([['requestIds', [], ['req-1']]]);
		$this->signing->method('bulkSign')->willThrowException(new RuntimeException('provider down'));

		$response = $this->controller()->bulkSign();

		$this->assertSame(Http::STATUS_INTERNAL_SERVER_ERROR, $response->getStatus());
		$this->assertSame(['error' => 'Failed to bulk sign'], $response->getData());

	}//end testAFailingBulkSignIsA500()

	/**
	 * A failing verification is a 500 with the generic message, asked as the session user.
	 *
	 * @return void
	 */
	public function testAFailingVerifyIsA500(): void {
		$this->verification->expects($this->once())
			->method('verifyDocument')
			->with(9, 'anna')
			->willThrowException(new RuntimeException('not a PDF'));

		$response = $this->controller()->verify(fileId: 9);

		$this->assertSame(Http::STATUS_INTERNAL_SERVER_ERROR, $response->getStatus());
		$this->assertSame(['error' => 'Failed to verify document'], $response->getData());

	}//end testAFailingVerifyIsA500()
}//end class
