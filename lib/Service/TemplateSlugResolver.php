<?php

/**
 * Template Slug Resolver
 *
 * Resolves a document template by namespace + slug (optionally scoped to a
 * tenant) instead of by UUID, and guards against two templates claiming the
 * same (namespace, tenantId, slug). Extracted from TemplateService to keep
 * that class under the phpmd ExcessiveClassComplexity threshold — the same
 * "extracted to reduce complexity" pattern TemplateRenderer already uses
 * against PdfService.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/filinq-configurable-report-templates/specs/template-management/spec.md#requirement-template-slug-resolution-req-tmpl-13
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use Exception;
use OCP\App\IAppManager;
use Psr\Container\ContainerInterface;
use RuntimeException;

/**
 * Namespace/tenant-scoped slug lookup and duplicate-slug guard for templates.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/filinq-configurable-report-templates/specs/template-management/spec.md#requirement-template-slug-resolution-req-tmpl-13
 */
class TemplateSlugResolver {

	/**
	 * Constructor.
	 *
	 * Depends only on the raw OpenRegister/config plumbing (not on
	 * TemplateService) so TemplateService can depend on this class without
	 * creating a constructor-time circular dependency.
	 *
	 * @param ContainerInterface $container Container for dependency injection.
	 * @param IAppManager $appManager App manager interface.
	 * @param OpenRegisterResolver $registerResolver Resolver for register/schema config.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly ContainerInterface $container,
		private readonly IAppManager $appManager,
		private readonly OpenRegisterResolver $registerResolver,
	) {

	}//end __construct()

	/**
	 * Resolve a template by namespace + slug, optionally scoped to a tenant.
	 *
	 * Resolution order: a template matching (namespace, slug, tenantId) wins
	 * when it exists; otherwise the namespace-wide default — the same slug
	 * with no tenantId set — is used. This lets a school (tenant) override
	 * the default report-card template without every school needing one.
	 *
	 * @param string $namespace The owning app's namespace (e.g. "learniq").
	 * @param string $slug The stable, human-readable template identifier.
	 * @param string|null $tenantId Optional tenant (e.g. school) to prefer.
	 *
	 * @return array The resolved template object.
	 *
	 * @throws Exception When no template matches (code 404).
	 *
	 * @spec openspec/changes/filinq-configurable-report-templates/specs/template-management/spec.md#requirement-template-slug-resolution-req-tmpl-13
	 */
	public function resolve(string $namespace, string $slug, ?string $tenantId = null): array {
		if ($tenantId !== null && $tenantId !== '') {
			$tenantMatch = $this->query(
				filters: [
					'namespace' => $namespace,
					'slug' => $slug,
					'tenantId' => $tenantId,
				],
				limit: 1
			);

			if (empty($tenantMatch) === false) {
				return $this->normaliseResult(result: $tenantMatch[0]);
			}
		}

		$namespaceMatch = $this->query(
			filters: [
				'namespace' => $namespace,
				'slug' => $slug,
			],
			limit: 20
		);

		foreach ($namespaceMatch as $candidate) {
			$candidate = $this->normaliseResult(result: $candidate);
			if (empty($candidate['tenantId'] ?? null) === true) {
				return $candidate;
			}
		}

		throw new Exception(
			message: sprintf(
				'No template found for namespace "%s" and slug "%s".',
				$namespace,
				$slug
			),
			code: 404
		);
	}//end resolve()

	/**
	 * Guard against two templates sharing (namespace, tenantId, slug).
	 *
	 * @param string $namespace The template's namespace.
	 * @param string $slug The slug being claimed.
	 * @param string|null $tenantId The tenant scope, if any.
	 *
	 * @return void
	 *
	 * @throws Exception When a template already holds that exact combination (code 400).
	 *
	 * @spec openspec/changes/filinq-configurable-report-templates/specs/template-management/spec.md#scenario-duplicate-slug-within-the-same-namespace-and-tenant-is-rejected
	 */
	public function assertAvailable(string $namespace, string $slug, ?string $tenantId): void {
		$filters = [
			'namespace' => $namespace,
			'slug' => $slug,
		];
		if (empty($tenantId) === false) {
			$filters['tenantId'] = $tenantId;
		}

		foreach ($this->query(filters: $filters, limit: 20) as $candidate) {
			$candidate = $this->normaliseResult(result: $candidate);
			$candidateTenant = $candidate['tenantId'] ?? null;
			$sameTenant = ($candidateTenant === $tenantId)
				|| (empty($candidateTenant) === true && empty($tenantId) === true);

			if ($sameTenant === true) {
				throw new Exception(
					message: sprintf(
						'A template with slug "%s" already exists for this namespace/tenant.',
						$slug
					),
					code: 400
				);
			}
		}
	}//end assertAvailable()

	/**
	 * Run a filtered template search.
	 *
	 * @param array<string, mixed> $filters OpenRegister search filters.
	 * @param int $limit Result cap.
	 *
	 * @return array<int, mixed> Raw result rows.
	 */
	private function query(array $filters, int $limit): array {
		$objectService = $this->getObjectService();
		$config = $this->registerResolver->getRegisterAndSchema();

		$requestParams = $filters;
		$requestParams['_limit'] = $limit;
		$requestParams['_offset'] = 0;

		$query = $objectService->buildSearchQuery(
			requestParams: $requestParams,
			register: $config['register'],
			schema: $config['schema']
		);

		$result = $objectService->searchObjectsPaginated(query: $query);

		return $result['results'] ?? [];
	}//end query()

	/**
	 * Get the ObjectService from OpenRegister.
	 *
	 * @return \OCA\OpenRegister\Service\ObjectService The ObjectService instance.
	 *
	 * @throws RuntimeException If OpenRegister is not available.
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
	 * Normalise a raw OpenRegister search result into a plain array.
	 *
	 * @param mixed $result A single result row from searchObjectsPaginated().
	 *
	 * @return array The result as an associative array.
	 */
	private function normaliseResult(mixed $result): array {
		if (is_object($result) === true
			&& method_exists(object_or_class: $result, method: 'jsonSerialize') === true
		) {
			return $result->jsonSerialize();
		}

		return (array) $result;
	}//end normaliseResult()
}//end class
