<?php

/**
 * Grondslagen Summary Attacher
 *
 * Attaches the rendered grondslagen summary to a freshly-anonymised file:
 * appended in place for a PDF, written beside it as `<base>_grondslagen.pdf`
 * otherwise. Failure is non-fatal and is reported as a structured `warning`
 * field on the result info.
 *
 * Extracted from AnonymizationService as a pure refactor; behaviour unchanged.
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
 * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use Exception;
use OCP\Files\File;
use Psr\Log\LoggerInterface;

/**
 * Attaches the grondslagen summary to an anonymised file.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-2
 */
class GrondslagenSummaryAttacher {
	/**
	 * Constructor for GrondslagenSummaryAttacher
	 *
	 * @param LoggerInterface $logger Logger for the non-fatal failure warning.
	 * @param LegalBasesSummaryService $grondslagenSummary Renderer for the per-document grondslagen
	 *                                                     summary page.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly LoggerInterface $logger,
		private readonly LegalBasesSummaryService $grondslagenSummary,
	) {

	}//end __construct()

	/**
	 * Render and attach the grondslagen summary to a freshly-anonymised file.
	 *
	 * If the anonymised file is a PDF, the summary is appended to it in place (one
	 * extra page); for other formats it is saved as a separate
	 * `<base>_grondslagen.pdf` beside the anonymised file.
	 *
	 * Summary-step failure is **non-fatal**: the anonymise call still returns
	 * success and the result gets a structured `warning` field, so the caller can
	 * surface the issue without rolling back the anonymisation.
	 *
	 * @param mixed $anonymisedNode The Node/File returned by OR's anonymizeDocument.
	 * @param int $sourceFileId The pre-anonymisation source file id (used to look
	 *                          up the EntityRelation rows that carry the bases).
	 * @param array<string, mixed> $resultInfo The current result info — extended with the
	 *                                         summary's `summaryFileId` / `warning` fields and
	 *                                         returned.
	 * @param array<string, string> $placeholderMap OpenRegister's per-entity placeholder map
	 *                                              (global entity id → emitted placeholder, e.g.
	 *                                              `"7" => "[PERSOON: 1]"`) so the summary renders
	 *                                              the SAME placeholder the document carries. Empty
	 *                                              → summary uses its own scope-local map or omits.
	 *
	 * @return array<string, mixed> The (possibly-extended) result info.
	 *
	 * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-2
	 */
	public function attachGrondslagenSummary(mixed $anonymisedNode, int $sourceFileId, array $resultInfo, array $placeholderMap = []): array {
		if (($anonymisedNode instanceof File) === false) {
			$resultInfo['warning'] = 'grondslagen_summary_skipped: anonymised result is not a File node';
			return $resultInfo;
		}

		$mime = $anonymisedNode->getMimeType();
		$isPdf = ($mime === 'application/pdf');

		try {
			if ($isPdf === true) {
				$this->grondslagenSummary->appendSummaryToPdf(
					anonymisedFile: $anonymisedNode,
					sourceFileId: $sourceFileId,
					placeholderMap: $placeholderMap
				);
				$resultInfo['summaryAppended'] = true;

				return $resultInfo;
			}

			$summaryFile = $this->grondslagenSummary->renderSummaryBesideFile(
				anonymisedFile: $anonymisedNode,
				sourceFileId: $sourceFileId,
				placeholderMap: $placeholderMap
			);
			$resultInfo['summaryAppended'] = false;
			$resultInfo['summaryFileId'] = $summaryFile->getId();
			$resultInfo['summaryFilePath'] = $summaryFile->getPath();
		} catch (Exception $e) {
			$this->logger->warning(
				'Grondslagen summary attach failed',
				[
					'fileId' => $anonymisedNode->getId(),
					'sourceFileId' => $sourceFileId,
					'isPdf' => $isPdf,
					'error' => $e->getMessage(),
				]
			);
			$resultInfo['warning'] = 'grondslagen_summary_failed: ' . $e->getMessage();
		}//end try

		return $resultInfo;
	}//end attachGrondslagenSummary()
}//end class
