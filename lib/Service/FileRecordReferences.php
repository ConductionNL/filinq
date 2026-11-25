<?php

/**
 * File Record References
 *
 * The records other Filinq processes keep about one file, as the status
 * tool reports them (mcp-generation-tools REQ-DDMGT-002): the generation
 * records that stored their document as this file, the signing requests on
 * it, and the validation verdict a generation record carries. References and
 * statuses only: never a name, path, signer or finding text.
 *
 * Read under the caller's own rights, so a record the caller may not read is
 * not referenced.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/mcp-generation-tools/tasks.md#task-1.2
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Reads the generation and signing references and the validation verdict of a file.
 *
 * @spec openspec/changes/mcp-generation-tools/tasks.md#task-1.2
 */
class FileRecordReferences {
	use ReadsStoredObjectRows;

	/**
	 * The schema of a generation record.
	 *
	 * @var string
	 */
	public const GENERATED = 'generatedDocument';

	/**
	 * The schema of a signing request.
	 *
	 * @var string
	 */
	public const SIGNING = 'signingRequest';

	/**
	 * Constructor.
	 *
	 * @param DocumentObjectServiceResolver $objectResolver Resolves OpenRegister's ObjectService.
	 * @param LoggerInterface               $logger         Logger.
	 */
	public function __construct(
		private readonly DocumentObjectServiceResolver $objectResolver,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * The references and the validation verdict of one file.
	 *
	 * @param int $fileId The Nextcloud file id.
	 *
	 * @return array{validationStatus: string|null, generationRecords: list<array{id: string, status: string|null}>,
	 *     signingRecords: list<array{id: string, status: string|null}>}
	 *
	 * @spec openspec/changes/mcp-generation-tools/tasks.md#task-1.2
	 */
	public function forFile(int $fileId): array {
		$generated = $this->rows(schema: self::GENERATED, field: 'fileId', fileId: $fileId);
		usort(
			$generated,
			static fn (array $left, array $right): int => strcmp((string) ($right['generatedAt'] ?? ''), (string) ($left['generatedAt'] ?? ''))
		);

		$verdict = null;
		foreach ($generated as $row) {
			$status = ($row['validationStatus'] ?? null);
			if (is_string($status) === true && $status !== '') {
				$verdict = $status;
				break;
			}
		}

		return [
			'validationStatus' => $verdict,
			'generationRecords' => $this->references(rows: $generated),
			'signingRecords' => $this->references(rows: $this->rows(schema: self::SIGNING, field: 'documentFileId', fileId: $fileId)),
		];

	}//end forFile()

	/**
	 * The rows of a schema that point at the file; none when the store cannot be read.
	 *
	 * @param string $schema The schema slug.
	 * @param string $field  The field holding the file id.
	 * @param int    $fileId The file.
	 *
	 * @return list<array<string, mixed>>
	 */
	private function rows(string $schema, string $field, int $fileId): array {
		try {
			// Slugs go through searchObjectsBySlug: searchObjects answers a
			// slug with zero rows and no error.
			$results = (new SlugSearch(objects: $this->objectResolver->resolve()))->search(
				register: IntakeRepository::REGISTER,
				schema: $schema,
				filters: [$field => $fileId]
			);
		} catch (Throwable $e) {
			$this->logger->warning('The records of a file could not be read', ['schema' => $schema, 'fileId' => $fileId, 'exception' => $e->getMessage()]);
			return [];
		}

		$rows = [];
		foreach ((array) $results as $result) {
			$row = $this->storedFields(row: $result);
			// The filter is the search's; the match is ours, so a search that
			// ignored the filter still answers for this file only.
			if ((string) ($row[$field] ?? '') === (string) $fileId) {
				$rows[] = $row;
			}
		}

		return $rows;

	}//end rows()

	/**
	 * The id and status of each row.
	 *
	 * @param list<array<string, mixed>> $rows The rows.
	 *
	 * @return list<array{id: string, status: string|null}>
	 */
	private function references(array $rows): array {
		$references = [];
		foreach ($rows as $row) {
			$status = ($row['status'] ?? null);
			if (is_string($status) === false) {
				$status = null;
			}

			$references[] = ['id' => (string) ($row['uuid'] ?? ''), 'status' => $status];
		}

		return $references;

	}//end references()
}//end class
