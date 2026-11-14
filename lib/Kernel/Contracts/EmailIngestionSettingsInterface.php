<?php

/**
 * Contract of EmailIngestionSettings
 *
 * The public surface of the EmailIngestionSettings service, so callers in other
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
 * Contract of EmailIngestionSettings.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface EmailIngestionSettingsInterface {
	/**
	 * KEY_INBOXES
	 */
	public const KEY_INBOXES = 'filinq.email_ingestion.inbox_folders';

	/**
	 * KEY_FILES_PER_TICK
	 */
	public const KEY_FILES_PER_TICK = 'filinq.email_ingestion.files_per_tick';

	/**
	 * DEFAULT_FILES_PER_TICK
	 */
	public const DEFAULT_FILES_PER_TICK = 25;

	/**
	 * MAX_FILES_PER_TICK
	 */
	public const MAX_FILES_PER_TICK = 500;

	/**
	 * The watched inboxes.
	 *
	 * @return list<array{folderId: int, dossierRef: string}> The mappings; none when unset or unreadable.
	 *
	 * @spec openspec/changes/archive/2026-10-01-email-ingestion/tasks.md#2-1
	 */
	public function inboxes(): array;

	/**
	 * How many files one tick may take.
	 *
	 * @return int Between 1 and MAX_FILES_PER_TICK; 25 when unset or not a number.
	 *
	 * @spec openspec/changes/archive/2026-10-01-email-ingestion/tasks.md#2-4
	 */
	public function filesPerTick(): int;

	/**
	 * Both settings.
	 *
	 * @return array{inboxes: list<array{folderId: int, dossierRef: string}>, filesPerTick: int} The settings.
	 *
	 * @spec openspec/changes/archive/2026-10-01-email-ingestion/tasks.md#2-5
	 */
	public function toArray(): array;

	/**
	 * Store both settings.
	 *
	 * @param array<int, mixed> $inboxes      Rows of {folderId, dossierRef}.
	 * @param int               $filesPerTick The per-tick budget, clamped.
	 *
	 * @return array{inboxes: list<array{folderId: int, dossierRef: string}>, filesPerTick: int} What was stored.
	 *
	 * @throws InvalidArgumentException With code 400 for a row without folder or dossier, or a folder mapped twice.
	 *
	 * @spec openspec/changes/archive/2026-10-01-email-ingestion/tasks.md#2-5
	 */
	public function update(array $inboxes, int $filesPerTick): array;
}//end interface
