<?php

/**
 * Contract of ConformanceService
 *
 * The public surface of the ConformanceService service, so callers in other
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

use OCP\Files\File;
use RuntimeException;
use OCA\Filinq\Exception\VeraPdfException;

/**
 * Contract of ConformanceService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface ConformanceServiceInterface {
	/**
	 * TRIGGER_MANUAL
	 */
	public const TRIGGER_MANUAL = 'manual';

	/**
	 * TRIGGER_VALIDATION
	 */
	public const TRIGGER_VALIDATION = 'validation';

	/**
	 * TRIGGER_CONVERSION
	 */
	public const TRIGGER_CONVERSION = 'conversion';

	/**
	 * Whether a check can run now.
	 *
	 * @return bool True when veraPDF is switched on and answers.
	 *
	 * @spec openspec/changes/archive/2026-09-29-verapdf-validation/tasks.md#task-2.3
	 */
	public function isAvailable(): bool;

	/**
	 * The validator's status for the admin settings row: switched on,
	 * installed, and which version. Not its path on the server.
	 *
	 * @return array{enabled: bool, available: bool, version: string} The status.
	 *
	 * @spec openspec/changes/archive/2026-09-29-verapdf-validation/tasks.md#task-3.2
	 */
	public function status(): array;

	/**
	 * Check a stored file and store the report.
	 *
	 * @param File   $file    The PDF.
	 * @param string $trigger TRIGGER_MANUAL or TRIGGER_VALIDATION.
	 *
	 * @return array<string, mixed> The stored report.
	 *
	 * @throws VeraPdfException When veraPDF gives no verdict.
	 * @throws RuntimeException When the report cannot be stored.
	 *
	 * @spec openspec/changes/archive/2026-09-29-verapdf-validation/tasks.md#task-2.3
	 */
	public function checkFile(File $file, string $trigger): array;

	/**
	 * Check the PDF/A-3 bytes a conversion made, before they are returned.
	 *
	 * @param string   $bytes        The converted PDF.
	 * @param string   $origin       ConformanceGuidance::ORIGIN_RENDERED for HTML, ORIGIN_IMPORTED for a wrapped PDF.
	 * @param int|null $sourceFileId The file converted, when there is one; the report is stored on it.
	 *
	 * @return array<string, mixed> The report, with `stored` saying whether it was kept on the source file.
	 *
	 * @throws VeraPdfException When veraPDF gives no verdict.
	 *
	 * @spec openspec/changes/archive/2026-09-29-verapdf-validation/tasks.md#task-2.5
	 */
	public function checkConversion(string $bytes, string $origin, ?int $sourceFileId): array;

	/**
	 * The reports stored for a file, keyed by subject.
	 *
	 * @param int $fileId The file id.
	 *
	 * @return array<string, array<string, mixed>> The reports.
	 *
	 * @throws RuntimeException When the register cannot be read.
	 *
	 * @spec openspec/changes/archive/2026-09-29-verapdf-validation/tasks.md#task-2.3
	 */
	public function reportsFor(int $fileId): array;
}//end interface
