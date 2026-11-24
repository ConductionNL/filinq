<?php

/**
 * Profile Extraction Service
 *
 * Reads a candidate's profile from a CV file for the app that asks (humaniq's
 * Read CV): the education level, the years of experience and the competence
 * codes the caller lists. It stores nothing: the caller decides what to write
 * (REQ-CVP-001, REQ-CVP-002).
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/extraction-cv-profile/specs/cv-profile-extraction/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCA\Filinq\Service\Extraction\CvProfileParser;
use OCP\AppFramework\Utility\ITimeFactory;

/**
 * The read-only CV profile contract other apps call by class name.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/extraction-cv-profile/specs/cv-profile-extraction/spec.md
 */
class ProfileExtractionService {

	/**
	 * Constructor.
	 *
	 * @param DocumentTextSource $textSource The text of a file the session user can read.
	 * @param CvProfileParser    $parser     The profile rules.
	 * @param ITimeFactory       $time       Today, for a period that is still running.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly DocumentTextSource $textSource,
		private readonly CvProfileParser $parser,
		private readonly ITimeFactory $time,
	) {

	}//end __construct()

	/**
	 * Read the profile of a CV.
	 *
	 * `$data` carries `fileId` (the Nextcloud file id, or a `documentUri`
	 * ending in it) and `competenceCodes`, the codes to look for. The answer
	 * is `{status, educationLevel, experienceYears, competenceCodes, message}`
	 * with status `extracted`, or `unreadable` and no profile when the file
	 * cannot be found, read or yields no text.
	 *
	 * @param array<string, mixed> $data        The request.
	 * @param string               $requestedBy The Nextcloud user who asked (the file is read as the session user).
	 *
	 * @return array{status: string, educationLevel: int|null, experienceYears: float|null, competenceCodes: list<string>, message: string}
	 *
	 * @spec openspec/changes/extraction-cv-profile/specs/cv-profile-extraction/spec.md#REQ-CVP-001
	 * @spec openspec/changes/extraction-cv-profile/specs/cv-profile-extraction/spec.md#REQ-CVP-002
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter) $requestedBy keeps the extractFinancial() call shape; the read is the session user's.
	 */
	public function extractProfile(array $data, string $requestedBy): array {
		$fileId = $this->fileIdOf(data: $data);
		if ($fileId === null) {
			return $this->unreadable(message: 'No CV file was given.');
		}

		$text = trim((string)($this->textSource->resolveText(fileId: $fileId)['text'] ?? ''));
		if ($text === '') {
			return $this->unreadable(message: 'The text of the CV could not be read. It may be a scan without text, or not a file you can open.');
		}

		$codes = array_values(array_filter(array_map('strval', (array)($data['competenceCodes'] ?? [])), static fn (string $code): bool => trim($code) !== ''));
		$profile = $this->parser->parse(text: $text, competenceCodes: $codes, today: $this->time->now());

		return [
			'status' => 'extracted',
			'educationLevel' => $profile['educationLevel'],
			'experienceYears' => $profile['experienceYears'],
			'competenceCodes' => $profile['competenceCodes'],
			'message' => 'The profile was read from the CV.',
		];
	}//end extractProfile()

	/**
	 * The file id of the request: `fileId`, else the trailing number of
	 * `documentUri`; null when neither names a positive id.
	 *
	 * @param array<string, mixed> $data The request.
	 *
	 * @return int|null
	 */
	private function fileIdOf(array $data): ?int {
		$fileId = ($data['fileId'] ?? null);
		if (is_numeric($fileId) === true && (int)$fileId > 0) {
			return (int)$fileId;
		}

		if (preg_match('/(\d+)\s*$/', (string)($data['documentUri'] ?? ''), $match) === 1 && (int)$match[1] > 0) {
			return (int)$match[1];
		}

		return null;
	}//end fileIdOf()

	/**
	 * The answer without a profile.
	 *
	 * @param string $message Why.
	 *
	 * @return array{status: string, educationLevel: null, experienceYears: null, competenceCodes: list<string>, message: string}
	 */
	private function unreadable(string $message): array {
		return ['status' => 'unreadable', 'educationLevel' => null, 'experienceYears' => null, 'competenceCodes' => [], 'message' => $message];
	}//end unreadable()
}//end class
