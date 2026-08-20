<?php

/**
 * Chart Palette
 *
 * Resolves the ordered hex color list a chart is drawn with: an explicit
 * palette override, a deterministic hue-rotated ramp seeded from the huisstijl
 * primary color, or the fixed accessible fallback palette.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service\Charts
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/changes/template-charts/specs/template-charts/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service\Charts;

/**
 * Resolves and derives chart color palettes.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service\Charts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/changes/template-charts/tasks.md#task-1.1
 */
class ChartPalette {

	/**
	 * Fixed accessible fallback palette (used when no huisstijl seed color is
	 * available, or the seed color does not yield sufficient contrast).
	 * Chosen for pairwise distinguishability on a white background.
	 *
	 * @var string[]
	 */
	public const FALLBACK_PALETTE = [
		'#21468B',
		'#DE6E4B',
		'#3FA796',
		'#F2A93B',
		'#7A5FB5',
		'#5B8DEF',
		'#C0562F',
		'#4B8F29',
	];

	/**
	 * Resolve the color palette for a chart, honouring an explicit override.
	 *
	 * @param array $options Rendering options (may include 'palette' and/or
	 *                       'huisstijlPrimaryColor').
	 *
	 * @return string[] Ordered list of hex colors.
	 *
	 * @spec openspec/changes/template-charts/specs/template-charts/spec.md#REQ-DDTCH-003
	 */
	public function resolve(array $options): array {
		$override = $this->explicitOverride(options: $options);
		if ($override !== []) {
			return $override;
		}

		$seed = $options['huisstijlPrimaryColor'] ?? null;
		if (is_string($seed) === true && $this->isValidHexColor(color: $seed) === true) {
			$ramp = $this->buildRampFromSeed(seed: $seed);
			if ($ramp !== null) {
				return $ramp;
			}
		}

		return self::FALLBACK_PALETTE;
	}//end resolve()

	/**
	 * Collect the valid hex colors from an explicit `palette` option.
	 *
	 * @param array $options Rendering options.
	 *
	 * @return string[] The valid override colors, empty when there is no
	 *                  usable override.
	 */
	private function explicitOverride(array $options): array {
		$palette = ($options['palette'] ?? null);
		if (is_array($palette) === false || $palette === []) {
			return [];
		}

		$override = [];
		foreach ($palette as $color) {
			if (is_string($color) === true && $this->isValidHexColor(color: $color) === true) {
				$override[] = $color;
			}
		}

		return $override;
	}//end explicitOverride()

	/**
	 * Build a deterministic multi-color ramp seeded from a huisstijl primary
	 * color by rotating hue in fixed steps. Rejects seed colors that would
	 * not provide sufficient contrast against a white background.
	 *
	 * @param string $seed Hex color, e.g. '#21468B'.
	 *
	 * @return string[]|null Ramp of 8 hex colors, or null if the seed is unusable.
	 */
	private function buildRampFromSeed(string $seed): ?array {
		[$r, $g, $b] = $this->hexToRgb(hex: $seed);

		// Relative luminance (WCAG); reject seed colors too close to white
		// (poor contrast/near-invisible bars/lines/slices).
		$luminance = ((0.2126 * $r) + (0.7152 * $g) + (0.0722 * $b)) / 255;
		if ($luminance > 0.85) {
			return null;
		}

		[$h, $s, $l] = $this->rgbToHsl(r: $r, g: $g, b: $b);

		// Keep saturation/lightness in a legible band regardless of seed.
		$s = max(0.35, min($s, 0.85));
		$l = max(0.30, min($l, 0.55));

		$ramp = [];
		for ($i = 0; $i < 8; $i++) {
			$hue = fmod(($h + ($i * 45)), 360);
			$ramp[] = $this->hslToHex(h: $hue, s: $s, l: $l);
		}

		return $ramp;
	}//end buildRampFromSeed()

	/**
	 * Validate a string is a `#RRGGBB` hex color.
	 *
	 * @param string $color Candidate color string.
	 *
	 * @return bool True when valid.
	 */
	private function isValidHexColor(string $color): bool {
		return preg_match('/^#[0-9A-Fa-f]{6}$/', $color) === 1;
	}//end isValidHexColor()

	/**
	 * Convert a `#RRGGBB` hex color to an `[r, g, b]` (0-255) triple.
	 *
	 * @param string $hex Hex color, validated by the caller.
	 *
	 * @return array{0: int, 1: int, 2: int}
	 */
	private function hexToRgb(string $hex): array {
		$hex = ltrim($hex, '#');

		return [
			hexdec(substr($hex, 0, 2)),
			hexdec(substr($hex, 2, 2)),
			hexdec(substr($hex, 4, 2)),
		];

	}//end hexToRgb()

	/**
	 * Convert an RGB (0-255) triple to `[hue(0-360), saturation(0-1), lightness(0-1)]`.
	 *
	 * @param int $r Red channel.
	 * @param int $g Green channel.
	 * @param int $b Blue channel.
	 *
	 * @return array{0: float, 1: float, 2: float}
	 */
	private function rgbToHsl(int $r, int $g, int $b): array {
		$rf = $r / 255;
		$gf = $g / 255;
		$bf = $b / 255;

		$max = max($rf, $gf, $bf);
		$min = min($rf, $gf, $bf);
		$l = ($max + $min) / 2;

		if ($max === $min) {
			return [0.0, 0.0, $l];
		}

		$d = $max - $min;
		$s = $d / ($max + $min);
		if ($l > 0.5) {
			$s = $d / (2 - $max - $min);
		}

		return [$this->hueOf(rf: $rf, gf: $gf, bf: $bf, max: $max, d: $d), $s, $l];
	}//end rgbToHsl()

	/**
	 * Compute the hue (0-360°) of a color from its normalized channels.
	 *
	 * @param float $rf Red channel (0-1).
	 * @param float $gf Green channel (0-1).
	 * @param float $bf Blue channel (0-1).
	 * @param float $max The largest of the three channels.
	 * @param float $d The channel range (max - min), guaranteed non-zero.
	 *
	 * @return float Hue in degrees.
	 */
	private function hueOf(float $rf, float $gf, float $bf, float $max, float $d): float {
		switch ($max) {
			case $rf:
				$h = fmod((($gf - $bf) / $d), 6);
				break;
			case $gf:
				$h = (($bf - $rf) / $d) + 2;
				break;
			default:
				$h = (($rf - $gf) / $d) + 4;
				break;
		}

		$h *= 60;
		if ($h < 0) {
			$h += 360;
		}

		return $h;
	}//end hueOf()

	/**
	 * Convert `[hue(0-360), saturation(0-1), lightness(0-1)]` to a `#RRGGBB` hex color.
	 *
	 * @param float $h Hue in degrees.
	 * @param float $s Saturation (0-1).
	 * @param float $l Lightness (0-1).
	 *
	 * @return string Hex color.
	 */
	private function hslToHex(float $h, float $s, float $l): string {
		$c = (1 - abs((2 * $l) - 1)) * $s;
		$x = $c * (1 - abs(fmod(($h / 60), 2) - 1));
		$m = $l - ($c / 2);

		[$r, $g, $b] = $this->hueSextant(h: $h, c: $c, x: $x);

		$rHex = str_pad(dechex((int)round(($r + $m) * 255)), 2, '0', STR_PAD_LEFT);
		$gHex = str_pad(dechex((int)round(($g + $m) * 255)), 2, '0', STR_PAD_LEFT);
		$bHex = str_pad(dechex((int)round(($b + $m) * 255)), 2, '0', STR_PAD_LEFT);

		return '#' . strtoupper($rHex . $gHex . $bHex);
	}//end hslToHex()

	/**
	 * Map a hue (0-360°) to its `[r, g, b]` sextant using the chroma/second
	 * largest component (`c`/`x`) already computed by {@see hslToHex()}.
	 *
	 * @param float $h Hue in degrees.
	 * @param float $c Chroma.
	 * @param float $x Second-largest color component.
	 *
	 * @return array{0: float, 1: float, 2: float}
	 */
	private function hueSextant(float $h, float $c, float $x): array {
		if ($h < 60) {
			return [$c, $x, 0];
		}

		if ($h < 120) {
			return [$x, $c, 0];
		}

		if ($h < 180) {
			return [0, $c, $x];
		}

		if ($h < 240) {
			return [0, $x, $c];
		}

		if ($h < 300) {
			return [$x, 0, $c];
		}

		return [$c, 0, $x];
	}//end hueSextant()
}//end class
