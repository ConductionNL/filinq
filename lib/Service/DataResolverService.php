<?php

/**
 * Data Resolver Service
 *
 * Service for resolving data from OpenRegister objects by register, schema,
 * and UUID. Supports nested reference resolution up to 3 levels deep and
 * merging ad-hoc data on top of resolved data.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-correspondence-generation-api
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use Exception;
use OCP\App\IAppManager;
use OCP\IAppConfig;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Service for resolving data from OpenRegister objects
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/letter-correspondence-generation/tasks.md#task-2
 */
class DataResolverService {

	/**
	 * Maximum depth for nested reference resolution
	 *
	 * @var int
	 */
	private const MAX_DEPTH = 3;

	/**
	 * Per-request cache of resolved objects to avoid duplicate lookups
	 *
	 * @var array<string, array>
	 */
	private array $resolvedCache = [];

	/**
	 * Lazily-constructed resolver for listRefs (collection references)
	 *
	 * @var ListReferenceResolver|null
	 */
	private ?ListReferenceResolver $listResolver = null;

	/**
	 * Constructor for DataResolverService
	 *
	 * @param ContainerInterface $container Container for dependency injection
	 * @param IAppManager $appManager App manager interface
	 * @param LoggerInterface $logger Logger for error reporting
	 * @param IAppConfig $appConfig App configuration accessor
	 *
	 * @return void
	 */
	public function __construct(
		private readonly ContainerInterface $container,
		private readonly IAppManager $appManager,
		private readonly LoggerInterface $logger,
		private readonly IAppConfig $appConfig,
	) {

	}//end __construct()

	/**
	 * Get the ObjectService from OpenRegister
	 *
	 * @return \OCA\OpenRegister\Service\ObjectService The ObjectService instance
	 *
	 * @throws RuntimeException If OpenRegister is not available
	 */
	private function getObjectService(): \OCA\OpenRegister\Service\ObjectService {
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
	 * Get the ListReferenceResolver, constructing it on first use.
	 *
	 * Built lazily (rather than constructor-injected) so existing callers
	 * that construct DataResolverService directly — including its own unit
	 * tests — are unaffected by this addition.
	 *
	 * @return ListReferenceResolver The resolver for listRefs
	 *
	 * @spec openspec/changes/document-generation-list-refs/specs/document-creatie-sjablonen/spec.md
	 */
	private function getListReferenceResolver(): ListReferenceResolver {
		if ($this->listResolver === null) {
			$this->listResolver = new ListReferenceResolver(
				container: $this->container,
				appManager: $this->appManager
			);
		}

		return $this->listResolver;
	}//end getListReferenceResolver()

	/**
	 * Resolve data from OpenRegister objects
	 *
	 * Fetches objects from OpenRegister by register, schema, and UUID.
	 * Returns resolved data keyed by schema name. listRefs are resolved
	 * next, each producing an array of objects under its 'as' key (or
	 * schema name + '_list' by default) — see {@see ListReferenceResolver::resolve()}.
	 * Ad-hoc data is merged on top of both, overriding resolved values when
	 * keys conflict. Precedence is therefore: dataRefs < listRefs < adHocData.
	 *
	 * @param array $dataRefs Array of object references, each with
	 *                        'register', 'schema', and 'id' keys
	 * @param array $listRefs Array of collection references, each with
	 *                        'register', 'schema' and optional 'filter',
	 *                        'limit', 'order', 'as' keys
	 * @param array $adHocData Ad-hoc data to merge on top of resolved data
	 *
	 * @return array{data: array, errors: array, warnings: array} Resolved data and any errors
	 *
	 * @throws Exception If listRefs violate a request-level guardrail (too
	 *                   many entries, invalid filter values, invalid or
	 *                   colliding 'as' key) — these are malformed-request
	 *                   errors (HTTP 400) rather than per-item resolution
	 *                   failures
	 *
	 * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-correspondence-generation-api
	 * @spec openspec/changes/document-generation-list-refs/specs/document-creatie-sjablonen/spec.md
	 */
	public function resolve(array $dataRefs, array $listRefs = [], array $adHocData = []): array {
		$this->resolvedCache = [];
		$resolved = [];
		$errors = [];

		foreach ($dataRefs as $index => $ref) {
			try {
				$this->validateReference(ref: $ref, index: $index);
				$data = $this->resolveReference(ref: $ref, depth: 0);

				$schemaKey = $ref['schema'];
				if (isset($resolved[$schemaKey]) === false) {
					$resolved[$schemaKey] = [];
				}

				$resolved[$schemaKey] = array_merge($resolved[$schemaKey], $data);
			} catch (Exception $e) {
				$errors[] = [
					'index' => $index,
					'register' => $ref['register'] ?? 'unknown',
					'schema' => $ref['schema'] ?? 'unknown',
					'id' => $ref['id'] ?? 'unknown',
					'message' => $e->getMessage(),
				];
			}//end try
		}//end foreach

		// Resolve listRefs (collections) after dataRefs and before adHocData,
		// per the documented precedence. Guardrail violations throw and
		// abort the whole request; per-item search failures are collected
		// as soft errors, mirroring dataRefs.
		$listResolution = $this->getListReferenceResolver()->resolve(
			listRefs: $listRefs,
			reservedKeys: array_keys($resolved)
		);
		$resolved = array_merge($resolved, $listResolution['data']);
		$errors = array_merge($errors, $listResolution['errors']);

		// Merge ad-hoc data on top of resolved data.
		$mergedData = array_merge($resolved, $adHocData);

		return [
			'data' => $mergedData,
			'errors' => $errors,
			'warnings' => [],
		];

	}//end resolve()

	/**
	 * Validate that a data reference has the required fields
	 *
	 * @param array $ref The reference to validate
	 * @param int $index The index of the reference in the array
	 *
	 * @return void
	 *
	 * @throws Exception If the reference is missing required fields
	 */
	private function validateReference(array $ref, int $index): void {
		$required = ['register', 'schema', 'id'];
		foreach ($required as $field) {
			if (empty($ref[$field]) === true) {
				throw new Exception(
					message: "Data reference at index {$index} is missing required field: {$field}"
				);
			}
		}//end foreach

	}//end validateReference()

	/**
	 * Resolve a single object reference from OpenRegister
	 *
	 * Recursively resolves nested references up to MAX_DEPTH levels.
	 * Uses a per-request cache to avoid duplicate lookups.
	 *
	 * @param array $ref The reference with 'register', 'schema', 'id'
	 * @param int $depth Current recursion depth
	 *
	 * @return array The resolved object data
	 *
	 * @throws Exception If the object is not found or resolution fails
	 */
	private function resolveReference(array $ref, int $depth): array {
		$cacheKey = $ref['register'] . '/' . $ref['schema'] . '/' . $ref['id'];

		if (isset($this->resolvedCache[$cacheKey]) === true) {
			return $this->resolvedCache[$cacheKey];
		}

		$objectService = $this->getObjectService();

		$result = $objectService->find(
			id: $ref['id'],
			register: $ref['register'],
			schema: $ref['schema']
		);

		if (empty($result) === true) {
			throw new Exception(
				message: "Object not found: register={$ref['register']}, schema={$ref['schema']}, id={$ref['id']}"
			);
		}

		$data = $result;
		if (is_object($result) === true
			&& method_exists(object_or_class: $result, method: 'jsonSerialize') === true
		) {
			$data = $result->jsonSerialize();
		}

		$this->resolvedCache[$cacheKey] = $data;

		// Resolve nested references if within depth limit.
		$maxDepth = (int)$this->appConfig->getValueString(
			'filinq',
			'resolver.max_depth',
			(string)self::MAX_DEPTH
		);
		if ($depth < $maxDepth) {
			$data = $this->resolveNestedReferences(data: $data, depth: $depth);
			$this->resolvedCache[$cacheKey] = $data;
		}

		return $data;
	}//end resolveReference()

	/**
	 * Scan object data for nested UUID references and resolve them
	 *
	 * Looks for fields that contain UUID-like values and attempts to
	 * resolve them as OpenRegister object references.
	 *
	 * @param array $data The object data to scan
	 * @param int $depth Current recursion depth
	 *
	 * @return array The data with nested references resolved
	 *
	 * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-correspondence-generation-api
	 */
	private function resolveNestedReferences(array $data, int $depth): array {
		foreach ($data as $key => $value) {
			if (is_string($value) === false) {
				continue;
			}

			// Check if the value looks like a UUID.
			if ($this->isUuid(value: $value) === false) {
				continue;
			}

			// Check if this looks like a reference field (skip id fields).
			if ($key === 'id' || $key === 'uuid') {
				continue;
			}

			// Attempt to resolve as a nested object reference.
			try {
				$nestedRef = [
					'register' => $data['_register'] ?? '',
					'schema' => $key,
					'id' => $value,
				];

				if (empty($nestedRef['register']) === true) {
					continue;
				}

				$nestedData = $this->resolveReference(ref: $nestedRef, depth: ($depth + 1));
				$data[$key] = $nestedData;
			} catch (Exception $e) {
				// Not a valid reference; keep original value.
				$this->logger->debug(
					message: "Nested reference resolution skipped for {$key}: {$e->getMessage()}"
				);
			}//end try
		}//end foreach

		return $data;
	}//end resolveNestedReferences()

	/**
	 * Check if a string looks like a UUID
	 *
	 * @param string $value The string to check
	 *
	 * @return bool True if the string matches UUID format
	 */
	private function isUuid(string $value): bool {
		$pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';
		return preg_match(pattern: $pattern, subject: $value) === 1;
	}//end isUuid()
}//end class
