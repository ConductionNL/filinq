<?php

/**
 * Tests for SvgRasterizer (template-charts REQ-DDTCH-007).
 *
 * @category Test
 * @package  OCA\Filinq\Tests\Unit\Service\Charts
 * @author   Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Charts;

use OCA\Filinq\Service\Charts\ChartSvgRenderer;
use OCA\Filinq\Service\Charts\SvgRasterizer;
use OCA\Filinq\Service\Conversion\SofficeProcessRunner;
use OCP\IAppConfig;
use OCP\Lock\ILockingProvider;
use OCP\Lock\LockedException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Inline chart SVG becomes a PNG before an HTML to ODF or DOCX conversion,
 * and a chart that cannot be converted becomes a marker plus a warning.
 */
class SvgRasterizerTest extends TestCase {

	private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

	private IAppConfig&MockObject $appConfig;
	private ILockingProvider&MockObject $locking;
	private SofficeProcessRunner&MockObject $runner;

	protected function setUp(): void {
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->appConfig->method('getValueString')->willReturnCallback(
			static fn (string $app, string $key, string $default) => $default
		);
		$this->locking = $this->createMock(ILockingProvider::class);
		$this->runner = $this->createMock(SofficeProcessRunner::class);
	}

	private function rasterizer(): SvgRasterizer {
		return new SvgRasterizer($this->appConfig, $this->locking, new NullLogger(), $this->runner);
	}

	private function chart(): string {
		return (new ChartSvgRenderer())->render('bar', ['labels' => ['A'], 'series' => [['name' => 'S', 'values' => [1]]]]);
	}

	/**
	 * A runner that behaves like soffice: it writes <name>.png next to the input.
	 */
	private function sofficeWritesPng(): void {
		$this->runner->method('run')->willReturnCallback(static function (array $argv, int $timeout, string $tmpDir): int {
			$input = end($argv);
			file_put_contents($tmpDir . '/' . pathinfo($input, PATHINFO_FILENAME) . '.png', base64_decode(self::PNG));
			return 0;
		});
	}

	public function testAChartBecomesAnEmbeddedPngAtItsPosition(): void {
		$this->sofficeWritesPng();
		$this->locking->expects($this->once())->method('acquireLock')->with('soffice:headless:convert');
		$this->locking->expects($this->once())->method('releaseLock');

		$result = $this->rasterizer()->rasterizeInlineSvg(html: '<p>before</p>' . $this->chart() . '<p>after</p>', format: 'odf');

		$this->assertSame(
			'<p>before</p><img src="data:image/png;base64,' . self::PNG . '" width="600" height="300" alt="" /><p>after</p>',
			$result['html']
		);
		$this->assertSame([], $result['warnings']);
	}

	public function testHtmlWithoutSvgIsUntouchedAndStartsNoProcess(): void {
		$this->runner->expects($this->never())->method('run');
		$this->locking->expects($this->never())->method('acquireLock');

		$result = $this->rasterizer()->rasterizeInlineSvg(html: '<p>no chart</p>', format: 'odf');

		$this->assertSame('<p>no chart</p>', $result['html']);
		$this->assertSame([], $result['warnings']);
	}

	public function testAFailedConversionLeavesAMarkerAndAWarningNamingTheFormat(): void {
		$this->runner->method('run')->willReturn(1);

		$result = $this->rasterizer()->rasterizeInlineSvg(html: $this->chart(), format: 'docx');

		$this->assertSame('<span>[chart error: the chart could not be converted for docx]</span>', $result['html']);
		$this->assertSame(['chart error: the chart could not be converted for docx'], $result['warnings']);
	}

	public function testATimedOutConversionIsTheSameHonestMarker(): void {
		$this->runner->method('run')->willThrowException(
			new \OCA\Filinq\Exception\ConversionFailedException(message: 'soffice timed out after 60 seconds.', attempts: [])
		);

		$result = $this->rasterizer()->rasterizeInlineSvg(html: $this->chart(), format: 'odf');

		$this->assertSame('<span>[chart error: the chart could not be converted for odf]</span>', $result['html']);
		$this->assertCount(1, $result['warnings']);
	}

	public function testABusyConverterIsTheSameHonestMarker(): void {
		$this->locking->method('acquireLock')->willThrowException(new LockedException('soffice:headless:convert'));
		$this->runner->expects($this->never())->method('run');

		$result = $this->rasterizer()->rasterizeInlineSvg(html: $this->chart() . $this->chart(), format: 'odf');

		$this->assertSame(2, substr_count($result['html'], '[chart error: the chart could not be converted for odf]'));
		$this->assertCount(2, $result['warnings']);
		$this->assertStringNotContainsString('<svg', $result['html']);
	}

	public function testTheRunnerGetsAPrivateProfileAndThePngFilter(): void {
		$seen = [];
		$this->runner->method('run')->willReturnCallback(static function (array $argv, int $timeout, string $tmpDir) use (&$seen): int {
			$seen = $argv;
			file_put_contents($tmpDir . '/' . pathinfo(end($argv), PATHINFO_FILENAME) . '.png', base64_decode(self::PNG));
			return 0;
		});

		$this->rasterizer()->rasterizeInlineSvg(html: $this->chart(), format: 'odf');

		$this->assertSame('soffice', $seen[0]);
		$this->assertContains('--headless', $seen);
		$this->assertContains('png', $seen);
		$this->assertMatchesRegularExpression('#^-env:UserInstallation=file://#', $seen[1]);
		$this->assertStringEndsWith('.svg', end($seen));
	}

	/**
	 * Soffice exiting 0 without a PNG, or with an empty one, is the same marker; the lock is released.
	 *
	 * @return void
	 */
	public function testAMissingOrEmptyPngIsTheSameMarker(): void {
		$calls = 0;
		$this->runner->method('run')->willReturnCallback(static function (array $argv, int $timeout, string $tmpDir) use (&$calls): int {
			$calls++;
			if ($calls === 2) {
				file_put_contents($tmpDir . '/chart.png', '');
			}

			return 0;
		});
		$this->locking->expects($this->once())->method('releaseLock')->with('soffice:headless:convert', ILockingProvider::LOCK_EXCLUSIVE);

		$result = $this->rasterizer()->rasterizeInlineSvg(html: $this->chart() . '|' . $this->chart(), format: 'docx');

		$marker = '<span>[chart error: the chart could not be converted for docx]</span>';
		$this->assertSame($marker . '|' . $marker, $result['html']);
		$this->assertSame(2, $calls);

	}//end testAMissingOrEmptyPngIsTheSameMarker()

	/**
	 * A busy converter is never released, and only the converted charts change.
	 *
	 * @return void
	 */
	public function testABusyConverterIsNotReleased(): void {
		$this->locking->method('acquireLock')->willThrowException(new LockedException('soffice:headless:convert'));
		$this->locking->expects($this->never())->method('releaseLock');

		$result = $this->rasterizer()->rasterizeInlineSvg(html: '<p>a</p>' . $this->chart(), format: 'odf');

		$this->assertSame('<p>a</p><span>[chart error: the chart could not be converted for odf]</span>', $result['html']);

	}//end testABusyConverterIsNotReleased()

	/**
	 * The configured binary and timeout are used; an empty binary is soffice, a timeout under one second is one.
	 *
	 * @return void
	 */
	public function testTheConfiguredBinaryAndTimeout(): void {
		$config = ['filinq.conversion.libreoffice_binary_path' => '/opt/lo/soffice', 'filinq.conversion.timeout_seconds' => '30'];
		$appConfig = $this->createMock(IAppConfig::class);
		$appConfig->method('getValueString')->willReturnCallback(
			static function (string $app, string $key, string $default) use (&$config): string {
				return $config[$key] ?? $default;
			}
		);
		$seen = [];
		$this->runner->method('run')->willReturnCallback(static function (array $argv, int $timeout, string $tmpDir, string $backend) use (&$seen): int {
			$seen[] = [$argv[0], $timeout, $backend, $argv[count($argv) - 2] === $tmpDir, end($argv) === $tmpDir . '/chart.svg', is_dir($tmpDir)];
			return 1;
		});
		$rasterizer = new SvgRasterizer($appConfig, $this->locking, new NullLogger(), $this->runner);

		$rasterizer->rasterizeInlineSvg(html: $this->chart(), format: 'odf');
		$config = ['filinq.conversion.libreoffice_binary_path' => '', 'filinq.conversion.timeout_seconds' => '0'];
		$rasterizer->rasterizeInlineSvg(html: $this->chart(), format: 'odf');

		$this->assertSame(
			[
				['/opt/lo/soffice', 30, 'svg-rasterizer', true, true, true],
				['soffice', 1, 'svg-rasterizer', true, true, true],
			],
			$seen
		);

	}//end testTheConfiguredBinaryAndTimeout()

	/**
	 * The work directory is gone afterwards, also when soffice fails.
	 *
	 * @return void
	 */
	public function testTheWorkDirectoryIsRemoved(): void {
		$dirs = [];
		$this->runner->method('run')->willReturnCallback(static function (array $argv, int $timeout, string $tmpDir) use (&$dirs): int {
			$dirs[] = $tmpDir;
			mkdir($tmpDir . '/profile/user', 0700, true);
			file_put_contents($tmpDir . '/profile/user/registry.xml', 'x');
			if (count($dirs) === 2) {
				throw new \OCA\Filinq\Exception\ConversionFailedException(message: 'crash', attempts: []);
			}

			file_put_contents($tmpDir . '/chart.png', base64_decode(self::PNG));
			return 0;
		});

		$result = $this->rasterizer()->rasterizeInlineSvg(html: $this->chart() . $this->chart(), format: 'odf');

		$this->assertCount(2, $dirs);
		$this->assertFalse(is_dir($dirs[0]));
		$this->assertFalse(is_dir($dirs[1]));
		$this->assertStringStartsWith('<img src="data:image/png;base64,', $result['html']);
		$this->assertSame(['chart error: the chart could not be converted for odf'], $result['warnings']);

	}//end testTheWorkDirectoryIsRemoved()
}
