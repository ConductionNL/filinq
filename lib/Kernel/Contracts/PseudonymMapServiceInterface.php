<?php

/**
 * Contract of PseudonymMapService
 *
 * The public surface of the PseudonymMapService service, so callers in other
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

use OCA\Filinq\Exception\PseudonymRestoreRefusedException;
use RuntimeException;

/**
 * Contract of PseudonymMapService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface PseudonymMapServiceInterface {
	/**
	 * What encrypted the mapping. The schema enum lists exactly this value.
	 *
	 * @var string
	 */
	public const ALGORITHM = 'nextcloud-icrypto-v1';

	/**
	 * Encrypt and store the pairs of one run, replacing the link's previous map.
	 *
	 * @param string $linkId The anonymisation link uuid.
	 * @param int $sourceFileId The original document's file id.
	 * @param array<int, array<string, string>> $pairs The pairs from PseudonymPairs::build().
	 * @param string $scope `document` or `dossier`.
	 * @param string $userId Who ran the anonymisation.
	 *
	 * @return array{uuid: string, entryCount: int} The stored map.
	 *
	 * @throws RuntimeException When the map could not be encrypted or stored.
	 *
	 * @spec openspec/changes/archive/2026-09-29-reversible-pseudonymization/tasks.md#task-2.1
	 */
	public function store(string $linkId, int $sourceFileId, array $pairs, string $scope, string $userId): array;

	/**
	 * The metadata of the link's map, never the pairs.
	 *
	 * @param string $linkId The anonymisation link uuid.
	 *
	 * @return array<string, mixed>|null The row without `mappings`.
	 *
	 * @throws RuntimeException When OpenRegister cannot be read.
	 *
	 * @spec openspec/changes/archive/2026-09-29-reversible-pseudonymization/tasks.md#task-2.1
	 */
	public function describe(string $linkId): ?array;

	/**
	 * Decrypt the pairs of the link's map. For the restore path only.
	 *
	 * @param string $linkId The anonymisation link uuid.
	 *
	 * @return array{uuid: string, pairs: array<int, array<string, string>>} The map uuid and its pairs.
	 *
	 * @throws PseudonymRestoreRefusedException When there is no map, or it cannot be decrypted.
	 *
	 * @spec openspec/changes/archive/2026-09-29-reversible-pseudonymization/tasks.md#task-3.1
	 */
	public function readPairs(string $linkId): array;

	/**
	 * Destroy the entries for these values in every map kept for one source
	 * document. A map left with no entries is deleted; one with other people's
	 * entries is re-encrypted without these.
	 *
	 * An erasure that leaves a way back is not an erasure (design D6).
	 *
	 * @param int                $sourceFileId The original document's file id.
	 * @param array<int, string> $values       The erased person's identifiers.
	 *
	 * @return array{maps: array<int, string>, entriesDestroyed: int, mapsDeleted: int} What was destroyed.
	 *
	 * @throws RuntimeException When a map cannot be read, decrypted or rewritten: the
	 *                          way back is then still open, and the caller must say so.
	 *
	 * @spec openspec/changes/archive/2026-09-29-erase-a-person-while-the-records-stay/tasks.md#task-3.3
	 */
	public function forgetValues(int $sourceFileId, array $values): array;

	/**
	 * Delete the link's map, if it has one.
	 *
	 * @param string $linkId The anonymisation link uuid.
	 *
	 * @return bool True when a map was deleted.
	 *
	 * @throws RuntimeException When the map exists and could not be deleted.
	 *
	 * @spec openspec/changes/archive/2026-09-29-reversible-pseudonymization/tasks.md#task-2.2
	 */
	public function deleteForLink(string $linkId): bool;
}//end interface
