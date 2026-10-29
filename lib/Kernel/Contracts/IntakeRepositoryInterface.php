<?php

/**
 * Contract of IntakeRepository
 *
 * The public surface of the IntakeRepository service, so callers in other
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
 * Contract of IntakeRepository.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface IntakeRepositoryInterface {
	/**
	 * The register holding Filinq's schemas.
	 *
	 * @var string
	 */
	public const REGISTER = 'filinq';

	/**
	 * The schema holding the waiting documents.
	 *
	 * @var string
	 */
	public const SCHEMA = 'intakeDocument';

	/**
	 * Waiting for a clerk. The initial state.
	 *
	 * @var string
	 */
	public const STATUS_RECEIVED = 'received';

	/**
	 * Assigned to a record. Terminal.
	 *
	 * @var string
	 */
	public const STATUS_ASSIGNED = 'assigned';

	/**
	 * Rejected with a reason. Terminal.
	 *
	 * @var string
	 */
	public const STATUS_REJECTED = 'rejected';

	/**
	 * Taken back off a record, waiting on the worklist.
	 *
	 * @var string
	 */
	public const STATUS_DETACHED = 'detached';

	/**
	 * Every document still waiting for a clerk, newest first.
	 *
	 * @return array<int, array<string, mixed>> The waiting documents.
	 *
	 * @throws RuntimeException When the register could not be read.
	 *
	 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
	 */
	public function findWaiting(): array;

	/**
	 * Every document in one state, newest first.
	 *
	 * @param string $status The state to list.
	 *
	 * @return array<int, array<string, mixed>> The documents in that state.
	 *
	 * @throws RuntimeException When the register could not be read.
	 *
	 * @spec openspec/changes/archive/2026-10-09-inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	public function findByStatus(string $status): array;

	/**
	 * Everything that arrived with one message.
	 *
	 * @param string $uuid The message's intake document.
	 *
	 * @return array<int, array<string, mixed>> The attachments.
	 *
	 * @spec openspec/changes/archive/2026-10-09-inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	public function findArrivedWith(string $uuid): array;

	/**
	 * Find the intake document that belongs to one file, whatever its state.
	 *
	 * @param int $fileId The Nextcloud file id.
	 *
	 * @return array<string, mixed>|null The document, or null when the file never had one.
	 *
	 * @spec openspec/changes/archive/2026-10-09-inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	public function findByFile(int $fileId): ?array;

	/**
	 * Find the intake document a channel already delivered under this reference.
	 *
	 * A channel that delivers the same message twice must not produce two rows
	 * in the inbox: a clerk would then assign one and leave the other waiting
	 * forever, and nothing would say why.
	 *
	 * @param string $sourceRef The channel's own reference.
	 *
	 * @return array<string, mixed>|null The stored document, or null when this is the first delivery.
	 *
	 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
	 */
	public function findBySourceRef(string $sourceRef): ?array;

	/**
	 * Find one intake document by its uuid.
	 *
	 * @param string $uuid The uuid.
	 *
	 * @return array<string, mixed>|null The document, or null when there is none.
	 *
	 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
	 */
	public function findByUuid(string $uuid): ?array;

	/**
	 * Write an intake document, creating it when its uuid is not known yet.
	 *
	 * @param array<string, mixed> $document The document to store.
	 * @param string|null $uuid The uuid to write under, or null to create one.
	 *
	 * @return array<string, mixed> The stored document.
	 *
	 * @throws RuntimeException When the write fails.
	 *
	 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
	 */
	public function save(array $document, ?string $uuid = null): array;
}//end interface
