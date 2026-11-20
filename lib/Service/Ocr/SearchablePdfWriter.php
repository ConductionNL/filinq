<?php

/**
 * Searchable PDF Writer
 *
 * Builds a searchable PDF: each page of a scanned PDF imported as it is,
 * or an image placed on a page of its own size, with the words Tesseract
 * reads on it behind it as invisible text (REQ-FHB-01).
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

use InvalidArgumentException;
use OCA\Filinq\Service\TesseractCommand;
use RuntimeException;

/**
 * A text layer over page images that stay as they are.
 */
class SearchablePdfWriter {

	/**
	 * The image types a page can be built from, and FPDF's name for each.
	 *
	 * @var array<string, string>
	 */
	public const IMAGE_TYPES = ['image/jpeg' => 'JPG', 'image/png' => 'PNG'];

	/**
	 * Constructor.
	 *
	 * @param TesseractCommand  $tesseract  Reads the words of a page image.
	 * @param PdfPageRasterizer $rasterizer Renders the pages of a PDF.
	 */
	public function __construct(
		private readonly TesseractCommand $tesseract,
		private readonly PdfPageRasterizer $rasterizer,
	) {

	}//end __construct()

	/**
	 * A scanned PDF with a text layer on every page; the pages themselves are imported unchanged.
	 *
	 * @param string $pdfPath   The PDF.
	 * @param string $languages Tesseract language codes.
	 * @param int    $dpi       The resolution the pages are read at.
	 *
	 * @return array{bytes: string, words: int} The new PDF and how many words it carries.
	 *
	 * @throws RuntimeException When the pages cannot be rendered, or their count differs from the PDF's.
	 * @throws \setasign\Fpdi\PdfParser\PdfParserException When FPDI cannot read the PDF.
	 *
	 * @SuppressWarnings(PHPMD.StaticAccess) OcrPage's named constructors build a value object from OCR
	 * output; an instance would add nothing.
	 *
	 * @spec openspec/changes/archive/2026-11-20-finish-half-built-capabilities/tasks.md#task-1.1
	 */
	public function fromPdf(string $pdfPath, string $languages, int $dpi): array {
		$pdf   = new InvisibleTextPdf();
		$count = $pdf->setSourceFile($pdfPath);

		$directory = sys_get_temp_dir() . '/filinq_textlayer_' . bin2hex(random_bytes(6));
		mkdir($directory, 0700, true);
		try {
			$images = $this->rasterizer->pages(pdfPath: $pdfPath, dpi: $dpi, directory: $directory);
			if (count($images) !== $count) {
				throw new RuntimeException(sprintf('%d page images were rendered for %d pages.', count($images), $count));
			}

			$words = 0;
			foreach ($images as $index => $image) {
				$template = $pdf->importPage($index + 1);
				$size     = $pdf->getTemplateSize($template);
				$pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
				$pdf->useTemplate($template, 0, 0, $size['width'], $size['height']);
				$words += $pdf->addInvisibleWords(page: OcrPage::fromTsv(tsv: $this->tesseract->words(filePath: $image, languages: $languages, dpi: $dpi)));
			}
		} finally {
			foreach ((array) glob($directory . '/*') as $image) {
				unlink((string) $image);
			}

			rmdir($directory);
		}

		return ['bytes' => $pdf->Output('S'), 'words' => $words];

	}//end fromPdf()

	/**
	 * A one-page PDF of an image, at the size the resolution gives it, with its words as a text layer.
	 *
	 * @param string $imagePath The image.
	 * @param string $mimeType  Its MIME type: one of IMAGE_TYPES.
	 * @param string $languages Tesseract language codes.
	 * @param int    $dpi       The resolution the image was scanned at.
	 *
	 * @return array{bytes: string, words: int} The PDF and how many words it carries.
	 *
	 * @throws InvalidArgumentException When the type is not one of IMAGE_TYPES, or the image has no size.
	 *
	 * @SuppressWarnings(PHPMD.StaticAccess) OcrPage's named constructors build a value object from OCR
	 * output; an instance would add nothing.
	 * @SuppressWarnings(PHPMD.ErrorControlOperator) The call reports failure through its return value,
	 * which is checked right after; the PHP warning it would also raise adds nothing.
	 *
	 * @spec openspec/changes/archive/2026-11-20-finish-half-built-capabilities/tasks.md#task-1.1
	 */
	public function fromImage(string $imagePath, string $mimeType, string $languages, int $dpi): array {
		if (isset(self::IMAGE_TYPES[$mimeType]) === false) {
			throw new InvalidArgumentException(sprintf('A searchable PDF is not built from %s.', $mimeType));
		}

		$dimensions = @getimagesize($imagePath);
		if ($dimensions === false || $dimensions[0] <= 0 || $dimensions[1] <= 0) {
			throw new InvalidArgumentException('The image has no size.');
		}

		$width  = ($dimensions[0] * 72 / max(1, $dpi));
		$height = ($dimensions[1] * 72 / max(1, $dpi));
		$page   = OcrPage::fromTsv(tsv: $this->tesseract->words(filePath: $imagePath, languages: $languages, dpi: $dpi));

		$pdf = new InvisibleTextPdf();
		$pdf->AddPage($this->orientation(width: $width, height: $height), [$width, $height]);
		$pdf->Image($imagePath, 0, 0, $width, $height, self::IMAGE_TYPES[$mimeType]);
		$words = $pdf->addInvisibleWords(page: new OcrPage(width: $dimensions[0], height: $dimensions[1], words: $page->words));

		return ['bytes' => $pdf->Output('S'), 'words' => $words];

	}//end fromImage()

	/**
	 * FPDF's orientation for a page size.
	 *
	 * @param float $width  The width.
	 * @param float $height The height.
	 *
	 * @return string `L` when wider than high, `P` otherwise.
	 */
	private function orientation(float $width, float $height): string {
		if ($width > $height) {
			return 'L';
		}

		return 'P';

	}//end orientation()
}//end class
