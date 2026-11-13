<?php

/**
 * Unit tests for LibreOfficeHeadlessBackend's binary lookup, lock release and temp cleanup.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Conversion
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Conversion;

use OCA\Filinq\Service\Conversion\LibreOfficeHeadlessBackend;
use OCA\Filinq\Service\Conversion\SofficeProcessRunner;
use OCP\IAppConfig;
use OCP\Lock\ILockingProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * How the LibreOffice backend finds soffice, releases its lock and cleans its sandbox.
 *
 * @covers \OCA\Filinq\Service\Conversion\LibreOfficeHeadlessBackend
 *
 * @uses \OCA\Filinq\Service\Conversion\SofficeCommand
 * @uses \OCA\Filinq\Service\Conversion\SofficeExport
 */
class LibreOfficeHeadlessBackendRuntimeTest extends TestCase {

	/**
	 * The PATH before the test.
	 *
	 * @var string|false
	 */
	private string|false $path;

	/**
	 * A scratch directory for fake binaries.
	 *
	 * @var string
	 */
	private string $bin;

	/**
	 * The app config double.
	 *
	 * @var IAppConfig&MockObject
	 */
	private IAppConfig $appConfig;

	/**
	 * The lock provider double.
	 *
	 * @var ILockingProvider&MockObject
	 */
	private ILockingProvider $locks;

	/**
	 * The logger double.
	 *
	 * @var LoggerInterface&MockObject
	 */
	private LoggerInterface $logger;

	/**
	 * Set up.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->path = getenv('PATH');
		$this->bin = sys_get_temp_dir() . '/lo_bin_' . bin2hex(random_bytes(4));
		mkdir($this->bin);
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->locks = $this->createMock(ILockingProvider::class);
		$this->logger = $this->createMock(LoggerInterface::class);
	}//end setUp()

	/**
	 * Restore PATH and remove the scratch directory.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		if ($this->path === false) {
			putenv('PATH');
		} else {
			putenv('PATH=' . $this->path);
		}

		foreach ((array) glob($this->bin . '/*') as $file) {
			@unlink((string) $file);
		}

		@rmdir($this->bin);
		parent::tearDown();
	}//end tearDown()

	/**
	 * Configure the app config values.
	 *
	 * @param array<string, string> $values Values by key; absent keys answer their default.
	 *
	 * @return void
	 */
	private function config(array $values): void {
		$this->appConfig->method('getValueString')->willReturnCallback(
			static fn (string $app, string $key, string $default = ''): string => ($values[$key] ?? $default)
		);
	}//end config()

	/**
	 * Create a file in the scratch directory.
	 *
	 * @param string $name       The file name.
	 * @param bool   $executable Whether to mark it executable.
	 *
	 * @return string Its path.
	 */
	private function binary(string $name, bool $executable): string {
		$path = $this->bin . '/' . $name;
		file_put_contents($path, "#!/bin/sh\n");
		chmod($path, $executable ? 0755 : 0644);

		return $path;
	}//end binary()

	/**
	 * The backend under test.
	 *
	 * @param SofficeProcessRunner|null $runner The process runner.
	 *
	 * @return LibreOfficeHeadlessBackend
	 */
	private function backend(?SofficeProcessRunner $runner = null): LibreOfficeHeadlessBackend {
		return new LibreOfficeHeadlessBackend($this->appConfig, $this->locks, $this->logger, $runner ?? $this->createMock(SofficeProcessRunner::class));
	}//end backend()

	/**
	 * An unqualified binary is found by walking PATH, skipping empty entries; an empty setting means soffice.
	 *
	 * @return void
	 */
	public function testAnUnqualifiedBinaryIsFoundOnThePath(): void {
		$this->binary(name: 'soffice', executable: true);
		$this->config(['filinq.conversion.libreoffice_binary_path' => '']);
		putenv('PATH=' . PATH_SEPARATOR . '/nonexistent-dir' . PATH_SEPARATOR . $this->bin . '/');

		self::assertTrue($this->backend()->isAvailable());
	}//end testAnUnqualifiedBinaryIsFoundOnThePath()

	/**
	 * A binary that is on the PATH but not executable, or not on it at all, is unavailable.
	 *
	 * @return void
	 */
	public function testAMissingOrNonExecutableBinaryOnThePathIsUnavailable(): void {
		$this->binary(name: 'lowriter', executable: false);
		$this->config(['filinq.conversion.libreoffice_binary_path' => 'lowriter']);
		putenv('PATH=' . $this->bin);

		self::assertFalse($this->backend()->isAvailable());
	}//end testAMissingOrNonExecutableBinaryOnThePathIsUnavailable()

	/**
	 * Without a PATH an unqualified binary cannot be found.
	 *
	 * @return void
	 */
	public function testWithoutAPathAnUnqualifiedBinaryIsUnavailable(): void {
		$this->binary(name: 'soffice', executable: true);
		$this->config([]);
		putenv('PATH=');

		self::assertFalse($this->backend()->isAvailable());

		putenv('PATH');
		self::assertFalse($this->backend()->isAvailable());
	}//end testWithoutAPathAnUnqualifiedBinaryIsUnavailable()

	/**
	 * An absolute path is checked directly, without the PATH.
	 *
	 * @return void
	 */
	public function testAnAbsolutePathIsCheckedDirectly(): void {
		$runnable = $this->binary(name: 'soffice-7', executable: true);
		$plain = $this->binary(name: 'soffice-8', executable: false);
		putenv('PATH=');

		$this->config(['filinq.conversion.libreoffice_binary_path' => $runnable]);
		self::assertTrue($this->backend()->isAvailable());

		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->config(['filinq.conversion.libreoffice_binary_path' => $plain]);
		self::assertFalse($this->backend()->isAvailable());

		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->config(['filinq.conversion.libreoffice_binary_path' => $this->bin]);
		self::assertFalse($this->backend()->isAvailable());
	}//end testAnAbsolutePathIsCheckedDirectly()

	/**
	 * A lock that fails to release is logged, and the converted bytes are still answered;
	 * the sandbox, nested directories included, is removed.
	 *
	 * @return void
	 */
	public function testAFailedLockReleaseIsLoggedAndTheSandboxIsRemoved(): void {
		$this->config([]);
		$sandbox = '';
		$runner = $this->createMock(SofficeProcessRunner::class);
		$runner->expects(self::once())->method('run')->willReturnCallback(
			static function (array $argv, int $timeout, string $tmpDir, string $backendName) use (&$sandbox): int {
				$sandbox = $tmpDir;
				mkdir($tmpDir . '/profile/user', 0700, true);
				file_put_contents($tmpDir . '/profile/user/registry.xcu', 'x');
				file_put_contents($tmpDir . '/input.odt', 'ODT-BYTES');

				return 0;
			}
		);
		$this->locks->expects(self::once())->method('acquireLock')->with('soffice:headless:convert', ILockingProvider::LOCK_EXCLUSIVE);
		$this->locks->expects(self::once())->method('releaseLock')->with('soffice:headless:convert', ILockingProvider::LOCK_EXCLUSIVE)
			->willThrowException(new RuntimeException('redis gone'));
		$this->logger->expects(self::once())->method('warning')->with(
			'[LibreOfficeHeadlessBackend] Failed to release lock after conversion',
			['message' => 'redis gone']
		);

		self::assertSame('ODT-BYTES', $this->backend(runner: $runner)->convertHtml(html: '<p>hoi</p>', toExtension: 'odt'));
		self::assertNotSame('', $sandbox);
		self::assertDirectoryDoesNotExist($sandbox);
	}//end testAFailedLockReleaseIsLoggedAndTheSandboxIsRemoved()
}//end class
