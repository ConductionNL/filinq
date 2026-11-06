<?php

/**
 * Contract of BatchUploadService
 *
 * The public surface of the BatchUploadService service, so callers in other
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

use OCP\IRequest;

/**
 * Contract of BatchUploadService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface BatchUploadServiceInterface {
	/**
	 * Return the current user's identifier.
	 *
	 * @return string Current user ID, or an empty string when no user is signed in.
	 */
	public function getUserId(): string;

	/**
	 * Collect uploaded files from the request.
	 *
	 * Supports two multipart conventions: indexed fields (`files0`, `files1`, ...)
	 * and a single `files` field carrying an array of uploads.
	 *
	 * @param IRequest $request Incoming HTTP request holding the multipart uploads.
	 *
	 * @return array<int, array{name: string, tmp_name: string, error: int}> List of raw uploaded file entries.
	 *
	 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-creation-via-multi-file-upload
	 */
	public function collectFiles(IRequest $request): array;

	/**
	 * Persist a set of uploaded files and create a new batch record.
	 *
	 * Each entry is uploaded through FileUploadService. Failures are recorded
	 * on the resulting batch file entry rather than aborting the whole batch.
	 *
	 * @param string $userId User identifier to associate with the new batch.
	 * @param array<int, array<string, mixed>> $files Raw uploaded file entries as produced by collectFiles().
	 *
	 * @return array<string, mixed> The new batch record as returned by BatchStateService.
	 *
	 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-creation-via-multi-file-upload
	 */
	public function processBatchUpload(string $userId, array $files): array;
}//end interface
