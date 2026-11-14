<?php

/**
 * Contract of LegalHoldCaseService
 *
 * The public surface of the LegalHoldCaseService service, so callers in other
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

use OCA\Filinq\Exception\LegalHoldRefusedException;

/**
 * Contract of LegalHoldCaseService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface LegalHoldCaseServiceInterface {
	/**
	 * The cases matching the filters.
	 *
	 * @param array<string, string> $filters status, holdType and/or custodian.
	 * @param string                $userId  The caller.
	 *
	 * @return array<int, array<string, mixed>> The cases.
	 *
	 * @throws LegalHoldRefusedException When the caller has no hold authority.
	 *
	 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-3.1
	 */
	public function list(array $filters, string $userId): array;

	/**
	 * One case.
	 *
	 * @param string $uuid   The case.
	 * @param string $userId The caller.
	 *
	 * @return array<string, mixed> The case.
	 *
	 * @throws LegalHoldRefusedException When the caller has no hold authority or there is no such case.
	 *
	 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-3.1
	 */
	public function get(string $uuid, string $userId): array;

	/**
	 * Open a case and freeze its records.
	 *
	 * @param array<string, mixed> $input  name, holdType, reason, caseReference, custodian, scopeDocuments, scopeDossiers.
	 * @param string               $userId The caller.
	 *
	 * @return array<string, mixed> The case, with its fan-out.
	 *
	 * @throws LegalHoldRefusedException When the caller has no hold authority or the input is incomplete.
	 *
	 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-2.1
	 */
	public function place(array $input, string $userId): array;

	/**
	 * Add references to an active case and freeze only those.
	 *
	 * @param string               $uuid   The case.
	 * @param array<string, mixed> $input  scopeDocuments and/or scopeDossiers to add.
	 * @param string               $userId The caller.
	 *
	 * @return array<string, mixed> The case.
	 *
	 * @throws LegalHoldRefusedException When refused.
	 *
	 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-2.2
	 */
	public function addScope(string $uuid, array $input, string $userId): array;

	/**
	 * Try again for every record whose freeze or unfreeze failed.
	 *
	 * @param string $uuid   The case.
	 * @param string $userId The caller.
	 *
	 * @return array<string, mixed> The case.
	 *
	 * @throws LegalHoldRefusedException When refused.
	 *
	 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-2.1
	 */
	public function retry(string $uuid, string $userId): array;

	/**
	 * Release a case: a reason is required, and records another active case
	 * still lists stay frozen.
	 *
	 * @param string $uuid          The case.
	 * @param string $releaseReason Why.
	 * @param string $userId        The caller.
	 *
	 * @return array<string, mixed> The released case.
	 *
	 * @throws LegalHoldRefusedException When refused.
	 *
	 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-2.2
	 */
	public function release(string $uuid, string $releaseReason, string $userId): array;

	/**
	 * Whether a record is frozen, and by which cases.
	 *
	 * Anyone who can open the record learns whether it is held; only someone
	 * with hold authority learns which matter holds it, because the existence of
	 * a lawsuit is not everybody's business.
	 *
	 * @param string $ref    The record uuid.
	 * @param string $userId The caller.
	 *
	 * @return array{held: bool, cases: array<int, array{uuid: string, name: string}>} The status.
	 *
	 * @throws LegalHoldRefusedException When the caller cannot read the record.
	 *
	 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-3.2
	 */
	public function statusFor(string $ref, string $userId): array;
}//end interface
