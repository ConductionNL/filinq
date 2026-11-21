<?php

/**
 * Bridge source badges
 *
 * Says, for a page of files, which ones came in from a case system and
 * from which vendor, so the listing can show "Zaaksysteem: {vendor}".
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/zgw-document-bridge/specs/zgw-document-bridge/spec.md#requirement-source-badge-on-externally-sourced-documents-req-ddzgw-009
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

/**
 * One batched lookup per listing page: the bridge records of the files the
 * caller can open, and the vendor of each record's source.
 *
 * @spec openspec/changes/zgw-document-bridge/specs/zgw-document-bridge/spec.md#requirement-source-badge-on-externally-sourced-documents-req-ddzgw-009
 */
class BridgeSourceBadges {

	/**
	 * The most files one lookup answers for, a listing page or a few.
	 *
	 * @var integer
	 */
	public const MAX_FILES = 200;

	/**
	 * Constructor.
	 *
	 * @param DocumentObjectServiceResolver $objectResolver OpenRegister's ObjectService.
	 * @param SessionUserFile               $files          The caller's own files.
	 *
	 * @spec openspec/changes/zgw-document-bridge
	 */
	public function __construct(
		private readonly DocumentObjectServiceResolver $objectResolver,
		private readonly SessionUserFile $files,
	) {
	}//end __construct()

	/**
	 * The vendor per file id, for the files that came in through the bridge.
	 *
	 * Only files the caller can open are looked up, so the answer never says
	 * anything about a file id the caller merely guessed. The bridge records
	 * themselves are read by the server (they are admin-only through the API):
	 * the caller learns only the vendor name of their own files. Two queries
	 * per call, whatever the number of files.
	 *
	 * @param array<int, mixed> $fileIds The listing's file ids.
	 *
	 * @return array<int, string> The vendor, keyed by file id; files without a bridge record are absent.
	 *
	 * @spec openspec/changes/zgw-document-bridge/specs/zgw-document-bridge/spec.md#requirement-source-badge-on-externally-sourced-documents-req-ddzgw-009
	 */
	public function forFiles(array $fileIds): array {
		$readable = [];
		foreach (array_slice(array_values(array_unique(array_map('intval', $fileIds))), 0, self::MAX_FILES) as $fileId) {
			if ($fileId > 0 && $this->files->find(fileId: $fileId) !== null) {
				$readable[] = $fileId;
			}
		}

		if ($readable === []) {
			return [];
		}

		$sourceByFile = [];
		foreach ($this->search(schema: BridgeService::SCHEMA_DOCUMENT, filters: ['stagedFileRef' => $readable]) as $document) {
			$fileId = (int)($document['stagedFileRef'] ?? 0);
			if (in_array($fileId, $readable, true) === true) {
				$sourceByFile[$fileId] = (string)($document['sourceId'] ?? '');
			}
		}

		if ($sourceByFile === []) {
			return [];
		}

		$vendors = [];
		foreach ($this->search(schema: BridgeService::SCHEMA_SOURCE, filters: []) as $source) {
			$vendors[(string)($source['uuid'] ?? ($source['id'] ?? ''))] = (string)($source['vendor'] ?? '');
		}

		$badges = [];
		foreach ($sourceByFile as $fileId => $sourceId) {
			$vendor = ($vendors[$sourceId] ?? '');
			if ($vendor !== '') {
				$badges[$fileId] = $vendor;
			}
		}

		return $badges;
	}//end forFiles()

	/**
	 * Search one bridge schema as the server.
	 *
	 * @param string               $schema  The schema slug.
	 * @param array<string, mixed> $filters The filters.
	 *
	 * @return array<int, array<string, mixed>> The rows as flat fields.
	 *
	 * @spec openspec/changes/zgw-document-bridge
	 */
	private function search(string $schema, array $filters): array {
		$results = $this->objectResolver->resolve()->searchObjectsBySlug(
			registerSlug: BridgeService::REGISTER,
			schemaSlug: $schema,
			filters: $filters,
			_rbac: false
		);
		if (is_array($results) === false) {
			return [];
		}

		$rows = [];
		foreach ($results as $result) {
			$rows[] = (new StoredRecordFields())->read(row: $result);
		}

		return $rows;
	}//end search()
}//end class
