<?php

/**
 * Dossier Management Service
 *
 * Index and detail aggregation for the dossier surface, plus the membership
 * operations behind it: create, rename (with bound-folder sync), link and
 * unlink documents, and lifecycle transitions.
 *
 * MEMBERSHIP IS A UNION, NOT A FOLDER. A dossier keeps a bound home folder
 * (`@self.folder`) as the physical home of the files it owns, but membership
 * is the deduplicated union of that folder's files and the explicit
 * `documents[]` references — so one document can belong to several dossiers
 * without being copied. A reference whose target is gone or unreadable is
 * surfaced as a visible marker and never silently dropped: a dossier that
 * quietly lists four of its five documents is worse than one that says the
 * fifth is missing.
 *
 * WRITES ARE FULL-PAYLOAD. OpenRegister saves are PUT-semantic, so every
 * update here carries the whole object forward. A rename that posted only
 * `name` would null `bases`, `checkedOn`, `status` and `documents`.
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

use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Aggregates and mutates dossiers for the dossier-management surface.
 *
 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects) The aggregation deliberately
 *     wires existing capabilities (bases, folder enumeration, batch state,
 *     anonymisation links) rather than reimplementing any of them.
 */
class DossierManagementService {

	/**
	 * The register every filinq schema lives in.
	 *
	 * Aliased rather than repeated: DossierObjectReader owns the definition
	 * now that three classes read it, and a second literal here is a second
	 * thing to forget when the register moves.
	 *
	 * @var string
	 */
	private const REGISTER = DossierObjectReader::REGISTER;

	/**
	 * The dossier schema slug.
	 *
	 * @var string
	 */
	private const SCHEMA = DossierObjectReader::SCHEMA;

	/**
	 * The status a dossier without one is read as.
	 *
	 * `status` is optional and existing objects were deliberately not
	 * migrated, so absence is a value with a meaning, not missing data.
	 *
	 * @var string
	 */
	public const DEFAULT_STATUS = DossierObjectReader::DEFAULT_STATUS;

	/**
	 * The declared lifecycle, mirroring `x-openregister-lifecycle` on the schema.
	 *
	 * Mirrored, not owned: OpenRegister's guard is the authority and rejects an
	 * out-of-order write regardless of what this map says. It exists so the UI
	 * can offer only the legal targets instead of offering all five and letting
	 * the operator discover the rule by being refused.
	 *
	 * @var array<string, list<string>>
	 */
	private const TRANSITIONS = [
		'open' => ['in-review'],
		'in-review' => ['processed', 'open'],
		'processed' => ['published', 'closed'],
		'published' => ['closed'],
		'closed' => [],
	];

	/**
	 * Constructor.
	 *
	 * @param DossierObjectRepository $repository Dossier object + folder resolution.
	 * @param DossierFileService $files The filesystem half: home folders and file nodes.
	 * @param DossierContextService $context The live context a dossier is shown with.
	 * @param DossierObjectReader $reader Object-shape reading.
	 * @param LoggerInterface $logger Logger.
	 */
	public function __construct(
		private readonly DossierObjectRepository $repository,
		private readonly DossierFileService $files,
		private readonly DossierContextService $context,
		private readonly DossierObjectReader $reader,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Every dossier the caller can read, with its live context.
	 *
	 * @return array<int, array<string, mixed>> Index rows.
	 *
	 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
	 */
	public function index(): array {
		$objectService = $this->requireObjectService();

		$objects = $this->reader->findAllOf(objectService: $objectService, schema: self::SCHEMA);

		$rows = [];
		foreach ($objects as $object) {
			$payload = $this->reader->payloadOf(object: $object);
			$uuid = $this->reader->uuidOf(object: $object, payload: $payload);
			if ($uuid === '') {
				continue;
			}

			$members = $this->context->members(object: $object, payload: $payload);

			$rows[] = [
				'id' => $uuid,
				'name' => (string)($payload['name'] ?? ''),
				'description' => (string)($payload['description'] ?? ''),
				'status' => $this->reader->statusOf(payload: $payload),
				'checkedOn' => (string)($payload['checkedOn'] ?? ''),
				'documentCount' => count($members['documents']),
				'missingCount' => $members['missing'],
				'bases' => $this->context->resolveBases(payload: $payload),
			];
		}

		return $rows;

	}//end index()

	/**
	 * One dossier with its documents, grondslagen, batch runs and publication state.
	 *
	 * @param string $dossierId The dossier object UUID.
	 *
	 * @return array<string, mixed> The aggregated dossier.
	 *
	 * @throws RuntimeException When the caller cannot read the dossier.
	 *
	 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
	 */
	public function detail(string $dossierId): array {
		$object = $this->requireReadable(dossierId: $dossierId);
		$payload = $this->reader->payloadOf(object: $object);
		$members = $this->context->members(object: $object, payload: $payload);
		$status = $this->reader->statusOf(payload: $payload);

		return [
			'id' => $dossierId,
			'name' => (string)($payload['name'] ?? ''),
			'description' => (string)($payload['description'] ?? ''),
			'status' => $status,
			'availableTransitions' => (self::TRANSITIONS[$status] ?? []),
			'checkedOn' => (string)($payload['checkedOn'] ?? ''),
			'bases' => $this->context->resolveBases(payload: $payload),
			'documents' => $members['documents'],
			'batchRuns' => $this->context->batchRuns(object: $object, payload: $payload),
			'publication' => $this->context->publication(),
			'capabilities' => [
				// Presence gates. Each optional capability is HIDDEN when
				// absent, never offered-and-broken — a "mark as checked" action
				// that 404s is worse than no action at all.
				//
				// Both are in-app capabilities that have not shipped yet
				// (anonymization-review-workbench, woo-publicatie-pipeline), so
				// they are reported absent from the one place that decides it.
				// When either lands, flip it here and the detail picks it up.
				'reviewWorkbench' => false,
				'publicationPipeline' => $this->context->publication()['installed'],
			],
		];

	}//end detail()

	/**
	 * Create a dossier bound to a home folder.
	 *
	 * @param string $name The dossier name; also the home-folder name.
	 * @param string $description Optional free text.
	 * @param array<int, string> $bases Grondslag slugs.
	 *
	 * @return array<string, mixed> The created dossier's detail shape.
	 *
	 * @throws RuntimeException When the name is empty or the save fails.
	 *
	 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
	 */
	public function create(string $name, string $description = '', array $bases = []): array {
		$name = trim($name);
		if ($name === '') {
			throw new RuntimeException('A dossier needs a name.', 400);
		}

		$objectService = $this->requireObjectService();
		$folder = $this->files->createHomeFolder(name: $name);

		$saved = $objectService->saveObject(
			object: [
				'@self' => [
					'register' => self::REGISTER,
					'schema' => self::SCHEMA,
					'folder' => $folder->getId(),
				],
				'name' => $name,
				'description' => $description,
				'bases' => array_values($bases),
				'documents' => [],
				// Written explicitly, not left to the reader's default.
				// OpenRegister's lifecycle guard compares the STORED value, and
				// an absent status is `""` to it — from which no transition is
				// declared. A dossier created without this could never take its
				// first transition: the UI offered "Start review" and the save
				// came back 409 "No transition allows moving from ''".
				'status' => self::DEFAULT_STATUS,
			],
			register: self::REGISTER,
			schema: self::SCHEMA
		);

		$payload = $this->reader->payloadOf(object: $saved);

		return $this->detail(dossierId: $this->reader->uuidOf(object: $saved, payload: $payload));

	}//end create()

	/**
	 * Rename a dossier and keep its bound home folder in sync.
	 *
	 * The object rename ALWAYS succeeds; the folder rename is best-effort and
	 * reports rather than blocks. A caller without write permission, or a
	 * sibling already holding the target name, must not cost the operator
	 * their rename — and a name collision must never merge two folders.
	 *
	 * @param string $dossierId The dossier object UUID.
	 * @param string $name The new name.
	 *
	 * @return array<string, mixed> The detail shape plus a `folderWarning` key.
	 *
	 * @throws RuntimeException When the name is empty or the caller cannot read the dossier.
	 *
	 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
	 */
	public function rename(string $dossierId, string $name): array {
		$name = trim($name);
		if ($name === '') {
			throw new RuntimeException('A dossier needs a name.', 400);
		}

		$object = $this->requireReadable(dossierId: $dossierId);
		$payload = $this->reader->payloadOf(object: $object);

		$warning = $this->files->renameHomeFolder(object: $object, payload: $payload, name: $name);
		$this->save(payload: ($payload + []), changes: ['name' => $name]);

		$detail = $this->detail(dossierId: $dossierId);
		$detail['folderWarning'] = $warning;

		return $detail;

	}//end rename()

	/**
	 * Move a dossier to a new lifecycle status.
	 *
	 * @param string $dossierId The dossier object UUID.
	 * @param string $status The target status.
	 *
	 * @return array<string, mixed> The refreshed detail shape.
	 *
	 * @throws RuntimeException When the transition is not declared, or the save is refused.
	 *
	 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
	 */
	public function transition(string $dossierId, string $status): array {
		$object = $this->requireReadable(dossierId: $dossierId);
		$payload = $this->reader->payloadOf(object: $object);
		$current = $this->reader->statusOf(payload: $payload);

		if (in_array($status, (self::TRANSITIONS[$current] ?? []), true) === false) {
			// Refused here as well as by OpenRegister. The server-side guard is
			// the authority, but answering 409 with both states reads far
			// better than the generic rejection the guard produces.
			throw new RuntimeException(
				sprintf('A dossier cannot move from "%s" to "%s".', $current, $status),
				409
			);
		}

		// A dossier stored before `status` existed holds no value, and the
		// lifecycle guard reads that as `""` rather than as the initial state —
		// so every legacy dossier is frozen until the initial state is written
		// once. Settle it first, then transition. Both writes are full-payload.
		if (trim((string)($payload['status'] ?? '')) === '' && $status !== self::DEFAULT_STATUS) {
			$this->save(payload: $payload, changes: ['status' => self::DEFAULT_STATUS]);
			$payload['status'] = self::DEFAULT_STATUS;
		}

		$this->save(payload: $payload, changes: ['status' => $status]);

		return $this->detail(dossierId: $dossierId);

	}//end transition()

	/**
	 * Add an existing file to a dossier by reference, without moving it.
	 *
	 * Link semantics: the file stays where it is and the dossier gains a
	 * reference, so the same document can be a member of several dossiers.
	 *
	 * @param string $dossierId The dossier object UUID.
	 * @param int $fileId The Nextcloud file node id.
	 *
	 * @return array<string, mixed> The refreshed detail shape.
	 *
	 * @throws RuntimeException When the caller cannot read the dossier or the file.
	 *
	 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
	 */
	public function linkDocument(string $dossierId, int $fileId): array {
		$object = $this->requireReadable(dossierId: $dossierId);
		$payload = $this->reader->payloadOf(object: $object);

		if ($this->files->nodeFor(fileId: $fileId) === null) {
			throw new RuntimeException('That file does not exist or you cannot read it.', 404);
		}

		$documents = $this->reader->documentRefs(payload: $payload);
		if (in_array((string)$fileId, $documents, true) === false) {
			$documents[] = (string)$fileId;
			$this->save(payload: $payload, changes: ['documents' => $documents]);
		}

		return $this->detail(dossierId: $dossierId);

	}//end linkDocument()

	/**
	 * Remove a document from a dossier.
	 *
	 * Two different operations behind one verb, and the caller is told which
	 * one it will be by {@see self::removalMode()} before confirming:
	 *
	 *  - The file lives in this dossier's home folder and no other dossier
	 *    references it → it is moved to the TRASHBIN, recoverable. Never a
	 *    hard delete.
	 *  - The file is a reference (lives elsewhere, or another dossier also
	 *    holds it) → only this dossier's membership reference is dropped and
	 *    the file is untouched.
	 *
	 * @param string $dossierId The dossier object UUID.
	 * @param int $fileId The Nextcloud file node id.
	 *
	 * @return array<string, mixed> The refreshed detail shape.
	 *
	 * @throws RuntimeException When the caller cannot read the dossier.
	 *
	 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
	 */
	public function removeDocument(string $dossierId, int $fileId): array {
		$object = $this->requireReadable(dossierId: $dossierId);
		$payload = $this->reader->payloadOf(object: $object);

		$documents = $this->reader->documentRefs(payload: $payload);
		$remaining = array_values(array_filter(
			$documents,
			static fn (string $ref): bool => $ref !== (string)$fileId
		));

		if ($remaining !== $documents) {
			$this->save(payload: $payload, changes: ['documents' => $remaining]);
		}

		if ($this->removalMode(dossierId: $dossierId, fileId: $fileId) === 'trash') {
			$this->files->trash(fileId: $fileId);
		}

		return $this->detail(dossierId: $dossierId);

	}//end removeDocument()

	/**
	 * Whether removing this file would trash it or merely unlink it.
	 *
	 * Exposed so the confirmation dialog can SAY which one it is. A confirm
	 * that reads "remove?" for both is how an operator deletes a file they
	 * meant to unlink.
	 *
	 * @param string $dossierId The dossier object UUID.
	 * @param int $fileId The Nextcloud file node id.
	 *
	 * @return string Either 'trash' or 'unlink'.
	 *
	 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md
	 */
	public function removalMode(string $dossierId, int $fileId): string {
		try {
			$object = $this->requireReadable(dossierId: $dossierId);
			$payload = $this->reader->payloadOf(object: $object);
		} catch (Throwable $e) {
			return 'unlink';
		}

		$node = $this->files->nodeFor(fileId: $fileId);
		if ($node === null) {
			return 'unlink';
		}

		$folder = $this->files->homeFolder(object: $object, payload: $payload);
		if ($folder === null || $this->files->isInFolder(node: $node, folder: $folder) === false) {
			return 'unlink';
		}

		if ($this->referencedElsewhere(dossierId: $dossierId, fileId: $fileId) === true) {
			return 'unlink';
		}

		return 'trash';

	}//end removalMode()

	// -----------------------------------------------------------------
	// Aggregation helpers
	// -----------------------------------------------------------------

	// -----------------------------------------------------------------
	// Object + filesystem helpers
	// -----------------------------------------------------------------

	/**
	 * The dossier object, or a refusal.
	 *
	 * ⚠️ The guard is only as strong as the schema's cascade. `dossier`
	 * declares `read: ["authenticated"]`, so OpenRegister admits any
	 * authenticated user in the organisation and this resolves to an existence
	 * test. That is enforced HERE rather than bypassed: passing `_rbac: false`
	 * would make the endpoint unconditionally open, and tightening the cascade
	 * is a separate decision (ConductionNL/filinq#441). The FILE half is real —
	 * every listing below runs through the caller's own view.
	 *
	 * @param string $dossierId The dossier object UUID.
	 *
	 * @return object The dossier object.
	 *
	 * @throws RuntimeException When it is absent or unreadable.
	 */
	private function requireReadable(string $dossierId): object {
		$objectService = $this->requireObjectService();

		$object = $objectService->find(
			id: $dossierId,
			register: self::REGISTER,
			schema: self::SCHEMA
		);

		if ($object === null) {
			throw new RuntimeException('Dossier not found, or you cannot read it: ' . $dossierId, 404);
		}

		return $object;

	}//end requireReadable()

	/**
	 * OpenRegister's object service, or a refusal.
	 *
	 * @return object The object service.
	 *
	 * @throws RuntimeException When OpenRegister is unavailable.
	 */
	private function requireObjectService(): object {
		$objectService = $this->repository->objectService();
		if ($objectService === null) {
			throw new RuntimeException('OpenRegister is not available.', 503);
		}

		return $objectService;

	}//end requireObjectService()

	/**
	 * Write changes onto a dossier as a FULL payload.
	 *
	 * THE PAYLOAD IS THE WHOLE INPUT. This used to take the object too and
	 * never read it, which phpmd reports as an unused parameter and which cost
	 * every call site a named argument that carried nothing: the register and
	 * schema are constants and the identity travels inside `@self`.
	 *
	 * @param array<string, mixed> $payload Its current payload.
	 * @param array<string, mixed> $changes The fields to change.
	 *
	 * @return void
	 *
	 * @throws RuntimeException When the save is refused.
	 */
	private function save(array $payload, array $changes): void {
		$objectService = $this->requireObjectService();

		// Everything forward, then the change on top. OR saves are
		// PUT-semantic: posting only the changed key nulls the rest.
		$next = ($payload + []);
		foreach ($changes as $key => $value) {
			$next[$key] = $value;
		}

		$self = ($payload['@self'] ?? []);
		if (is_array($self) === false) {
			$self = [];
		}

		$self['register'] = self::REGISTER;
		$self['schema'] = self::SCHEMA;
		$next['@self'] = $self;

		try {
			$objectService->saveObject(
				object: $next,
				register: self::REGISTER,
				schema: self::SCHEMA
			);
		} catch (Throwable $e) {
			// A lifecycle refusal arrives here. Surfacing the message keeps
			// the rejection readable instead of a bare 500.
			throw new RuntimeException($e->getMessage(), 409, $e);
		}

	}//end save()

	/**
	 * Whether another dossier also references this file.
	 *
	 * @param string $dossierId The dossier being removed from.
	 * @param int $fileId The Nextcloud file node id.
	 *
	 * @return bool True when at least one other dossier references it.
	 */
	private function referencedElsewhere(string $dossierId, int $fileId): bool {
		$objectService = $this->repository->objectService();
		if ($objectService === null) {
			return false;
		}

		try {
			$objects = $this->reader->findAllOf(objectService: $objectService, schema: self::SCHEMA);
		} catch (Throwable $e) {
			// Fail SAFE: unable to prove the file is unreferenced, so treat it
			// as referenced and unlink rather than trash.
			return true;
		}

		foreach ($objects as $object) {
			$payload = $this->reader->payloadOf(object: $object);
			if ($this->reader->uuidOf(object: $object, payload: $payload) === $dossierId) {
				continue;
			}

			if (in_array((string)$fileId, $this->reader->documentRefs(payload: $payload), true) === true) {
				return true;
			}
		}

		return false;

	}//end referencedElsewhere()

	// -----------------------------------------------------------------
	// Payload helpers
	// -----------------------------------------------------------------

}//end class
