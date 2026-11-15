<?php

/**
 * Doubles that let OcrService's PDF path run without Imagick installed.
 *
 * OcrService asks `extension_loaded('imagick')` unqualified from its own
 * namespace, so a function of that name in that namespace answers first;
 * it defers to the real one unless a test sets an answer. When the Imagick
 * extension is absent, a recording stand-in for the class is declared.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Ocr
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Service {

	/**
	 * The extension check OcrService sees; a test may fix its answer.
	 *
	 * @param string $extension The extension name.
	 *
	 * @return bool Whether it is loaded.
	 */
	function extension_loaded(string $extension): bool {
		$fixed = \OCA\Filinq\Tests\Unit\Service\Ocr\ImagickPdfWorld::$extensionLoaded;
		if ($fixed !== null) {
			return $fixed;
		}

		return \extension_loaded($extension);
	}//end extension_loaded()
}

namespace OCA\Filinq\Tests\Unit\Service\Ocr {

	/**
	 * What the doubles answer and what they were asked.
	 */
	final class ImagickPdfWorld {
		/**
		 * Fixed answer of the extension check, or null for the real one.
		 *
		 * @var bool|null
		 */
		public static ?bool $extensionLoaded = null;

		/**
		 * How many pages the PDF has.
		 *
		 * @var int
		 */
		public static int $pages = 0;

		/**
		 * The calls made, in order.
		 *
		 * @var array<int, array<int, mixed>>
		 */
		public static array $calls = [];

		/**
		 * The real paths of the page images written.
		 *
		 * @var array<int, string>
		 */
		public static array $written = [];

		/**
		 * Record a call, with the temp directory written as <tmp>.
		 *
		 * @param array<int, mixed> $call The call.
		 *
		 * @return void
		 */
		public static function record(array $call): void {
			self::$calls[] = array_map(
				static fn (mixed $value): mixed => is_string($value) === true
					? (string)preg_replace('#^' . preg_quote(sys_get_temp_dir(), '#') . '/filinq_ocr_[0-9a-f]+#', '<tmp>', $value)
					: $value,
				$call
			);
		}//end record()

		/**
		 * Forget everything.
		 *
		 * @return void
		 */
		public static function reset(): void {
			self::$extensionLoaded = null;
			self::$pages = 0;
			self::$calls = [];
			foreach (self::$written as $path) {
				if (is_file($path) === true) {
					unlink($path);
				}
			}

			foreach (array_unique(array_map('dirname', self::$written)) as $dir) {
				if (is_dir($dir) === true) {
					rmdir($dir);
				}
			}

			self::$written = [];
		}//end reset()
	}//end class
}

namespace {

	use OCA\Filinq\Tests\Unit\Service\Ocr\ImagickPdfWorld;

	if (class_exists('Imagick') === false) {
		/**
		 * A recording stand-in for Imagick.
		 */
		class Imagick {
			/**
			 * Record the resolution.
			 *
			 * @param float $x Horizontal.
			 * @param float $y Vertical.
			 *
			 * @return bool True.
			 */
			public function setResolution(float $x, float $y): bool {
				ImagickPdfWorld::record(['setResolution', $x, $y]);
				return true;
			}

			/**
			 * Record the read.
			 *
			 * @param string $path The PDF.
			 *
			 * @return bool True.
			 */
			public function readImage(string $path): bool {
				ImagickPdfWorld::record(['readImage', $path]);
				return true;
			}

			/**
			 * The page count.
			 *
			 * @return int The pages.
			 */
			public function getNumberImages(): int {
				ImagickPdfWorld::record(['getNumberImages']);
				return ImagickPdfWorld::$pages;
			}

			/**
			 * Record the page.
			 *
			 * @param int $index The page.
			 *
			 * @return bool True.
			 */
			public function setIteratorIndex(int $index): bool {
				ImagickPdfWorld::record(['setIteratorIndex', $index]);
				return true;
			}

			/**
			 * Record the format.
			 *
			 * @param string $format The format.
			 *
			 * @return bool True.
			 */
			public function setImageFormat(string $format): bool {
				ImagickPdfWorld::record(['setImageFormat', $format]);
				return true;
			}

			/**
			 * Write a placeholder page image.
			 *
			 * @param string $path Where.
			 *
			 * @return bool True.
			 */
			public function writeImage(string $path): bool {
				ImagickPdfWorld::record(['writeImage', $path, is_dir(dirname($path))]);
				file_put_contents($path, 'png');
				ImagickPdfWorld::$written[] = $path;
				return true;
			}

			/**
			 * Record the clear.
			 *
			 * @return bool True.
			 */
			public function clear(): bool {
				ImagickPdfWorld::record(['clear']);
				return true;
			}

			/**
			 * Record the destroy.
			 *
			 * @return bool True.
			 */
			public function destroy(): bool {
				ImagickPdfWorld::record(['destroy']);
				return true;
			}
		}//end class
	}//end if
}
