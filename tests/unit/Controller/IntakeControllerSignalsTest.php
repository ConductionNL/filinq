<?php

/**
 * Unit tests for IntakeController's Overdue mode and deadline move.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link https://conduction.nl
 *
 * @spec openspec/changes/intake-worklist-deadline-and-duplicates/specs/intake-worklist-signals/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\IntakeController;
use OCA\Filinq\Controller\Support\IntakeHandling;
use OCA\Filinq\Exception\IntakeRefusedException;
use OCA\Filinq\Kernel\Contracts\IntakeDetachmentServiceInterface;
use OCA\Filinq\Kernel\Contracts\IntakeRoutingServiceInterface;
use OCA\Filinq\Kernel\Contracts\IntakeServiceInterface;
use OCA\Filinq\Service\Intake\IntakeNotificationReach;
use OCA\Filinq\Service\Intake\IntakeOverdueList;
use OCA\Filinq\Service\PartySuggestionService;
use OCP\AppFramework\Http;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * The worklist answers the Overdue mode, and a deadline moves through the service.
 *
 * @covers \OCA\Filinq\Controller\IntakeController
 * @uses   \OCA\Filinq\Controller\Support\IntakeHandling
 * @uses   \OCA\Filinq\Exception\IntakeRefusedException
 * @uses   \OCA\Filinq\Service\Intake\IntakeNotificationReach
 */
class IntakeControllerSignalsTest extends TestCase {

	/**
	 * The intake service.
	 *
	 * @var IntakeServiceInterface&MockObject
	 */
	private IntakeServiceInterface&MockObject $intake;

	/**
	 * The overdue list.
	 *
	 * @var IntakeOverdueList&MockObject
	 */
	private IntakeOverdueList&MockObject $overdue;

	/**
	 * The controller for a signed-in registrar.
	 *
	 * @return IntakeController
	 */
	private function controller(): IntakeController {
		$this->intake ??= $this->createMock(IntakeServiceInterface::class);
		$this->overdue ??= $this->createMock(IntakeOverdueList::class);
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('anna');
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);

		return new IntakeController(
			$this->createMock(IRequest::class),
			new IntakeHandling(
				intake: $this->intake,
				detachment: $this->createMock(IntakeDetachmentServiceInterface::class),
				routing: $this->createMock(IntakeRoutingServiceInterface::class),
				overdue: $this->overdue
			),
			$this->createMock(PartySuggestionService::class),
			$session,
			new IntakeNotificationReach(),
			$this->createMock(IGroupManager::class)
		);
	}//end controller()

	/**
	 * Mode `overdue` lists the overdue documents and not the waiting ones.
	 *
	 * @return void
	 */
	public function testTheOverdueModeListsTheOverdueDocuments(): void {
		$this->intake = $this->createMock(IntakeServiceInterface::class);
		$this->intake->expects($this->never())->method('listWaiting');
		$this->overdue = $this->createMock(IntakeOverdueList::class);
		$this->overdue->expects($this->once())->method('list')->willReturn([['uuid' => 'late', 'handlingDeadline' => '2026-10-08']]);

		$response = $this->controller()->index(mode: 'overdue');

		self::assertSame(Http::STATUS_OK, $response->getStatus());
		self::assertSame(
			['results' => [['uuid' => 'late', 'handlingDeadline' => '2026-10-08']], 'total' => 1, 'mode' => 'overdue'],
			$response->getData()
		);
	}//end testTheOverdueModeListsTheOverdueDocuments()

	/**
	 * Without a mode the worklist is still the waiting list.
	 *
	 * @return void
	 */
	public function testTheDefaultModeIsWaiting(): void {
		$this->intake = $this->createMock(IntakeServiceInterface::class);
		$this->intake->expects($this->once())->method('listWaiting')->willReturn([['uuid' => 'w']]);
		$this->overdue = $this->createMock(IntakeOverdueList::class);
		$this->overdue->expects($this->never())->method('list');

		$data = $this->controller()->index()->getData();

		self::assertSame([['uuid' => 'w']], $data['results']);
		self::assertSame(1, $data['total']);
	}//end testTheDefaultModeIsWaiting()

	/**
	 * A moved deadline answers the document; a refusal answers its status.
	 *
	 * @return void
	 */
	public function testADeadlineMoveAnswersTheDocumentOrTheRefusal(): void {
		$this->intake = $this->createMock(IntakeServiceInterface::class);
		$this->intake->method('changeDeadline')->willReturnCallback(
			static function (string $uuid, string $deadline, string $reason): array {
				if ($reason === '') {
					throw new IntakeRefusedException(message: 'Moving a deadline says why it moved.', status: 400);
				}

				return ['uuid' => $uuid, 'handlingDeadline' => $deadline, 'deadlineReason' => $reason];
			}
		);

		$moved = $this->controller()->changeDeadline(uuid: 'doc-1', deadline: '2026-10-21', reason: 'waiting for the attachment');
		self::assertSame(Http::STATUS_OK, $moved->getStatus());
		self::assertSame(['uuid' => 'doc-1', 'handlingDeadline' => '2026-10-21', 'deadlineReason' => 'waiting for the attachment'], $moved->getData());

		$refused = $this->controller()->changeDeadline(uuid: 'doc-1', deadline: '2026-10-21');
		self::assertSame(400, $refused->getStatus());
	}//end testADeadlineMoveAnswersTheDocumentOrTheRefusal()
}//end class
