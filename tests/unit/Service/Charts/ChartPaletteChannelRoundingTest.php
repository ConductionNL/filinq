<?php

/**
 * Channel rounding in the chart palette: the same bytes on every PHP version.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service\Charts
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Charts;

use OCA\Filinq\Service\Charts\ChartPalette;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * The noisy inputs have products with 255 one float step below a half, where
 * PHP 8.3's round() and PHP 8.4's round() disagree. The expected bytes are the
 * PHP 8.3 results.
 *
 * @covers \OCA\Filinq\Service\Charts\ChartPalette
 */
final class ChartPaletteChannelRoundingTest extends TestCase {

	/**
	 * The channels.
	 *
	 * @return array<string, array{0: float, 1: int}> The cases.
	 */
	public static function channels(): array {
		return [
			'zero' => [0.0, 0],
			'one' => [1.0, 255],
			'an exact half rounds up' => [83.5 / 255, 84],
			'below a half rounds down' => [83.4 / 255, 83],
			'above a half rounds up' => [83.6 / 255, 84],
			'float noise below 83.5' => [0.32745098039215681, 84],
			'float noise below 101.5' => [0.39803921568627443, 102],
		];

	}//end channels()

	/**
	 * A normalised value becomes the expected channel.
	 *
	 * @param float $unit     The value in [0, 1].
	 * @param int   $expected The channel.
	 *
	 * @return void
	 */
	#[DataProvider('channels')]
	public function testChannel(float $unit, int $expected): void {
		$toByte = new ReflectionMethod(ChartPalette::class, 'toByte');

		self::assertSame($expected, $toByte->invoke(new ChartPalette(), $unit));

	}//end testChannel()
}//end class
