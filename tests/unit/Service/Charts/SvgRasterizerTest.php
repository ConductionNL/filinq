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
}
