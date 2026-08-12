<?php

/**
 * Base Label Resolver
 *
 * Resolves Woo Art. 5 `base` (grondslag) references — slugs or UUIDs — to the
 * human-readable name plus toelichting the grondslagen report renders.
 *
 * Extracted from {@see LegalBasesSummaryService}: the lookup, the
 * ObjectService result normalisation and the "unresolved reference" fallback
 * are one concern, and the renderer no longer needs to know any of it.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/specs/anonymisation-grondslagen-summary/spec.md
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use Exception;
use Psr\Log\LoggerInterface;

/**
 * Resolves `base` references to display names and toelichtingen.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class BaseLabelResolver {

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
	 * @param DossierObjectRepository $repository OpenRegister object access.
	 * @param LoggerInterface $logger Structured logger.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly DossierObjectRepository $repository,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Resolve a list of `base` references (slugs or UUIDs) to human-readable labels.
	 *
	 * Looks each reference up in the dossier register's `base` schema and
	 * returns a map `{ref => {name, description}}`. Unresolved references
	 * (rule deleted, malformed reference, etc.) resolve to the raw reference
	 * so the rendered report flags the data gap rather than silently dropping
	 * the row.
	 *
	 * Wave 1.1's `add-dossier-schema` ships `bases` as plain slug strings
	 * (per the v1 trade-off documented in its design.md §D1), so this method
	 * primarily resolves by slug; UUID fallback is supported for
	 * forward-compatibility with a future `$ref` enforcement story.
	 *
	 * @param array<int, string> $baseRefs Slugs or UUIDs of base records.
	 *
	 * @return array<string, array{name: string, description: string}> Map from each
	 *                                                                 reference to its
	 *                                                                 display name and
	 *                                                                 Woo Art. 5 toelichting.
	 */
	public function resolve(array $baseRefs): array {
		if (count($baseRefs) === 0) {
			return [];
		}

		$objectService = $this->repository->objectService();
		if ($objectService === null) {
			// ObjectService unavailable — best-effort: show the raw ref so
			// the operator at least sees the slug, not a dangling
			// placeholder. Better than masking the failure entirely.
			return $this->rawLabels(baseRefs: $baseRefs);
		}

		$lookups = $this->loadBaseLookups(objectService: $objectService);

		$labels = [];
		foreach ($baseRefs as $ref) {
			$refString = (string)$ref;
			$labels[$refString] = $this->labelFor(refString: $refString, lookups: $lookups);
		}

		return $labels;
	}//end resolve()

	/**
	 * Group resolved base labels by display name for the report legend.
	 *
	 * Distinct references may resolve to the same grondslag, so entries are
	 * deduplicated by name; nameless entries are dropped and the first
	 * non-empty description wins.
	 *
	 * @param array<string, array{name: string, description: string}> $detail Output of {@see resolve}.
	 *
	 * @return array<int, array{name: string, description: string}> Distinct bases, sorted by name.
	 */
	public function groupByName(array $detail): array {
		$byName = [];
		foreach ($detail as $entry) {
			$name = $entry['name'];
			if ($name === '') {
				continue;
			}

			if (isset($byName[$name]) === false || $byName[$name] === '') {
				$byName[$name] = $entry['description'];
			}
		}

		$bases = [];
		foreach ($byName as $name => $description) {
			$bases[] = ['name' => $name, 'description' => $description];
		}

		usort($bases, static fn (array $a, array $b): int => strcmp($a['name'], $b['name']));

		return $bases;
	}//end groupByName()

	/**
	 * Best-effort labels used when OpenRegister cannot be consulted.
	 *
	 * @param array<int, string> $baseRefs The references to label.
	 *
	 * @return array<string, array{name: string, description: string}> Raw-reference labels.
	 */
	private function rawLabels(array $baseRefs): array {
		$labels = [];
		foreach ($baseRefs as $ref) {
			$labels[(string)$ref] = ['name' => (string)$ref, 'description' => ''];
		}

		return $labels;
	}//end rawLabels()

	/**
	 * Build the slug→ and uuid→ lookups from the register's `base` objects.
	 *
	 * Pulls every `base` object in one shot — the canonical set is six Woo
	 * Art. 5 grondslagen plus any tenant-added entries; very small
	 * cardinality. Both lookup shapes are built so the resolver works
	 * regardless of which reference shape the `bases` column carries.
	 *
	 * `searchObjectsBySlug` is the path that resolves slug filters to numeric
	 * IDs and reaches the magic-mapped `dossier` register; `findAll` with slug
	 * filters returns nothing because the magic tables aren't visible to the
	 * generic getHandler path.
	 *
	 * @param mixed $objectService OpenRegister's ObjectService.
	 *
	 * @return array<string, array<string,string>> Keyed `slugToName`, `uuidToName`,
	 *                                             `slugToDesc` and `uuidToDesc`.
	 */
	private function loadBaseLookups(mixed $objectService): array {
		$lookups = [
			'slugToName' => [],
			'uuidToName' => [],
			'slugToDesc' => [],
			'uuidToDesc' => [],
		];

		try {
			$result = $objectService->searchObjectsBySlug(
				registerSlug: 'dossier',
				schemaSlug: 'base',
				filters: [],
				_rbac: false,
				_multitenancy: false
			);

			foreach ($this->extractObjects(result: $result) as $base) {
				$lookups = $this->withBase(lookups: $lookups, base: $base);
			}
		} catch (Exception $e) {
			$this->logger->warning(
				'LegalBasesSummaryService: failed to load `base` objects for label resolution',
				['error' => $e->getMessage()]
			);
		}//end try

		return $lookups;
	}//end loadBaseLookups()

	/**
	 * Fold one `base` object into the lookups.
	 *
	 * @param array<string, array<string,string>> $lookups The lookups so far.
	 * @param array<string, mixed> $base One `base` object payload.
	 *
	 * @return array<string, array<string,string>> The updated lookups.
	 */
	private function withBase(array $lookups, array $base): array {
		$name = (string)($base['name'] ?? '');
		if ($name === '') {
			return $lookups;
		}

		// `description` is the Woo Art. 5 toelichting (schema `base`,
		// property "Omschrijving") — the explanatory text shown under
		// the summary table.
		$desc = (string)($base['description'] ?? '');

		$self = ($base['@self'] ?? []);
		$slug = '';
		$uuid = '';
		if (is_array($self) === true) {
			$slug = (string)($self['slug'] ?? '');
			$uuid = (string)($self['id'] ?? ($self['uuid'] ?? ''));
		}

		if ($slug !== '') {
			$lookups['slugToName'][$slug] = $name;
			$lookups['slugToDesc'][$slug] = $desc;
		}

		if ($uuid !== '') {
			$lookups['uuidToName'][$uuid] = $name;
			$lookups['uuidToDesc'][$uuid] = $desc;
		}

		return $lookups;
	}//end withBase()

	/**
	 * Resolve one reference against the lookups, falling back to the raw ref.
	 *
	 * @param string $refString The reference.
	 * @param array<string, array<string,string>> $lookups The lookups.
	 *
	 * @return array{name: string, description: string} The label.
	 */
	private function labelFor(string $refString, array $lookups): array {
		if (isset($lookups['slugToName'][$refString]) === true) {
			return [
				'name' => $lookups['slugToName'][$refString],
				'description' => ($lookups['slugToDesc'][$refString] ?? ''),
			];
		}

		if (isset($lookups['uuidToName'][$refString]) === true) {
			return [
				'name' => $lookups['uuidToName'][$refString],
				'description' => ($lookups['uuidToDesc'][$refString] ?? ''),
			];
		}

		return ['name' => $refString, 'description' => ''];
	}//end labelFor()

	/**
	 * Coerce an ObjectService search result into a plain array of object payloads.
	 *
	 * The result may be ObjectEntity instances, plain associative arrays, or a
	 * `{results: [...]}` envelope depending on the path that served it.
	 *
	 * @param mixed $result The raw search return value.
	 *
	 * @return array<int, array<string, mixed>> The normalised payloads.
	 */
	private function extractObjects(mixed $result): array {
		if (is_array($result) === true && isset($result['results']) === true && is_array($result['results']) === true) {
			$result = $result['results'];
		}

		$out = [];
		if (is_iterable($result) === false) {
			return $out;
		}

		foreach ($result as $item) {
			$payload = $this->normaliseItem(item: $item);
			if ($payload !== null) {
				$out[] = $payload;
			}
		}

		return $out;
	}//end extractObjects()

	/**
	 * Normalise a single search-result item to a payload array, or null.
	 *
	 * `ObjectEntity::jsonSerialize()` returns a flat payload that includes a
	 * synthetic `@self` block (id, slug, register, schema, …) reconstructed
	 * from the entity's columns. That's the shape {@see resolve} needs, so it
	 * is preferred when the item is a real ObjectEntity.
	 *
	 * @param mixed $item One search-result item.
	 *
	 * @return array<string, mixed>|null The payload, or null to skip the item.
	 */
	private function normaliseItem(mixed $item): ?array {
		$objectEntityClass = self::OBJECT_ENTITY_CLASS;
		if (is_object($item) === true
			&& class_exists($objectEntityClass) === true
			&& $item instanceof $objectEntityClass
		) {
			try {
				$payload = $item->jsonSerialize();
				if (is_array($payload) === true) {
					return $payload;
				}
			} catch (\Throwable $e) {
				// Fall through to other branches.
			}
		}

		if (is_array($item) === true) {
			return $item;
		}

		return null;
	}//end normaliseItem()
}//end class
