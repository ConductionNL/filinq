<?php

/**
 * Contract of TemplateVersionService
 *
 * The public surface of the TemplateVersionService service, so callers in other
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

use DateTimeInterface;
use Exception;
use OCA\Filinq\Kernel\Contracts\TemplateServiceInterface;

/**
 * Contract of TemplateVersionService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface TemplateVersionServiceInterface {
	/**
	 * Create a version snapshot of a template's current state
	 *
	 * @param string $templateId The UUID of the parent template
	 * @param array $templateState The current template data to capture
	 * @param string $editor The Nextcloud user ID who made the edit
	 * @param string|null $changelog Optional note describing the change
	 *
	 * @return array The created version object
	 *
	 * @throws Exception If version creation fails
	 *
	 * @spec openspec/specs/template-management/spec.md
	 */
	public function createVersion(string $templateId, array $templateState, string $editor, ?string $changelog = null): array;

	/**
	 * List versions for a template, ordered by version number descending
	 *
	 * @param string $templateId The UUID of the parent template
	 * @param int $limit Maximum number of results (default: 20)
	 * @param int $offset Result offset for pagination (default: 0)
	 *
	 * @return array{results: array, total: int} Paginated version results
	 *
	 * @throws Exception If listing fails
	 *
	 * @spec openspec/specs/template-management/spec.md
	 */
	public function getVersions(string $templateId, int $limit = 20, int $offset = 0): array;

	/**
	 * Get a single version by UUID
	 *
	 * @param string $versionId The version UUID
	 *
	 * @return array The version object
	 *
	 * @throws Exception If the version is not found
	 *
	 * @spec openspec/specs/template-management/spec.md
	 */
	public function getVersion(string $versionId): array;

	/**
	 * Get the next version number for a template
	 *
	 * @param string $templateId The UUID of the parent template
	 *
	 * @return int The next version number (existing count + 1)
	 *
	 * @throws Exception If counting fails
	 *
	 * @spec openspec/specs/template-management/spec.md
	 */
	public function getNextVersionNumber(string $templateId): int;

	/**
	 * Find the stored snapshot of one version of a template, by its number.
	 *
	 * Snapshot N holds the content version N had while it was the head. The
	 * head itself has no snapshot: it is the template object.
	 *
	 * @param string $templateId The UUID of the parent template
	 * @param int    $number     The version number
	 *
	 * @return array|null The snapshot, or null when the chain has no such version
	 *
	 * @throws Exception If the chain cannot be read
	 *
	 * @spec openspec/changes/generated-document-names-its-template-version/specs/template-version-provenance/spec.md#requirement-a-caller-can-pin-the-template-version-to-render-req-ddtvp-003
	 */
	public function findVersionByNumber(string $templateId, int $number): ?array;

	/**
	 * Which version of a template was the head at a moment.
	 *
	 * 🔑 A snapshot is written when its version is REPLACED, so snapshot N's
	 * creation time is the moment version N stopped being the head, and version
	 * N+1 started. Version 1 started when the template itself was created. The
	 * version in force at a moment is therefore the lowest N whose snapshot was
	 * created after it, or the head when every snapshot is older. A moment
	 * before the template existed has no version, and this method says so
	 * rather than rounding to the oldest one.
	 *
	 * @param string            $templateId The UUID of the parent template
	 * @param DateTimeInterface $moment     The moment asked about
	 *
	 * @return int|null The version number, or null when none was in force or the chain cannot say
	 *
	 * @throws Exception If the chain cannot be read
	 *
	 * @spec openspec/changes/generated-document-names-its-template-version/specs/template-version-provenance/spec.md#requirement-filinq-answers-which-version-was-in-force-on-a-date-req-ddtvp-004
	 */
	public function versionInForceAt(string $templateId, DateTimeInterface $moment): ?int;

	/**
	 * Restore a template to a previous version
	 *
	 * Saves the current template state as a new version, then updates the
	 * template with the content from the target version.
	 *
	 * @param string $templateId The UUID of the template to restore
	 * @param string $versionId The UUID of the version to restore to
	 * @param string $editor The Nextcloud user ID performing the restore
	 * @param TemplateServiceInterface $service The template service for updating the template
	 *
	 * @return array The restored template object
	 *
	 * @throws Exception If restore fails
	 *
	 * @spec openspec/specs/template-management/spec.md
	 */
	public function restoreVersion(string $templateId, string $versionId, string $editor, TemplateServiceInterface $service): array;

	/**
	 * Get two versions for client-side diff comparison
	 *
	 * @param string $versionIdFrom The UUID of the source version
	 * @param string $versionIdTo The UUID of the target version
	 *
	 * @return array{from: array, to: array} Both version objects
	 *
	 * @throws Exception If either version is not found
	 *
	 * @spec openspec/specs/template-management/spec.md
	 */
	public function getDiff(string $versionIdFrom, string $versionIdTo): array;
}//end interface
