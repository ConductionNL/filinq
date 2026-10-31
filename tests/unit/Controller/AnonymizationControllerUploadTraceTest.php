<?php

/**
 * Characterization tests for AnonymizationController::upload()
 *
 * Pins every refusal of the upload endpoint, the failure body and log line,
 * the status bounds, and that the request cannot name another user.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/anonymization/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\AnonymizationController;
use OCA\Filinq\Kernel\Contracts\AnonymizationServiceInterface;
use OCA\Filinq\Kernel\Contracts\AnonymizeRequestServiceInterface;
use OCA\Filinq\Kernel\Contracts\FileListingServiceInterface;
use OCP\IAppConfig;
use OCP\IL10N;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Characterization of the upload endpoint.
 *
 * @covers \OCA\Filinq\Controller\AnonymizationController
 */
class AnonymizationControllerUploadTraceTest extends TestCase {

	private FileListingServiceInterface&MockObject $files;

	private LoggerInterface&MockObject $logger;

	/**
	 * Temp files to remove.
	 *
	 * @var array<int, string>
	 */
	private array $temp = [];

	/**
	 * Fresh doubles.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->files = $this->createMock(FileListingServiceInterface::class);
		$this->logger = $this->createMock(LoggerInterface::class);

	}//end setUp()

	/**
	 * Remove temp files.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		foreach ($this->temp as $path) {
			if (is_file($path) === true) {
				unlink($path);
			}
		}

		parent::tearDown();

	}//end tearDown()

	/**
	 * A refused authentication answers with the guard's own status and body.
	 *
	 * @return void
	 */
	public function testRefusedAuthenticationAnswersTheGuardsResponse(): void {
		$this->files->expects($this->never())->method('uploadFile');

		$response = $this->controller(upload: $this->upload(contents: 'x'), signedIn: false)->upload();

		$this->assertSame(401, $response->getStatus());
		$this->assertSame(['error' => 'Not authenticated'], $response->getData());

	}//end testRefusedAuthenticationAnswersTheGuardsResponse()

	/**
	 * An upload without a temp path is no upload.
	 *
	 * @return void
	 */
	public function testUploadWithoutTmpNameIs400(): void {
		$this->files->expects($this->never())->method('uploadFile');

		$response = $this->controller(upload: ['name' => 'a.pdf', 'error' => UPLOAD_ERR_OK])->upload();

		$this->assertSame(400, $response->getStatus());
		$this->assertSame(['error' => 'No file uploaded'], $response->getData());

	}//end testUploadWithoutTmpNameIs400()

	/**
	 * A failed transfer names its error code.
	 *
	 * @return void
	 */
	public function testFailedTransferNamesTheCode(): void {
		$upload = $this->upload(contents: 'x');
		$upload['error'] = UPLOAD_ERR_PARTIAL;

		$response = $this->controller(upload: $upload)->upload();

		$this->assertSame(400, $response->getStatus());
		$this->assertSame(['error' => 'File upload failed with error code: ' . UPLOAD_ERR_PARTIAL], $response->getData());

	}//end testFailedTransferNamesTheCode()

	/**
	 * An unreadable temp file answers 500 and stores nothing.
	 *
	 * @return void
	 */
	public function testUnreadableTempFileIs500(): void {
		$this->files->expects($this->never())->method('uploadFile');
		$upload = ['name' => 'a.pdf', 'tmp_name' => sys_get_temp_dir() . '/l65-missing-' . uniqid(), 'error' => UPLOAD_ERR_OK];

		set_error_handler(static fn (): bool => true);
		try {
			$response = $this->controller(upload: $upload)->upload();
		} finally {
			restore_error_handler();
		}

		$this->assertSame(500, $response->getStatus());
		$this->assertSame(['error' => 'Failed to read uploaded file'], $response->getData());

	}//end testUnreadableTempFileIs500()

	/**
	 * The name and bytes reach the service; a forged userId changes nothing.
	 *
	 * @return void
	 */
	public function testNameAndBytesReachTheServiceWhateverTheRequestClaims(): void {
		$this->files->expects($this->once())->method('uploadFile')
			->with('brief.pdf', "%PDF bytes\n")
			->willReturn(['fileId' => 9]);

		$response = $this->controller(upload: $this->upload(contents: "%PDF bytes\n"), params: ['userId' => 'admin', 'requestedBy' => 'admin'])->upload();

		$this->assertSame(200, $response->getStatus());
		$this->assertSame(['fileId' => 9], $response->getData());

	}//end testNameAndBytesReachTheServiceWhateverTheRequestClaims()

	/**
	 * A storage failure answers with its HTTP-range code, else 500, and logs.
	 *
	 * @return void
	 */
	public function testFailureBodyLogAndStatusBounds(): void {
		foreach ([0 => 500, 399 => 500, 400 => 400, 599 => 599, 600 => 500] as $code => $status) {
			$this->files = $this->createMock(FileListingServiceInterface::class);
			$this->files->method('uploadFile')->willThrowException(new RuntimeException('disk full', $code));
			$this->logger = $this->createMock(LoggerInterface::class);
			$this->logger->expects($this->once())->method('error')
				->with('Failed to upload file: disk full', $this->callback(static fn (array $ctx): bool => $ctx['exception'] instanceof RuntimeException));

			$response = $this->controller(upload: $this->upload(contents: 'x'))->upload();

			$this->assertSame($status, $response->getStatus(), 'code ' . $code);
			$this->assertSame(['error' => 'Failed to upload file: disk full'], $response->getData());
		}

	}//end testFailureBodyLogAndStatusBounds()

	/**
	 * An uploaded-file array over a real temp file.
	 *
	 * @param string $contents The bytes.
	 *
	 * @return array<string, mixed> The upload.
	 */
	private function upload(string $contents): array {
		$path = (string)tempnam(sys_get_temp_dir(), 'l65-upload-');
		file_put_contents($path, $contents);
		$this->temp[] = $path;

		return ['name' => 'brief.pdf', 'tmp_name' => $path, 'error' => UPLOAD_ERR_OK, 'size' => strlen($contents)];
	}//end upload()

	/**
	 * The controller over a request carrying $upload and $params.
	 *
	 * @param array<string, mixed>|null $upload The uploaded `file` part.
	 * @param bool $signedIn Whether the auth guard passes.
	 * @param array<string, mixed> $params Other request parameters.
	 *
	 * @return AnonymizationController The controller.
	 */
	private function controller(?array $upload, bool $signedIn = true, array $params = []): AnonymizationController {
		$request = $this->createMock(IRequest::class);
		$request->method('getUploadedFile')->willReturnCallback(static fn (string $key): ?array => ($key === 'file' ? $upload : null));
		$request->method('getParams')->willReturn($params);
		$request->method('getParam')->willReturnCallback(
			static fn (string $key, mixed $default = null): mixed => (array_key_exists($key, $params) === true ? $params[$key] : $default)
		);

		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnCallback(static fn (string $text, array $args = []): string => vsprintf($text, $args));

		$guard = $this->createMock(AnonymizeRequestServiceInterface::class);
		$refusal = null;
		if ($signedIn === false) {
			$refusal = ['status' => 401, 'body' => ['error' => 'Not authenticated']];
		}

		$guard->method('requireAuthenticated')->willReturn($refusal);

		return new AnonymizationController(
			appName: 'filinq',
			request: $request,
			logger: $this->logger,
			anonymizationService: $this->createMock(AnonymizationServiceInterface::class),
			fileListingService: $this->files,
			l10n: $l10n,
			appConfig: $this->createMock(IAppConfig::class),
			anonymizeRequest: $guard
		);

	}//end controller()
}//end class
