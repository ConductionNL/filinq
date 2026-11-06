<?php

/**
 * Contract of PublicationStore
 *
 * The public surface of the PublicationStore service, so callers in other
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
 * Contract of PublicationStore.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface PublicationStoreInterface {
	/**
	 * Filinq's register.
	 */
	public const REGISTER = 'filinq';

	/**
	 * The record schema.
	 */
	public const RECORD = 'publicationRecord';

	/**
	 * The log schema.
	 */
	public const LOG = 'publicationLogEntry';

	/**
	 * Store a record.
	 *
	 * @param array<string, mixed> $record The record
	 * @param string|null          $uuid   Its id, for an update
	 *
	 * @return array<string, mixed> The stored record, with uuid.
	 *
	 * @spec openspec/changes/archive/2026-09-29-woo-publicatie-pipeline/tasks.md#task-2.2
	 */
	public function saveRecord(array $record, ?string $uuid = null): array;

	/**
	 * Read a record.
	 *
	 * @param string $uuid The id
	 *
	 * @return array<string, mixed>|null The record.
	 *
	 * @spec openspec/changes/archive/2026-09-29-woo-publicatie-pipeline/tasks.md#task-2.2
	 */
	public function findRecord(string $uuid): ?array;

	/**
	 * Every record, or those matching the filters.
	 *
	 * @param array<string, mixed> $filters Property filters
	 *
	 * @return array<int, array<string, mixed>> The records.
	 *
	 * @spec openspec/changes/archive/2026-09-29-woo-publicatie-pipeline/tasks.md#task-3.1
	 */
	public function listRecords(array $filters = []): array;

	/**
	 * Append a log entry. There is no way to change one.
	 *
	 * @param array<string, mixed> $entry The entry
	 *
	 * @return array<string, mixed> The stored entry.
	 *
	 * @spec openspec/changes/archive/2026-09-29-woo-publicatie-pipeline/tasks.md#task-2.5
	 */
	public function appendLog(array $entry): array;

	/**
	 * The log of one record, oldest first.
	 *
	 * @param string $recordUuid The record
	 *
	 * @return array<int, array<string, mixed>> The entries.
	 *
	 * @spec openspec/changes/archive/2026-09-29-woo-publicatie-pipeline/tasks.md#task-2.5
	 */
	public function logFor(string $recordUuid): array;

	/**
	 * The redacted copy of a document, from its anonymisation link.
	 *
	 * @param string $fileId The original's file id
	 *
	 * @return string|null The redacted copy's file id.
	 *
	 * @spec openspec/changes/archive/2026-09-29-woo-publicatie-pipeline/tasks.md#task-2.2
	 */
	public function findRedactedCopy(string $fileId): ?string;

	/**
	 * The anonymisation link of a document's redacted copy, with what the
	 * redaction recorded about it.
	 *
	 * @param string $fileId The source document
	 *
	 * @return array<string, mixed>|null The link, or null when there is no redacted copy.
	 *
	 * @spec openspec/changes/archive/2026-09-29-accessible-redaction-output/tasks.md#task-2.3
	 */
	public function findRedactionLink(string $fileId): ?array;

	/**
	 * Create or update the platform's publication object.
	 *
	 * @param array<string, mixed> $publication The fields to write
	 * @param string|null          $uuid        The existing object's id
	 *
	 * @return array<string, mixed> The stored object, with uuid.
	 *
	 * @throws RuntimeException When OpenRegister refuses, for example for a missing right.
	 *
	 * @spec openspec/changes/archive/2026-09-29-woo-publicatie-pipeline/tasks.md#task-2.3
	 */
	public function savePlatformPublication(array $publication, ?string $uuid = null): array;
}//end interface
