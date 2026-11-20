<?php

/**
 * Contract of SigningEvidenceService
 *
 * The public surface of the SigningEvidenceService service, so callers in other
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

/**
 * Contract of SigningEvidenceService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface SigningEvidenceServiceInterface {
	/**
	 * The sealed evidence PDF, for download.
	 *
	 * @param string $requestId The signing request id.
	 * @param string $callerScope The caller's uid, or '' for an admin (unscoped).
	 * @param string $userId The caller.
	 *
	 * @return array{pdf: string, fileName: string, waarmerk: array<string, mixed>}
	 *
	 * @throws WaarmerkRefusedException 404 unknown or not the caller's, 409 still open.
	 *
	 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-a-completed-signing-transaction-yields-one-sealed-evidence-document-req-ddwmk-006
	 */
	public function download(string $requestId, string $callerScope, string $userId): array;

	/**
	 * Write the sealed evidence PDF next to the signed document ("Kopie naar dossier").
	 *
	 * @param string $requestId The signing request id.
	 * @param string $callerScope The caller's uid, or '' for an admin (unscoped).
	 * @param string $userId The caller.
	 *
	 * @return array{waarmerk: array<string, mixed>, fileId: int, fileName: string}
	 *
	 * @throws WaarmerkRefusedException 404 unknown or the document is not readable, 409 still open.
	 *
	 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-a-completed-signing-transaction-yields-one-sealed-evidence-document-req-ddwmk-006
	 */
	public function copyToDossier(string $requestId, string $callerScope, string $userId): array;
}
