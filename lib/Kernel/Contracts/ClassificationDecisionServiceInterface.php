<?php

/**
 * Contract of ClassificationDecisionService
 *
 * The public surface of the ClassificationDecisionService service, so callers in other
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

use OCA\Filinq\Exception\ClassificationRefused;

/**
 * Contract of ClassificationDecisionService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface ClassificationDecisionServiceInterface {
	/**
	 * The suggestions still waiting, limited to files the reviewer can reach.
	 *
	 * @param string $userId The reviewer.
	 *
	 * @return array<int, array<string, mixed>> The records.
	 *
	 * @spec openspec/changes/inbound-auto-classification/tasks.md#2-4
	 */
	public function pending(string $userId): array;

	/**
	 * Whether the reviewer can open the file in their own folder: the per-object
	 * guard every classification endpoint asks first.
	 *
	 * @param int    $fileId The file.
	 * @param string $userId The reviewer.
	 *
	 * @return bool True when the file resolves in the reviewer's folder.
	 *
	 * @spec openspec/changes/inbound-auto-classification/tasks.md#2-4
	 */
	public function canAccessFile(int $fileId, string $userId): bool;

	/**
	 * The active record of one file, decided or not, for the document card.
	 *
	 * @param int    $fileId The file.
	 * @param string $userId The reviewer.
	 *
	 * @return array<string, mixed>|null The record, or null for a file the reviewer cannot reach or without one.
	 *
	 * @spec openspec/changes/inbound-auto-classification/tasks.md#3-2
	 */
	public function forFile(int $fileId, string $userId): ?array;

	/**
	 * Confirm a suggestion, with the reviewer's corrections if any.
	 *
	 * @param int                  $fileId  The file.
	 * @param string               $userId  The reviewer.
	 * @param array<string, mixed> $choices documentType, correspondent ({name, entityType}) and dossier
	 *                                      (a uuid, or null for none); a missing key keeps the suggestion.
	 *
	 * @return array<string, mixed> The confirmed record, with `filing` moved or recorded.
	 *
	 * @throws ClassificationRefused 404 for a file the reviewer cannot reach or without a suggestion,
	 *                               409 when it was already decided, 422 for an unknown type.
	 *
	 * @spec openspec/changes/inbound-auto-classification/tasks.md#2-4
	 */
	public function confirm(int $fileId, string $userId, array $choices): array;

	/**
	 * Reject a suggestion: the record closes, nothing else changes.
	 *
	 * @param int    $fileId The file.
	 * @param string $userId The reviewer.
	 *
	 * @return array<string, mixed> The rejected record.
	 *
	 * @throws ClassificationRefused As for confirm().
	 *
	 * @spec openspec/changes/inbound-auto-classification/tasks.md#2-4
	 */
	public function reject(int $fileId, string $userId): array;
}//end interface
