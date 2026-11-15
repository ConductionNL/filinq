<?php

/**
 * Unit tests for ChartPalette::resolve override and fallback paths.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Charts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Charts;

use OCA\Filinq\Service\Charts\ChartPalette;
use PHPUnit\Framework\TestCase;

/**
 * Explicit overrides, seed rejection and the fallback palette.
 *
 * @covers \OCA\Filinq\Service\Charts\ChartPalette
 */
class ChartPaletteResolveTest extends TestCase {

	/**
	 * Valid override colours are kept in order and invalid ones dropped.
	 *
	 * @return void
	 */
	public function testExplicitOverrideKeepsOnlyValidColours(): void {
		$palette = (new ChartPalette())->resolve(options: ['palette' => ['#ff0000', 'red', 12, '#00FF00', '#abc'], 'huisstijlPrimaryColor' => '#21468B']);

		$this->assertSame(['#ff0000', '#00FF00'], $palette);
	}//end testExplicitOverrideKeepsOnlyValidColours()

	/**
	 * An override without any valid colour falls through to the seed ramp.
	 *
	 * @return void
	 */
	public function testUnusableOverrideFallsThroughToSeed(): void {
		$resolver = new ChartPalette();

		$seeded = $resolver->resolve(options: ['palette' => ['nope'], 'huisstijlPrimaryColor' => '#21468B']);

		$this->assertCount(8, $seeded);
		$this->assertSame($resolver->resolve(options: ['huisstijlPrimaryColor' => '#21468B']), $seeded);
		$this->assertNotSame(ChartPalette::FALLBACK_PALETTE, $seeded);
	}//end testUnusableOverrideFallsThroughToSeed()

	/**
	 * Missing, malformed, non-string and near-white seeds use the fallback palette.
	 *
	 * @return array<string, array{0: array<string, mixed>}>
	 */
	public static function fallbackOptions(): array {
		return [
			'no options'           => [[]],
			'empty palette'        => [['palette' => []]],
			'palette not an array' => [['palette' => '#ff0000']],
			'short hex seed'       => [['huisstijlPrimaryColor' => '#fff']],
			'seed not a string'    => [['huisstijlPrimaryColor' => 0x21468B]],
			'near white seed'      => [['huisstijlPrimaryColor' => '#F0F0F0']],
		];
	}//end fallbackOptions()

	/**
	 * Each option set resolves to the fallback palette.
	 *
	 * @param array<string, mixed> $options Rendering options.
	 *
	 * @dataProvider fallbackOptions
	 *
	 * @return void
	 */
	public function testFallbackPalette(array $options): void {
		$this->assertSame(ChartPalette::FALLBACK_PALETTE, (new ChartPalette())->resolve(options: $options));
	}//end testFallbackPalette()
}//end class
