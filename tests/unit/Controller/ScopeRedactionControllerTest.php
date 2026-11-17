<?php

/**
 * Scope decisions are only read, stored and committed on a file the caller
 * can open; a refusal is a 400 that wrote nothing.
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
 * @spec openspec/changes/image-redaction/specs/image-redaction/spec.md#requirement-a-page-a-page-range-or-a-whole-document-is-redacted-in-one-action-req-ddimr-009
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\ScopeRedactionController;
use OCA\Filinq\Service\Review\DocumentPageCounter;
use OCA\Filinq\Service\Review\ImageSeamGateway;
use OCA\Filinq\Service\Review\ScopeDecisionStore;
use OCA\Filinq\Service\Review\ScopeRedactionService;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * @covers \OCA\Filinq\Controller\ScopeRedactionController
 * @uses   \OCA\Filinq\Service\Review\ScopeRedactionService
 * @uses   \OCA\Filinq\Exception\ScopeDecisionRefusedException
 */
class ScopeRedactionControllerTest extends TestCase {

	/**
	 * The decision store.
	 *
	 * @var ScopeDecisionStore&MockObject
	 */
	private ScopeDecisionStore&MockObject $store;

	/**
	 * The seam.
	 *
	 * @var ImageSeamGateway&MockObject
	 */
	private ImageSeamGateway&MockObject $seam;

	protected function setUp(): void {
		parent::setUp();
		$this->store = $this->createMock(ScopeDecisionStore::class);
		$this->seam = $this->createMock(ImageSeamGateway::class);

	}//end setUp()

	/**
	 * A controller for a caller who can open file 701 (4 pages) and nothing else.
	 *
	 * @param array<string, mixed> $body The request body.
	 * @param bool                 $user Whether someone is signed in.
	 *
	 * @return ScopeRedactionController The controller.
	 */
	private function controller(array $body = [], bool $user = true): ScopeRedactionController {
		$request = $this->createMock(IRequest::class);
		$request->method('getParams')->willReturn($body);
		$session = $this->createMock(IUserSession::class);
		$petra = $this->createMock(IUser::class);
		$petra->method('getUID')->willReturn('petra');
		$session->method('getUser')->willReturn($user === true ? $petra : null);

		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn(701);
		$file->method('getMimeType')->willReturn('application/pdf');
		$folder = $this->createMock(Folder::class);
		$folder->method('getById')->willReturnCallback(static fn (int $id): array => $id === 701 ? [$file] : []);
		$root = $this->createMock(IRootFolder::class);
		$root->method('getUserFolder')->with('petra')->willReturn($folder);
		$pages = $this->createMock(DocumentPageCounter::class);
		$pages->method('count')->willReturn(4);

		return new ScopeRedactionController(
			$request,
			$session,
			$root,
			new ScopeRedactionService(store: $this->store, seam: $this->seam),
			$this->store,
			$pages,
			$this->seam,
			new NullLogger()
		);

	}//end controller()

	public function testAFileTheCallerCannotOpenIsNotFound(): void {
		$this->store->expects($this->never())->method('append');
		$this->store->expects($this->never())->method('withhold');

		$this->assertSame(404, $this->controller()->show(fileId: 702)->getStatus());
		$this->assertSame(404, $this->controller(body: ['scope' => 'document', 'bases' => ['5.1.1.c']])->decide(fileId: 702)->getStatus());
		$this->assertSame(404, $this->controller(body: ['scope' => 'document', 'bases' => ['5.1.1.c']])->commit(fileId: 702)->getStatus());

	}//end testAFileTheCallerCannotOpenIsNotFound()

	public function testNobodySignedInIsNotFound(): void {
		$this->assertSame(404, $this->controller(user: false)->show(fileId: 701)->getStatus());

	}//end testNobodySignedInIsNotFound()

	public function testShowReportsDecisionsPagesAndTheSeam(): void {
		$this->store->method('decisionsFor')->with(701)->willReturn([['scope' => 'page', 'fromPage' => 2, 'toPage' => 2, 'bases' => ['5.1.2.e']]]);
		$this->seam->method('available')->willReturn(false);

		$response = $this->controller()->show(fileId: 701);

		$this->assertSame(200, $response->getStatus());
		$this->assertSame(4, $response->getData()['pageCount']);
		$this->assertFalse($response->getData()['pageScopesAvailable']);
		$this->assertCount(1, $response->getData()['decisions']);

	}//end testShowReportsDecisionsPagesAndTheSeam()

	public function testADecisionIsStoredForTheCaller(): void {
		$this->store->expects($this->once())->method('append')
			->with(701, $this->callback(static fn (array $d): bool => $d['decidedBy'] === 'petra' && $d['toPage'] === 3))
			->willReturnArgument(1);

		$response = $this->controller(body: ['scope' => 'pageRange', 'fromPage' => 2, 'toPage' => 3, 'bases' => ['5.1.2.e'], 'decidedBy' => 'mallory'])->decide(fileId: 701);

		$this->assertSame(201, $response->getStatus());
		$this->assertSame('petra', $response->getData()['decidedBy']);

	}//end testADecisionIsStoredForTheCaller()

	public function testARefusalIsABadRequest(): void {
		$response = $this->controller(body: ['scope' => 'pageRange', 'fromPage' => 3, 'toPage' => 6, 'bases' => ['5.1.2.e']])->decide(fileId: 701);

		$this->assertSame(400, $response->getStatus());
		$this->assertSame('The document has 4 pages, so pages 3 to 6 cannot be redacted.', $response->getData()['error']);

	}//end testARefusalIsABadRequest()

	public function testAWholeDocumentCommitWithholdsIt(): void {
		$this->store->expects($this->once())->method('withhold')->with(701, ['5.1.1.c'], 'petra');

		$response = $this->controller(body: ['scope' => 'document', 'bases' => ['5.1.1.c']])->commit(fileId: 701);

		$this->assertSame(200, $response->getStatus());
		$this->assertTrue($response->getData()['withheld']);

	}//end testAWholeDocumentCommitWithholdsIt()

	public function testAFailingStoreIsAServerError(): void {
		$this->store->method('withhold')->willThrowException(new \RuntimeException('The anonymizationLink could not be written, so nothing changed: down'));

		$response = $this->controller(body: ['scope' => 'document', 'bases' => ['5.1.1.c']])->commit(fileId: 701);

		$this->assertSame(500, $response->getStatus());

	}//end testAFailingStoreIsAServerError()
}//end class
