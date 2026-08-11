<?php
/**
 * Grondslag Base Catalog
 *
 * Owns every OpenRegister-facing read the grondslag (legal basis) pipeline
 * needs: locating OpenRegister services/mappers at runtime, and listing the
 * available `base` records for the settings selector.
 *
 * Extracted from {@see LegalBasisProposalService} so that the proposal service
 * keeps a single concern — deciding and writing proposals — while all
 * knowledge of OpenRegister's availability, result envelopes and
 * `ObjectEntity` unwrapping lives here.
 *
 * Every method is best-effort: OpenRegister is an optional dependency, so a
 * missing app, an unresolvable class or a failing lookup degrades to an empty
 * result rather than an exception. The settings page must still render.
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
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Resolves OpenRegister collaborators and reads `base` (grondslag) records.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/changes/propose-grondslag-per-entity-type/specs/grondslag-proposal/spec.md
 */
class LegalBasisCatalog
{

    /**
     * The OpenRegister app id.
     *
     * @var string
     */
    private const OPENREGISTER_APP_ID = 'openregister';

    /**
     * Constructor for LegalBasisCatalog.
     *
     * @param IAppManager        $appManager App manager (OpenRegister availability).
     * @param ContainerInterface $container  DI container resolving OpenRegister services at runtime.
     * @param LoggerInterface    $logger     Logger for best-effort diagnostics.
     *
     * @return void
     */
    public function __construct(
        private readonly IAppManager $appManager,
        private readonly ContainerInterface $container,
        private readonly LoggerInterface $logger,
    ) {

    }//end __construct()

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
    public function resolve(string $className): mixed
    {
        if (in_array(self::OPENREGISTER_APP_ID, $this->appManager->getInstalledApps(), true) === false) {
            return null;
        }

        try {
            return $this->container->get($className);
        } catch (Exception $e) {
            $this->logger->warning(
                'LegalBasisCatalog: OpenRegister service unavailable',
                ['class' => $className, 'error' => $e->getMessage()]
            );
            return null;
        }

    }//end resolve()

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
        $objectService = $this->resolve(className: 'OCA\\OpenRegister\\Service\\ObjectService');
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
                'LegalBasisCatalog: failed to load `base` records',
                ['error' => $e->getMessage()]
            );
            return [];
        }

        $bases = [];
        foreach ($this->extractObjects(result: $result) as $base) {
            $record = $this->toBaseRecord(base: $base);
            if ($record !== null) {
                $bases[] = $record;
            }
        }

        return $bases;

    }//end getAvailableBases()

    /**
     * Convert one raw `base` object into the selector record shape.
     *
     * Returns null when the object is not usable as an option — it is not an
     * array, or it lacks either a slug or a name.
     *
     * @param mixed $base One raw `base` object from OpenRegister.
     *
     * @return array{slug: string, name: string, description: string}|null The record, or null.
     */
    private function toBaseRecord(mixed $base): ?array
    {
        if (is_array($base) === false) {
            return null;
        }

        $self = ($base['@self'] ?? []);
        $slug = '';
        if (is_array($self) === true) {
            $slug = (string) ($self['slug'] ?? '');
        }

        // Cast only scalars. An OpenRegister object property may legally be
        // an array (multi-value / nested), and `(string) $array` emits a
        // PHP "Array to string conversion" warning for EVERY such field.
        // Measured 2026-07-28: a single object create produced 6,240 of
        // these warnings (6,623 log lines total) because this runs on the
        // OR object-write path — which made every
        // `POST /apps/openregister/api/objects/...` hang fleet-wide and
        // grew nextcloud.log without bound (cf. the 163GB log incident
        // that filled the Docker disk and PANICked Postgres).
        $name = self::asString(value: $base['name'] ?? '');
        if ($slug === '' || $name === '') {
            return null;
        }

        return [
            'slug'        => $slug,
            'name'        => $name,
            'description' => self::asString(value: $base['description'] ?? ''),
        ];

    }//end toBaseRecord()

    /**
     * Coerce an OpenRegister property to a string without warning on arrays.
     *
     * OR object properties are `mixed`: a field may be a scalar, null, or an
     * array (multi-value / nested object). A bare `(string) $value` cast on an
     * array raises "Array to string conversion" — harmless-looking, but on the
     * object-write path it fires once per field per object and buries the
     * request (and the log) under thousands of warnings.
     *
     * @param mixed $value The raw property value.
     *
     * @return string The scalar rendering, or '' when the value is not scalar.
     *
     * @psalm-pure
     */
    private static function asString(mixed $value): string
    {
        if (is_scalar($value) === true) {
            return (string) $value;
        }

        return '';

    }//end asString()

    /**
     * Normalise a raw OpenRegister search result into a plain list of objects.
     *
     * The result may be a paginated envelope (`['results' => [...]]`) or a
     * bare iterable, and each item may be an `ObjectEntity` rather than an
     * array.
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
