<?php

/**
 * Stubs for OpenRegister classes used in tests
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2025 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\OpenRegister\Service;

/**
 * Stub for ObjectService
 *
 * @category Tests
 * @package  OCA\OpenRegister\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class ObjectService {
	/**
	 * Find an object by id
	 *
	 * @param string $id Object UUID
	 * @param string $register Register slug
	 * @param string $schema Schema slug
	 * @param bool $_rbac RBAC bypass flag.
	 * @param bool $_multitenancy Multitenancy bypass flag.
	 *
	 * @return mixed
	 */
	public function find(
		string $id = '',
		string $register = '',
		string $schema = '',
		bool $_rbac = true,
		bool $_multitenancy = true,
	) {
		return null;
	}//end find()

	/**
	 * Save an object
	 *
	 * @param array $object Object data
	 * @param string $register Register slug
	 * @param string $schema Schema slug
	 * @param string|null $uuid Optional UUID for updates.
	 * @param bool $_rbac RBAC bypass flag.
	 * @param bool $_multitenancy Multitenancy bypass flag.
	 *
	 * @return mixed
	 */
	public function saveObject(
		array $object = [],
		string $register = '',
		string $schema = '',
		?string $uuid = null,
		bool $_rbac = true,
		bool $_multitenancy = true,
	) {
		return null;
	}//end saveObject()

	/**
	 * Find all objects matching a set of filters
	 *
	 * Signature pinned to the real OpenRegister ObjectService::findAll() at
	 * HEAD (`array $config=[], bool $_rbac=true, bool $_multitenancy=true`) —
	 * a mock built from a drifted stub silently accepts named arguments the
	 * real class never declared, then errors at call time instead of at
	 * mock-setup time (test-fake drift; see
	 * `reference_or-objectservice-findall-signature-and-fake-drift`).
	 *
	 * @param array<string, mixed> $config Config with 'filters' key
	 * @param bool $_rbac RBAC bypass flag.
	 * @param bool $_multitenancy Multitenancy bypass flag.
	 *
	 * @return array<mixed>
	 */
	public function findAll(array $config = [], bool $_rbac = true, bool $_multitenancy = true): array {
		return [];
	}//end findAll()

	/**
	 * Delete an object
	 *
	 * @param string $uuid Object UUID
	 * @param string $register Register slug
	 * @param string $schema Schema slug
	 * @param bool $_rbac RBAC bypass flag.
	 * @param bool $_multitenancy Multitenancy bypass flag.
	 *
	 * @return bool
	 */
	public function deleteObject(
		string $uuid = '',
		string $register = '',
		string $schema = '',
		bool $_rbac = true,
		bool $_multitenancy = true,
	) {
		return true;
	}//end deleteObject()

	/**
	 * Build a search query
	 *
	 * @param array $requestParams Search params
	 * @param string $register Register slug
	 * @param string $schema Schema slug
	 *
	 * @return array
	 */
	public function buildSearchQuery(array $requestParams = [], string $register = '', string $schema = '') {
		return [];
	}//end buildSearchQuery()

	/**
	 * Search objects (paginated)
	 *
	 * @param array $query Search query
	 *
	 * @return array{results: array, total: int}
	 */
	public function searchObjectsPaginated(array $query = []) {
		return ['results' => [], 'total' => 0];
	}//end searchObjectsPaginated()

	/**
	 * Search objects
	 *
	 * @param array<string, mixed> $query Search query with optional @self scope and filters
	 *
	 * @return array
	 */
	public function searchObjects(array $query = []) {
		return [];
	}//end searchObjects()

	/**
	 * Search objects by register/schema slug.
	 *
	 * Signature mirrors the real OCA\OpenRegister\Service\ObjectService so
	 * PHPUnit-generated mocks accept the named arguments the merged callers use
	 * (PolicyMatchService, LegalBasisProposalService).
	 *
	 * @param string $registerSlug Register slug.
	 * @param string $schemaSlug Schema slug.
	 * @param array<string, mixed> $filters Optional filters.
	 * @param bool $_rbac RBAC bypass flag.
	 * @param bool $_multitenancy Multitenancy flag.
	 *
	 * @return array|int
	 */
	public function searchObjectsBySlug(
		string $registerSlug,
		string $schemaSlug,
		array $filters = [],
		bool $_rbac = true,
		bool $_multitenancy = true,
	) {
		return [];
	}//end searchObjectsBySlug()

	/**
	 * Set the current register context.
	 *
	 * Loosely typed (vs. the real `Register|string|int`) so the stub does
	 * not need to know about OpenRegister's `Register` entity class.
	 *
	 * @param mixed $register Register slug/ID, or a Register-shaped object
	 *
	 * @return static
	 */
	public function setRegister($register) {
		return $this;
	}//end setRegister()

	/**
	 * Set the current schema context.
	 *
	 * Loosely typed (vs. the real `Schema|string|int`) so the stub does not
	 * need to know about OpenRegister's `Schema` entity class.
	 *
	 * @param mixed $schema Schema slug/ID, or a Schema-shaped object
	 *
	 * @return static
	 */
	public function setSchema($schema) {
		return $this;
	}//end setSchema()

	/**
	 * Get the current register context's resolved numeric ID.
	 *
	 * @return int|null
	 */
	public function getRegister() {
		return null;
	}//end getRegister()

	/**
	 * Get the current schema context's resolved numeric ID.
	 *
	 * @return int|null
	 */
	public function getSchema() {
		return null;
	}//end getSchema()
}//end class

/**
 * Stub for OrganisationService (custom-dictionary-recognition organisation gate).
 *
 * @category Tests
 * @package  OCA\OpenRegister\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class OrganisationService {
	/**
	 * Whether the current user has access to an organisation.
	 *
	 * @param string $organisationUuid The organisation UUID.
	 *
	 * @return bool
	 */
	public function hasAccessToOrganisation(string $organisationUuid): bool {
		return false;
	}//end hasAccessToOrganisation()
}//end class

/**
 * Stub for RegisterService
 *
 * @category Tests
 * @package  OCA\OpenRegister\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class RegisterService {
	/**
	 * Find all registers
	 *
	 * @return array
	 */
	public function findAll($limit = null, $offset = null, $filters = [], $searchConditions = [], $searchParams = [], $_extend = []) {
		return [];
	}//end findAll()
}//end class

/**
 * Stub for ConfigurationService
 *
 * @category Tests
 * @package  OCA\OpenRegister\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class ConfigurationService {
	/**
	 * Import from app
	 *
	 * @return void
	 */
	public function importFromApp() {

	}//end importFromApp()
}//end class

/**
 * Stub for TextExtractionService
 *
 * @category Tests
 * @package  OCA\OpenRegister\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class TextExtractionService {
	public function extractFile(int $fileId, bool $force = false): void {
	}//end extractFile()
}//end class

/**
 * Stub for FileService
 *
 * @category Tests
 * @package  OCA\OpenRegister\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class FileService {
}//end class

/**
 * Stub for RiskLevelService
 *
 * @category Tests
 * @package  OCA\OpenRegister\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class RiskLevelService {
	/**
	 * Get risk level
	 *
	 * @param int $fileId File ID
	 *
	 * @return string
	 */
	public function getRiskLevel(int $fileId) {
		return 'none';
	}//end getRiskLevel()
}//end class

/**
 * Stub for LanguageService
 *
 * Mirrors the OR LanguageService API the docudesk
 * LanguageNegotiationMiddleware consumes. Provides in-memory state so
 * tests can assert the middleware pushed the right values into it.
 *
 * @category Tests
 * @package  OCA\OpenRegister\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class LanguageService {
	/**
	 * Preferred language code resolved from the request.
	 *
	 * @var string
	 */
	private string $preferredLanguage = 'nl';

	/**
	 * Full list of accepted languages in priority order.
	 *
	 * @var string[]
	 */
	private array $acceptedLanguages = [];

	/**
	 * Whether `_translations=all` was requested.
	 *
	 * @var boolean
	 */
	private bool $returnAllTranslations = false;

	/**
	 * Whether the resolved language is a fallback (not present in object).
	 *
	 * @var boolean
	 */
	private bool $fallbackUsed = false;

	/**
	 * Source identifier (default | query | header).
	 *
	 * @var string
	 */
	private string $requestedLanguageSource = 'default';

	/**
	 * Write-side target language from X-Translation-Target-Language.
	 *
	 * @var string|null
	 */
	private ?string $targetLanguage = null;

	public function setPreferredLanguage(string $language): void {
		$this->preferredLanguage = $language;
	}

	public function getPreferredLanguage(): string {
		return $this->preferredLanguage;
	}

	public function setAcceptedLanguages(array $languages): void {
		$this->acceptedLanguages = $languages;
	}

	public function getAcceptedLanguages(): array {
		return $this->acceptedLanguages;
	}

	public function setReturnAllTranslations(bool $returnAll): void {
		$this->returnAllTranslations = $returnAll;
	}

	public function shouldReturnAllTranslations(): bool {
		return $this->returnAllTranslations;
	}

	public function setFallbackUsed(bool $fallback): void {
		$this->fallbackUsed = $fallback;
	}

	public function isFallbackUsed(): bool {
		return $this->fallbackUsed;
	}

	public function setRequestedLanguageSource(string $source): void {
		$this->requestedLanguageSource = $source;
	}

	public function getRequestedLanguageSource(): string {
		return $this->requestedLanguageSource;
	}

	public function setTargetLanguage(?string $language): void {
		$this->targetLanguage = $language;
	}

	public function getTargetLanguage(): ?string {
		return $this->targetLanguage;
	}

	/**
	 * Parse an Accept-Language header into a priority-ordered list.
	 *
	 * Mirrors the OR LanguageService::parseAcceptLanguageHeader signature
	 * just closely enough that the middleware compiles + tests pass.
	 *
	 * @param string $headerValue The raw Accept-Language header value.
	 *
	 * @return string[]
	 */
	public static function parseAcceptLanguageHeader(string $headerValue): array {
		if (trim($headerValue) === '') {
			return [];
		}

		$entries = [];
		foreach (explode(',', $headerValue) as $part) {
			$part = trim($part);
			if ($part === '') {
				continue;
			}

			$segments = explode(';', $part);
			$tag = trim($segments[0]);
			if ($tag === '' || $tag === '*') {
				continue;
			}

			$quality = 1.0;
			for ($i = 1, $n = count($segments); $i < $n; $i++) {
				$seg = trim($segments[$i]);
				if (str_starts_with($seg, 'q=') === true) {
					$quality = (float)substr($seg, 2);
				}
			}

			$entries[] = ['tag' => $tag, 'q' => $quality];
		}

		usort($entries, static fn (array $a, array $b): int => $b['q'] <=> $a['q']);

		return array_map(static fn (array $e): string => $e['tag'], $entries);
	}
}//end class

namespace OCA\OpenRegister\Db;

/**
 * Stub for Register entity
 *
 * @category Tests
 * @package  OCA\OpenRegister\Db
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class Register {
	/**
	 * Get slug
	 *
	 * @return string
	 */
	public function getSlug(): string {
		return '';
	}//end getSlug()

	/**
	 * Get title
	 *
	 * @return string
	 */
	public function getTitle(): string {
		return '';
	}//end getTitle()

	/**
	 * Get ID
	 *
	 * @return int
	 */
	public function getId(): int {
		return 0;
	}//end getId()

	/**
	 * Serialize to array
	 *
	 * @return array
	 */
	public function jsonSerialize(): array {
		return [];
	}//end jsonSerialize()
}//end class

/**
 * Stub for Schema entity
 *
 * @category Tests
 * @package  OCA\OpenRegister\Db
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class Schema {
	/**
	 * Get title
	 *
	 * @return string
	 */
	public function getTitle(): string {
		return '';
	}//end getTitle()

	/**
	 * Get ID
	 *
	 * @return int
	 */
	public function getId(): int {
		return 0;
	}//end getId()

	/**
	 * Serialize to array
	 *
	 * @return array
	 */
	public function jsonSerialize(): array {
		return [];
	}//end jsonSerialize()
}//end class

/**
 * Stub for ObjectEntity
 *
 * @category Tests
 * @package  OCA\OpenRegister\Db
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class ObjectEntity {

	/** @var string|null */
	protected ?string $uuid = null;

	/** @var string|null */
	protected ?string $register = null;

	/** @var string|null */
	protected ?string $schema = null;

	/**
	 * Set UUID.
	 *
	 * @param string|null $uuid UUID.
	 *
	 * @return void
	 */
	public function setUuid(?string $uuid): void {
		$this->uuid = $uuid;

	}//end setUuid()

	/**
	 * Get UUID.
	 *
	 * @return string|null
	 */
	public function getUuid(): ?string {
		return $this->uuid;
	}//end getUuid()

	/**
	 * Get register.
	 *
	 * @return string|null
	 */
	public function getRegister(): ?string {
		return $this->register;
	}//end getRegister()

	/**
	 * Get schema.
	 *
	 * @return string|null
	 */
	public function getSchema(): ?string {
		return $this->schema;
	}//end getSchema()

	/**
	 * Get integer ID.
	 *
	 * @return int|null
	 */
	public function getId(): ?int {
		return null;
	}//end getId()

	/**
	 * Serialize to array.
	 *
	 * @return array
	 */
	public function jsonSerialize() {
		return [];
	}//end jsonSerialize()
}//end class

/**
 * Stub for AuditTrail entity.
 *
 * @category Tests
 * @package  OCA\OpenRegister\Db
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class AuditTrail {

	/** @var string|null */
	protected ?string $objectUuid = null;

	/** @var string|null */
	protected ?string $action = null;

	/** @var array<string, mixed> */
	protected array $changed = [];

	/** @var \DateTime|null */
	protected ?\DateTime $created = null;

	/**
	 * Set objectUuid.
	 *
	 * @param string|null $objectUuid UUID.
	 *
	 * @return void
	 */
	public function setObjectUuid(?string $objectUuid): void {
		$this->objectUuid = $objectUuid;

	}//end setObjectUuid()

	/**
	 * Get objectUuid.
	 *
	 * @return string|null
	 */
	public function getObjectUuid(): ?string {
		return $this->objectUuid;
	}//end getObjectUuid()

	/**
	 * Set action.
	 *
	 * @param string|null $action Action type.
	 *
	 * @return void
	 */
	public function setAction(?string $action): void {
		$this->action = $action;

	}//end setAction()

	/**
	 * Get action.
	 *
	 * @return string|null
	 */
	public function getAction(): ?string {
		return $this->action;
	}//end getAction()

	/**
	 * Set changed.
	 *
	 * @param array<string, mixed> $changed Changed data.
	 *
	 * @return void
	 */
	public function setChanged(array $changed): void {
		$this->changed = $changed;

	}//end setChanged()

	/**
	 * Get changed.
	 *
	 * @return array<string, mixed>
	 */
	public function getChanged(): array {
		return $this->changed;
	}//end getChanged()

	/**
	 * Set created timestamp.
	 *
	 * @param \DateTime|null $created Created at.
	 *
	 * @return void
	 */
	public function setCreated(?\DateTime $created): void {
		$this->created = $created;

	}//end setCreated()

	/**
	 * Get created timestamp.
	 *
	 * @return \DateTime|null
	 */
	public function getCreated(): ?\DateTime {
		return $this->created;
	}//end getCreated()

	/**
	 * Serialize to array.
	 *
	 * @return array<string, mixed>
	 */
	public function jsonSerialize(): array {
		return [
			'objectUuid' => $this->objectUuid,
			'action' => $this->action,
			'changed' => $this->changed,
			'created' => $this->created !== null ? $this->created->format(\DateTimeInterface::ATOM) : null,
		];

	}//end jsonSerialize()
}//end class

/**
 * Stub for AuditTrailMapper.
 *
 * @category Tests
 * @package  OCA\OpenRegister\Db
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class AuditTrailMapper {

	/**
	 * Find all audit trail entries with optional filters.
	 *
	 * @param int|null $limit Limit.
	 * @param int|null $offset Offset.
	 * @param array|null $filters Filters.
	 * @param array|null $sort Sort order.
	 * @param string|null $search Search term.
	 *
	 * @return AuditTrail[]
	 */
	public function findAll(
		?int $limit = null,
		?int $offset = null,
		?array $filters = [],
		?array $sort = ['created' => 'DESC'],
		?string $search = null,
	): array {
		return [];
	}//end findAll()

	/**
	 * Create an audit trail entry for a custom action.
	 *
	 * @param ObjectEntity $object The object the entry relates to.
	 * @param string $action The action type.
	 * @param array $context Additional context data.
	 *
	 * @return AuditTrail
	 */
	public function createAuditTrailEntry(
		ObjectEntity $object,
		string $action,
		array $context = [],
	): AuditTrail {
		$trail = new AuditTrail();
		$trail->setObjectUuid($object->getUuid());
		$trail->setAction($action);
		$trail->setChanged($context);
		$trail->setCreated(new \DateTime());
		return $trail;
	}//end createAuditTrailEntry()
}//end class

/**
 * Stub for SchemaMapper.
 *
 * @category Tests
 * @package  OCA\OpenRegister\Db
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class SchemaMapper {

	/**
	 * Find a schema by its identifier.
	 *
	 * @param int|string $id Schema id or slug.
	 * @param array $extend Relations to extend.
	 * @param bool|null $published Published filter.
	 * @param bool $rbac Whether RBAC scoping applies.
	 * @param bool $multitenancy Whether tenant scoping applies.
	 *
	 * @return mixed
	 */
	public function find(
		$id,
		array $extend = [],
		?bool $published = null,
		bool $rbac = true,
		bool $multitenancy = true,
	) {
		return null;
	}//end find()

	/**
	 * Find all schemas with optional filters.
	 *
	 * @param int|null $limit Limit.
	 * @param int|null $offset Offset.
	 * @param array|null $filters Filters.
	 *
	 * @return array
	 */
	public function findAll(
		?int $limit = null,
		?int $offset = null,
		?array $filters = [],
	): array {
		return [];
	}//end findAll()
}//end class

/**
 * Stub for EntityRelationMapper.
 *
 * Backs the NER entity-relation lookups consumed by FileEntityStatsService,
 * FileListingService and AnonymizationService.
 *
 * @category Tests
 * @package  OCA\OpenRegister\Db
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class EntityRelationMapper {

	/**
	 * Find all entity relations detected within a given file.
	 *
	 * @param int $fileId The Nextcloud file id.
	 *
	 * @return array
	 */
	public function findEntitiesForFile(int $fileId): array {
		return [];
	}//end findEntitiesForFile()

	/**
	 * Find entity relations by file id.
	 *
	 * @param int $fileId The Nextcloud file id.
	 *
	 * @return array
	 */
	public function findByFileId(int $fileId): array {
		return [];
	}//end findByFileId()

	/**
	 * Find a single relation by id.
	 *
	 * @param int $id Relation id.
	 *
	 * @return mixed
	 */
	public function find(int $id) {
		return new EntityRelation();
	}//end find()

	/**
	 * Update decision metadata (bases / skipAnonymization) on a relation.
	 *
	 * @param mixed $relation Relation row.
	 * @param array $fields Whitelisted fields to update.
	 * @param mixed $actingUser Optional acting user.
	 *
	 * @return mixed
	 */
	public function updateDecisionMetadata($relation, array $fields, $actingUser = null) {
		return $relation;
	}//end updateDecisionMetadata()

	/**
	 * Find the entity relations for a file that are marked for anonymisation
	 * (i.e. not skipped). Used by the absolute-prohibition backstop.
	 *
	 * @param int $fileId The Nextcloud file id.
	 *
	 * @return array
	 */
	public function findEntitiesForAnonymization(int $fileId): array {
		return [];
	}//end findEntitiesForAnonymization()

	/**
	 * Insert multiple relation rows in one pass (custom-dictionary-recognition).
	 *
	 * @param array<int, array<string, mixed>> $rows Rows to insert.
	 *
	 * @return EntityRelation[]
	 */
	public function insertBatch(array $rows = []): array {
		return [];
	}//end insertBatch()

	/**
	 * Delete a relation row (custom-dictionary-recognition idempotent re-run).
	 *
	 * @param EntityRelation $entity The relation to delete.
	 *
	 * @return EntityRelation
	 */
	public function delete($entity) {
		return $entity;
	}//end delete()
}//end class

/**
 * Stub for GdprEntity (custom-dictionary-recognition catalogue entries).
 *
 * @category Tests
 * @package  OCA\OpenRegister\Db
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class GdprEntity {
	/** @var int|null */
	private $id = null;

	/** @var string|null */
	private $uuid = null;

	/** @var string|null */
	private $value = null;

	/** @var string|null */
	private $type = null;

	/** @var string|null */
	private $category = null;

	/** @var \DateTime|null */
	private $detectedAt = null;

	/** @var \DateTime|null */
	private $updatedAt = null;

	/**
	 * Get the entity id.
	 *
	 * @return int|null
	 */
	public function getId() {
		return $this->id;
	}//end getId()

	/**
	 * Set the entity id (test helper — real OR sets this on insert).
	 *
	 * @param int|null $id Entity id.
	 *
	 * @return void
	 */
	public function setId($id) {
		$this->id = $id;

	}//end setId()

	/**
	 * Set the UUID.
	 *
	 * @param string|null $uuid UUID.
	 *
	 * @return void
	 */
	public function setUuid($uuid) {
		$this->uuid = $uuid;

	}//end setUuid()

	/**
	 * Get the UUID.
	 *
	 * @return string|null
	 */
	public function getUuid() {
		return $this->uuid;
	}//end getUuid()

	/**
	 * Set the value.
	 *
	 * @param string $value Entity value.
	 *
	 * @return void
	 */
	public function setValue($value) {
		$this->value = $value;

	}//end setValue()

	/**
	 * Get the value.
	 *
	 * @return string|null
	 */
	public function getValue() {
		return $this->value;
	}//end getValue()

	/**
	 * Set the type.
	 *
	 * @param string $type Entity type.
	 *
	 * @return void
	 */
	public function setType($type) {
		$this->type = $type;

	}//end setType()

	/**
	 * Get the type.
	 *
	 * @return string|null
	 */
	public function getType() {
		return $this->type;
	}//end getType()

	/**
	 * Set the category.
	 *
	 * @param string $category Entity category.
	 *
	 * @return void
	 */
	public function setCategory($category) {
		$this->category = $category;

	}//end setCategory()

	/**
	 * Get the category.
	 *
	 * @return string|null
	 */
	public function getCategory() {
		return $this->category;
	}//end getCategory()

	/**
	 * Set detectedAt.
	 *
	 * @param \DateTime $detectedAt Detected-at timestamp.
	 *
	 * @return void
	 */
	public function setDetectedAt($detectedAt) {
		$this->detectedAt = $detectedAt;

	}//end setDetectedAt()

	/**
	 * Set updatedAt.
	 *
	 * @param \DateTime $updatedAt Updated-at timestamp.
	 *
	 * @return void
	 */
	public function setUpdatedAt($updatedAt) {
		$this->updatedAt = $updatedAt;

	}//end setUpdatedAt()
}//end class

/**
 * Stub for GdprEntityMapper (custom-dictionary-recognition catalogue lookups).
 *
 * @category Tests
 * @package  OCA\OpenRegister\Db
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class GdprEntityMapper {
	/**
	 * Look up a catalogue entry by (value, type).
	 *
	 * @param string $value Entity value.
	 * @param string $type Entity type.
	 *
	 * @return GdprEntity|null
	 */
	public function findOneByValueAndType(string $value, string $type) {
		return null;
	}//end findOneByValueAndType()

	/**
	 * Insert a catalogue entry.
	 *
	 * @param GdprEntity $entity Entity to insert.
	 *
	 * @return GdprEntity
	 */
	public function insert($entity) {
		return $entity;
	}//end insert()
}//end class

/**
 * Stub for Chunk (custom-dictionary-recognition text-chunk matching).
 *
 * @category Tests
 * @package  OCA\OpenRegister\Db
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class Chunk {
	/** @var int|null */
	private $id = null;

	/** @var string */
	private $textContent = '';

	/** @var int */
	private $startOffset = 0;

	/** @var int */
	private $chunkIndex = 0;

	/**
	 * Get the chunk id.
	 *
	 * @return int|null
	 */
	public function getId() {
		return $this->id;
	}//end getId()

	/**
	 * Set the chunk id (test helper).
	 *
	 * @param int|null $id Chunk id.
	 *
	 * @return void
	 */
	public function setId($id) {
		$this->id = $id;

	}//end setId()

	/**
	 * Get the chunk's text content.
	 *
	 * @return string
	 */
	public function getTextContent(): string {
		return $this->textContent;
	}//end getTextContent()

	/**
	 * Set the chunk's text content (test helper).
	 *
	 * @param string $textContent Text content.
	 *
	 * @return void
	 */
	public function setTextContent(string $textContent): void {
		$this->textContent = $textContent;

	}//end setTextContent()

	/**
	 * Get the chunk's absolute start offset within the source document.
	 *
	 * @return int
	 */
	public function getStartOffset(): int {
		return $this->startOffset;
	}//end getStartOffset()

	/**
	 * Set the chunk's absolute start offset (test helper).
	 *
	 * @param int $startOffset Start offset.
	 *
	 * @return void
	 */
	public function setStartOffset(int $startOffset): void {
		$this->startOffset = $startOffset;

	}//end setStartOffset()

	/**
	 * Get the chunk index.
	 *
	 * @return int
	 */
	public function getChunkIndex(): int {
		return $this->chunkIndex;
	}//end getChunkIndex()

	/**
	 * Set the chunk index (test helper).
	 *
	 * @param int $chunkIndex Chunk index.
	 *
	 * @return void
	 */
	public function setChunkIndex(int $chunkIndex): void {
		$this->chunkIndex = $chunkIndex;

	}//end setChunkIndex()
}//end class

/**
 * Stub for ChunkMapper (custom-dictionary-recognition text-chunk lookups).
 *
 * @category Tests
 * @package  OCA\OpenRegister\Db
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class ChunkMapper {
	/**
	 * Find chunks by source reference.
	 *
	 * @param string $sourceType Source type (e.g. `file`).
	 * @param int $sourceId Source id.
	 *
	 * @return Chunk[]
	 */
	public function findBySource(string $sourceType, int $sourceId): array {
		return [];
	}//end findBySource()
}//end class

/**
 * Stub for EntityRelation entity.
 *
 * @category Tests
 * @package  OCA\OpenRegister\Db
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class EntityRelation {

	/**
	 * Relation row id.
	 *
	 * @var int|null
	 */
	private $id = null;

	/**
	 * Legal bases (grondslagen) assigned to the relation.
	 *
	 * @var array|null
	 */
	private $bases = null;

	/**
	 * The Nextcloud file id this relation belongs to.
	 *
	 * @var int|null
	 */
	private $fileId = null;

	/**
	 * Detection method tag (e.g. `presidio`, `manual`, `custom_dictionary`).
	 *
	 * @var string|null
	 */
	private $detectionMethod = null;

	/**
	 * Get the relation id.
	 *
	 * @return int|null
	 */
	public function getId() {
		return $this->id;
	}//end getId()

	/**
	 * Get the detection method.
	 *
	 * @return string|null
	 */
	public function getDetectionMethod() {
		return $this->detectionMethod;
	}//end getDetectionMethod()

	/**
	 * Set the detection method.
	 *
	 * @param string|null $detectionMethod Detection method tag.
	 *
	 * @return void
	 */
	public function setDetectionMethod($detectionMethod) {
		$this->detectionMethod = $detectionMethod;

	}//end setDetectionMethod()

	/**
	 * Get the file id.
	 *
	 * Mirrors the real EntityRelation magic getter used by the merged
	 * AnonymizationService prohibition-skip guard (evaluateProhibitionSkip).
	 *
	 * @return int|null
	 */
	public function getFileId() {
		return $this->fileId;
	}//end getFileId()

	/**
	 * Set the file id.
	 *
	 * @param int|null $fileId The Nextcloud file id.
	 *
	 * @return void
	 */
	public function setFileId($fileId) {
		$this->fileId = $fileId;

	}//end setFileId()

	/**
	 * Set the relation id.
	 *
	 * @param int|null $id Relation id.
	 *
	 * @return void
	 */
	public function setId($id) {
		$this->id = $id;

	}//end setId()

	/**
	 * Get the assigned bases.
	 *
	 * @return array|null
	 */
	public function getBases() {
		return $this->bases;
	}//end getBases()

	/**
	 * Set the assigned bases.
	 *
	 * @param array|null $bases Bases to assign.
	 *
	 * @return void
	 */
	public function setBases($bases) {
		$this->bases = $bases;

	}//end setBases()

}//end class

// OCP\IRequest and OCP\IL10N are defined in NextcloudStubs.php (loaded first).

namespace OC\Hooks;

/**
 * Stub for OC\Hooks\Emitter interface
 *
 * @category Tests
 * @package  OC\Hooks
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
interface Emitter {
}//end interface

namespace OCP;

/**
 * Stub for OCP\IUserSession
 *
 * @category Tests
 * @package  OCP
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
interface IUserSession {
	/**
	 * Get the currently logged in user
	 *
	 * @return \OCP\IUser|null
	 */
	public function getUser(): ?\OCP\IUser;
}//end interface

// OCP\AppFramework\Http\JSONResponse, DataDownloadResponse, and OCP\AppFramework\Controller
// are defined in NextcloudStubs.php (loaded first).

namespace Psr\Log;

/**
 * Stub for Psr\Log\LoggerInterface
 *
 * @category Tests
 * @package  Psr\Log
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
interface LoggerInterface {
	public function emergency(string|\Stringable $message, array $context = []): void;

	public function alert(string|\Stringable $message, array $context = []): void;

	public function critical(string|\Stringable $message, array $context = []): void;

	public function error(string|\Stringable $message, array $context = []): void;

	public function warning(string|\Stringable $message, array $context = []): void;

	public function notice(string|\Stringable $message, array $context = []): void;

	public function info(string|\Stringable $message, array $context = []): void;

	public function debug(string|\Stringable $message, array $context = []): void;

	public function log(mixed $level, string|\Stringable $message, array $context = []): void;
}//end interface

namespace OCP;

/**
 * Stub for OCP\IUser
 *
 * @category Tests
 * @package  OCP
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
interface IUser {
	public function getUID(): string;

	public function getDisplayName(): string;

	public function getEMailAddress(): ?string;

	public function isEnabled(): bool;
}//end interface

/**
 * Stub for OCP\IGroupManager
 *
 * @category Tests
 * @package  OCP
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
interface IGroupManager {
	public function isAdmin(string $userId): bool;
}//end interface

namespace OCP\Files;

/**
 * Stub for OCP\Files\Folder
 *
 * @category Tests
 * @package  OCP\Files
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
interface Folder extends Node {
	/**
	 * Get nodes by file ID
	 *
	 * @param int $id The file ID
	 *
	 * @return array<\OCP\Files\Node>
	 */
	public function getById(int $id): array;

	public function get(string $path): \OCP\Files\Node;

	public function getDirectoryListing(): array;

	public function getRelativePath(string $path): ?string;

	public function nodeExists(string $path): bool;

	public function newFolder(string $path): \OCP\Files\Folder;

	public function newFile(string $path, mixed $content = null): \OCP\Files\File;

	public function search(string $query): array;

	public function searchByMime(string $mimetype): array;

	/**
	 * Add a suffix to the name in case the file exists, mirroring
	 * OCP\Files\Folder::getNonExistingName() (added for
	 * document-output-destinations-and-bulk-retention's
	 * DocumentStorageService, which dedupes filenames via this platform
	 * helper).
	 *
	 * @param string $name The desired filename
	 *
	 * @return string A non-colliding filename
	 */
	public function getNonExistingName(string $name): string;
}//end interface

/**
 * Stub for OCP\Files\Node
 *
 * @category Tests
 * @package  OCP\Files
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
interface Node {
	public function getName(): string;

	public function getPath(): string;

	public function getId(): int;

	public function getPermissions(): int;

	public function getMimeType(): string;

	public function getMTime(): int;

	public function getSize();

	public function getOwner(): ?\OCP\IUser;
}//end interface

/**
 * Stub for OCP\Files\File
 *
 * @category Tests
 * @package  OCP\Files
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
interface File extends Node {
	public function getContent(): string;

	/**
	 * Write content to the file (creates a new file version).
	 *
	 * @param string $data The bytes to write.
	 *
	 * @return void
	 */
	public function putContent($data): void;

	public function getParent(): \OCP\Files\Folder;

	public function delete(): void;

	/**
	 * Move this node to a new absolute path.
	 *
	 * @param string $targetPath The absolute target path.
	 *
	 * @return \OCP\Files\Node The moved node.
	 */
	public function move(string $targetPath): \OCP\Files\Node;
}//end interface

/**
 * Stub for OCP\Files\IRootFolder
 *
 * @category Tests
 * @package  OCP\Files
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
interface IRootFolder {
	/**
	 * Get a user's home folder
	 *
	 * @param string $userId The user ID
	 *
	 * @return \OCP\Files\Folder
	 */
	public function getUserFolder(string $userId): \OCP\Files\Folder;
}//end interface

// ICache and ICacheFactory are defined in NextcloudStubs.php — no duplicate here.

namespace OCA\OpenRegister\Db;

/**
 * Stub for ApprovalChain entity.
 *
 * @category Tests
 * @package  OCA\OpenRegister\Db
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class ApprovalChain {
	private ?int $id = null;
	private ?string $uuid = null;
	private ?string $name = null;
	private ?string $registerSlug = null;
	private ?string $schemaSlug = null;
	/** @var array<int, array<string, mixed>>|null */
	private ?array $steps = null;

	public function getId(): ?int {
		return $this->id;
	}

	public function setId(?int $id): void {
		$this->id = $id;
	}

	public function getUuid(): ?string {
		return $this->uuid;
	}

	public function setUuid(?string $uuid): void {
		$this->uuid = $uuid;
	}

	public function getName(): ?string {
		return $this->name;
	}

	public function setName(?string $name): void {
		$this->name = $name;
	}

	public function getRegisterSlug(): ?string {
		return $this->registerSlug;
	}

	public function setRegisterSlug(?string $slug): void {
		$this->registerSlug = $slug;
	}

	public function getSchemaSlug(): ?string {
		return $this->schemaSlug;
	}

	public function setSchemaSlug(?string $slug): void {
		$this->schemaSlug = $slug;
	}

	/**
	 * @return array<int, array<string, mixed>>|null
	 */
	public function getSteps(): ?array {
		return $this->steps;
	}

	/**
	 * @param array<int, array<string, mixed>>|null $steps
	 */
	public function setSteps(?array $steps): void {
		$this->steps = $steps;
	}
}//end class

/**
 * Stub for ApprovalStep entity.
 *
 * @category Tests
 * @package  OCA\OpenRegister\Db
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class ApprovalStep {
	private ?string $uuid = null;
	private ?int $chainId = null;
	private ?string $objectUuid = null;
	private int $stepOrder = 0;
	private ?string $role = null;
	private ?string $status = 'pending';
	private ?string $decidedBy = null;
	private ?string $comment = null;

	public function getUuid(): ?string {
		return $this->uuid;
	}

	public function setUuid(?string $uuid): void {
		$this->uuid = $uuid;
	}

	public function getChainId(): ?int {
		return $this->chainId;
	}

	public function setChainId(?int $chainId): void {
		$this->chainId = $chainId;
	}

	public function getObjectUuid(): ?string {
		return $this->objectUuid;
	}

	public function setObjectUuid(?string $uuid): void {
		$this->objectUuid = $uuid;
	}

	public function getStepOrder(): int {
		return $this->stepOrder;
	}

	public function setStepOrder(int $order): void {
		$this->stepOrder = $order;
	}

	public function getRole(): ?string {
		return $this->role;
	}

	public function setRole(?string $role): void {
		$this->role = $role;
	}

	public function getStatus(): ?string {
		return $this->status;
	}

	public function setStatus(?string $status): void {
		$this->status = $status;
	}

	public function getDecidedBy(): ?string {
		return $this->decidedBy;
	}

	public function setDecidedBy(?string $decidedBy): void {
		$this->decidedBy = $decidedBy;
	}

	public function getComment(): ?string {
		return $this->comment;
	}

	public function setComment(?string $comment): void {
		$this->comment = $comment;
	}
}//end class

/**
 * Stub for ApprovalChainMapper.
 *
 * @category Tests
 * @package  OCA\OpenRegister\Db
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class ApprovalChainMapper {
	public function insert(ApprovalChain $chain): ApprovalChain {
		return $chain;
	}

	public function find(int $id): ApprovalChain {
		return new ApprovalChain();
	}
}//end class

/**
 * Stub for ApprovalStepMapper.
 *
 * @category Tests
 * @package  OCA\OpenRegister\Db
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class ApprovalStepMapper {
	public function insert(ApprovalStep $step): ApprovalStep {
		return $step;
	}

	/**
	 * @return array<int, ApprovalStep>
	 */
	public function findByChain(int $chainId): array {
		return [];
	}
}//end class

namespace OCA\OpenRegister\Exception;

/**
 * Stub for ArchivalImmutableException.
 *
 * Mirrors the real class in `openregister/lib/Exception/`: OpenRegister's
 * `ObjectService::deleteObject()` throws it for every user-driven delete on a
 * schema that declares `x-openregister-archival`. DocuDesk's
 * `publicationProhibition` schema does, so `PolicyController` must translate
 * this into an honest client status rather than a 500.
 *
 * @category Tests
 * @package  OCA\OpenRegister\Exception
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class ArchivalImmutableException extends \Exception {
	/**
	 * The schema slug that triggered the refusal.
	 *
	 * @var string
	 */
	private string $schemaIdentifier;

	/**
	 * Construct the stub exception with the real class's signature.
	 *
	 * @param string $schemaIdentifier The schema slug, UUID or ID.
	 * @param string $operation The blocked operation name.
	 * @param \Throwable|null $previous Previous exception.
	 */
	public function __construct(
		string $schemaIdentifier,
		string $operation = 'delete',
		?\Throwable $previous = null,
	) {
		$this->schemaIdentifier = $schemaIdentifier;

		parent::__construct(
			sprintf(
				'SCHEMA_ARCHIVAL_IMMUTABLE: Schema "%s" declares x-openregister-archival; '
				. 'user-driven %s operations are not permitted. Rows expire automatically '
				. 'via the ArchivalRetentionTask cron.',
				$schemaIdentifier,
				$operation
			),
			403,
			$previous
		);
	}//end __construct()

	/**
	 * Get the schema identifier that triggered this exception.
	 *
	 * @return string
	 */
	public function getSchemaIdentifier(): string {
		return $this->schemaIdentifier;
	}//end getSchemaIdentifier()
}//end class

namespace OCA\OpenRegister\Event;

use OCA\OpenRegister\Db\ApprovalChain;
use OCA\OpenRegister\Db\ApprovalStep;
use OCP\EventDispatcher\Event;

/**
 * Stub for ApprovalStepInitiatedEvent.
 *
 * @category Tests
 * @package  OCA\OpenRegister\Event
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class ApprovalStepInitiatedEvent extends Event {
	public function __construct(
		private readonly ApprovalChain $chain,
		private readonly ApprovalStep $step,
		private readonly string $objectUuid,
	) {
		parent::__construct();
	}

	public function getChain(): ApprovalChain {
		return $this->chain;
	}

	public function getStep(): ApprovalStep {
		return $this->step;
	}

	public function getObjectUuid(): string {
		return $this->objectUuid;
	}
}//end class

/**
 * Stub for ApprovalStepApprovedEvent.
 *
 * @category Tests
 * @package  OCA\OpenRegister\Event
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class ApprovalStepApprovedEvent extends Event {
	public function __construct(
		private readonly ApprovalChain $chain,
		private readonly ApprovalStep $step,
		private readonly string $userId,
		private readonly string $statusOnApprove,
		private readonly ?ApprovalStep $nextStep,
	) {
		parent::__construct();
	}

	public function getChain(): ApprovalChain {
		return $this->chain;
	}

	public function getStep(): ApprovalStep {
		return $this->step;
	}

	public function getUserId(): string {
		return $this->userId;
	}

	public function getStatusOnApprove(): string {
		return $this->statusOnApprove;
	}

	public function getNextStep(): ?ApprovalStep {
		return $this->nextStep;
	}

	public function isFinalStep(): bool {
		return $this->nextStep === null;
	}

	public function getObjectUuid(): string {
		return $this->step->getObjectUuid() ?? '';
	}
}//end class

/**
 * Stub for ApprovalStepRejectedEvent.
 *
 * @category Tests
 * @package  OCA\OpenRegister\Event
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class ApprovalStepRejectedEvent extends Event {
	public function __construct(
		private readonly ApprovalChain $chain,
		private readonly ApprovalStep $step,
		private readonly string $userId,
		private readonly string $statusOnReject,
	) {
		parent::__construct();
	}

	public function getChain(): ApprovalChain {
		return $this->chain;
	}

	public function getStep(): ApprovalStep {
		return $this->step;
	}

	public function getUserId(): string {
		return $this->userId;
	}

	public function getStatusOnReject(): string {
		return $this->statusOnReject;
	}

	public function getObjectUuid(): string {
		return $this->step->getObjectUuid() ?? '';
	}
}//end class

/**
 * Stub for ApprovalStepCompletedEvent.
 *
 * @category Tests
 * @package  OCA\OpenRegister\Event
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class ApprovalStepCompletedEvent extends Event {
	public function __construct(
		private readonly ApprovalChain $chain,
		private readonly ApprovalStep $finalStep,
		private readonly string $userId,
		private readonly string $statusOnApprove,
	) {
		parent::__construct();
	}

	public function getChain(): ApprovalChain {
		return $this->chain;
	}

	public function getFinalStep(): ApprovalStep {
		return $this->finalStep;
	}

	public function getUserId(): string {
		return $this->userId;
	}

	public function getStatusOnApprove(): string {
		return $this->statusOnApprove;
	}

	public function getObjectUuid(): string {
		return $this->finalStep->getObjectUuid() ?? '';
	}
}//end class

namespace OCA\OpenRegister\AppHost;

/**
 * Stub for AppHost Routes — canonical route table builder.
 *
 * @category Tests
 * @package  OCA\OpenRegister\AppHost
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class Routes {
	/**
	 * Return the canonical route array, merging app-specific $extra routes.
	 *
	 * @param array<int, array<string, mixed>> $extra App-specific routes.
	 *
	 * @return array{routes: array<int, array<string, mixed>>}
	 */
	public static function standard(array $extra = []): array {
		$canonical = [
			['name' => 'dashboard#page',             'url' => '/',                         'verb' => 'GET'],
			['name' => 'settings#index',             'url' => '/api/settings',             'verb' => 'GET'],
			['name' => 'settings#create',            'url' => '/api/settings',             'verb' => 'POST'],
			['name' => 'settings#load',              'url' => '/api/settings/load',        'verb' => 'POST'],
			['name' => 'preferences#getPreference',  'url' => '/api/preferences/{key}',    'verb' => 'GET'],
			['name' => 'preferences#setPreference',  'url' => '/api/preferences/{key}',    'verb' => 'PUT'],
			['name' => 'metrics#index',              'url' => '/api/metrics',              'verb' => 'GET'],
			['name' => 'health#index',               'url' => '/api/health',               'verb' => 'GET'],
		];

		$extraNames = [];
		foreach ($extra as $route) {
			if (isset($route['name']) === true) {
				$extraNames[(string)$route['name']] = true;
			}
		}

		$merged = [];
		foreach ($canonical as $route) {
			if (isset($extraNames[$route['name']]) === false) {
				$merged[] = $route;
			}
		}

		foreach ($extra as $route) {
			$merged[] = $route;
		}

		$merged[] = [
			'name' => 'dashboard#catchAll',
			'url' => '/{path}',
			'verb' => 'GET',
			'requirements' => ['path' => '.+'],
			'defaults' => ['path' => ''],
		];

		return ['routes' => $merged];
	}//end standard()
}//end class
