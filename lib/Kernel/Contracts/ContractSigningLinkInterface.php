<?php

/**
 * Contract of ContractSigningLink
 *
 * The public surface of the ContractSigningLink service, so callers in other
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
 * Contract of ContractSigningLink.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface ContractSigningLinkInterface {
	/**
	 * Record the request on the contract and, when it completed, the signed document.
	 *
	 * @param string $uuid             The contract.
	 * @param string $signingRequestId The signing request sent for it.
	 *
	 * @return array{contract: array<string, mixed>, signingRequest: array<string, mixed>} The contract and what the detail shows of the request.
	 *
	 * @throws ContractNotFoundException When the contract is not there or not readable.
	 * @throws InvalidArgumentException  400 without a request id, 404 when the caller cannot read the request.
	 *
	 * @spec openspec/changes/archive/2026-09-30-contract-lifecycle-management/tasks.md#3-1
	 */
	public function link(string $uuid, string $signingRequestId): array;
}//end interface
