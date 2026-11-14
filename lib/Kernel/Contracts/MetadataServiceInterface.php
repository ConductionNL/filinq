<?php

/**
 * Contract of MetadataService
 *
 * The public surface of the MetadataService service, so callers in other
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
 * Contract of MetadataService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface MetadataServiceInterface {
	/**
	 * Enhance metadata for a document object
	 *
	 * @param array<string, mixed> $objectData The document object data from OpenRegister
	 *
	 * @return array<string, mixed> Enhanced metadata fields
	 *
	 * @throws Exception If metadata enhancement fails
	 *
	 * @spec openspec/specs/metadata-enrichment/spec.md
	 */
	public function enhanceMetadata(array $objectData): array;

	/**
	 * Enrich a document object with metadata and save it back via ObjectService
	 *
	 * The read + write run under the requesting user's OpenRegister RBAC. This
	 * is the variant every controller / user-request caller must use. Callers
	 * without a user session (event listeners reacting to webcron-created
	 * objects) must use {@see saveEnrichedMetadataAsSystem()} instead.
	 *
	 * @param string $objectId The object UUID in OpenRegister
	 * @param string $register The register ID
	 * @param string $schema The schema ID
	 * @param array<string, mixed> $metadata The metadata to merge into the object
	 *
	 * @return array<string, mixed> Updated object data
	 *
	 * @throws Exception If saving fails
	 *
	 * @spec openspec/specs/metadata-enrichment/spec.md
	 * @spec exclude system-context adoption
	 */
	public function saveEnrichedMetadata(string $objectId, string $register, string $schema, array $metadata): array;

	/**
	 * Enrich a document object with metadata as a trusted system operation.
	 *
	 * The read + write run inside OpenRegister's `ObjectService::runAsSystem()`
	 * scoped elevation. This is for app-initiated maintenance without a user
	 * session (event listeners reacting to webcron-created objects), where RBAC
	 * would otherwise deny every write as 'Anonymous'. On released OpenRegister
	 * versions without `runAsSystem()` the call falls back to the direct
	 * (non-elevated) path.
	 *
	 * @param string $objectId The object UUID in OpenRegister
	 * @param string $register The register ID
	 * @param string $schema The schema ID
	 * @param array<string, mixed> $metadata The metadata to merge into the object
	 *
	 * @return array<string, mixed> Updated object data
	 *
	 * @throws Exception If saving fails
	 *
	 * @spec openspec/specs/metadata-enrichment/spec.md
	 * @spec exclude system-context adoption
	 */
	public function saveEnrichedMetadataAsSystem(string $objectId, string $register, string $schema, array $metadata): array;
}//end interface
