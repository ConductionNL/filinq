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
 * @spec openspec/changes/entity-search/tasks.md#task-2.2
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
	private const RISK = 'OCA\OpenRegister\Service\RiskLevelService';

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
	 * @return array{documents: array<int, array<string, mixed>>, noAccess: int, other: array<int, array{kind: string, count: int}>, occurrenceCount: int}
	 *
	 * @spec openspec/changes/entity-search/tasks.md#task-2.2
	 */
	public function forUser(array $relations, string $userId): array {
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

		$others = [];
		foreach ($other as $kind => $count) {
			if ($count > 0) {
				$others[] = ['kind' => $kind, 'count' => $count];
			}
		}

		return [
			'documents' => $documents,
			'noAccess' => $noAccess,
			'other' => $others,
			'occurrenceCount' => count($relations),
		];

	}//end forUser()

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

		try {
			$node = $userFolder->getFirstNodeById($fileId);
		} catch (Throwable) {
			return null;
		}

		if ($node instanceof File === false || $node->isReadable() === false) {
			return null;
		}

		return $node;

	}//end readable()

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
			// Walk up until a dossier folder or the root, whose getParent() throws.
			$parent = $node->getParent();
			for ($depth = 0; $depth < 64; $depth++) {
				if (isset($dossiers[(int) $parent->getId()]) === true) {
					return $dossiers[(int) $parent->getId()];
				}

				$parent = $parent->getParent();
			}
		} catch (Throwable) {
			return null;
		}

		return null;

	}//end dossierOf()

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
		$state = 'none';
		$counterpart = 0;
		$source = $this->links->forSource(sourceFileId: $fileId);
		if ($source !== null) {
			$state = 'anonymised';
			$counterpart = (int) ($source['anonymizedFileId'] ?? 0);
		}

		if ($source === null) {
			$copy = $this->links->forAnonymized(anonymizedFileId: $fileId);
			if ($copy !== null) {
				$state = 'derivative';
				$counterpart = (int) ($copy['sourceFileId'] ?? 0);
			}
		}

		if ($counterpart <= 0 || $this->readable(userFolder: $userFolder, fileId: $counterpart) === null) {
			return ['state' => $state, 'counterpartFileId' => null];
		}

		return ['state' => $state, 'counterpartFileId' => $counterpart];

	}//end anonymisation()

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
