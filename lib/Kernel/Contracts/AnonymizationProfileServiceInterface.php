<?php

/**
 * Contract of WooProfileService
 *
 * The public surface of the WooProfileService service, so callers in other
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

/**
 * Contract of WooProfileService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface AnonymizationProfileServiceInterface {
	/**
	 * Return the active WOO anonymization profile.
	 *
	 * @return array{anonymize: array<string>, keep: array<string>} Active profile (configured or default).
	 *
	 * @spec openspec/specs/batch-anonymization/spec.md#requirement-woo-entity-category-profiles
	 */
	public function getProfile(): array;

	/**
	 * Persist a WOO anonymization profile.
	 *
	 * @param array{anonymize: array<string>, keep: array<string>} $profile Profile to store.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/batch-anonymization/spec.md#requirement-woo-entity-category-profiles
	 */
	public function saveProfile(array $profile): void;

	/**
	 * Check whether the given entity type is subject to anonymization under the active profile.
	 *
	 * @param string $entityType Entity type to check (e.g., "PERSON", "BSN").
	 *
	 * @return bool True when the type should be anonymized.
	 *
	 * @spec openspec/specs/batch-anonymization/spec.md#requirement-woo-entity-category-profiles
	 */
	public function shouldAnonymize(string $entityType): bool;
}//end interface
