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
 *     (`docudesk.grondslagen.entity_type_bases`), value
 *     `{ "<TYPE>": ["<base-slug>", ...] }`.
 *   - Bases are stored as plain slug strings, matching the existing
 *     `EntityRelation.bases` shape and `GrondslagenSummaryService` resolver.
 *   - Selectable entity types come from a curated in-DocuDesk list (v1).
 *     Sourcing them live from the anonymiser backend is deferred until that
 *     backend exposes a supported-types endpoint; {@see getSelectableEntityTypes}
 *     is the single seam to swap when it does.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/changes/propose-grondslag-per-entity-type/specs/grondslag-proposal/spec.md
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use Exception;
use OCP\App\IAppManager;
use OCP\IAppConfig;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Proposes grondslagen per entity type and pre-fills them at detection time.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class GrondslagProposalService
{

    /**
     * App config key holding the entity-type → base-slug[] mapping.
     *
     * @var string
     */
    public const CONFIG_KEY = 'docudesk.grondslagen.entity_type_bases';

    /**
     * The DocuDesk app id, used as the app-config namespace.
     *
     * @var string
     */
    private const APP_ID = 'docudesk';

    /**
     * The OpenRegister app id.
     *
     * @var string
     */
    private const OPENREGISTER_APP_ID = 'openregister';

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
        // Additional GLiNER targets emitted by the GPU flavor.
        'STREET_ADDRESS',
        'NORP',
        'POLITICAL_PARTY',
        'INCOME',
        'EDUCATION_LEVEL',
    ];


    /**
     * Constructor for GrondslagProposalService.
     *
     * @param IAppConfig         $config     App configuration (mapping storage).
     * @param IAppManager        $appManager App manager (OpenRegister availability).
     * @param ContainerInterface $container  DI container resolving OpenRegister services at runtime.
     * @param LoggerInterface    $logger     Logger for best-effort diagnostics.
     *
     * @return void
     */
    public function __construct(
        private readonly IAppConfig $config,
        private readonly IAppManager $appManager,
        private readonly ContainerInterface $container,
        private readonly LoggerInterface $logger,
    ) {

    }//end __construct()


    /**
     * Return the entity types selectable in the settings UI.
     *
     * Version 1 returns the curated constant. This is the single seam to swap
     * for a live backend-sourced list when that endpoint exists.
     *
     * @return array<int, string> Entity type identifiers.
     */
    public function getSelectableEntityTypes(): array
    {
        return self::CURATED_ENTITY_TYPES;

    }//end getSelectableEntityTypes()


    /**
     * Return the configured entity-type → base-slug[] mapping.
     *
     * @return array<string, array<int, string>> Mapping; empty when unset or malformed.
     */
    public function getMapping(): array
    {
        $raw     = $this->config->getValueString(self::APP_ID, self::CONFIG_KEY, '{}');
        $decoded = json_decode($raw, true);
        if (is_array($decoded) === false) {
            return [];
        }

        $mapping = [];
        foreach ($decoded as $type => $slugs) {
            if (is_string($type) === false || is_array($slugs) === false) {
                continue;
            }

            $clean = [];
            foreach ($slugs as $slug) {
                if (is_string($slug) === true && $slug !== '') {
                    $clean[] = $slug;
                }
            }

            if (count($clean) > 0) {
                $mapping[$type] = $clean;
            }
        }//end foreach

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
    public function getAvailableBases(): array
    {
        $objectService = $this->resolveOpenRegister(className: 'OCA\\OpenRegister\\Service\\ObjectService');
        if ($objectService === null) {
            return [];
        }

        try {
            $result = $objectService->searchObjectsBySlug(
                registerSlug: 'dossier',
                schemaSlug: 'base',
                filters: [],
                _rbac: false,
                _multitenancy: false
            );
        } catch (Exception $e) {
            $this->logger->warning(
                'GrondslagProposalService: failed to load `base` records',
                ['error' => $e->getMessage()]
            );
            return [];
        }

        $bases = [];
        foreach ($this->extractObjects(result: $result) as $base) {
            if (is_array($base) === false) {
                continue;
            }

            $self = ($base['@self'] ?? []);
            $slug = '';
            if (is_array($self) === true) {
                $slug = (string) ($self['slug'] ?? '');
            }

            $name = (string) ($base['name'] ?? '');
            if ($slug === '' || $name === '') {
                continue;
            }

            $bases[] = [
                'slug'        => $slug,
                'name'        => $name,
                'description' => (string) ($base['description'] ?? ''),
            ];
        }//end foreach

        return $bases;

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
     */
    public function applyProposals(int $fileId): int
    {
        $mapping = $this->getMapping();
        if (count($mapping) === 0) {
            return 0;
        }

        $mapper = $this->resolveOpenRegister(className: 'OCA\\OpenRegister\\Db\\EntityRelationMapper');
        if ($mapper === null) {
            return 0;
        }

        $filled = 0;
        try {
            $detections = $mapper->findEntitiesForFile($fileId);
        } catch (Exception $e) {
            $this->logger->warning(
                'GrondslagProposalService: could not load detections for proposal',
                ['fileId' => $fileId, 'error' => $e->getMessage()]
            );
            return 0;
        }

        foreach ($detections as $detection) {
            $filled += $this->proposeForDetection(
                mapper: $mapper,
                mapping: $mapping,
                detection: $detection,
                fileId: $fileId
            );
        }

        return $filled;

    }//end applyProposals()


    /**
     * Pre-fill the proposed bases for a single detection, when applicable.
     *
     * Returns 1 when a proposal was written, 0 otherwise (unmapped type,
     * missing relation id, already-filled relation, or a best-effort failure).
     *
     * @param mixed                             $mapper    The EntityRelationMapper.
     * @param array<string, array<int, string>> $mapping   Entity-type → base-slug[] mapping.
     * @param mixed                             $detection One detection row from findEntitiesForFile.
     * @param int                               $fileId    File id (for log context).
     *
     * @return int 1 if a proposal was written, 0 otherwise.
     */
    private function proposeForDetection(mixed $mapper, array $mapping, mixed $detection, int $fileId): int
    {
        $row        = (array) $detection;
        $type       = (string) ($row['entity_type'] ?? $row['entityType'] ?? '');
        $relationId = (int) ($row['relation_id'] ?? $row['relationId'] ?? 0);
        if ($type === '' || $relationId === 0 || isset($mapping[$type]) === false) {
            return 0;
        }

        try {
            $relation = $mapper->find($relationId);
            $existing = $relation->getBases();
            if ($existing !== null && count($existing) > 0) {
                // Non-clobber: never overwrite an operator (or prior) decision.
                return 0;
            }

            $mapper->updateDecisionMetadata(
                relation: $relation,
                fields: ['bases' => array_values($mapping[$type])]
            );
            return 1;
        } catch (Exception $e) {
            $this->logger->warning(
                'GrondslagProposalService: failed to pre-fill bases',
                ['fileId' => $fileId, 'relationId' => $relationId, 'error' => $e->getMessage()]
            );
            return 0;
        }//end try

    }//end proposeForDetection()


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
     * @param int               $fileId   File whose relations supply the bases.
     *
     * @return array<int, mixed> The detection rows with a `bases` key populated.
     */
    public function enrichEntitiesWithBases(array $entities, int $fileId): array
    {
        $mapper = $this->resolveOpenRegister(className: 'OCA\\OpenRegister\\Db\\EntityRelationMapper');
        if ($mapper === null) {
            return $entities;
        }

        try {
            $basesByRelation = [];
            foreach ($mapper->findByFileId($fileId) as $relation) {
                $basesByRelation[(int) $relation->getId()] = $relation->getBases();
            }
        } catch (Exception $e) {
            $this->logger->warning(
                'GrondslagProposalService: could not load bases for enrichment',
                ['fileId' => $fileId, 'error' => $e->getMessage()]
            );
            return $entities;
        }

        foreach ($entities as $index => $row) {
            $data       = (array) $row;
            $relationId = (int) ($data['relation_id'] ?? $data['relationId'] ?? 0);
            if ($relationId !== 0 && array_key_exists($relationId, $basesByRelation) === true) {
                $data['bases']    = $basesByRelation[$relationId];
                $entities[$index] = $data;
            }
        }

        return $entities;

    }//end enrichEntitiesWithBases()


    /**
     * Resolve an OpenRegister service/mapper by class name, or null.
     *
     * Returns null (best-effort) when OpenRegister is not installed or the
     * container cannot resolve the class — callers degrade gracefully.
     *
     * @param string $className Fully-qualified OpenRegister class name.
     *
     * @return mixed The resolved instance, or null.
     */
    private function resolveOpenRegister(string $className): mixed
    {
        if (in_array(self::OPENREGISTER_APP_ID, $this->appManager->getInstalledApps(), true) === false) {
            return null;
        }

        try {
            return $this->container->get($className);
        } catch (Exception $e) {
            $this->logger->warning(
                'GrondslagProposalService: OpenRegister service unavailable',
                ['class' => $className, 'error' => $e->getMessage()]
            );
            return null;
        }

    }//end resolveOpenRegister()


    /**
     * Normalise an ObjectService search result into a list of object arrays.
     *
     * Mirrors the extraction GrondslagenSummaryService uses: the result may be
     * a paginated envelope (`['results' => [...]]`) or a bare list, and each
     * item may be an `ObjectEntity` (not an array). ObjectEntity items are
     * flattened via `jsonSerialize()`, which yields the `@self` + property
     * payload this service reads.
     *
     * @param mixed $result The raw search result.
     *
     * @return array<int, array<string, mixed>> The list of object arrays.
     */
    private function extractObjects(mixed $result): array
    {
        if (is_array($result) === true && isset($result['results']) === true && is_array($result['results']) === true) {
            $result = $result['results'];
        }

        if (is_iterable($result) === false) {
            return [];
        }

        $out = [];
        foreach ($result as $item) {
            $row = $this->normaliseSearchItem(item: $item);
            if ($row !== null) {
                $out[] = $row;
            }
        }

        return $out;

    }//end extractObjects()


    /**
     * Normalise a single search-result item to an array, or null to skip it.
     *
     * Items may be plain arrays or `ObjectEntity` objects; the latter are
     * flattened via `jsonSerialize()` to the `@self` + property payload.
     *
     * @param mixed $item One search-result item.
     *
     * @return array<string, mixed>|null The flattened object, or null.
     */
    private function normaliseSearchItem(mixed $item): ?array
    {
        if (is_array($item) === true) {
            return $item;
        }

        $objectEntityClass = '\OCA\OpenRegister\Db\ObjectEntity';
        if (is_object($item) === false
            || class_exists($objectEntityClass) === false
            || ($item instanceof $objectEntityClass) === false
        ) {
            return null;
        }

        try {
            $payload = $item->jsonSerialize();
        } catch (\Throwable $e) {
            return null;
        }

        if (is_array($payload) === true) {
            return $payload;
        }

        return null;

    }//end normaliseSearchItem()


}//end class
