<?php

/**
 * Contract of ScanProfileService
 *
 * The public surface of the ScanProfileService service, so callers in other
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
 * Contract of ScanProfileService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface ScanProfileServiceInterface {
	/**
	 * The configuration key the profiles live under.
	 *
	 * @var string
	 */
	public const CONFIG_KEY = 'scanProfiles';

	/**
	 * Every declared profile.
	 *
	 * @return array<int, array<string, mixed>> The profiles.
	 *
	 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
	 */
	public function all(): array;

	/**
	 * One profile by its id.
	 *
	 * @param string $id The profile id.
	 *
	 * @return array<string, mixed>|null The profile, or null when nobody declared it.
	 *
	 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
	 */
	public function find(string $id): ?array;

	/**
	 * The profile whose watched folder a file landed in.
	 *
	 * The match is on the folder PATH, and the longest matching folder wins, so
	 * a profile for `Scans/bezwaar` is not shadowed by one for `Scans`.
	 *
	 * @param string $path The path the file landed at.
	 *
	 * @return array<string, mixed>|null The profile, or null when the path is nobody's.
	 *
	 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
	 */
	public function forPath(string $path): ?array;

	/**
	 * Declare the profiles.
	 *
	 * @param array<int, array<string, mixed>> $profiles The profiles.
	 *
	 * @return array<int, array<string, mixed>> What was stored.
	 *
	 * @throws RuntimeException When a profile names no id or no folder.
	 *
	 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
	 */
	public function declare(array $profiles): array;
}//end interface
