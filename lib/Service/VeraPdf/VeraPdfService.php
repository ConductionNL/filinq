<?php

/**
 * veraPDF, the reference PDF/A validator, as an optional local binary.
 *
 * Same pattern as soffice and Tesseract: the admin installs it, Filinq
 * probes it, and without it every caller says "not validated" instead of
 * guessing. The document is written to a temporary file on this server
 * and never sent anywhere else.
 *
 * App config: filinq.verapdf.binary_path (default "verapdf" on the PATH),
 * filinq.verapdf.enabled (default true, so it is used when found),
 * filinq.verapdf.max_seconds (default 60).
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
use OCP\Files\File;
use OCP\IAppConfig;
use OCP\ITempManager;
use Throwable;

/**
 * Probe and validate.
 */
class VeraPdfService {

	public const CFG_BINARY_PATH = 'filinq.verapdf.binary_path';

	public const CFG_ENABLED = 'filinq.verapdf.enabled';

	public const CFG_MAX_SECONDS = 'filinq.verapdf.max_seconds';

	/**
	 * The profile used when the document claims no PDF/A flavour.
	 */
	public const DEFAULT_FLAVOUR = '3b';

	private const APP_ID = 'filinq';

	/**
	 * The failed checks veraPDF prints per rule; enough to name the fonts.
	 */
	private const CHECKS_PER_RULE = '20';

	/**
	 * The probe result for this request.
	 *
	 * @var array{available: bool, version: string}|null
	 */
	private ?array $probe = null;

	/**
	 * Constructor.
	 *
	 * @param IAppConfig          $appConfig The app config.
	 * @param ITempManager        $temp      Temporary files.
	 * @param VeraPdfProcess      $process   The process runner.
	 * @param VeraPdfReportParser $parser    The report parser.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IAppConfig $appConfig,
		private readonly ITempManager $temp,
		private readonly VeraPdfProcess $process,
		private readonly VeraPdfReportParser $parser,
	) {

	}//end __construct()

	/**
	 * What the admin settings page shows.
	 *
	 * @return array{enabled: bool, available: bool, version: string, binaryPath: string} The status.
	 *
	 * @spec openspec/changes/archive/2026-09-29-verapdf-validation/tasks.md#task-3.2
	 */
	public function status(): array {
		$enabled = $this->isEnabled();
		$probe = ['available' => false, 'version' => ''];
		if ($enabled === true) {
			$probe = $this->probe();
		}

		return [
			'enabled' => $enabled,
			'available' => $probe['available'],
			'version' => $probe['version'],
			'binaryPath' => $this->binaryPath(),
		];

	}//end status()

	/**
	 * Whether a validation can run now: switched on and the binary answers.
	 *
	 * @return bool True when available.
	 *
	 * @spec openspec/changes/archive/2026-09-29-verapdf-validation/tasks.md#task-2.1
	 */
	public function isAvailable(): bool {
		return ($this->isEnabled() === true && $this->probe()['available'] === true);

	}//end isAvailable()

	/**
	 * Validate a stored file.
	 *
	 * @param File        $file    The PDF.
	 * @param string|null $flavour A flavour such as "2b", or null for the one the document claims.
	 *
	 * @return array<string, mixed> The verdict (see VeraPdfReportParser::parse) with elapsedMs.
	 *
	 * @throws VeraPdfException When no verdict could be had.
	 *
	 * @spec openspec/changes/archive/2026-09-29-verapdf-validation/tasks.md#task-2.1
	 */
	public function validate(File $file, ?string $flavour = null): array {
		try {
			$bytes = (string) $file->getContent();
		} catch (Throwable $e) {
			throw new VeraPdfException(reason: VeraPdfException::REASON_FAILED, message: 'The document could not be read.', previous: $e);
		}

		return $this->validateBytes(bytes: $bytes, flavour: $flavour);

	}//end validate()

	/**
	 * Validate PDF bytes, such as a conversion's output before it is stored.
	 *
	 * @param string      $bytes   The PDF.
	 * @param string|null $flavour A flavour such as "2b", or null for the one the document claims.
	 *
	 * @return array<string, mixed> The verdict with elapsedMs.
	 *
	 * @throws VeraPdfException When no verdict could be had.
	 *
	 * @spec openspec/changes/archive/2026-09-29-verapdf-validation/tasks.md#task-2.1
	 */
	public function validateBytes(string $bytes, ?string $flavour = null): array {
		if ($this->isAvailable() === false) {
			throw new VeraPdfException(reason: VeraPdfException::REASON_UNAVAILABLE, message: 'veraPDF is not installed or is switched off.');
		}

		$path = $this->temp->getTemporaryFile('.pdf');
		if ($path === false || file_put_contents($path, $bytes) === false) {
			throw new VeraPdfException(reason: VeraPdfException::REASON_FAILED, message: 'No temporary file could be written for validation.');
		}

		$started = microtime(true);
		try {
			$run = $this->process->run(argv: $this->arguments(flavour: $flavour, path: $path), seconds: $this->maxSeconds());
		} finally {
			if (is_file($path) === true) {
				unlink($path);
			}
		}

		if ($run['timedOut'] === true) {
			throw new VeraPdfException(
				reason: VeraPdfException::REASON_TIMEOUT,
				message: sprintf('veraPDF took longer than %d seconds and was stopped.', $this->maxSeconds())
			);
		}

		try {
			$verdict = $this->parser->parse(json: $run['stdout']);
		} catch (VeraPdfException $e) {
			throw new VeraPdfException(
				reason: VeraPdfException::REASON_FAILED,
				message: $e->getMessage() . ' (exit code ' . $run['exitCode'] . ')',
				previous: $e
			);
		}

		$verdict['elapsedMs'] = (int) round((microtime(true) - $started) * 1000);

		return $verdict;

	}//end validateBytes()

	/**
	 * The command line for one run.
	 *
	 * @param string|null $flavour The requested flavour.
	 * @param string      $path    The file.
	 *
	 * @return array<int, string> The argv.
	 */
	private function arguments(?string $flavour, string $path): array {
		$argv = [$this->binaryPath(), '--format', 'json', '--maxfailuresdisplayed', self::CHECKS_PER_RULE];
		// Validate what the document claims; a document claiming nothing gets 3b.
		$flavourArgs = ['--flavour', '0', '--defaultflavour', self::DEFAULT_FLAVOUR];
		if ($flavour !== null && preg_match('/^[1-3][abu]$/', $flavour) === 1) {
			$flavourArgs = ['--flavour', $flavour];
		}

		array_push($argv, ...$flavourArgs);

		$argv[] = $path;

		return $argv;

	}//end arguments()

	/**
	 * Ask the binary for its version, once per request.
	 *
	 * @return array{available: bool, version: string} The probe.
	 */
	private function probe(): array {
		if ($this->probe !== null) {
			return $this->probe;
		}

		$this->probe = ['available' => false, 'version' => ''];
		try {
			$run = $this->process->run(argv: [$this->binaryPath(), '--version'], seconds: min(30, $this->maxSeconds()));
		} catch (VeraPdfException) {
			return $this->probe;
		}

		if ($run['timedOut'] === false && $run['exitCode'] === 0 && preg_match('/veraPDF\s+([0-9][0-9.]*)/i', $run['stdout'], $match) === 1) {
			$this->probe = ['available' => true, 'version' => 'veraPDF ' . $match[1]];
		}

		return $this->probe;

	}//end probe()

	/**
	 * Whether the admin left the validator switched on.
	 *
	 * @return bool True unless switched off.
	 */
	private function isEnabled(): bool {
		$value = strtolower($this->appConfig->getValueString(self::APP_ID, self::CFG_ENABLED, 'true'));

		return in_array($value, ['false', '0', 'no', 'off'], true) === false;

	}//end isEnabled()

	/**
	 * The configured binary.
	 *
	 * @return string The path or command name.
	 */
	private function binaryPath(): string {
		$path = trim($this->appConfig->getValueString(self::APP_ID, self::CFG_BINARY_PATH, 'verapdf'));

		if ($path === '') {
			return 'verapdf';
		}

		return $path;

	}//end binaryPath()

	/**
	 * The wall-clock budget per run.
	 *
	 * @return int Seconds, at least 1.
	 */
	private function maxSeconds(): int {
		return max(1, $this->appConfig->getValueInt(self::APP_ID, self::CFG_MAX_SECONDS, 60));

	}//end maxSeconds()
}//end class
