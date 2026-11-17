<?php

/**
 * Contract of RefusalGroundsLegacyMap
 *
 * The public surface of the RefusalGroundsLegacyMap service that callers in other
 * components use, so they depend on this contract instead of the implementation.
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
 * Contract of RefusalGroundsLegacyMap.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface RefusalGroundsLegacyMapInterface {
	/**
	 * Every old slug with its code or `unmapped`.
	 *
	 * @return array<string, string> Slug to code.
	 *
	 * @throws RuntimeException When the mapping file cannot be read.
	 *
	 * @spec openspec/changes/grondslagen-read-from-dossiq/tasks.md
	 */
	public function all(): array;

	/**
	 * Whether a reference is one of filinq's old Woo slugs.
	 *
	 * @param string $slug The reference.
	 *
	 * @return bool
	 *
	 * @spec openspec/changes/grondslagen-read-from-dossiq/tasks.md
	 */
	public function isLegacySlug(string $slug): bool;

	/**
	 * The dossiq code an old slug re-points to, or null when it is not an old
	 * slug or is unmapped. Never a guess.
	 *
	 * @param string $slug The reference.
	 *
	 * @return string|null The code.
	 *
	 * @spec openspec/changes/grondslagen-read-from-dossiq/tasks.md
	 */
	public function codeFor(string $slug): ?string;
}//end interface
