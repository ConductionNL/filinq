<?php

/**
 * Characterization tests for PrintController
 *
 * Pins template resolution (stored and inline), the arguments handed to the
 * PDF service, both responses and every error answer of preview and
 * downloadPdfA.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/print-preview/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use Exception;
use OCA\Filinq\Controller\PrintController;
use OCA\Filinq\Kernel\Contracts\PdfServiceInterface;
use OCA\Filinq\Kernel\Contracts\TemplateServiceInterface;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Characterization of the print preview and PDF/A download endpoints.
 *
 * @covers \OCA\Filinq\Controller\PrintController
 */
class PrintControllerTraceTest extends TestCase {

	private const RECORD = [
		'content' => '<p>{{ a }}</p>',
		'name' => 'Brief',
		'format' => 'A5',
		'orientation' => 'L',
		'duplex' => true,
		'color' => false,
		'paperTray' => 'tray2',
		'stapling' => true,
	];

	private const RECORD_OPTIONS = ['format' => 'A5', 'orientation' => 'L', 'duplex' => true, 'color' => false, 'paperTray' => 'tray2', 'stapling' => true];

	private PdfServiceInterface&MockObject $pdf;

	private TemplateServiceInterface&MockObject $templates;

	private LoggerInterface&MockObject $logger;

	/**
	 * Fresh doubles.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->pdf = $this->createMock(PdfServiceInterface::class);
		$this->templates = $this->createMock(TemplateServiceInterface::class);
		$this->logger = $this->createMock(LoggerInterface::class);

	}//end setUp()

	/**
	 * Both endpoints answer 401 without a session user and resolve nothing.
	 *
	 * @return void
	 */
	public function testNotAuthenticated(): void {
		$this->templates->expects($this->never())->method('getTemplate');
		$controller = $this->controller(params: ['templateId' => 't1'], signedIn: false);

		foreach ([$controller->preview(), $controller->downloadPdfA()] as $response) {
			$this->assertSame(401, $response->getStatus());
			$this->assertSame(['error' => 'Not authenticated'], $response->getData());
		}

	}//end testNotAuthenticated()

	/**
	 * A stored template's content, title and print options reach the preview.
	 *
	 * @return void
	 */
	public function testPreviewOfAStoredTemplate(): void {
		$this->templates->expects($this->once())->method('getTemplate')->with('t1')->willReturn(self::RECORD);
		$this->pdf->expects($this->once())->method('renderHtmlPreview')
			->with('<p>{{ a }}</p>', ['a' => 1], self::RECORD_OPTIONS)
			->willReturn('<p>1</p>');

		$response = $this->controller(params: ['templateId' => 't1', 'template' => '<p>ignored</p>', 'data' => ['a' => 1], 'options' => ['format' => 'A3']])->preview();

		$this->assertInstanceOf(JSONResponse::class, $response);
		$this->assertSame(200, $response->getStatus());
		$this->assertSame(
			['html' => '<p>1</p>', 'title' => 'Brief', 'printConfig' => ['duplex' => true, 'color' => false, 'paperTray' => 'tray2', 'stapling' => true]],
			$response->getData()
		);

	}//end testPreviewOfAStoredTemplate()

	/**
	 * A sparse stored template falls back to the defaults.
	 *
	 * @return void
	 */
	public function testStoredTemplateDefaults(): void {
		$this->templates->method('getTemplate')->willReturn([]);
		$this->pdf->expects($this->once())->method('renderHtmlPreview')
			->with('', [], ['format' => 'A4', 'orientation' => 'P', 'duplex' => false, 'color' => true, 'paperTray' => 'default', 'stapling' => false])
			->willReturn('');

		$response = $this->controller(params: ['templateId' => 't1', 'data' => 'oops'])->preview();

		$this->assertSame('document', $response->getData()['title']);

	}//end testStoredTemplateDefaults()

	/**
	 * An inline template uses the request's options, and non-array options read as empty.
	 *
	 * @return void
	 */
	public function testPreviewOfAnInlineTemplate(): void {
		$this->templates->expects($this->never())->method('getTemplate');
		$this->pdf->expects($this->exactly(2))->method('renderHtmlPreview')
			->willReturnCallback(fn (string $content, array $data, array $options): string => $content . json_encode($options));

		$withOptions = $this->controller(params: ['template' => '<b>x</b>', 'options' => ['duplex' => 1, 'paperTray' => 5]])->preview();
		$this->assertSame(
			['html' => '<b>x</b>{"duplex":1,"paperTray":5}', 'title' => 'document', 'printConfig' => ['duplex' => true, 'color' => true, 'paperTray' => '5', 'stapling' => false]],
			$withOptions->getData()
		);

		$badOptions = $this->controller(params: ['template' => '<b>x</b>', 'options' => 'nope'])->preview();
		$this->assertSame('<b>x</b>[]', $badOptions->getData()['html']);

	}//end testPreviewOfAnInlineTemplate()

	/**
	 * Neither a template id nor content answers 400 on both endpoints, logged per endpoint.
	 *
	 * @return void
	 */
	public function testNoTemplateAnswers400(): void {
		$messages = [];
		$this->logger->method('error')->willReturnCallback(
			function (string $message, array $context) use (&$messages): void {
				$messages[] = $message;
				$this->assertInstanceOf(Exception::class, $context['exception']);
			}
		);
		$controller = $this->controller(params: ['template' => '']);

		foreach ([$controller->preview(), $controller->downloadPdfA()] as $response) {
			$this->assertInstanceOf(JSONResponse::class, $response);
			$this->assertSame(400, $response->getStatus());
			$this->assertSame(['error' => 'Either templateId or template content is required'], $response->getData());
		}

		$this->assertSame(
			['Print preview failed: Either templateId or template content is required', 'PDF/A download failed: Either templateId or template content is required'],
			$messages
		);

	}//end testNoTemplateAnswers400()

	/**
	 * The PDF/A download forces pdfa on, sets the title and names the file.
	 *
	 * @return void
	 */
	public function testDownloadPdfAOfAStoredTemplate(): void {
		$this->templates->method('getTemplate')->with('t1')->willReturn(self::RECORD);
		$this->pdf->expects($this->once())->method('renderPdf')
			->with('<p>{{ a }}</p>', ['a' => 1], self::RECORD_OPTIONS + ['pdfa' => true, 'title' => 'Brief'])
			->willReturn('%PDF-A');

		$response = $this->controller(params: ['templateId' => 't1', 'data' => ['a' => 1], 'filename' => 'brief.pdf'])->downloadPdfA();

		$this->assertInstanceOf(DataDownloadResponse::class, $response);
		$this->assertSame('%PDF-A', $response->getData());
		$this->assertSame('application/pdf', $response->getHeaders()['Content-Type']);
		$this->assertSame('attachment; filename="brief.pdf"', $response->getHeaders()['Content-Disposition']);

	}//end testDownloadPdfAOfAStoredTemplate()

	/**
	 * An inline download overrides any pdfa option and defaults the filename.
	 *
	 * @return void
	 */
	public function testDownloadPdfAOfAnInlineTemplate(): void {
		$this->pdf->expects($this->once())->method('renderPdf')
			->with('<i>y</i>', [], ['pdfa' => true, 'title' => 'document'])
			->willReturn('%PDF');

		$response = $this->controller(params: ['template' => '<i>y</i>', 'options' => ['pdfa' => false, 'title' => 'x']])->downloadPdfA();

		$this->assertSame('attachment; filename="document.pdf"', $response->getHeaders()['Content-Disposition']);

	}//end testDownloadPdfAOfAnInlineTemplate()

	/**
	 * Failure codes are kept inside 400..599 and answer 500 otherwise, on both endpoints.
	 *
	 * @return void
	 */
	public function testFailureCodesAreClamped(): void {
		foreach ([0 => 500, 399 => 500, 400 => 400, 599 => 599, 600 => 500] as $code => $status) {
			$this->templates = $this->createMock(TemplateServiceInterface::class);
			$this->templates->method('getTemplate')->willThrowException(new Exception('boom', $code));
			$controller = $this->controller(params: ['templateId' => 't1']);

			foreach ([$controller->preview(), $controller->downloadPdfA()] as $response) {
				$this->assertSame($status, $response->getStatus(), 'code ' . $code);
				$this->assertSame(['error' => 'boom'], $response->getData());
			}
		}

	}//end testFailureCodesAreClamped()

	/**
	 * The controller over a request with $params.
	 *
	 * @param array<string, mixed> $params The request parameters.
	 * @param bool $signedIn Whether a session user exists.
	 *
	 * @return PrintController The controller.
	 */
	private function controller(array $params, bool $signedIn = true): PrintController {
		$request = $this->createMock(IRequest::class);
		$request->method('getParam')->willReturnCallback(
			fn (string $key, mixed $default = null): mixed => (array_key_exists($key, $params) === true ? $params[$key] : $default)
		);

		$session = $this->createMock(IUserSession::class);
		$user = null;
		if ($signedIn === true) {
			$user = $this->createMock(IUser::class);
		}

		$session->method('getUser')->willReturn($user);

		return new PrintController(
			appName: 'filinq',
			request: $request,
			logger: $this->logger,
			pdfService: $this->pdf,
			templateService: $this->templates,
			userSession: $session,
		);
	}//end controller()
}//end class
