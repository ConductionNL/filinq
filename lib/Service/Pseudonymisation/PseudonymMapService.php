<?php

/**
 * Pseudonym Map Service
 *
 * Keeps the key of a reversible anonymisation: which placeholder stands for which
 * original value. The pairs are encrypted with Nextcloud's server secret before
 * they reach OpenRegister, and the property that holds them is writeOnly, so a
 * database read and an API read both come away with nothing usable.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Pseudonymisation
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-reversible-pseudonymization/tasks.md#task-2.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Pseudonymisation;

use OCA\Filinq\Exception\PseudonymRestoreRefusedException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Security\ICrypto;
use RuntimeException;
use Throwable;

/**
 * Encrypt, store, read and delete the key of one reversible anonymisation.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Pseudonymisation
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class PseudonymMapService {

	/**
	 * What encrypted the mapping. The schema enum lists exactly this value.
	 *
	 * @var string
	 */
	public const ALGORITHM = 'nextcloud-icrypto-v1';

	/**
	 * Constructor.
	 *
	 * @param PseudonymMapRepository $repository The `pseudonymMap` rows.
	 * @param ICrypto $crypto Nextcloud's authenticated encryption with the server secret.
	 * @param ITimeFactory $time The clock.
	 */
	public function __construct(
		private readonly PseudonymMapRepository $repository,
		private readonly ICrypto $crypto,
		private readonly ITimeFactory $time,
	) {

	}//end __construct()

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
	public function store(string $linkId, int $sourceFileId, array $pairs, string $scope, string $userId): array {
		$plaintext = json_encode(value: array_values($pairs), flags: JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
		$ciphertext = $this->crypto->encrypt($plaintext);
		if ($ciphertext === '' || str_contains($ciphertext, $plaintext) === true) {
			throw new RuntimeException(message: 'Encryption returned nothing usable; the map is not stored.');
		}

		$existing = $this->repository->findForLink(linkId: $linkId);
		$row = [
			'anonymizationLink' => $linkId,
			'sourceFileId' => $sourceFileId,
			'mappings' => $ciphertext,
			'algorithm' => self::ALGORITHM,
			'entryCount' => count($pairs),
			'scope' => $this->scope(scope: $scope),
			'storedAt' => $this->time->getDateTime()->format(format: DATE_ATOM),
			'storedBy' => mb_substr($userId, 0, 64),
		];

		$uuid = $this->repository->save(row: $row, uuid: ($existing['uuid'] ?? null));

		return ['uuid' => $uuid, 'entryCount' => count($pairs)];

	}//end store()

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
	public function describe(string $linkId): ?array {
		return $this->repository->findForLink(linkId: $linkId);

	}//end describe()

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
	public function readPairs(string $linkId): array {
		$map = $this->repository->findForLink(linkId: $linkId);
		if ($map === null || ($map['uuid'] ?? '') === '') {
			throw new PseudonymRestoreRefusedException(
				reason: PseudonymRestoreRefusedException::REASON_NO_MAP,
				message: 'This anonymisation kept no key: it was irreversible.'
			);
		}

		try {
			$plaintext = $this->crypto->decrypt($this->repository->readCiphertext(uuid: (string) $map['uuid']));
			$pairs = json_decode(json: $plaintext, associative: true, flags: JSON_THROW_ON_ERROR);
		} catch (Throwable $e) {
			throw new PseudonymRestoreRefusedException(
				reason: PseudonymRestoreRefusedException::REASON_MAP_UNREADABLE,
				message: 'The key of this anonymisation could not be decrypted: ' . $e->getMessage()
			);
		}

		if (is_array($pairs) === false) {
			throw new PseudonymRestoreRefusedException(
				reason: PseudonymRestoreRefusedException::REASON_MAP_UNREADABLE,
				message: 'The key of this anonymisation decrypted to something that is not a list of pairs.'
			);
		}

		return ['uuid' => (string) $map['uuid'], 'pairs' => array_values($pairs)];

	}//end readPairs()

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
	 * @spec openspec/changes/erase-a-person-while-the-records-stay/tasks.md#task-3.3
	 */
	public function forgetValues(int $sourceFileId, array $values): array {
		$needles = array_map(static fn (string $value): string => mb_strtolower(trim($value)), $values);
		$result = ['maps' => [], 'entriesDestroyed' => 0, 'mapsDeleted' => 0];
		foreach ($this->repository->findForSource(sourceFileId: $sourceFileId) as $map) {
			$uuid = (string) $map['uuid'];
			try {
				$pairs = json_decode(json: $this->crypto->decrypt($this->repository->readCiphertext(uuid: $uuid)), associative: true, flags: JSON_THROW_ON_ERROR);
			} catch (Throwable $e) {
				throw new RuntimeException(message: 'The pseudonym map ' . $uuid . ' could not be decrypted: ' . $e->getMessage(), code: 0, previous: $e);
			}

			$kept = array_values(
				array_filter(
					(array) $pairs,
					static fn (mixed $pair): bool => in_array(mb_strtolower(trim((string) ($pair['originalValue'] ?? ''))), $needles, true) === false
				)
			);
			$removed = (count((array) $pairs) - count($kept));
			if ($removed === 0) {
				continue;
			}

			$result['maps'][] = $uuid;
			$result['entriesDestroyed'] += $removed;
			if ($kept === []) {
				$this->repository->delete(uuid: $uuid);
				$result['mapsDeleted']++;
				continue;
			}

			$map['mappings'] = $this->crypto->encrypt(json_encode(value: $kept, flags: JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
			$map['entryCount'] = count($kept);
			$this->repository->save(row: $map, uuid: $uuid);
		}//end foreach

		return $result;

	}//end forgetValues()

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
	public function deleteForLink(string $linkId): bool {
		$map = $this->repository->findForLink(linkId: $linkId);
		if ($map === null || ($map['uuid'] ?? '') === '') {
			return false;
		}

		$this->repository->delete(uuid: (string) $map['uuid']);

		return true;

	}//end deleteForLink()

	/**
	 * The scope the schema accepts: anything but `dossier` numbers per document.
	 *
	 * @param string $scope The requested scope.
	 *
	 * @return string `document` or `dossier`.
	 */
	private function scope(string $scope): string {
		if ($scope === 'dossier') {
			return 'dossier';
		}

		return 'document';

	}//end scope()
}//end class
