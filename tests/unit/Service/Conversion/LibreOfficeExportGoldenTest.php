<?php

/**
 * Golden: what the three soffice conversions run and return
 *
 * Records, for convert(), convertTagged() and convertHtml(), the soffice
 * command line, the timeout, the source written for soffice, the lock calls,
 * what came back or the failure, and whether the temp directory was removed.
 * Recorded on the export helper that took seven parameters.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Conversion
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-pdfua-accessible-output/tasks.md#task-1.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Conversion;

use OCA\Filinq\Exception\ConversionFailedException;
use OCA\Filinq\Service\Conversion\LibreOfficeHeadlessBackend;
use OCA\Filinq\Service\Conversion\SofficeProcessRunner;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\IAppConfig;
use OCP\Lock\ILockingProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Golden for the soffice conversions.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Conversion
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @covers \OCA\Filinq\Service\Conversion\LibreOfficeHeadlessBackend
 * @uses   \OCA\Filinq\Exception\ConversionFailedException
 * @uses   \OCA\Filinq\Service\Conversion\SofficeCommand
 * @uses   \OCA\Filinq\Service\Conversion\SofficeExport
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class LibreOfficeExportGoldenTest extends TestCase {

	private const GOLDEN = __DIR__ . '/golden/libreoffice-export.json';

	/**
	 * What happened in the current case.
	 *
	 * @var array<int, mixed>
	 */
	private array $events = [];

	/**
	 * Every case answers as recorded.
	 *
	 * @return void
	 */
	public function testGolden(): void {
		$actual = [];
		foreach (['ok', 'exit3', 'none', 'empty'] as $mode) {
			foreach (['brief aan inwoner.docx', 'noext', 'a.b.ODT', '../x.html', 'page.htm'] as $name) {
				$actual['convert '.$mode.' '.$name] = $this->record(
					mode: $mode,
					config: [],
					call: fn (LibreOfficeHeadlessBackend $backend): mixed => $this->convertFile(backend: $backend, name: $name, exists: ($name === 'noext'))
				);
			}

			foreach (['html', 'HTM', 'docx', '', 'odt'] as $ext) {
				foreach ([false, true] as $pdfa) {
					$actual['tagged '.$mode.' '.$ext.' '.var_export($pdfa, true)] = $this->record(
						mode: $mode,
						config: [],
						call: static fn (LibreOfficeHeadlessBackend $backend): mixed => $backend->convertTagged(bytes: 'src '.$ext, extension: $ext, pdfa: $pdfa)
					);
				}
			}

			foreach (['docx', 'odt'] as $to) {
				$actual['html '.$mode.' '.$to] = $this->record(
					mode: $mode,
					config: [],
					call: static fn (LibreOfficeHeadlessBackend $backend): mixed => $backend->convertHtml(html: '<p>hoi</p>', toExtension: $to)
				);
			}
		}//end foreach

		foreach (['0', '7', 'abc', '-3', ''] as $timeout) {
			$config = ['filinq.conversion.timeout_seconds' => $timeout];
			$actual['timeout '.$timeout.' convert'] = $this->record(
				mode: 'ok',
				config: $config,
				call: fn (LibreOfficeHeadlessBackend $backend): mixed => $this->convertFile(backend: $backend, name: 'a.docx', exists: false)
			);
			$actual['timeout '.$timeout.' tagged'] = $this->record(
				mode: 'ok',
				config: $config,
				call: static fn (LibreOfficeHeadlessBackend $backend): mixed => $backend->convertTagged(bytes: 'x', extension: 'html', pdfa: true)
			);
			$actual['timeout '.$timeout.' html'] = $this->record(
				mode: 'ok',
				config: $config,
				call: static fn (LibreOfficeHeadlessBackend $backend): mixed => $backend->convertHtml(html: 'x', toExtension: 'odt')
			);
		}

		if (getenv('GOLDEN_RECORD') === '1') {
			file_put_contents(self::GOLDEN, json_encode($actual, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
		}

		$recorded = json_decode((string) file_get_contents(self::GOLDEN), true);
		$this->assertSame($recorded, json_decode((string) json_encode($actual), true));

	}//end testGolden()

	/**
	 * Run one call against a fresh backend and record it.
	 *
	 * @param string                $mode   How soffice behaves.
	 * @param array<string, string> $config App config values by key.
	 * @param callable              $call   The conversion.
	 *
	 * @return array<string, mixed>
	 */
	private function record(string $mode, array $config, callable $call): array {
		$this->events = [];
		$runner       = new RecordingSofficeRunner(mode: $mode);
		try {
			$result = ['returned' => $this->plain(value: $call($this->backend(runner: $runner, config: $config)))];
		} catch (Throwable $e) {
			$result = ['threw' => $this->failure(error: $e)];
		}

		$left = array_filter($runner->dirs, 'is_dir');

		return $result + ['runs' => $runner->runs, 'events' => $this->events, 'tmpLeft' => count($left)];

	}//end record()

	/**
	 * Convert a source file node of a given name.
	 *
	 * @param LibreOfficeHeadlessBackend $backend The backend.
	 * @param string                     $name    The source name.
	 * @param bool                       $exists  Whether a PDF of that name already exists.
	 *
	 * @return string The name of the node written.
	 */
	private function convertFile(LibreOfficeHeadlessBackend $backend, string $name, bool $exists): string {
		$existing = $this->createMock(File::class);
		$existing->method('delete')->willReturnCallback(function (): void {
			$this->events[] = ['delete'];
		});
		$parent = $this->createMock(Folder::class);
		$parent->method('nodeExists')->willReturnCallback(function (string $path) use ($exists): bool {
			$this->events[] = ['nodeExists', $path];
			return $exists;
		});
		$parent->method('get')->willReturn($existing);
		$written = $this->createMock(File::class);
		$written->method('getName')->willReturn('written');
		$parent->method('newFile')->willReturnCallback(function (string $path, mixed $content = null) use ($written): File {
			$this->events[] = ['newFile', $path, $content];
			return $written;
		});
		$source = $this->createMock(File::class);
		$source->method('getName')->willReturn($name);
		$source->method('getContent')->willReturn('bytes of '.$name);
		$source->method('getParent')->willReturn($parent);

		return $backend->convert($source)->getName();

	}//end convertFile()

	/**
	 * A backend over a recording lock and the runner.
	 *
	 * @param RecordingSofficeRunner $runner The runner.
	 * @param array<string, string>  $config App config values by key.
	 *
	 * @return LibreOfficeHeadlessBackend
	 */
	private function backend(RecordingSofficeRunner $runner, array $config): LibreOfficeHeadlessBackend {
		$config += [
			'filinq.conversion.libreoffice_binary_path'      => '/bin/sh',
			'filinq.conversion.backends.libreoffice_enabled' => 'true',
		];
		$appConfig = $this->createMock(IAppConfig::class);
		$appConfig->method('getValueString')->willReturnCallback(
			static fn (string $app, string $key, string $default = ''): string => ($config[$key] ?? $default)
		);
		$lock = $this->createMock(ILockingProvider::class);
		$lock->method('acquireLock')->willReturnCallback(function (string $path, int $type): void {
			$this->events[] = ['acquireLock', $path, $type];
		});
		$lock->method('releaseLock')->willReturnCallback(function (string $path, int $type): void {
			$this->events[] = ['releaseLock', $path, $type];
		});

		return new LibreOfficeHeadlessBackend(
			appConfig: $appConfig,
			lockingProvider: $lock,
			logger: $this->createMock(LoggerInterface::class),
			processRunner: $runner,
		);

	}//end backend()

	/**
	 * A failure as data, with temp paths made stable.
	 *
	 * @param Throwable $error The failure.
	 *
	 * @return array<string, mixed>
	 */
	private function failure(Throwable $error): array {
		$out = ['class' => get_class($error), 'message' => $error->getMessage(), 'code' => $error->getCode()];
		if ($error instanceof ConversionFailedException) {
			$out['attempts'] = $this->plain(value: $error->getAttempts());
		}

		return $out;

	}//end failure()

	/**
	 * A value with every temp directory replaced by <tmp>.
	 *
	 * @param mixed $value The value.
	 *
	 * @return mixed
	 */
	private function plain(mixed $value): mixed {
		$json = (string) json_encode($value, JSON_UNESCAPED_SLASHES);
		$json = (string) preg_replace('#'.preg_quote(sys_get_temp_dir(), '#').'/filinq_libreoffice_[0-9a-f]{16}#', '<tmp>', $json);

		return json_decode($json, true);

	}//end plain()
}//end class

/**
 * A soffice runner that records each run and behaves as told.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Conversion
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class RecordingSofficeRunner extends SofficeProcessRunner {

	/**
	 * Each run, with its temp directory made stable.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	public array $runs = [];

	/**
	 * The temp directory of each run.
	 *
	 * @var array<int, string>
	 */
	public array $dirs = [];

	/**
	 * Constructor.
	 *
	 * @param string $mode ok, exit3, none or empty.
	 */
	public function __construct(private readonly string $mode) {
	}//end __construct()

	/**
	 * Record the run and emit soffice's output for the mode.
	 *
	 * @param array<int, string> $argv        The argv.
	 * @param int                $timeout     The timeout.
	 * @param string             $tmpDir      The directory.
	 * @param string             $backendName The backend.
	 *
	 * @return int The exit code.
	 */
	public function run(array $argv, int $timeout, string $tmpDir, string $backendName): int {
		$this->dirs[] = $tmpDir;
		$input  = (string) end($argv);
		$outdir = $argv[(int) array_search('--outdir', $argv, true) + 1];
		$listed = scandir($tmpDir);
		$this->runs[] = [
			'argv'        => str_replace($tmpDir, '<tmp>', $argv),
			'timeout'     => $timeout,
			'tmpIsOutdir' => ($outdir === $tmpDir),
			'tmpPrefix'   => str_starts_with($tmpDir, sys_get_temp_dir().'/filinq_libreoffice_'),
			'tmpMode'     => substr(sprintf('%o', fileperms($tmpDir)), -4),
			'files'       => array_values(array_diff(($listed === false ? [] : $listed), ['.', '..'])),
			'input'       => file_get_contents($input),
			'backendName' => $backendName,
		];
		$target = strtok($argv[(int) array_search('--convert-to', $argv, true) + 1], ':');
		$output = $outdir.'/'.pathinfo($input, PATHINFO_FILENAME).'.'.$target;
		return match ($this->mode) {
			'exit3' => 3,
			'none'  => 0,
			'empty' => (int) file_put_contents($output, '') * 0,
			default => (int) file_put_contents($output, '%PDF out '.$target) * 0,
		};

	}//end run()
}//end class
