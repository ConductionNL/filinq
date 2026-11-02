<?php

/**
 * Contract of DocumentSanitizationService
 *
 * The public surface of the DocumentSanitizationService service, so callers in other
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
 * Contract of DocumentSanitizationService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface DocumentSanitizationServiceInterface {
	/**
	 * OpenRegister's office sanitizer.
	 */
	public const OFFICE_ENGINE = 'OCA\OpenRegister\Service\File\OfficeDocumentSanitizer';

	/**
	 * Sanitize a file the user can read into a derivative beside it.
	 *
	 * @param int    $fileId  The file.
	 * @param string $userId  Who asks.
	 * @param string $trigger manual, anonymisation or publication.
	 *
	 * @return array<string, mixed> sanitized, sanitizedFileId, sanitizedFileName, report, recordId; or sanitizationSkipped with a reason.
	 *
	 * @throws InvalidArgumentException 404 unreachable, 415 unsupported, 422 encrypted, 500 engine failure.
	 *
	 * @spec openspec/changes/document-sanitization/tasks.md#3-1
	 */
	public function sanitize(int $fileId, string $userId, string $trigger = 'manual'): array;
}//end interface
