<?php

/**
 * Contract of DigitisationWorklist
 *
 * The public surface of the DigitisationWorklist service, so callers in other
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

use InvalidArgumentException;

/**
 * Contract of DigitisationWorklist.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface DigitisationWorklistInterface {
	/**
	 * A project as the caller may read it, with its `id`, or null.
	 *
	 * @param string $projectId The digitisationProject.
	 *
	 * @return array<string, mixed>|null
	 *
	 * @spec openspec/changes/archive/2026-11-25-intake-paper-archive-digitisation/specs/paper-archive-digitisation/spec.md#requirement-an-archivist-runs-a-digitisation-project-req-pad-001
	 */
	public function project(string $projectId): ?array;

	/**
	 * The originals of a project, of one box when a box is given.
	 *
	 * @param string      $projectId The digitisationProject.
	 * @param string|null $box       The box, or null for every box.
	 *
	 * @return array<int, array<string, mixed>> Each with its `id`.
	 *
	 * @spec openspec/changes/archive/2026-11-25-intake-paper-archive-digitisation/specs/paper-archive-digitisation/spec.md#requirement-a-separator-sheet-names-the-original-it-precedes-req-pad-002
	 */
	public function originals(string $projectId, ?string $box = null): array;

	/**
	 * The originals of a project counted per box and state.
	 *
	 * Each original counts once: a rejected scan counts as rejected until the
	 * original is scanned again, also after it went back to `registered`.
	 * Boxes the project names but holds no original yet are listed with zeros,
	 * in the project's order, before boxes only the originals name.
	 *
	 * @param array<string, mixed> $project The project, with its `id`.
	 *
	 * @return array<int, array<string, mixed>> Rows of id (the box), project, box, registered, scanned, checked, rejected and total.
	 *
	 * @spec openspec/changes/archive/2026-11-25-intake-paper-archive-digitisation/specs/paper-archive-digitisation/spec.md#requirement-an-archivist-runs-a-digitisation-project-req-pad-001
	 */
	public function boxCounts(array $project): array;

	/**
	 * Record a checker's verdict on a scanned original.
	 *
	 * Passed: the original is `checked`. Rejected: the note is required, the
	 * original goes `rejected` and then back to `registered` for a rescan, both
	 * through the declared lifecycle transitions.
	 *
	 * @param string $originalId The paperOriginal.
	 * @param string $result     passed or rejected.
	 * @param string $note       The checker's note.
	 * @param string $checkedBy  The checker's user id.
	 *
	 * @return array<string, mixed>|null The original as saved, or null when the caller cannot read it.
	 *
	 * @throws InvalidArgumentException When the verdict is unknown, a rejection has no note or the original is not waiting for a check.
	 *
	 * @spec openspec/changes/archive/2026-11-25-intake-paper-archive-digitisation/specs/paper-archive-digitisation/spec.md#requirement-a-sampled-share-is-checked-by-a-person-req-pad-004
	 */
	public function check(string $originalId, string $result, string $note, string $checkedBy): ?array;
}//end interface
