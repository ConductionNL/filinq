<?php

/**
 * Unit tests for EmlPreviewController
 *
 * Covers the file-access guard: the endpoint renders the ORIGINAL,
 * un-redacted message, so a caller-supplied file id must be resolved
 * through the caller's own file tree before anything is rendered.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Controller
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Tests\Unit\Controller;

use OCA\DocuDesk\Controller\EmlPreviewController;
use OCA\DocuDesk\Service\EmlPreviewService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for EmlPreviewController.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://conduction.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class EmlPreviewControllerTest extends TestCase {

	/**
	 * @var EmlPreviewService|MockObject
	 */
	private EmlPreviewService|MockObject $mockService;

	/**
	 * @var IUserSession|MockObject
	 */
	private IUserSession|MockObject $mockUserSession;

	/**
	 * @var IRootFolder|MockObject
	 */
	private IRootFolder|MockObject $mockRootFolder;

	/**
	 * @var EmlPreviewController
	 */
	private EmlPreviewController $controller;

	/**
	 * Build the controller with mocked collaborators.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->mockService = $this->createMock(EmlPreviewService::class);
		$this->mockUserSession = $this->createMock(IUserSession::class);
		$this->mockRootFolder = $this->createMock(IRootFolder::class);

		$mockL10n = $this->createMock(IL10N::class);
		$mockL10n->method('t')->willReturnArgument(0);

		$this->controller = new EmlPreviewController(
			'docudesk',
			$this->createMock(IRequest::class),
			$this->mockService,
			$this->createMock(LoggerInterface::class),
			$this->mockUserSession,
			$this->mockRootFolder,
			$mockL10n
		);

	}//end setUp()

	/**
	 * Point the session at a user whose folder resolves $fileId to $nodes.
	 *
	 * @param array $nodes Nodes the caller's own user folder resolves the id to.
	 *
	 * @return void
	 */
	private function givenUserFolderResolvesTo(array $nodes): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('bob');
		$this->mockUserSession->method('getUser')->willReturn($user);

		$folder = $this->createMock(Folder::class);
		$folder->method('getById')->willReturn($nodes);
		$this->mockRootFolder->method('getUserFolder')->with('bob')->willReturn($folder);

	}//end givenUserFolderResolvesTo()

	/**
	 * A file id the caller cannot resolve in their own tree is refused with
	 * 404 — and the render service is never reached, so no un-redacted bytes
	 * are produced.
	 *
	 * @return void
	 */
	public function testPreviewRefusesFileIdOutsideTheCallersOwnTree(): void {
		$this->givenUserFolderResolvesTo([]);
		$this->mockService->expects($this->never())->method('renderOriginalPreview');

		$response = $this->controller->preview(fileId: 21992);

		$this->assertInstanceOf(JSONResponse::class, $response);
		$this->assertSame(Http::STATUS_NOT_FOUND, $response->getStatus());

	}//end testPreviewRefusesFileIdOutsideTheCallersOwnTree()

	/**
	 * An unauthenticated caller is refused with 401 and never reaches the
	 * render service.
	 *
	 * @return void
	 */
	public function testPreviewRefusesUnauthenticatedCaller(): void {
		$this->mockUserSession->method('getUser')->willReturn(null);
		$this->mockService->expects($this->never())->method('renderOriginalPreview');

		$response = $this->controller->preview(fileId: 21992);

		$this->assertInstanceOf(JSONResponse::class, $response);
		$this->assertSame(Http::STATUS_UNAUTHORIZED, $response->getStatus());

	}//end testPreviewRefusesUnauthenticatedCaller()

	/**
	 * A file id that resolves to a folder rather than a file is refused with
	 * 404 rather than handed to the renderer.
	 *
	 * @return void
	 */
	public function testPreviewRefusesNonFileNode(): void {
		$this->givenUserFolderResolvesTo([$this->createMock(Folder::class)]);
		$this->mockService->expects($this->never())->method('renderOriginalPreview');

		$response = $this->controller->preview(fileId: 21992);

		$this->assertInstanceOf(JSONResponse::class, $response);
		$this->assertSame(Http::STATUS_NOT_FOUND, $response->getStatus());

	}//end testPreviewRefusesNonFileNode()

	/**
	 * A file the caller CAN resolve in their own tree — including one reached
	 * through a share, which is mounted inside the user folder — still
	 * renders, so the guard grants nothing less than before.
	 *
	 * @return void
	 */
	public function testPreviewRendersFileReachableByTheCaller(): void {
		$this->givenUserFolderResolvesTo([$this->createMock(File::class)]);
		$this->mockService->expects($this->once())
			->method('renderOriginalPreview')
			->with(fileId: 21991)
			->willReturn('%PDF-1.7 bytes');

		$response = $this->controller->preview(fileId: 21991);

		$this->assertInstanceOf(DataDownloadResponse::class, $response);

	}//end testPreviewRendersFileReachableByTheCaller()

	/**
	 * A render failure on a file the caller may read still surfaces as 422,
	 * unchanged by the guard.
	 *
	 * @return void
	 */
	public function testPreviewStillReturns422OnRenderFailure(): void {
		$this->givenUserFolderResolvesTo([$this->createMock(File::class)]);
		$this->mockService->method('renderOriginalPreview')
			->willThrowException(new \RuntimeException('EML preview requires the OpenRegister anonymise-EML API.'));

		$response = $this->controller->preview(fileId: 21991);

		$this->assertInstanceOf(JSONResponse::class, $response);
		$this->assertSame(422, $response->getStatus());

	}//end testPreviewStillReturns422OnRenderFailure()
}//end class
