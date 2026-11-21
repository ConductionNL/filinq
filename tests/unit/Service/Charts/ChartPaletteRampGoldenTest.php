<?php

/**
 * Golden test for the colour ramp a huisstijl seed colour produces.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service\Charts
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/template-charts/spec.md#REQ-DDTCH-001
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Charts;

use OCA\Filinq\Service\Charts\ChartPalette;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Seeds whose largest channel is red, green or blue, a seed whose hue comes
 * out negative before it is wrapped, a grey and a near-white seed. The golden
 * pins the eight colours of each ramp. RECORD_GOLDEN=1 rewrites the golden file.
 *
 * @covers \OCA\Filinq\Service\Charts\ChartPalette
 */
class ChartPaletteRampGoldenTest extends TestCase {

	/**
	 * The seeds.
	 *
	 * @return array<string, array{0: string}> The cases.
	 */
	public static function seeds(): array {
		return [
			'red largest' => ['#C0562F'],
			'red largest, blue over green' => ['#B02060'],
			'green largest' => ['#4B8F29'],
			'blue largest' => ['#21468B'],
			'blue largest, red over green' => ['#6A2BA0'],
			'grey' => ['#666666'],
			'near white' => ['#F4F4F4'],
		];

	}//end seeds()

	/**
	 * The ramp matches the golden.
	 *
	 * @param string $seed The seed colour.
	 *
	 * @return void
	 */
	#[DataProvider('seeds')]
	public function testRamp(string $seed): void {
		$ramp = (new ChartPalette())->resolve(options: ['huisstijlPrimaryColor' => $seed]);

		$path   = __DIR__.'/golden/palette-ramps.json';
		$golden = [];
		if (is_file($path) === true) {
			$golden = json_decode((string) file_get_contents($path), true);
		}

		if (getenv('RECORD_GOLDEN') === '1') {
			$golden[$seed] = $ramp;
			ksort($golden);
			file_put_contents($path, json_encode($golden, JSON_PRETTY_PRINT)."\n");
		}

		self::assertSame($golden[$seed] ?? null, $ramp);

	}//end testRamp()
}//end class
