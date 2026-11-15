<?php

/**
 * Contract of ContractTermSuggestionService
 *
 * The public surface of the ContractTermSuggestionService service, so callers in other
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
 * Contract of ContractTermSuggestionService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface ContractTermSuggestionServiceInterface {
	/**
	 * TOGGLE
	 */
	public const TOGGLE = 'enable_contract_term_extraction';

	/**
	 * Whether extraction is switched on (default on).
	 *
	 * @return bool True when it runs.
	 *
	 * @spec openspec/changes/archive/2026-09-30-contract-lifecycle-management/tasks.md#2-2
	 */
	public function isEnabled(): bool;

	/**
	 * Read the contract's documents and add what they propose. A term already
	 * suggested with the same value is not suggested again; a decided
	 * suggestion stays as it is. Contract fields are never touched.
	 *
	 * @param array<string, mixed> $contract The contract (from ContractService::requireReadable()).
	 * @param string               $userId   The caller, whose files are read.
	 *
	 * @return array{contract: array<string, mixed>, added: int, enabled: bool} The stored contract and how many were added.
	 *
	 * @spec openspec/changes/archive/2026-09-30-contract-lifecycle-management/tasks.md#2-2
	 */
	public function suggest(array $contract, string $userId): array;
}//end interface
