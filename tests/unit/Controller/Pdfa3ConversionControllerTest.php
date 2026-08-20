<?php

/**
 * Unit tests for Pdfa3ConversionController
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Controller
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Tests\Unit\Controller;

use OCA\DocuDesk\Controller\Pdfa3ConversionController;
use OCA\DocuDesk\Exception\Pdfa3ConversionException;
use OCA\DocuDesk\Service\Pdfa3ConversionService;
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
 * Unit tests for Pdfa3ConversionController
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class Pdfa3ConversionControllerTest extends TestCase {

	/**
	 * @var Pdfa3ConversionController
	 */
	private Pdfa3ConversionController $controller;

	/**
	 * @var IRequest|MockObject
	 */
	private IRequest|MockObject $mockRequest;

	/**
	 * @var Pdfa3ConversionService|MockObject
	 */
	private Pdfa3ConversionService|MockObject $mockService;

	/**
	 * @var IRootFolder|MockObject
	 */
	private IRootFolder|MockObject $mockRootFolder;

	/**
	 * @var IUserSession|MockObject
	 */
	private IUserSession|MockObject $mockUserSession;

	/**
	 * Set up test environment
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->mockRequest = $this->createMock(IRequest::class);
		$mockLogger = $this->createMock(LoggerInterface::class);
		$this->mockService = $this->createMock(Pdfa3ConversionService::class);
		$this->mockRootFolder = $this->createMock(IRootFolder::class);
		$mockL10n = $this->createMock(IL10N::class);
		$mockL10n->method('t')->willReturnCallback(
			function ($text, $params = []) {
				return vsprintf($text, $params);
			}
		);
		$this->mockUserSession = $this->createMock(IUserSession::class);

		$mockUser = $this->createMock(IUser::class);
		$mockUser->method('getUID')->willReturn('testuser');
		$this->mockUserSession->method('getUser')->willReturn($mockUser);

		$this->controller = new Pdfa3ConversionController(
			'docudesk',
			$this->mockRequest,
			$mockLogger,
			$this->mockService,
			$this->mockRootFolder,
			$mockL10n,
			$this->mockUserSession
		);

	}//end setUp()

	/**
	 * Test convert returns 400 when fileId is missing.
	 *
	 * @return void
	 */
	public function testConvertReturns400WhenFileIdMissing(): void {
		$this->mockRequest->method('getParam')->willReturnMap([['fileId', 0, 0]]);

		$result = $this->controller->convert();

		$this->assertInstanceOf(JSONResponse::class, $result);
		$this->assertEquals(400, $result->getStatus());

	}//end testConvertReturns400WhenFileIdMissing()

	/**
	 * Test convert returns 404 (not disclosing existence) when the
	 * fileId does not resolve to a file the user can read — the
	 * IDOR-safe resolution pattern shared with ValidationController.
	 *
	 * @return void
	 */
	public function testConvertReturns404WhenFileNotFound(): void {
		$this->mockRequest->method('getParam')->willReturnMap([['fileId', 0, 42]]);

		$mockUserFolder = $this->createMock(Folder::class);
		$mockUserFolder->method('getById')->with(42)->willReturn([]);
		$this->mockRootFolder->method('getUserFolder')->with('testuser')->willReturn($mockUserFolder);

		$result = $this->controller->convert();

		$this->assertInstanceOf(JSONResponse::class, $result);
		$this->assertEquals(404, $result->getStatus());

	}//end testConvertReturns404WhenFileNotFound()

	/**
	 * Test convert returns a PDF/A-3 download with composition headers
	 * on success — proving the route is actually wired to the service
	 * (orphaned-capability guard).
	 *
	 * @return void
	 */
	public function testConvertReturnsDownloadWithCompositionHeadersOnSuccess(): void {
		$this->mockRequest->method('getParam')
			->willReturnMap(
				[
					['fileId', 0, 42],
					['metadata', [], ['identifier' => 'ZAAK-1']],
					['attachments', [], []],
					['filename', 'document-pdfa3.pdf', 'beschikking.pdf'],
				]
			);

		$mockFile = $this->createMock(File::class);
		$mockUserFolder = $this->createMock(Folder::class);
		$mockUserFolder->method('getById')->with(42)->willReturn([$mockFile]);
		$this->mockRootFolder->method('getUserFolder')->with('testuser')->willReturn($mockUserFolder);

		$this->mockService->method('convertExistingPdf')->willReturn(
			[
				'content' => '%PDF-1.7 fake pdfa3 content',
				'checksumSha256' => str_repeat('b', 64),
				'pages' => 3,
				'conformance' => '3-B',
			]
		);

		$result = $this->controller->convert();

		$this->assertInstanceOf(DataDownloadResponse::class, $result);
		$headers = $result->getHeaders();
		$this->assertEquals(str_repeat('b', 64), $headers['X-Docudesk-Pdfa3-Checksum-Sha256']);
		$this->assertEquals('3', $headers['X-Docudesk-Pdfa3-Pages']);
		$this->assertEquals('3-B', $headers['X-Docudesk-Pdfa3-Conformance']);

	}//end testConvertReturnsDownloadWithCompositionHeadersOnSuccess()

	/**
	 * Test convert maps a Pdfa3ConversionException to its declared HTTP
	 * status with reason + adminHint surfaced.
	 *
	 * @return void
	 */
	public function testConvertMapsPdfa3ExceptionToTypedErrorResponse(): void {
		$this->mockRequest->method('getParam')
			->willReturnMap(
				[
					['fileId', 0, 42],
					['metadata', [], []],
					['attachments', [], []],
					['filename', 'document-pdfa3.pdf', 'document-pdfa3.pdf'],
				]
			);

		$mockFile = $this->createMock(File::class);
		$mockUserFolder = $this->createMock(Folder::class);
		$mockUserFolder->method('getById')->with(42)->willReturn([$mockFile]);
		$this->mockRootFolder->method('getUserFolder')->with('testuser')->willReturn($mockUserFolder);

		$this->mockService->method('convertExistingPdf')->willThrowException(
			new Pdfa3ConversionException(
				reason: Pdfa3ConversionException::REASON_SOURCE_TOO_LARGE,
				message: 'Source PDF exceeds the configured cap.',
				adminHint: 'Increase the cap in app config.',
				code: 413
			)
		);

		$result = $this->controller->convert();

		$this->assertInstanceOf(JSONResponse::class, $result);
		$this->assertEquals(413, $result->getStatus());
		$this->assertEquals('source_too_large', $result->getData()['reason']);
		$this->assertNotEmpty($result->getData()['adminHint']);

	}//end testConvertMapsPdfa3ExceptionToTypedErrorResponse()
}//end class
