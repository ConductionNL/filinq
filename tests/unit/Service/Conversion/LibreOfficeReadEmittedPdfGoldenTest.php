<?php

/**
 * Golden test for reading back the file soffice emitted: found, missing, outside the sandbox or empty.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Conversion
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/pdf-conversion-service/tasks.md#task-6
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Conversion;

use OCA\Filinq\Exception\ConversionFailedException;
use OCA\Filinq\Service\Conversion\LibreOfficeHeadlessBackend;
use OCP\IAppConfig;
use OCP\Lock\ILockingProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use ReflectionMethod;

/**
 * Every outcome of readEmittedPdf(), compared with JSON recorded from the
 * implementation before it was split.
 *
 * @covers \OCA\Filinq\Service\Conversion\LibreOfficeHeadlessBackend
 * @uses   \OCA\Filinq\Exception\ConversionFailedException
 * @uses   \OCA\Filinq\Service\Conversion\SofficeProcessRunner
 */
class LibreOfficeReadEmittedPdfGoldenTest extends TestCase {

	/**
	 * The sandbox directory of one case.
	 *
	 * @var string
	 */
	private string $tmpDir = '';

	/**
	 * A directory outside the sandbox.
	 *
	 * @var string
	 */
	private string $outside = '';

	/**
	 * The scenarios: what lies in the sandbox, and the extension asked for.
	 *
	 * @return array<string, array{0: string, 1: string}>
	 */
	public static function scenarios(): array {
		return [
			'a pdf' => ['file', 'pdf'],
			'a docx' => ['file', 'docx'],
			'nothing' => ['none', 'pdf'],
			'another extension' => ['file-odt', 'pdf'],
			'an empty file' => ['empty', 'pdf'],
			'a link out of the sandbox' => ['link-out', 'pdf'],
			'a link inside the sandbox' => ['link-in', 'pdf'],
			'a dangling link' => ['link-dangling', 'pdf'],
			'a sandbox that is a link' => ['sandbox-link', 'pdf'],
		];
	}//end scenarios()

	/**
	 * Remove the case's directories.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		foreach ([$this->tmpDir, $this->outside, $this->tmpDir . '-real'] as $dir) {
			if ($dir !== '' && (is_dir($dir) === true || is_link($dir) === true)) {
				exec('rm -rf ' . escapeshellarg($dir));
			}
		}

		parent::tearDown();
	}//end tearDown()

	/**
	 * The outcome matches the recorded one.
	 *
	 * @param string $setup     What lies in the sandbox.
	 * @param string $extension The extension asked for.
	 *
	 * @dataProvider scenarios
	 *
	 * @return void
	 */
	public function testOutcome(string $setup, string $extension): void {
		$base = sys_get_temp_dir() . '/filinq_read_golden_' . bin2hex(random_bytes(6));
		$this->tmpDir = $base;
		$this->outside = $base . '-outside';
		mkdir($this->outside, 0700, true);
		file_put_contents($this->outside . '/input.pdf', '%PDF-outside');
		$this->arrange($setup);

		$backend = new LibreOfficeHeadlessBackend(
			$this->createMock(IAppConfig::class),
			$this->createMock(ILockingProvider::class),
			new NullLogger()
		);
		$read = new ReflectionMethod($backend, 'readEmittedPdf');

		try {
			$trace = ['bytes' => $read->invoke($backend, $this->tmpDir, 'input', $extension)];
		} catch (ConversionFailedException $e) {
			$trace = ['error' => [$e->getMessage(), $e->getCode(), $e->getAttempts()]];
		}

		$trace = json_decode(str_replace($base, '<tmp>', (string)json_encode($trace, JSON_UNESCAPED_SLASHES)), true);

		$path = __DIR__ . '/../golden/libreoffice-read-emitted-pdf.json';
		$recorded = [];
		if (is_file($path) === true) {
			$recorded = json_decode((string)file_get_contents($path), true);
		}

		$name = (string)$this->dataName();
		if (getenv('GOLDEN_RECORD') === '1') {
			$recorded[$name] = $trace;
			file_put_contents($path, json_encode($recorded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		}

		$this->assertSame($recorded[$name] ?? null, $trace);
	}//end testOutcome()

	/**
	 * A directory or path that does not resolve is never inside.
	 *
	 * @return void
	 */
	public function testUnresolvedPathsAreNotInside(): void {
		$backend = new LibreOfficeHeadlessBackend(
			$this->createMock(IAppConfig::class),
			$this->createMock(ILockingProvider::class),
			new NullLogger()
		);
		$inside = new ReflectionMethod($backend, 'isInsideDir');
		$tmp = sys_get_temp_dir();

		$this->assertFalse($inside->invoke($backend, $tmp . '/filinq-missing-dir', $tmp . '/filinq-missing-dir/input.pdf'));
		$this->assertFalse($inside->invoke($backend, $tmp, $tmp . '/filinq-missing-file.pdf'));
		$this->assertFalse($inside->invoke($backend, $tmp . '/filinq-missing-dir', $tmp));
	}//end testUnresolvedPathsAreNotInside()

	/**
	 * Lay out the sandbox for one case.
	 *
	 * @param string $setup What lies in the sandbox.
	 *
	 * @return void
	 */
	private function arrange(string $setup): void {
		if ($setup === 'sandbox-link') {
			mkdir($this->tmpDir . '-real', 0700, true);
			file_put_contents($this->tmpDir . '-real/input.pdf', '%PDF-real');
			symlink($this->tmpDir . '-real', $this->tmpDir);
			return;
		}

		mkdir($this->tmpDir, 0700, true);
		$file = $this->tmpDir . '/input';
		match ($setup) {
			'file' => file_put_contents($file . '.pdf', '%PDF-1.7 pdf') && file_put_contents($file . '.docx', 'PK docx'),
			'file-odt' => file_put_contents($file . '.odt', 'PK odt'),
			'empty' => touch($file . '.pdf'),
			'link-out' => symlink($this->outside . '/input.pdf', $file . '.pdf'),
			'link-in' => file_put_contents($this->tmpDir . '/real.pdf', '%PDF-linked') && symlink($this->tmpDir . '/real.pdf', $file . '.pdf'),
			'link-dangling' => symlink($this->tmpDir . '/gone.pdf', $file . '.pdf'),
			default => null,
		};
	}//end arrange()
}//end class
