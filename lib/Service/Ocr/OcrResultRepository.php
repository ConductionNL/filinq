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

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\IntakeRepository;
use RuntimeException;
use Throwable;

/**
 * The `ocrResult` rows, keyed by file id.
 */
class OcrResultRepository {

	public const SCHEMA = 'ocrResult';

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
		try {
			// Slugs go through searchObjectsBySlug: searchObjects answers a
			// slug with zero rows and no error.
			$results = $this->objectResolver->resolve()->searchObjectsBySlug(
				registerSlug: IntakeRepository::REGISTER,
				schemaSlug: self::SCHEMA,
				filters: ['fileId' => $fileId]
			);
		} catch (Throwable $e) {
			throw new RuntimeException(
				message: 'The OCR result could not be read: ' . $e->getMessage(),
				code: 0,
				previous: $e
			);
		}

		foreach ((array) $results as $result) {
			$row = $this->normalise(row: $result);
			// The filter is the search's; the match is ours, so a search that
			// ignored the filter still answers for this file only.
			if ((int) ($row['fileId'] ?? 0) === $fileId) {
				return $row;
			}
		}

		return null;

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
		unset($result['uuid']);
		$arguments = [
			'object' => $result,
			'register' => IntakeRepository::REGISTER,
			'schema' => self::SCHEMA,
		];

		$existing = $this->findForFile(fileId: (int) $result['fileId']);
		if ($existing !== null && ($existing['uuid'] ?? '') !== '') {
			$arguments['uuid'] = $existing['uuid'];
		}

		try {
			$stored = $this->objectResolver->resolve()->saveObject(...$arguments);
		} catch (Throwable $e) {
			throw new RuntimeException(
				message: 'Could not store the OCR result: ' . $e->getMessage(),
				code: 0,
				previous: $e
			);
		}

		return $this->normalise(row: $stored);

	}//end saveForFile()

	/**
	 * Flatten an OpenRegister row to its fields plus uuid.
	 *
	 * @param mixed $row An ObjectEntity or array.
	 *
	 * @return array<string, mixed> The fields.
	 */
	private function normalise(mixed $row): array {
		$data = $row;
		if (is_object($row) === true && method_exists($row, 'jsonSerialize') === true) {
			$data = $row->jsonSerialize();
		}

		if (is_array($data) === false) {
			return [];
		}

		$fields = $data;
		if (isset($data['object']) === true && is_array($data['object']) === true) {
			$fields = $data['object'];
		}

		unset($fields['@self']);
		$fields['uuid'] = (string) ($fields['uuid'] ?? ($data['uuid'] ?? ($data['@self']['id'] ?? ($data['id'] ?? ''))));

		return $fields;

	}//end normalise()
}//end class
