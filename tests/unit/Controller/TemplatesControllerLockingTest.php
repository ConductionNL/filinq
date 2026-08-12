<?php

/**
 * Wire-contract tests for the template duplicate / lock / unlock endpoints
 *
 * Covers `templates#duplicate` (POST api/templates/{id}/duplicate),
 * `templates#lock` (POST api/templates/{id}/lock) and `templates#unlock`
 * (DELETE api/templates/{id}/lock).
 *
 * The real TemplateRequestHandler is used (it needs only a logger) so the
 * exception-to-status mapping asserted here is the shipped one. The 409
 * conflict body of `lock()` is asserted structurally, because the UI reads
 * `lockedBy` / `lockedAt` out of it to tell the user who holds the lock.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/specs/template-management/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Tests\Unit\Controller;

use Exception;
use OCA\DocuDesk\Controller\TemplateRequestHandler;
use OCA\DocuDesk\Controller\TemplatesController;
use OCA\DocuDesk\Service\TemplateService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for template duplication and edit locking.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @psalm-suppress                              PropertyNotSetInConstructor
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class TemplatesControllerLockingTest extends TestCase {

	/**
	 * Mocked template service.
	 *
	 * @var TemplateService|MockObject
	 */
	private TemplateService|MockObject $templateService;

	/**
	 * Controller under test, with an authenticated session.
	 *
	 * @var TemplatesController
	 */
	private TemplatesController $controller;

	/**
	 * Set up an authenticated controller.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->templateService = $this->createMock(TemplateService::class);
		$this->controller = $this->buildController($this->authenticatedSession());

	}//end setUp()

	/**
	 * Build the controller for a given session.
	 *
	 * @param IUserSession $session The session the controller should see.
	 *
	 * @return TemplatesController The controller under test.
	 */
	private function buildController(IUserSession $session): TemplatesController {
		return new TemplatesController(
			'docudesk',
			$this->createMock(IRequest::class),
			$this->templateService,
			new TemplateRequestHandler($this->createMock(LoggerInterface::class)),
			$session,
			$this->createMock(LoggerInterface::class)
		);

	}//end buildController()

	/**
	 * Build a session with a logged-in user.
	 *
	 * @return IUserSession The authenticated session.
	 */
	private function authenticatedSession(): IUserSession {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);

		return $session;
	}//end authenticatedSession()

	/**
	 * Build a session with no logged-in user.
	 *
	 * @return IUserSession The anonymous session.
	 */
	private function anonymousSession(): IUserSession {
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn(null);

		return $session;
	}//end anonymousSession()

	/**
	 * Duplicating answers 200 with the NEW template object — a different id
	 * from the source, which is what the UI navigates to.
	 *
	 * @return void
	 */
	public function testDuplicateReturnsNewTemplate(): void {
		$duplicate = ['id' => 'uuid-copy', 'name' => 'Invoice (copy)'];

		$this->templateService->expects($this->once())
			->method('duplicateTemplate')
			->with('uuid-1')
			->willReturn($duplicate);

		$response = $this->controller->duplicate('uuid-1');

		$this->assertInstanceOf(JSONResponse::class, $response);
		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertSame($duplicate, $response->getData());
		$this->assertNotSame('uuid-1', $response->getData()['id']);

	}//end testDuplicateReturnsNewTemplate()

	/**
	 * Duplicating an unknown template surfaces the service's own 404.
	 *
	 * @return void
	 */
	public function testDuplicateSurfacesNotFound(): void {
		$this->templateService->method('duplicateTemplate')
			->willThrowException(new Exception('Template not found', 404));

		$response = $this->controller->duplicate('missing');

		$this->assertSame(Http::STATUS_NOT_FOUND, $response->getStatus());
		$this->assertSame(['error' => 'Template not found'], $response->getData());

	}//end testDuplicateSurfacesNotFound()

	/**
	 * An anonymous caller cannot duplicate a template.
	 *
	 * @return void
	 */
	public function testDuplicateRejectsAnonymousCaller(): void {
		$this->templateService->expects($this->never())->method('duplicateTemplate');

		$response = $this->buildController($this->anonymousSession())->duplicate('uuid-1');

		$this->assertSame(Http::STATUS_UNAUTHORIZED, $response->getStatus());
		$this->assertSame(['error' => 'Not authenticated'], $response->getData());

	}//end testDuplicateRejectsAnonymousCaller()

	/**
	 * Acquiring a free lock answers 200 with the locked template, and the lock
	 * is taken in the calling user's name.
	 *
	 * @return void
	 */
	public function testLockReturnsLockedTemplate(): void {
		$locked = [
			'id' => 'uuid-1',
			'lockedBy' => 'alice',
			'lockedAt' => '2026-08-09T10:00:00+00:00',
		];

		$this->templateService->expects($this->once())
			->method('acquireLock')
			->with('uuid-1', 'alice')
			->willReturn($locked);

		$response = $this->controller->lock('uuid-1');

		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertSame($locked, $response->getData());

	}//end testLockReturnsLockedTemplate()

	/**
	 * A lock held by someone else answers 409 with the service's structured
	 * conflict body decoded — the UI shows `lockedBy` / `lockedAt` from it, so
	 * the body must arrive as an array, not as a JSON string.
	 *
	 * @return void
	 */
	public function testLockReturnsDecodedConflictBody(): void {
		$conflict = [
			'error' => 'Template is locked',
			'lockedBy' => 'bob',
			'lockedAt' => '2026-08-09T09:00:00+00:00',
		];

		$this->templateService->method('acquireLock')
			->willThrowException(new Exception((string)json_encode($conflict), 409));

		$response = $this->controller->lock('uuid-1');

		$this->assertSame(Http::STATUS_CONFLICT, $response->getStatus());
		$this->assertSame($conflict, $response->getData());

	}//end testLockReturnsDecodedConflictBody()

	/**
	 * A 409 whose message is not JSON still answers 409, with the raw message
	 * under `error` — the status must not degrade to 500.
	 *
	 * @return void
	 */
	public function testLockHandlesNonJsonConflictMessage(): void {
		$this->templateService->method('acquireLock')
			->willThrowException(new Exception('Template is locked by bob', 409));

		$response = $this->controller->lock('uuid-1');

		$this->assertSame(Http::STATUS_CONFLICT, $response->getStatus());
		$this->assertSame(['error' => 'Template is locked by bob'], $response->getData());

	}//end testLockHandlesNonJsonConflictMessage()

	/**
	 * An anonymous caller cannot take an edit lock.
	 *
	 * @return void
	 */
	public function testLockRejectsAnonymousCaller(): void {
		$this->templateService->expects($this->never())->method('acquireLock');

		$response = $this->buildController($this->anonymousSession())->lock('uuid-1');

		$this->assertSame(Http::STATUS_UNAUTHORIZED, $response->getStatus());

	}//end testLockRejectsAnonymousCaller()

	/**
	 * Releasing a lock answers 200 with the unlocked template, released in the
	 * calling user's name.
	 *
	 * @return void
	 */
	public function testUnlockReturnsUnlockedTemplate(): void {
		$unlocked = ['id' => 'uuid-1', 'lockedBy' => null];

		$this->templateService->expects($this->once())
			->method('releaseLock')
			->with('uuid-1', 'alice')
			->willReturn($unlocked);

		$response = $this->controller->unlock('uuid-1');

		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertSame($unlocked, $response->getData());

	}//end testUnlockReturnsUnlockedTemplate()

	/**
	 * Releasing a lock held by another user surfaces the service's 403 — not a
	 * silent success that would let a second editor steal the lock.
	 *
	 * @return void
	 */
	public function testUnlockSurfacesForbidden(): void {
		$this->templateService->method('releaseLock')
			->willThrowException(new Exception('Lock held by another user', 403));

		$response = $this->controller->unlock('uuid-1');

		$this->assertSame(Http::STATUS_FORBIDDEN, $response->getStatus());
		$this->assertSame(['error' => 'Lock held by another user'], $response->getData());

	}//end testUnlockSurfacesForbidden()

	/**
	 * An anonymous caller cannot release an edit lock.
	 *
	 * @return void
	 */
	public function testUnlockRejectsAnonymousCaller(): void {
		$this->templateService->expects($this->never())->method('releaseLock');

		$response = $this->buildController($this->anonymousSession())->unlock('uuid-1');

		$this->assertSame(Http::STATUS_UNAUTHORIZED, $response->getStatus());

	}//end testUnlockRejectsAnonymousCaller()

}//end class
