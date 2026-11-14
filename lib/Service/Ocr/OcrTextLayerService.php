<?php

/**
 * OCR Text Layer Service
 *
 * After an OCR run, makes the file itself searchable (REQ-FHB-01): a scanned
 * PDF gets its text layer written into a new version of the same file, so the
 * file before the run stays in its version history, and a JPEG or PNG gets a
 * searchable PDF stored beside it. A text layer that cannot be made is
 * reported, never thrown: the OCR result stands without it.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Ocr
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/finish-half-built-capabilities/tasks.md#task-1.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Ocr;

use OCP\Files\File;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Writes the text layer of an OCR run back to the file.
 */
class OcrTextLayerService {

	/**
	 * The PDF has a text layer in a new version.
	 */
	public const WRITTEN = 'written';

	/**
	 * A searchable PDF was stored beside the image.
	 */
	public const COPY_CREATED = 'copy_created';

	/**
	 * The file is neither a PDF nor an image a page is built from.
	 */
	public const SKIP_UNSUPPORTED_TYPE = 'unsupported_type';

	/**
	 * The person may not change the PDF, or may not add a file beside the image.
	 */
	public const SKIP_NOT_WRITABLE = 'not_writable';

	/**
	 * No word with a box was read, so there is nothing to lay over the page.
	 */
	public const SKIP_NO_WORDS = 'no_words';

	/**
	 * The text layer could not be made; the reason is logged.
	 */
	public const SKIP_FAILED = 'failed';

	/**
	 * Constructor.
	 *
	 * @param SearchablePdfWriter $writer Builds the searchable PDF.
	 * @param LoggerInterface     $logger The logger.
	 */
	public function __construct(
		private readonly SearchablePdfWriter $writer,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Write the text layer for a file OCR just read.
	 *
	 * @param File   $file      The file, already access-checked by the caller.
	 * @param string $languages The Tesseract language codes the run used.
	 * @param int    $dpi       The resolution the run used.
	 *
	 * @return string WRITTEN, COPY_CREATED or one of the SKIP_* reasons.
	 *
	 * @spec openspec/changes/finish-half-built-capabilities/specs/ocr-document-scanning/spec.md
	 */
	public function apply(File $file, string $languages, int $dpi): string {
		$mimeType = $file->getMimeType();
		$isPdf    = ($mimeType === 'application/pdf');
		if ($isPdf === false && isset(SearchablePdfWriter::IMAGE_TYPES[$mimeType]) === false) {
			return self::SKIP_UNSUPPORTED_TYPE;
		}

		if ($this->mayStore(file: $file, isPdf: $isPdf) === false) {
			return self::SKIP_NOT_WRITABLE;
		}

		$temp = sys_get_temp_dir() . '/filinq_textlayer_' . bin2hex(random_bytes(6));
		try {
			file_put_contents($temp, $file->getContent());
			$built = $this->build(path: $temp, mimeType: $mimeType, languages: $languages, dpi: $dpi);
			if ($built['words'] === 0) {
				return self::SKIP_NO_WORDS;
			}

			return $this->store(file: $file, isPdf: $isPdf, bytes: $built['bytes']);
		} catch (Throwable $e) {
			$this->logger->warning('The OCR text layer could not be written', ['fileId' => $file->getId(), 'exception' => $e->getMessage()]);
			return self::SKIP_FAILED;
		} finally {
			if (file_exists($temp) === true) {
				unlink($temp);
			}
		}

	}//end apply()

	/**
	 * Whether the searchable PDF may be stored: the PDF is updateable, or the image's folder takes a new file.
	 *
	 * @param File $file  The file read.
	 * @param bool $isPdf Whether it is a PDF.
	 *
	 * @return bool
	 */
	private function mayStore(File $file, bool $isPdf): bool {
		try {
			if ($isPdf === true) {
				return $file->isUpdateable();
			}

			return $file->getParent()->isCreatable();
		} catch (Throwable) {
			return false;
		}

	}//end mayStore()

	/**
	 * The searchable PDF of a PDF or an image.
	 *
	 * @param string $path      The local copy.
	 * @param string $mimeType  Its MIME type.
	 * @param string $languages Tesseract language codes.
	 * @param int    $dpi       The resolution.
	 *
	 * @return array{bytes: string, words: int}
	 */
	private function build(string $path, string $mimeType, string $languages, int $dpi): array {
		if ($mimeType === 'application/pdf') {
			return $this->writer->fromPdf(pdfPath: $path, languages: $languages, dpi: $dpi);
		}

		return $this->writer->fromImage(imagePath: $path, mimeType: $mimeType, languages: $languages, dpi: $dpi);

	}//end build()

	/**
	 * Store the searchable PDF: as a new version of a PDF, or as a new file beside an image.
	 *
	 * @param File   $file  The file read.
	 * @param bool   $isPdf Whether it is a PDF.
	 * @param string $bytes The searchable PDF.
	 *
	 * @return string WRITTEN or COPY_CREATED.
	 */
	private function store(File $file, bool $isPdf, string $bytes): string {
		if ($isPdf === true) {
			// Writing the content makes the file before the run a version of it.
			$file->putContent($bytes);
			return self::WRITTEN;
		}

		$folder = $file->getParent();
		$folder->newFile($folder->getNonExistingName(pathinfo($file->getName(), PATHINFO_FILENAME) . '.pdf'), $bytes);

		return self::COPY_CREATED;

	}//end store()
}//end class
