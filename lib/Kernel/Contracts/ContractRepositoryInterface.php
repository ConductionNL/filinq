<?php

/**
 * Contract of ContractRepository
 *
 * The public surface of the ContractRepository service, so callers in other
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
 * Contract of ContractRepository.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface ContractRepositoryInterface {
	/**
	 * SCHEMA
	 */
	public const SCHEMA = 'documentContract';

	/**
	 * A contract the caller can read, or null.
	 *
	 * @param string $uuid The contract uuid.
	 *
	 * @return array<string, mixed>|null The contract fields with `uuid`.
	 *
	 * @spec openspec/changes/archive/2026-09-30-contract-lifecycle-management/tasks.md#2-3
	 */
	public function find(string $uuid): ?array;

	/**
	 * Save a whole contract (every field carried, never a partial object).
	 *
	 * @param array<string, mixed> $contract The contract fields.
	 * @param string|null          $uuid     The uuid to update, or null to create.
	 *
	 * @return array<string, mixed> The stored contract with `uuid`.
	 *
	 * @throws RuntimeException When OpenRegister refuses the write.
	 *
	 * @spec openspec/changes/archive/2026-09-30-contract-lifecycle-management/tasks.md#2-1
	 */
	public function save(array $contract, ?string $uuid = null): array;
}//end interface
