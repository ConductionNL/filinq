<?php

/**
 * Dossier File Service
 *
 * Everything the dossier surface does to the FILESYSTEM: the home folder it
 * creates, renames and lists, and the file nodes it resolves, tests for
 * membership and trashes.
 *
 * WHY THIS IS A CLASS OF ITS OWN. These eight methods were the only part of
 * DossierManagementService that touched IRootFolder or IUserSession, and they
 * carried its complexity past every phpmd threshold at once: 1092 lines, 32
 * methods and an overall complexity of 106. Splitting on the dependency,
 * rather than on line count, leaves each class with one thing to be right
 * about — the object side reads and writes OpenRegister, this side reads and
 * writes the filesystem — and neither now needs the other's collaborators.
 *
 * NULL AND '' ARE ANSWERS HERE, NOT FAULTS. A missing folder, an unreadable
 * node and a rename the caller lacks permission for are all ordinary states of
 * a dossier whose files a user can move at any time, so they come back as
 * `null` or as a readable warning. Only a name that cannot be honoured at all
 * throws.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\Files\NotPermittedException;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * The filesystem half of the dossier surface.
 *
 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
 */
class DossierFileService {

	/**
	 * Constructor.
	 *
	 * @param DossierObjectRepository $repository Folder-reference resolution.
	 * @param IRootFolder $rootFolder Nextcloud filesystem root.
	 * @param IUserSession $userSession The current session.
	 * @param LoggerInterface $logger Logger.
	 */
	public function __construct(
		private readonly DossierObjectRepository $repository,
		private readonly IRootFolder $rootFolder,
		private readonly IUserSession $userSession,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Create the dossier's home folder under the caller's Filinq directory.
	 *
	 * @param string $name The dossier name.
	 *
	 * @return Folder The created (or existing) folder.
	 *
	 * @throws RuntimeException When it cannot be created.
	 *
	 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
	 */
	public function createHomeFolder(string $name): Folder {
		$user = $this->userSession->getUser();
		if ($user === null) {
			throw new RuntimeException('Not authenticated.', 401);
		}

		try {
			$userFolder = $this->rootFolder->getUserFolder($user->getUID());
			// Ensure-then-read, rather than if/else or a ternary: phpmd rejects
			// the else and phpcs rejects the inline if, and this reads better
			// than either.
			if ($userFolder->nodeExists('Filinq') === false) {
				$userFolder->newFolder('Filinq');
			}

			$parent = $userFolder->get('Filinq');

			if (($parent instanceof Folder) === false) {
				throw new RuntimeException('Filinq is not a folder.', 500);
			}

			$safe = $this->safeFolderName(name: $name);

			if ($parent->nodeExists($safe) === false) {
				return $parent->newFolder($safe);
			}

			// `get()` answers a Node, and a FILE can carry this name — a user
			// who saved "Mijn dossier" into Filinq/ occupies it. Returning that
			// against a `: Folder` signature is a TypeError, which the catch
			// below rewrites into "Could not create the dossier folder", naming
			// the wrong cause. Say what is actually in the way.
			$existing = $parent->get($safe);
			if (($existing instanceof Folder) === false) {
				throw new RuntimeException(
					'Filinq/' . $safe . ' already exists and is a file, not a folder.',
					500
				);
			}

			return $existing;
		} catch (Throwable $e) {
			throw new RuntimeException('Could not create the dossier folder: ' . $e->getMessage(), 500, $e);
		}

	}//end createHomeFolder()

	/**
	 * Rename the bound home folder to match the dossier, best-effort.
	 *
	 * @param object $object The dossier object.
	 * @param array<string, mixed> $payload Its payload.
	 * @param string $name The new name.
	 *
	 * @return string A readable warning, or '' when the folder was renamed.
	 *
	 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
	 */
	public function renameHomeFolder(object $object, array $payload, string $name): string {
		$folder = $this->homeFolder(object: $object, payload: $payload);
		if ($folder === null) {
			return '';
		}

		$safe = $this->safeFolderName(name: $name);
		if ($folder->getName() === $safe) {
			return '';
		}

		try {
			$parent = $folder->getParent();
			if ($parent->nodeExists($safe) === true) {
				// Never merge or overwrite. The object rename still stands.
				return sprintf('The folder was not renamed: "%s" already exists here.', $safe);
			}

			$folder->move($parent->getPath() . '/' . $safe);

			return '';
		} catch (NotPermittedException $e) {
			return 'The folder was not renamed: you do not have permission to rename it.';
		} catch (Throwable $e) {
			$this->logger->warning(
				'DossierFileService: bound folder rename failed',
				['exception' => $e->getMessage()]
			);

			return 'The folder was not renamed: ' . $e->getMessage();
		}

	}//end renameHomeFolder()

	/**
	 * A folder name safe to write to disk.
	 *
	 * @param string $name The dossier name.
	 *
	 * @return string The sanitised name.
	 *
	 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
	 */
	public function safeFolderName(string $name): string {
		$safe = trim(str_replace(['/', '\\'], '-', $name));
		if ($safe === '') {
			// A name that is nothing but whitespace still has to land
			// somewhere the operator can find it. A name of separators does
			// NOT reach here: they become dashes first, so "///" is the
			// perfectly valid folder "---".
			return 'Dossier';
		}

		return $safe;

	}//end safeFolderName()

	/**
	 * The dossier's bound home folder, under the caller's view.
	 *
	 * @param object $object The dossier object.
	 * @param array<string, mixed> $payload Its payload.
	 *
	 * @return Folder|null The folder, or null when it is gone or unreadable.
	 *
	 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
	 */
	public function homeFolder(object $object, array $payload): ?Folder {
		try {
			$ref = ($payload['@self']['folder'] ?? null);
			if ($ref === null && method_exists($object, 'getFolder') === true) {
				$ref = $object->getFolder();
			}

			if ($ref === null || $ref === '') {
				return null;
			}

			return $this->repository->resolveDossierFolder(folderRef: $ref);
		} catch (Throwable $e) {
			return null;
		}

	}//end homeFolder()

	/**
	 * List the files in a folder under the caller's view.
	 *
	 * Deliberately NOT `FolderFileEnumerator::enumerate()`. That method builds
	 * an ANALYSIS QUEUE and filters out prior anonymisation outputs so a second
	 * run does not re-redact its own results. A dossier's document list is the
	 * opposite question — the operator wants to see everything the dossier
	 * holds, redacted copies included — so reusing it here would hide files
	 * from the very list that exists to show them.
	 *
	 * @param Folder $folder The dossier home folder.
	 *
	 * @return array<int, Node> The files, never folders.
	 *
	 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
	 */
	public function enumerateFolder(Folder $folder): array {
		try {
			return array_values(array_filter(
				$folder->getDirectoryListing(),
				static fn (Node $node): bool => $node->getType() === \OCP\Files\FileInfo::TYPE_FILE
			));
		} catch (Throwable $e) {
			$this->logger->warning(
				'DossierFileService: cannot list the dossier folder',
				['exception' => $e->getMessage()]
			);
			return [];
		}

	}//end enumerateFolder()

	/**
	 * Resolve a file node under the caller's view.
	 *
	 * @param int $fileId The Nextcloud file node id.
	 *
	 * @return Node|null The node, or null when absent or unreadable.
	 *
	 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
	 */
	public function nodeFor(int $fileId): ?Node {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return null;
		}

		try {
			$nodes = $this->rootFolder->getUserFolder($user->getUID())->getById($fileId);

			return ($nodes[0] ?? null);
		} catch (Throwable $e) {
			// `Throwable` alone: naming NotFoundException beside it caught
			// nothing extra, and phpstan reports the unreachable arm.
			return null;
		}

	}//end nodeFor()

	/**
	 * Whether a node sits inside a folder.
	 *
	 * @param Node $node The node.
	 * @param Folder $folder The folder.
	 *
	 * @return bool True when the node is inside.
	 *
	 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
	 */
	public function isInFolder(Node $node, Folder $folder): bool {
		try {
			return str_starts_with($node->getPath(), rtrim($folder->getPath(), '/') . '/');
		} catch (Throwable $e) {
			return false;
		}

	}//end isInFolder()

	/**
	 * Move a file to the trashbin.
	 *
	 * @param int $fileId The Nextcloud file node id.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
	 */
	public function trash(int $fileId): void {
		$node = $this->nodeFor(fileId: $fileId);
		if ($node === null) {
			return;
		}

		try {
			// NC's delete() routes through the trashbin when files_trashbin is
			// enabled, which is what makes this recoverable.
			$node->delete();
		} catch (Throwable $e) {
			$this->logger->warning(
				'DossierFileService: could not remove the document',
				['fileId' => $fileId, 'exception' => $e->getMessage()]
			);
		}

	}//end trash()

}//end class
