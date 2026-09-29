<?php

/**
 * Runs the veraPDF command line with a wall-clock budget and keeps what it
 * printed.
 *
 * The array form of proc_open execs the binary directly, so no shell reads
 * the arguments. A run past the budget is killed and reported as timed out.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\VeraPdf
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-verapdf-validation/tasks.md#task-2.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\VeraPdf;

use OCA\Filinq\Exception\VeraPdfException;

/**
 * One veraPDF process.
 */
class VeraPdfProcess {

	/**
	 * The most output kept; a report for one document is far smaller.
	 */
	private const MAX_OUTPUT_BYTES = 16777216;

	/**
	 * Run a command and wait for it, at most $seconds.
	 *
	 * @param array<int, string> $argv    The binary and its arguments.
	 * @param int                $seconds The wall-clock budget.
	 *
	 * @return array{exitCode: int, stdout: string, stderr: string, timedOut: bool} What happened.
	 *
	 * @throws VeraPdfException When the binary cannot be started.
	 *
	 * @spec openspec/changes/archive/2026-09-29-verapdf-validation/tasks.md#task-2.1
	 */
	public function run(array $argv, int $seconds): array {
		if ($this->isRunnable(binary: (string) ($argv[0] ?? '')) === false) {
			throw new VeraPdfException(reason: VeraPdfException::REASON_UNAVAILABLE, message: 'veraPDF could not be started: ' . $argv[0]);
		}

		$pipes = [];
		$proc = proc_open($argv, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
		if (is_resource($proc) === false) {
			throw new VeraPdfException(reason: VeraPdfException::REASON_UNAVAILABLE, message: 'veraPDF could not be started: ' . $argv[0]);
		}

		fclose($pipes[0]);
		stream_set_blocking($pipes[1], false);
		stream_set_blocking($pipes[2], false);

		$outcome = $this->pump(proc: $proc, pipes: $pipes, deadline: (microtime(true) + max(1, $seconds)));
		fclose($pipes[1]);
		fclose($pipes[2]);
		$closed = proc_close($proc);
		if ($outcome['exitCode'] === null) {
			$outcome['exitCode'] = $closed;
		}

		return $outcome;

	}//end run()

	/**
	 * Read both pipes until the process exits or the deadline passes.
	 *
	 * @param resource             $proc     The process.
	 * @param array<int, resource> $pipes    Its pipes.
	 * @param float                $deadline When to give up.
	 *
	 * @return array{exitCode: int|null, stdout: string, stderr: string, timedOut: bool} What happened.
	 */
	private function pump($proc, array $pipes, float $deadline): array {
		$out = ['exitCode' => null, 'stdout' => '', 'stderr' => '', 'timedOut' => false];
		while (true) {
			$remaining = ($deadline - microtime(true));
			if ($remaining <= 0) {
				proc_terminate($proc, 9);
				$out['timedOut'] = true;
				return $out;
			}

			$read = [$pipes[1], $pipes[2]];
			$write = null;
			$except = null;
			if ((int) stream_select($read, $write, $except, 0, (int) min(200000, ($remaining * 1000000))) > 0) {
				$out['stdout'] .= $this->chunk(stream: $pipes[1], ready: $read, kept: strlen($out['stdout']));
				$out['stderr'] .= $this->chunk(stream: $pipes[2], ready: $read, kept: strlen($out['stderr']));
			}

			$status = proc_get_status($proc);
			if ($status['running'] === false) {
				// The exit code is only reported by the first status call after exit.
				$out['exitCode'] = (int) $status['exitcode'];
				$out['stdout'] .= (string) stream_get_contents($pipes[1], (self::MAX_OUTPUT_BYTES - strlen($out['stdout'])));
				$out['stderr'] .= (string) stream_get_contents($pipes[2], 65536);
				return $out;
			}
		}//end while

	}//end pump()

	/**
	 * Read what a ready stream has, up to the output cap.
	 *
	 * @param resource             $stream The stream.
	 * @param array<int, resource> $ready  The streams select marked ready.
	 * @param int                  $kept   How much was kept already.
	 *
	 * @return string The new bytes.
	 */
	private function chunk($stream, array $ready, int $kept): string {
		if (in_array($stream, $ready, true) === false || $kept >= self::MAX_OUTPUT_BYTES) {
			return '';
		}

		return (string) fread($stream, 65536);

	}//end chunk()

	/**
	 * Whether the binary exists and may run: a path, or a name on the PATH.
	 * Checked first so a missing binary is an answer, not a PHP warning.
	 *
	 * @param string $binary The binary.
	 *
	 * @return bool
	 */
	private function isRunnable(string $binary): bool {
		if ($binary === '') {
			return false;
		}

		if (str_contains($binary, '/') === true) {
			return is_file($binary) === true && is_executable($binary) === true;
		}

		foreach (explode(PATH_SEPARATOR, (string) getenv('PATH')) as $dir) {
			if ($dir !== '' && is_file($dir . '/' . $binary) === true && is_executable($dir . '/' . $binary) === true) {
				return true;
			}
		}

		return false;

	}//end isRunnable()
}//end class
