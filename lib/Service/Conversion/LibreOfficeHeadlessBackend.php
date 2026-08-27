<?php

/**
 * LibreOffice Headless Conversion Backend
 *
 * Invokes `soffice --headless` to convert any LibreOffice-supported
 * input to PDF/A-3b. Serialises concurrent invocations via an
 * ILockingProvider lock (`soffice:headless:convert`) to avoid the
 * user-profile lock contention that soffice exhibits under concurrent
 * headless use. If the lock cannot be acquired, the backend fails fast
 * and the cascade falls through to the next tier.
 *
 * Configuration keys (IAppConfig, app "filinq"):
 *   - filinq.conversion.backends.libreoffice_enabled  (default "true")
 *   - filinq.conversion.libreoffice_binary_path       (default "soffice")
 *   - filinq.conversion.timeout_seconds               (default "60")
 *
 * @category Service
 * @package  OCA\Filinq\Service\Conversion
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/pdf-conversion-service/tasks.md#task-6
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Conversion;

use OCA\Filinq\Exception\ConversionFailedException;
use OCP\Files\File;
use OCP\IAppConfig;
use OCP\Lock\ILockingProvider;
use OCP\Lock\LockedException;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Converts documents to PDF/A-3b via LibreOffice headless (`soffice --headless`).
 *
 * The conversion is performed by writing the source file to a temp path,
 * invoking soffice with `proc_open`, and collecting the emitted `.pdf`
 * file. A global ILockingProvider lock serialises concurrent calls;
 * a `proc_open` + stream-select loop enforces the configured timeout.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Conversion
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/pdf-conversion-service/tasks.md#task-6
 */
class LibreOfficeHeadlessBackend implements ConversionBackendInterface {

	/**
	 * App config key controlling whether this backend is attempted.
	 */
	private const ENABLED_KEY = 'filinq.conversion.backends.libreoffice_enabled';

	/**
	 * App config key for the path to the soffice binary.
	 */
	private const BINARY_KEY = 'filinq.conversion.libreoffice_binary_path';

	/**
	 * App config key for conversion timeout in seconds.
	 */
	private const TIMEOUT_KEY = 'filinq.conversion.timeout_seconds';

	/**
	 * ILockingProvider lock key — one concurrent soffice process per NC host.
	 */
	private const LOCK_KEY = 'soffice:headless:convert';

	/**
	 * App identifier used for IAppConfig reads.
	 */
	private const APP_ID = 'filinq';

	/**
	 * MIME types LibreOffice can convert to PDF. Only common document
	 * formats are listed; the Office-app backend handles these first
	 * when present.
	 *
	 * @var array<string, true>
	 */
	private const SUPPORTED_MIMES = [
		'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => true,
		'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => true,
		'application/vnd.openxmlformats-officedocument.presentationml.presentation' => true,
		'application/msword' => true,
		'application/vnd.ms-excel' => true,
		'application/vnd.ms-powerpoint' => true,
		'application/vnd.oasis.opendocument.text' => true,
		'application/vnd.oasis.opendocument.spreadsheet' => true,
		'application/vnd.oasis.opendocument.presentation' => true,
		'application/rtf' => true,
		'text/rtf' => true,
		'text/html' => true,
		'text/plain' => true,
		'image/png' => true,
		'image/jpeg' => true,
	];

	/**
	 * File extensions that LibreOffice handles. Used as fallback when the
	 * MIME type is generic (e.g. application/octet-stream).
	 *
	 * @var array<string, true>
	 */
	private const SUPPORTED_EXTENSIONS = [
		'doc' => true,
		'docx' => true,
		'xls' => true,
		'xlsx' => true,
		'ppt' => true,
		'pptx' => true,
		'odt' => true,
		'ods' => true,
		'odp' => true,
		'rtf' => true,
		'html' => true,
		'htm' => true,
		'txt' => true,
		'png' => true,
		'jpg' => true,
		'jpeg' => true,
	];

	/**
	 * Runs and supervises the headless soffice subprocess.
	 *
	 * @var SofficeProcessRunner
	 */
	private readonly SofficeProcessRunner $processRunner;

	/**
	 * Constructor.
	 *
	 * @param IAppConfig $appConfig Tenant configuration provider.
	 * @param ILockingProvider $lockingProvider Nextcloud locking for soffice serialisation.
	 * @param LoggerInterface $logger Logger for diagnostics.
	 * @param SofficeProcessRunner|null $processRunner Subprocess runner; autowired in
	 *                                                 production, defaulted here so existing
	 *                                                 call sites stay source-compatible.
	 */
	public function __construct(
		private readonly IAppConfig $appConfig,
		private readonly ILockingProvider $lockingProvider,
		private readonly LoggerInterface $logger,
		?SofficeProcessRunner $processRunner = null,
	) {
		$this->processRunner = ($processRunner ?? new SofficeProcessRunner($logger));

	}//end __construct()

	/**
	 * Backend identifier used in attempt records and diagnostics.
	 *
	 * @return string
	 *
	 * @spec openspec/changes/pdf-conversion-service/tasks.md#task-6
	 */
	public function name(): string {
		return 'libreoffice_headless';
	}//end name()

	/**
	 * Returns true iff:
	 *  - the tenant flag is enabled (default true)
	 *  - the configured soffice binary exists and is executable
	 *
	 * @return bool
	 *
	 * @spec openspec/changes/pdf-conversion-service/tasks.md#task-6
	 */
	public function isAvailable(): bool {
		$enabled = $this->appConfig->getValueString(self::APP_ID, self::ENABLED_KEY, 'true');
		if ($enabled === 'false') {
			return false;
		}

		$binary = $this->resolveBinaryPath();
		return $this->isBinaryExecutable(binary: $binary);
	}//end isAvailable()

	/**
	 * Returns true when the MIME type or extension is in the supported set.
	 *
	 * @param string $mimeType Source MIME.
	 * @param string $extension Lowercased extension without dot.
	 *
	 * @return bool
	 *
	 * @spec openspec/changes/pdf-conversion-service/tasks.md#task-6
	 */
	public function canHandle(string $mimeType, string $extension): bool {
		if (isset(self::SUPPORTED_MIMES[$mimeType]) === true) {
			return true;
		}

		return isset(self::SUPPORTED_EXTENSIONS[$extension]);
	}//end canHandle()

	/**
	 * Convert via LibreOffice headless. Acquires the global lock to
	 * serialise concurrent soffice calls, writes the source to a temp
	 * directory, invokes soffice with `proc_open`, enforces the
	 * configured timeout, and resolves the output PDF back to a
	 * Nextcloud File node.
	 *
	 * @param File $source Source file node.
	 *
	 * @return File Newly written PDF file node.
	 *
	 * @throws ConversionFailedException On lock failure, timeout, or non-zero exit.
	 *
	 * @spec openspec/changes/pdf-conversion-service/tasks.md#task-6
	 */
	public function convert(File $source): File {
		$binary = $this->resolveBinaryPath();
		$timeout = $this->resolveTimeout();

		// Acquire lock — serialise concurrent soffice processes.
		try {
			$this->lockingProvider->acquireLock(self::LOCK_KEY, ILockingProvider::LOCK_EXCLUSIVE);
		} catch (LockedException $e) {
			throw new ConversionFailedException(
				message: 'LibreOffice headless lock contention; cascade falling through.',
				attempts: [
					[
						'name' => $this->name(),
						'available' => true,
						'supports' => true,
						'reason' => 'could not acquire soffice:headless:convert lock: ' . $e->getMessage(),
					],
				],
				previous: $e
			);
		}

		try {
			return $this->runConversion(source: $source, binary: $binary, timeout: $timeout);
		} finally {
			try {
				$this->lockingProvider->releaseLock(self::LOCK_KEY, ILockingProvider::LOCK_EXCLUSIVE);
			} catch (Throwable $ignored) {
				// Lock release failures are non-fatal; log but don't
				// mask the real result/exception.
				$this->logger->warning(
					'[LibreOfficeHeadlessBackend] Failed to release lock after conversion',
					['message' => $ignored->getMessage()]
				);
			}
		}//end try

	}//end convert()

	/**
	 * Write source to a temp dir, invoke soffice, wait (with timeout),
	 * then write the emitted PDF to Nextcloud Files beside the source.
	 *
	 * @param File $source Source file node.
	 * @param string $binary Path to the soffice binary.
	 * @param int $timeout Timeout in seconds.
	 *
	 * @return File Newly written PDF file node.
	 *
	 * @throws ConversionFailedException On soffice failure, timeout, or file I/O error.
	 */
	private function runConversion(File $source, string $binary, int $timeout): File {
		// Defensive: strip any path components from the file name before
		// using it to build temp paths. NC nodes shouldn't surface '../'
		// in getName(), but some external storage / DAV mounts have been
		// observed returning trailing path segments. basename() makes us
		// robust to that source of path traversal.
		$name = basename($source->getName());
		$ext = $this->extractExtension(name: $name);
		$baseName = $this->stripExtension(name: $name);

		// Write source bytes to a temp file for soffice.
		$tmpDir = sys_get_temp_dir() . '/filinq_libreoffice_' . bin2hex(random_bytes(8));
		mkdir($tmpDir, 0700, true);

		$extSuffix = '';
		if ($ext !== '') {
			$extSuffix = '.' . $ext;
		}

		$srcPath = $tmpDir . '/input' . $extSuffix;

		try {
			$this->writeSourceBytes(source: $source, srcPath: $srcPath);

			$exitCode = $this->processRunner->run(
				argv: $this->buildArgv(binary: $binary, tmpDir: $tmpDir, srcPath: $srcPath),
				timeout: $timeout,
				tmpDir: $tmpDir,
				backendName: $this->name()
			);

			if ($exitCode !== 0) {
				throw new ConversionFailedException(
					message: sprintf('soffice exited with code %d.', $exitCode),
					attempts: [
						[
							'name' => $this->name(),
							'available' => true,
							'supports' => true,
							'reason' => sprintf('soffice non-zero exit: %d', $exitCode),
						],
					]
				);
			}

			$pdfBytes = $this->readEmittedPdf(tmpDir: $tmpDir, baseName: $baseName);

			$parent = $source->getParent();
			$outputName = $baseName . '.pdf';
			if ($parent->nodeExists($outputName) === true) {
				$parent->get($outputName)->delete();
			}

			return $parent->newFile($outputName, $pdfBytes);
		} finally {
			// Clean up the temp directory regardless of outcome.
			$this->cleanupDir(dir: $tmpDir);
		}//end try

	}//end runConversion()

	/**
	 * Copy the node's bytes to the temp path soffice will read.
	 *
	 * @param File $source Source file node.
	 * @param string $srcPath Temp path to write the source bytes to.
	 *
	 * @return void
	 *
	 * @throws ConversionFailedException When the node yields no readable content.
	 */
	private function writeSourceBytes(File $source, string $srcPath): void {
		$bytes = $source->getContent();
		if (is_string($bytes) === false) {
			throw new ConversionFailedException(
				message: 'LibreOffice backend could not read source content.',
				attempts: [
					[
						'name' => $this->name(),
						'available' => true,
						'supports' => true,
						'reason' => 'File::getContent returned non-string',
					],
				]
			);
		}

		file_put_contents($srcPath, $bytes);

	}//end writeSourceBytes()

	/**
	 * Build the soffice argv for a PDF/A-3b conversion.
	 *
	 * The array form of proc_open avoids the `/bin/sh -c` layer entirely —
	 * strictly safer than the string form even with escapeshellarg().
	 * `--norestore` and `--nofirststartwizard` keep soffice from trying to
	 * bring up its on-disk profile UI under headless.
	 *
	 * @param string $binary Path to the soffice binary.
	 * @param string $tmpDir Temp directory soffice writes its output into.
	 * @param string $srcPath Path of the materialised source document.
	 *
	 * @return array<int, string> Process argv (argv[0] = binary).
	 */
	private function buildArgv(string $binary, string $tmpDir, string $srcPath): array {
		// PDF/A-3b via writer_pdf_Export filter options.
		$filterArgs = 'pdf:writer_pdf_Export:UseTaggedPDF=true,SelectPdfVersion=2';

		return [
			$binary,
			'--headless',
			'--norestore',
			'--nofirststartwizard',
			'--convert-to',
			$filterArgs,
			'--outdir',
			$tmpDir,
			$srcPath,
		];

	}//end buildArgv()

	/**
	 * Locate, containment-check, and read the PDF soffice emitted.
	 *
	 * Soffice emits the file with the source basename + ".pdf". Even though
	 * $baseName is derived from basename($source->getName()), the resolved
	 * output path is realpath'd and checked to stay inside $tmpDir before it
	 * is read — that closes any remaining TOCTOU / symlink window.
	 *
	 * @param string $tmpDir Temp directory soffice wrote its output into.
	 * @param string $baseName Source basename without extension.
	 *
	 * @return string Non-empty PDF bytes.
	 *
	 * @throws ConversionFailedException When the output is missing, escapes
	 *                                   the sandbox, or is empty.
	 */
	private function readEmittedPdf(string $tmpDir, string $baseName): string {
		$outputTmp = $tmpDir . '/' . $baseName . '.pdf';
		if (file_exists($outputTmp) === false) {
			throw new ConversionFailedException(
				message: 'soffice reported success but output PDF was not found.',
				attempts: [
					[
						'name' => $this->name(),
						'available' => true,
						'supports' => true,
						'reason' => 'expected output at ' . $outputTmp . ' but file is missing',
					],
				]
			);
		}

		$realTmpDir = realpath($tmpDir);
		$realOutputTmp = realpath($outputTmp);
		if ($realTmpDir === false
			|| $realOutputTmp === false
			|| str_starts_with($realOutputTmp, $realTmpDir . '/') === false
		) {
			throw new ConversionFailedException(
				message: 'soffice output path escaped the conversion sandbox.',
				attempts: [
					[
						'name' => $this->name(),
						'available' => true,
						'supports' => true,
						'reason' => 'output path not contained in tmp dir',
					],
				]
			);
		}

		$pdfBytes = file_get_contents($outputTmp);
		if ($pdfBytes === false || $pdfBytes === '') {
			throw new ConversionFailedException(
				message: 'soffice emitted an empty PDF file.',
				attempts: [
					[
						'name' => $this->name(),
						'available' => true,
						'supports' => true,
						'reason' => 'output PDF was empty',
					],
				]
			);
		}

		return $pdfBytes;
	}//end readEmittedPdf()

	/**
	 * Return the lowercased extension of $name without the leading dot.
	 *
	 * @param string $name File name, with or without an extension.
	 *
	 * @return string Lowercased extension, or an empty string when the name
	 *                carries no dot.
	 */
	private function extractExtension(string $name): string {
		$dotPos = strrpos($name, '.');
		if ($dotPos === false) {
			return '';
		}

		return strtolower(substr($name, ($dotPos + 1)));
	}//end extractExtension()

	/**
	 * Return $name without its trailing `.ext` suffix.
	 *
	 * @param string $name File name with or without an extension.
	 *
	 * @return string Name without extension.
	 */
	private function stripExtension(string $name): string {
		$dotPos = strrpos($name, '.');
		if ($dotPos === false) {
			return $name;
		}

		return substr($name, 0, $dotPos);
	}//end stripExtension()

	/**
	 * Read and resolve the configured soffice binary path.
	 * Defaults to `"soffice"` (resolved via PATH).
	 *
	 * @return string Binary path.
	 */
	private function resolveBinaryPath(): string {
		$path = $this->appConfig->getValueString(self::APP_ID, self::BINARY_KEY, 'soffice');
		if ($path === '') {
			return 'soffice';
		}

		return $path;
	}//end resolveBinaryPath()

	/**
	 * Read and resolve the configured conversion timeout.
	 * Defaults to 60 seconds; minimum clamped to 1.
	 *
	 * @return int Timeout in seconds.
	 */
	private function resolveTimeout(): int {
		$raw = $this->appConfig->getValueString(self::APP_ID, self::TIMEOUT_KEY, '60');
		$val = (int)$raw;
		return max(1, $val);
	}//end resolveTimeout()

	/**
	 * Check whether the given binary path is executable.
	 * Wraps the `is_executable` filesystem check.
	 *
	 * @param string $binary Path to check.
	 *
	 * @return bool True when the path resolves to an executable file.
	 */
	private function isBinaryExecutable(string $binary): bool {
		// When given an unqualified binary name (e.g. "soffice"), walk
		// $PATH ourselves rather than shelling out to `which` — `which`
		// is absent on minimal container images, and a PHP-native walk
		// avoids any shell layer regardless of input.
		if (str_contains($binary, '/') === false) {
			$pathEnv = getenv('PATH');
			if ($pathEnv === false || $pathEnv === '') {
				return false;
			}

			foreach (explode(PATH_SEPARATOR, $pathEnv) as $dir) {
				if ($dir === '') {
					continue;
				}

				$candidate = rtrim($dir, '/') . '/' . $binary;
				if (is_file($candidate) === true && is_executable($candidate) === true) {
					return true;
				}
			}

			return false;
		}

		return is_file($binary) === true && is_executable($binary) === true;
	}//end isBinaryExecutable()

	/**
	 * Recursively delete a temp directory and its contents.
	 *
	 * @param string $dir Directory path.
	 *
	 * @return void
	 */
	private function cleanupDir(string $dir): void {
		if (is_dir($dir) === false) {
			return;
		}

		$files = scandir($dir);
		if ($files === false) {
			return;
		}

		foreach ($files as $file) {
			if ($file === '.' || $file === '..') {
				continue;
			}

			$path = $dir . '/' . $file;
			if (is_dir($path) === true) {
				$this->cleanupDir(dir: $path);
				continue;
			}

			unlink($path);
		}

		rmdir($dir);

	}//end cleanupDir()
}//end class
