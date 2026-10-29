<?php

/**
 * Sync Bulk Generation
 *
 * Generates a small bulk batch inline, one document per object.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
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

namespace OCA\Filinq\Service;

use Exception;

/**
 * One synchronous bulk run of DocumentService::generateBulk().
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/document-output-destinations-and-bulk-retention/specs/document-creatie-sjablonen/spec.md#req-ddob-007
 */
class SyncBulkGeneration {

	/**
	 * @param DocumentService         $documents      Generates each document
	 * @param GeneratedDocumentLogger $documentLogger Writes the failed audit entries
	 */
	public function __construct(
		private readonly DocumentService $documents,
		private readonly GeneratedDocumentLogger $documentLogger,
	) {
	}//end __construct()

	/**
	 * Process a bulk generation request synchronously.
	 *
	 * Honours `options.output.mode` per object exactly as single-generate
	 * does (REQ-DDOB-007): 'return' (default) keeps today's inline
	 * `content`; 'files' stores each object's output and returns
	 * `{fileId, path, name, size}` inline instead of `content`; 'both'
	 * returns both.
	 *
	 * @param string $templateId The template UUID
	 * @param array $objectIds Array of object UUIDs
	 * @param array $options Generation options (register, schema, format, output, ...)
	 *
	 * @return array{results: array, total: int, completed: int, errors: int}
	 *
	 * @spec openspec/changes/document-output-destinations-and-bulk-retention/specs/document-creatie-sjablonen/spec.md#req-ddob-007
	 */
	public function run(
		string $templateId,
		array $objectIds,
		array $options,
	): array {
		$results = [];
		foreach ($objectIds as $objectId) {
			$results[] = $this->generateBulkItem(
				bulk: ['templateId' => $templateId, 'options' => $options],
				objectId: $objectId
			);
		}

		$errorCount = count(
			array_filter(
				$results,
				static function (array $r): bool {
					return $r['status'] === 'error';
				}
			)
		);

		return [
			'results' => $results,
			'total' => count($objectIds),
			'completed' => (count($objectIds) - $errorCount),
			'errors' => $errorCount,
		];

	}//end run()

	/**
	 * Generate one object of a sync bulk run; a failure becomes an error item
	 * and a failed audit entry instead of aborting the run.
	 *
	 * @param array<string, mixed> $bulk     The run: templateId and options (register, schema, format, userId, output).
	 * @param mixed                $objectId The object UUID.
	 *
	 * @return array<string, mixed> The success or error item.
	 *
	 * @spec openspec/changes/document-output-destinations-and-bulk-retention/specs/document-creatie-sjablonen/spec.md#req-ddob-007
	 */
	private function generateBulkItem(array $bulk, mixed $objectId): array {
		$options  = $bulk['options'];
		$dataRefs = [['register' => ($options['register'] ?? ''), 'schema' => ($options['schema'] ?? ''), 'id' => $objectId]];

		try {
			$result = $this->documents->generateDocument(templateId: $bulk['templateId'], dataRefs: $dataRefs, options: $options);
		} catch (Exception $e) {
			$this->documentLogger->logFailure(
				attempt: [
					'templateId' => $bulk['templateId'],
					'dataRefs' => $dataRefs,
					'format' => ($options['format'] ?? DocumentService::DEFAULT_FORMAT),
					'userId' => (string)($options['userId'] ?? ''),
				],
				errorMessage: $e->getMessage()
			);

			return ['objectId' => $objectId, 'status' => 'error', 'error' => $e->getMessage()];
		}

		$item = ['objectId' => $objectId, 'status' => 'success', 'warnings' => $result['warnings']];
		$mode = $result['output']['mode'] ?? 'return';
		if ($mode === 'return' || $mode === 'both') {
			$item['content'] = $result['content'];
		}

		if ($mode === 'files' || $mode === 'both') {
			$item += array_intersect_key($result['output'], array_flip(['fileId', 'path', 'name', 'size']));
		}

		return $item;
	}//end generateBulkItem()
}//end class
