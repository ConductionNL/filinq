<?php

/**
 * Contract of PeriodicDocumentService
 *
 * The public surface of the PeriodicDocumentService service, so callers in other
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

use DateTimeImmutable;
use RuntimeException;

/**
 * Contract of PeriodicDocumentService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface PeriodicDocumentServiceInterface {
	/**
	 * The schema a schedule lives in.
	 *
	 * @var string
	 */
	public const SCHEMA = 'periodicDocument';

	/**
	 * Run one periodic document now and return the document it produced.
	 *
	 * 🔴 IT GOES THROUGH THE SAME GENERATION PATH AS `POST api/documents/generate`.
	 * The template renders over the view's records (as `records` in the
	 * template, with `view` naming the view), the PDF is stored in the owner's
	 * Files and the generatedDocument entry points at it. A periodic document
	 * that wrote an entry and no file was an entry nobody could open.
	 *
	 * @param array<string, mixed> $schedule The schedule.
	 * @param string               $userId   Whose Files receive the PDF; empty means the schedule's owner.
	 *
	 * @return array<string, mixed> The generatedDocument entry, with the stored file id.
	 *
	 * @throws RuntimeException When the schedule names no view, the view is gone,
	 *                          nobody owns the schedule, or generation fails.
	 *
	 * @spec openspec/specs/document-creatie-sjablonen/spec.md
	 */
	public function run(array $schedule, string $userId = ''): array;

	/**
	 * Run every active schedule whose cadence has come round.
	 *
	 * 🔴 ONE BROKEN SCHEDULE DOES NOT STOP THE OTHERS, AND IT IS WRITTEN DOWN.
	 * A failure lands on that schedule as `lastRunError`, and its
	 * `lastRunDocument` keeps pointing at the last good one.
	 *
	 * @param DateTimeImmutable $now The moment the job runs.
	 *
	 * @return array{ran: int, failed: int, skipped: int} What the sweep did.
	 *
	 * @spec openspec/specs/document-creatie-sjablonen/spec.md
	 */
	public function runDue(DateTimeImmutable $now): array;

	/**
	 * The records a saved view returns, or null when the view is gone.
	 *
	 * @param string $slug The view slug.
	 *
	 * @return array<int, mixed>|null The records.
	 *
	 * @spec openspec/specs/document-creatie-sjablonen/spec.md
	 */
	public function readView(string $slug): ?array;
}//end interface
