<?php

/**
 * Unit tests for WaitingWorkController
 *
 * `GET api/documents/waiting` lists what waits for the person at the
 * keyboard, and refuses without a session (specify-capability-rows REQ-SCR-006).
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/specify-capability-rows/tasks.md#2-documents
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\WaitingWorkController;
use OCA\Filinq\Service\WaitingWorkService;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \OCA\Filinq\Controller\WaitingWorkController
 */
#[CoversClass(WaitingWorkController::class)]
class WaitingWorkControllerTest extends TestCase {

	/**
	 * The caller's waiting work is listed with its total, for the caller only.
	 *
	 * @return void
	 */
	public function testTheCallersWaitingWorkIsListed(): void {
		$items = [['kind' => 'review', 'id' => 'gen-1', 'name' => 'Nota', 'reason' => 'Due for review', 'since' => '']];
		$service = $this->createMock(WaitingWorkService::class);
		$service->expects($this->once())->method('listFor')->with(userId: 'bea')->willReturn($items);

		$response = $this->controller(service: $service, uid: 'bea')->index();

		$this->assertSame(200, $response->getStatus());
		$this->assertSame(['results' => $items, 'total' => 1], $response->getData());

	}//end testTheCallersWaitingWorkIsListed()

	/**
	 * Without a session the list answers 401 and nothing is read.
	 *
	 * @return void
	 */
	public function testNobodySignedInIsRefused(): void {
		$service = $this->createMock(WaitingWorkService::class);
		$service->expects($this->never())->method('listFor');

		$response = $this->controller(service: $service, uid: null)->index();

		$this->assertSame(401, $response->getStatus());

	}//end testNobodySignedInIsRefused()

	/**
	 * The controller with this caller, or nobody.
	 *
	 * @param WaitingWorkService $service The waiting work double.
	 * @param string|null        $uid     The caller, or null.
	 *
	 * @return WaitingWorkController
	 */
	private function controller(WaitingWorkService $service, ?string $uid): WaitingWorkController {
		$session = $this->createMock(IUserSession::class);
		if ($uid !== null) {
			$user = $this->createMock(IUser::class);
			$user->method('getUID')->willReturn($uid);
			$session->method('getUser')->willReturn($user);
		}

		return new WaitingWorkController(
			appName: 'filinq',
			request: $this->createMock(IRequest::class),
			waitingWork: $service,
			userSession: $session
		);

	}//end controller()
}//end class
