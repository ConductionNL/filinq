<?php

/**
 * Contract of SigningDelegationService
 *
 * The public surface of the SigningDelegationService service, so callers in other
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

use InvalidArgumentException;

/**
 * Contract of SigningDelegationService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface SigningDelegationServiceInterface {
	/**
	 * Record a delegation from the signer to a colleague.
	 *
	 * @param string            $delegator The signer giving the authority
	 * @param string            $delegate  The colleague receiving it
	 * @param string            $from      First day, Y-m-d
	 * @param string            $until     Last day, Y-m-d
	 * @param array<int, mixed> $types     The `<app>/<schema>` document types it covers
	 *
	 * @return array{id: string, delegator: string, delegate: string, from: string, until: string, types: list<string>} The stored delegation.
	 *
	 * @throws InvalidArgumentException When a party, a date or the types are missing or wrong.
	 *
	 * @spec openspec/changes/specify-capability-rows/specs/document-signing/spec.md#requirement-a-signer-delegates-signing-authority-for-a-period-and-for-named-document-types-req-scr-003
	 */
	public function declare(string $delegator, string $delegate, string $from, string $until, array $types): array;


	/**
	 * Withdraw a delegation the signer gave.
	 *
	 * @param string $id        The delegation
	 * @param string $delegator The signer asking; only the giver may withdraw it
	 *
	 * @return bool True when it was withdrawn, false when the signer gave no such delegation.
	 *
	 * @spec openspec/changes/specify-capability-rows/specs/document-signing/spec.md#requirement-a-signer-delegates-signing-authority-for-a-period-and-for-named-document-types-req-scr-003
	 */
	public function withdraw(string $id, string $delegator): bool;


	/**
	 * The delegations a user gave and received.
	 *
	 * @param string $userId The user
	 *
	 * @return array{given: list<array<string, mixed>>, received: list<array<string, mixed>>}
	 *
	 * @spec openspec/changes/specify-capability-rows/specs/document-signing/spec.md#requirement-a-signer-delegates-signing-authority-for-a-period-and-for-named-document-types-req-scr-003
	 */
	public function forUser(string $userId): array;
}//end interface
