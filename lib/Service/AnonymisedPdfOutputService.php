<?php

/**
 * Anonymised PDF Output Service
 *
 * Owns the PDF-conversion gate that runs on the anonymised intermediate after
 * OpenRegister has produced it: it decides whether the cascade fires at all
 * (`outputFormat` + "not already a PDF" mime check), performs the conversion
 * through PdfConversionService, rolls the intermediate back when the cascade is
 * exhausted, and — for `pdf-only` — best-effort deletes the native intermediate
 * once the PDF exists.
 *
 * Extracted from AnonymizationService so that class stays a thin orchestrator;
 * the behaviour is unchanged.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/specs/anonymization/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCA\Filinq\Exception\ConversionFailedException;
use OCP\Files\File;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Runs the PDF-conversion gate on an anonymised intermediate.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/anonymization/spec.md
 */
class AnonymisedPdfOutputService {
	/**
	 * Constructor for AnonymisedPdfOutputService
	 *
	 * @param LoggerInterface $logger Logger for the rollback / cleanup warnings.
	 * @param PdfConversionService $pdfConversion Cascade orchestrator that converts the
	 *                                            anonymised intermediate to PDF.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly LoggerInterface $logger,
		private readonly PdfConversionService $pdfConversion,
	) {

	}//end __construct()

	/**
	 * Run the PDF-conversion gate on the anonymised intermediate.
	 *
	 * Fires when outputFormat requests a PDF ('pdf-only' or 'pdf') AND the anonymised
	 * result is not already a PDF. On failure the un-converted intermediate is
	 * deleted and the typed exception re-thrown for the controller's 422; on success
	 * in 'pdf-only' mode the native intermediate is best-effort deleted.
	 *
	 * @param mixed $result The anonymised node returned by OpenRegister.
	 * @param string $outputFormat Output format: 'pdf-only', 'pdf' or 'preserve'.
	 * @param int $fileId The source file id (for the log context).
	 *
	 * @return mixed The converted PDF node, or the untouched result when the gate does not fire.
	 *
	 * @throws ConversionFailedException When the cascade could not convert the intermediate.
	 *
	 * @spec openspec/specs/anonymization/spec.md
	 */
	public function convertResultToPdf(mixed $result, string $outputFormat, int $fileId): mixed {
		if (in_array($outputFormat, ['pdf-only', 'pdf'], true) === false
			|| $result instanceof File === false
		) {
			return $result;
		}

		// When the result is already a PDF the cascade is skipped, so there is
		// no native intermediate to delete and 'pdf-only' behaves identically
		// to 'pdf'.
		$resultMime = (string)$result->getMimeType();
		if ($resultMime !== 'application/pdf') {
			$result = $this->runPdfCascade(
				result: $result,
				outputFormat: $outputFormat,
				fileId: $fileId
			);
		}

		return $result;
	}//end convertResultToPdf()

	/**
	 * Convert the native anonymised intermediate to PDF, with rollback.
	 *
	 * @param mixed $result The native anonymised node.
	 * @param string $outputFormat Output format: 'pdf-only' or 'pdf'.
	 * @param int $fileId The source file id (for the log context).
	 *
	 * @return mixed The converted PDF node.
	 *
	 * @throws ConversionFailedException When the cascade could not convert the intermediate.
	 *
	 * @spec openspec/specs/anonymization/spec.md
	 */
	private function runPdfCascade(mixed $result, string $outputFormat, int $fileId): mixed {
		// Capture the native anonymised node BEFORE $result is reassigned to
		// the converted PDF — 'pdf-only' deletes it after a successful
		// conversion.
		$nativeIntermediate = $result;
		try {
			$result = $this->pdfConversion->convertToPdf($result);
		} catch (ConversionFailedException $e) {
			$this->logger->warning(
				'PDF conversion failed; rolling back anonymised intermediate.',
				[
					'fileId' => $fileId,
					'attempts' => $e->getAttempts(),
				]
			);
			// Best-effort rollback. If delete fails, log and continue —
			// re-throwing is more important than leaving the operator in a
			// partial state that they CAN inspect (they sent a PDF outputFormat
			// and got 422, so the expectation is "no file written"). $result
			// still points at the un-converted native intermediate here, as the
			// reassignment above only runs on success.
			try {
				$result->delete();
			} catch (Throwable $deleteError) {
				$this->logger->warning(
					'Rollback delete failed; orphaned anonymised file remains.',
					[
						'fileId' => $fileId,
						'exception' => get_class($deleteError),
						'message' => $deleteError->getMessage(),
					]
				);
			}

			throw $e;
		}//end try

		// 'pdf-only': the conversion succeeded and the PDF is the referenced
		// output, so the native intermediate is now un-redactable leftover.
		// Best-effort delete it; a failure here MUST NOT fail an
		// otherwise-successful run (mirrors the rollback above). PII-free log
		// (file id + exception metadata only).
		if ($outputFormat === 'pdf-only') {
			try {
				$nativeIntermediate->delete();
			} catch (Throwable $deleteError) {
				$this->logger->warning(
					'pdf-only: failed to delete native anonymised intermediate; orphaned file remains.',
					[
						'fileId' => $fileId,
						'exception' => get_class($deleteError),
						'message' => $deleteError->getMessage(),
					]
				);
			}
		}//end if

		return $result;
	}//end runPdfCascade()
}//end class
