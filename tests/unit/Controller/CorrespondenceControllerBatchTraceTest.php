<?php

/**
 * Characterization tests for CorrespondenceController::generateBatch()
 *
 * Pins each refusal, that the acting user in the options is always the
 * session user, and the 200/202 split.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-batch-correspondence-rest-endpoints
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\CorrespondenceController;
use OCA\Filinq\Kernel\Contracts\CorrespondenceServiceInterface;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Characterization of the batch correspondence endpoint.
 *
 * @covers \OCA\Filinq\Controller\CorrespondenceController
 */
class CorrespondenceControllerBatchTraceTest extends TestCase {

	private CorrespondenceServiceInterface&MockObject $corr;

	private LoggerInterface&MockObject $logger;

	/**
	 * Fresh doubles.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->corr = $this->createMock(CorrespondenceServiceInterface::class);
		$this->logger = $this->createMock(LoggerInterface::class);

	}//end setUp()

	/**
	 * The options carry the SESSION user, whatever the body says.
	 *
	 * @return void
	 */
	public function testTheActingUserIsTheSessionUser(): void {
		$this->corr->expects($this->once())->method('generateBatch')
			->with('t-1', ['r-1', 'r-2'], ['format' => 'pdf', 'userId' => 'alice'])
			->willReturn(['generated' => 2]);

		$params = ['templateId' => 't-1', 'recipientIds' => ['r-1', 'r-2'], 'options' => ['format' => 'pdf', 'userId' => 'mallory'], 'userId' => 'mallory', 'requestedBy' => 'mallory'];
		$response = $this->controller(uid: 'alice', params: $params)->generateBatch();

		$this->assertSame(200, $response->getStatus());
		$this->assertSame(['generated' => 2], $response->getData());

	}//end testTheActingUserIsTheSessionUser()

	/**
	 * A queued batch answers 202; non-array options become just the user.
	 *
	 * @return void
	 */
	public function testAQueuedBatchAnswers202(): void {
		$this->corr->expects($this->once())->method('generateBatch')
			->with('t-1', ['r-1'], ['userId' => 'alice'])
			->willReturn(['jobId' => 'j-9']);

		$response = $this->controller(uid: 'alice', params: ['templateId' => 't-1', 'recipientIds' => ['r-1'], 'options' => 'nope'])->generateBatch();

		$this->assertSame(202, $response->getStatus());
		$this->assertSame(['jobId' => 'j-9'], $response->getData());

	}//end testAQueuedBatchAnswers202()

	/**
	 * Each refusal answers before anything is generated.
	 *
	 * @return void
	 */
	public function testEachRefusal(): void {
		$this->corr->expects($this->never())->method('generateBatch');
		$cases = [
			[null, [], 401, ['error' => 'Not authenticated']],
			['alice', ['recipientIds' => ['r-1']], 400, ['error' => 'templateId is required']],
			['alice', ['templateId' => 't-1'], 400, ['error' => 'recipientIds is required and must be an array']],
			['alice', ['templateId' => 't-1', 'recipientIds' => 'r-1'], 400, ['error' => 'recipientIds is required and must be an array']],
		];

		foreach ($cases as [$uid, $params, $status, $body]) {
			$response = $this->controller(uid: $uid, params: $params)->generateBatch();
			$this->assertSame([$status, $body], [$response->getStatus(), $response->getData()], (string)json_encode($params));
		}

	}//end testEachRefusal()

	/**
	 * A failure answers its HTTP-range code and message, and logs.
	 *
	 * @return void
	 */
	public function testFailure(): void {
		$this->corr->method('generateBatch')->willThrowException(new RuntimeException('template gone', 404));
		$this->logger->expects($this->once())->method('error')->with('Correspondence generation failed: template gone', $this->anything());

		$response = $this->controller(uid: 'alice', params: ['templateId' => 't-1', 'recipientIds' => ['r-1']])->generateBatch();

		$this->assertSame([404, ['error' => 'template gone']], [$response->getStatus(), $response->getData()]);

	}//end testFailure()

	/**
	 * The controller for session user $uid over $params.
	 *
	 * @param string|null $uid The session user, or null for none.
	 * @param array<string, mixed> $params The request body.
	 *
	 * @return CorrespondenceController The controller.
	 */
	private function controller(?string $uid, array $params): CorrespondenceController {
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

		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnArgument(0);

		return new CorrespondenceController(
			appName: 'filinq',
			request: $request,
			corrSvc: $this->corr,
			userSession: $session,
			logger: $this->logger,
			l10n: $l10n,
		);

	}//end controller()
}//end class
