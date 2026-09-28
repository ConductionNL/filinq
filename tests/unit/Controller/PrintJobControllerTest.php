<?php

/**
 * Unit tests for PrintJobController
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
 * @spec openspec/changes/print-jobs-in-the-app/specs/print-preview/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\Filinq\Tests\Unit\Controller;

require_once __DIR__ . '/../Service/PrintJobDoubles.php';

use OCA\Filinq\Controller\PrintJobController;
use OCA\Filinq\Service\DataResolverService;
use OCA\Filinq\Service\PdfService;
use OCA\Filinq\Service\PrintJobService;
use OCA\Filinq\Service\TemplateService;
use OCA\Filinq\Tests\Unit\Service\PrintJobDoubles;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\BackgroundJob\IJobList;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * The print job endpoints over the real service, with in-memory rows.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 * @author   Conduction Development Team <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class PrintJobControllerTest extends TestCase {
	use PrintJobDoubles;

	/**
	 * The real service, shared by the controllers of each caller.
	 *
	 * @var PrintJobService
	 */
	private PrintJobService $service;

	/**
	 * Build the service.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$pdf = $this->createMock(PdfService::class);
		$pdf->method('renderPdf')->willReturn('%PDF-x');
		$templates = $this->createMock(TemplateService::class);
		$templates->method('getTemplate')->willReturn(['id' => 't', 'name' => 'Brief', 'content' => '']);

		$this->service = new PrintJobService(
			$pdf,
			$templates,
			$this->createMock(DataResolverService::class),
			$this->printJobRepository(),
			$this->printJobFileStore(),
			$this->createMock(IJobList::class),
			$this->createMock(LoggerInterface::class)
		);

	}//end setUp()

	/**
	 * A controller as one caller, with the given request parameters.
	 *
	 * @param string               $uid     The caller
	 * @param array<string, mixed> $params  Request parameters
	 * @param bool                 $isAdmin Whether the caller is an admin
	 *
	 * @return PrintJobController
	 */
	private function as(string $uid, array $params = [], bool $isAdmin = false): PrintJobController {
		$request = $this->createMock(IRequest::class);
		$request->method('getParam')->willReturnCallback(
			static fn (string $key, $default = null) => $params[$key] ?? $default
		);
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn($uid);
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);
		$groups = $this->createMock(IGroupManager::class);
		$groups->method('isAdmin')->willReturn($isAdmin);

		return new PrintJobController('filinq', $request, $this->service, $session, $groups, $this->createMock(LoggerInterface::class));

	}//end as()

	/**
	 * Somebody else's jobs stay out of the list.
	 *
	 * @return void
	 */
	public function testSomebodyElsesJobsStayOutOfTheList(): void {
		$this->as('handler-a', ['templateId' => 't'])->create();
		$this->as('handler-b', ['templateId' => 't'])->create();

		$listed = $this->as('handler-a')->index()->getData()['results'];

		$this->assertCount(1, $listed);
		$this->assertSame('handler-a', $listed[0]['requestedBy']);

		// An admin's list is also their own.
		$this->assertSame([], $this->as('admin', [], true)->index()->getData()['results']);

	}//end testSomebodyElsesJobsStayOutOfTheList()

	/**
	 * Another user cannot read or report on a job; an admin can read it.
	 *
	 * @return void
	 */
	public function testAnotherUsersJobIsForbidden(): void {
		$id = $this->as('handler-a', ['templateId' => 't'])->create()->getData()['jobId'];

		$this->assertSame(Http::STATUS_FORBIDDEN, $this->as('handler-b')->show($id)->getStatus());
		$this->assertSame(Http::STATUS_FORBIDDEN, $this->as('handler-b', ['status' => 'printed'])->updateStatus($id)->getStatus());
		$this->assertSame(Http::STATUS_OK, $this->as('admin', [], true)->show($id)->getStatus());

	}//end testAnotherUsersJobIsForbidden()

	/**
	 * A batch becomes one job; an empty batch is refused.
	 *
	 * @return void
	 */
	public function testABatchIsOneJob(): void {
		$response = $this->as('u', ['templateId' => 't', 'items' => [['data' => []], ['data' => []], ['data' => []]]])->batch();

		$this->assertSame(Http::STATUS_CREATED, $response->getStatus());
		$this->assertSame(3, $response->getData()['total']);
		$this->assertCount(1, $this->rows);
		$this->assertSame(Http::STATUS_BAD_REQUEST, $this->as('u', ['templateId' => 't', 'items' => []])->batch()->getStatus());

	}//end testABatchIsOneJob()

	/**
	 * The print service reports back through the status endpoint.
	 *
	 * @return void
	 */
	public function testThePrintServiceReportsBack(): void {
		$id = $this->as('u', ['templateId' => 't'])->create()->getData()['jobId'];

		$response = $this->as('u', ['status' => 'printed', 'details' => ['tray' => 2]])->updateStatus($id);

		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertSame('printed', $response->getData()['status']);
		$this->assertSame('{"tray":2}', $response->getData()['statusDetails']);
		$this->assertSame(Http::STATUS_BAD_REQUEST, $this->as('u', ['status' => 'lost'])->updateStatus($id)->getStatus());

	}//end testThePrintServiceReportsBack()

	/**
	 * The download is the PDF; a job still rendering is a conflict.
	 *
	 * @return void
	 */
	public function testTheDownload(): void {
		$id = $this->as('u', ['templateId' => 't', 'filename' => 'brief.pdf'])->create()->getData()['jobId'];

		$response = $this->as('u')->download($id);
		$this->assertInstanceOf(DataDownloadResponse::class, $response);

		$this->rows[$id]['status'] = 'rendering';
		$this->assertSame(Http::STATUS_CONFLICT, $this->as('u')->download($id)->getStatus());
		$this->assertSame(Http::STATUS_NOT_FOUND, $this->as('u')->download('nope')->getStatus());

	}//end testTheDownload()
}//end class
