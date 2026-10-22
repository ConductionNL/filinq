<?php

/**
 * Pseudonym Map Recorder
 *
 * The step at the end of an anonymise run that keeps, replaces or removes the
 * key of the copy just written. A reversible run stores the key and points the
 * anonymisation link at it; an irreversible run removes any key a previous run
 * of the same file left behind, because that key belongs to a copy the link no
 * longer points at.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Pseudonymisation
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-reversible-pseudonymization/tasks.md#task-2.2
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Pseudonymisation;

use OCA\Filinq\Service\AnonymizationPersistenceService;
use OCA\Filinq\Service\OpenRegisterServiceLocator;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Keep, replace or remove the key of one anonymised copy.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Pseudonymisation
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class PseudonymMapRecorder {

	/**
	 * Constructor.
	 *
	 * @param PseudonymPairs $pairs Joins placeholders with original values.
	 * @param PseudonymMapService $maps The encrypted key store.
	 * @param AnonymizationPersistenceService $persistence Writes `mappingRef` on the link.
	 * @param OpenRegisterServiceLocator $locator Resolves OpenRegister's EntityRelationMapper.
	 * @param LoggerInterface $logger Logger.
	 */
	public function __construct(
		private readonly PseudonymPairs $pairs,
		private readonly PseudonymMapService $maps,
		private readonly AnonymizationPersistenceService $persistence,
		private readonly OpenRegisterServiceLocator $locator,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Keep the key of a reversible run, or remove a stale one after an irreversible run.
	 *
	 * The irreversible path adds nothing to the result unless it removed a key,
	 * so the default response stays what it was. The reversible path always says
	 * whether the key was kept: an operator who asked for a reversible copy and
	 * silently got an irreversible one would find out only when it is too late.
	 *
	 * @param array<string, mixed> $resultInfo The result, carrying `anonymizationLinkId`.
	 * @param array<string, mixed> $run fileId, entities (as sent to OpenRegister),
	 *                                  placeholderMap, reversible, scope, userId.
	 *
	 * @return array<string, mixed> The result, with `pseudonymisation` when there is something to say.
	 *
	 * @spec openspec/changes/archive/2026-09-29-reversible-pseudonymization/tasks.md#task-2.2
	 */
	public function record(array $resultInfo, array $run): array {
		$linkId = (string) ($resultInfo['anonymizationLinkId'] ?? '');
		$fileId = (int) ($run['fileId'] ?? 0);

		if (($run['reversible'] ?? false) !== true) {
			return $this->removeStaleKey(resultInfo: $resultInfo, linkId: $linkId, fileId: $fileId);
		}

		if ($linkId === '') {
			return $this->notKept(resultInfo: $resultInfo, reason: 'link_not_recorded');
		}

		$pairs = $this->pairs->build(
			entities: (array) ($run['entities'] ?? []),
			idsByValue: $this->idsByValue(fileId: $fileId),
			placeholderMap: (array) ($run['placeholderMap'] ?? [])
		);

		if ($pairs === []) {
			// A key for this copy cannot exist, and the old one opens a copy
			// the link no longer points at.
			$this->removeStaleKey(resultInfo: $resultInfo, linkId: $linkId, fileId: $fileId);
			return $this->notKept(resultInfo: $resultInfo, reason: 'no_placeholders');
		}

		try {
			$stored = $this->maps->store(
				linkId: $linkId,
				sourceFileId: $fileId,
				pairs: $pairs,
				scope: (string) ($run['scope'] ?? 'document'),
				userId: (string) ($run['userId'] ?? '')
			);
			$this->persistence->setMappingRef(fileId: $fileId, mappingRef: $stored['uuid']);
		} catch (Throwable $e) {
			$this->logger->error(
				message: '[PseudonymMapRecorder] the key of a reversible run was not kept',
				context: ['fileId' => $fileId, 'linkId' => $linkId, 'error' => $e->getMessage()]
			);
			return $this->notKept(resultInfo: $resultInfo, reason: 'store_failed');
		}

		$resultInfo['pseudonymisation'] = [
			'reversible' => true,
			'keyKept' => true,
			'entryCount' => $stored['entryCount'],
			'mappingRef' => $stored['uuid'],
		];

		return $resultInfo;

	}//end record()

	/**
	 * Remove the key a previous run of this file kept, if any.
	 *
	 * @param array<string, mixed> $resultInfo The result.
	 * @param string $linkId The link uuid, '' when the link was not recorded.
	 * @param int $fileId The source file id.
	 *
	 * @return array<string, mixed> The result, unchanged unless a key was removed or could not be.
	 */
	private function removeStaleKey(array $resultInfo, string $linkId, int $fileId): array {
		if ($linkId === '') {
			return $resultInfo;
		}

		try {
			if ($this->maps->deleteForLink(linkId: $linkId) === false) {
				return $resultInfo;
			}

			$this->persistence->setMappingRef(fileId: $fileId, mappingRef: '');
		} catch (Throwable $e) {
			$this->logger->error(
				message: '[PseudonymMapRecorder] a key from an earlier run could not be removed',
				context: ['fileId' => $fileId, 'linkId' => $linkId, 'error' => $e->getMessage()]
			);
			$resultInfo['pseudonymisation'] = ['reversible' => false, 'previousKeyRemoved' => false];

			return $resultInfo;
		}

		$resultInfo['pseudonymisation'] = ['reversible' => false, 'previousKeyRemoved' => true];

		return $resultInfo;

	}//end removeStaleKey()

	/**
	 * Say that a reversible run kept no key, and why.
	 *
	 * @param array<string, mixed> $resultInfo The result.
	 * @param string $reason `link_not_recorded`, `no_placeholders` or `store_failed`.
	 *
	 * @return array<string, mixed> The result.
	 */
	private function notKept(array $resultInfo, string $reason): array {
		$resultInfo['pseudonymisation'] = [
			'reversible' => true,
			'keyKept' => false,
			'reason' => $reason,
		];

		return $resultInfo;

	}//end notKept()

	/**
	 * OpenRegister's entity rows for the file: value to `{id, type}`.
	 *
	 * @param int $fileId The source file id.
	 *
	 * @return array<string, array<string, mixed>> The rows, [] when they cannot be read.
	 */
	private function idsByValue(int $fileId): array {
		try {
			$mapper = $this->locator->get(className: 'OCA\OpenRegister\Db\EntityRelationMapper');

			return (array) $mapper->findEntityIdsByValueForFile($fileId);
		} catch (Throwable $e) {
			$this->logger->warning(
				message: '[PseudonymMapRecorder] the entity rows of the file could not be read',
				context: ['fileId' => $fileId, 'error' => $e->getMessage()]
			);

			return [];
		}

	}//end idsByValue()
}//end class
