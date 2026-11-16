<?php

/**
 * Encodes a short text as a QR code module matrix, without a library.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Qr
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/signature-verification-portal/tasks.md#task-2.4
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Qr;

use InvalidArgumentException;

/**
 * A QR code encoder for byte-mode text at error correction level M,
 * versions 1 to 10 (up to 213 bytes, enough for a verification link).
 *
 * The bundled mPDF ships no QR encoder (that is the separate `mpdf/qrcode`
 * package), and the verification stamp must not add a dependency, so the
 * encoding follows ISO/IEC 18004 directly: data and Reed-Solomon codewords,
 * the function patterns, the data walk, and the mask with the lowest penalty.
 *
 * @spec openspec/changes/signature-verification-portal/tasks.md#task-2.4
 */
class QrCodeEncoder {

	/**
	 * Per version (index 1..10): total codewords, error-correction blocks and
	 * error-correction codewords per block, at level M.
	 *
	 * @var array<int, array{0: int, 1: int, 2: int}>
	 */
	private const VERSIONS = [
		1 => [26, 1, 10],
		2 => [44, 1, 16],
		3 => [70, 1, 26],
		4 => [100, 2, 18],
		5 => [134, 2, 24],
		6 => [172, 4, 16],
		7 => [196, 4, 18],
		8 => [242, 4, 22],
		9 => [292, 5, 22],
		10 => [346, 5, 26],
	];

	/**
	 * Alignment pattern centres per version.
	 *
	 * @var array<int, array<int, int>>
	 */
	private const ALIGNMENT = [
		1 => [],
		2 => [6, 18],
		3 => [6, 22],
		4 => [6, 26],
		5 => [6, 30],
		6 => [6, 34],
		7 => [6, 22, 38],
		8 => [6, 24, 42],
		9 => [6, 26, 46],
		10 => [6, 28, 50],
	];

	/**
	 * The format bits of error correction level M.
	 *
	 * @var int
	 */
	private const LEVEL_M = 0;

	/**
	 * GF(256) exponent table.
	 *
	 * @var array<int, int>
	 */
	private array $exp = [];

	/**
	 * GF(256) logarithm table.
	 *
	 * @var array<int, int>
	 */
	private array $log = [];

	/**
	 * Module colours of the matrix being built, true = dark, by [row][column].
	 *
	 * @var array<int, array<int, bool>>
	 */
	private array $modules = [];

	/**
	 * Whether a module belongs to a function pattern, by [row][column].
	 *
	 * @var array<int, array<int, bool>>
	 */
	private array $function = [];

	/**
	 * The side of the matrix being built.
	 *
	 * @var int
	 */
	private int $size = 0;

	/**
	 * Constructor: builds the GF(256) tables (primitive polynomial 0x11D).
	 *
	 * @return void
	 */
	public function __construct() {
		$value = 1;
		for ($i = 0; $i < 255; $i++) {
			$this->exp[$i] = $value;
			$this->log[$value] = $i;
			$value <<= 1;
			if ($value > 255) {
				$value ^= 0x11D;
			}
		}

		for ($i = 255; $i < 512; $i++) {
			$this->exp[$i] = $this->exp[$i - 255];
		}
	}//end __construct()

	/**
	 * Encode a text as a QR code.
	 *
	 * @param string $text The text, encoded as its bytes.
	 *
	 * @return array<int, array<int, bool>> The modules by [row][column], true for dark.
	 *
	 * @throws InvalidArgumentException When the text is empty or longer than version 10 holds.
	 *
	 * @spec openspec/changes/signature-verification-portal/tasks.md#task-2.4
	 */
	public function encode(string $text): array {
		if ($text === '') {
			throw new InvalidArgumentException('A QR code needs a text to encode');
		}

		$version = $this->versionFor(length: strlen($text));
		$codewords = $this->withErrorCorrection(data: $this->dataCodewords(text: $text, version: $version), version: $version);

		$this->size = (17 + (4 * $version));
		$this->modules = array_fill(0, $this->size, array_fill(0, $this->size, false));
		$this->function = $this->modules;
		$this->drawFunctionPatterns(version: $version);
		$this->placeData(codewords: $codewords);

		$best = null;
		$bestPenalty = PHP_INT_MAX;
		for ($mask = 0; $mask < 8; $mask++) {
			$candidate = $this->masked(mask: $mask);
			$penalty = self::penalty(matrix: $candidate);
			if ($penalty < $bestPenalty) {
				$best = $candidate;
				$bestPenalty = $penalty;
			}
		}

		return $best;
	}//end encode()

	/**
	 * The smallest version whose capacity holds this many bytes.
	 *
	 * @param int $length The byte length.
	 *
	 * @return int The version.
	 *
	 * @throws InvalidArgumentException When no supported version holds it.
	 */
	private function versionFor(int $length): int {
		foreach (self::VERSIONS as $version => [$total, $blocks, $ecPerBlock]) {
			$dataBits = (($total - ($blocks * $ecPerBlock)) * 8);
			if ((4 + self::countBits(version: $version) + (8 * $length)) <= $dataBits) {
				return $version;
			}
		}

		throw new InvalidArgumentException('The text is too long for a QR code of version 10 or lower');
	}//end versionFor()

	/**
	 * The number of bits of the character count in byte mode.
	 *
	 * @param int $version The version.
	 *
	 * @return int 8 below version 10, else 16.
	 */
	private static function countBits(int $version): int {
		if ($version < 10) {
			return 8;
		}

		return 16;
	}//end countBits()

	/**
	 * The data codewords: byte mode, the length, the bytes, terminator and padding.
	 *
	 * @param string $text    The text.
	 * @param int    $version The version.
	 *
	 * @return array<int, int> The data codewords.
	 */
	private function dataCodewords(string $text, int $version): array {
		[$total, $blocks, $ecPerBlock] = self::VERSIONS[$version];
		$capacity = (($total - ($blocks * $ecPerBlock)) * 8);

		$bits = '0100' . str_pad(decbin(strlen($text)), self::countBits(version: $version), '0', STR_PAD_LEFT);
		foreach (str_split($text) as $byte) {
			$bits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
		}

		$bits .= str_repeat('0', min(4, ($capacity - strlen($bits))));
		$bits .= str_repeat('0', ((8 - (strlen($bits) % 8)) % 8));

		$codewords = array_map('bindec', str_split($bits, 8));
		for ($pad = 0; count($codewords) < ($capacity / 8); $pad++) {
			$codewords[] = [0xEC, 0x11][($pad % 2)];
		}

		return array_map('intval', $codewords);
	}//end dataCodewords()

	/**
	 * Split the data into blocks, add each block's Reed-Solomon codewords, and interleave.
	 *
	 * @param array<int, int> $data    The data codewords.
	 * @param int             $version The version.
	 *
	 * @return array<int, int> The final codeword sequence.
	 */
	private function withErrorCorrection(array $data, int $version): array {
		[, $blockCount, $ecPerBlock] = self::VERSIONS[$version];
		$dataBlocks = $this->splitBlocks(data: $data, blockCount: $blockCount);
		$generator = $this->generator(degree: $ecPerBlock);
		$ecBlocks = [];
		foreach ($dataBlocks as $block) {
			$ecBlocks[] = $this->remainder(data: $block, generator: $generator);
		}

		return array_merge(
			self::interleave(blocks: $dataBlocks, width: (intdiv(count($data), $blockCount) + 1)),
			self::interleave(blocks: $ecBlocks, width: $ecPerBlock)
		);
	}//end withErrorCorrection()

	/**
	 * Split the data codewords into blocks; the last (length mod count) blocks are one longer.
	 *
	 * @param array<int, int> $data       The data codewords.
	 * @param int             $blockCount The number of blocks.
	 *
	 * @return array<int, array<int, int>> The blocks.
	 */
	private function splitBlocks(array $data, int $blockCount): array {
		$shortLength = intdiv(count($data), $blockCount);
		$longBlocks = (count($data) % $blockCount);
		$blocks = [];
		$offset = 0;
		for ($block = 0; $block < $blockCount; $block++) {
			$length = $shortLength + (int)($block >= ($blockCount - $longBlocks));
			$blocks[] = array_slice($data, $offset, $length);
			$offset += $length;
		}

		return $blocks;
	}//end splitBlocks()

	/**
	 * Interleave blocks column by column, skipping positions a shorter block lacks.
	 *
	 * @param array<int, array<int, int>> $blocks The blocks.
	 * @param int                         $width  The length of the longest block.
	 *
	 * @return array<int, int> The interleaved codewords.
	 */
	private static function interleave(array $blocks, int $width): array {
		$result = [];
		for ($i = 0; $i < $width; $i++) {
			foreach ($blocks as $block) {
				if (isset($block[$i]) === true) {
					$result[] = $block[$i];
				}
			}
		}

		return $result;
	}//end interleave()

	/**
	 * The Reed-Solomon generator polynomial of a degree, highest coefficient first.
	 *
	 * @param int $degree The number of error-correction codewords.
	 *
	 * @return array<int, int> The coefficients, without the leading 1.
	 */
	private function generator(int $degree): array {
		$poly = [1];
		for ($i = 0; $i < $degree; $i++) {
			$next = array_fill(0, (count($poly) + 1), 0);
			foreach ($poly as $j => $coefficient) {
				$next[$j] ^= $coefficient;
				$next[($j + 1)] ^= $this->multiply(left: $coefficient, right: $this->exp[$i]);
			}

			$poly = $next;
		}

		return array_slice($poly, 1);
	}//end generator()

	/**
	 * The remainder of the data polynomial divided by the generator.
	 *
	 * @param array<int, int> $data      The data codewords.
	 * @param array<int, int> $generator The generator, without its leading 1.
	 *
	 * @return array<int, int> The error-correction codewords.
	 */
	private function remainder(array $data, array $generator): array {
		$result = array_fill(0, count($generator), 0);
		foreach ($data as $codeword) {
			$factor = ($codeword ^ $result[0]);
			array_shift($result);
			$result[] = 0;
			foreach ($generator as $i => $coefficient) {
				$result[$i] ^= $this->multiply(left: $coefficient, right: $factor);
			}
		}

		return $result;
	}//end remainder()

	/**
	 * Multiply two GF(256) elements.
	 *
	 * @param int $left  An element.
	 * @param int $right An element.
	 *
	 * @return int The product.
	 */
	private function multiply(int $left, int $right): int {
		if ($left === 0 || $right === 0) {
			return 0;
		}

		return $this->exp[($this->log[$left] + $this->log[$right])];
	}//end multiply()

	/**
	 * Draw the finder, timing and alignment patterns, the dark module, and
	 * reserve the format and version areas.
	 *
	 * @param int $version The version.
	 *
	 * @return void
	 */
	private function drawFunctionPatterns(int $version): void {
		for ($i = 0; $i < $this->size; $i++) {
			$this->setFunction(row: 6, column: $i, dark: ($i % 2) === 0);
			$this->setFunction(row: $i, column: 6, dark: ($i % 2) === 0);
		}

		foreach ([[3, 3], [3, ($this->size - 4)], [($this->size - 4), 3]] as $centre) {
			$this->drawSquare(centre: $centre, radius: 4);
		}

		$centres = self::ALIGNMENT[$version];
		$last = (count($centres) - 1);
		foreach ($centres as $i => $row) {
			foreach ($centres as $j => $column) {
				if (in_array([$i, $j], [[0, 0], [0, $last], [$last, 0]], true) === false) {
					$this->drawSquare(centre: [$row, $column], radius: 2);
				}
			}
		}

		// Reserve the format areas now; the bits are written per mask.
		$this->reserveFormat();
		$this->drawVersion(version: $version);
	}//end drawFunctionPatterns()

	/**
	 * Draw a finder (radius 4, with its separator) or alignment (radius 2) pattern
	 * as concentric squares: light at distance 2 and 4 for a finder, at distance 1
	 * for an alignment pattern, dark elsewhere.
	 *
	 * @param array{0: int, 1: int} $centre The centre row and column.
	 * @param int                   $radius  4 for a finder, 2 for an alignment pattern.
	 *
	 * @return void
	 */
	private function drawSquare(array $centre, int $radius): void {
		$light = [1];
		if ($radius === 4) {
			$light = [2, 4];
		}

		for ($dy = -$radius; $dy <= $radius; $dy++) {
			for ($dx = -$radius; $dx <= $radius; $dx++) {
				$distance = max(abs($dx), abs($dy));
				$this->setFunction(row: ($centre[0] + $dy), column: ($centre[1] + $dx), dark: in_array($distance, $light, true) === false);
			}
		}
	}//end drawSquare()

	/**
	 * Set one function-pattern module, ignoring positions outside the matrix.
	 *
	 * @param int  $row    The row.
	 * @param int  $column The column.
	 * @param bool $dark   Whether it is dark.
	 *
	 * @return void
	 */
	private function setFunction(int $row, int $column, bool $dark): void {
		if ($row < 0 || $column < 0 || $row >= $this->size || $column >= $this->size) {
			return;
		}

		$this->modules[$row][$column] = $dark;
		$this->function[$row][$column] = true;
	}//end setFunction()

	/**
	 * Reserve both format areas and the dark module in the build matrix.
	 *
	 * @return void
	 */
	private function reserveFormat(): void {
		$bits = self::formatBits(mask: 0);
		foreach ($this->formatPositions() as [$row, $column, $bit]) {
			$this->setFunction(row: $row, column: $column, dark: ((($bits >> $bit) & 1) === 1));
		}

		$this->setFunction(row: ($this->size - 8), column: 8, dark: true);
	}//end reserveFormat()

	/**
	 * Write the 15 format bits (level M, a mask) into both format areas, and the dark module.
	 *
	 * @param array<int, array<int, bool>> $modules The matrix to write into.
	 * @param int                          $mask    The mask number.
	 *
	 * @return array<int, array<int, bool>> The matrix with the format written.
	 */
	private function drawFormat(array $modules, int $mask): array {
		$bits = self::formatBits(mask: $mask);
		foreach ($this->formatPositions() as [$row, $column, $bit]) {
			$modules[$row][$column] = ((($bits >> $bit) & 1) === 1);
		}

		$modules[($this->size - 8)][8] = true;

		return $modules;
	}//end drawFormat()

	/**
	 * The 15 format bits of level M and a mask, BCH-coded and XOR-masked.
	 *
	 * @param int $mask The mask number.
	 *
	 * @return int The format bits.
	 */
	private static function formatBits(int $mask): int {
		$data = ((self::LEVEL_M << 3) | $mask);
		$remainder = $data;
		for ($i = 0; $i < 10; $i++) {
			$remainder = (($remainder << 1) ^ (($remainder >> 9) * 0x537));
		}

		return ((($data << 10) | $remainder) ^ 0x5412);
	}//end formatBits()

	/**
	 * Every format module as [row, column, bit index], both copies.
	 *
	 * @return array<int, array{0: int, 1: int, 2: int}> The positions.
	 */
	private function formatPositions(): array {
		$positions = [];
		for ($i = 0; $i <= 5; $i++) {
			$positions[] = [$i, 8, $i];
		}

		$positions[] = [7, 8, 6];
		$positions[] = [8, 8, 7];
		$positions[] = [8, 7, 8];
		for ($i = 9; $i < 15; $i++) {
			$positions[] = [8, (14 - $i), $i];
		}

		for ($i = 0; $i < 8; $i++) {
			$positions[] = [8, ($this->size - 1 - $i), $i];
		}

		for ($i = 8; $i < 15; $i++) {
			$positions[] = [($this->size - 15 + $i), 8, $i];
		}

		return $positions;
	}//end formatPositions()

	/**
	 * Write the 18 version bits, for version 7 and up.
	 *
	 * @param int $version The version.
	 *
	 * @return void
	 */
	private function drawVersion(int $version): void {
		if ($version < 7) {
			return;
		}

		$remainder = $version;
		for ($i = 0; $i < 12; $i++) {
			$remainder = (($remainder << 1) ^ (($remainder >> 11) * 0x1F25));
		}

		$bits = (($version << 12) | $remainder);
		for ($i = 0; $i < 18; $i++) {
			$dark = ((($bits >> $i) & 1) === 1);
			$near = ($this->size - 11 + ($i % 3));
			$far = intdiv($i, 3);
			$this->setFunction(row: $far, column: $near, dark: $dark);
			$this->setFunction(row: $near, column: $far, dark: $dark);
		}
	}//end drawVersion()

	/**
	 * Walk the codeword bits into the non-function modules, two columns at a time, zigzagging.
	 *
	 * @param array<int, int> $codewords The codewords.
	 *
	 * @return void
	 */
	private function placeData(array $codewords): void {
		$bits = '';
		foreach ($codewords as $codeword) {
			$bits .= str_pad(decbin($codeword), 8, '0', STR_PAD_LEFT);
		}

		$index = 0;
		foreach ($this->dataPath() as [$row, $column]) {
			// Remainder bits past the codewords stay light.
			$this->modules[$row][$column] = (($bits[$index] ?? '0') === '1');
			$index++;
		}
	}//end placeData()

	/**
	 * The non-function modules in placement order: column pairs from the right,
	 * skipping the vertical timing column, alternately upward and downward.
	 *
	 * @return array<int, array{0: int, 1: int}> Row and column of each data module.
	 */
	private function dataPath(): array {
		$path = [];
		for ($right = ($this->size - 1); $right >= 1; $right -= 2) {
			if ($right === 6) {
				$right = 5;
			}

			$upward = ((($right + 1) & 2) === 0);
			for ($vertical = 0; $vertical < $this->size; $vertical++) {
				$row = $vertical;
				if ($upward === true) {
					$row = ($this->size - 1 - $vertical);
				}

				foreach ([$right, ($right - 1)] as $column) {
					if ($this->function[$row][$column] === false) {
						$path[] = [$row, $column];
					}
				}
			}
		}

		return $path;
	}//end dataPath()

	/**
	 * The matrix with a mask applied to the data modules and that mask's format bits written.
	 *
	 * @param int $mask The mask number, 0 to 7.
	 *
	 * @return array<int, array<int, bool>> The masked matrix.
	 */
	private function masked(int $mask): array {
		$modules = $this->modules;
		for ($row = 0; $row < $this->size; $row++) {
			for ($column = 0; $column < $this->size; $column++) {
				if ($this->function[$row][$column] === false && self::flips(mask: $mask, row: $row, column: $column) === true) {
					$modules[$row][$column] = !$modules[$row][$column];
				}
			}
		}

		return $this->drawFormat(modules: $modules, mask: $mask);
	}//end masked()

	/**
	 * Whether a mask flips a module.
	 *
	 * @param int $mask   The mask number.
	 * @param int $row    The row.
	 * @param int $column The column.
	 *
	 * @return bool True when the module is flipped.
	 */
	private static function flips(int $mask, int $row, int $column): bool {
		return match ($mask) {
			0 => (($row + $column) % 2) === 0,
			1 => ($row % 2) === 0,
			2 => ($column % 3) === 0,
			3 => (($row + $column) % 3) === 0,
			4 => ((intdiv($row, 2) + intdiv($column, 3)) % 2) === 0,
			5 => ((($row * $column) % 2) + (($row * $column) % 3)) === 0,
			6 => (((($row * $column) % 2) + (($row * $column) % 3)) % 2) === 0,
			default => (((($row + $column) % 2) + (($row * $column) % 3)) % 2) === 0,
		};
	}//end flips()

	/**
	 * The ISO 18004 penalty of a masked matrix: runs, 2x2 blocks, finder-like
	 * sequences and dark balance.
	 *
	 * @param array<int, array<int, bool>> $matrix The matrix.
	 *
	 * @return int The penalty, lower is better.
	 */
	private static function penalty(array $matrix): int {
		$size = count($matrix);
		$penalty = 0;
		foreach (self::matrixLines(matrix: $matrix) as $line) {
			$penalty += self::linePenalty(line: $line);
		}

		$dark = 0;
		for ($i = 0; $i < $size; $i++) {
			$dark += array_sum(array_map('intval', $matrix[$i]));
			for ($j = 0; ($j + 1) < $size && ($i + 1) < $size; $j++) {
				$penalty += (3 * (int)self::isUniformBlock(matrix: $matrix, origin: [$i, $j]));
			}
		}

		$penalty += (10 * intdiv(abs((int)(($dark * 100) / ($size * $size)) - 50), 5));

		return $penalty;
	}//end penalty()

	/**
	 * Every row and every column of a matrix as a string of 0 and 1.
	 *
	 * @param array<int, array<int, bool>> $matrix The matrix.
	 *
	 * @return array<int, string> The lines, row and column interleaved.
	 */
	private static function matrixLines(array $matrix): array {
		$size = count($matrix);
		$lines = [];
		for ($i = 0; $i < $size; $i++) {
			$row = '';
			$column = '';
			for ($j = 0; $j < $size; $j++) {
				$row .= (string)(int)$matrix[$i][$j];
				$column .= (string)(int)$matrix[$j][$i];
			}

			$lines[] = $row;
			$lines[] = $column;
		}

		return $lines;
	}//end matrixLines()

	/**
	 * The penalty of one line: runs of five or more, and finder-like sequences.
	 *
	 * @param string $line The line as 0 and 1.
	 *
	 * @return int The penalty.
	 */
	private static function linePenalty(string $line): int {
		$penalty = 0;
		preg_match_all('/0{5,}|1{5,}/', $line, $runs);
		foreach ($runs[0] as $run) {
			$penalty += (3 + strlen($run) - 5);
		}

		return $penalty + (40 * (substr_count($line, '10111010000') + substr_count($line, '00001011101')));
	}//end linePenalty()

	/**
	 * Whether the 2x2 block at an origin is one colour.
	 *
	 * @param array<int, array<int, bool>> $matrix The matrix.
	 * @param array{0: int, 1: int}        $origin The top-left row and column.
	 *
	 * @return bool True when all four modules match.
	 */
	private static function isUniformBlock(array $matrix, array $origin): bool {
		[$i, $j] = $origin;
		$colour = $matrix[$i][$j];

		return $matrix[$i][($j + 1)] === $colour && $matrix[($i + 1)][$j] === $colour && $matrix[($i + 1)][($j + 1)] === $colour;
	}//end isUniformBlock()
}//end class
