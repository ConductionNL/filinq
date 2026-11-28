<?php

/**
 * Filinq Text Fragment Service
 *
 * Manages text fragments (bouwstenen): pieces of text written once and
 * inserted into many templates with ${fragment:slug}.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\OfficeTemplate
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-11-25-office-template-authoring/specs/office-template-authoring/spec.md#requirement-reusable-text-fragments-bouwstenen-resolve-in-templates-req-ddota-004
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\OfficeTemplate;

use Exception;
use Throwable;

/**
 * Text fragment CRUD and lookup by slug.
 *
 * A slug is unique within its namespace. The objects live in the templates
 * register as `textFragment`, through OpenRegister.
 *
 * @category Service
 * @package  OCA\Filinq\Service\OfficeTemplate
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-11-25-office-template-authoring/specs/office-template-authoring/spec.md#requirement-reusable-text-fragments-bouwstenen-resolve-in-templates-req-ddota-004
 */
class TextFragmentService {

	/**
	 * The schema slug.
	 */
	public const SCHEMA = 'textFragment';

	/**
	 * The fields a request may set.
	 */
	private const FIELDS = ['name', 'slug', 'content', 'namespace', 'category', 'tags', 'language'];

	/**
	 * The slug shape the schema requires.
	 */
	private const SLUG_PATTERN = '/^[a-z0-9]+(-[a-z0-9]+)*$/';

	/**
	 * Constructor.
	 *
	 * @param TemplatesRegisterObjects $objects The templates register.
	 *
	 * @spec openspec/changes/archive/2026-11-25-office-template-authoring/specs/office-template-authoring/spec.md#requirement-reusable-text-fragments-bouwstenen-resolve-in-templates-req-ddota-004
	 */
	public function __construct(
		private readonly TemplatesRegisterObjects $objects,
	) {
	}//end __construct()

	/**
	 * A page of fragments.
	 *
	 * @param array<string, mixed> $filters Field filters (namespace, category, ...).
	 * @param int                  $limit   The page size.
	 * @param int                  $offset  The offset.
	 *
	 * @return array{results: array<int, array<string, mixed>>, total: int}
	 *
	 * @spec openspec/changes/archive/2026-11-25-office-template-authoring/specs/office-template-authoring/spec.md#requirement-reusable-text-fragments-bouwstenen-resolve-in-templates-req-ddota-004
	 */
	public function list(array $filters=[], int $limit=20, int $offset=0): array {
		return $this->objects->search(schema: self::SCHEMA, query: $filters + ['_limit' => $limit, '_offset' => $offset]);
	}//end list()

	/**
	 * One fragment.
	 *
	 * @param string $id The fragment's id.
	 *
	 * @return array<string, mixed>
	 *
	 * @throws Exception With code 404 when there is no such fragment.
	 *
	 * @spec openspec/changes/archive/2026-11-25-office-template-authoring/specs/office-template-authoring/spec.md#requirement-reusable-text-fragments-bouwstenen-resolve-in-templates-req-ddota-004
	 */
	public function get(string $id): array {
		return $this->objects->get(schema: self::SCHEMA, id: $id);
	}//end get()

	/**
	 * Create a fragment.
	 *
	 * @param array<string, mixed> $data The fragment.
	 *
	 * @return array<string, mixed> The saved fragment.
	 *
	 * @throws Exception With code 400 when a required field is missing or the slug is malformed,
	 *                   409 when the slug is taken in the namespace.
	 *
	 * @spec openspec/changes/archive/2026-11-25-office-template-authoring/specs/office-template-authoring/spec.md#requirement-reusable-text-fragments-bouwstenen-resolve-in-templates-req-ddota-004
	 */
	public function create(array $data): array {
		$fragment = $this->validated(data: array_intersect_key($data, array_flip(self::FIELDS)));
		$this->assertSlugFree(namespace: $fragment['namespace'], slug: $fragment['slug'], exceptId: null);

		return $this->objects->save(schema: self::SCHEMA, data: $fragment);
	}//end create()

	/**
	 * Update a fragment.
	 *
	 * @param string               $id   The fragment's id.
	 * @param array<string, mixed> $data The changed fields.
	 *
	 * @return array<string, mixed> The saved fragment.
	 *
	 * @throws Exception With code 404, 400 or 409 as for create().
	 *
	 * @spec openspec/changes/archive/2026-11-25-office-template-authoring/specs/office-template-authoring/spec.md#requirement-reusable-text-fragments-bouwstenen-resolve-in-templates-req-ddota-004
	 */
	public function update(string $id, array $data): array {
		$current = $this->get(id: $id);
		$merged = array_merge(
			array_intersect_key($current, array_flip(self::FIELDS)),
			array_intersect_key($data, array_flip(self::FIELDS))
		);
		$fragment = $this->validated(data: $merged);
		$this->assertSlugFree(namespace: $fragment['namespace'], slug: $fragment['slug'], exceptId: $id);

		return $this->objects->save(schema: self::SCHEMA, data: $fragment, id: $id);
	}//end update()

	/**
	 * Delete a fragment.
	 *
	 * @param string $id The fragment's id.
	 *
	 * @return void
	 *
	 * @throws Exception With code 404 when there is no such fragment.
	 *
	 * @spec openspec/changes/archive/2026-11-25-office-template-authoring/specs/office-template-authoring/spec.md#requirement-reusable-text-fragments-bouwstenen-resolve-in-templates-req-ddota-004
	 */
	public function delete(string $id): void {
		$this->objects->delete(schema: self::SCHEMA, id: $id);
	}//end delete()

	/**
	 * The texts of the fragments with these slugs in a namespace.
	 *
	 * A slug without a fragment is absent from the answer; a lookup that
	 * fails leaves every slug absent, so each renders as a visible marker.
	 *
	 * @param array<int, string> $slugs     The slugs.
	 * @param string             $namespace The namespace.
	 *
	 * @return array<string, string> The texts by slug.
	 *
	 * @spec openspec/changes/archive/2026-11-25-office-template-authoring/specs/office-template-authoring/spec.md#requirement-reusable-text-fragments-bouwstenen-resolve-in-templates-req-ddota-004
	 */
	public function texts(array $slugs, string $namespace): array {
		$texts = [];
		foreach (array_unique($slugs) as $slug) {
			$fragment = $this->findBySlug(namespace: $namespace, slug: $slug);
			if ($fragment !== null) {
				$texts[$slug] = (string) ($fragment['content'] ?? '');
			}
		}

		return $texts;
	}//end texts()

	/**
	 * The fragment with this slug in a namespace, or null.
	 *
	 * @param string $namespace The namespace.
	 * @param string $slug      The slug.
	 *
	 * @return array<string, mixed>|null
	 *
	 * @spec openspec/changes/archive/2026-11-25-office-template-authoring/specs/office-template-authoring/spec.md#requirement-reusable-text-fragments-bouwstenen-resolve-in-templates-req-ddota-004
	 */
	public function findBySlug(string $namespace, string $slug): ?array {
		try {
			$page = $this->objects->search(schema: self::SCHEMA, query: ['namespace' => $namespace, 'slug' => $slug, '_limit' => 5]);
		} catch (Throwable) {
			return null;
		}

		foreach ($page['results'] as $row) {
			// The search may match loosely; only an exact slug and namespace count.
			if (($row['slug'] ?? null) === $slug && ($row['namespace'] ?? null) === $namespace) {
				return $row;
			}
		}

		return null;
	}//end findBySlug()

	/**
	 * The fragment with its required fields checked.
	 *
	 * @param array<string, mixed> $data The fragment.
	 *
	 * @return array<string, mixed>
	 *
	 * @throws Exception With code 400 when a field is missing or malformed.
	 */
	private function validated(array $data): array {
		foreach (['name', 'slug', 'content', 'namespace'] as $field) {
			if (is_string($data[$field] ?? null) === false || trim($data[$field]) === '') {
				throw new Exception(message: ucfirst($field).' is required', code: 400);
			}
		}

		$data['slug'] = trim($data['slug']);
		if (preg_match(self::SLUG_PATTERN, $data['slug']) !== 1) {
			throw new Exception(message: 'Slug must be lowercase letters and digits, separated by single hyphens', code: 400);
		}

		if (preg_match('/^[a-z0-9]+$/', $data['namespace']) !== 1) {
			throw new Exception(message: 'Invalid namespace: must be lowercase alphanumeric only', code: 400);
		}

		return $data;
	}//end validated()

	/**
	 * Refuse a slug another fragment in the namespace has.
	 *
	 * @param string      $namespace The namespace.
	 * @param string      $slug      The slug.
	 * @param string|null $exceptId  The fragment being updated, which may keep its slug.
	 *
	 * @return void
	 *
	 * @throws Exception With code 409 when the slug is taken.
	 */
	private function assertSlugFree(string $namespace, string $slug, ?string $exceptId): void {
		$existing = $this->findBySlug(namespace: $namespace, slug: $slug);
		if ($existing === null) {
			return;
		}

		$existingId = (string) ($existing['id'] ?? ($existing['uuid'] ?? ''));
		if ($exceptId !== null && $existingId === $exceptId) {
			return;
		}

		throw new Exception(message: 'A text fragment with slug "'.$slug.'" already exists in namespace "'.$namespace.'"', code: 409);
	}//end assertSlugFree()
}//end class
