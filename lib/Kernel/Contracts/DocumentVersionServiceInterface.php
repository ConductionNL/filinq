<?php

/**
 * Contract of DocumentVersionService
 *
 * The public surface of the DocumentVersionService service, so callers in other
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

use OCA\Filinq\Exception\ComparisonException;

/**
 * Contract of DocumentVersionService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface DocumentVersionServiceInterface {
	/**
	 * List a document's Nextcloud file versions, newest first.
	 *
	 * @param int $fileId The Nextcloud file id.
	 * @param int $limit Maximum entries to return.
	 * @param int $offset Entries to skip (pagination).
	 *
	 * @return array<int, array<string, mixed>> The version entries (newest first).
	 *
	 * @throws ComparisonException 404 (not readable) / 422 (versions-unavailable).
	 *
	 * @spec openspec/specs/document-versions/spec.md
	 */
	public function listVersions(int $fileId, int $limit = 50, int $offset = 0): array;

	/**
	 * Read the bytes of a specific version (for open/download).
	 *
	 * @param int $fileId The Nextcloud file id.
	 * @param int $versionTimestamp The version timestamp (0 = current file).
	 *
	 * @return string The version bytes.
	 *
	 * @throws ComparisonException 404 / 422.
	 *
	 * @spec openspec/specs/document-versions/spec.md
	 */
	public function readVersion(int $fileId, int $versionTimestamp): string;

	/**
	 * Restore a prior version. Requires write access; Nextcloud preserves the
	 * current state as a new version on rollback.
	 *
	 * A final document refuses the restore: FinalDocumentService::assertWritable()
	 * throws DocumentFinalException, which the controller renders as a 409. That
	 * is written here as prose rather than as a second `@throws` tag because
	 * phpmd counts a docblock type towards this class's coupling, and the class
	 * sits on the ceiling.
	 *
	 * @param int $fileId The Nextcloud file id.
	 * @param int $versionTimestamp The version timestamp to restore.
	 *
	 * @return void
	 *
	 * @throws ComparisonException 404 (not writeable) / 422 / 404 (unknown version).
	 *
	 * @spec openspec/specs/document-versions/spec.md
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function restoreVersion(int $fileId, int $versionTimestamp): void;
}//end interface
