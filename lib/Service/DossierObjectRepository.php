<?php

/**
 * Dossier Object Repository
 *
 * Owns every OpenRegister-object read and write the grondslagen report needs:
 * resolving OR's optional services, loading a dossier's context, and writing
 * back the `configuration.grondslagen` freshness metadata.
 *
 * Extracted from {@see LegalBasesSummaryService} so that knowledge of OR's
 * object shape — the `getObject()` payload, the entity-level `folder` column
 * that lives OUTSIDE that payload, and the save-path folder-preservation
 * dance — sits in one place instead of being spread through the renderer.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/anonymisation-grondslagen-summary/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use Exception;
use OCP\App\IAppManager;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IUserSession;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Reads and writes the OpenRegister dossier objects the report depends on.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class DossierObjectRepository {

	/**
	 * Fully-qualified name of OpenRegister's ObjectEntity.
	 *
	 * Referenced as a string, not as a `::class` constant: OpenRegister is an
	 * optional dependency and must never be autoloaded by a type reference.
	 *
	 * @var string
	 */
	private const OBJECT_ENTITY_CLASS = '\OCA\OpenRegister\Db\ObjectEntity';

	/**
	 * Constructor.
	 *
	 * @param IAppManager $appManager App-availability check for OpenRegister.
	 * @param ContainerInterface $container DI container for OpenRegister-side services.
	 * @param IRootFolder $rootFolder Nextcloud file API entry point.
	 * @param IUserSession $userSession Session-user lookup.
	 * @param LoggerInterface $logger Structured logger.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IAppManager $appManager,
		private readonly ContainerInterface $container,
		private readonly IRootFolder $rootFolder,
		private readonly IUserSession $userSession,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Resolve the dossier's `@self.folder` reference to a Nextcloud Folder node.
	 *
	 * @param mixed $folderRef The raw reference value — typically a file-node id (int/string).
	 *
	 * @return Folder The dossier's folder.
	 *
	 * @throws RuntimeException When the reference cannot be resolved.
	 */
	public function resolveDossierFolder(mixed $folderRef): Folder {
		if ($folderRef === null || $folderRef === '') {
			throw new RuntimeException('Grondslagen summary: dossier has no @self.folder reference.');
		}

		try {
			$user = $this->userSession->getUser();
			if ($user === null) {
				throw new RuntimeException('Grondslagen summary: no session user to resolve folder.');
			}

			$userFolder = $this->rootFolder->getUserFolder($user->getUID());
			$nodes = $userFolder->getById((int)$folderRef);
			$node = ($nodes[0] ?? null);
			if ($node === null) {
				throw new RuntimeException(
					'Grondslagen summary: folder node id ' . ((string)$folderRef) . ' not found for user ' . $user->getUID()
				);
			}
		} catch (NotFoundException $e) {
			throw new RuntimeException(
				'Grondslagen summary: dossier folder not found (' . ((string)$folderRef) . '): ' . $e->getMessage(),
				previous: $e
			);
		}

		if (($node instanceof Folder) === false) {
			throw new RuntimeException(
				'Grondslagen summary: dossier @self.folder (' . ((string)$folderRef) . ') is not a folder node.'
			);
		}

		return $node;
	}//end resolveDossierFolder()

	/**
	 * Get the OpenRegister ObjectService, or null when unavailable.
	 *
	 * @return object|null The ObjectService instance, or null.
	 *
	 * @spec openspec/specs/anonymisation-grondslagen-summary/spec.md#requirement-a-per-dossier-summary-endpoint-must-exist
	 */
	public function objectService(): ?object {
		if ($this->isOpenRegisterAvailable() === false) {
			return null;
		}

		try {
			return $this->container->get('OCA\OpenRegister\Service\ObjectService');
		} catch (Exception $e) {
			$this->logger->warning(
				'LegalBasesSummaryService: ObjectService unavailable',
				['error' => $e->getMessage()]
			);
			return null;
		}

	}//end objectService()

	/**
	 * Get the OpenRegister EntityRelationMapper, or null when unavailable.
	 *
	 * @return object|null The EntityRelationMapper instance, or null.
	 *
	 * @spec openspec/specs/anonymisation-grondslagen-summary/spec.md#requirement-a-per-dossier-summary-endpoint-must-exist
	 */
	public function entityRelationMapper(): ?object {
		if ($this->isOpenRegisterAvailable() === false) {
			return null;
		}

		try {
			return $this->container->get('OCA\OpenRegister\Db\EntityRelationMapper');
		} catch (Exception $e) {
			$this->logger->warning(
				'LegalBasesSummaryService: EntityRelationMapper unavailable',
				['error' => $e->getMessage()]
			);
			return null;
		}

	}//end entityRelationMapper()

	/**
	 * Load the minimum dossier context the renderer needs.
	 *
	 * @param string $dossierUuid The OR object UUID.
	 *
	 * @return array<string, mixed> `{name, description, checkedOn, folderRef, configuration}`.
	 *
	 * @throws RuntimeException When the dossier cannot be resolved.
	 */
	public function loadDossierContext(string $dossierUuid): array {
		$object = $this->findDossier(dossierUuid: $dossierUuid);
		$payload = $this->payloadOf(object: $object);

		return [
			'name' => (string)($payload['name'] ?? ''),
			'description' => (string)($payload['description'] ?? ''),
			'checkedOn' => (string)($payload['checkedOn'] ?? ''),
			'folderRef' => $this->folderRefOf(object: $object, payload: $payload),
			'configuration' => ($payload['configuration'] ?? []),
		];

	}//end loadDossierContext()

	/**
	 * Update the dossier object's `configuration.grondslagen.{fileId, lastGeneratedAt}`.
	 *
	 * Failure is logged but does not roll back the rendered file — the PDF
	 * is on disk and the operator can find it; the metadata refresh is
	 * convenience for the dossier UI's freshness-badge.
	 *
	 * @param string $dossierUuid The OR dossier object UUID.
	 * @param int $summaryFileId The newly-written summary file's NC node id.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/anonymisation-grondslagen-summary/spec.md#requirement-a-per-dossier-summary-endpoint-must-exist
	 */
	public function updateDossierConfiguration(string $dossierUuid, int $summaryFileId): void {
		$objectService = $this->objectService();
		if ($objectService === null) {
			$this->logger->warning(
				'LegalBasesSummaryService: cannot update dossier configuration — ObjectService unavailable',
				['dossierUuid' => $dossierUuid]
			);
			return;
		}

		try {
			$object = $objectService->find(
				id: $dossierUuid,
				register: 'filinq',
				schema: 'dossier',
				_rbac: false,
				_multitenancy: false
			);
			if ($object === null) {
				return;
			}

			$payload = $this->withGrondslagenConfiguration(
				payload: $this->payloadOf(object: $object),
				summaryFileId: $summaryFileId
			);

			$objectService->saveObject(
				object: $this->withPreservedFolderRef(
					object: $object,
					payload: $payload,
					dossierUuid: $dossierUuid
				),
				register: 'filinq',
				schema: 'dossier',
				uuid: $dossierUuid,
				_rbac: false,
				_multitenancy: false
			);
		} catch (Exception $e) {
			$this->logger->warning(
				'LegalBasesSummaryService: failed to update dossier configuration.grondslagen',
				['dossierUuid' => $dossierUuid, 'error' => $e->getMessage()]
			);
		}//end try

	}//end updateDossierConfiguration()

	/**
	 * Resolve one dossier object with RBAC disabled, or throw.
	 *
	 * @param string $dossierUuid The OR object UUID.
	 *
	 * @return mixed The resolved dossier object (never null).
	 *
	 * @throws RuntimeException When OR is unavailable, the lookup fails, or nothing matches.
	 */
	private function findDossier(string $dossierUuid): mixed {
		$objectService = $this->objectService();
		if ($objectService === null) {
			throw new RuntimeException('Grondslagen summary: OpenRegister ObjectService unavailable.');
		}

		try {
			$object = $objectService->find(
				id: $dossierUuid,
				register: 'filinq',
				schema: 'dossier',
				_rbac: false,
				_multitenancy: false
			);
		} catch (Exception $e) {
			throw new RuntimeException(
				'Grondslagen summary: failed to load dossier ' . $dossierUuid . ': ' . $e->getMessage(),
				previous: $e
			);
		}

		if ($object === null) {
			throw new RuntimeException('Grondslagen summary: dossier not found: ' . $dossierUuid);
		}

		return $object;
	}//end findDossier()

	/**
	 * Reduce an OR object to its schema-typed payload array.
	 *
	 * @param mixed $object The raw ObjectService result.
	 *
	 * @return array<string, mixed> The payload.
	 */
	private function payloadOf(mixed $object): array {
		if (is_object($object) === true && method_exists($object, 'getObject') === true) {
			return $object->getObject();
		}

		return (array)$object;
	}//end payloadOf()

	/**
	 * Resolve the dossier's `@self.folder` reference.
	 *
	 * The `@self.folder` reference is stored on the ObjectEntity's `folder`
	 * column, NOT inside the schema-typed payload returned by `getObject()`.
	 * OR's renderer reconstructs the `@self` block from the entity's columns
	 * when serialising for the API, but in-process callers must read the
	 * columns directly. Read the entity-level getter first; fall back to a
	 * payload-embedded `@self.folder` for future-compat in case the renderer
	 * ever inlines it.
	 *
	 * @param mixed $object The raw ObjectService result.
	 * @param array<string, mixed> $payload The schema-typed payload.
	 *
	 * @return mixed The folder reference, or null.
	 */
	private function folderRefOf(mixed $object, array $payload): mixed {
		$folderRef = $this->entityFolderRef(object: $object);
		if ($folderRef !== null && $folderRef !== '') {
			return $folderRef;
		}

		$self = ($payload['@self'] ?? []);

		return ($self['folder'] ?? null);
	}//end folderRefOf()

	/**
	 * Read the `folder` column off an OpenRegister ObjectEntity.
	 *
	 * `getFolder` is a magic method on Nextcloud's `Entity` base class
	 * (auto-generated via `__call`, declared only as `@method`), so
	 * `method_exists` returns false even when the call works. Probe via
	 * `ObjectEntity` instanceof, then invoke directly. OpenRegister's lib is
	 * an optional dependency, so the class is probed by name.
	 *
	 * @param mixed $object The raw ObjectService result.
	 *
	 * @return mixed The folder reference, or null when unavailable.
	 */
	private function entityFolderRef(mixed $object): mixed {
		$objectEntityClass = self::OBJECT_ENTITY_CLASS;
		if (is_object($object) === false
			|| class_exists($objectEntityClass) === false
			|| ($object instanceof $objectEntityClass) === false
		) {
			return null;
		}

		try {
			return $object->getFolder();
		} catch (\Throwable $e) {
			return null;
		}

	}//end entityFolderRef()

	/**
	 * Merge the grondslagen freshness metadata into a dossier payload.
	 *
	 * @param array<string, mixed> $payload The dossier payload.
	 * @param int $summaryFileId The summary file's NC node id.
	 *
	 * @return array<string, mixed> The payload with `configuration.grondslagen` set.
	 */
	private function withGrondslagenConfiguration(array $payload, int $summaryFileId): array {
		$configuration = [];
		if (is_array(($payload['configuration'] ?? null)) === true) {
			$configuration = $payload['configuration'];
		}

		$grondslagen = [];
		if (is_array(($configuration['grondslagen'] ?? null)) === true) {
			$grondslagen = $configuration['grondslagen'];
		}

		$grondslagen['fileId'] = $summaryFileId;
		$grondslagen['lastGeneratedAt'] = date('c');
		$configuration['grondslagen'] = $grondslagen;
		$payload['configuration'] = $configuration;

		return $payload;
	}//end withGrondslagenConfiguration()

	/**
	 * Re-inject the dossier's existing `@self.folder` before saving.
	 *
	 * `getObject()` returns the schema-typed payload only — the `folder`
	 * column lives on the ObjectEntity itself. Without explicit
	 * re-injection, OR's save path sees no folder ref on the incoming
	 * payload, hands the object to `ensureObjectFolderExists`, and that
	 * helper auto-creates a brand-new folder under the register's storage
	 * tree — overwriting `_folder` with the auto-folder's id. Operators see
	 * their original dossier folder mysteriously replaced by a generated one
	 * in OR's `Open Registers` folder.
	 *
	 * OR's `setSelfMetadata` reads `@self.folder` and re-applies it via
	 * `setFolder()` on save (per the `validate-self-folder-access` change),
	 * so the original folder binding is preserved.
	 *
	 * @param mixed $object The raw ObjectService result.
	 * @param array<string, mixed> $payload The payload about to be saved.
	 * @param string $dossierUuid The dossier UUID (log context).
	 *
	 * @return array<string, mixed> The payload, with `@self.folder` preserved when readable.
	 */
	private function withPreservedFolderRef(mixed $object, array $payload, string $dossierUuid): array {
		$objectEntityClass = self::OBJECT_ENTITY_CLASS;
		if (is_object($object) === false
			|| class_exists($objectEntityClass) === false
			|| ($object instanceof $objectEntityClass) === false
		) {
			return $payload;
		}

		try {
			$existingFolder = $object->getFolder();
		} catch (\Throwable $e) {
			// Folder probe failure must not abort the configuration update —
			// log and proceed with the save (the user-visible PDF is already
			// on disk).
			$this->logger->warning(
				'LegalBasesSummaryService: could not read existing folder ref before save',
				['dossierUuid' => $dossierUuid, 'error' => $e->getMessage()]
			);
			return $payload;
		}

		if (is_string($existingFolder) === false || $existingFolder === '') {
			return $payload;
		}

		$self = ($payload['@self'] ?? []);
		if (is_array($self) === false) {
			$self = [];
		}

		$self['folder'] = $existingFolder;
		$payload['@self'] = $self;

		return $payload;
	}//end withPreservedFolderRef()

	/**
	 * True when the OpenRegister app is installed and enabled.
	 *
	 * @return bool
	 */
	private function isOpenRegisterAvailable(): bool {
		return in_array('openregister', $this->appManager->getInstalledApps(), true);
	}//end isOpenRegisterAvailable()
}//end class
