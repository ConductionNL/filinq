<?php

/**
 * Contract of ContractParties
 *
 * The public surface of the ContractParties service, so callers in other
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
 * Contract of ContractParties.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface ContractPartiesInterface {
	/**
	 * The contract's parties as the detail shows them.
	 *
	 * @param array<string, mixed> $contract The contract.
	 *
	 * @return list<array{role: string, displayName: string, contactRef: string, linked: bool, email: string}> The parties.
	 *
	 * @spec openspec/changes/archive/2026-09-30-contract-lifecycle-management/tasks.md#3-1
	 */
	public function resolve(array $contract): array;
}//end interface
