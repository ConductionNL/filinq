<?php

/**
 * LibreSign client seam
 *
 * The four calls the LibreSign provider makes, in one place, so the provider
 * can be tested against a fake and the HTTP details live in one class.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Signing
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

namespace OCA\Filinq\Service\Signing;

use RuntimeException;

/**
 * What Filinq asks of LibreSign.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Signing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/libresign-signing-provider/spec.md
 */
interface LibreSignClient {
	/**
	 * Create a signature request (POST request-signature).
	 *
	 * @param array<string, mixed>             $file    LibreSign's NewFile: nodeId or path
	 * @param string                           $name    The document name
	 * @param array<int, array<string, mixed>> $signers LibreSign's NewSigner entries
	 *
	 * @return array<string, mixed> The `ocs.data` of the answer (uuid, status, nodeId, ...).
	 *
	 * @throws RuntimeException When LibreSign cannot be reached or refuses.
	 *
	 * @spec openspec/changes/archive/2026-09-29-libresign-signing-provider/tasks.md#task-1.3
	 */
	public function requestSignature(array $file, string $name, array $signers): array;

	/**
	 * Read a request's state (GET file/validate/uuid/{uuid}).
	 *
	 * @param string $uuid The LibreSign file uuid
	 *
	 * @return array<string, mixed> The `ocs.data` of the answer (status, nodeId, signers, ...).
	 *
	 * @throws RuntimeException When LibreSign cannot be reached or refuses.
	 *
	 * @spec openspec/changes/archive/2026-09-29-libresign-signing-provider/tasks.md#task-1.3
	 */
	public function validate(string $uuid): array;

	/**
	 * Fetch the signed PDF (GET /apps/libresign/p/pdf/{uuid}).
	 *
	 * @param string $uuid The LibreSign file uuid
	 *
	 * @return string The bytes LibreSign serves.
	 *
	 * @throws RuntimeException When LibreSign cannot be reached or refuses.
	 *
	 * @spec openspec/changes/archive/2026-09-29-libresign-signing-provider/tasks.md#task-1.3
	 */
	public function downloadSigned(string $uuid): string;

	/**
	 * Withdraw every sign request on a file (DELETE sign/file_id/{fileId}).
	 *
	 * @param int $nodeId The Nextcloud file id LibreSign keeps the request under
	 *
	 * @return void
	 *
	 * @throws RuntimeException When LibreSign cannot be reached or refuses.
	 *
	 * @spec openspec/changes/archive/2026-09-29-libresign-signing-provider/tasks.md#task-1.3
	 */
	public function deleteRequest(int $nodeId): void;
}//end interface
