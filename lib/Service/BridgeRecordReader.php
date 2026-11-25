<?php

/**
 * Bridge Record Reader
 *
 * Reads the bridge's records as the server, for the callers that show a user
 * one derived fact about their own document (the vendor badge, the
 * zaaksysteem's destruction date) while the records stay admin-only.
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
 * One read of one bridge schema, without the caller's rights, as flat fields.
 *
 * Every caller returns a single derived value, never a record, and only for
 * a document the caller works on; see docs/authorization-decisions.md.
 *
 * @spec openspec/changes/zgw-document-bridge/specs/zgw-document-bridge/spec.md#requirement-source-badge-on-externally-sourced-documents-req-ddzgw-009
 */
class BridgeRecordReader {

	/**
	 * Constructor.
	 *
	 * @param DocumentObjectServiceResolver $objectResolver OpenRegister's ObjectService.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly DocumentObjectServiceResolver $objectResolver,
	) {
	}//end __construct()

	/**
	 * The rows of one bridge schema that match the filters.
	 *
	 * @param string               $schema  The schema slug (BridgeService::SCHEMA_*).
	 * @param array<string, mixed> $filters Equality filters.
	 *
	 * @return array<int, array<string, mixed>> The rows as flat fields.
	 *
	 * @spec openspec/changes/zgw-document-bridge/specs/zgw-document-bridge/spec.md#requirement-source-badge-on-externally-sourced-documents-req-ddzgw-009
	 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-destruction-dates-propagate-to-the-publication-pipeline-with-source-precedence-req-ddare-007
	 */
	public function rows(string $schema, array $filters): array {
		$results = (new SlugSearch(objects: $this->objectResolver->resolve(), _rbac: false))->search(
			register: BridgeService::REGISTER,
			schema: $schema,
			filters: $filters
		);
		if (is_array($results) === false) {
			return [];
		}

		$fields = new StoredRecordFields();

		return array_values(array_map(static fn (mixed $result): array => $fields->read(row: $result), $results));
	}//end rows()
}//end class
