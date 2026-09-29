<?php

/**
 * OCR Extraction Fallback
 *
 * OpenRegister extracts text but does no OCR, so a scanned PDF comes back
 * empty and would read as "nothing to redact". After OpenRegister's
 * extraction this class asks whether the file needed OCR, runs it, and hands
 * the recovered text to OpenRegister's provided-text seam when OpenRegister
 * has one. When it cannot, it says so on the extraction result: `ocrSkipped`
 * with the reason, or `ocrDetectionPending` when OCR ran but detection could
 * not read the text. It never runs its own entity detection.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Ocr
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/ocr-trigger-surface/tasks.md#task-2.3
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Ocr;

use OCA\Filinq\Service\OcrService;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * The OCR step between OpenRegister's extraction and reading the entities back.
 */
class OcrExtractionFallback {

	/**
	 * The OpenRegister method that would take the recovered text
	 * (ConductionNL/openregister#2033). Not on OpenRegister yet.
	 *
	 * @var string
	 */
	public const INGEST_METHOD = 'extractFromProvidedText';

	/**
	 * Constructor.
	 *
	 * @param OcrService $ocr Whether a file needs OCR.
	 * @param OcrRunService $runs Runs OCR and records the result.
	 * @param OcrResultRepository $results The ocrResult rows.
	 * @param IRootFolder $rootFolder Resolves the file node, with or without a session.
	 * @param LoggerInterface $logger The logger.
	 */
	public function __construct(
		private readonly OcrService $ocr,
		private readonly OcrRunService $runs,
		private readonly OcrResultRepository $results,
		private readonly IRootFolder $rootFolder,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Run OCR when OpenRegister's extraction left a scan without text.
	 *
	 * @param int $fileId The Nextcloud file id.
	 * @param object $textExtractor OpenRegister's TextExtractionService.
	 * @param bool $force Whether the caller asked for a fresh analysis.
	 *
	 * @return array<string, mixed> Fields for the extraction result: `ocr`, and
	 *                              `ocrSkipped` or `ocrDetectionPending`. Empty
	 *                              when the file did not need OCR.
	 *
	 * @spec openspec/changes/ocr-trigger-surface/tasks.md#task-2.3
	 */
	public function afterExtraction(int $fileId, object $textExtractor, bool $force): array {
		$file = $this->resolve(fileId: $fileId);
		if ($file === null) {
			return [];
		}

		$extracted = null;
		if (method_exists($textExtractor, 'getExtractedText') === true) {
			$extracted = $textExtractor->getExtractedText($fileId);
		}

		if ($this->ocr->needsOcr(mimeType: $file->getMimeType(), existingText: $extracted) === false) {
			return [];
		}

		// A scan OCR'd before, whose text OpenRegister could not take: running
		// Tesseract again on every reopen would find the same text and still
		// have nowhere to put it.
		$previous = $this->results->findForFile(fileId: $fileId);
		if ($force === false && $previous !== null && $this->canIngest(textExtractor: $textExtractor) === false) {
			return $this->pending(result: $previous);
		}

		$outcome = $this->runs->run(file: $file, trigger: 'fallback');
		if ($outcome['processed'] === false) {
			return [
				'ocr' => ['ran' => false, 'reason' => $outcome['reason']],
				'ocrSkipped' => $outcome['reason'],
			];
		}

		if ($this->canIngest(textExtractor: $textExtractor) === false) {
			return $this->pending(result: $outcome['result']);
		}

		try {
			$textExtractor->{self::INGEST_METHOD}($fileId, $outcome['text']);
		} catch (Throwable $e) {
			$this->logger->warning(
				'OpenRegister did not take the OCR text',
				['fileId' => $fileId, 'exception' => $e->getMessage()]
			);
			return $this->pending(result: $outcome['result']);
		}

		return [
			'ocr' => $this->summary(result: $outcome['result'], ingested: true),
			'ocrDetectionPending' => false,
		];

	}//end afterExtraction()

	/**
	 * Whether OpenRegister has the provided-text seam.
	 *
	 * @param object $textExtractor OpenRegister's TextExtractionService.
	 *
	 * @return bool True when the seam exists.
	 */
	private function canIngest(object $textExtractor): bool {
		return method_exists($textExtractor, self::INGEST_METHOD) === true;

	}//end canIngest()

	/**
	 * OCR ran, detection could not read the text.
	 *
	 * @param array<string, mixed> $result The ocrResult row.
	 *
	 * @return array<string, mixed> The fields.
	 */
	private function pending(array $result): array {
		return [
			'ocr' => $this->summary(result: $result, ingested: false),
			'ocrDetectionPending' => true,
		];

	}//end pending()

	/**
	 * What the review screen shows about the run.
	 *
	 * @param array<string, mixed> $result The ocrResult row.
	 * @param bool $ingested Whether OpenRegister took the text.
	 *
	 * @return array<string, mixed> ran, ingested, confidence, textLength, ocrProcessedAt.
	 */
	private function summary(array $result, bool $ingested): array {
		return [
			'ran' => true,
			'ingested' => $ingested,
			'confidence' => $result['confidence'] ?? null,
			'textLength' => $result['textLength'] ?? null,
			'ocrProcessedAt' => $result['ocrProcessedAt'] ?? null,
		];

	}//end summary()

	/**
	 * The file node, or null.
	 *
	 * @param int $fileId The Nextcloud file id.
	 *
	 * @return File|null The file.
	 */
	private function resolve(int $fileId): ?File {
		try {
			$node = $this->rootFolder->getFirstNodeById($fileId);
		} catch (Throwable $e) {
			return null;
		}

		if ($node instanceof File === false) {
			return null;
		}

		return $node;

	}//end resolve()
}//end class
