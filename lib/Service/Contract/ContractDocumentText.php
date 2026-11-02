<?php

/**
 * Contract document text
 *
 * Reads the text of a file the caller can open, locally: plain text as it
 * is, a PDF through poppler's pdftotext when it is installed. Anything else,
 * or a file the caller cannot open, reads as no text.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Contract
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-30-contract-lifecycle-management/tasks.md#2-2
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Contract;

use OCP\Files\File;
use OCP\Files\IRootFolder;
use Throwable;

/**
 * Local text of a contract document.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Contract
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-30-contract-lifecycle-management/specs/contract-lifecycle-management/spec.md#requirement-key-term-extraction-is-suggestion-only-req-ddclm-005
 */
class ContractDocumentText {

	/**
	 * The most text read from one document, in bytes.
	 */
	private const MAX_BYTES = 2000000;

	/**
	 * Constructor.
	 *
	 * @param IRootFolder $rootFolder The file tree.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IRootFolder $rootFolder,
	) {

	}//end __construct()

	/**
	 * The text of a file the user can open, or '' when there is none to read.
	 *
	 * @param string $userId The caller.
	 * @param int    $fileId The Nextcloud file id.
	 *
	 * @return string The text.
	 *
	 * @spec openspec/changes/archive/2026-09-30-contract-lifecycle-management/tasks.md#2-2
	 */
	public function textOf(string $userId, int $fileId): string {
		try {
			$nodes = $this->rootFolder->getUserFolder($userId)->getById($fileId);
		} catch (Throwable) {
			return '';
		}

		$file = ($nodes[0] ?? null);
		if (($file instanceof File) === false || $file->getSize() > self::MAX_BYTES) {
			return '';
		}

		$mime = (string) $file->getMimeType();
		try {
			if (str_starts_with($mime, 'text/') === true) {
				return (string) $file->getContent();
			}

			if ($mime === 'application/pdf') {
				return $this->pdfText(content: (string) $file->getContent());
			}
		} catch (Throwable) {
			return '';
		}

		return '';

	}//end textOf()

	/**
	 * The embedded text of a PDF, through pdftotext; '' when it is not installed.
	 *
	 * @param string $content The PDF bytes.
	 *
	 * @return string The text.
	 */
	private function pdfText(string $content): string {
		$path = tempnam(sys_get_temp_dir(), 'filinq_contract_');
		if ($path === false) {
			return '';
		}

		try {
			file_put_contents($path, $content);
			$output = [];
			$code = 0;
			exec('pdftotext ' . escapeshellarg($path) . ' - 2>/dev/null', $output, $code);
			if ($code !== 0) {
				return '';
			}

			return implode("\n", $output);
		} finally {
			unlink($path);
		}

	}//end pdfText()
}//end class
