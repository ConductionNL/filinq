<?php

/**
 * Contract of RedactionOutputGuard
 *
 * The public surface of the RedactionOutputGuard service, so callers in other
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

use OCA\Filinq\Exception\RedactionNotReviewedException;

/**
 * Contract of RedactionOutputGuard.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface RedactionOutputGuardInterface {
	/**
	 * Stop unless this document has been checked for this run.
	 *
	 * @param int                              $fileId   The document about to be written.
	 * @param array<int, array<string, mixed>> $entities The entities this run found.
	 * @param string                           $etag     The file's etag, when known.
	 *
	 * @return void
	 *
	 * @throws RedactionNotReviewedException When nobody has checked this run.
	 *
	 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
	 */
	public function assertMayWrite(int $fileId, array $entities, string $etag = ''): void;

	/**
	 * Record that a person checked this run, and say which run that was.
	 *
	 * @param int                              $fileId    The document.
	 * @param array<int, array<string, mixed>> $entities  The entities this run found.
	 * @param string                           $checkedBy Who checked it.
	 * @param string                           $etag      The file's etag, when known.
	 * @param string                           $note      What the checker wanted recorded.
	 *
	 * @return array<string, mixed> The stored mark.
	 *
	 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
	 */
	public function markChecked(int $fileId, array $entities, string $checkedBy, string $etag = '', string $note = ''): array;

	/**
	 * The latest mark of each document, keyed by file id, for a status column.
	 *
	 * Only who and when travel: the mark says a run was checked, and whether
	 * that run is still the current one is for the write path to decide.
	 *
	 * @param array<int, int> $fileIds The documents.
	 *
	 * @return array<int, array{checkedBy: string, checkedAt: string, entityCount: int}> The marks found.
	 *
	 * @spec openspec/changes/anonymise-asks-for-the-review-mark/tasks.md#task-3
	 */
	public function marksFor(array $fileIds): array;

	/**
	 * Which of these documents may be written, and why the others may not.
	 *
	 * A batch asks once rather than per document, so the operator gets one
	 * list of what is holding the run up instead of a refusal per file.
	 *
	 * @param array<int, array<string, mixed>> $documents Each with `fileId` and `entities`.
	 *
	 * @return array<string, mixed> `allowed` file ids and `refused` entries.
	 *
	 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
	 */
	public function screen(array $documents): array;
}//end interface
