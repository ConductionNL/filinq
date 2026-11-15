<?php

/**
 * Unit tests for ChartSvgRenderer's placeholder fallback
 *
 * Pins the placeholder's size, its message with and without a translator,
 * and the warning it leaves, before render() was split into steps.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Charts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/specs/template-charts/spec.md#REQ-DDTCH-001
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Charts;

use OCA\Filinq\Service\Charts\ChartSvgRenderer;
use OCP\IL10N;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the placeholder a chart falls back to.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Charts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class ChartPlaceholderTest extends TestCase {

	/**
	 * A translator that marks what it translated.
	 *
	 * @return IL10N The translator.
	 */
	private function l10n(): IL10N {
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnCallback(
			static fn (string $text, array $parameters = []): string => '[t] ' . vsprintf($text, $parameters)
		);
		return $l10n;

	}//end l10n()

	/**
	 * Without a translator, the parameters are filled in and the size comes from the options.
	 *
	 * @return void
	 */
	public function testUntranslatedPlaceholderUsesTheOptionsSize(): void {
		$renderer = new ChartSvgRenderer();
		$svg = $renderer->render(type: 'radar', data: [], options: ['width' => '320', 'height' => 0]);

		$this->assertSame('chart error: unsupported chart type "radar"', $renderer->getLastWarning());
		$this->assertStringContainsString('width="320"', $svg);
		$this->assertStringContainsString('height="1"', $svg);
		$this->assertStringContainsString('unsupported chart type', $svg);
		$this->assertStringEndsWith('</svg>', $svg);

	}//end testUntranslatedPlaceholderUsesTheOptionsSize()

	/**
	 * With a translator, every placeholder message is translated, parameters or not.
	 *
	 * @return void
	 */
	public function testTranslatedPlaceholders(): void {
		$renderer = new ChartSvgRenderer(l10n: $this->l10n());

		$unsupported = $renderer->render(type: 'radar', data: []);
		$this->assertSame('[t] chart error: unsupported chart type "radar"', $renderer->getLastWarning());
		$this->assertStringContainsString('width="600"', $unsupported);
		$this->assertStringContainsString('height="300"', $unsupported);

		$renderer->render(type: 'bar', data: []);
		$this->assertSame('[t] chart error: data must include "labels" and "series"', $renderer->getLastWarning());

	}//end testTranslatedPlaceholders()

	/**
	 * A real chart after a placeholder clears the warning.
	 *
	 * @return void
	 */
	public function testARealChartClearsTheWarning(): void {
		$renderer = new ChartSvgRenderer();
		$renderer->render(type: 'radar', data: []);
		$svg = $renderer->render(
			type: 'bar',
			data: ['labels' => ['A'], 'series' => [['name' => 'S', 'values' => [1]]]],
			options: ['width' => 'wide']
		);

		$this->assertNull($renderer->getLastWarning());
		$this->assertStringContainsString('width="600"', $svg);

	}//end testARealChartClearsTheWarning()
}//end class
