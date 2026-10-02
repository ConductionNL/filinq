<?php

/**
 * Sanitization status
 *
 * What earlier sanitization runs on a file removed, and whether the file is
 * itself a sanitized derivative: the signal the publication hand-off reads.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Sanitization
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/document-sanitization/tasks.md#3-5
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Sanitization;

use InvalidArgumentException;
use OCP\Files\IRootFolder;
use Throwable;

/**
 * Reads the sanitization records of one file.
 */
class SanitizationStatus {

	/**
	 * Constructor.
	 *
	 * @param IRootFolder                  $rootFolder The files, read through the user's folder.
	 * @param SanitizationRecordRepository $records    The run records.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IRootFolder $rootFolder,
		private readonly SanitizationRecordRepository $records,
	) {

	}//end __construct()

	/**
	 * The status of a file the user can read.
	 *
	 * @param int    $fileId The file.
	 * @param string $userId The user.
	 *
	 * @return array{fileId: int, sanitized: bool, runs: list<array<string, mixed>>} Whether it is a sanitized derivative, and the runs on it.
	 *
	 * @throws InvalidArgumentException 404 when the user cannot read the file.
	 *
	 * @spec openspec/changes/document-sanitization/tasks.md#3-5
	 */
	public function forFile(int $fileId, string $userId): array {
		try {
			$nodes = $this->rootFolder->getUserFolder($userId)->getById($fileId);
		} catch (Throwable) {
			$nodes = [];
		}

		if ($nodes === []) {
			throw new InvalidArgumentException(message: 'not_found', code: 404);
		}

		$runs = array_map(
			static fn (array $record): array => [
				'sanitizedFileId' => (int) ($record['sanitizedFileId'] ?? 0),
				'trigger'         => (string) ($record['trigger'] ?? ''),
				'engine'          => (string) ($record['engine'] ?? ''),
				'report'          => (array) ($record['report'] ?? []),
				'sanitizedAt'     => (string) ($record['sanitizedAt'] ?? ''),
				'sanitizedBy'     => (string) ($record['sanitizedBy'] ?? ''),
			],
			$this->records->forSourceFile(fileId: $fileId)
		);

		return [
			'fileId'    => $fileId,
			'sanitized' => $this->isSanitized(fileId: $fileId),
			'runs'      => $runs,
		];

	}//end forFile()

	/**
	 * Whether the file is the derivative of a sanitization run.
	 *
	 * @param int $fileId The file.
	 *
	 * @return bool True when a record names it as the sanitized file.
	 *
	 * @spec openspec/changes/document-sanitization/tasks.md#3-5
	 */
	public function isSanitized(int $fileId): bool {
		foreach ($this->records->forSanitizedFile(fileId: $fileId) as $record) {
			if ((int) ($record['sanitizedFileId'] ?? 0) === $fileId) {
				return true;
			}
		}

		return false;

	}//end isSanitized()
}//end class
