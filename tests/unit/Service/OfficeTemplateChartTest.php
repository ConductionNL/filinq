<?php

/**
 * Tests for OfficeChartFiller (office-charts-and-images REQ-DDTCH-003).
 *
 * @category Test
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\Charts\ChartDataNormalizer;
use OCA\Filinq\Service\Charts\ChartRenderError;
use OCA\Filinq\Service\Office\OfficeChartFiller;
use PhpOffice\PhpWord\Element\Chart;
use PhpOffice\PhpWord\TemplateProcessor;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ZipArchive;

/**
 * A `${chart:key}` placeholder becomes a native Word chart part, and a bad
 * context becomes a visible marker plus a warning.
 *
 * @covers \OCA\Filinq\Service\Office\OfficeChartFiller
 * @uses   \OCA\Filinq\Service\Charts\ChartDataNormalizer
 * @uses   \OCA\Filinq\Service\Charts\ChartRenderError
 */
class OfficeTemplateChartTest extends TestCase {

	private const FIXTURE = __DIR__ . '/../../sample-documents/chart-template.docx';

	/**
	 * Temp files written by a test.
	 *
	 * @var string[]
	 */
	private array $tempFiles = [];

	/**
	 * Remove the files a test wrote.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		foreach ($this->tempFiles as $file) {
			@unlink($file);
		}

		$this->tempFiles = [];
	}

	/**
	 * The shipped PhpWord has setChart() taking a Chart element, for every type we map to.
	 *
	 * @return void
	 */
	public function testSetChartCapabilityPinned(): void {
		$this->assertTrue(method_exists(TemplateProcessor::class, 'setChart'));

		$method = new ReflectionMethod(TemplateProcessor::class, 'setChart');
		$params = $method->getParameters();
		$this->assertCount(2, $params);
		$this->assertTrue($params[1]->getType()?->allowsNull() === false);
		$this->assertTrue(is_a(Chart::class, (string)$params[1]->getType(), true));

		foreach (['column', 'bar', 'line', 'pie'] as $type) {
			$chart = new Chart($type, ['a', 'b'], [1, 2]);
			$this->assertSame($type, $chart->getType());

			$processor = new TemplateProcessor(self::FIXTURE);
			$processor->setChart('chart:bezwaren', $chart);
			$parts = $this->chartParts(parts: $this->savedParts(processor: $processor));
			$this->assertCount(1, $parts, 'one chart part for type ' . $type);
		}
	}

	/**
	 * A valid context fills the fixture with a native chart part holding the data.
	 *
	 * @return void
	 */
	public function testValidContextWritesNativeChartPart(): void {
		$processor = new TemplateProcessor(self::FIXTURE);
		$warnings  = $this->filler()->fill(
			processor: $processor,
			charts: [
				'bezwaren' => [
					'type'    => 'bar',
					'data'    => [
						'labels' => ['Q1', 'Q2', 'Q3'],
						'series' => [['name' => 'Bezwaren', 'values' => [4, 7, 2]]],
					],
					'options' => ['title' => 'Bezwaren per kwartaal'],
				],
			]
		);

		$this->assertSame([], $warnings);
		$this->assertNotContains('chart:bezwaren', $processor->getVariables());

		$parts = $this->chartParts(parts: $this->savedParts(processor: $processor));
		$this->assertCount(1, $parts);
		$chartXml = array_values($parts)[0];
		$this->assertMatchesRegularExpression('#^word/charts/chart\d+\.xml$#', array_keys($parts)[0]);
		$this->assertStringContainsString('<c:barChart>', $chartXml);
		$this->assertStringContainsString('<c:barDir val="col"/>', $chartXml);
		$this->assertStringContainsString('Q2', $chartXml);
		$this->assertStringContainsString('Bezwaren per kwartaal', $chartXml);
	}

	/**
	 * Horizontal orientation is a Word bar chart, pie keeps only the first series.
	 *
	 * @return void
	 */
	public function testBuildMapsOrientationAndPie(): void {
		$data = [
			'labels' => ['a', 'b'],
			'series' => [['name' => 'one', 'values' => [1, null]], ['name' => 'two', 'values' => [3, 4]]],
		];

		$horizontal = $this->filler()->build(type: 'bar', data: $data, options: ['orientation' => 'horizontal']);
		$this->assertInstanceOf(Chart::class, $horizontal);
		$this->assertSame('bar', $horizontal->getType());
		$this->assertCount(2, $horizontal->getSeries());
		$this->assertSame([1.0, 0.0], $horizontal->getSeries()[0]['values']);
		$this->assertTrue($horizontal->getStyle()->isShowLegend());

		$pie = $this->filler()->build(type: 'pie', data: $data);
		$this->assertInstanceOf(Chart::class, $pie);
		$this->assertSame('pie', $pie->getType());
		$this->assertCount(1, $pie->getSeries());
		$this->assertSame('one', $pie->getSeries()[0]['name']);

		$line = $this->filler()->build(type: 'line', data: ['labels' => ['a'], 'series' => [['values' => [1]]]]);
		$this->assertInstanceOf(Chart::class, $line);
		$this->assertFalse($line->getStyle()->isShowLegend());
	}

	/**
	 * An unsupported type is an error naming the type.
	 *
	 * @return void
	 */
	public function testUnsupportedTypeIsAnError(): void {
		$result = $this->filler()->build(type: 'radar', data: ['labels' => ['a'], 'series' => [['values' => [1]]]]);

		$this->assertInstanceOf(ChartRenderError::class, $result);
		$this->assertSame('chart error: unsupported chart type "%s"', $result->message);
		$this->assertSame(['radar'], $result->parameters);
	}

	/**
	 * A missing or malformed context leaves the marker text and a warning, no chart part.
	 *
	 * @return void
	 */
	public function testBadContextLeavesMarkerAndWarning(): void {
		$processor = new TemplateProcessor(self::FIXTURE);
		$warnings  = $this->filler()->fill(
			processor: $processor,
			charts: ['bezwaren' => ['type' => 'bar', 'data' => ['labels' => ['Q1']]]]
		);

		$this->assertSame(['chart error: data must include "labels" and "series"'], $warnings);
		$saved = $this->savedParts(processor: $processor);
		$this->assertSame([], $this->chartParts(parts: $saved));
		$this->assertStringContainsString(
			'[chart error: data must include "labels" and "series"]',
			$saved['word/document.xml']
		);

		$missing = new TemplateProcessor(self::FIXTURE);
		$this->assertSame(['chart error: no data'], $this->filler()->fill(processor: $missing, charts: []));
	}

	/**
	 * Only `${chart:…}` placeholders are touched; other variables stay for the value pass.
	 *
	 * @return void
	 */
	public function testOtherPlaceholdersAreLeftAlone(): void {
		$processor = new TemplateProcessor(self::FIXTURE);
		$this->filler()->fill(processor: $processor, charts: []);

		$variables = $processor->getVariables();
		$this->assertContains('image:logo', $variables);
		$this->assertContains('zaak.nummer', $variables);
		$this->assertContains('block_besluiten', $variables);
	}

	/**
	 * Build the filler under test with the real normalizer.
	 *
	 * @return OfficeChartFiller
	 */
	private function filler(): OfficeChartFiller {
		return new OfficeChartFiller(normalizer: new ChartDataNormalizer());
	}

	/**
	 * Save the processor (a TemplateProcessor saves once) and return every part, keyed by zip path.
	 *
	 * @param TemplateProcessor $processor The filled template.
	 *
	 * @return array<string, string>
	 */
	private function savedParts(TemplateProcessor $processor): array {
		$path = (string)tempnam(sys_get_temp_dir(), 'filinq-chart');
		$this->tempFiles[] = $path;
		$processor->saveAs($path);

		$zip = new ZipArchive();
		$this->assertTrue($zip->open($path));
		$parts = [];
		for ($i = 0; $i < $zip->numFiles; $i++) {
			$name = (string)$zip->getNameIndex($i);
			$parts[$name] = (string)$zip->getFromName($name);
		}

		$zip->close();
		return $parts;
	}

	/**
	 * The chart parts of a saved document.
	 *
	 * @param array<string, string> $parts The saved parts.
	 *
	 * @return array<string, string>
	 */
	private function chartParts(array $parts): array {
		return array_filter($parts, static fn (string $name): bool => str_starts_with($name, 'word/charts/'), ARRAY_FILTER_USE_KEY);
	}
}
