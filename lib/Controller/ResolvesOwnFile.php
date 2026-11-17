<?php

/**
 * ResolvesOwnFile — the file with an id in the caller's own folder, and the 500 a failed step answers with.
 *
 * The controller that uses this trait holds `$userSession` (IUserSession),
 * `$rootFolder` (IRootFolder), `$logger` (LoggerInterface) and `$l10n` (IL10N).
 * A file outside the caller's folder resolves to null, so it answers 404
 * exactly like a file that does not exist.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#requirement-a-persisted-accessibility-conformance-report-is-stored-per-document-and-re-runnable-req-ddpum-002
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Files\File;
use Throwable;

trait ResolvesOwnFile {
	/**
	 * The file with this id in the caller's own folder.
	 *
	 * @param int $fileId The file id.
	 *
	 * @return File|null The file, or null when there is no caller or no such file for them.
	 *
	 * @spec openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#requirement-a-persisted-accessibility-conformance-report-is-stored-per-document-and-re-runnable-req-ddpum-002
	 */
	private function ownFile(int $fileId): ?File {
		$user = $this->userSession->getUser();
		if ($user === null || $fileId <= 0) {
			return null;
		}

		try {
			$nodes = $this->rootFolder->getUserFolder($user->getUID())->getById($fileId);
		} catch (Throwable) {
			return null;
		}

		$node = ($nodes[0] ?? null);
		if ($node instanceof File) {
			return $node;
		}

		return null;

	}//end ownFile()

	/**
	 * Log a failed step and answer 500 with a sentence for the person who asked.
	 *
	 * @param string    $log     The log message.
	 * @param int       $fileId  The file id.
	 * @param Throwable $e       What failed.
	 * @param string    $message The translated sentence.
	 *
	 * @return JSONResponse The 500 response.
	 *
	 * @spec openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#requirement-a-persisted-accessibility-conformance-report-is-stored-per-document-and-re-runnable-req-ddpum-002
	 */
	private function stepFailed(string $log, int $fileId, Throwable $e, string $message): JSONResponse {
		$this->logger->error(message: $log, context: ['fileId' => $fileId, 'error' => $e->getMessage()]);

		return new JSONResponse(['error' => $message], Http::STATUS_INTERNAL_SERVER_ERROR);

	}//end stepFailed()
}//end trait
