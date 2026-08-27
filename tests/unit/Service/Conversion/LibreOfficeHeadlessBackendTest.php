<?php

/**
 * Unit tests for LibreOfficeHeadlessBackend
 *
 * Covers: isAvailable() paths (tenant flag, binary check), canHandle()
 * for supported MIMEs/extensions, and lock-acquisition failure.
 * The actual soffice invocation is not tested here (shell-out is
 * tested at integration level); these unit tests exercise the
 * class's internal logic with all I/O mocked.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Conversion
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/pdf-conversion-service/tasks.md#task-11
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\Filinq\Tests\Unit\Service\Conversion;

use OCA\Filinq\Exception\ConversionFailedException;
use OCA\Filinq\Service\Conversion\LibreOfficeHeadlessBackend;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\IAppConfig;
use OCP\Lock\ILockingProvider;
use OCP\Lock\LockedException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for LibreOfficeHeadlessBackend
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Conversion
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class LibreOfficeHeadlessBackendTest extends TestCase {

	/**
	 * App config mock.
	 *
	 * @var IAppConfig|MockObject
	 */
	private IAppConfig|MockObject $appConfig;

	/**
	 * Locking provider mock.
	 *
	 * @var ILockingProvider|MockObject
	 */
	private ILockingProvider|MockObject $lockingProvider;

	/**
	 * Logger mock.
	 *
	 * @var LoggerInterface|MockObject
	 */
	private LoggerInterface|MockObject $logger;

	/**
	 * Set up the test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->appConfig = $this->createMock(originalClassName: IAppConfig::class);
		$this->lockingProvider = $this->createMock(originalClassName: ILockingProvider::class);
		$this->logger = $this->createMock(originalClassName: LoggerInterface::class);

	}//end setUp()

	/**
	 * Build a backend instance with configurable app-config returns.
	 *
	 * @param array<string,string> $configMap Key-value config returns.
	 *
	 * @return LibreOfficeHeadlessBackend
	 */
	private function makeBackend(array $configMap = []): LibreOfficeHeadlessBackend {
		$this->appConfig
			->method('getValueString')
			->willReturnCallback(
				static function (string $app, string $key, string $default) use ($configMap): string {
					return $configMap[$key] ?? $default;
				}
			);

		return new LibreOfficeHeadlessBackend(
			appConfig: $this->appConfig,
			lockingProvider: $this->lockingProvider,
			logger: $this->logger,
		);

	}//end makeBackend()

	/**
	 * Test that name() returns the stable identifier 'libreoffice_headless'
	 *
	 * @return void
	 */
	public function testNameReturnsLibreofficeHeadless(): void {
		$backend = $this->makeBackend();
		$this->assertSame(expected: 'libreoffice_headless', actual: $backend->name());

	}//end testNameReturnsLibreofficeHeadless()

	/**
	 * Test that isAvailable() returns false when tenant flag is 'false'
	 *
	 * @return void
	 */
	public function testIsUnavailableWhenFlagFalse(): void {
		$backend = $this->makeBackend(configMap: ['filinq.conversion.backends.libreoffice_enabled' => 'false']);
		$this->assertFalse(condition: $backend->isAvailable());

	}//end testIsUnavailableWhenFlagFalse()

	/**
	 * Test that canHandle() returns true for DOCX MIME
	 *
	 * @return void
	 */
	public function testCanHandleDocxMime(): void {
		$backend = $this->makeBackend();
		$this->assertTrue(
			condition: $backend->canHandle(
				mimeType: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
				extension: 'docx'
			)
		);

	}//end testCanHandleDocxMime()

	/**
	 * Test that canHandle() returns true for ODT MIME
	 *
	 * @return void
	 */
	public function testCanHandleOdtMime(): void {
		$backend = $this->makeBackend();
		$this->assertTrue(
			condition: $backend->canHandle(
				mimeType: 'application/vnd.oasis.opendocument.text',
				extension: 'odt'
			)
		);

	}//end testCanHandleOdtMime()

	/**
	 * Test that canHandle() returns true for .xlsx extension
	 *
	 * @return void
	 */
	public function testCanHandleXlsxExtension(): void {
		$backend = $this->makeBackend();
		$this->assertTrue(
			condition: $backend->canHandle(
				mimeType: 'application/octet-stream',
				extension: 'xlsx'
			)
		);

	}//end testCanHandleXlsxExtension()

	/**
	 * Test that canHandle() returns true for text/plain MIME
	 *
	 * @return void
	 */
	public function testCanHandlePlainTextMime(): void {
		$backend = $this->makeBackend();
		$this->assertTrue(
			condition: $backend->canHandle(mimeType: 'text/plain', extension: 'txt')
		);

	}//end testCanHandlePlainTextMime()

	/**
	 * Test that canHandle() returns false for message/rfc822 (EML)
	 *
	 * @return void
	 */
	public function testCannotHandleEmlMime(): void {
		$backend = $this->makeBackend();
		$this->assertFalse(
			condition: $backend->canHandle(mimeType: 'message/rfc822', extension: 'eml')
		);

	}//end testCannotHandleEmlMime()

	/**
	 * Test that canHandle() returns true for image/png
	 *
	 * @return void
	 */
	public function testCanHandleImagePngMime(): void {
		$backend = $this->makeBackend();
		$this->assertTrue(
			condition: $backend->canHandle(mimeType: 'image/png', extension: 'png')
		);

	}//end testCanHandleImagePngMime()

	/**
	 * Test that canHandle() returns true for .pptx extension
	 *
	 * @return void
	 */
	public function testCanHandlePptxExtension(): void {
		$backend = $this->makeBackend();
		$this->assertTrue(
			condition: $backend->canHandle(mimeType: 'application/octet-stream', extension: 'pptx')
		);

	}//end testCanHandlePptxExtension()

	/**
	 * Test that convert() throws ConversionFailedException when lock cannot be acquired
	 *
	 * @return void
	 */
	public function testConvertThrowsOnLockContention(): void {
		$backend = $this->makeBackend(
			configMap: [
				'filinq.conversion.backends.libreoffice_enabled' => 'true',
				'filinq.conversion.libreoffice_binary_path' => '/usr/bin/soffice',
				'filinq.conversion.timeout_seconds' => '60',
			]
		);

		$this->lockingProvider
			->method('acquireLock')
			->willThrowException(new LockedException('soffice:headless:convert'));

		$source = $this->createMock(originalClassName: File::class);
		$source->method('getName')->willReturn('doc.docx');
		$source->method('getParent')->willReturn($this->createMock(originalClassName: Folder::class));
		$source->method('getContent')->willReturn('fake docx bytes');

		$this->expectException(exception: ConversionFailedException::class);
		$this->expectExceptionMessageMatches(regularExpression: '/lock/i');

		$backend->convert(source: $source);

	}//end testConvertThrowsOnLockContention()

	/**
	 * Test that the attempt record on lock failure has the correct shape
	 *
	 * @return void
	 */
	public function testConvertLockFailureAttemptRecord(): void {
		$backend = $this->makeBackend(
			configMap: [
				'filinq.conversion.backends.libreoffice_enabled' => 'true',
				'filinq.conversion.libreoffice_binary_path' => '/usr/bin/soffice',
				'filinq.conversion.timeout_seconds' => '60',
			]
		);

		$this->lockingProvider
			->method('acquireLock')
			->willThrowException(new LockedException('soffice:headless:convert'));

		$source = $this->createMock(originalClassName: File::class);
		$source->method('getName')->willReturn('doc.docx');
		$source->method('getParent')->willReturn($this->createMock(originalClassName: Folder::class));
		$source->method('getContent')->willReturn('fake docx bytes');

		try {
			$backend->convert(source: $source);
			$this->fail(message: 'Expected ConversionFailedException');
		} catch (ConversionFailedException $e) {
			$attempts = $e->getAttempts();
			$this->assertNotEmpty(actual: $attempts);
			$this->assertSame(expected: 'libreoffice_headless', actual: $attempts[0]['name']);
			$this->assertTrue(condition: $attempts[0]['available']);
			$this->assertTrue(condition: $attempts[0]['supports']);
			$this->assertStringContainsString(needle: 'lock', haystack: $attempts[0]['reason']);
		}

	}//end testConvertLockFailureAttemptRecord()
}//end class
