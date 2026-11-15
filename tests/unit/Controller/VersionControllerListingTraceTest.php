<?php

/**
 * Characterization tests for VersionController::index() and download()
 *
 * Pins paging defaults, the download filename, and the error mapping of both.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/document-versions/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\VersionController;
use OCA\Filinq\Exception\ComparisonException;
use OCA\Filinq\Kernel\Contracts\DocumentVersionServiceInterface;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Characterization of listing and downloading file versions.
 *
 * @covers \OCA\Filinq\Controller\VersionController
 * @uses   \OCA\Filinq\Controller\AnswersNotAuthenticated
 * @uses   \OCA\Filinq\Exception\ComparisonException
 */
class VersionControllerListingTraceTest extends TestCase {

	private DocumentVersionServiceInterface&MockObject $service;

	private IRequest&MockObject $request;

	private LoggerInterface&MockObject $logger;

	/**
	 * Fresh doubles.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->service = $this->createMock(DocumentVersionServiceInterface::class);
		$this->request = $this->createMock(IRequest::class);
		$this->logger = $this->createMock(LoggerInterface::class);

	}//end setUp()

	/**
	 * The controller, with or without a session user.
	 *
	 * @param bool $signedIn Whether somebody is logged in.
	 *
	 * @return VersionController The controller.
	 */
	private function controller(bool $signedIn = true): VersionController {
		$session = $this->createMock(IUserSession::class);
		if ($signedIn === true) {
			$session->method('getUser')->willReturn($this->createMock(IUser::class));
		} else {
			$session->method('getUser')->willReturn(null);
		}

		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnArgument(0);

		return new VersionController('filinq', $this->request, $this->logger, $this->service, $l10n, $session);

	}//end controller()

	/**
	 * Feed the request these params; anything else answers its default.
	 *
	 * @param array<string, mixed> $params The request params.
	 *
	 * @return void
	 */
	private function params(array $params): void {
		$this->request->method('getParam')->willReturnCallback(
			static fn (string $key, mixed $default = null): mixed => (array_key_exists($key, $params) === true ? $params[$key] : $default)
		);

	}//end params()

	/**
	 * Listing passes limit and offset through and wraps the versions.
	 *
	 * @return void
	 */
	public function testIndexPassesPagingThrough(): void {
		$this->params(['limit' => '10', 'offset' => '20']);
		$this->service->expects($this->once())
			->method('listVersions')
			->with(42, 10, 20)
			->willReturn([['timestamp' => 1]]);

		$response = $this->controller()->index(fileId: 42);

		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertSame(['versions' => [['timestamp' => 1]]], $response->getData());

	}//end testIndexPassesPagingThrough()

	/**
	 * A non-positive limit falls back to 50 and a negative offset to 0.
	 *
	 * @return void
	 */
	public function testIndexClampsBadPaging(): void {
		$this->params(['limit' => '0', 'offset' => '-5']);
		$this->service->expects($this->once())->method('listVersions')->with(42, 50, 0)->willReturn([]);

		$response = $this->controller()->index(fileId: 42);

		$this->assertSame(['versions' => []], $response->getData());

	}//end testIndexClampsBadPaging()

	/**
	 * No session: 401 before the service is asked.
	 *
	 * @return void
	 */
	public function testIndexWithoutASessionIs401(): void {
		$this->service->expects($this->never())->method('listVersions');

		$response = $this->controller(signedIn: false)->index(fileId: 42);

		$this->assertSame(Http::STATUS_UNAUTHORIZED, $response->getStatus());

	}//end testIndexWithoutASessionIs401()

	/**
	 * Versions not available on the instance answer with that reason and its status.
	 *
	 * @return void
	 */
	public function testIndexReportsVersionsUnavailable(): void {
		$this->params([]);
		$this->service->method('listVersions')->willThrowException(
			new ComparisonException(statusCode: 501, reason: 'versions-unavailable')
		);

		$response = $this->controller()->index(fileId: 42);

		$this->assertSame(501, $response->getStatus());
		$this->assertSame(
			['error' => 'File versions are not available on this instance', 'reason' => 'versions-unavailable'],
			$response->getData()
		);

	}//end testIndexReportsVersionsUnavailable()

	/**
	 * Any other failure is a logged 500 with a generic message.
	 *
	 * @return void
	 */
	public function testIndexContainsAnUnexpectedFailure(): void {
		$this->params([]);
		$this->service->method('listVersions')->willThrowException(new RuntimeException('disk'));
		$this->logger->expects($this->once())->method('error')->with('Version listing failed', ['exception' => 'disk']);

		$response = $this->controller()->index(fileId: 42);

		$this->assertSame(Http::STATUS_INTERNAL_SERVER_ERROR, $response->getStatus());
		$this->assertSame(['error' => 'Could not list versions'], $response->getData());

	}//end testIndexContainsAnUnexpectedFailure()

	/**
	 * A download answers the bytes under a name built from file id and timestamp.
	 *
	 * @return void
	 */
	public function testDownloadAnswersTheBytes(): void {
		$this->service->expects($this->once())->method('readVersion')->with(42, 1700000000)->willReturn('bytes');

		$response = $this->controller()->download(fileId: 42, versionTimestamp: 1700000000);

		$this->assertInstanceOf(DataDownloadResponse::class, $response);
		$this->assertSame('bytes', $response->getData());
		$this->assertSame('application/octet-stream', $response->getHeaders()['Content-Type']);
		$this->assertStringContainsString('filename="version-42-1700000000"', $response->getHeaders()['Content-Disposition']);

	}//end testDownloadAnswersTheBytes()

	/**
	 * No session: 401 before the service is asked.
	 *
	 * @return void
	 */
	public function testDownloadWithoutASessionIs401(): void {
		$this->service->expects($this->never())->method('readVersion');

		$response = $this->controller(signedIn: false)->download(fileId: 42, versionTimestamp: 0);

		$this->assertSame(Http::STATUS_UNAUTHORIZED, $response->getStatus());

	}//end testDownloadWithoutASessionIs401()

	/**
	 * An inaccessible file is a 404 saying the document was not found.
	 *
	 * @return void
	 */
	public function testDownloadOfAnInaccessibleFileIs404(): void {
		$this->service->method('readVersion')->willThrowException(
			new ComparisonException(statusCode: 404, reason: 'not-found')
		);

		$response = $this->controller()->download(fileId: 42, versionTimestamp: 0);

		$this->assertInstanceOf(JSONResponse::class, $response);
		$this->assertSame(Http::STATUS_NOT_FOUND, $response->getStatus());
		$this->assertSame(['error' => 'Document not found', 'reason' => 'not-found'], $response->getData());

	}//end testDownloadOfAnInaccessibleFileIs404()

	/**
	 * Any other failure is a logged 500 with a generic message.
	 *
	 * @return void
	 */
	public function testDownloadContainsAnUnexpectedFailure(): void {
		$this->service->method('readVersion')->willThrowException(new RuntimeException('disk'));
		$this->logger->expects($this->once())->method('error')->with('Version download failed', ['exception' => 'disk']);

		$response = $this->controller()->download(fileId: 42, versionTimestamp: 0);

		$this->assertSame(Http::STATUS_INTERNAL_SERVER_ERROR, $response->getStatus());
		$this->assertSame(['error' => 'Could not download version'], $response->getData());

	}//end testDownloadContainsAnUnexpectedFailure()
}//end class
