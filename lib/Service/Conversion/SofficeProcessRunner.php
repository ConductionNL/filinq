<?php

/**
 * Soffice Subprocess Runner
 *
 * Runs the `soffice --headless` subprocess for LibreOfficeHeadlessBackend:
 * launches it via the array form of `proc_open` (no `/bin/sh -c` layer),
 * drains its pipes, and enforces the caller's timeout with `stream_select`.
 *
 * Extracted from LibreOfficeHeadlessBackend so the backend keeps to
 * conversion orchestration and the process plumbing is independently
 * readable and testable.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service\Conversion
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/specs/pdf-conversion/spec.md
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service\Conversion;

use OCA\DocuDesk\Exception\ConversionFailedException;
use Psr\Log\LoggerInterface;

/**
 * Launches and supervises the headless soffice subprocess.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service\Conversion
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/specs/pdf-conversion/spec.md
 */
class SofficeProcessRunner {
	/**
	 * Constructor.
	 *
	 * @param LoggerInterface $logger Logger for subprocess diagnostics.
	 */
	public function __construct(
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Invoke the command in a subprocess, draining stdout/stderr and
	 * enforcing the timeout via `stream_select`.
	 *
	 * Uses the array form of proc_open so PHP execs the binary directly
	 * without going through `/bin/sh -c` — eliminates the shell layer
	 * and the associated quoting/injection surface.
	 *
	 * @param array<int, string> $argv Process argv (argv[0] = binary).
	 * @param int $timeout Timeout in seconds.
	 * @param string $tmpDir Temp directory (for logging only).
	 * @param string $backendName Backend identifier for attempt records.
	 *
	 * @return int Process exit code (or 1 when the platform reports -1).
	 *
	 * @throws ConversionFailedException On timeout or if proc_open fails.
	 *
	 * @spec openspec/changes/pdf-conversion-service/tasks.md#task-6
	 */
	public function run(array $argv, int $timeout, string $tmpDir, string $backendName): int {
		[$proc, $pipes] = $this->openProcess(argv: $argv, backendName: $backendName);

		$outcome = $this->pumpUntilExit(proc: $proc, pipes: $pipes, timeout: $timeout);

		// Drain whatever is left before closing; stdout is discarded, soffice
		// reports everything actionable on stderr. The discard is deliberate —
		// an undrained pipe can block the child at proc_close() — so the
		// result is assigned to make that intent explicit rather than looking
		// like a forgotten return value.
		$discardedStdout = stream_get_contents($pipes[1]);
		unset($discardedStdout);

		$stderr = $outcome['stderr'] . stream_get_contents($pipes[2]);
		fclose($pipes[1]);
		fclose($pipes[2]);

		$exitCode = proc_close($proc);

		if ($outcome['timedOut'] === true) {
			$this->logger->warning(
				'[LibreOfficeHeadlessBackend] soffice timed out',
				[
					'timeout' => $timeout,
					'tmpDir' => $tmpDir,
					'stderr' => substr($stderr, 0, 500),
				]
			);
			throw new ConversionFailedException(
				message: sprintf('soffice timed out after %d seconds.', $timeout),
				attempts: [
					[
						'name' => $backendName,
						'available' => true,
						'supports' => true,
						'reason' => sprintf('timeout after %d seconds', $timeout),
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
	}//end run()

	/**
	 * Launch the subprocess and put its output pipes in non-blocking mode.
	 *
	 * @param array<int, string> $argv Process argv (argv[0] = binary).
	 * @param string $backendName Backend identifier for attempt records.
	 *
	 * @return array{0: resource, 1: array<int, resource>} The process handle and its pipes.
	 *
	 * @throws ConversionFailedException When proc_open refuses to launch.
	 */
	private function openProcess(array $argv, string $backendName): array {
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
						'name' => $backendName,
						'available' => true,
						'supports' => true,
						'reason' => 'proc_open returned false',
					],
				]
			);
		}

		fclose($pipes[0]);
		stream_set_blocking($pipes[1], false);
		stream_set_blocking($pipes[2], false);

		return [$proc, $pipes];
	}//end openProcess()

	/**
	 * Read from the process pipes until it exits or the deadline passes.
	 *
	 * Both pipes are drained so the child never blocks on a full buffer, but
	 * only stderr is retained — it is the only stream soffice reports
	 * actionable diagnostics on.
	 *
	 * @param resource $proc Process handle from proc_open.
	 * @param array<int, resource> $pipes Pipes from proc_open.
	 * @param int $timeout Timeout in seconds.
	 *
	 * @return array{timedOut: bool, stderr: string} Whether the deadline was hit,
	 *                                               plus the stderr collected so far.
	 */
	private function pumpUntilExit($proc, array $pipes, int $timeout): array {
		$deadline = (time() + $timeout);
		$stderr = '';
		$timedOut = false;

		while (true) {
			$remaining = ($deadline - time());
			if ($remaining <= 0) {
				$timedOut = true;
				proc_terminate($proc, 9);
				break;
			}

			$read = [$pipes[1], $pipes[2]];
			$write = null;
			$except = null;
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

			$stderr .= $this->drainStreams(streams: $read, stderrPipe: $pipes[2]);

			$status = proc_get_status($proc);
			if ($status['running'] === false) {
				break;
			}
		}//end while

		return [
			'timedOut' => $timedOut,
			'stderr' => $stderr,
		];

	}//end pumpUntilExit()

	/**
	 * Read one chunk from every readable stream, returning the stderr part.
	 *
	 * @param array<int, resource> $streams Streams flagged readable by stream_select.
	 * @param resource $stderrPipe The stderr pipe, so its chunk can be kept.
	 *
	 * @return string The chunk read from stderr, or an empty string.
	 */
	private function drainStreams(array $streams, $stderrPipe): string {
		$stderr = '';
		foreach ($streams as $stream) {
			$chunk = fread($stream, 8192);
			if ($chunk === false) {
				continue;
			}

			if ($stream === $stderrPipe) {
				$stderr .= $chunk;
			}
		}

		return $stderr;
	}//end drainStreams()
}//end class
