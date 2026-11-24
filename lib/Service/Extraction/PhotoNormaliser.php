<?php

/**
 * Photo Normaliser
 *
 * Turns a receipt photo upright from its EXIF orientation and converts a
 * HEIC photo to JPEG (extraction-receipt-photo-and-invoice-agent D2). A
 * phone stores the photo as the sensor saw it and records how it was held;
 * text recognition reads the pixels, so a sideways photo reads as nothing.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Extraction
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

namespace OCA\Filinq\Service\Extraction;

use Imagick;
use OCA\Filinq\Exception\ReceiptCaptureRefusedException;
use Throwable;

/**
 * Uprights JPEG photos and converts HEIC to JPEG.
 *
 * @spec openspec/changes/extraction-receipt-photo-and-invoice-agent/tasks.md#task-2.1
 */
class PhotoNormaliser {

	/**
	 * The refusal of a HEIC photo on a server that cannot read it.
	 *
	 * @var string
	 */
	public const MESSAGE_HEIC = 'This photo format cannot be read on this server. Take the photo as JPEG.';

	/**
	 * The degrees to turn a photo counter-clockwise, per EXIF orientation.
	 *
	 * Orientations 2, 4, 5 and 7 are mirrored; a camera never writes them.
	 *
	 * @var array<int, int>
	 */
	private const ROTATIONS = [3 => 180, 6 => 270, 8 => 90];

	/**
	 * Whether this server can convert HEIC.
	 *
	 * @return bool
	 *
	 * @spec openspec/changes/extraction-receipt-photo-and-invoice-agent/tasks.md#task-2.1
	 */
	public function readsHeic(): bool {
		if (class_exists('Imagick') === false) {
			return false;
		}

		try {
			return (new Imagick())->queryFormats('HEIC') !== [];
		} catch (Throwable) {
			return false;
		}
	}//end readsHeic()

	/**
	 * A HEIC photo as an upright JPEG.
	 *
	 * @param string $heic The HEIC bytes.
	 *
	 * @return string The JPEG bytes.
	 *
	 * @throws ReceiptCaptureRefusedException (415) When this server cannot read HEIC, or this photo.
	 *
	 * @spec openspec/changes/extraction-receipt-photo-and-invoice-agent/tasks.md#task-2.1
	 */
	public function heicToJpeg(string $heic): string {
		if ($this->readsHeic() === false) {
			throw new ReceiptCaptureRefusedException(message: self::MESSAGE_HEIC, code: 415);
		}

		try {
			$image = new Imagick();
			$image->readImageBlob($heic);
			$image->autoOrient();
			$image->setImageFormat('jpeg');
			$jpeg = $image->getImagesBlob();
			$image->clear();

			return $jpeg;
		} catch (Throwable $e) {
			throw new ReceiptCaptureRefusedException(message: self::MESSAGE_HEIC, code: 415, previous: $e);
		}
	}//end heicToJpeg()

	/**
	 * A JPEG photo turned upright from its EXIF orientation; unchanged when it already is, or carries none.
	 *
	 * @param string $jpeg The JPEG bytes.
	 *
	 * @return string The upright JPEG bytes.
	 *
	 * @spec openspec/changes/extraction-receipt-photo-and-invoice-agent/tasks.md#task-2.1
	 */
	public function upright(string $jpeg): string {
		$degrees = (self::ROTATIONS[$this->orientationOf(jpeg: $jpeg)] ?? 0);
		if ($degrees === 0 || function_exists('imagecreatefromstring') === false) {
			return $jpeg;
		}

		$image = $this->quietly(work: static fn (): mixed => imagecreatefromstring($jpeg));
		if ($image === false) {
			return $jpeg;
		}

		$turned = imagerotate($image, $degrees, 0);
		if ($turned === false) {
			return $jpeg;
		}

		ob_start();
		imagejpeg($turned, null, 92);

		return (string)ob_get_clean();
	}//end upright()

	/**
	 * The EXIF orientation of a JPEG, 1 when it records none.
	 *
	 * @param string $jpeg The JPEG bytes.
	 *
	 * @return int The orientation, 1 to 8.
	 */
	private function orientationOf(string $jpeg): int {
		if (function_exists('exif_read_data') === false) {
			return 1;
		}

		$exif = $this->quietly(work: static fn (): mixed => exif_read_data('data://image/jpeg;base64,' . base64_encode($jpeg)));
		if (is_array($exif) === false) {
			return 1;
		}

		return (int)($exif['Orientation'] ?? 1);
	}//end orientationOf()

	/**
	 * Run a GD or EXIF call that warns on bytes it cannot read, without the warning.
	 *
	 * A photo that is not a JPEG, or has a broken EXIF block, is answered by
	 * keeping it as it came, not by a warning in the log.
	 *
	 * @param callable $work The call.
	 *
	 * @return mixed What it returned.
	 */
	private function quietly(callable $work): mixed {
		set_error_handler(static fn (): bool => true);
		try {
			return $work();
		} finally {
			restore_error_handler();
		}
	}//end quietly()
}//end class
