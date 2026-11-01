<?php

/**
 * Characterization tests for PdfController::renderPdfA()
 *
 * Pins the plain and archival paths, the arguments handed to both services,
 * the response headers and every error answer of the PDF/A endpoint.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/pdfa3-conversion/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use Exception;
use OCA\Filinq\Controller\PdfController;
use OCA\Filinq\Exception\Pdfa3ConversionException;
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
 * Characterization of the PDF/A render endpoint.
 *
 * @covers \OCA\Filinq\Controller\PdfController
 * @uses   \OCA\Filinq\Exception\Pdfa3ConversionException
 */
class PdfControllerRenderPdfATraceTest extends TestCase {

	private PdfServiceInterface&MockObject $pdf;

	private Pdfa3ConversionServiceInterface&MockObject $pdfa3;

	private LoggerInterface&MockObject $logger;

	/**
	 * Fresh doubles.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->pdf = $this->createMock(PdfServiceInterface::class);
		$this->pdfa3 = $this->createMock(Pdfa3ConversionServiceInterface::class);
		$this->logger = $this->createMock(LoggerInterface::class);

	}//end setUp()

	/**
	 * Without a session user the endpoint answers 401 and renders nothing.
	 *
	 * @return void
	 */
	public function testNotAuthenticatedAnswers401(): void {
		$this->pdf->expects($this->never())->method('renderPdf');
		$this->pdfa3->expects($this->never())->method('convertHtml');

		$response = $this->controller(params: ['template' => '<p>x</p>', 'metadata' => ['a' => 1]], signedIn: false)->renderPdfA();

		$this->assertInstanceOf(JSONResponse::class, $response);
		$this->assertSame(401, $response->getStatus());
		$this->assertSame(['error' => 'Not authenticated'], $response->getData());

	}//end testNotAuthenticatedAnswers401()

	/**
	 * An empty template answers 400 on both paths.
	 *
	 * @return void
	 */
	public function testEmptyTemplateAnswers400(): void {
		$this->pdf->expects($this->never())->method('renderPdf');
		$this->pdfa3->expects($this->never())->method('convertHtml');

		$response = $this->controller(params: ['template' => '', 'metadata' => ['a' => 1]])->renderPdfA();

		$this->assertSame(400, $response->getStatus());
		$this->assertSame(['error' => 'Template content is required'], $response->getData());

	}//end testEmptyTemplateAnswers400()

	/**
	 * Without metadata or attachments the plain service renders with pdfa forced on.
	 *
	 * @return void
	 */
	public function testPlainPathForcesPdfaAndDownloads(): void {
		$this->pdfa3->expects($this->never())->method('convertHtml');
		$this->pdf->expects($this->never())->method('renderTemplateToHtml');
		$this->pdf->expects($this->once())->method('renderPdf')
			->with('<p>{{ a }}</p>', ['a' => 1], ['format' => 'A4', 'pdfa' => true])
			->willReturn('%PDF-A');

		$response = $this->controller(
			params: ['template' => '<p>{{ a }}</p>', 'data' => ['a' => 1], 'options' => ['format' => 'A4', 'pdfa' => false], 'filename' => 'akte.pdf']
		)->renderPdfA();

		$this->assertInstanceOf(DataDownloadResponse::class, $response);
		$this->assertSame(200, $response->getStatus());
		$this->assertSame('%PDF-A', $response->getData());
		$this->assertSame('application/pdf', $response->getHeaders()['Content-Type']);
		$this->assertSame('attachment; filename="akte.pdf"', $response->getHeaders()['Content-Disposition']);
		$this->assertArrayNotHasKey('X-Docudesk-Pdfa3-Pages', $response->getHeaders());

	}//end testPlainPathForcesPdfaAndDownloads()

	/**
	 * Non-array parameters read as empty, so non-array metadata keeps the plain path; the filename defaults.
	 *
	 * @return void
	 */
	public function testNonArrayParamsAreEmptiedAndFilenameDefaults(): void {
		$this->pdfa3->expects($this->never())->method('convertHtml');
		$this->pdf->expects($this->once())->method('renderPdf')
			->with('<p>x</p>', [], ['pdfa' => true])
			->willReturn('%PDF');

		$response = $this->controller(
			params: ['template' => '<p>x</p>', 'data' => 'oops', 'options' => 7, 'metadata' => 'meta', 'attachments' => 'att']
		)->renderPdfA();

		$this->assertSame('attachment; filename="document.pdf"', $response->getHeaders()['Content-Disposition']);

	}//end testNonArrayParamsAreEmptiedAndFilenameDefaults()

	/**
	 * Metadata sends the rendered HTML to the PDF/A-3 service, options untouched, with the composition headers.
	 *
	 * @return void
	 */
	public function testMetadataTakesTheArchivalPath(): void {
		$this->pdf->expects($this->never())->method('renderPdf');
		$this->pdf->expects($this->once())->method('renderTemplateToHtml')
			->with('<p>{{ a }}</p>', ['a' => 1])
			->willReturn('<p>1</p>');
		$this->pdfa3->expects($this->once())->method('convertHtml')
			->with('<p>1</p>', ['title' => 'T'], [], ['format' => 'A4'])
			->willReturn(['content' => '%PDF-A3', 'checksumSha256' => 'abc', 'pages' => 3, 'conformance' => 'PDF/A-3b', 'verified' => true]);

		$response = $this->controller(
			params: ['template' => '<p>{{ a }}</p>', 'data' => ['a' => 1], 'options' => ['format' => 'A4'], 'metadata' => ['title' => 'T'], 'filename' => 'arch.pdf']
		)->renderPdfA();

		$this->assertInstanceOf(DataDownloadResponse::class, $response);
		$this->assertSame('%PDF-A3', $response->getData());
		$headers = $response->getHeaders();
		$this->assertSame('attachment; filename="arch.pdf"', $headers['Content-Disposition']);
		$this->assertSame('application/pdf', $headers['Content-Type']);
		$this->assertSame('abc', $headers['X-Docudesk-Pdfa3-Checksum-Sha256']);
		$this->assertSame('3', $headers['X-Docudesk-Pdfa3-Pages']);
		$this->assertSame('PDF/A-3b', $headers['X-Docudesk-Pdfa3-Conformance']);
		$this->assertSame('1', $headers['X-Docudesk-Pdfa3-Verified']);

	}//end testMetadataTakesTheArchivalPath()

	/**
	 * Attachments alone also take the archival path; a missing verified flag reads "skipped".
	 *
	 * @return void
	 */
	public function testAttachmentsAloneTakeTheArchivalPath(): void {
		$attachments = [['name' => 'a.xml', 'mime' => 'text/xml', 'content' => '<a/>']];
		$this->pdf->expects($this->never())->method('renderPdf');
		$this->pdf->method('renderTemplateToHtml')->with('<p>x</p>', [])->willReturn('<p>x</p>');
		$this->pdfa3->expects($this->once())->method('convertHtml')
			->with('<p>x</p>', [], $attachments, [])
			->willReturn(['content' => '%PDF', 'checksumSha256' => 'c', 'pages' => 1, 'conformance' => 'PDF/A-3b']);

		$response = $this->controller(params: ['template' => '<p>x</p>', 'attachments' => $attachments])->renderPdfA();

		$headers = $response->getHeaders();
		$this->assertSame('attachment; filename="document.pdf"', $headers['Content-Disposition']);
		$this->assertSame('skipped', $headers['X-Docudesk-Pdfa3-Verified']);

	}//end testAttachmentsAloneTakeTheArchivalPath()

	/**
	 * A PDF/A-3 failure answers with its reason and admin hint and logs a warning.
	 *
	 * @return void
	 */
	public function testPdfa3FailureAnswersTyped(): void {
		$this->pdf->method('renderTemplateToHtml')->willReturn('<p>x</p>');
		$this->pdfa3->method('convertHtml')->willThrowException(
			new Pdfa3ConversionException(reason: 'source_too_large', message: 'too big', adminHint: 'raise the limit', code: 413)
		);
		$this->logger->expects($this->never())->method('error');
		$this->logger->expects($this->once())->method('warning')
			->with('PDF/A-3 generation failed: too big', ['reason' => 'source_too_large']);

		$response = $this->controller(params: ['template' => '<p>x</p>', 'metadata' => ['a' => 1]])->renderPdfA();

		$this->assertSame(413, $response->getStatus());
		$this->assertSame(['error' => 'too big', 'reason' => 'source_too_large', 'adminHint' => 'raise the limit'], $response->getData());

	}//end testPdfa3FailureAnswersTyped()

	/**
	 * A PDF/A-3 failure code outside 400..599 answers 500.
	 *
	 * @return void
	 */
	public function testPdfa3FailureOutsideRangeAnswers500(): void {
		$this->pdf->method('renderTemplateToHtml')->willReturn('<p>x</p>');
		$this->pdfa3->method('convertHtml')->willThrowException(
			new Pdfa3ConversionException(reason: 'render_failed', message: 'x', adminHint: 'h', code: 600)
		);

		$response = $this->controller(params: ['template' => '<p>x</p>', 'metadata' => ['a' => 1]])->renderPdfA();

		$this->assertSame(500, $response->getStatus());

	}//end testPdfa3FailureOutsideRangeAnswers500()

	/**
	 * A generic failure logs an error and keeps an HTTP-range code; the bounds are 400 and 599.
	 *
	 * @return void
	 */
	public function testGenericFailureClampsItsCode(): void {
		foreach ([0 => 500, 399 => 500, 400 => 400, 599 => 599, 600 => 500] as $code => $status) {
			$this->pdf = $this->createMock(PdfServiceInterface::class);
			$this->pdf->method('renderPdf')->willThrowException(new Exception('boom', $code));
			$this->logger = $this->createMock(LoggerInterface::class);
			$this->logger->expects($this->once())->method('error')
				->with('PDF/A generation failed: boom', $this->callback(fn (array $ctx): bool => $ctx['exception'] instanceof Exception));

			$response = $this->controller(params: ['template' => '<p>x</p>'])->renderPdfA();

			$this->assertSame($status, $response->getStatus(), 'code ' . $code);
			$this->assertSame(['error' => 'boom'], $response->getData());
		}

	}//end testGenericFailureClampsItsCode()

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
			pdfa3Service: $this->pdfa3,
			l10n: $l10n,
			userSession: $session,
		);

	}//end controller()
}//end class
