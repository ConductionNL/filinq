<?php

/**
 * Contract of DossierManagementService
 *
 * The public surface of the DossierManagementService service, so callers in other
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

use RuntimeException;

/**
 * Contract of DossierManagementService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface DossierManagementServiceInterface {
	/**
	 * Every dossier the caller can read, with its live context.
	 *
	 * @return array<int, array<string, mixed>> Index rows.
	 *
	 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
	 */
	public function index(): array;

	/**
	 * One dossier with its documents, grondslagen, batch runs and publication state.
	 *
	 * @param string $dossierId The dossier object UUID.
	 *
	 * @return array<string, mixed> The aggregated dossier.
	 *
	 * @throws RuntimeException When the caller cannot read the dossier.
	 *
	 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
	 */
	public function detail(string $dossierId): array;

	/**
	 * Create a dossier bound to a home folder.
	 *
	 * @param string $name The dossier name; also the home-folder name.
	 * @param string $description Optional free text.
	 * @param array<int, string> $bases Grondslag slugs.
	 *
	 * @return array<string, mixed> The created dossier's detail shape.
	 *
	 * @throws RuntimeException When the name is empty or the save fails.
	 *
	 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
	 */
	public function create(string $name, string $description = '', array $bases = []): array;

	/**
	 * Rename a dossier and keep its bound home folder in sync.
	 *
	 * The object rename ALWAYS succeeds; the folder rename is best-effort and
	 * reports rather than blocks. A caller without write permission, or a
	 * sibling already holding the target name, must not cost the operator
	 * their rename — and a name collision must never merge two folders.
	 *
	 * @param string $dossierId The dossier object UUID.
	 * @param string $name The new name.
	 *
	 * @return array<string, mixed> The detail shape plus a `folderWarning` key.
	 *
	 * @throws RuntimeException When the name is empty or the caller cannot read the dossier.
	 *
	 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
	 */
	public function rename(string $dossierId, string $name): array;

	/**
	 * Move a dossier to a new lifecycle status.
	 *
	 * @param string $dossierId The dossier object UUID.
	 * @param string $status The target status.
	 *
	 * @return array<string, mixed> The refreshed detail shape.
	 *
	 * @throws RuntimeException When the transition is not declared, or the save is refused.
	 *
	 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
	 */
	public function transition(string $dossierId, string $status): array;

	/**
	 * Add an existing file to a dossier by reference, without moving it.
	 *
	 * Link semantics: the file stays where it is and the dossier gains a
	 * reference, so the same document can be a member of several dossiers.
	 *
	 * @param string $dossierId The dossier object UUID.
	 * @param int $fileId The Nextcloud file node id.
	 *
	 * @return array<string, mixed> The refreshed detail shape.
	 *
	 * @throws RuntimeException When the caller cannot read the dossier or the file.
	 *
	 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
	 */
	public function linkDocument(string $dossierId, int $fileId): array;

	/**
	 * Remove a document from a dossier.
	 *
	 * Two different operations behind one verb, and the caller is told which
	 * one it will be by {@see self::removalMode()} before confirming:
	 *
	 *  - The file lives in this dossier's home folder and no other dossier
	 *    references it → it is moved to the TRASHBIN, recoverable. Never a
	 *    hard delete.
	 *  - The file is a reference (lives elsewhere, or another dossier also
	 *    holds it) → only this dossier's membership reference is dropped and
	 *    the file is untouched.
	 *
	 * @param string $dossierId The dossier object UUID.
	 * @param int $fileId The Nextcloud file node id.
	 *
	 * @return array<string, mixed> The refreshed detail shape.
	 *
	 * @throws RuntimeException When the caller cannot read the dossier, or with 423 while it is under a legal hold.
	 *
	 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
	 */
	public function removeDocument(string $dossierId, int $fileId): array;

	/**
	 * Whether removing this file would trash it or merely unlink it.
	 *
	 * Exposed so the confirmation dialog can SAY which one it is. A confirm
	 * that reads "remove?" for both is how an operator deletes a file they
	 * meant to unlink.
	 *
	 * @param string $dossierId The dossier object UUID.
	 * @param int $fileId The Nextcloud file node id.
	 *
	 * @return string Either 'trash' or 'unlink'.
	 *
	 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
	 */
	public function removalMode(string $dossierId, int $fileId): string;
}//end interface
