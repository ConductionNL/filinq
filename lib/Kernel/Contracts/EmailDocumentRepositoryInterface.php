<?php

/**
 * Contract of EmailDocumentRepository
 *
 * The public surface of the EmailDocumentRepository service, so callers in other
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
 * Contract of EmailDocumentRepository.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface EmailDocumentRepositoryInterface {
	/**
	 * SCHEMA
	 */
	public const SCHEMA = 'emailDocument';

	/**
	 * Store a record.
	 *
	 * @param array<string, mixed> $record The record.
	 * @param string|null          $uuid   The uuid when updating.
	 *
	 * @return array<string, mixed> The stored record with its uuid.
	 *
	 * @spec openspec/changes/archive/2026-10-01-email-ingestion/tasks.md#2-1
	 */
	public function save(array $record, ?string $uuid = null): array;

	/**
	 * A filed record of the same email for the same dossier: same bytes, or same Message-ID.
	 *
	 * @param string $dossierRef  The dossier.
	 * @param string $contentHash The sha256 of the file.
	 * @param string $messageId   The Message-ID, '' when unknown.
	 *
	 * @return array<string, mixed>|null The record, or null.
	 *
	 * @spec openspec/changes/archive/2026-10-01-email-ingestion/tasks.md#2-1
	 */
	public function findFiledDuplicate(string $dossierRef, string $contentHash, string $messageId): ?array;

	/**
	 * The file ids that already have a failed record, so a tick does not record them again.
	 *
	 * @return list<string> The ids.
	 *
	 * @spec openspec/changes/archive/2026-10-01-email-ingestion/tasks.md#2-1
	 */
	public function failedSourceRefs(): array;

	/**
	 * One record.
	 *
	 * @param string $uuid The uuid.
	 *
	 * @return array<string, mixed>|null The record, or null.
	 *
	 * @spec openspec/changes/archive/2026-10-01-email-ingestion/tasks.md#2-3
	 */
	public function findByUuid(string $uuid): ?array;

	/**
	 * Records matching equality filters.
	 *
	 * @param array<string, mixed> $filters The filters.
	 *
	 * @return list<array<string, mixed>> The records.
	 *
	 * @spec openspec/changes/archive/2026-10-01-email-ingestion/tasks.md#3-1
	 */
	public function search(array $filters): array;
}//end interface
