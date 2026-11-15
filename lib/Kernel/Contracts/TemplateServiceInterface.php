<?php

/**
 * Contract of TemplateService
 *
 * The public surface of the TemplateService service, so callers in other
 * components depend on this contract instead of the implementation.
 *
 * @category  Kernel
 * @package   OCA\Filinq\Kernel\Contracts
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Kernel\Contracts;

use Exception;

/**
 * Contract of TemplateService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface TemplateServiceInterface {
	/**
	 * List templates with optional filters
	 *
	 * Supports namespace, category, and tags filter parameters.
	 *
	 * @param array $filters Optional filters (e.g. namespace, category, tags, _search)
	 * @param int $limit Maximum number of results (default: 20)
	 * @param int $offset Result offset for pagination (default: 0)
	 *
	 * @return array{results: array, total: int} Paginated template results
	 *
	 * @throws Exception If listing fails
	 *
	 * @spec openspec/specs/template-management/spec.md
	 */
	public function getTemplates(array $filters = [], int $limit = 20, int $offset = 0): array;

	/**
	 * Get a single template by UUID
	 *
	 * @param string $id The template UUID
	 *
	 * @return array The template object
	 *
	 * @throws Exception If the template is not found
	 *
	 * @spec openspec/specs/template-management/spec.md
	 */
	public function getTemplate(string $id): array;

	/**
	 * Get a template as it was at one version, or the head when none is named.
	 *
	 * A pinned version renders the content stored in its snapshot. Nothing is
	 * restored: the head stays where it is. A version the chain does not hold
	 * is refused rather than answered with the head, because a document
	 * claiming a version it was not rendered from is worse than no document.
	 *
	 * @param string   $id      The template id
	 * @param int|null $version The version to read, or null for the head
	 *
	 * @return array The template, with `version` set to the version returned
	 *
	 * @throws Exception 404 naming the template and the version when it does not exist
	 *
	 * @spec openspec/changes/generated-document-names-its-template-version/specs/template-version-provenance/spec.md#requirement-a-caller-can-pin-the-template-version-to-render-req-ddtvp-003
	 */
	public function getTemplateAtVersion(string $id, ?int $version): array;

	/**
	 * Create a new template
	 *
	 * @param array $data Template data (name, content, namespace required)
	 *
	 * @return array The created template object
	 *
	 * @throws Exception If creation fails or validation errors occur
	 *
	 * @spec openspec/specs/template-management/spec.md
	 */
	public function createTemplate(array $data): array;

	/**
	 * Update an existing template with version history
	 *
	 * The namespace field cannot be changed after creation. A save is refused
	 * while another user holds a live edit lock, and the previous state is kept
	 * as a version once the save has succeeded.
	 *
	 * @param string $id The template UUID
	 * @param array $data Updated template data
	 *
	 * @return array The updated template object
	 *
	 * @throws Exception If the template is not found or update fails
	 *
	 * @spec openspec/specs/template-management/spec.md
	 */
	public function updateTemplate(string $id, array $data): array;

	/**
	 * Update a template without creating a version (used for restore operations)
	 *
	 * @param string $id The template UUID
	 * @param array $data Updated template data
	 *
	 * @return array The updated template object
	 *
	 * @throws Exception If the template is not found or update fails
	 *
	 * @spec openspec/specs/template-management/spec.md
	 */
	public function updateTemplateWithoutVersion(string $id, array $data): array;

	/**
	 * Delete a template
	 *
	 * @param string $id The template UUID
	 *
	 * @return bool True if deletion succeeded
	 *
	 * @throws Exception If the template is not found or deletion fails
	 *
	 * @spec openspec/specs/template-management/spec.md
	 */
	public function deleteTemplate(string $id): bool;

	/**
	 * Get all templates for a specific app namespace
	 *
	 * @param string $namespace The app namespace (e.g. 'larpingapp')
	 *
	 * @return array Array of template objects
	 *
	 * @throws Exception If listing fails
	 *
	 * @spec openspec/specs/template-management/spec.md
	 */
	public function getTemplatesByNamespace(string $namespace): array;

	/**
	 * Duplicate a template
	 *
	 * Creates a copy with name suffixed " (kopie)", a new UUID,
	 * and no version history. Preserves namespace, category, and tags.
	 *
	 * @param string $id The UUID of the template to duplicate
	 *
	 * @return array The duplicated template object
	 *
	 * @throws Exception If the template is not found or duplication fails
	 *
	 * @spec openspec/specs/template-management/spec.md
	 */
	public function duplicateTemplate(string $id): array;

	/**
	 * Acquire an edit lock on a template
	 *
	 * Sets lockedBy and lockedAt if the template is not currently locked
	 * or if the existing lock has expired (older than 15 minutes).
	 *
	 * @param string $id The template UUID
	 * @param string $userId The Nextcloud user ID requesting the lock
	 *
	 * @return array The updated template object with lock information
	 *
	 * @throws Exception If the template is locked by another user (code 409)
	 *
	 * @spec openspec/specs/template-management/spec.md
	 */
	public function acquireLock(string $id, string $userId): array;

	/**
	 * Release an edit lock on a template
	 *
	 * Clears the lockedBy and lockedAt fields if the lock is held by the given user.
	 *
	 * @param string $id The template UUID
	 * @param string $userId The Nextcloud user ID releasing the lock
	 *
	 * @return array The updated template object with lock cleared
	 *
	 * @throws Exception If the template is not locked by this user
	 *
	 * @spec openspec/specs/template-management/spec.md
	 */
	public function releaseLock(string $id, string $userId): array;
}//end interface
