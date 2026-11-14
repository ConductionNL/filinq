<?php

/**
 * Contract of PrintJobFileStore
 *
 * The public surface of the PrintJobFileStore service, so callers in other
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

/**
 * Contract of PrintJobFileStore.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface PrintJobFileStoreInterface {
	/**
	 * The app data folder holding every print job PDF.
	 */
	public const FOLDER = 'print-jobs';

	/**
	 * Store one letter of a job.
	 *
	 * @param string $jobId   The job id
	 * @param int    $index   The letter's place in the job
	 * @param string $content The PDF bytes
	 *
	 * @return string The stored file name, to keep on the job.
	 *
	 * @spec openspec/changes/archive/2026-09-29-print-jobs-in-the-app/tasks.md#task-1.2
	 */
	public function put(string $jobId, int $index, string $content): string;

	/**
	 * Read one stored file.
	 *
	 * @param string $name The file name kept on the job
	 *
	 * @return string|null The bytes, or null when the file is not there.
	 *
	 * @spec openspec/changes/archive/2026-09-29-print-jobs-in-the-app/tasks.md#task-1.2
	 */
	public function get(string $name): ?string;

	/**
	 * Pack the PDFs of a job and its manifest into one ZIP.
	 *
	 * @param array<int, string> $names    The stored file names
	 * @param array              $manifest The job manifest
	 *
	 * @return string The ZIP bytes.
	 *
	 * @spec openspec/changes/archive/2026-09-29-print-jobs-in-the-app/tasks.md#task-1.2
	 */
	public function zip(array $names, array $manifest): string;
}//end interface
