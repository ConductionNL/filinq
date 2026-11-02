<?php

/**
 * Contract of OcrResultRepository
 *
 * The public surface of the OcrResultRepository service, so callers in other
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
 * Contract of OcrResultRepository.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface OcrResultRepositoryInterface {
	/**
	 * SCHEMA
	 */
	public const SCHEMA = 'ocrResult';

	/**
	 * The result of the last run on a file, or null when it never ran.
	 *
	 * @param int $fileId The Nextcloud file id.
	 *
	 * @return array<string, mixed>|null The row, with its uuid.
	 *
	 * @throws RuntimeException When OpenRegister cannot be read.
	 *
	 * @spec openspec/changes/archive/2026-09-29-ocr-trigger-surface/tasks.md#task-2.4
	 */
	public function findForFile(int $fileId): ?array;

	/**
	 * Store a run's result, replacing the file's previous one.
	 *
	 * @param array<string, mixed> $result The row: fileId, confidence, languages, dpi,
	 *                                     textLength, ocrProcessedAt, triggeredBy, engineVersion.
	 *
	 * @return array<string, mixed> The stored row, with its uuid.
	 *
	 * @throws RuntimeException When OpenRegister refuses the write.
	 *
	 * @spec openspec/changes/archive/2026-09-29-ocr-trigger-surface/tasks.md#task-2.1
	 */
	public function saveForFile(array $result): array;
}//end interface
