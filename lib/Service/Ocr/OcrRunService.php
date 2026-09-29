<?php

/**
 * OCR Run Service
 *
 * Runs the local Tesseract engine on one file for a person (the route) or
 * for the anonymisation pipeline (the fallback), with the admin's settings,
 * and records what it found in an `ocrResult` row. It says why when it cannot
 * run, so no caller has to guess from an empty text.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Ocr
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-ocr-trigger-surface/tasks.md#task-2.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Ocr;

use OCA\Filinq\Service\OcrService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\File;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * One OCR run on one file node.
 */
class OcrRunService {

	public const SKIP_DISABLED = 'ocr_disabled';

	public const SKIP_NO_TESSERACT = 'tesseract_unavailable';

	public const SKIP_NOT_CANDIDATE = 'not_candidate';

	public const SKIP_NO_TEXT = 'no_text_recovered';

	public const SKIP_FAILED = 'ocr_failed';

	/**
	 * Constructor.
	 *
	 * @param OcrService $ocr The Tesseract engine and the admin's OCR settings.
	 * @param OcrResultRepository $results The ocrResult rows.
	 * @param ITimeFactory $time The clock.
	 * @param LoggerInterface $logger The logger.
	 */
	public function __construct(
		private readonly OcrService $ocr,
		private readonly OcrResultRepository $results,
		private readonly ITimeFactory $time,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Whether OCR can run on this instance at all.
	 *
	 * @return array{enabled: bool, tesseractAvailable: bool, available: bool} The capability.
	 *
	 * @spec openspec/changes/archive/2026-09-29-ocr-trigger-surface/tasks.md#task-3.1
	 */
	public function capability(): array {
		$enabled = $this->ocr->isOcrEnabled();
		$installed = $this->ocr->isTesseractAvailable();

		return [
			'enabled' => $enabled,
			'tesseractAvailable' => $installed,
			'available' => $enabled === true && $installed === true,
		];

	}//end capability()

	/**
	 * Whether a MIME type is one OCR is offered for: an image, or a PDF.
	 *
	 * @param string $mimeType The MIME type.
	 *
	 * @return bool True for an OCR candidate.
	 *
	 * @spec openspec/changes/archive/2026-09-29-ocr-trigger-surface/tasks.md#task-2.4
	 */
	public function isCandidate(string $mimeType): bool {
		// A PDF with no text is a candidate; needsOcr() without text answers that.
		return $this->ocr->needsOcr(mimeType: $mimeType);

	}//end isCandidate()

	/**
	 * Run OCR on a file and record the result.
	 *
	 * @param File $file The file, already resolved and access-checked by the caller.
	 * @param string $trigger `manual` or `fallback`.
	 *
	 * @return array{processed: bool, reason: string|null, text: string, result: array<string, mixed>|null}
	 *         `text` is for the pipeline only; no caller may return it.
	 *
	 * @spec openspec/changes/archive/2026-09-29-ocr-trigger-surface/tasks.md#task-2.1
	 */
	public function run(File $file, string $trigger): array {
		$reason = $this->refusal(mimeType: $file->getMimeType());
		if ($reason !== null) {
			return $this->skipped(reason: $reason);
		}

		try {
			$recognised = $this->ocr->processNode(file: $file);
		} catch (Throwable $e) {
			$this->logger->warning(
				'OCR failed',
				['fileId' => $file->getId(), 'exception' => $e->getMessage()]
			);
			return $this->skipped(reason: self::SKIP_FAILED);
		}

		$text = trim($recognised['text']);
		if ($text === '') {
			return $this->skipped(reason: self::SKIP_NO_TEXT);
		}

		$result = $this->results->saveForFile(
			result: [
				'fileId' => (int) $file->getId(),
				'confidence' => round(max(0.0, min(100.0, $recognised['confidence'])), 1),
				'languages' => $recognised['languages'],
				'dpi' => $recognised['dpi'],
				'textLength' => mb_strlen($text),
				'ocrProcessedAt' => $this->time->getDateTime()->format(DATE_ATOM),
				'triggeredBy' => $trigger,
				'engineVersion' => $this->engineVersion(),
			]
		);

		return ['processed' => true, 'reason' => null, 'text' => $text, 'result' => $result];

	}//end run()

	/**
	 * Why OCR cannot run on a file of this type now, or null when it can.
	 *
	 * @param string $mimeType The MIME type.
	 *
	 * @return string|null One of the SKIP_* reasons, or null.
	 */
	private function refusal(string $mimeType): ?string {
		if ($this->ocr->isOcrEnabled() === false) {
			return self::SKIP_DISABLED;
		}

		if ($this->ocr->isTesseractAvailable() === false) {
			return self::SKIP_NO_TESSERACT;
		}

		if ($this->isCandidate(mimeType: $mimeType) === false) {
			return self::SKIP_NOT_CANDIDATE;
		}

		return null;

	}//end refusal()

	/**
	 * A run that did not produce a result.
	 *
	 * @param string $reason Why.
	 *
	 * @return array{processed: false, reason: string, text: string, result: null} The outcome.
	 */
	private function skipped(string $reason): array {
		return ['processed' => false, 'reason' => $reason, 'text' => '', 'result' => null];

	}//end skipped()

	/**
	 * The Tesseract version, as the row records it.
	 *
	 * @return string The first line of `tesseract --version`, e.g. "tesseract 5.3.0", or
	 *                "tesseract" when it is unknown.
	 */
	private function engineVersion(): string {
		$version = trim((string) $this->ocr->getTesseractVersion());
		if ($version === '') {
			return 'tesseract';
		}

		return substr($version, 0, 64);

	}//end engineVersion()
}//end class
