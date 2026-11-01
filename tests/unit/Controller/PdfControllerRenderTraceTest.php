<?php

/**
 * Characterization tests for PdfController::render()
 *
 * Pins the arguments handed to the PDF service, the download response and
 * every error answer of the plain render endpoint.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/pdf-generation/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use Exception;
use OCA\Filinq\Controller\PdfController;
use OCA\Filinq\Kernel\Contracts\Pdfa3ConversionServiceInterface;
use OCA\Filinq\Kernel\Contracts\PdfServiceInterface;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Characterization of the plain render endpoint.
 *
 * @covers \OCA\Filinq\Controller\PdfController
 */
class PdfControllerRenderTraceTest extends TestCase {

	private PdfServiceInterface&MockObject $pdf;

	private LoggerInterface&MockObject $logger;

	/**
	 * Fresh doubles.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->pdf = $this->createMock(PdfServiceInterface::class);
		$this->logger = $this->createMock(LoggerInterface::class);

	}//end setUp()

	/**
	 * Without a session user the endpoint answers 401 and renders nothing.
	 *
	 * @return void
	 */
	public function testNotAuthenticatedAnswers401(): void {
		$this->pdf->expects($this->never())->method('renderPdf');

		$response = $this->controller(params: ['template' => '<p>x</p>'], signedIn: false)->render();

		$this->assertInstanceOf(JSONResponse::class, $response);
		$this->assertSame(401, $response->getStatus());
		$this->assertSame(['error' => 'Not authenticated'], $response->getData());

	}//end testNotAuthenticatedAnswers401()

	/**
	 * An empty template answers 400.
	 *
	 * @return void
	 */
	public function testEmptyTemplateAnswers400(): void {
		$this->pdf->expects($this->never())->method('renderPdf');

		$response = $this->controller(params: ['template' => ''])->render();

		$this->assertSame(400, $response->getStatus());
		$this->assertSame(['error' => 'Template content is required'], $response->getData());

	}//end testEmptyTemplateAnswers400()

	/**
	 * The template, data and options reach the service and the PDF comes back as a download.
	 *
	 * @return void
	 */
	public function testRequestReachesTheServiceAndDownloads(): void {
		$this->pdf->expects($this->once())->method('renderPdf')
			->with('<p>{{ a }}</p>', ['a' => 1], ['format' => 'A4'])
			->willReturn('%PDF-1.7');

		$response = $this->controller(
			params: ['template' => '<p>{{ a }}</p>', 'data' => ['a' => 1], 'options' => ['format' => 'A4'], 'filename' => 'brief.pdf']
		)->render();

		$this->assertInstanceOf(DataDownloadResponse::class, $response);
		$this->assertSame(200, $response->getStatus());
		$this->assertSame('%PDF-1.7', $response->getData());
		$this->assertSame('application/pdf', $response->getHeaders()['Content-Type']);
		$this->assertSame('attachment; filename="brief.pdf"', $response->getHeaders()['Content-Disposition']);

	}//end testRequestReachesTheServiceAndDownloads()

	/**
	 * Non-array data and options become empty arrays; the filename defaults.
	 *
	 * @return void
	 */
	public function testNonArrayDataAndOptionsAreEmptiedAndFilenameDefaults(): void {
		$this->pdf->expects($this->once())->method('renderPdf')
			->with('<p>x</p>', [], [])
			->willReturn('%PDF');

		$response = $this->controller(params: ['template' => '<p>x</p>', 'data' => 'oops', 'options' => 7])->render();

		$this->assertSame('attachment; filename="document.pdf"', $response->getHeaders()['Content-Disposition']);

	}//end testNonArrayDataAndOptionsAreEmptiedAndFilenameDefaults()

	/**
	 * A failure with an HTTP-range code answers with that code and logs.
	 *
	 * @return void
	 */
	public function testHttpRangeFailureKeepsItsCode(): void {
		$this->pdf->method('renderPdf')->willThrowException(new Exception('bad template', 422));
		$this->logger->expects($this->once())->method('error')
			->with('PDF generation failed: bad template', $this->callback(fn (array $ctx): bool => $ctx['exception'] instanceof Exception));

		$response = $this->controller(params: ['template' => '<p>x</p>'])->render();

		$this->assertSame(422, $response->getStatus());
		$this->assertSame(['error' => 'bad template'], $response->getData());

	}//end testHttpRangeFailureKeepsItsCode()

	/**
	 * Codes outside 400..599 answer 500; the bounds are 400 inclusive and 600 exclusive.
	 *
	 * @return void
	 */
	public function testOtherCodesAnswer500(): void {
		foreach ([0 => 500, 399 => 500, 400 => 400, 599 => 599, 600 => 500] as $code => $status) {
			$this->pdf = $this->createMock(PdfServiceInterface::class);
			$this->pdf->method('renderPdf')->willThrowException(new Exception('boom', $code));

			$response = $this->controller(params: ['template' => '<p>x</p>'])->render();

			$this->assertSame($status, $response->getStatus(), 'code ' . $code);
		}

	}//end testOtherCodesAnswer500()

	/**
	 * The controller over a request with $params.
	 *
	 * @param array<string, mixed> $params The request parameters.
	 * @param bool $signedIn Whether a session user exists.
	 *
	 * @return PdfController The controller.
	 */
	private function controller(array $params, bool $signedIn = true): PdfController {
		$request = $this->createMock(IRequest::class);
		$request->method('getParam')->willReturnCallback(
			fn (string $key, mixed $default = null): mixed => (array_key_exists($key, $params) === true ? $params[$key] : $default)
		);

		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnArgument(0);

		$session = $this->createMock(IUserSession::class);
		$user = null;
		if ($signedIn === true) {
			$user = $this->createMock(IUser::class);
		}

		$session->method('getUser')->willReturn($user);

		return new PdfController(
			appName: 'filinq',
			request: $request,
			logger: $this->logger,
			pdfService: $this->pdf,
			pdfa3Service: $this->createMock(Pdfa3ConversionServiceInterface::class),
			l10n: $l10n,
			userSession: $session,
		);

	}//end controller()
}//end class
