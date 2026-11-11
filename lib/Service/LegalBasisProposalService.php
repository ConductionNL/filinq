<?php

/**
 * Grondslag Proposal Service
 *
 * Proposes a grondslag (legal basis) per detected entity type. After PII
 * detection, for each EntityRelation whose `bases` is still empty, this
 * service pre-fills the operator-configured default base slug(s) for that
 * entity type — leaving any operator-assigned bases untouched (fill-only-
 * when-empty). It also supplies the settings UI with the selectable entity
 * types and the available `base` records.
 *
 * Decisions (see openspec/changes/propose-grondslag-per-entity-type):
 *   - Mapping is instance-global app-config JSON
 *     (`filinq.grondslagen.entity_type_bases`), value
 *     `{ "<TYPE>": ["<base-slug>", ...] }`.
 *   - Bases are stored as plain slug strings, matching the existing
 *     `EntityRelation.bases` shape and `LegalBasesSummaryService` resolver.
 *   - Selectable entity types come from a curated in-Filinq list (v1).
 *     Sourcing them live from the anonymiser backend is deferred until that
 *     backend exposes a supported-types endpoint; {@see getSelectableEntityTypes}
 *     is the single seam to swap when it does.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/propose-grondslag-per-entity-type/specs/grondslag-proposal/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use Exception;
use OCP\App\IAppManager;
use OCP\IAppConfig;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Proposes grondslagen per entity type and pre-fills them at detection time.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/propose-grondslag-per-entity-type/specs/grondslag-proposal/spec.md
 */
class LegalBasisProposalService {

	/**
	 * App config key holding the entity-type → base-slug[] mapping.
	 *
	 * @var string
	 */
	public const CONFIG_KEY = 'filinq.grondslagen.entity_type_bases';

	/**
	 * App config key holding the JSON array of entity types left enabled for
	 * automatic detection. Unset or empty means "all types" (the default).
	 *
	 * @var string
	 */
	public const ENABLED_TYPES_CONFIG_KEY = 'filinq.anonymisation.enabled_entity_types';

	/**
	 * The Filinq app id, used as the app-config namespace.
	 *
	 * @var string
	 */
	private const APP_ID = 'filinq';

	/**
	 * Curated list of entity types offered in the settings selector (v1).
	 *
	 * The identifiers MUST match the `entity_type` strings the backend emits
	 * on detections, so the mapping keys line up at pre-fill time. The first
	 * group is verified against this deployment's classic/light flavor
	 * (spaCy NER + Dutch regex). The second group are the additional GLiNER
	 * targets the GPU flavor emits (kept so the same instance works against
	 * either flavor). This constant is the single seam to replace when the
	 * backend exposes a supported-types endpoint (deferred — see design.md D4).
	 *
	 * @var array<int, string>
	 */
	private const CURATED_ENTITY_TYPES = [
		// Classic/light flavor — verified against live detections.
		'PERSON',
		'LOCATION',
		'ORGANIZATION',
		'EMAIL',
		'PHONE',
		'DATE',
		'BSN',
		'IBAN',
		'KENTEKEN',
		// Additional GLiNER targets emitted by the GPU flavor.
		'STREET_ADDRESS',
		'NORP',
		'POLITICAL_PARTY',
		'INCOME',
		'EDUCATION_LEVEL',
		// Dynamic, per-organisation-list type contributed by
		// CustomDictionaryMatchService (custom-dictionary-recognition,
		// design.md §D4) — distinct from the fixed backend-emitted types
		// above: this one toggle enables/disables ALL active custom
		// dictionaries' automatic detection at once.
		'CUSTOM_DICTIONARY',
	];

	/**
	 * OpenRegister-facing reads (service resolution + `base` records).
	 *
	 * @var LegalBasisCatalog
	 */
	private readonly LegalBasisCatalog $baseCatalog;

	/**
	 * Constructor for LegalBasisProposalService.
	 *
	 * The catalog is an injected collaborator; the null default keeps the
	 * historical four-argument signature usable, in which case an equivalent
	 * catalog is built from the same three dependencies.
	 *
	 * @param IAppConfig $config App configuration (mapping storage).
	 * @param IAppManager $appManager App manager (OpenRegister availability).
	 * @param ContainerInterface $container DI container resolving OpenRegister services at runtime.
	 * @param LoggerInterface $logger Logger for best-effort diagnostics.
	 * @param LegalBasisCatalog|null $baseCatalog OpenRegister-facing reads; built from the above when null.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IAppConfig $config,
		IAppManager $appManager,
		ContainerInterface $container,
		private readonly LoggerInterface $logger,
		?LegalBasisCatalog $baseCatalog = null,
	) {
		$this->baseCatalog = ($baseCatalog ?? new LegalBasisCatalog($appManager, $container, $logger));

	}//end __construct()

	/**
	 * Return the entity types selectable in the settings UI.
	 *
	 * Version 1 returns the curated constant. This is the single seam to swap
	 * for a live backend-sourced list when that endpoint exists.
	 *
	 * @return array<int, string> Entity type identifiers.
	 */
	public function getSelectableEntityTypes(): array {
		return self::CURATED_ENTITY_TYPES;
	}//end getSelectableEntityTypes()

	/**
	 * Return the entity types currently enabled for automatic detection.
	 *
	 * Backs the settings selector, which is all-on by default: when nothing has
	 * been stored yet every curated type is returned so the UI shows them all
	 * checked. A stored selection is sanitised against the curated list so an
	 * unknown/stale type can never surface in the UI.
	 *
	 * @return array<int, string> Enabled entity type identifiers.
	 */
	public function getEnabledEntityTypes(): array {
		$stored = $this->readEnabledEntityTypes();
		if ($stored === null) {
			return self::CURATED_ENTITY_TYPES;
		}

		return $stored;
	}//end getEnabledEntityTypes()

	/**
	 * Return the entity-type whitelist to hand to OpenRegister's analysis call.
	 *
	 * Null means "do not constrain detection" (detect every type) and is
	 * returned whenever the operator has everything enabled — either because
	 * nothing was stored or because the stored selection covers the full
	 * curated set. Only a genuine subset yields a whitelist, keeping the
	 * default behaviour identical to today and avoiding a filter that would
	 * needlessly narrow the detector's vocabulary.
	 *
	 * @return array<int, string>|null Entity type whitelist, or null for "all".
	 */
	public function getEntityTypeWhitelist(): ?array {
		$stored = $this->readEnabledEntityTypes();
		if ($stored === null || count($stored) === count(self::CURATED_ENTITY_TYPES)) {
			return null;
		}

		return $stored;
	}//end getEntityTypeWhitelist()

	/**
	 * Read and sanitise the stored enabled-types selection.
	 *
	 * Returns null when unset, empty, malformed, or reduced to nothing after
	 * sanitisation — every one of those cases means "all types". Otherwise the
	 * stored ids are intersected with the curated list and returned in curated
	 * order, dropping unknown/stale/duplicate entries.
	 *
	 * @return array<int, string>|null Sanitised enabled subset, or null for "all".
	 */
	private function readEnabledEntityTypes(): ?array {
		$raw = $this->config->getValueString(self::APP_ID, self::ENABLED_TYPES_CONFIG_KEY, '');
		if ($raw === '') {
			return null;
		}

		$decoded = json_decode($raw, true);
		if (is_array($decoded) === false) {
			return null;
		}

		$enabled = array_values(
			array_filter(
				self::CURATED_ENTITY_TYPES,
				static function (string $type) use ($decoded): bool {
					return in_array($type, $decoded, true);
				}
			)
		);

		if (count($enabled) === 0) {
			return null;
		}

		return $enabled;
	}//end readEnabledEntityTypes()

	/**
	 * Return the configured entity-type → base-slug[] mapping.
	 *
	 * @return array<string, array<int, string>> Mapping; empty when unset or malformed.
	 */
	public function getMapping(): array {
		$raw = $this->config->getValueString(self::APP_ID, self::CONFIG_KEY, '{}');
		$decoded = json_decode($raw, true);
		if (is_array($decoded) === false) {
			return [];
		}

		$mapping = [];
		foreach ($decoded as $type => $slugs) {
			$clean = $this->cleanSlugs(type: $type, slugs: $slugs);
			if (count($clean) > 0) {
				$mapping[$type] = $clean;
			}
		}

		return $mapping;
	}//end getMapping()

	/**
	 * List the available `base` (grondslag) records for the settings selector.
	 *
	 * Returns slug + name + description per base so the UI can offer them as
	 * options. Operator-added bases appear automatically. Best-effort: an
	 * empty array is returned when OpenRegister is unavailable or the lookup
	 * fails — the settings page must still render.
	 *
	 * @return array<int, array{slug: string, name: string, description: string}> Available bases.
	 */
	public function getAvailableBases(): array {
		return $this->baseCatalog->getAvailableBases();
	}//end getAvailableBases()

	/**
	 * Pre-fill proposed bases onto a file's freshly-detected entity relations.
	 *
	 * For each EntityRelation of the file whose `bases` is empty, sets the
	 * configured base slug(s) for that entity's type. Relations with existing
	 * bases are skipped (non-clobber); entity types with no mapping are left
	 * empty. Best-effort and idempotent — safe to call after every detection.
	 *
	 * @param int $fileId Nextcloud file id whose relations were just detected.
	 *
	 * @return int Number of relations a proposal was written to.
	 *
	 * @spec openspec/changes/propose-grondslag-per-entity-type/specs/grondslag-proposal/spec.md#requirement-proposed-grondslag-pre-filled-at-detection-time
	 */
	public function applyProposals(int $fileId): int {
		$run = $this->proposalRun(fileId: $fileId);
		if ($run === null) {
			return 0;
		}

		$filled = 0;
		foreach ($this->detectionsFor(run: $run) as $detection) {
			$filled += $this->proposeForDetection(run: $run, detection: $detection);
		}

		return $filled;
	}//end applyProposals()

	/**
	 * Start a proposal pass for a file, when there is a mapping and OpenRegister is available.
	 *
	 * @param int $fileId Nextcloud file id whose relations were just detected.
	 *
	 * @return BasisProposalRun|null The pass, or null when there is nothing to propose.
	 */
	private function proposalRun(int $fileId): ?BasisProposalRun {
		$mapping = $this->getMapping();
		if (count($mapping) === 0) {
			return null;
		}

		$mapper = $this->baseCatalog->resolve(className: 'OCA\\OpenRegister\\Db\\EntityRelationMapper');
		if ($mapper === null) {
			return null;
		}

		return new BasisProposalRun(mapper: $mapper, mapping: $mapping, fileId: $fileId);

	}//end proposalRun()

	/**
	 * Load the detections of the run's file; none when the lookup fails.
	 *
	 * @param BasisProposalRun $run The proposal pass.
	 *
	 * @return iterable<mixed> The detection rows from findEntitiesForFile.
	 */
	private function detectionsFor(BasisProposalRun $run): iterable {
		try {
			return $run->mapper->findEntitiesForFile($run->fileId);
		} catch (Exception $e) {
			$this->logger->warning(
				'LegalBasisProposalService: could not load detections for proposal',
				['fileId' => $run->fileId, 'error' => $e->getMessage()]
			);
			return [];
		}

	}//end detectionsFor()

	/**
	 * Pre-fill the proposed bases for a single detection, when applicable.
	 *
	 * Returns 1 when a proposal was written, 0 otherwise (unmapped type,
	 * missing relation id, already-filled relation, or a best-effort failure).
	 *
	 * @param BasisProposalRun $run       The proposal pass.
	 * @param mixed            $detection One detection row from findEntitiesForFile.
	 *
	 * @return int 1 if a proposal was written, 0 otherwise.
	 */
	private function proposeForDetection(BasisProposalRun $run, mixed $detection): int {
		$row = (array)$detection;
		if ($this->isProposable(row: $row, mapping: $run->mapping) === false) {
			return 0;
		}

		try {
			return $this->fillEmptyBases(run: $run, row: $row);
		} catch (Exception $e) {
			$this->logger->warning(
				'LegalBasisProposalService: failed to pre-fill bases',
				['fileId' => $run->fileId, 'relationId' => $this->relationIdOf(row: $row), 'error' => $e->getMessage()]
			);
			return 0;
		}

	}//end proposeForDetection()

	/**
	 * Whether a detection row has a type, a relation id and a mapping for its type.
	 *
	 * @param array<mixed>                      $row     The detection row.
	 * @param array<string, array<int, string>> $mapping Entity-type → base-slug[] mapping.
	 *
	 * @return bool True when a proposal may be written for it.
	 */
	private function isProposable(array $row, array $mapping): bool {
		$type = $this->entityTypeOf(row: $row);
		return $type !== '' && $this->relationIdOf(row: $row) !== 0 && isset($mapping[$type]) === true;

	}//end isProposable()

	/**
	 * Write the mapped bases onto the row's relation unless it already has some.
	 *
	 * @param BasisProposalRun $run The proposal pass.
	 * @param array<mixed>     $row The detection row.
	 *
	 * @return int 1 if a proposal was written, 0 when the relation already has bases.
	 */
	private function fillEmptyBases(BasisProposalRun $run, array $row): int {
		$relation = $run->mapper->find($this->relationIdOf(row: $row));
		$existing = $relation->getBases();
		if ($existing !== null && count($existing) > 0) {
			// Non-clobber: never overwrite an operator (or prior) decision.
			return 0;
		}

		$run->mapper->updateDecisionMetadata(
			relation: $relation,
			fields: ['bases' => array_values($run->mapping[$this->entityTypeOf(row: $row)])]
		);
		return 1;

	}//end fillEmptyBases()

	/**
	 * The entity type of a detection row, snake or camel case.
	 *
	 * @param array<mixed> $row The detection row.
	 *
	 * @return string The type, or '' when it has none.
	 */
	private function entityTypeOf(array $row): string {
		return (string)($row['entity_type'] ?? $row['entityType'] ?? '');

	}//end entityTypeOf()

	/**
	 * The relation id of a detection row, snake or camel case.
	 *
	 * @param array<mixed> $row The detection row.
	 *
	 * @return int The relation id, or 0 when it has none.
	 */
	private function relationIdOf(array $row): int {
		return (int)($row['relation_id'] ?? $row['relationId'] ?? 0);

	}//end relationIdOf()

	/**
	 * The non-empty string slugs of one mapping entry.
	 *
	 * @param mixed $type  The entry's key.
	 * @param mixed $slugs The entry's value.
	 *
	 * @return array<int, string> The slugs, or none when the key or the value has the wrong shape.
	 */
	private function cleanSlugs(mixed $type, mixed $slugs): array {
		if (is_string($type) === false || is_array($slugs) === false) {
			return [];
		}

		return $this->nonEmptyStrings(values: $slugs);

	}//end cleanSlugs()

	/**
	 * The non-empty strings of a list, in order.
	 *
	 * @param array<mixed> $values The list.
	 *
	 * @return array<int, string> The non-empty strings.
	 */
	private function nonEmptyStrings(array $values): array {
		$clean = [];
		foreach ($values as $value) {
			if (is_string($value) === true && $value !== '') {
				$clean[] = $value;
			}
		}

		return $clean;

	}//end nonEmptyStrings()

	/**
	 * Merge each relation's current `bases` into the detection rows.
	 *
	 * `findEntitiesForFile` does not select `bases`, so callers that surface
	 * detections for review (e.g. the anonymisation sidebar) enrich them here
	 * after {@see applyProposals} — otherwise the proposed grondslag would be
	 * stored but invisible in the review UI. Best-effort: the rows are
	 * returned unchanged when OpenRegister is unavailable or the lookup fails.
	 *
	 * @param array<int, mixed> $entities Detection rows (each carrying relation_id).
	 * @param int $fileId File whose relations supply the bases.
	 *
	 * @return array<int, mixed> The detection rows with a `bases` key populated.
	 *
	 * @spec openspec/changes/propose-grondslag-per-entity-type/specs/grondslag-proposal/spec.md#requirement-proposed-grondslag-pre-filled-at-detection-time
	 */
	public function enrichEntitiesWithBases(array $entities, int $fileId): array {
		$mapper = $this->baseCatalog->resolve(className: 'OCA\\OpenRegister\\Db\\EntityRelationMapper');
		if ($mapper === null) {
			return $entities;
		}

		$basesByRelation = $this->knownBases(mapper: $mapper, fileId: $fileId);
		if ($basesByRelation === null) {
			return $entities;
		}

		return $this->withBases(entities: $entities, basesByRelation: $basesByRelation);
	}//end enrichEntitiesWithBases()

	/**
	 * The current bases of every relation of a file, or null (logged) when the lookup fails.
	 *
	 * @param mixed $mapper The EntityRelationMapper.
	 * @param int   $fileId File whose relations supply the bases.
	 *
	 * @return array<int, mixed>|null Bases per relation id, or null on failure.
	 */
	private function knownBases(mixed $mapper, int $fileId): ?array {
		try {
			return $this->basesByRelation(mapper: $mapper, fileId: $fileId);
		} catch (Exception $e) {
			$this->logger->warning(
				'LegalBasisProposalService: could not load bases for enrichment',
				['fileId' => $fileId, 'error' => $e->getMessage()]
			);
			return null;
		}

	}//end knownBases()

	/**
	 * The current bases of every relation of a file, by relation id.
	 *
	 * @param mixed $mapper The EntityRelationMapper.
	 * @param int   $fileId File whose relations supply the bases.
	 *
	 * @return array<int, mixed> Bases per relation id.
	 */
	private function basesByRelation(mixed $mapper, int $fileId): array {
		$basesByRelation = [];
		foreach ($mapper->findByFileId($fileId) as $relation) {
			$basesByRelation[(int)$relation->getId()] = $relation->getBases();
		}

		return $basesByRelation;

	}//end basesByRelation()

	/**
	 * Put the known bases on every detection row whose relation id is in the map.
	 *
	 * @param array<int, mixed> $entities        Detection rows (each carrying relation_id).
	 * @param array<int, mixed> $basesByRelation Bases per relation id.
	 *
	 * @return array<int, mixed> The detection rows with a `bases` key where known.
	 */
	private function withBases(array $entities, array $basesByRelation): array {
		foreach ($entities as $index => $row) {
			$data = (array)$row;
			$relationId = $this->relationIdOf(row: $data);
			if ($relationId !== 0 && array_key_exists($relationId, $basesByRelation) === true) {
				$data['bases'] = $basesByRelation[$relationId];
				$entities[$index] = $data;
			}
		}

		return $entities;

	}//end withBases()
}//end class
