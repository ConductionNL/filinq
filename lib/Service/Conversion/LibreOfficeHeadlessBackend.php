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
 * Configuration keys (IAppConfig, app "docudesk"):
 *   - docudesk.conversion.backends.libreoffice_enabled  (default "true")
 *   - docudesk.conversion.libreoffice_binary_path       (default "soffice")
 *   - docudesk.conversion.timeout_seconds               (default "60")
 *
 * @category Service
 * @package  OCA\DocuDesk\Service\Conversion
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

namespace OCA\DocuDesk\Service\Conversion;

use OCA\DocuDesk\Exception\ConversionFailedException;
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
 * @package   OCA\DocuDesk\Service\Conversion
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/changes/pdf-conversion-service/tasks.md#task-6
 */
class LibreOfficeHeadlessBackend implements ConversionBackendInterface
{


    /**
     * App config key controlling whether this backend is attempted.
     */
    private const ENABLED_KEY = 'docudesk.conversion.backends.libreoffice_enabled';

    /**
     * App config key for the path to the soffice binary.
     */
    private const BINARY_KEY = 'docudesk.conversion.libreoffice_binary_path';

    /**
     * App config key for conversion timeout in seconds.
     */
    private const TIMEOUT_KEY = 'docudesk.conversion.timeout_seconds';

    /**
     * ILockingProvider lock key — one concurrent soffice process per NC host.
     */
    private const LOCK_KEY = 'soffice:headless:convert';

    /**
     * App identifier used for IAppConfig reads.
     */
    private const APP_ID = 'docudesk';

    /**
     * MIME types LibreOffice can convert to PDF. Only common document
     * formats are listed; the Office-app backend handles these first
     * when present.
     *
     * @var array<string, true>
     */
    private const SUPPORTED_MIMES = [
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'   => true,
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'         => true,
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => true,
        'application/msword'                                                        => true,
        'application/vnd.ms-excel'                                                  => true,
        'application/vnd.ms-powerpoint'                                             => true,
        'application/vnd.oasis.opendocument.text'                                   => true,
        'application/vnd.oasis.opendocument.spreadsheet'                            => true,
        'application/vnd.oasis.opendocument.presentation'                           => true,
        'application/rtf'                                                           => true,
        'text/rtf'                                                                  => true,
        'text/html'                                                                 => true,
        'text/plain'                                                                => true,
        'image/png'                                                                 => true,
        'image/jpeg'                                                                => true,
    ];

    /**
     * File extensions that LibreOffice handles. Used as fallback when the
     * MIME type is generic (e.g. application/octet-stream).
     *
     * @var array<string, true>
     */
    private const SUPPORTED_EXTENSIONS = [
        'doc'  => true,
        'docx' => true,
        'xls'  => true,
        'xlsx' => true,
        'ppt'  => true,
        'pptx' => true,
        'odt'  => true,
        'ods'  => true,
        'odp'  => true,
        'rtf'  => true,
        'html' => true,
        'htm'  => true,
        'txt'  => true,
        'png'  => true,
        'jpg'  => true,
        'jpeg' => true,
    ];

    /**
     * Constructor.
     *
     * @param IAppConfig       $appConfig       Tenant configuration provider.
     * @param ILockingProvider $lockingProvider Nextcloud locking for soffice serialisation.
     * @param LoggerInterface  $logger          Logger for diagnostics.
     */
    public function __construct(
        private readonly IAppConfig $appConfig,
        private readonly ILockingProvider $lockingProvider,
        private readonly LoggerInterface $logger,
    ) {

    }//end __construct()

    /**
     * Backend identifier used in attempt records and diagnostics.
     *
     * @return string
     *
     * @spec openspec/changes/pdf-conversion-service/tasks.md#task-6
     */
    public function name(): string
    {
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
    public function isAvailable(): bool
    {
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
     * @param string $mimeType  Source MIME.
     * @param string $extension Lowercased extension without dot.
     *
     * @return bool
     *
     * @spec openspec/changes/pdf-conversion-service/tasks.md#task-6
     */
    public function canHandle(string $mimeType, string $extension): bool
    {
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
    public function convert(File $source): File
    {
        $binary  = $this->resolveBinaryPath();
        $timeout = $this->resolveTimeout();

        // Acquire lock — serialise concurrent soffice processes.
        try {
            $this->lockingProvider->acquireLock(self::LOCK_KEY, ILockingProvider::LOCK_EXCLUSIVE);
        } catch (LockedException $e) {
            throw new ConversionFailedException(
                message: 'LibreOffice headless lock contention; cascade falling through.',
                attempts: [
                    [
                        'name'      => $this->name(),
                        'available' => true,
                        'supports'  => true,
                        'reason'    => 'could not acquire soffice:headless:convert lock: '.$e->getMessage(),
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
     * @param File   $source  Source file node.
     * @param string $binary  Path to the soffice binary.
     * @param int    $timeout Timeout in seconds.
     *
     * @return File Newly written PDF file node.
     *
     * @throws ConversionFailedException On soffice failure, timeout, or file I/O error.
     */
    private function runConversion(File $source, string $binary, int $timeout): File
    {
        // Defensive: strip any path components from the file name before
        // using it to build temp paths. NC nodes shouldn't surface '../'
        // in getName(), but some external storage / DAV mounts have been
        // observed returning trailing path segments. basename() makes us
        // robust to that source of path traversal.
        $name   = basename($source->getName());
        $dotPos = strrpos($name, '.');
        if ($dotPos === false) {
            $ext = '';
        } else {
            $ext = strtolower(substr($name, ($dotPos + 1)));
        }

        // Write source bytes to a temp file for soffice.
        $tmpDir = sys_get_temp_dir().'/docudesk_libreoffice_'.bin2hex(random_bytes(8));
        mkdir($tmpDir, 0700, true);
        if ($ext !== '') {
            $extSuffix = '.'.$ext;
        } else {
            $extSuffix = '';
        }

        $srcPath = $tmpDir.'/input'.$extSuffix;

        try {
            $bytes = $source->getContent();
            if (is_string($bytes) === false) {
                throw new ConversionFailedException(
                    message: 'LibreOffice backend could not read source content.',
                    attempts: [
                        [
                            'name'      => $this->name(),
                            'available' => true,
                            'supports'  => true,
                            'reason'    => 'File::getContent returned non-string',
                        ],
                    ]
                );
            }

            file_put_contents($srcPath, $bytes);

            // PDF/A-3b via writer_pdf_Export filter options.
            $filterArgs = 'pdf:writer_pdf_Export:UseTaggedPDF=true,SelectPdfVersion=2';
            // Array-form proc_open avoids the /bin/sh -c layer entirely —
            // strictly safer than the string form even with escapeshellarg().
            // Also add --norestore + --nofirststartwizard so soffice never
            // tries to bring up its on-disk profile UI under headless.
            $argv = [
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

            $exitCode = $this->runWithTimeout(argv: $argv, timeout: $timeout, tmpDir: $tmpDir);

            if ($exitCode !== 0) {
                throw new ConversionFailedException(
                    message: sprintf('soffice exited with code %d.', $exitCode),
                    attempts: [
                        [
                            'name'      => $this->name(),
                            'available' => true,
                            'supports'  => true,
                            'reason'    => sprintf('soffice non-zero exit: %d', $exitCode),
                        ],
                    ]
                );
            }

            // Soffice emits the file with the source basename + ".pdf".
            if ($dotPos === false) {
                $baseName = $name;
            } else {
                $baseName = substr($name, 0, $dotPos);
            }

            $outputTmp = $tmpDir.'/'.$baseName.'.pdf';
            if (file_exists($outputTmp) === false) {
                throw new ConversionFailedException(
                    message: 'soffice reported success but output PDF was not found.',
                    attempts: [
                        [
                            'name'      => $this->name(),
                            'available' => true,
                            'supports'  => true,
                            'reason'    => 'expected output at '.$outputTmp.' but file is missing',
                        ],
                    ]
                );
            }

            // Defensive containment check — even though $baseName was
            // derived from basename($source->getName()) above, realpath
            // the result and ensure it stays inside $tmpDir before we
            // read it. Closes any remaining TOCTOU / symlink window.
            $realTmpDir    = realpath($tmpDir);
            $realOutputTmp = realpath($outputTmp);
            if ($realTmpDir === false
                || $realOutputTmp === false
                || str_starts_with($realOutputTmp, $realTmpDir.'/') === false
            ) {
                throw new ConversionFailedException(
                    message: 'soffice output path escaped the conversion sandbox.',
                    attempts: [
                        [
                            'name'      => $this->name(),
                            'available' => true,
                            'supports'  => true,
                            'reason'    => 'output path not contained in tmp dir',
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
                            'name'      => $this->name(),
                            'available' => true,
                            'supports'  => true,
                            'reason'    => 'output PDF was empty',
                        ],
                    ]
                );
            }

            $parent     = $source->getParent();
            $outputName = $baseName.'.pdf';
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
     * Invoke the command in a subprocess via `proc_open`, draining stdout/
     * stderr and enforcing the timeout via `stream_select`.
     *
     * Uses the array form of proc_open so PHP execs the binary directly
     * without going through `/bin/sh -c` — eliminates the shell layer
     * and the associated quoting/injection surface.
     *
     * @param array<int, string> $argv    Process argv (argv[0] = binary).
     * @param int                $timeout Timeout in seconds.
     * @param string             $tmpDir  Temp directory (for logging only).
     *
     * @return int Process exit code (or 1 on timeout).
     *
     * @throws ConversionFailedException On timeout or if proc_open fails.
     */
    private function runWithTimeout(array $argv, int $timeout, string $tmpDir): int
    {
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $proc = proc_open($argv, $descriptors, $pipes);
        if (is_resource($proc) === false) {
            throw new ConversionFailedException(
                message: 'proc_open failed to launch soffice.',
                attempts: [
                    [
                        'name'      => $this->name(),
                        'available' => true,
                        'supports'  => true,
                        'reason'    => 'proc_open returned false',
                    ],
                ]
            );
        }

        fclose($pipes[0]);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $deadline = time() + $timeout;
        $stdout   = '';
        $stderr   = '';
        $timedOut = false;

        while (true) {
            $remaining = $deadline - time();
            if ($remaining <= 0) {
                $timedOut = true;
                proc_terminate($proc, 9);
                break;
            }

            $read     = [$pipes[1], $pipes[2]];
            $write    = null;
            $except   = null;
            $selected = stream_select($read, $write, $except, $remaining);

            if ($selected === false || $selected === 0) {
                // Select() returned without readable data — check if
                // the process is still running.
                $status = proc_get_status($proc);
                if ($status['running'] === false) {
                    break;
                }

                continue;
            }

            foreach ($read as $stream) {
                $chunk = fread($stream, 8192);
                if ($chunk !== false) {
                    if ($stream === $pipes[1]) {
                        $stdout .= $chunk;
                    } else {
                        $stderr .= $chunk;
                    }
                }
            }

            $status = proc_get_status($proc);
            if ($status['running'] === false) {
                break;
            }
        }//end while

        // Drain remaining output after process exits.
        $stdout .= stream_get_contents($pipes[1]);
        $stderr .= stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($proc);

        if ($timedOut === true) {
            $this->logger->warning(
                '[LibreOfficeHeadlessBackend] soffice timed out',
                [
                    'timeout' => $timeout,
                    'tmpDir'  => $tmpDir,
                    'stderr'  => substr($stderr, 0, 500),
                ]
            );
            throw new ConversionFailedException(
                message: sprintf('soffice timed out after %d seconds.', $timeout),
                attempts: [
                    [
                        'name'      => $this->name(),
                        'available' => true,
                        'supports'  => true,
                        'reason'    => sprintf('timeout after %d seconds', $timeout),
                    ],
                ]
            );
        }//end if

        if ($stderr !== '') {
            $this->logger->debug(
                '[LibreOfficeHeadlessBackend] soffice stderr',
                ['stderr' => substr($stderr, 0, 500)]
            );
        }

        if ($exitCode === -1) {
            return 1;
        }

        return $exitCode;

    }//end runWithTimeout()

    /**
     * Read and resolve the configured soffice binary path.
     * Defaults to `"soffice"` (resolved via PATH).
     *
     * @return string Binary path.
     */
    private function resolveBinaryPath(): string
    {
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
    private function resolveTimeout(): int
    {
        $raw = $this->appConfig->getValueString(self::APP_ID, self::TIMEOUT_KEY, '60');
        $val = (int) $raw;
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
    private function isBinaryExecutable(string $binary): bool
    {
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

                $candidate = rtrim($dir, '/').'/'.$binary;
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
    private function cleanupDir(string $dir): void
    {
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

            $path = $dir.'/'.$file;
            if (is_dir($path) === true) {
                $this->cleanupDir(dir: $path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);

    }//end cleanupDir()
}//end class
