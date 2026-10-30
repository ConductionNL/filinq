<?php

/**
 * Contract of AnonymizeRequestService
 *
 * The public surface of the AnonymizeRequestService service, so callers in other
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

use OCA\Filinq\Exception\ConversionFailedException;
use OCA\Filinq\Kernel\AnonymizeCall;

/**
 * Contract of AnonymizeRequestService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface AnonymizeRequestServiceInterface {
	/**
	 * Require an authenticated session.
	 *
	 * @return array{status: int, body: array<string, mixed>}|null The 401 payload, or null when
	 *                                                             a user is signed in.
	 *
	 * @spec openspec/specs/anonymization/spec.md
	 */
	public function requireAuthenticated(): ?array;

	/**
	 * Return the acting user's UID, or an empty string when unauthenticated.
	 *
	 * @return string The UID of the signed-in user.
	 *
	 * @spec openspec/specs/anonymization/spec.md
	 */
	public function currentUserId(): string;

	/**
	 * Verify the current user has access to the given file ID.
	 *
	 * Resolves the file via the user's own file tree so that an authenticated
	 * user cannot operate on files they do not own (security finding C3 —
	 * file IDOR). Returns 404 on failure so callers cannot probe for existence.
	 *
	 * @param int $fileId The Nextcloud file ID to check.
	 *
	 * @return array{status: int, body: array<string, mixed>}|null Null when access is granted,
	 *                                                             the error payload otherwise.
	 *
	 * @spec openspec/specs/anonymization/spec.md
	 */
	public function verifyFileAccess(int $fileId): ?array;

	/**
	 * Validate the anonymize request body.
	 *
	 * @param array<string, mixed> $params Request parameters.
	 *
	 * @return array{error: array{status: int, body: array<string, mixed>}|null,
	 *               request: array<string, mixed>|null} The first validation failure, or the
	 *                                                   normalised request under `request`.
	 *
	 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-1
	 */
	public function validateBody(array $params): array;

	/**
	 * Reject unredacted entities that match a publication-prohibition rule.
	 *
	 * @param array<int, array<string, mixed>> $unredactedEntities Entities to publish unredacted.
	 *
	 * @return array{status: int, body: array<string, mixed>}|null The 422 payload, or null when clear.
	 *
	 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-2
	 */
	public function checkUnredactedProhibitions(array $unredactedEntities): ?array;

	/**
	 * Run the defence-in-depth backstop and perform the anonymisation.
	 *
	 * `ConversionFailedException` propagates to the caller by design — the
	 * controller owns the documented 422 conversion body.
	 *
	 * @param AnonymizeCall $call   The file, parameters, body and output format.
	 * @param string        $userId UID of the acting user (for override audit entries).
	 *
	 * @return array{status: int, body: array<string, mixed>} The response payload.
	 *
	 * @throws \OCA\Filinq\Exception\ConversionFailedException When the PDF cascade is exhausted.
	 *
	 * @spec openspec/specs/anonymization/spec.md
	 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-4
	 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-11
	 * @spec openspec/changes/archive/2026-09-29-anonymisation-fails-closed-without-a-detector/tasks.md#task-2
	 */
	public function executeAnonymize(AnonymizeCall $call, string $userId): array;

	/**
	 * Record a per-entity skip/include decision, guarded by the prohibition policy.
	 *
	 * @param int $relationId The EntityRelation id.
	 * @param array<string, mixed> $params Request parameters.
	 *
	 * @return array{status: int, body: array<string, mixed>} The response payload.
	 *
	 * @spec openspec/changes/anonymisation-prohibition-gate/tasks.md#task-6
	 */
	public function applyRelationDecision(int $relationId, array $params): array;
}//end interface
