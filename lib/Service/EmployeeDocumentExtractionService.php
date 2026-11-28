<?php

/**
 * Employee Document Extraction Service
 *
 * Reads an employment contract or an identity document for the app that asks
 * (humaniq's Read document on an onboarding case): each value with its
 * confidence. It stores nothing; the caller decides what to write
 * (REQ-EDE-001, REQ-EDE-002, REQ-EDE-003).
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-11-24-extraction-employee-document/specs/employee-document-extraction/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCA\Filinq\Service\Extraction\EmploymentContractParser;
use OCA\Filinq\Service\Extraction\MrzParser;
use OCP\AppFramework\Utility\ITimeFactory;

/**
 * The read-only employee document contract other apps call by class name.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-11-24-extraction-employee-document/specs/employee-document-extraction/spec.md
 */
class EmployeeDocumentExtractionService {

	/**
	 * The document types this service reads.
	 */
	public const DOC_TYPES = ['employment-contract', 'identity-document'];

	/**
	 * Constructor.
	 *
	 * @param DocumentTextSource       $textSource The text of a file the session user can read.
	 * @param MrzParser                $mrz        The identity document zone.
	 * @param EmploymentContractParser $contract   The contract terms.
	 * @param ITimeFactory             $time       Today, for a two-digit birth year.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly DocumentTextSource $textSource,
		private readonly MrzParser $mrz,
		private readonly EmploymentContractParser $contract,
		private readonly ITimeFactory $time,
	) {

	}//end __construct()

	/**
	 * Read an employee document.
	 *
	 * `$data` carries `fileId`, the Nextcloud file id, and `docType`,
	 * `employment-contract` or `identity-document`. The answer is
	 * `{status, docType, fields, message}`: `fields` maps each value read to
	 * `{value, confidence}`; status `extracted`, `unreadable` when the file
	 * has no text or nothing could be read from it, or `refused` for another
	 * document type.
	 *
	 * @param array<string, mixed> $data        The request.
	 * @param string               $requestedBy The Nextcloud user who asked (the file is read as the session user).
	 *
	 * @return array{status: string, docType: string, fields: array<string, array{value: string|float, confidence: float}>, message: string}
	 *
	 * @spec openspec/changes/archive/2026-11-24-extraction-employee-document/specs/employee-document-extraction/spec.md#REQ-EDE-003
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter) $requestedBy keeps the extractFinancial() call shape; the read is the session user's.
	 */
	public function extractEmployeeDocument(array $data, string $requestedBy): array {
		$docType = (string)($data['docType'] ?? '');
		if (in_array($docType, self::DOC_TYPES, true) === false) {
			return $this->answer(status: 'refused', docType: $docType, message: 'The document type must be employment-contract or identity-document.');
		}

		$fileId = ($data['fileId'] ?? null);
		$text = '';
		if (is_numeric($fileId) === true && (int)$fileId > 0) {
			$text = trim((string)($this->textSource->resolveText(fileId: (int)$fileId)['text'] ?? ''));
		}

		if ($text === '') {
			$message = 'The text of the document could not be read. It may be a scan without text, or not a file you can open.';
			return $this->answer(status: 'unreadable', docType: $docType, message: $message);
		}

		$fields = $this->contract->parse(text: $text);
		if ($docType === 'identity-document') {
			$fields = ($this->mrz->parse(text: $text, today: $this->time->now()) ?? []);
		}

		if ($fields === []) {
			return $this->answer(status: 'unreadable', docType: $docType, message: 'Nothing could be read from the document. Type the values instead.');
		}

		$message = 'The document was read. Check every value before you use it.';
		return $this->answer(status: 'extracted', docType: $docType, message: $message, fields: $fields);
	}//end extractEmployeeDocument()

	/**
	 * An answer.
	 *
	 * @param string                                                      $status  What happened.
	 * @param string                                                      $docType The document type asked for.
	 * @param string                                                      $message What to tell the reader.
	 * @param array<string, array{value: string|float, confidence: float}> $fields  The values read.
	 *
	 * @return array{status: string, docType: string, fields: array<string, array{value: string|float, confidence: float}>, message: string}
	 */
	private function answer(string $status, string $docType, string $message, array $fields = []): array {
		return ['status' => $status, 'docType' => $docType, 'fields' => $fields, 'message' => $message];
	}//end answer()
}//end class
