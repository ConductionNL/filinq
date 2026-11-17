<?php

/**
 * Contract of BridgeApi
 *
 * The public surface of the BridgeApi service, so callers in other
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
 * Contract of BridgeApi.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface BridgeApiInterface {
	/**
	 * Every source with its health.
	 *
	 * @return array<int, array<string, mixed>>
	 *
	 * @spec openspec/changes/zgw-document-bridge
	 */
	public function sources(): array;

	/**
	 * The staged documents the caller may read, of one source or all.
	 *
	 * @param string $sourceId The source uuid, or '' for every source.
	 *
	 * @return array<int, array<string, mixed>>
	 *
	 * @spec openspec/changes/zgw-document-bridge
	 */
	public function stagedDocuments(string $sourceId = ''): array;

	/**
	 * Release a processed document for write-back.
	 *
	 * @param string $id The external document's uuid.
	 *
	 * @return array<string, mixed> The saved document.
	 *
	 * @spec openspec/changes/zgw-document-bridge
	 */
	public function release(string $id): array;

	/**
	 * Retry a failed write-back.
	 *
	 * @param string $id The external document's uuid.
	 *
	 * @return array<string, mixed> The saved document.
	 *
	 * @spec openspec/changes/zgw-document-bridge
	 */
	public function retry(string $id): array;

	/**
	 * Attach staged documents to a dossier.
	 *
	 * @param string            $dossierUuid The dossier object's uuid.
	 * @param array<int, mixed> $documentIds The external documents' uuids.
	 *
	 * @return array<int, array<string, mixed>> The attached documents.
	 *
	 * @spec openspec/changes/zgw-document-bridge
	 */
	public function attach(string $dossierUuid, array $documentIds): array;

	/**
	 * The vendor per file id, for the caller's own files that came in through the bridge.
	 *
	 * @param array<int, mixed> $fileIds The file ids of a listing page.
	 *
	 * @return array<int|string, string>
	 *
	 * @spec openspec/changes/zgw-document-bridge/specs/zgw-document-bridge/spec.md#requirement-source-badge-on-externally-sourced-documents-req-ddzgw-009
	 */
	public function badges(array $fileIds): array;
}//end interface
