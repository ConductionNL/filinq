<?php

/**
 * Unit tests for BatchLifecycleController.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#requirement-cooperative-cancellation-req-ddras-004
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\BatchLifecycleController;
use OCA\Filinq\Kernel\Contracts\BatchStateServiceInterface;
use OCA\Filinq\Service\BatchLifecycleService;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * HTTP status and body of cancel and resume.
 *
 * @covers \OCA\Filinq\Controller\BatchLifecycleController
 * @uses   \OCA\Filinq\Service\BatchLifecycleService
 */
class BatchLifecycleControllerTest extends TestCase {

	/**
	 * Batch state double.
	 *
	 * @var BatchStateServiceInterface&MockObject
	 */
	private BatchStateServiceInterface&MockObject $state;

	/**
	 * Session double.
	 *
	 * @var IUserSession&MockObject
	 */
	private IUserSession&MockObject $session;

	/**
	 * Subject.
	 *
	 * @var BatchLifecycleController
	 */
	private BatchLifecycleController $controller;

	/**
	 * Build the subject with a logged-in user.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->state = $this->createMock(BatchStateServiceInterface::class);
		$this->session = $this->createMock(IUserSession::class);
		$this->session->method('getUser')->willReturn($this->createMock(IUser::class));
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnCallback(static fn (string $text): string => $text);
		$this->controller = new BatchLifecycleController(
			'filinq',
			$this->createMock(IRequest::class),
			new BatchLifecycleService(stateService: $this->state),
			$this->session,
			$l10n
		);

	}//end setUp()

	/**
	 * Cancel answers 202 with the new status.
	 *
	 * @return void
	 */
	public function testCancelAnswersAccepted(): void {
		$this->state->method('getBatch')->willReturn(['batchId' => 'b-1', 'status' => 'anonymizing', 'files' => []]);

		$response = $this->controller->cancel(batchId: 'b-1');

		self::assertSame(202, $response->getStatus());
		self::assertSame(['batchId' => 'b-1', 'batchStatus' => 'cancelling'], $response->getData());

	}//end testCancelAnswersAccepted()

	/**
	 * Cancel of a completed batch answers 409 with a message.
	 *
	 * @return void
	 */
	public function testCancelOfACompletedBatchAnswersConflict(): void {
		$this->state->method('getBatch')->willReturn(['batchId' => 'b-1', 'status' => 'completed', 'files' => []]);

		$response = $this->controller->cancel(batchId: 'b-1');

		self::assertSame(409, $response->getStatus());
		self::assertSame(
			['batchStatus' => 'completed', 'error' => 'This batch can no longer be cancelled'],
			$response->getData()
		);

	}//end testCancelOfACompletedBatchAnswersConflict()

	/**
	 * Resume of a running batch answers 409; of a foreign batch 404.
	 *
	 * @return void
	 */
	public function testResumeRefusals(): void {
		$this->state->method('getBatch')->willReturnOnConsecutiveCalls(
			['batchId' => 'b-1', 'status' => 'anonymizing', 'files' => []],
			null
		);

		$running = $this->controller->resume(batchId: 'b-1');
		$foreign = $this->controller->resume(batchId: 'b-2');

		self::assertSame(409, $running->getStatus());
		self::assertSame('Only an errored or cancelled batch can be resumed', $running->getData()['error']);
		self::assertSame(404, $foreign->getStatus());
		self::assertSame(['error' => 'Batch not found'], $foreign->getData());

	}//end testResumeRefusals()

	/**
	 * Resume answers 202 with the re-queued count.
	 *
	 * @return void
	 */
	public function testResumeAnswersAcceptedWithTheRequeuedCount(): void {
		$this->state->method('getBatch')->willReturn(
			['batchId' => 'b-1', 'status' => 'cancelled', 'files' => [['fileId' => 1, 'status' => 'extracted']]]
		);

		$response = $this->controller->resume(batchId: 'b-1');

		self::assertSame(202, $response->getStatus());
		self::assertSame(['batchId' => 'b-1', 'batchStatus' => 'review', 'requeued' => 1], $response->getData());

	}//end testResumeAnswersAcceptedWithTheRequeuedCount()

	/**
	 * Without a session neither endpoint reaches the batch.
	 *
	 * @return void
	 */
	public function testWithoutASessionBothAnswerUnauthorized(): void {
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn(null);
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnArgument(0);
		$this->state->expects(self::never())->method('getBatch');
		$controller = new BatchLifecycleController(
			'filinq',
			$this->createMock(IRequest::class),
			new BatchLifecycleService(stateService: $this->state),
			$session,
			$l10n
		);

		self::assertSame(401, $controller->cancel(batchId: 'b-1')->getStatus());
		self::assertSame(401, $controller->resume(batchId: 'b-1')->getStatus());

	}//end testWithoutASessionBothAnswerUnauthorized()
}//end class
