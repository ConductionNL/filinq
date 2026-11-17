<?php

/**
 * Unit tests for QrCodeEncoder.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Qr
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/signature-verification-portal/tasks.md#task-2.4
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Qr;

use InvalidArgumentException;
use OCA\Filinq\Service\Qr\QrCodeEncoder;
use PHPUnit\Framework\TestCase;

/**
 * The encoder writes QR codes a reader decodes back to the text.
 *
 * The two expected matrices were decoded with an independent QR reader
 * (zxing-cpp) to exactly their texts, at error correction level M, when they
 * were recorded; version 8 also carries the version information blocks.
 *
 * @covers \OCA\Filinq\Service\Qr\QrCodeEncoder
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Qr
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class QrCodeEncoderTest extends TestCase {

	/**
	 * A verification link, version 5.
	 *
	 * @var array<int, string>
	 */
	private const LINK = [
			'#######.....#.#####...####..#.#######',
			'#.....#......##.#.#...#.#..#..#.....#',
			'#.###.#.####.##.#..#####...##.#.###.#',
			'#.###.#.##.#...###....#.#####.#.###.#',
			'#.###.#.#....###.#####.##...#.#.###.#',
			'#.....#.#...####.#....#.#.###.#.....#',
			'#######.#.#.#.#.#.#.#.#.#.#.#.#######',
			'........######.####.###...###........',
			'#.#####...#..####..#...#.###..#####..',
			'.###.....#..##.#.########...##...#.#.',
			'.####.#.#..#...###..##.....##.#.##.##',
			'##..........#.##..#.###...#..#.#.#..#',
			'....####.###.#.#.#....#.####.##.#.###',
			'#.#.....#.#...#.#..##.##....#..#...#.',
			'..#.######...####.#.##..####.#####..#',
			'..#....#########.....#.#....##.##..#.',
			'#..#..#..#........##...#.#....###.##.',
			'###..#.#..#..#.##..########.##...#.#.',
			'########.#..#.....#..#..#.##.#.#...##',
			'..#..#..##.###..#...##....#...##.#..#',
			'##.####.#.#.#.#..##...#.####.##.#.#..',
			'.##....####..#..#####.##..#......#...',
			'#..####.....##.#.#...#..#..#...###..#',
			'.##..#.#.###..#..#######...##.###..##',
			'#.##.###...######.....##.#.#..###.#..',
			'###..#.#...##..#.#####.##.#.##...#...',
			'#.###.####.##..###..##.....###...####',
			'#...##.........#...#.#.##..#.#.#.#..#',
			'#..##.#####....###....#.#########.##.',
			'........##...##.#..######..##...##.#.',
			'#######..##.##.##.#.##......#.#.#.###',
			'#.....#.###.#.###..####...#.#...##...',
			'#.###.#.#.....##..##..#..#.######.###',
			'#.###.#.##.######..###.####..##.##..#',
			'#.###.#.#.#.#....#......#.#..#....###',
			'#.....#..####...#.##.####..##...##..#',
			'#######.#........#......###..##.#.###',
	];

	/**
	 * A 130-byte text, version 8.
	 *
	 * @var array<int, string>
	 */
	private const LONG = [
			'#######.####.#####.##.#..##.###.####.#..#.#######',
			'#.....#.#..#.##..#.##.##..#..####...#####.#.....#',
			'#.###.#.#.#.####.....#.###.#.#..##.#.#.##.#.###.#',
			'#.###.#......#.#..###...#...#..##.####.#..#.###.#',
			'#.###.#.##..#....#..#######.#.#.#...##....#.###.#',
			'#.....#...##..####.#..#...##.#..#...#.#...#.....#',
			'#######.#.#.#.#.#.#.#.#.#.#.#.#.#.#.#.#.#.#######',
			'.........###.##.##.####...###.#..##..#.##........',
			'#..#######.######.#########.#....#..##.#.#..#.###',
			'#.#....#.#.##.#############.#############.#.###..',
			'...#.##.#####.#..####..##..###.####.#..###.#.#.##',
			'........#.#.####..#..##..#.#..#.#.#..##.##..#.#..',
			'##....###.####.#..####..###.######..##.#.#..##...',
			'.##.....#.#.#...###..###..##.##.##.##.#...#.####.',
			'##..#.#.####.#....####.#..#.##...#.####.#..#.####',
			'.#...#.####.#.####.#.#.#.#..##..##.....#####.##.#',
			'.#.#..#...#....##....#.###..##.#.##.###.#.######.',
			'..#.##....#####..#.#.....#......##..##.##.######.',
			'..#..#####.###...#########...##..#####..##...##.#',
			'..##...####.#..##..#.#......##.#.......#.#...#..#',
			'..#.###..###.#.#..###...#####.....###.##.###....#',
			'.###.#...#..#....#.###.###...###.##..#######.#...',
			'...#########..#..##.#.########.#.##.##..#####.#.#',
			'#.#.#...#....##########...#..#..##.#...##...###.#',
			'##.##.#.##.###....#..##.#.#.#.#.##..#.###.#.##..#',
			'.##.#...#.#.##.##...#.#...######.#..#.#.#...###..',
			'.##.#####.#.#....###..#####......#.#.##.######..#',
			'#..#.#.#####...##.#.#.#...#..##......#.#.#..###..',
			'##.####..#.##.##.#.######....####...#..##....###.',
			'####...###.########...#..#....#..###.#.....#.###.',
			'#.##..#.#..##..###..#.##..#.#....#...#.##.##..#.#',
			'..####.#.####...##.##..#.#.#..#.#.....####.......',
			'..###.#.##.#...###..###....#####..########.###..#',
			'#......##..#..###..##..##.###.###.##.##..#.#####.',
			'#.#.###.#.###..#######.#####..#.#....#...##.##..#',
			'#......#...#.....#.#.....###....##...#.#.#..###.#',
			'#.#####.##..#..##..#.#...########.#.#.###.##.....',
			'####...##..#.##..##.#..##..##.#.##.#..###.#.#..#.',
			'.#...#############..#...#..##..##..#.##.#.##...##',
			'.###...###.####..#..##..#....###.#...#.#...#.###.',
			'###...#..#..#..#...#..######.#.###..###########..',
			'........##.###..#.##.##...###.###.####.##...###..',
			'#######.#..#.#.#..###.#.#.##.....#.##...#.#.#.###',
			'#.....#.#.#.####.##.#.#...###.##.#.....##...##.#.',
			'#.###.#.#.##.#...###..#####.#.#..#..##..#####...#',
			'#.###.#.#.#.#.#.#.##...#..##.##.###.#.#...##.#..#',
			'#.###.#..##.....##....#.###.#...#.#.#....#######.',
			'#.....#...#......#.##..#....#....#.#..#...#..####',
			'#######.#.#...####.#..#.#.#..#...#.##.#.##.###..#',
	];

	/**
	 * A matrix as rows of '#' (dark) and '.' (light).
	 *
	 * @param array<int, array<int, bool>> $matrix The modules.
	 *
	 * @return array<int, string>
	 */
	private static function rows(array $matrix): array {
		return array_map(
			static fn (array $row): string => implode('', array_map(static fn (bool $dark): string => ($dark === true) ? '#' : '.', $row)),
			$matrix
		);
	}//end rows()

	/**
	 * A verification link encodes to the recorded, reader-checked matrix.
	 *
	 * @return void
	 */
	public function testAVerificationLinkEncodesToTheReaderCheckedMatrix(): void {
		$matrix = (new QrCodeEncoder())->encode(text: 'https://cloud.example.nl/apps/filinq/verify/0123456789abcdef0123456789abcdef');

		$this->assertSame(self::LINK, self::rows(matrix: $matrix));

	}//end testAVerificationLinkEncodesToTheReaderCheckedMatrix()

	/**
	 * A longer text takes version 8, with its version blocks and alignment grid.
	 *
	 * @return void
	 */
	public function testALongerTextEncodesToTheReaderCheckedMatrix(): void {
		$text = str_repeat('https://cloud.example.nl/verify/', 4) . 'ab';

		$matrix = (new QrCodeEncoder())->encode(text: $text);

		$this->assertSame(self::LONG, self::rows(matrix: $matrix));

	}//end testALongerTextEncodesToTheReaderCheckedMatrix()

	/**
	 * The smallest version that holds the text is used: 21 modules for 14 bytes, 25 for 15, 57 for 213.
	 *
	 * @return void
	 */
	public function testTheSmallestVersionHoldingTheTextIsUsed(): void {
		$encoder = new QrCodeEncoder();

		$this->assertCount(21, $encoder->encode(text: str_repeat('a', 14)));
		$this->assertCount(25, $encoder->encode(text: str_repeat('a', 15)));
		$this->assertCount(57, $encoder->encode(text: str_repeat('a', 213)));

	}//end testTheSmallestVersionHoldingTheTextIsUsed()

	/**
	 * Every code has the three finder patterns, the timing lines and the dark module.
	 *
	 * @return void
	 */
	public function testEveryCodeCarriesTheFunctionPatterns(): void {
		$rows = self::rows(matrix: (new QrCodeEncoder())->encode(text: 'filinq'));
		$size = count($rows);

		$finder = ['#######', '#.....#', '#.###.#', '#.###.#', '#.###.#', '#.....#', '#######'];
		foreach ($finder as $i => $line) {
			$this->assertSame($line, substr($rows[$i], 0, 7));
			$this->assertSame($line, substr($rows[$i], ($size - 7), 7));
			$this->assertSame($line, substr($rows[($size - 7 + $i)], 0, 7));
		}

		for ($i = 8; $i < ($size - 8); $i++) {
			$expected = (($i % 2) === 0) ? '#' : '.';
			$this->assertSame($expected, $rows[6][$i]);
			$this->assertSame($expected, $rows[$i][6]);
		}

		$this->assertSame('#', $rows[($size - 8)][8]);

	}//end testEveryCodeCarriesTheFunctionPatterns()

	/**
	 * The format bits name level M and are a valid BCH codeword.
	 *
	 * @return void
	 */
	public function testTheFormatBitsNameLevelM(): void {
		$rows = self::rows(matrix: (new QrCodeEncoder())->encode(text: 'filinq'));
		$size = count($rows);

		// The second copy: row 8 from the right edge (bits 0-7), column 8 from the bottom (bits 8-14).
		$bits = 0;
		for ($i = 0; $i < 8; $i++) {
			$bits |= (($rows[8][($size - 1 - $i)] === '#') ? 1 : 0) << $i;
		}

		for ($i = 8; $i < 15; $i++) {
			$bits |= (($rows[($size - 15 + $i)][8] === '#') ? 1 : 0) << $i;
		}

		$bits ^= 0x5412;
		$remainder = $bits;
		for ($i = 14; $i >= 10; $i--) {
			if ((($remainder >> $i) & 1) === 1) {
				$remainder ^= (0x537 << ($i - 10));
			}
		}

		$this->assertSame(0, $remainder, 'the format bits are a BCH(15,5) codeword');
		$this->assertSame(0, ($bits >> 13), 'level M is 00');

	}//end testTheFormatBitsNameLevelM()

	/**
	 * An empty text and a text longer than version 10 holds are refused.
	 *
	 * @return void
	 */
	public function testAnEmptyOrOversizedTextIsRefused(): void {
		$encoder = new QrCodeEncoder();

		try {
			$encoder->encode(text: '');
			$this->fail('An empty text was encoded');
		} catch (InvalidArgumentException $e) {
			$this->assertSame('A QR code needs a text to encode', $e->getMessage());
		}

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('The text is too long for a QR code of version 10 or lower');
		$encoder->encode(text: str_repeat('a', 214));

	}//end testAnEmptyOrOversizedTextIsRefused()
}//end class
