<?php

/**
 * Contract of FieldFillProfileService
 *
 * The public surface of the FieldFillProfileService service, so callers in other
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

use OCA\Filinq\Exception\FieldFillRefused;

/**
 * Contract of FieldFillProfileService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface FieldFillProfileServiceInterface {
	/**
	 * Every declared profile, active or not.
	 *
	 * @return array<int, array<string, mixed>> The profiles.
	 *
	 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
	 */
	public function all(): array;

	/**
	 * Store a profile after checking it against the schema it names.
	 *
	 * @param array<string, mixed> $profile The profile: declaringApp, register, schema, fields and active.
	 * @param string|null          $uuid    The profile to replace, or null for a new one.
	 *
	 * @return array<string, mixed> The stored profile.
	 *
	 * @throws FieldFillRefused 400 when the profile is incomplete, names an unknown field or a bsn field.
	 *
	 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
	 */
	public function save(array $profile, ?string $uuid = null): array;
}//end interface
