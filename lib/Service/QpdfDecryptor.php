<?php

/**
 * Filinq QpdfDecryptor
 *
 * The qpdf command line, for one job: decrypt a password-protected PDF. The
 * password goes to qpdf on its standard input (`--password-file=-`), never as
 * a process argument, and is never logged or put in an exception message
 * (intake-pdf-password-removal D1, D3).
 *
 * @category Service
 * @package  OCA\Filinq\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/intake-pdf-password-removal/tasks.md#1-service-and-route
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use RuntimeException;

/**
 * Runs qpdf to decrypt a PDF with a password read from standard input.
 *
 * @spec openspec/changes/intake-pdf-password-removal/tasks.md#1-service-and-route
 */
class QpdfDecryptor {

	/**
	 * Constructor.
	 *
	 * @param string $binary The qpdf executable.
	 */
	public function __construct(
		private readonly string $binary = 'qpdf',
	) {
	}//end __construct()

	/**
	 * Whether qpdf is installed and answers.
	 *
	 * @return bool
	 *
	 * @spec openspec/changes/intake-pdf-password-removal/tasks.md#1-service-and-route
	 */
	public function isAvailable(): bool {
		return $this->version() !== null;
	}//end isAvailable()

	/**
	 * The installed qpdf version line, or null when qpdf does not answer.
	 *
	 * @return string|null
	 *
	 * @spec openspec/changes/intake-pdf-password-removal/tasks.md#1-service-and-route
	 */
	public function version(): ?string {
		$run = $this->run(arguments: ['--version'], input: '');
		if ($run === null || $run['code'] !== 0) {
			return null;
		}

		$line = trim((string)strtok($run['stdout'], "\n"));
		if ($line === '') {
			return null;
		}

		return $line;
	}//end version()

	/**
	 * The decrypted bytes of a PDF, or null when the password is wrong.
	 *
	 * @param string $pdf      The locked PDF's bytes.
	 * @param string $password The password; it reaches qpdf only on its standard input.
	 *
	 * @return string|null The unlocked PDF, or null for a wrong password.
	 *
	 * @throws RuntimeException When qpdf cannot run or fails otherwise; the message never carries the password.
	 *
	 * @spec openspec/changes/intake-pdf-password-removal/tasks.md#1-service-and-route
	 */
	public function decrypt(string $pdf, string $password): ?string {
		$in  = tempnam(sys_get_temp_dir(), 'filinq-locked-');
		$out = tempnam(sys_get_temp_dir(), 'filinq-unlocked-');
		if ($in === false || $out === false) {
			throw new RuntimeException('No temporary file for qpdf.');
		}

		try {
			file_put_contents($in, $pdf);
			$run = $this->run(arguments: ['--password-file=-', '--decrypt', $in, $out], input: $password);
			if ($run === null) {
				throw new RuntimeException('qpdf could not be started.');
			}

			// 0 is done, 3 is done with warnings; 2 is an error, and a wrong
			// password is the error qpdf names "invalid password".
			if ($run['code'] === 2 && stripos($run['stderr'], 'invalid password') !== false) {
				return null;
			}

			if ($run['code'] !== 0 && $run['code'] !== 3) {
				throw new RuntimeException(sprintf('qpdf could not decrypt the PDF (exit %d).', $run['code']));
			}

			return (string)file_get_contents($out);
		} finally {
			@unlink($in);
			@unlink($out);
		}
	}//end decrypt()

	/**
	 * Run qpdf without a shell, writing the input to its standard input.
	 *
	 * @param array<int, string> $arguments The arguments after the binary.
	 * @param string             $input     What to write to its standard input.
	 *
	 * @return array{code: int, stdout: string, stderr: string}|null The outcome, or null when it could not start.
	 */
	protected function run(array $arguments, string $input): ?array {
		$pipes   = [];
		$process = @proc_open(
			array_merge([$this->binary], $arguments),
			[0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
			$pipes
		);
		if (is_resource($process) === false) {
			return null;
		}

		fwrite($pipes[0], $input);
		fclose($pipes[0]);
		$stdout = (string)stream_get_contents($pipes[1]);
		$stderr = (string)stream_get_contents($pipes[2]);
		fclose($pipes[1]);
		fclose($pipes[2]);
		$code = proc_close($process);

		// A missing binary surfaces as exit 127 from proc_open's exec.
		if ($code === 127) {
			return null;
		}

		return ['code' => $code, 'stdout' => $stdout, 'stderr' => $stderr];
	}//end run()
}//end class
