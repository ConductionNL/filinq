<?php

/**
 * Contract of FolderBatchService
 *
 * The public surface of the FolderBatchService service, so callers in other
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

use Exception;

/**
 * Contract of FolderBatchService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface FolderBatchServiceInterface {
	/**
	 * Create a batch from an existing Nextcloud folder
	 *
	 * Resolves the folder via either ID or path, enumerates file children
	 * (flat), creates the batch, and fires extraction immediately in the
	 * background. Exactly one of $folderId or $folderPath MUST be provided.
	 *
	 * @param int|null $folderId Node ID of the folder, or null
	 * @param string|null $folderPath Path relative to the user's root folder, or null
	 *
	 * @return array<string, mixed> Batch data with batchId, folderId, folderPath, fileCount, files
	 *
	 * @throws Exception If input is invalid, folder is not found, not a folder, empty, or too large
	 *
	 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-creation-via-multi-file-upload
	 * @spec openspec/changes/folder-batch-accept-folder-id/tasks.md#task-1
	 */
	public function createFolderBatch(?int $folderId = null, ?string $folderPath = null): array;

	/**
	 * Create a batch from a folder and every subfolder below it
	 *
	 * As createFolderBatch(), but the files of every subfolder are included
	 * (the anonymised-output subfolder excepted), each recording its path
	 * below the folder. The batch size cap applies to the whole tree.
	 *
	 * @param int|null $folderId Node ID of the folder, or null
	 * @param string|null $folderPath Path relative to the user's root folder, or null
	 *
	 * @return array<string, mixed> Batch data with batchId, folderId, folderPath, fileCount, files
	 *
	 * @throws Exception If input is invalid, folder is not found, not a folder, empty, or too large
	 *
	 * @spec openspec/changes/redaction-at-scale/specs/folder-batch-analysis/spec.md#requirement-optional-recursive-folder-enumeration-req-ddras-011
	 */
	public function createRecursiveFolderBatch(?int $folderId = null, ?string $folderPath = null): array;
}//end interface
