<?php

/**
 * Bridge API
 *
 * The one entry the bridge endpoints call: sources, staged documents, dossier
 * pick-up, release and retry, and the source badges of a listing page.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/zgw-document-bridge
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCA\Filinq\Kernel\Contracts\BridgeApiInterface;

/**
 * Hands each bridge request to the service that answers it.
 *
 * @spec openspec/changes/zgw-document-bridge
 */
class BridgeApi implements BridgeApiInterface {

	/**
	 * Constructor.
	 *
	 * @param BridgeService           $bridge The bridge register.
	 * @param BridgeDossierPickUp     $pickUp Attaches staged documents to a dossier.
	 * @param BridgeSourceBadges|null $badges The source badge per file, for a listing page.
	 *
	 * @spec openspec/changes/zgw-document-bridge
	 */
	public function __construct(
		private readonly BridgeService $bridge,
		private readonly BridgeDossierPickUp $pickUp,
		private readonly ?BridgeSourceBadges $badges = null,
	) {
	}//end __construct()

	/**
	 * Every source with its health.
	 *
	 * @return array<int, array<string, mixed>>
	 *
	 * @spec openspec/changes/zgw-document-bridge
	 */
	public function sources(): array {
		return $this->bridge->sources();
	}//end sources()

	/**
	 * The staged documents the caller may read, of one source or all.
	 *
	 * @param string $sourceId The source uuid, or '' for every source.
	 *
	 * @return array<int, array<string, mixed>>
	 *
	 * @spec openspec/changes/zgw-document-bridge
	 */
	public function stagedDocuments(string $sourceId = ''): array {
		return $this->bridge->stagedDocuments(sourceId: $sourceId);
	}//end stagedDocuments()

	/**
	 * Release a processed document for write-back.
	 *
	 * @param string $id The external document's uuid.
	 *
	 * @return array<string, mixed> The saved document.
	 *
	 * @spec openspec/changes/zgw-document-bridge
	 */
	public function release(string $id): array {
		return $this->bridge->release(id: $id);
	}//end release()

	/**
	 * Retry a failed write-back.
	 *
	 * @param string $id The external document's uuid.
	 *
	 * @return array<string, mixed> The saved document.
	 *
	 * @spec openspec/changes/zgw-document-bridge
	 */
	public function retry(string $id): array {
		return $this->bridge->retry(id: $id);
	}//end retry()

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
	public function attach(string $dossierUuid, array $documentIds): array {
		return $this->pickUp->attach(dossierUuid: $dossierUuid, documentIds: $documentIds);
	}//end attach()

	/**
	 * The vendor per file id, for the caller's own files that came in through the bridge.
	 *
	 * @param array<int, mixed> $fileIds The file ids of a listing page.
	 *
	 * @return array<int|string, string>
	 *
	 * @spec openspec/changes/zgw-document-bridge/specs/zgw-document-bridge/spec.md#requirement-source-badge-on-externally-sourced-documents-req-ddzgw-009
	 */
	public function badges(array $fileIds): array {
		return $this->badges?->forFiles(fileIds: $fileIds) ?? [];
	}//end badges()
}//end class
