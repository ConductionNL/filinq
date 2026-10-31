<?php

/**
 * Contract of BulkSigningService
 *
 * The public surface of the BulkSigningService service, so callers in other
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
use RuntimeException;

/**
 * Contract of BulkSigningService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface BulkSigningServiceInterface {
	/**
	 * Phase 1: validate a recipient list into a batch that is ready and has sent nothing.
	 *
	 * @param array  $settings Title, documentFileId, documentName, signatureLevel, signingMode, provider
	 * @param string $content  The list's bytes
	 * @param string $filename The list's file name
	 * @param string $userId   The initiator
	 *
	 * @return array The stored batch, status `ready`
	 *
	 * @throws RuntimeException 400 when the request a row would become is invalid for everybody
	 * @throws \InvalidArgumentException 400 when the list cannot be read
	 *
	 * @spec openspec/specs/bulk-signing-field-builder/spec.md#requirement-bulk-send-validates-first-then-creates-isolated-ordinary-requests-req-ddbsf-002
	 */
	public function createBatch(array $settings, string $content, string $filename, string $userId): array;

	/**
	 * Phase 2 starts here: the initiator confirms and the job is queued.
	 *
	 * @param string $id      The batch uuid
	 * @param string $userId  The caller
	 * @param bool   $isAdmin Whether the caller is an admin
	 *
	 * @return array|null The batch, status `creating`, or null when the caller may not reach it
	 *
	 * @throws RuntimeException 409 when the batch is not ready or names nobody to send to
	 *
	 * @spec openspec/specs/bulk-signing-field-builder/spec.md#requirement-bulk-send-validates-first-then-creates-isolated-ordinary-requests-req-ddbsf-002
	 */
	public function confirm(string $id, string $userId, bool $isAdmin): ?array;

	/**
	 * Cancel a batch, and every member request that can still be cancelled.
	 *
	 * A member that is already signed, declined or expired stays as it is.
	 *
	 * @param string $id      The batch uuid
	 * @param string $userId  The caller
	 * @param bool   $isAdmin Whether the caller is an admin
	 *
	 * @return array|null The batch with `cancelledRequests`, or null when the caller may not reach it
	 *
	 * @spec openspec/specs/bulk-signing-field-builder/spec.md#requirement-bulk-send-validates-first-then-creates-isolated-ordinary-requests-req-ddbsf-002
	 */
	public function cancel(string $id, string $userId, bool $isAdmin): ?array;

	/**
	 * Read one batch.
	 *
	 * @param string $id      The batch uuid
	 * @param string $userId  The caller
	 * @param bool   $isAdmin Whether the caller is an admin
	 *
	 * @return array|null The batch, or null when absent or not the caller's
	 *
	 * @spec openspec/specs/bulk-signing-field-builder/spec.md#requirement-bulk-send-validates-first-then-creates-isolated-ordinary-requests-req-ddbsf-002
	 */
	public function get(string $id, string $userId, bool $isAdmin): ?array;

	/**
	 * List the caller's batches, or every batch for an admin.
	 *
	 * @param string $userId  The caller
	 * @param bool   $isAdmin Whether the caller is an admin
	 *
	 * @return list<array>
	 *
	 * @spec openspec/specs/bulk-signing-field-builder/spec.md#requirement-bulk-send-validates-first-then-creates-isolated-ordinary-requests-req-ddbsf-002
	 */
	public function listFor(string $userId, bool $isAdmin): array;
}//end interface
