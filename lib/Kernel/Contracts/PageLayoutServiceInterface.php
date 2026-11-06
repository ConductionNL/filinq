<?php

/**
 * Contract of PageLayoutService
 *
 * The public surface of the PageLayoutService service, so callers in other
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

use RuntimeException;

/**
 * Contract of PageLayoutService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface PageLayoutServiceInterface {
	/**
	 * The schema holding the layouts.
	 *
	 * @var string
	 */
	public const SCHEMA = 'pageLayout';

	/**
	 * The layout a template names, at the version it names.
	 *
	 * A template that names no version gets the ACTIVE one, which is the
	 * current paper. A template that names version 2 gets version 2, even
	 * after version 3 exists: that is the whole reason versions exist.
	 *
	 * @param string $name The layout name.
	 * @param int|null $version The version it names, or null for the active one.
	 *
	 * @return array<string, mixed>|null The layout, or null when there is none.
	 *
	 * @spec openspec/specs/document-creatie-sjablonen/spec.md
	 */
	public function resolve(string $name, ?int $version = null): ?array;

	/**
	 * Every layout at its active version, for the admin list.
	 *
	 * @return array<int, array<string, mixed>> The active layouts, by name.
	 *
	 * @spec openspec/specs/document-creatie-sjablonen/spec.md
	 */
	public function activeLayouts(): array;

	/**
	 * Write the first version of a new layout.
	 *
	 * The version bookkeeping is the service's, never the caller's, exactly as
	 * for an edit.
	 *
	 * @param string $name The layout name.
	 * @param array<string, mixed> $fields The paper, margins, header, footer, logo and first-page fields.
	 *
	 * @return array<string, mixed> The stored version 1.
	 *
	 * @throws RuntimeException When the name is empty or already taken, or the write fails.
	 *
	 * @spec openspec/specs/document-creatie-sjablonen/spec.md
	 */
	public function create(string $name, array $fields): array;

	/**
	 * Edit a layout by writing the next version of it.
	 *
	 * The earlier version is left on disk and only marked inactive. Documents
	 * and templates naming it keep resolving to exactly what they were made
	 * with.
	 *
	 * @param string $name The layout name.
	 * @param array<string, mixed> $changes The fields to change.
	 *
	 * @return array<string, mixed> The new version.
	 *
	 * @throws RuntimeException When there is no such layout, or the write fails.
	 *
	 * @spec openspec/specs/document-creatie-sjablonen/spec.md
	 */
	public function edit(string $name, array $changes): array;

	/**
	 * The PDF options one layout asks for.
	 *
	 * @param array<string, mixed> $layout The layout.
	 *
	 * @return array<string, mixed> The options, in the shape the render pipeline takes.
	 *
	 * @spec openspec/specs/document-creatie-sjablonen/spec.md
	 */
	public function pdfOptions(array $layout): array;

	/**
	 * Record on a generated document which layout version made it.
	 *
	 * @param array<string, mixed> $document The generated-document entry.
	 * @param array<string, mixed>|null $layout The layout it rendered through.
	 *
	 * @return array<string, mixed> The entry, naming the layout version.
	 *
	 * @spec openspec/specs/document-creatie-sjablonen/spec.md
	 */
	public function stamp(array $document, ?array $layout): array;

	/**
	 * Every version of one layout, newest version first.
	 *
	 * @param string $name The layout name.
	 *
	 * @return array<int, array<string, mixed>> The versions.
	 *
	 * @spec openspec/specs/document-creatie-sjablonen/spec.md
	 */
	public function versionsOf(string $name): array;
}//end interface
