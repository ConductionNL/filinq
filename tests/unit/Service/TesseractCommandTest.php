<?php

/**
 * The tesseract command line as OcrService uses it.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/ocr-document-scanning/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\TesseractCommand;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * The hOCR confidence mean, and answers that hold with or without tesseract installed.
 *
 * @covers \OCA\Filinq\Service\TesseractCommand
 */
class TesseractCommandTest extends TestCase {

	/**
	 * hOCR fragments and their mean word confidence.
	 *
	 * @return array<string, array{0: string, 1: float}>
	 */
	public static function hocr(): array {
		$word = static fn (int $conf): string => "<span class='ocrx_word' title='bbox 1 2 3 4; x_wconf " . $conf . "'>w</span>";
		return [
			'no words' => ['<div class="ocr_page"></div>', 0.0],
			'empty output' => ['', 0.0],
			'one word' => [$word(87), 87.0],
			'a mean rounded to one decimal' => [$word(90) . $word(85) . $word(80), 85.0],
			'a mean that needs rounding' => [$word(90) . $word(91) . $word(91), 90.7],
			'a zero confidence counts' => [$word(0) . $word(100), 50.0],
			'several spaces before the number' => ['x_wconf    42', 42.0],
			'a line break before the number' => ["x_wconf\n64", 64.0],
			'no space is no match' => ['x_wconf42', 0.0],
			'a negative sign is no match' => ['x_wconf -5', 0.0],
			'words on separate lines' => [$word(70) . "\n" . $word(71), 70.5],
		];
	}//end hocr()

	/**
	 * The mean of the x_wconf values.
	 *
	 * @param string $content The hOCR.
	 * @param float $expected The mean.
	 *
	 * @dataProvider hocr
	 *
	 * @return void
	 */
	public function testAverageConfidence(string $content, float $expected): void {
		$this->assertSame($expected, (new TesseractCommand(logger: new NullLogger()))->averageConfidence(hocrContent: $content));
	}//end testAverageConfidence()

	/**
	 * Recognising a file that does not exist reports no confidence.
	 *
	 * @return void
	 */
	public function testAMissingFileHasNoConfidence(): void {
		$command = new TesseractCommand(logger: new NullLogger());
		$this->assertSame(0.0, $command->confidence(filePath: '/nonexistent/' . uniqid() . '.png', languages: 'nld+eng', dpi: 300));
	}//end testAMissingFileHasNoConfidence()

	/**
	 * A version is reported exactly when the binary is available.
	 *
	 * @return void
	 */
	public function testAVersionExactlyWhenAvailable(): void {
		$command = new TesseractCommand(logger: new NullLogger());
		$version = $command->version();
		$this->assertSame($command->isAvailable(), $version !== null);
		if ($version !== null) {
			$this->assertSame(trim($version), $version);
			$this->assertNotSame('', $version);
		}
	}//end testAVersionExactlyWhenAvailable()
}//end class
