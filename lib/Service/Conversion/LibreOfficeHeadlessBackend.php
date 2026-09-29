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
	 * The file name (without extension) the source is written under; soffice
	 * names its output after it.
	 */
	private const INPUT_STEM = 'input';

	/**
	 * The --convert-to argument of the default (archival) conversion.
	 */
	private const FILTER_PDFA = 'pdf:writer_pdf_Export:UseTaggedPDF=true,SelectPdfVersion=2';

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

		return $this->underLock(
			work: fn (): File => $this->runConversion(source: $source, binary: $binary, timeout: $timeout)
		);

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
		$baseName = $this->stripExtension(name: $name);

		$pdfBytes = $this->exportPdfBytes(
			bytes: $this->sourceBytes(source: $source),
			extension: $this->extractExtension(name: $name),
			convertTo: self::FILTER_PDFA,
			binary: $binary,
			timeout: $timeout
		);

		$parent = $source->getParent();
		$outputName = $baseName . '.pdf';
		if ($parent->nodeExists($outputName) === true) {
			$parent->get($outputName)->delete();
		}

		return $parent->newFile($outputName, $pdfBytes);

	}//end runConversion()

	/**
	 * Tagged (PDF/UA) export of a document's bytes: the accessible mode.
	 *
	 * Asks LibreOffice for tagged PDF with PDF/UA compliance, keeping
	 * PDF/A-3 when asked for. HTML is opened in Writer (not Writer/Web),
	 * whose PDF export writes the structure tree. Without a usable soffice
	 * this throws: nothing else in the cascade can tag, so there is no
	 * fallback to an untagged PDF.
	 *
	 * @param string $bytes     The source document.
	 * @param string $extension Its extension (html, docx, odt ...).
	 * @param bool   $pdfa      Whether to keep PDF/A-3 conformance as well.
	 *
	 * @return string The PDF bytes.
	 *
	 * @throws ConversionFailedException When soffice is unavailable or fails.
	 *
	 * @spec openspec/changes/archive/2026-09-29-pdfua-accessible-output/tasks.md#task-1.1
	 */
	public function convertTagged(string $bytes, string $extension, bool $pdfa): string {
		if ($this->isAvailable() === false) {
			throw new ConversionFailedException(
				message: 'Accessible PDF output needs LibreOffice, which is not available; no untagged PDF is made instead.',
				attempts: [
					['name' => $this->name(), 'available' => false, 'supports' => true, 'reason' => 'backend disabled or soffice binary not found'],
				],
				code: 503
			);
		}

		$filter = [
			'UseTaggedPDF' => ['type' => 'boolean', 'value' => 'true'],
			'PDFUACompliance' => ['type' => 'boolean', 'value' => 'true'],
			'SelectPdfVersion' => ['type' => 'long', 'value' => ($pdfa === true ? '3' : '0')],
		];
		$ext = strtolower($extension);
		$binary = $this->resolveBinaryPath();
		$timeout = $this->resolveTimeout();

		return $this->underLock(
			work: fn (): string => $this->exportPdfBytes(
				bytes: $bytes,
				extension: $ext,
				convertTo: 'pdf:writer_pdf_Export:' . json_encode($filter),
				binary: $binary,
				timeout: $timeout,
				htmlInWriter: in_array($ext, ['html', 'htm'], true)
			)
		);

	}//end convertTagged()

	/**
	 * Run work while holding the soffice lock, which serialises soffice
	 * processes (they share a user profile).
	 *
	 * @param callable $work The work.
	 *
	 * @return mixed What the work returns.
	 *
	 * @throws ConversionFailedException When the lock is taken.
	 */
	private function underLock(callable $work): mixed {
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
			return $work();
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

	}//end underLock()

	/**
	 * Write bytes to a temp dir, run soffice on them and read back the PDF.
	 *
	 * The source is written as `input.<ext>`, so soffice emits `input.pdf`:
	 * that is the name read back, whatever the original file was called.
	 *
	 * @param string $bytes        The source bytes.
	 * @param string $extension    The source extension, '' for none.
	 * @param string $convertTo    The --convert-to argument.
	 * @param string $binary       Path to the soffice binary.
	 * @param int    $timeout      Timeout in seconds.
	 * @param bool   $htmlInWriter Open HTML in Writer rather than Writer/Web.
	 *
	 * @return string The PDF bytes.
	 *
	 * @throws ConversionFailedException On soffice failure, timeout, or file I/O error.
	 */
	private function exportPdfBytes(string $bytes, string $extension, string $convertTo, string $binary, int $timeout, bool $htmlInWriter = false): string {
		$tmpDir = sys_get_temp_dir() . '/filinq_libreoffice_' . bin2hex(random_bytes(8));
		mkdir($tmpDir, 0700, true);

		$srcPath = $tmpDir . '/' . self::INPUT_STEM;
		if ($extension !== '') {
			$srcPath .= '.' . $extension;
		}

		try {
			file_put_contents($srcPath, $bytes);

			$argv = $this->buildArgv(binary: $binary, tmpDir: $tmpDir, srcPath: $srcPath, convertTo: $convertTo);
			if ($htmlInWriter === true) {
				array_splice($argv, 4, 0, ['--infilter=HTML (StarWriter)']);
			}

			$exitCode = $this->processRunner->run(argv: $argv, timeout: $timeout, tmpDir: $tmpDir, backendName: $this->name());
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

			return $this->readEmittedPdf(tmpDir: $tmpDir, baseName: self::INPUT_STEM);
		} finally {
			// Clean up the temp directory regardless of outcome.
			$this->cleanupDir(dir: $tmpDir);
		}//end try

	}//end exportPdfBytes()

	/**
	 * The node's bytes.
	 *
	 * @param File $source Source file node.
	 *
	 * @return string The bytes.
	 *
	 * @throws ConversionFailedException When the node yields no readable content.
	 */
	private function sourceBytes(File $source): string {
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

		return $bytes;

	}//end sourceBytes()

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
	 * @param string $convertTo The --convert-to argument (format and filter options).
	 *
	 * @return array<int, string> Process argv (argv[0] = binary).
	 */
	private function buildArgv(string $binary, string $tmpDir, string $srcPath, string $convertTo): array {
		return [
			$binary,
			'--headless',
			'--norestore',
			'--nofirststartwizard',
			'--convert-to',
			$convertTo,
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
