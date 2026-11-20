<?php

/**
 * PDF Page Rasterizer
 *
 * Renders each page of a PDF to a PNG for Tesseract to read, through the
 * Imagick extension, the same way the OCR pass renders them.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Ocr
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-11-20-finish-half-built-capabilities/tasks.md#task-1.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Ocr;

use Imagick;
use RuntimeException;

/**
 * Page images of a PDF.
 */
class PdfPageRasterizer {

	/**
	 * Render every page of a PDF to a PNG in a directory.
	 *
	 * @param string $pdfPath   The PDF.
	 * @param int    $dpi       The resolution.
	 * @param string $directory An existing directory for the images.
	 *
	 * @return list<string> The image paths, one per page, in page order.
	 *
	 * @throws RuntimeException When the Imagick extension is not loaded.
	 *
	 * @spec openspec/changes/archive/2026-11-20-finish-half-built-capabilities/tasks.md#task-1.1
	 */
	public function pages(string $pdfPath, int $dpi, string $directory): array {
		if (extension_loaded('imagick') === false) {
			throw new RuntimeException('The Imagick extension is not loaded.');
		}

		$imagick = new Imagick();
		$imagick->setResolution($dpi, $dpi);
		$imagick->readImage($pdfPath);

		$paths = [];
		try {
			$count = $imagick->getNumberImages();
			for ($page = 0; $page < $count; $page++) {
				$imagick->setIteratorIndex($page);
				$imagick->setImageFormat('png');
				$path = $directory . '/page_' . $page . '.png';
				$imagick->writeImage($path);
				$paths[] = $path;
			}
		} finally {
			$imagick->clear();
			$imagick->destroy();
		}

		return $paths;

	}//end pages()
}//end class
