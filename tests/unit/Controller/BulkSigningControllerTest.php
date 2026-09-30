<?php

/**
 * Unit tests for the bulk send endpoints
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
 * @spec openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\Filinq\Tests\Unit\Controller;

use InvalidArgumentException;
use OCA\Filinq\Controller\BulkSigningController;
use OCA\Filinq\Service\BulkSigning\BulkSigningService;
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
class BulkSigningControllerTest extends TestCase {

	/**
	 * The service.
	 *
	 * @var BulkSigningService&MockObject
	 */
	private BulkSigningService $service;

	/**
	 * The request.
	 *
	 * @var IRequest&MockObject
	 */
	private IRequest $request;

	/**
	 * Build the controller for caller 'bob', not an admin.
	 *
	 * @return BulkSigningController
	 */
	private function controller(): BulkSigningController {
		$this->service ??= $this->createMock(BulkSigningService::class);
		$this->request ??= $this->createMock(IRequest::class);
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('bob');
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);
		$groups = $this->createMock(IGroupManager::class);
		$groups->method('isAdmin')->willReturn(false);

		return new BulkSigningController('filinq', $this->request, $this->service, $session, $groups, $this->createMock(LoggerInterface::class));

	}//end controller()

	/**
	 * Somebody else's batch is a 404, never a 403.
	 *
	 * @return void
	 */
	public function testSomebodyElsesBatchIsNotFound(): void {
		$this->service = $this->createMock(BulkSigningService::class);
		$this->service->expects($this->once())->method('get')->with('b1', 'bob', false)->willReturn(null);

		$this->assertSame(404, $this->controller()->show(id: 'b1')->getStatus());

	}//end testSomebodyElsesBatchIsNotFound()

	/**
	 * A second confirm answers 409 with the reason.
	 *
	 * @return void
	 */
	public function testASecondConfirmIsAConflict(): void {
		$this->service = $this->createMock(BulkSigningService::class);
		$this->service->method('confirm')->willThrowException(new RuntimeException('This bulk send was already confirmed or cancelled', 409));

		$response = $this->controller()->confirm(id: 'b1');

		$this->assertSame(409, $response->getStatus());
		$this->assertSame('This bulk send was already confirmed or cancelled', $response->getData()['error']);

	}//end testASecondConfirmIsAConflict()

	/**
	 * An upload creates a batch as the caller; an unreadable list is a 400 with the reason.
	 *
	 * @return void
	 */
	public function testAnUploadCreatesTheCallersBatch(): void {
		$path = tempnam(sys_get_temp_dir(), 'csv');
		file_put_contents($path, "email\nan@example.invalid\n");
		$this->request = $this->createMock(IRequest::class);
		$this->request->method('getUploadedFile')->with('file')->willReturn(['tmp_name' => $path, 'name' => 'list.csv', 'error' => UPLOAD_ERR_OK]);
		$this->request->method('getParam')->willReturnCallback(static fn (string $key): ?string => ['documentFileId' => '7', 'documentName' => 'a.pdf'][$key] ?? null);
		$this->service = $this->createMock(BulkSigningService::class);
		$this->service->expects($this->exactly(2))->method('createBatch')
			->with(['documentFileId' => '7', 'documentName' => 'a.pdf'], "email\nan@example.invalid\n", 'list.csv', 'bob')
			->willReturnOnConsecutiveCalls(['uuid' => 'b1', 'status' => 'ready'], $this->throwException(new InvalidArgumentException('The recipient list is empty', 400)));
		$controller = $this->controller();

		$this->assertSame(201, $controller->create()->getStatus());
		$refused = $controller->create();
		unlink($path);

		$this->assertSame(400, $refused->getStatus());
		$this->assertSame('The recipient list is empty', $refused->getData()['error']);

	}//end testAnUploadCreatesTheCallersBatch()

	/**
	 * No upload is a 400 before the service is asked.
	 *
	 * @return void
	 */
	public function testNoUploadIsRefused(): void {
		$this->request = $this->createMock(IRequest::class);
		$this->request->method('getUploadedFile')->willReturn(null);
		$this->service = $this->createMock(BulkSigningService::class);
		$this->service->expects($this->never())->method('createBatch');

		$this->assertSame(400, $this->controller()->create()->getStatus());

	}//end testNoUploadIsRefused()

	/**
	 * An unexpected failure is a 500 without the internal message.
	 *
	 * @return void
	 */
	public function testAnUnexpectedFailureHidesItsMessage(): void {
		$this->service = $this->createMock(BulkSigningService::class);
		$this->service->method('listFor')->willThrowException(new RuntimeException('SQLSTATE secret'));

		$response = $this->controller()->index();

		$this->assertSame(500, $response->getStatus());
		$this->assertSame('Bulk send failed', $response->getData()['error']);

	}//end testAnUnexpectedFailureHidesItsMessage()
}//end class
