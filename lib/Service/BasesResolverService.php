<?php

/**
 * Bases Resolver Service
 *
 * Resolves the union of Woo Art. 5 grondslagen (`bases[]`) for a batch of
 * files by locating the dossier(s) the files belong to via OpenRegister.
 *
 * The resolver supports three scenarios:
 *
 *   - **Folder-based batch** — the batch carries a `folderId`; the resolver
 *     uses that as the Nextcloud folder reference and queries the dossier
 *     register for matching dossier objects.
 *   - **Upload batch (multiple source folders)** — each file's parent folder
 *     is resolved via `IRootFolder`; any number of distinct folders are
 *     supported. Results are unioned.
 *   - **Orphan files / empty dossier bases** — gracefully returns `[]`.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/anonymisation-entity-review-prohibition-hints/tasks.md#task-3
 * @spec openspec/changes/anonymisation-entity-review-prohibition-hints/tasks.md#task-4
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCP\App\IAppManager;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IUserSession;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Resolves suggested dossier grondslagen bases for a batch of files.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://conduction.nl
 *
 * @spec openspec/changes/anonymisation-entity-review-prohibition-hints/tasks.md#task-3
 */
class BasesResolverService {

	/**
	 * OpenRegister register slug that holds dossier objects.
	 *
	 * `filinq`, not `dossier`: this app declares ONE register holding all 23
	 * schemas. Note that the SCHEMA below is still called `dossier` — the two
	 * used to share a name, and only the register moved.
	 */
	private const REGISTER_SLUG = 'filinq';

	/**
	 * OpenRegister schema slug for the dossier type.
	 *
	 * Unchanged by the register consolidation: a schema slug is resolved by the
	 * (application, slug) pair and has nothing to do with which register holds
	 * it.
	 */
	private const SCHEMA_SLUG = 'dossier';

	/**
	 * Constructor.
	 *
	 * @param LoggerInterface $logger Structured logger.
	 * @param IRootFolder $rootFolder Nextcloud root-folder API.
	 * @param IUserSession $userSession Current-user accessor.
	 * @param IAppManager $appManager App-availability check.
	 * @param ContainerInterface $container DI container (OpenRegister lookup).
	 *
	 * @return void
	 */
	public function __construct(
		private readonly LoggerInterface $logger,
		private readonly IRootFolder $rootFolder,
		private readonly IUserSession $userSession,
		private readonly IAppManager $appManager,
		private readonly ContainerInterface $container,
	) {

	}//end __construct()

	/**
	 * Return the union of `bases[]` across all dossiers the batch belongs to.
	 *
	 * Returns a deduplicated array of UUID/slug strings. Returns `[]` when
	 * no dossier is found, when the matching dossier has no bases, or when
	 * OpenRegister is unavailable.
	 *
	 * @param array<string, mixed> $batch Batch record from BatchStateService.
	 *
	 * @return array<int, string> Deduplicated union of dossier bases.
	 *
	 * @spec openspec/changes/anonymisation-entity-review-prohibition-hints/tasks.md#task-3
	 * @spec openspec/changes/anonymisation-entity-review-prohibition-hints/tasks.md#task-4
	 */
	public function resolveBasesForBatch(array $batch): array {
		$folderIds = $this->collectFolderIds(batch: $batch);
		if (count($folderIds) === 0) {
			return [];
		}

		$objectService = $this->getObjectService();
		if ($objectService === null) {
			return [];
		}

		$basesSet = [];
		foreach ($folderIds as $folderId) {
			foreach ($this->loadDossierBasesForFolder(objectService: $objectService, folderId: $folderId) as $base) {
				$basesSet[(string)$base] = true;
			}
		}

		return array_keys($basesSet);
	}//end resolveBasesForBatch()

	/**
	 * Collect the unique Nextcloud folder IDs that contain the batch's files.
	 *
	 * For a folder-based batch (`$batch['folderId']` is set), the folder ID
	 * is taken directly. For upload batches the parent of each file is resolved
	 * via `IRootFolder`; unique folder IDs are returned.
	 *
	 * @param array<string, mixed> $batch Batch record.
	 *
	 * @return array<int, int> Distinct folder node IDs.
	 */
	private function collectFolderIds(array $batch): array {
		// Folder-based batch — parent folder is known.
		$explicitFolderId = $this->extractExplicitFolderId(batch: $batch);
		if ($explicitFolderId !== null) {
			return [$explicitFolderId];
		}

		// Upload batch — resolve parent folder for each file.
		$userFolder = $this->getUserFolder();
		if ($userFolder === null) {
			return [];
		}

		return $this->resolveParentFolderIds(userFolder: $userFolder, files: $batch['files']);
	}//end collectFolderIds()

	/**
	 * Read the explicitly-supplied folder ID from a folder-based batch.
	 *
	 * Returns null when the batch carries no usable `folderId`, which signals
	 * the caller to fall back to per-file parent resolution.
	 *
	 * @param array<string, mixed> $batch Batch record.
	 *
	 * @return int|null Positive folder node ID, or null when absent/invalid.
	 */
	private function extractExplicitFolderId(array $batch): ?int {
		// An isset() check is already false for a null value, so no separate null check.
		if (isset($batch['folderId']) === false) {
			return null;
		}

		$folderId = (int)$batch['folderId'];
		if ($folderId > 0) {
			return $folderId;
		}

		return null;
	}//end extractExplicitFolderId()

	/**
	 * Resolve the current user's home folder.
	 *
	 * Returns null when there is no active user session or when the home
	 * folder cannot be opened — callers treat that as "no folders to inspect".
	 *
	 * @return Folder|null The user's home folder, or null.
	 */
	private function getUserFolder(): ?Folder {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return null;
		}

		try {
			return $this->rootFolder->getUserFolder(userId: $user->getUID());
		} catch (\Throwable $e) {
			$this->logger->debug(
				'BasesResolverService: could not get user folder',
				['error' => $e->getMessage()]
			);
			return null;
		}

	}//end getUserFolder()

	/**
	 * Resolve the distinct parent folder IDs of a batch's files.
	 *
	 * Files that cannot be located are skipped; a failure to resolve one file
	 * never aborts the resolution of the remaining files.
	 *
	 * @param Folder $userFolder The user's home folder.
	 * @param array<int, array<string, mixed>> $files File records from the batch.
	 *
	 * @return array<int, int> Distinct parent folder node IDs.
	 */
	private function resolveParentFolderIds(Folder $userFolder, array $files): array {
		$folderIds = [];
		foreach ($files as $file) {
			$fileId = (int)($file['fileId'] ?? 0);
			if ($fileId <= 0) {
				continue;
			}

			try {
				$nodes = $userFolder->getById(id: $fileId);
				if (count($nodes) > 0) {
					$parentId = (int)$nodes[0]->getParent()->getId();
					$folderIds[$parentId] = $parentId;
				}
			} catch (\Throwable $e) {
				$this->logger->debug(
					'BasesResolverService: could not resolve parent folder for file',
					['fileId' => $fileId, 'error' => $e->getMessage()]
				);
			}
		}//end foreach

		return array_values($folderIds);
	}//end resolveParentFolderIds()

	/**
	 * Load the `bases[]` array from all dossiers matching a given folder ID.
	 *
	 * Queries OpenRegister for dossier objects whose `@self.folder` equals
	 * `$folderId`. Returns an empty array when no dossier is found or when
	 * the dossier's `bases` field is absent or empty.
	 *
	 * @param mixed $objectService OpenRegister ObjectService instance.
	 * @param int $folderId Nextcloud folder node ID.
	 *
	 * @return array<int, string> Raw base references from matching dossier(s).
	 */
	private function loadDossierBasesForFolder(mixed $objectService, int $folderId): array {
		try {
			$result = $objectService->findAll(
				config: [
					'filters' => [
						'register' => self::REGISTER_SLUG,
						'schema' => self::SCHEMA_SLUG,
						'folder' => (string)$folderId,
					],
				],
				_rbac: false
			);
		} catch (\Throwable $e) {
			$this->logger->debug(
				'BasesResolverService: findAll failed for folder',
				['folderId' => $folderId, 'error' => $e->getMessage()]
			);
			return [];
		}

		$bases = [];
		foreach ($this->extractObjects(result: $result) as $dossier) {
			$dossierBases = $dossier['bases'] ?? null;
			if (is_array($dossierBases) === false) {
				continue;
			}

			foreach ($dossierBases as $base) {
				if (is_string($base) === true && $base !== '') {
					$bases[] = $base;
				}
			}
		}

		return $bases;
	}//end loadDossierBasesForFolder()

	/**
	 * Extract plain-array objects from an ObjectService findAll result.
	 *
	 * @param mixed $result Raw findAll return value.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function extractObjects(mixed $result): array {
		$objects = [];
		foreach ($this->extractCandidates(result: $result) as $candidate) {
			$payload = $this->normaliseCandidate(candidate: $candidate);
			if ($payload !== null) {
				$objects[] = $payload;
			}
		}

		return $objects;
	}//end extractObjects()

	/**
	 * Unwrap a findAll return value into a flat list of candidate entries.
	 *
	 * Accepts both the paginated `['results' => [...]]` envelope and a bare
	 * list, as well as any Traversable. Anything else yields an empty list.
	 *
	 * @param mixed $result Raw findAll return value.
	 *
	 * @return array<int|string, mixed> Candidate entries.
	 */
	private function extractCandidates(mixed $result): array {
		if ($result instanceof \Traversable) {
			return iterator_to_array(iterator: $result);
		}

		if (is_array($result) === false) {
			return [];
		}

		$candidates = $result['results'] ?? $result;
		if (is_array($candidates) === false) {
			return [];
		}

		return $candidates;
	}//end extractCandidates()

	/**
	 * Normalise a single candidate entry into a plain array payload.
	 *
	 * Plain arrays are returned unchanged; objects are unwrapped via
	 * `getObject()` and then `jsonSerialize()`. Returns null when the
	 * candidate cannot be represented as an array.
	 *
	 * @param mixed $candidate Candidate entry from a findAll result.
	 *
	 * @return array<string, mixed>|null Plain array payload, or null.
	 */
	private function normaliseCandidate(mixed $candidate): ?array {
		if (is_array($candidate) === true) {
			return $candidate;
		}

		if (is_object($candidate) === false) {
			return null;
		}

		if (method_exists($candidate, 'getObject') === true) {
			$payload = $candidate->getObject();
			if (is_array($payload) === true) {
				return $payload;
			}
		}

		if (method_exists($candidate, 'jsonSerialize') === true) {
			$payload = $candidate->jsonSerialize();
			if (is_array($payload) === true) {
				return $payload;
			}
		}

		return null;
	}//end normaliseCandidate()

	/**
	 * Try to obtain OpenRegister's ObjectService from the DI container.
	 *
	 * Returns null when OpenRegister is not installed or the service cannot
	 * be resolved — callers treat this as "no dossier found".
	 *
	 * @return mixed ObjectService instance, or null.
	 */
	private function getObjectService(): mixed {
		try {
			if (in_array('openregister', $this->appManager->getInstalledApps(), true) === false) {
				return null;
			}

			return $this->container->get('OCA\OpenRegister\Service\ObjectService');
		} catch (\Throwable $e) {
			$this->logger->debug(
				'BasesResolverService: ObjectService unavailable',
				['error' => $e->getMessage()]
			);
			return null;
		}

	}//end getObjectService()
}//end class
