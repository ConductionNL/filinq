<?php

/**
 * The legal hold routes: refusals first, then the statuses of a normal run.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-4.1
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\LegalHoldCaseController;
use OCA\Filinq\Tests\Unit\Service\LegalHold\LegalHoldDoubles;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * LegalHoldCaseController over the real service and the legal hold doubles.
 */
class LegalHoldCaseControllerTest extends TestCase {
	use LegalHoldDoubles;

	/**
	 * The controller as a user, with request parameters.
	 *
	 * @param string               $userId The caller.
	 * @param array<string, mixed> $params The request parameters.
	 *
	 * @return LegalHoldCaseController The controller.
	 */
	private function controller(string $userId, array $params = []): LegalHoldCaseController {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn($userId);
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($userId === '' ? null : $user);
		$request = $this->createMock(IRequest::class);
		$request->method('getParams')->willReturn($params);
		$request->method('getParam')->willReturnCallback(static fn (string $key, $default = null) => ($params[$key] ?? $default));
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnArgument(0);

		return new LegalHoldCaseController('filinq', $request, $this->service(), $session, $l10n, new NullLogger());

	}//end controller()

	/**
	 * Placing and releasing are refused with 403 outside the authority groups, and nothing is frozen.
	 *
	 * @return void
	 */
	public function testUnauthorizedUsersGet403(): void {
		$this->record(uuid: 'doc-1');

		$create = $this->controller(userId: 'mallory', params: $this->input(documents: ['doc-1']))->create();
		$release = $this->controller(userId: 'mallory', params: ['releaseReason' => 'x'])->release(id: 'case-1');
		$list = $this->controller(userId: '')->index();

		$this->assertSame([403, 403, 403], [$create->getStatus(), $release->getStatus(), $list->getStatus()]);
		$this->assertSame('You are not allowed to place or release legal holds.', $create->getData()['error']);
		$this->assertSame([], $this->cases);
		$this->assertFalse($this->records['doc-1']->hasActiveLegalHold());

	}//end testUnauthorizedUsersGet403()

	/**
	 * A reason-less release is 400, a second release 409, an unknown case 404.
	 *
	 * @return void
	 */
	public function testRefusalsAnswerWithTheirStatus(): void {
		$this->record(uuid: 'doc-1');
		$created = $this->controller(userId: 'alice', params: $this->input(documents: ['doc-1']))->create();
		$uuid = $created->getData()['uuid'];

		$this->assertSame(201, $created->getStatus());
		$this->assertSame(400, $this->controller(userId: 'alice', params: ['releaseReason' => ''])->release(id: $uuid)->getStatus());
		$this->assertSame(200, $this->controller(userId: 'alice', params: ['releaseReason' => 'Settled.'])->release(id: $uuid)->getStatus());
		$this->assertSame(409, $this->controller(userId: 'alice', params: ['releaseReason' => 'Again.'])->release(id: $uuid)->getStatus());
		$this->assertSame(404, $this->controller(userId: 'alice')->show(id: 'nope')->getStatus());

	}//end testRefusalsAnswerWithTheirStatus()

	/**
	 * The status route answers readers of the record, and names the matter to hold authority only.
	 *
	 * @return void
	 */
	public function testTheStatusRoute(): void {
		$this->record(uuid: 'doc-1');
		$this->controller(userId: 'alice', params: $this->input(documents: ['doc-1']))->create();

		$reader = $this->controller(userId: 'mallory')->status(objectId: 'doc-1');
		$this->assertSame([200, ['held' => true, 'cases' => []]], [$reader->getStatus(), $reader->getData()]);
		$this->assertSame('Bezwaar 2026-004', $this->controller(userId: 'alice')->status(objectId: 'doc-1')->getData()['cases'][0]['name']);
		$this->assertSame(404, $this->controller(userId: 'mallory')->status(objectId: 'doc-unknown')->getStatus());

	}//end testTheStatusRoute()

	/**
	 * A failure that is not a refusal is a 500 with a plain message, not a stack trace.
	 *
	 * @return void
	 */
	public function testAFailureIsA500WithAPlainMessage(): void {
		$this->record(uuid: 'doc-1');
		$this->registerDown = true;

		$response = $this->controller(userId: 'alice', params: $this->input(documents: ['doc-1']))->create();

		$this->assertSame(500, $response->getStatus());
		$this->assertSame(['error' => 'The legal hold could not be saved. Try again later.'], $response->getData());
		$this->assertFalse($this->records['doc-1']->hasActiveLegalHold());

	}//end testAFailureIsA500WithAPlainMessage()
}//end class
