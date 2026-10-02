<?php

/**
 * Text fragment service
 *
 * CRUD for text fragments (bouwstenen), with the caller's own OpenRegister
 * rights. A slug is unique within its namespace, because that pair is what
 * `${fragment:slug}` resolves; the namespace cannot change after creation.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\OfficeTemplate
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/office-template-authoring/tasks.md#2-4
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\OfficeTemplate;

/**
 * Creates, reads, updates and deletes text fragments.
 *
 * @spec openspec/changes/office-template-authoring/tasks.md#2-4
 */
class TextFragmentService {

	/**
	 * The fields a caller may set.
	 *
	 * @var string[]
	 */
	private const FIELDS = ['name', 'slug', 'content', 'namespace', 'category', 'tags', 'language'];

	/**
	 * Constructor.
	 *
	 * @param TemplateObjectRepository $objects The objects (caller rights).
	 *
	 * @return void
	 */
	public function __construct(
		private readonly TemplateObjectRepository $objects,
	) {

	}//end __construct()

	/**
	 * Fragments by filter.
	 *
	 * @param array<string, mixed> $filters namespace, category.
	 *
	 * @return array<int, array<string, mixed>> The fragments.
	 *
	 * @spec openspec/changes/office-template-authoring/tasks.md#2-4
	 */
	public function list(array $filters): array {
		return $this->fragments()->search(filters: $filters);

	}//end list()

	/**
	 * One fragment.
	 *
	 * @param string $id The fragment.
	 *
	 * @return array<string, mixed> The fragment.
	 *
	 * @throws OfficeTemplateRefused 404 when it does not exist.
	 *
	 * @spec openspec/changes/office-template-authoring/tasks.md#2-4
	 */
	public function get(string $id): array {
		$fragment = $this->fragments()->find(uuid: $id);
		if ($fragment === null) {
			throw new OfficeTemplateRefused(message: 'Text fragment not found.', reason: 'not-found', code: 404);
		}

		return $fragment;

	}//end get()

	/**
	 * Create a fragment.
	 *
	 * @param array<string, mixed> $data name, slug, content, namespace (required), category, tags, language.
	 *
	 * @return array<string, mixed> The fragment.
	 *
	 * @throws OfficeTemplateRefused 400 for a missing field or bad slug, 409 for a slug already used in the namespace.
	 *
	 * @spec openspec/changes/office-template-authoring/tasks.md#2-4
	 */
	public function create(array $data): array {
		$fields = array_intersect_key($data, array_flip(self::FIELDS));
		foreach (['name', 'slug', 'content', 'namespace'] as $required) {
			if (trim((string) ($fields[$required] ?? '')) === '') {
				throw new OfficeTemplateRefused(message: $required . ' is required.', reason: 'fields', code: 400);
			}
		}

		$this->assertSlug(slug: (string) $fields['slug'], namespace: (string) $fields['namespace'], ownId: null);

		return $this->fragments()->save(record: $fields);

	}//end create()

	/**
	 * Update a fragment; its namespace stays.
	 *
	 * @param string               $id   The fragment.
	 * @param array<string, mixed> $data The changed fields.
	 *
	 * @return array<string, mixed> The fragment.
	 *
	 * @throws OfficeTemplateRefused 404, 400 or 409 as on create.
	 *
	 * @spec openspec/changes/office-template-authoring/tasks.md#2-4
	 */
	public function update(string $id, array $data): array {
		$current = $this->get(id: $id);
		$fields = array_intersect_key($data, array_flip(self::FIELDS));
		unset($fields['namespace']);
		$merged = array_merge($current, $fields);
		if (isset($fields['slug']) === true) {
			$this->assertSlug(slug: (string) $fields['slug'], namespace: (string) $current['namespace'], ownId: $id);
		}

		return $this->fragments()->save(record: array_intersect_key($merged, array_flip(self::FIELDS)), uuid: $id);

	}//end update()

	/**
	 * Delete a fragment. Templates that still use it show the missing-fragment marker.
	 *
	 * @param string $id The fragment.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/office-template-authoring/tasks.md#2-4
	 */
	public function delete(string $id): void {
		$this->get(id: $id);
		$this->fragments()->delete(uuid: $id);

	}//end delete()

	/**
	 * Refuse a malformed slug or one already used in the namespace.
	 *
	 * @param string      $slug      The slug.
	 * @param string      $namespace The namespace.
	 * @param string|null $ownId     The fragment being updated.
	 *
	 * @return void
	 *
	 * @throws OfficeTemplateRefused 400 or 409.
	 */
	private function assertSlug(string $slug, string $namespace, ?string $ownId): void {
		if (preg_match('/^[a-z0-9][a-z0-9-]*$/', $slug) !== 1) {
			throw new OfficeTemplateRefused(message: 'A slug has lowercase letters, digits and dashes only.', reason: 'slug', code: 400);
		}

		foreach ($this->fragments()->search(filters: ['slug' => $slug, 'namespace' => $namespace]) as $other) {
			if ($other['uuid'] !== $ownId) {
				throw new OfficeTemplateRefused(message: 'Slug ' . $slug . ' is already used in namespace ' . $namespace . '.', reason: 'slug-taken', code: 409);
			}
		}

	}//end assertSlug()

	/**
	 * The textFragment repository.
	 *
	 * @return TemplateObjectRepository The repository.
	 */
	private function fragments(): TemplateObjectRepository {
		return $this->objects->forSchema(schema: 'textFragment');

	}//end fragments()
}//end class
