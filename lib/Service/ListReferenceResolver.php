<?php
/**
 * List Reference Resolver
 *
 * Resolves listRefs — collection-shaped data references — into arrays of
 * OpenRegister objects for the document generation Twig context. Split out
 * of DataResolverService (which owns single-object dataRefs resolution) to
 * keep both classes within the class-complexity budget and because the two
 * concerns (single-object hydration vs collection search) are independently
 * testable.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/changes/document-generation-list-refs/specs/document-creatie-sjablonen/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use Exception;
use RuntimeException;
use OCP\App\IAppManager;
use Psr\Container\ContainerInterface;

/**
 * Resolves listRefs (collection references) against OpenRegister's
 * slug-aware search API.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/changes/document-generation-list-refs/specs/document-creatie-sjablonen/spec.md
 */
class ListReferenceResolver
{

    /**
     * Maximum number of listRefs accepted per request
     *
     * @var int
     */
    private const MAX_LIST_REFS = 10;

    /**
     * Default per-list result limit when a listRef does not specify one
     *
     * @var int
     */
    private const DEFAULT_LIST_LIMIT = 50;

    /**
     * Hard cap on a listRef's 'limit', regardless of what is requested
     *
     * @var int
     */
    private const MAX_LIST_LIMIT = 500;

    /**
     * Allowed shape for a listRef's 'as' context key
     *
     * @var string
     */
    private const AS_KEY_PATTERN = '/^[a-zA-Z_][a-zA-Z0-9_]{0,63}$/';

    /**
     * Constructor for ListReferenceResolver
     *
     * @param ContainerInterface $container  Container for dependency injection
     * @param IAppManager        $appManager App manager interface
     *
     * @return void
     */
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly IAppManager $appManager
    ) {

    }//end __construct()

    /**
     * Get the ObjectService from OpenRegister
     *
     * @return \OCA\OpenRegister\Service\ObjectService The ObjectService instance
     *
     * @throws RuntimeException If OpenRegister is not available
     */
    private function getObjectService(): \OCA\OpenRegister\Service\ObjectService
    {
        if (in_array(
            needle: 'openregister',
            haystack: $this->appManager->getInstalledApps(),
            strict: true
        ) === true
        ) {
            return $this->container->get('OCA\OpenRegister\Service\ObjectService');
        }

        throw new RuntimeException(message: 'OpenRegister service is not available.');

    }//end getObjectService()

    /**
     * Resolve listRefs (collection references) into arrays of objects.
     *
     * Every listRef is validated up front (fail-fast) before any
     * OpenRegister search runs, so a malformed listRef never triggers a
     * partial lookup. Once validated, each listRef is resolved
     * independently; a search failure for one listRef does not abort the
     * others and is instead collected as a soft error (mirroring dataRefs
     * in DataResolverService::resolve()).
     *
     * @param array $listRefs     Array of collection references, each with
     *                            'register', 'schema' and optional
     *                            'filter', 'limit', 'order', 'as' keys
     * @param array $reservedKeys Context keys already used by dataRefs; a
     *                            listRef's 'as' key must not collide with
     *                            these
     *
     * @return array{data: array<string, array>, errors: array} Resolved
     *         lists keyed by 'as', and any per-item resolution errors
     *
     * @throws Exception If a listRef violates a request-level guardrail
     *                    (too many entries, invalid filter values, invalid
     *                    or colliding 'as' key) — all HTTP 400
     *
     * @spec openspec/changes/document-generation-list-refs/specs/document-creatie-sjablonen/spec.md
     */
    public function resolve(array $listRefs, array $reservedKeys=[]): array
    {
        if (empty($listRefs) === true) {
            return [
                'data'   => [],
                'errors' => [],
            ];
        }

        if (count($listRefs) > self::MAX_LIST_REFS) {
            throw new Exception(
                message: sprintf(
                    'Too many listRefs: %d exceeds the maximum of %d per request',
                    count($listRefs),
                    self::MAX_LIST_REFS
                ),
                code: 400
            );
        }

        // Validate every listRef before resolving any of them.
        $usedKeys     = $reservedKeys;
        $asKeyByIndex = [];
        foreach ($listRefs as $index => $listRef) {
            $asKey      = $this->validateListReference(ref: $listRef, index: $index, usedKeys: $usedKeys);
            $usedKeys[] = $asKey;
            $asKeyByIndex[$index] = $asKey;
        }

        return $this->resolveValidatedListRefs(listRefs: $listRefs, asKeyByIndex: $asKeyByIndex);

    }//end resolve()

    /**
     * Resolve a set of already-validated listRefs against OpenRegister.
     *
     * @param array $listRefs     The validated listRefs
     * @param array $asKeyByIndex The 'as' key each listRef resolves under, by index
     *
     * @return array{data: array<string, array>, errors: array}
     */
    private function resolveValidatedListRefs(array $listRefs, array $asKeyByIndex): array
    {
        $data   = [];
        $errors = [];
        foreach ($listRefs as $index => $listRef) {
            $asKey = $asKeyByIndex[$index];

            try {
                $data[$asKey] = $this->resolveList(ref: $listRef);
            } catch (Exception $e) {
                $errors[]     = [
                    'index'    => $index,
                    'register' => $listRef['register'] ?? 'unknown',
                    'schema'   => $listRef['schema'] ?? 'unknown',
                    'as'       => $asKey,
                    'message'  => $e->getMessage(),
                ];
                $data[$asKey] = [];
            }
        }//end foreach

        return [
            'data'   => $data,
            'errors' => $errors,
        ];

    }//end resolveValidatedListRefs()

    /**
     * Validate a single listRef and return its resolved 'as' context key.
     *
     * @param array $ref      The listRef to validate
     * @param int   $index    The index of the listRef in the array (for error messages)
     * @param array $usedKeys Context keys already claimed by dataRefs or earlier listRefs
     *
     * @return string The validated 'as' key this listRef will resolve under
     *
     * @throws Exception If the listRef is missing required fields, has a
     *                    non-scalar filter value, an out-of-range limit, or
     *                    an invalid/colliding 'as' key — all HTTP 400
     *
     * @spec openspec/changes/document-generation-list-refs/specs/document-creatie-sjablonen/spec.md
     */
    private function validateListReference(array $ref, int $index, array $usedKeys): string
    {
        $this->validateListReferenceFields(ref: $ref, index: $index);
        $this->validateListReferenceFilter(ref: $ref, index: $index);
        $this->validateListReferenceLimit(ref: $ref, index: $index);

        return $this->validateListReferenceAsKey(ref: $ref, index: $index, usedKeys: $usedKeys);

    }//end validateListReference()

    /**
     * Validate that the listRef carries its required 'register' and 'schema' fields.
     *
     * @param array $ref   The listRef to validate
     * @param int   $index The index of the listRef in the array (for error messages)
     *
     * @return void
     *
     * @throws Exception If a required field is missing
     */
    private function validateListReferenceFields(array $ref, int $index): void
    {
        foreach (['register', 'schema'] as $field) {
            if (empty($ref[$field]) === true) {
                throw new Exception(
                    message: "List reference at index {$index} is missing required field: {$field}",
                    code: 400
                );
            }
        }

    }//end validateListReferenceFields()

    /**
     * Validate that the listRef's 'filter' (if any) is an object of scalar values.
     *
     * @param array $ref   The listRef to validate
     * @param int   $index The index of the listRef in the array (for error messages)
     *
     * @return void
     *
     * @throws Exception If 'filter' is not an object, or a value is non-scalar
     */
    private function validateListReferenceFilter(array $ref, int $index): void
    {
        $filter = $ref['filter'] ?? [];
        if (is_array($filter) === false) {
            throw new Exception(
                message: "List reference at index {$index} has an invalid 'filter': must be an object",
                code: 400
            );
        }

        foreach ($filter as $key => $value) {
            if (is_scalar($value) === false && $value !== null) {
                throw new Exception(
                    message: "List reference at index {$index} has a non-scalar filter value for '{$key}'; "
                        ."filter values must be scalars",
                    code: 400
                );
            }
        }

    }//end validateListReferenceFilter()

    /**
     * Validate that the listRef's 'limit' (if any) is an integer within bounds.
     *
     * @param array $ref   The listRef to validate
     * @param int   $index The index of the listRef in the array (for error messages)
     *
     * @return void
     *
     * @throws Exception If 'limit' is not an integer between 1 and MAX_LIST_LIMIT
     */
    private function validateListReferenceLimit(array $ref, int $index): void
    {
        if (isset($ref['limit']) === false) {
            return;
        }

        $limit            = $ref['limit'];
        $isValidLimitType = is_int($limit) === true
            || (is_string($limit) === true && ctype_digit($limit) === true);
        if ($isValidLimitType === false || (int) $limit < 1 || (int) $limit > self::MAX_LIST_LIMIT) {
            throw new Exception(
                message: "List reference at index {$index} has an invalid 'limit': must be an integer "
                    ."between 1 and ".self::MAX_LIST_LIMIT,
                code: 400
            );
        }

    }//end validateListReferenceLimit()

    /**
     * Validate the listRef's 'as' context key: shape and non-collision.
     *
     * An explicit 'as' MUST already match AS_KEY_PATTERN (a caller mistake
     * is a 400, not silently corrected). The DEFAULT ('schema' + '_list')
     * is derived from the schema slug, which routinely contains hyphens
     * (e.g. 'v-app-competitors' — not a legal Twig variable name), so it is
     * sanitised via {@see slugToIdentifier()} before the pattern check.
     *
     * @param array $ref      The listRef to validate
     * @param int   $index    The index of the listRef in the array (for error messages)
     * @param array $usedKeys Context keys already claimed by dataRefs or earlier listRefs
     *
     * @return string The validated 'as' key
     *
     * @throws Exception If 'as' does not match the allowed pattern, or collides
     *                    with an existing data key
     */
    private function validateListReferenceAsKey(array $ref, int $index, array $usedKeys): string
    {
        $asKey = $this->slugToIdentifier(slug: $ref['schema']).'_list';
        if (isset($ref['as']) === true) {
            $asKey = (string) $ref['as'];
        }

        if (preg_match(self::AS_KEY_PATTERN, $asKey) !== 1) {
            throw new Exception(
                message: "List reference at index {$index} has an invalid 'as' key: '{$asKey}' "
                    ."(must match ^[a-zA-Z_][a-zA-Z0-9_]{0,63}$)",
                code: 400
            );
        }

        if (in_array($asKey, $usedKeys, true) === true) {
            throw new Exception(
                message: "List reference at index {$index} 'as' key '{$asKey}' collides with an "
                    ."existing data key",
                code: 400
            );
        }

        return $asKey;

    }//end validateListReferenceAsKey()

    /**
     * Convert a register/schema slug into a legal Twig identifier fragment.
     *
     * Slugs are conventionally kebab-case (e.g. 'v-app-competitors'), which
     * is not a legal Twig variable name — Twig would parse the hyphens as
     * subtraction. Every character outside [a-zA-Z0-9_] becomes '_', and a
     * leading digit is prefixed with '_' so the result is always a legal
     * identifier start.
     *
     * @param string $slug The register or schema slug
     *
     * @return string A string safe to use as (part of) a Twig context key
     */
    private function slugToIdentifier(string $slug): string
    {
        $identifier = (string) preg_replace('/[^a-zA-Z0-9_]/', '_', $slug);
        if (preg_match('/^[0-9]/', $identifier) === 1) {
            $identifier = '_'.$identifier;
        }

        return $identifier;

    }//end slugToIdentifier()

    /**
     * Resolve a single listRef against OpenRegister's paginated search.
     *
     * Uses `setRegister()`/`setSchema()` (both slug-aware) to put the
     * ObjectService instance into register/schema context, then
     * {@see \OCA\OpenRegister\Service\ObjectService::searchObjectsPaginated()}
     * — the SAME method + context pattern OpenRegister's own
     * `ObjectsController::index()` uses. This matters: the sibling
     * `searchObjects()`/`searchObjectsBySlug()` path never consults a
     * schema's `x-openregister-object-source` and silently returns nothing
     * for schemas backed by an external DBAL register (e.g. `spectr-live`)
     * — only `searchObjectsPaginated()` checks `$this->currentSchema`
     * (set by `setSchema()`) and delegates to the object-source provider
     * when one is configured. `setRegister()`/`setSchema()` in turn
     * auto-inject the resolved numeric `_register`/`_schema` into the
     * query, so `filter` stays plain top-level keys.
     *
     * @param array $ref The listRef, already validated by {@see validateListReference()}
     *
     * @return array The resolved objects, each serialized via jsonSerialize()
     *               where available
     *
     * @throws Exception If the register/schema slug does not resolve, or the
     *                    OpenRegister search fails
     *
     * @spec openspec/changes/document-generation-list-refs/specs/document-creatie-sjablonen/spec.md
     */
    private function resolveList(array $ref): array
    {
        $objectService = $this->getObjectService();

        $query = $ref['filter'] ?? [];

        $limit = self::DEFAULT_LIST_LIMIT;
        if (isset($ref['limit']) === true) {
            $limit = (int) $ref['limit'];
        }

        $query['_limit'] = $limit;

        if (isset($ref['order']) === true && is_array($ref['order']) === true) {
            $query['_order'] = $ref['order'];
        }

        try {
            $objectService->setRegister(register: (string) $ref['register']);
            $objectService->setSchema(schema: (string) $ref['schema']);

            $result = $objectService->searchObjectsPaginated(query: $query);
        } catch (Exception $e) {
            throw new Exception(
                message: "List resolution failed for register={$ref['register']}, "
                    ."schema={$ref['schema']}: {$e->getMessage()}"
            );
        }

        $rows = $result['results'] ?? [];

        $items = [];
        foreach ($rows as $item) {
            if (is_object($item) === true
                && method_exists(object_or_class: $item, method: 'jsonSerialize') === true
            ) {
                $items[] = $item->jsonSerialize();
                continue;
            }

            $items[] = $item;
        }

        return $items;

    }//end resolveList()
}//end class
