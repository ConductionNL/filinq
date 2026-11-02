<?php

/**
 * Contract of EmailIngestionService
 *
 * The public surface of the EmailIngestionService service, so callers in other
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
 * Contract of EmailIngestionService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface EmailIngestionServiceInterface {
	/**
	 * REASON_UNSUPPORTED_FORMAT
	 */
	public const REASON_UNSUPPORTED_FORMAT = 'unsupported-format';

	/**
	 * REASON_UNPARSEABLE
	 */
	public const REASON_UNPARSEABLE = 'unparseable';

	/**
	 * REASON_NO_DOSSIER_FOLDER
	 */
	public const REASON_NO_DOSSIER_FOLDER = 'dossier-folder-unavailable';

	/**
	 * REASON_FILING_FAILED
	 */
	public const REASON_FILING_FAILED = 'filing-failed';

	/**
	 * SOURCE_WATCHED_FOLDER
	 */
	public const SOURCE_WATCHED_FOLDER = 'watched-folder';

	/**
	 * SOURCE_MANUAL
	 */
	public const SOURCE_MANUAL = 'manual';

	/**
	 * One tick: file up to $limit emails from the watched inboxes.
	 *
	 * @param int    $limit  The budget.
	 * @param string $source watched-folder for the job, manual for a re-scan by hand.
	 *
	 * @return array{processed: int, filed: int, failed: int, duplicates: int} What the tick did.
	 *
	 * @spec openspec/changes/archive/2026-10-01-email-ingestion/tasks.md#2-1
	 */
	public function scan(int $limit, string $source = 'watched-folder'): array;

	/**
	 * Convert a filed email that was not converted; the record is updated, never duplicated.
	 *
	 * @param string $uuid The record.
	 *
	 * @return array<string, mixed> The record, with pdfFileRef when the cascade converted it.
	 *
	 * @throws InvalidArgumentException 404 for an unknown record or a gone file, 409 for a failed one.
	 *
	 * @spec openspec/changes/archive/2026-10-01-email-ingestion/tasks.md#2-3
	 */
	public function retryConversion(string $uuid): array;
}//end interface
