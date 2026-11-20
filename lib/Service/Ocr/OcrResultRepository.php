<?php

/**
 * OCR Result Repository
 *
 * Reads and writes the `ocrResult` rows in the `filinq` register: one row per
 * file, updated by every new run. The row never holds the recognised text.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Ocr
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-ocr-trigger-surface/tasks.md#task-1.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Ocr;

use OCA\Filinq\Kernel\Contracts\OcrResultRepositoryInterface;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\KeepsRowsPerFile;
use OCA\Filinq\Service\ReadsStoredObjectRows;
use RuntimeException;

/**
 * The `ocrResult` rows, keyed by file id.
 */
class OcrResultRepository implements OcrResultRepositoryInterface {
	use ReadsStoredObjectRows;
	use KeepsRowsPerFile;

	/**
	 * Constructor.
	 *
	 * @param DocumentObjectServiceResolver $objectResolver Resolves OpenRegister's ObjectService.
	 */
	public function __construct(
		private readonly DocumentObjectServiceResolver $objectResolver,
	) {

	}//end __construct()

	/**
	 * The result of the last run on a file, or null when it never ran.
	 *
	 * @param int $fileId The Nextcloud file id.
	 *
	 * @return array<string, mixed>|null The row, with its uuid.
	 *
	 * @throws RuntimeException When OpenRegister cannot be read.
	 *
	 * @spec openspec/changes/archive/2026-09-29-ocr-trigger-surface/tasks.md#task-2.4
	 */
	public function findForFile(int $fileId): ?array {
		return ($this->rowsForFile(schema: self::SCHEMA, fileId: $fileId, failure: 'The OCR result could not be read: ')[0] ?? null);

	}//end findForFile()

	/**
	 * Store a run's result, replacing the file's previous one.
	 *
	 * @param array<string, mixed> $result The row: fileId, confidence, languages, dpi,
	 *                                     textLength, ocrProcessedAt, triggeredBy, engineVersion.
	 *
	 * @return array<string, mixed> The stored row, with its uuid.
	 *
	 * @throws RuntimeException When OpenRegister refuses the write.
	 *
	 * @spec openspec/changes/archive/2026-09-29-ocr-trigger-surface/tasks.md#task-2.1
	 */
	public function saveForFile(array $result): array {
		return $this->saveRowForFile(
			schema: self::SCHEMA,
			row: $result,
			existing: $this->findForFile(fileId: (int) $result['fileId']),
			failure: 'Could not store the OCR result: '
		);

	}//end saveForFile()
}//end class
