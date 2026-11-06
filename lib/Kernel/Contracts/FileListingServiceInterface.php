<?php

/**
 * Contract of FileListingService
 *
 * The public surface of the FileListingService service, so callers in other
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
 * Contract of FileListingService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface FileListingServiceInterface {
	/**
	 * List all processed files in the user's Filinq folder
	 *
	 * @return array<int, array<string, mixed>> Array of file info
	 *
	 * @spec openspec/specs/anonymization/spec.md
	 */
	public function listProcessedFiles(): array;

	/**
	 * Upload a file to the user's Filinq folder
	 *
	 * @param string $fileName The name of the file to upload
	 * @param string $fileContent The raw file content
	 *
	 * @return array<string, mixed> Upload result with fileId, filePath, fileName, fileSize
	 *
	 * @throws Exception If the upload fails
	 *
	 * @spec openspec/specs/anonymization/spec.md
	 */
	public function uploadFile(string $fileName, string $fileContent): array;
}//end interface
