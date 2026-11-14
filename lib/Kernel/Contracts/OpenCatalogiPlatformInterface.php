<?php

/**
 * Contract of OpenCatalogiPlatform
 *
 * The public surface of the OpenCatalogiPlatform service, so callers in other
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

use OCP\Files\File;
use RuntimeException;

/**
 * Contract of OpenCatalogiPlatform.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface OpenCatalogiPlatformInterface {
	/**
	 * Whether the publication platform (OpenCatalogi) is there.
	 *
	 * @return bool True when OpenCatalogi is enabled.
	 *
	 * @spec openspec/changes/archive/2026-09-29-woo-publicatie-pipeline/tasks.md#task-2.3
	 */
	public function available(): bool;

	/**
	 * The Woo information categories, from OpenCatalogi's own TOOI value list.
	 *
	 * The code is OpenCatalogi's own (`infocat001` to `infocat017`), the value
	 * its publication schema accepts.
	 *
	 * @return list<array{code: string, label: string}> The categories, empty when OpenCatalogi is not there.
	 *
	 * @spec openspec/changes/archive/2026-09-29-woo-publicatie-pipeline/tasks.md#task-3.1
	 * @spec openspec/changes/woo-hand-off-files-its-category/tasks.md#task-1.1
	 */
	public function categories(): array;

	/**
	 * OpenCatalogi's code for a stored category: the code itself, or the code
	 * whose TOOI URI ends in the stored basename (records from before the codes).
	 *
	 * @param string $value The stored category.
	 *
	 * @return string|null The code, or null when the value is neither or OpenCatalogi is not there.
	 *
	 * @spec openspec/changes/woo-hand-off-files-its-category/tasks.md#task-1.1
	 */
	public function toPlatformCode(string $value): ?string;

	/**
	 * Attach the redacted copy to the platform's publication, shared so the public sees it.
	 *
	 * @param string $publicationUuid The platform's publication
	 * @param string $fileName        The file name
	 * @param string $content         The bytes
	 *
	 * @return void
	 *
	 * @throws RuntimeException When OpenRegister cannot attach it.
	 *
	 * @spec openspec/changes/archive/2026-09-29-woo-publicatie-pipeline/tasks.md#task-2.3
	 */
	public function attach(string $publicationUuid, string $fileName, string $content): void;

	/**
	 * The redacted copy, read from the actor's files.
	 *
	 * @param string $fileId The copy's file id
	 * @param string $actor  Whose files
	 *
	 * @return File The copy.
	 *
	 * @throws RuntimeException When the actor cannot read it.
	 *
	 * @spec openspec/changes/archive/2026-09-29-woo-publicatie-pipeline/tasks.md#task-2.3
	 */
	public function readCopy(string $fileId, string $actor): File;
}//end interface
