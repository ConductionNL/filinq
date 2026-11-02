<?php

/**
 * Contract of OcrRunService
 *
 * The public surface of the OcrRunService service, so callers in other
 * components depend on this contract instead of the implementation.
 *
 * @category  Kernel
 * @package   OCA\Filinq\Kernel\Contracts
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Kernel\Contracts;

use OCP\Files\File;

/**
 * Contract of OcrRunService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface OcrRunServiceInterface {
	/**
	 * SKIP_DISABLED
	 */
	public const SKIP_DISABLED = 'ocr_disabled';

	/**
	 * SKIP_NO_TESSERACT
	 */
	public const SKIP_NO_TESSERACT = 'tesseract_unavailable';

	/**
	 * SKIP_NOT_CANDIDATE
	 */
	public const SKIP_NOT_CANDIDATE = 'not_candidate';

	/**
	 * SKIP_NO_TEXT
	 */
	public const SKIP_NO_TEXT = 'no_text_recovered';

	/**
	 * SKIP_FAILED
	 */
	public const SKIP_FAILED = 'ocr_failed';

	/**
	 * Whether OCR can run on this instance at all.
	 *
	 * @return array{enabled: bool, tesseractAvailable: bool, available: bool} The capability.
	 *
	 * @spec openspec/changes/archive/2026-09-29-ocr-trigger-surface/tasks.md#task-3.1
	 */
	public function capability(): array;

	/**
	 * Whether a MIME type is one OCR is offered for: an image, or a PDF.
	 *
	 * @param string $mimeType The MIME type.
	 *
	 * @return bool True for an OCR candidate.
	 *
	 * @spec openspec/changes/archive/2026-09-29-ocr-trigger-surface/tasks.md#task-2.4
	 */
	public function isCandidate(string $mimeType): bool;

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
	public function run(File $file, string $trigger): array;
}//end interface
