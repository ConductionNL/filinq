<?php

/**
 * Contract of ContractService
 *
 * The public surface of the ContractService service, so callers in other
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

use OCA\Filinq\Exception\ContractNotFoundException;
use InvalidArgumentException;

/**
 * Contract of ContractService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface ContractServiceInterface {
	/**
	 * Renew an active contract: a successor draft carrying its parties, type,
	 * owner and value, linked both ways, and the original marked renewed.
	 *
	 * @param string $uuid The contract to renew.
	 *
	 * @return array{contract: array<string, mixed>, successor: array<string, mixed>} Both contracts as stored.
	 *
	 * @throws ContractNotFoundException When the caller cannot read the contract.
	 * @throws InvalidArgumentException  When the contract is not active (409).
	 *
	 * @spec openspec/changes/archive/2026-09-30-contract-lifecycle-management/tasks.md#2-1
	 */
	public function renew(string $uuid): array;

	/**
	 * End an active contract early. A reason is required and stored.
	 *
	 * @param string $uuid   The contract to terminate.
	 * @param string $reason Why it ends.
	 *
	 * @return array<string, mixed> The stored contract.
	 *
	 * @throws ContractNotFoundException When the caller cannot read the contract.
	 * @throws InvalidArgumentException  When the reason is empty (400) or the contract is not active (409).
	 *
	 * @spec openspec/changes/archive/2026-09-30-contract-lifecycle-management/tasks.md#2-1
	 */
	public function terminate(string $uuid, string $reason): array;

	/**
	 * Accept or reject one proposed key term. Accepting writes the value into
	 * its field; rejecting only marks the suggestion. A suggestion that was
	 * already decided is refused.
	 *
	 * @param string $uuid   The contract.
	 * @param int    $index  The suggestion's position in `keyTermSuggestions`.
	 * @param string $decision `accepted` or `rejected`.
	 *
	 * @return array<string, mixed> The stored contract.
	 *
	 * @throws ContractNotFoundException When the caller cannot read the contract.
	 * @throws InvalidArgumentException  When there is no such proposed suggestion (404/409) or its value does not fit (422).
	 *
	 * @spec openspec/changes/archive/2026-09-30-contract-lifecycle-management/tasks.md#2-1
	 */
	public function decideSuggestion(string $uuid, int $index, string $decision): array;

	/**
	 * A contract the caller can read.
	 *
	 * @param string $uuid The contract uuid.
	 *
	 * @return array<string, mixed> The contract.
	 *
	 * @throws ContractNotFoundException When it is not found or not readable.
	 *
	 * @spec openspec/changes/archive/2026-09-30-contract-lifecycle-management/tasks.md#2-3
	 */
	public function requireReadable(string $uuid): array;
}//end interface
