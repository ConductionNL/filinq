<?php

/**
 * Where one entity occurs, as the caller may see it.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category  Service
 * @package   OCA\Filinq\Service\EntitySearch
 *
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-entity-search/tasks.md#task-2.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\EntitySearch;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\IntakeRepository;
use OCA\Filinq\Service\Redaction\AnonymizationLinkReader;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use Psr\Container\ContainerInterface;
use Throwable;

/**
 * Groups an entity's occurrences per document and adds, per document the
 * caller can read: its name and path, the dossier whose folder holds it, its
 * anonymisation state and OpenRegister's risk level.
 *
 * A document the caller cannot read is only counted. Its name, path and
 * dossier are never looked up for the answer: the entity search grants a
 * look into the catalogue, not read rights on files.
 */
class EntityOccurrences {

	/**
	 * OpenRegister's per-file risk level.
	 *
	 * @var string
	 */
	private const RISK = 'OCA\OpenRegister\Files\Service\RiskLevelService';

	/**
	 * Constructor.
	 *
	 * @param IRootFolder                   $rootFolder     Every user's files.
	 * @param AnonymizationLinkReader       $links          Source and redacted copy pairs.
	 * @param DocumentObjectServiceResolver $objectResolver OpenRegister's ObjectService, for dossiers.
	 * @param ContainerInterface            $container      Resolves the risk level service.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IRootFolder $rootFolder,
		private readonly AnonymizationLinkReader $links,
		private readonly DocumentObjectServiceResolver $objectResolver,
		private readonly ContainerInterface $container,
	) {

	}//end __construct()

	/**
	 * The occurrences, grouped.
	 *
	 * @param array<int, array<string, mixed>> $relations The catalogue's occurrence rows.
	 * @param string                           $userId    The caller.
	 *
	 * @return array{documents: array<int, array<string, mixed>>, noAccess: int, other: list<array{kind: string, count: int}>, occurrenceCount: int}
	 *
	 * @spec openspec/changes/archive/2026-09-29-entity-search/tasks.md#task-2.2
	 */
	public function forUser(array $relations, string $userId): array {
		$grouped = $this->grouped(relations: $relations);
		$readable = $this->documents(byFile: $grouped['byFile'], userId: $userId);

		return [
			'documents' => $readable['documents'],
			'noAccess' => $readable['noAccess'],
			'other' => $this->others(counts: $grouped['other']),
			'occurrenceCount' => count($relations),
		];

	}//end forUser()

	/**
	 * The occurrence rows per file id (ascending), and the count of object and e-mail rows.
	 *
	 * @param array<int, array<string, mixed>> $relations The catalogue's occurrence rows.
	 *
	 * @return array{byFile: array<int, list<array{confidence: float, anonymized: bool, detectionMethod: string}>>,
	 *               other: array{object: int, email: int}} The groups.
	 */
	private function grouped(array $relations): array {
		$byFile = [];
		$other = ['object' => 0, 'email' => 0];
		foreach ($relations as $relation) {
			$fileId = (int) ($relation['fileId'] ?? 0);
			if ($fileId > 0) {
				$byFile[$fileId][] = [
					'confidence' => (float) ($relation['confidence'] ?? 0),
					'anonymized' => (bool) ($relation['anonymized'] ?? false),
					'detectionMethod' => (string) ($relation['detectionMethod'] ?? ''),
				];
				continue;
			}

			$kind = 'email';
			if ((int) ($relation['objectId'] ?? 0) > 0) {
				$kind = 'object';
			}

			$other[$kind]++;
		}

		ksort($byFile);
		return ['byFile' => $byFile, 'other' => $other];

	}//end grouped()

	/**
	 * The documents the caller can read, and how many they cannot.
	 *
	 * @param array<int, list<array<string, mixed>>> $byFile File id => its occurrences.
	 * @param string                                 $userId The caller.
	 *
	 * @return array{documents: array<int, array<string, mixed>>, noAccess: int} The documents.
	 */
	private function documents(array $byFile, string $userId): array {
		$userFolder = $this->userFolder(userId: $userId);
		$dossiers = null;
		$documents = [];
		$noAccess = 0;
		foreach ($byFile as $fileId => $occurrences) {
			$node = $this->readable(userFolder: $userFolder, fileId: $fileId);
			if ($node === null) {
				$noAccess++;
				continue;
			}

			$dossiers ??= $this->dossiers();
			$documents[] = [
				'fileId' => $fileId,
				'name' => $node->getName(),
				'path' => (string) $userFolder?->getRelativePath($node->getPath()),
				'dossier' => $this->dossierOf(node: $node, dossiers: $dossiers),
				'anonymisation' => $this->anonymisation(userFolder: $userFolder, fileId: $fileId),
				'riskLevel' => $this->riskLevel(fileId: $fileId),
				'occurrences' => $occurrences,
			];
		}

		return ['documents' => $documents, 'noAccess' => $noAccess];

	}//end documents()

	/**
	 * The non-file kinds that occur, in object then e-mail order.
	 *
	 * @param array{object: int, email: int} $counts Rows per kind.
	 *
	 * @return list<array{kind: string, count: int}> The kinds with a count above zero.
	 */
	private function others(array $counts): array {
		$others = [];
		foreach ($counts as $kind => $count) {
			if ($count > 0) {
				$others[] = ['kind' => $kind, 'count' => $count];
			}
		}

		return $others;

	}//end others()

	/**
	 * The caller's own view of the file tree, or null.
	 *
	 * @param string $userId The caller.
	 *
	 * @return Folder|null The user folder.
	 */
	private function userFolder(string $userId): ?Folder {
		try {
			return $this->rootFolder->getUserFolder($userId);
		} catch (Throwable) {
			return null;
		}

	}//end userFolder()

	/**
	 * The file as the caller sees it, or null when they cannot read it.
	 *
	 * @param Folder|null $userFolder The caller's folder.
	 * @param int         $fileId     The file.
	 *
	 * @return File|null The file.
	 */
	private function readable(?Folder $userFolder, int $fileId): ?File {
		if ($userFolder === null) {
			return null;
		}

		return $this->readableFileIn(userFolder: $userFolder, fileId: $fileId);

	}//end readable()

	/**
	 * The file with this id in the caller's folder, when the caller may read it.
	 *
	 * @param Folder $userFolder The caller's folder.
	 * @param int    $fileId     The file.
	 *
	 * @return File|null The file, or null.
	 */
	private function readableFileIn(Folder $userFolder, int $fileId): ?File {
		$file = $this->fileById(userFolder: $userFolder, fileId: $fileId);
		if ($file === null || $file->isReadable() === false) {
			return null;
		}

		return $file;

	}//end readableFileIn()

	/**
	 * The file with this id in the caller's folder.
	 *
	 * @param Folder $userFolder The caller's folder.
	 * @param int    $fileId     The file.
	 *
	 * @return File|null The file, or null when it is gone, unreachable or not a file.
	 */
	private function fileById(Folder $userFolder, int $fileId): ?File {
		try {
			$node = $userFolder->getFirstNodeById($fileId);
		} catch (Throwable) {
			return null;
		}

		if ($node instanceof File === false) {
			return null;
		}

		return $node;

	}//end fileById()

	/**
	 * Folder id => dossier, for the dossiers the caller may read.
	 *
	 * @return array<int, array{uuid: string, name: string}> The dossiers.
	 */
	private function dossiers(): array {
		try {
			$rows = $this->objectResolver->resolve()->searchObjectsBySlug(registerSlug: IntakeRepository::REGISTER, schemaSlug: 'dossier', filters: []);
		} catch (Throwable) {
			return [];
		}

		$dossiers = [];
		foreach ((array) $rows as $row) {
			$data = $row;
			if (is_object($row) === true && method_exists($row, 'jsonSerialize') === true) {
				$data = $row->jsonSerialize();
			}

			$folder = (int) ($data['@self']['folder'] ?? 0);
			if ($folder > 0) {
				$dossiers[$folder] = ['uuid' => (string) ($data['@self']['id'] ?? ($data['id'] ?? '')), 'name' => (string) ($data['name'] ?? '')];
			}
		}

		return $dossiers;

	}//end dossiers()

	/**
	 * The dossier whose folder holds the file, or null.
	 *
	 * @param File                                          $node     The file.
	 * @param array<int, array{uuid: string, name: string}> $dossiers Folder id => dossier.
	 *
	 * @return array{uuid: string, name: string}|null The dossier.
	 */
	private function dossierOf(File $node, array $dossiers): ?array {
		if ($dossiers === []) {
			return null;
		}

		try {
			return $this->nearestDossier(parent: $node->getParent(), dossiers: $dossiers);
		} catch (Throwable) {
			return null;
		}

	}//end dossierOf()

	/**
	 * The first dossier at or above a folder, at most 64 levels up.
	 *
	 * Walks up until a dossier folder or the root, whose getParent() throws.
	 *
	 * @param Folder                           $parent   The folder to start at.
	 * @param array<int, array<string, mixed>> $dossiers The dossiers by folder id.
	 *
	 * @return array<string, mixed>|null The dossier, or null when none is found in time.
	 */
	private function nearestDossier(Folder $parent, array $dossiers): ?array {
		for ($depth = 0; $depth < 64; $depth++) {
			if (isset($dossiers[(int) $parent->getId()]) === true) {
				return $dossiers[(int) $parent->getId()];
			}

			$parent = $parent->getParent();
		}

		return null;

	}//end nearestDossier()

	/**
	 * Whether the file was anonymised, or is itself a redacted copy. The other
	 * file is named only when the caller can read it.
	 *
	 * @param Folder|null $userFolder The caller's folder.
	 * @param int         $fileId     The file.
	 *
	 * @return array{state: string, counterpartFileId: int|null} The state.
	 */
	private function anonymisation(?Folder $userFolder, int $fileId): array {
		[$state, $counterpart] = $this->linkStateOf(fileId: $fileId);

		if ($counterpart <= 0 || $this->readable(userFolder: $userFolder, fileId: $counterpart) === null) {
			return ['state' => $state, 'counterpartFileId' => null];
		}

		return ['state' => $state, 'counterpartFileId' => $counterpart];

	}//end anonymisation()

	/**
	 * Whether the file is a source with an anonymised copy, a redacted copy, or neither, and the other file.
	 *
	 * @param int $fileId The file.
	 *
	 * @return array{0: string, 1: int} The state and the other file's id (0 when there is none).
	 */
	private function linkStateOf(int $fileId): array {
		$source = $this->links->forSource(sourceFileId: $fileId);
		if ($source !== null) {
			return ['anonymised', (int) ($source['anonymizedFileId'] ?? 0)];
		}

		$copy = $this->links->forAnonymized(anonymizedFileId: $fileId);
		if ($copy !== null) {
			return ['derivative', (int) ($copy['sourceFileId'] ?? 0)];
		}

		return ['none', 0];

	}//end linkStateOf()

	/**
	 * OpenRegister's risk level for the file, 'unknown' when it cannot say.
	 *
	 * @param int $fileId The file.
	 *
	 * @return string The level.
	 */
	private function riskLevel(int $fileId): string {
		try {
			$level = (string) $this->container->get(self::RISK)->getRiskLevel($fileId);
		} catch (Throwable) {
			return 'unknown';
		}

		if ($level === '') {
			return 'unknown';
		}

		return $level;

	}//end riskLevel()
}//end class
