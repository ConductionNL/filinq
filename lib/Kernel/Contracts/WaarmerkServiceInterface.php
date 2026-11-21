<?php

/**
 * Contract of WaarmerkService
 *
 * The public surface of the WaarmerkService service, so callers in other
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

use OCA\Filinq\Exception\WaarmerkRefusedException;
use OCA\Filinq\Kernel\SealedArtifact;

/**
 * Contract of WaarmerkService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface WaarmerkServiceInterface {
	/**
	 * Compose and sign a sealed PDF; nothing is stored yet.
	 *
	 * @param string $pdf The PDF bytes to seal.
	 * @param string $documentName The name the sealed file will have.
	 * @param string $userId The sealing user.
	 * @param string $sealType waarmerk, processing-certificate or signing-evidence.
	 * @param string|null $anonymizationLinkId The certified run, for a processing certificate.
	 *
	 * @return SealedArtifact
	 *
	 * @throws WaarmerkRefusedException 503 without a resolvable key, 422 with an expired certificate or a non-PDF.
	 *
	 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-a-processed-pdf-can-be-sealed-with-a-waarmerk-req-ddwmk-002
	 */
	public function prepare(
		string $pdf,
		string $documentName,
		string $userId,
		string $sealType = 'waarmerk',
		?string $anonymizationLinkId = null,
	): SealedArtifact;

	/**
	 * The seals a user may see: an admin sees all, anyone else their own.
	 *
	 * @param string $userId The caller.
	 * @param bool $isAdmin Whether the caller is an admin.
	 * @param int|null $documentFileId Only the seals of this sealed file.
	 *
	 * @return array<int, array<string, mixed>>
	 *
	 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-a-processed-pdf-can-be-sealed-with-a-waarmerk-req-ddwmk-002
	 */
	public function list(string $userId, bool $isAdmin, ?int $documentFileId = null): array;

	/**
	 * One seal, or null when it does not exist or is not the caller's (one answer for both).
	 *
	 * @param string $uuid The seal's uuid.
	 * @param string $userId The caller.
	 * @param bool $isAdmin Whether the caller is an admin.
	 *
	 * @return array<string, mixed>|null
	 *
	 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-a-processed-pdf-can-be-sealed-with-a-waarmerk-req-ddwmk-002
	 */
	public function show(string $uuid, string $userId, bool $isAdmin): ?array;

	/**
	 * Revoke a seal: admins or the sealer only, with a reason, irreversibly.
	 *
	 * @param string $uuid The seal's uuid.
	 * @param string $userId The caller.
	 * @param bool $isAdmin Whether the caller is an admin.
	 * @param string $reason Why it is revoked.
	 *
	 * @return array<string, mixed> The revoked seal.
	 *
	 * @throws WaarmerkRefusedException 404 unknown, 403 not admin or sealer, 400 no reason, 409 already revoked.
	 *
	 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-a-waarmerk-can-be-revoked-and-revocation-dominates-verification-req-ddwmk-005
	 */
	public function revoke(string $uuid, string $userId, bool $isAdmin, string $reason): array;
}
