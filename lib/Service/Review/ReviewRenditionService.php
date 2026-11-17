<?php

/**
 * The one PDF rendition with a text layer that review and detection run on.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Review
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-5.1
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Review;

use DateTimeImmutable;
use OCA\Filinq\Kernel\Contracts\OcrResultRepositoryInterface;
use OCA\Filinq\Service\OcrService;
use OCA\Filinq\Service\OpenRegisterServiceLocator;
use OCA\Filinq\Service\OpenRegisterTextExtractionCall;
use OCA\Filinq\Service\PdfConversionService;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Prepares, records and detects on a source file's review rendition.
 *
 * A PDF with a text layer is its own rendition. An office, e-mail or HTML file
 * is converted through PdfConversionService::convertToPdfReporting(). A scan or
 * an image gets its text layer from OCR, or has none when OCR is unavailable.
 * The rendition names the file, its sha256, how it was made, the backend and
 * the text layer, so a check can be tied to exactly what the reviewer saw.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Review
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/anonymization-review-workbench/specs/anonymization-review-workbench/spec.md#requirement-review-runs-on-one-pdf-rendition-with-a-text-layer-and-the-link-records-it-req-ddarw-012
 */
class ReviewRenditionService {

	/**
	 * Kind: the source PDF itself, with its own text layer.
	 *
	 * @var string
	 */
	public const KIND_NATIVE = 'native-pdf';

	/**
	 * Kind: a PDF converted from an office, e-mail or HTML source.
	 *
	 * @var string
	 */
	public const KIND_CONVERTED = 'converted';

	/**
	 * Kind: a scan or image whose text comes from OCR.
	 *
	 * @var string
	 */
	public const KIND_OCR = 'ocr-text-layer';

	/**
	 * Text layer: the rendition carries its own text.
	 *
	 * @var string
	 */
	public const LAYER_NATIVE = 'native';

	/**
	 * Text layer: the text was recognised by OCR.
	 *
	 * @var string
	 */
	public const LAYER_OCR = 'ocr';

	/**
	 * Text layer: no text could be read; detection did not see the pages.
	 *
	 * @var string
	 */
	public const LAYER_NONE = 'none';

	/**
	 * The OCR backend's name on a rendition.
	 *
	 * @var string
	 */
	private const OCR_BACKEND = 'tesseract';

	/**
	 * OpenRegister's text extraction service.
	 *
	 * @var string
	 */
	private const TEXT_EXTRACTOR = 'OCA\OpenRegister\Files\Service\TextExtractionService';

	/**
	 * The OpenRegister method that takes text for a file from the caller.
	 *
	 * @var string
	 */
	private const INGEST_METHOD = 'extractFromProvidedText';

	/**
	 * Constructor.
	 *
	 * @param PdfConversionService         $conversion The PDF conversion cascade.
	 * @param OcrService                   $ocr        Tesseract OCR.
	 * @param OcrResultRepositoryInterface $ocrResults The last OCR result per file.
	 * @param OpenRegisterServiceLocator   $locator    Resolver for OpenRegister's text extraction.
	 * @param ReviewRenditionRepository    $repository The current rendition per source.
	 * @param IRootFolder                  $rootFolder Resolves a stored rendition's file.
	 * @param LoggerInterface              $logger     Logger for diagnostics.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly PdfConversionService $conversion,
		private readonly OcrService $ocr,
		private readonly OcrResultRepositoryInterface $ocrResults,
		private readonly OpenRegisterServiceLocator $locator,
		private readonly ReviewRenditionRepository $repository,
		private readonly IRootFolder $rootFolder,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Prepare the review rendition of one source file.
	 *
	 * @param File $source The source file.
	 *
	 * @return array{fileId: int, sha256: string, kind: string, backend: string, textLayer: string, preparedAt: string}
	 *         The rendition.
	 *
	 * @throws Throwable When a conversion is needed and no backend can make it.
	 *
	 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-5.1
	 */
	public function prepare(File $source): array {
		$mime = (string) $source->getMimeType();
		if ($mime === 'application/pdf') {
			// A scan whose OCR text was handed to OpenRegister has extracted text
			// too; the stored OCR result tells it from a PDF with its own layer.
			if ($this->storedOcrText(source: $source) === false
				&& trim($this->extractedText(fileId: (int) $source->getId())) !== ''
			) {
				return $this->rendition(file: $source, kind: self::KIND_NATIVE, backend: '', textLayer: self::LAYER_NATIVE);
			}

			return $this->scanRendition(source: $source);
		}

		if (str_starts_with($mime, 'image/') === true) {
			return $this->scanRendition(source: $source);
		}

		$converted = $this->conversion->convertToPdfReporting(source: $source);

		return $this->rendition(
			file: $converted['file'],
			kind: self::KIND_CONVERTED,
			backend: (string) $converted['backend'],
			textLayer: self::LAYER_NATIVE
		);

	}//end prepare()

	/**
	 * Prepare and store a source's rendition, reusing a converted one that is still current.
	 *
	 * A converted rendition is written next to its source, so a PDF of the
	 * same name that is not the stored rendition is never overwritten: no
	 * rendition is made then. Best-effort: a failure is logged and answers null,
	 * and detection goes on on the source as before.
	 *
	 * @param File $source The source file.
	 *
	 * @return array<string, mixed>|null The stored rendition, or null when none could be made.
	 *
	 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-5.1
	 */
	public function record(File $source): ?array {
		$sourceId = (int) $source->getId();
		$previous = $this->repository->findFor(sourceFileId: $sourceId);

		try {
			$reused = $this->stillCurrent(source: $source, previous: $previous);
			if ($reused !== null) {
				return $previous;
			}

			if ($this->wouldOverwrite(source: $source, previous: $previous) === true) {
				$this->logger->info(
					'[ReviewRenditionService] a PDF of the same name exists next to the source; no rendition is made',
					['fileId' => $sourceId]
				);
				return null;
			}

			$rendition = $this->prepare(source: $source);

			return $this->repository->saveFor(
				sourceFileId: $sourceId,
				rendition: $rendition + ['fileName' => $this->nameOf(source: $source, fileId: $rendition['fileId'])]
			);
		} catch (Throwable $e) {
			$this->logger->warning(
				'[ReviewRenditionService] the review rendition could not be prepared; detection runs on the source',
				['fileId' => $sourceId, 'error' => $e->getMessage()]
			);
			return null;
		}//end try

	}//end record()

	/**
	 * Prepare and store the rendition of a source given by its file id.
	 *
	 * @param int $fileId The source's Nextcloud file id.
	 *
	 * @return array<string, mixed>|null The stored rendition, or null when the file cannot be found or none could be made.
	 *
	 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-5.1
	 */
	public function recordFor(int $fileId): ?array {
		try {
			$source = $this->rootFolder->getFirstNodeById($fileId);
		} catch (Throwable) {
			$source = null;
		}

		if (($source instanceof File) === false) {
			return null;
		}

		return $this->record(source: $source);

	}//end recordFor()

	/**
	 * The current rendition of a source, as stored.
	 *
	 * @param int $sourceFileId The source's Nextcloud file id.
	 *
	 * @return array<string, mixed>|null The rendition, or null when none was prepared.
	 *
	 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-5.2
	 */
	public function current(int $sourceFileId): ?array {
		return $this->repository->findFor(sourceFileId: $sourceFileId);

	}//end current()

	/**
	 * Run detection on a converted rendition's text, for the source's findings.
	 *
	 * OpenRegister extracts the rendition, and its text is handed to
	 * OpenRegister as the source's, so the findings the reviewer decides on are
	 * found on the pages the reviewer sees. A rendition that is the source
	 * itself needs nothing more. Without OpenRegister's provided-text seam the
	 * source's own extraction stands.
	 *
	 * @param int                       $sourceFileId  The source's Nextcloud file id.
	 * @param array<string, mixed>|null $rendition     The source's rendition.
	 * @param object                    $textExtractor OpenRegister's TextExtractionService.
	 * @param array<int, string>|null   $entityTypes   The enabled entity types, null for all.
	 *
	 * @return bool True when detection ran on the rendition's text.
	 *
	 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-5.1
	 */
	public function detectOnRendition(int $sourceFileId, array|null $rendition, object $textExtractor, array|null $entityTypes): bool {
		$renditionId = $this->convertedRenditionId(sourceFileId: $sourceFileId, rendition: $rendition, textExtractor: $textExtractor);
		if ($renditionId === 0) {
			return false;
		}

		$call = new OpenRegisterTextExtractionCall();
		try {
			$call->extractFile(extractor: $textExtractor, fileId: $renditionId, force: false, entityTypes: $entityTypes);
			$text = (string) $textExtractor->getExtractedText($renditionId);
			if (trim($text) === '') {
				return false;
			}

			$call->provideText(extractor: $textExtractor, fileId: $sourceFileId, text: $text);
		} catch (Throwable $e) {
			$this->logger->warning(
				'[ReviewRenditionService] detection on the rendition failed; the source\'s own findings stand',
				['fileId' => $sourceFileId, 'renditionFileId' => $renditionId, 'error' => $e->getMessage()]
			);
			return false;
		}

		return true;

	}//end detectOnRendition()

	/**
	 * The file id of a converted rendition detection can run on, or 0 when there is none.
	 *
	 * @param int                       $sourceFileId  The source document's file id.
	 * @param array<string, mixed>|null $rendition     The rendition.
	 * @param object                    $textExtractor OpenRegister's TextExtractionService.
	 *
	 * @return int The rendition's file id, or 0.
	 */
	private function convertedRenditionId(int $sourceFileId, array|null $rendition, object $textExtractor): int {
		$renditionId = (int) ($rendition['fileId'] ?? 0);
		if (($rendition['kind'] ?? '') !== self::KIND_CONVERTED
			|| $renditionId <= 0
			|| $renditionId === $sourceFileId
			|| method_exists($textExtractor, self::INGEST_METHOD) === false
		) {
			return 0;
		}

		return $renditionId;

	}//end convertedRenditionId()

	/**
	 * The rendition of a scan or an image: an OCR text layer, or none.
	 *
	 * An OCR result already stored for the file is used as it is, so the
	 * extraction's own OCR fallback does not run twice.
	 *
	 * @param File $source The scan.
	 *
	 * @return array{fileId: int, sha256: string, kind: string, backend: string, textLayer: string, preparedAt: string}
	 */
	private function scanRendition(File $source): array {
		if ($this->hasOcrText(source: $source) === true) {
			return $this->rendition(file: $source, kind: self::KIND_OCR, backend: self::OCR_BACKEND, textLayer: self::LAYER_OCR);
		}

		return $this->rendition(file: $source, kind: self::KIND_OCR, backend: '', textLayer: self::LAYER_NONE);

	}//end scanRendition()

	/**
	 * Whether OCR recovered text from the scan, from a stored result or a fresh run.
	 *
	 * @param File $source The scan.
	 *
	 * @return bool True when there is an OCR text layer.
	 */
	private function hasOcrText(File $source): bool {
		if ($this->storedOcrText(source: $source) === true) {
			return true;
		}

		if ($this->ocr->isOcrEnabled() === false || $this->ocr->isTesseractAvailable() === false) {
			return false;
		}

		try {
			return trim($this->ocr->processNode(file: $source)['text']) !== '';
		} catch (Throwable $e) {
			$this->logger->warning(
				'[ReviewRenditionService] OCR failed; the scan has no text layer',
				['fileId' => (int) $source->getId(), 'error' => $e->getMessage()]
			);
			return false;
		}

	}//end hasOcrText()

	/**
	 * Whether a stored OCR result for the file recovered any text.
	 *
	 * @param File $source The file.
	 *
	 * @return bool True when it did.
	 */
	private function storedOcrText(File $source): bool {
		try {
			$stored = $this->ocrResults->findForFile(fileId: (int) $source->getId());
		} catch (Throwable) {
			return false;
		}

		return $stored !== null && (int) ($stored['textLength'] ?? 0) > 0;

	}//end storedOcrText()

	/**
	 * The text OpenRegister extracted from a file, or '' when it has none or cannot be read.
	 *
	 * @param int $fileId The Nextcloud file id.
	 *
	 * @return string The text.
	 */
	private function extractedText(int $fileId): string {
		try {
			return (string) $this->locator->get(className: self::TEXT_EXTRACTOR)->getExtractedText($fileId);
		} catch (Throwable) {
			return '';
		}

	}//end extractedText()

	/**
	 * The stored rendition when it is converted, its file still exists and is newer than the source.
	 *
	 * @param File                      $source   The source.
	 * @param array<string, mixed>|null $previous The stored rendition.
	 *
	 * @return File|null The rendition's file, or null when it has to be made again.
	 */
	private function stillCurrent(File $source, array|null $previous): File|null {
		if ($previous === null || ($previous['kind'] ?? '') !== self::KIND_CONVERTED) {
			return null;
		}

		$file = $this->rootFolder->getFirstNodeById((int) ($previous['fileId'] ?? 0));
		if (($file instanceof File) === false || $this->isUnchanged(file: $file, source: $source, previous: $previous) === false) {
			return null;
		}

		return $file;

	}//end stillCurrent()

	/**
	 * Whether a stored rendition's file is not older than its source and still has the stored hash.
	 *
	 * @param File                 $file     The rendition's file.
	 * @param File                 $source   The source.
	 * @param array<string, mixed> $previous The stored rendition.
	 *
	 * @return bool True when it can be reused.
	 */
	private function isUnchanged(File $file, File $source, array $previous): bool {
		return $file->getMTime() >= $source->getMTime()
			&& hash_equals((string) ($previous['sha256'] ?? ''), hash('sha256', (string) $file->getContent())) === true;

	}//end isUnchanged()

	/**
	 * The rendition file's name, so the workbench can open it next to its source.
	 *
	 * @param File $source The source.
	 * @param int  $fileId The rendition's file id.
	 *
	 * @return string The name; '' when the file cannot be found.
	 */
	private function nameOf(File $source, int $fileId): string {
		if ($fileId === (int) $source->getId()) {
			return (string) $source->getName();
		}

		$file = $this->rootFolder->getFirstNodeById($fileId);
		if (($file instanceof File) === false) {
			return '';
		}

		return (string) $file->getName();

	}//end nameOf()

	/**
	 * Whether converting would replace a PDF next to the source that is not the stored rendition.
	 *
	 * @param File                      $source   The source.
	 * @param array<string, mixed>|null $previous The stored rendition.
	 *
	 * @return bool True when the conversion must not run.
	 */
	private function wouldOverwrite(File $source, ?array $previous): bool {
		$mime = (string) $source->getMimeType();
		if ($mime === 'application/pdf' || str_starts_with($mime, 'image/') === true) {
			return false;
		}

		$name = pathinfo($source->getName(), PATHINFO_FILENAME) . '.pdf';
		$parent = $source->getParent();
		if ($parent->nodeExists($name) === false) {
			return false;
		}

		return (int) $parent->get($name)->getId() !== (int) ($previous['fileId'] ?? 0);

	}//end wouldOverwrite()

	/**
	 * The six rendition fields of one file.
	 *
	 * @param File        $file      The rendition's file.
	 * @param string      $kind      How it was made.
	 * @param string      $backend   The backend that made it; empty when none did.
	 * @param string      $textLayer Where its text comes from.
	 *
	 * @return array{fileId: int, sha256: string, kind: string, backend: string, textLayer: string, preparedAt: string}
	 */
	private function rendition(File $file, string $kind, string $backend, string $textLayer): array {
		return [
			'fileId' => (int) $file->getId(),
			'sha256' => hash('sha256', (string) $file->getContent()),
			'kind' => $kind,
			'backend' => $backend,
			'textLayer' => $textLayer,
			'preparedAt' => (new DateTimeImmutable())->format('c'),
		];

	}//end rendition()
}//end class
