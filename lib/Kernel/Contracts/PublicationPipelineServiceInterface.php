<?php

/**
 * Contract of PublicationPipelineService
 *
 * The public surface of the PublicationPipelineService service, so callers in other
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
use OCA\Filinq\Exception\PublicationNotReadyException;
use RuntimeException;

/**
 * Contract of PublicationPipelineService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface PublicationPipelineServiceInterface {
	/**
	 * Start a publication for a document and check it straight away.
	 *
	 * @param string $documentFileRef The document's file id
	 * @param string $subjectType     document or dossier
	 * @param string $dossierRef      The dossier, if any
	 * @param string $actor           Who starts it
	 *
	 * @return array<string, mixed> The record.
	 *
	 * @spec openspec/changes/archive/2026-09-29-woo-publicatie-pipeline/tasks.md#task-2.2
	 */
	public function create(string $documentFileRef, string $subjectType, string $dossierRef, string $actor): array;

	/**
	 * Run the readiness checks, store the verdicts, and move the status
	 * between draft and ready to match.
	 *
	 * @param array<string, mixed> $record The record
	 * @param string               $actor  Who asks
	 *
	 * @return array<string, mixed> The stored record.
	 *
	 * @spec openspec/changes/archive/2026-09-29-woo-publicatie-pipeline/tasks.md#task-2.2
	 */
	public function evaluate(array $record, string $actor): array;

	/**
	 * Set the Woo metadata.
	 *
	 * @param array<string, mixed> $record   The record
	 * @param array<string, mixed> $metadata The fields, only those in METADATA are taken
	 * @param string               $actor    Who sets them
	 *
	 * @return array<string, mixed> The stored record.
	 *
	 * @throws InvalidArgumentException When the category is not one of OpenCatalogi's TOOI categories.
	 *
	 * @spec openspec/changes/archive/2026-09-29-woo-publicatie-pipeline/tasks.md#task-3.1
	 * @spec openspec/changes/archive/2026-09-29-accessible-redaction-output/tasks.md#task-2.3
	 */
	public function updateMetadata(array $record, array $metadata, string $actor): array;

	/**
	 * Hand a ready publication to the platform with its redacted copy.
	 *
	 * @param array<string, mixed> $record The record
	 * @param string               $actor  Who hands it off; the copy is read from their files
	 *
	 * @return array<string, mixed> The stored record.
	 *
	 * @throws PublicationNotReadyException When a check fails now, or the metadata is incomplete.
	 * @throws RuntimeException             When the platform is not there or refuses.
	 *
	 * @spec openspec/changes/archive/2026-09-29-woo-publicatie-pipeline/tasks.md#task-2.3
	 */
	public function handoff(array $record, string $actor): array;

	/**
	 * Withdraw a publication: the platform's publication gets a
	 * depublication date of now and is never deleted.
	 *
	 * @param array<string, mixed> $record The record
	 * @param string               $reason Why, required
	 * @param string               $actor  Who withdraws it
	 *
	 * @return array<string, mixed> The stored record.
	 *
	 * @throws InvalidArgumentException When there is no reason, or nothing was handed off.
	 *
	 * @spec openspec/changes/archive/2026-09-29-woo-publicatie-pipeline/tasks.md#task-2.4
	 */
	public function withdraw(array $record, string $reason, string $actor): array;

	/**
	 * Record a destruction date and pass it to the platform.
	 *
	 * @param array<string, mixed> $record The record
	 * @param string               $date   Y-m-d
	 * @param string               $source Where the date came from
	 * @param string               $actor  Who records it
	 *
	 * @return array<string, mixed> The stored record.
	 *
	 * @throws InvalidArgumentException When the date or its source is missing.
	 *
	 * @spec openspec/changes/archive/2026-09-29-woo-publicatie-pipeline/tasks.md#task-2.4
	 */
	public function setDestructionDate(array $record, string $date, string $source, string $actor): array;

	/**
	 * Move a record on when the platform side has: published once the
	 * publication date has come, withdrawn once the depublication date has.
	 *
	 * @param array<string, mixed> $record The record
	 *
	 * @return array<string, mixed> The record, stored again when it moved.
	 *
	 * @spec openspec/changes/archive/2026-09-29-woo-publicatie-pipeline/tasks.md#task-2.4
	 */
	public function sync(array $record): array;
}//end interface
