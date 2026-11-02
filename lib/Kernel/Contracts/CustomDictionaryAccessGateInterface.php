<?php

/**
 * Contract of CustomDictionaryAccessGate
 *
 * The public surface of the CustomDictionaryAccessGate service, so callers in other
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
 * Contract of CustomDictionaryAccessGate.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface CustomDictionaryAccessGateInterface {
	/**
	 * The group that maintains custom dictionaries, as the schema names it.
	 *
	 * @var string
	 */
	public const MAINTAINER_GROUP = 'filinq-policy-admins';

	/**
	 * Whether OpenRegister is installed. Callers use this to return an
	 * explanatory unavailable state instead of crashing (REQ-DDCDR-004).
	 *
	 * @return bool
	 *
	 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
	 */
	public function isAvailable(): bool;

	/**
	 * Whether the caller maintains custom dictionaries.
	 *
	 * @return bool True for an admin or a member of the maintainer group.
	 *
	 * @spec openspec/specs/custom-dictionary-recognition/spec.md
	 */
	public function mayMaintain(): bool;

	/**
	 * Refuse unless the caller maintains custom dictionaries.
	 *
	 * Creating, changing or deleting a list changes what anonymisation
	 * detects for everybody, so it belongs to the group the schema names
	 * (and to admins), not to every member of the organisation.
	 *
	 * @return void
	 *
	 * @throws RuntimeException When the caller is neither an admin nor a maintainer.
	 *
	 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
	 */
	public function assertMayMaintain(): void;

	/**
	 * Fail-closed organisation-membership check for one record.
	 *
	 * A record without an organisation, or any failure resolving
	 * OpenRegister's `OrganisationService`, is treated as inaccessible —
	 * never as "everyone may access it".
	 *
	 * @param array<string, mixed> $record The record to check.
	 *
	 * @return bool True when the current caller may read/write this record.
	 *
	 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
	 */
	public function callerHasAccess(array $record): bool;

	/**
	 * Extract the `@self` metadata block from a record, defensively.
	 *
	 * @param array<string, mixed> $record The record.
	 *
	 * @return array<string, mixed>
	 *
	 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
	 */
	public function selfMeta(array $record): array;
}//end interface
