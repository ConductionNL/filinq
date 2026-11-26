<?php

/**
 * Batch Resume Selection: whether a batch can be resumed, and which files a resume queues again.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/redaction-at-scale/tasks.md#5.6
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

/**
 * An errored or cancelled batch resumes every file that did not succeed. A
 * completed batch resumes only while it has files in `error`, and then only
 * those: per-file failures do not set a batch to `error`, so without this a
 * failed file could only be redone by starting a new batch.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/redaction-at-scale/tasks.md#5.6
 */
final class BatchResumeSelection {

	/**
	 * Batch statuses whose every unfinished file is queued again.
	 *
	 * @var array<int, string>
	 */
	private const RESUMABLE = ['error', 'cancelled'];

	/**
	 * Per-file status of a file that failed.
	 *
	 * @var string
	 */
	private const FILE_FAILED = 'error';

	/**
	 * Whether the batch can be resumed, and the file statuses a resume queues
	 * again (`only` null: every file that did not succeed).
	 *
	 * @param array<string, mixed> $batch The stored batch.
	 *
	 * @return array{resumable: bool, only: array<int, string>|null}
	 *
	 * @spec openspec/changes/redaction-at-scale/tasks.md#5.6
	 * @spec openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#requirement-idempotent-resume-req-ddras-005
	 */
	public static function forBatch(array $batch): array {
		$status = (string)($batch['status'] ?? '');
		if (in_array($status, self::RESUMABLE, true) === true) {
			return ['resumable' => true, 'only' => null];
		}

		$failed = array_filter(
			(array)($batch['files'] ?? []),
			static fn ($file): bool => is_array($file) === true && ($file['status'] ?? '') === self::FILE_FAILED
		);

		return ['resumable' => ($status === 'completed' && $failed !== []), 'only' => [self::FILE_FAILED]];

	}//end forBatch()
}//end class
