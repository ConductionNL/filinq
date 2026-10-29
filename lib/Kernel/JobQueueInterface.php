<?php

/**
 * Filinq job queue
 *
 * Queues Filinq's background jobs by name, so a service can schedule work
 * without naming the job class that does it.
 *
 * @category  Kernel
 * @package   OCA\Filinq\Kernel
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

namespace OCA\Filinq\Kernel;

/**
 * Adds and removes Filinq background jobs by name.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface JobQueueInterface {
	public const BATCH_CORRESPONDENCE = 'batch-correspondence';
	public const BATCH_DOCUMENT       = 'batch-document';
	public const BATCH_PRINT          = 'batch-print';
	public const BULK_SIGNING         = 'bulk-signing';
	public const FOLDER_EXTRACTION    = 'folder-extraction';
	public const INTAKE_OCR           = 'intake-ocr';

	/**
	 * Queue a job.
	 *
	 * @param string $job One of the job name constants.
	 * @param array<string, mixed> $argument The job argument.
	 *
	 * @return void
	 *
	 * @throws \InvalidArgumentException When the job name is unknown.
	 *
	 * @spec exclude Structural contract, no functional requirement.
	 */
	public function add(string $job, array $argument): void;

	/**
	 * Remove a queued job.
	 *
	 * @param string $job One of the job name constants.
	 * @param array<string, mixed> $argument The argument the job was queued with.
	 *
	 * @return void
	 *
	 * @throws \InvalidArgumentException When the job name is unknown.
	 *
	 * @spec exclude Structural contract, no functional requirement.
	 */
	public function remove(string $job, array $argument): void;
}//end interface
