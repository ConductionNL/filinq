<?php

/**
 * Unit tests for PhotoNormaliser.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Extraction
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/extraction-receipt-photo-and-invoice-agent/tasks.md#task-2.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Extraction;

use OCA\Filinq\Exception\ReceiptCaptureRefusedException;
use OCA\Filinq\Service\Extraction\PhotoNormaliser;
use PHPUnit\Framework\TestCase;

/**
 * REQ-ERP-002: a photo is read upright, and HEIC only where the server can.
 *
 * @covers \OCA\Filinq\Service\Extraction\PhotoNormaliser
 */
class PhotoNormaliserTest extends TestCase {
	/**
	 * A 40 by 20 JPEG, carrying the given EXIF orientation, or no EXIF at all.
	 *
	 * @param int|null $orientation The orientation, null for none.
	 *
	 * @return string The JPEG bytes.
	 */
	private function photo(?int $orientation): string {
		$image = imagecreatetruecolor(40, 20);
		ob_start();
		imagejpeg($image);
		$jpeg = (string)ob_get_clean();
		if ($orientation === null) {
			return $jpeg;
		}

		// TIFF little endian, one IFD entry: 0x0112 Orientation, SHORT, 1, value.
		$tiff = 'II' . pack('v', 42) . pack('V', 8) . pack('v', 1)
			. pack('v', 0x0112) . pack('v', 3) . pack('V', 1) . pack('v', $orientation) . pack('v', 0)
			. pack('V', 0);
		$app1 = "Exif\0\0" . $tiff;

		return substr($jpeg, 0, 2) . "\xFF\xE1" . pack('n', strlen($app1) + 2) . $app1 . substr($jpeg, 2);
	}//end photo()

	/**
	 * The width and height of a JPEG.
	 *
	 * @param string $jpeg The bytes.
	 *
	 * @return array{0: int, 1: int}
	 */
	private function dimensions(string $jpeg): array {
		$info = getimagesizefromstring($jpeg);

		return [(int)$info[0], (int)$info[1]];
	}//end dimensions()

	/**
	 * A photo taken with the phone held upright (orientation 6) comes back turned: 20 by 40.
	 *
	 * @return void
	 */
	public function testARotatedPhotoIsTurnedUpright(): void {
		$this->assertSame([20, 40], $this->dimensions((new PhotoNormaliser())->upright(jpeg: $this->photo(orientation: 6))));
		$this->assertSame([20, 40], $this->dimensions((new PhotoNormaliser())->upright(jpeg: $this->photo(orientation: 8))));
		$this->assertSame([40, 20], $this->dimensions((new PhotoNormaliser())->upright(jpeg: $this->photo(orientation: 3))));
	}//end testARotatedPhotoIsTurnedUpright()

	/**
	 * An upright photo, one without EXIF, and bytes that are no image come back unchanged.
	 *
	 * @return void
	 */
	public function testAnUprightOrUnreadablePhotoIsUnchanged(): void {
		$normaliser = new PhotoNormaliser();
		foreach ([$this->photo(orientation: 1), $this->photo(orientation: null), 'not a photo'] as $bytes) {
			$this->assertSame($bytes, $normaliser->upright(jpeg: $bytes));
		}
	}//end testAnUprightOrUnreadablePhotoIsUnchanged()

	/**
	 * Without HEIC support a HEIC photo is refused with 415 and the advice to take a JPEG.
	 *
	 * @return void
	 */
	public function testHeicWithoutSupportIsRefused(): void {
		$normaliser = new PhotoNormaliser();
		if ($normaliser->readsHeic() === true) {
			$this->markTestSkipped('This server reads HEIC; the refusal is for servers that cannot.');
		}

		try {
			$normaliser->heicToJpeg(heic: "\0\0\0\x18ftypheic");
			$this->fail('HEIC must be refused without support.');
		} catch (ReceiptCaptureRefusedException $e) {
			$this->assertSame(415, $e->getCode());
			$this->assertSame(PhotoNormaliser::MESSAGE_HEIC, $e->getMessage());
		}
	}//end testHeicWithoutSupportIsRefused()
	/**
	 * A photo that says it is turned but whose image data cannot be read comes back as it came.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/extraction-receipt-photo-and-invoice-agent/tasks.md#task-2.1
	 */
	public function testATurnedPhotoWithBrokenImageDataIsUnchanged(): void {
		$photo = $this->photo(orientation: 6);
		// Turn the frame header into a comment: the EXIF block still reads, the image does not.
		$broken = (string)preg_replace(pattern: '/\xFF\xC0/', replacement: "\xFF\xFE", subject: $photo, limit: 1);
		$this->assertNotSame(expected: $photo, actual: $broken);

		$this->assertSame(expected: $broken, actual: (new PhotoNormaliser())->upright(jpeg: $broken));
	}//end testATurnedPhotoWithBrokenImageDataIsUnchanged()

	/**
	 * A server that claims HEIC support but cannot decode this photo refuses it with 415 too.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/extraction-receipt-photo-and-invoice-agent/tasks.md#task-2.1
	 */
	public function testAHeicPhotoThatCannotBeDecodedIsRefused(): void {
		$normaliser = new class extends PhotoNormaliser {
			/**
			 * Claim HEIC support, so the conversion itself is attempted.
			 *
			 * @return bool
			 */
			public function readsHeic(): bool {
				return true;
			}//end readsHeic()
		};

		try {
			$normaliser->heicToJpeg(heic: 'not a heic photo');
			$this->fail(message: 'An undecodable HEIC photo must be refused.');
		} catch (ReceiptCaptureRefusedException $e) {
			$this->assertSame(expected: 415, actual: $e->getCode());
			$this->assertSame(expected: PhotoNormaliser::MESSAGE_HEIC, actual: $e->getMessage());
			$this->assertNotNull(actual: $e->getPrevious());
		}
	}//end testAHeicPhotoThatCannotBeDecodedIsRefused()
}//end class
