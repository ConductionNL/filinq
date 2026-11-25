<?php

/**
 * KeepsRowsPerFile — reads and writes the OpenRegister rows a repository keeps per file.
 *
 * Several repositories keep one or more rows per Nextcloud file in the intake
 * register: find the rows whose `fileId` matches, and save a row, updating an
 * existing one by uuid. The class that uses this trait also uses
 * ReadsStoredObjectRows and holds a DocumentObjectServiceResolver as
 * `$objectResolver`.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#requirement-a-persisted-accessibility-conformance-report-is-stored-per-document-and-re-runnable-req-ddpum-002
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCA\OpenRegister\Kernel\Contracts\Value\ObjectFindTarget;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectSaveOptions;
use RuntimeException;
use Throwable;

trait KeepsRowsPerFile {
	/**
	 * The stored rows of a schema that belong to one file, in search order.
	 *
	 * The filter is the search's; the match is ours, so a search that ignored
	 * the filter still answers for this file only.
	 *
	 * @param string $schema  The schema slug in the intake register.
	 * @param int    $fileId  The Nextcloud file id.
	 * @param string $failure The message prefix when the register cannot be read.
	 *
	 * @return array<int, array<string, mixed>> The rows, each with its uuid.
	 *
	 * @throws RuntimeException When the register cannot be read.
	 *
	 * @spec openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#requirement-a-persisted-accessibility-conformance-report-is-stored-per-document-and-re-runnable-req-ddpum-002
	 */
	private function rowsForFile(string $schema, int $fileId, string $failure): array {
		try {
			// Slugs go through searchObjectsBySlug: searchObjects answers a
			// slug with zero rows and no error.
			$results = (new SlugSearch(objects: $this->objectResolver->resolve()))->search(
				register: IntakeRepository::REGISTER,
				schema: $schema,
				filters: ['fileId' => $fileId]
			);
		} catch (Throwable $e) {
			throw new RuntimeException(message: $failure . $e->getMessage(), code: 0, previous: $e);
		}

		$rows = [];
		foreach ((array) $results as $result) {
			$row = $this->storedFields(row: $result);
			if ((int) ($row['fileId'] ?? 0) === $fileId) {
				$rows[] = $row;
			}
		}

		return $rows;

	}//end rowsForFile()

	/**
	 * Store a row of a schema, updating the stored one when it has a uuid.
	 *
	 * @param string                    $schema   The schema slug in the intake register.
	 * @param array<string, mixed>      $row      The row, without a uuid.
	 * @param array<string, mixed>|null $existing The row stored before, or null.
	 * @param string                    $failure  The message prefix when the register refuses it.
	 *
	 * @return array<string, mixed> The stored row with its uuid.
	 *
	 * @throws RuntimeException When the register refuses it.
	 *
	 * @spec openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#requirement-a-persisted-accessibility-conformance-report-is-stored-per-document-and-re-runnable-req-ddpum-002
	 */
	private function saveRowForFile(string $schema, array $row, ?array $existing, string $failure): array {
		unset($row['uuid']);
		$uuid = null;
		if ($existing !== null && ($existing['uuid'] ?? '') !== '') {
			$uuid = (string) $existing['uuid'];
		}

		try {
			$stored = $this->objectResolver->resolve()->saveObject(
				object: $row,
				options: new ObjectSaveOptions(
					target: new ObjectFindTarget(register: IntakeRepository::REGISTER, schema: $schema),
					uuid: $uuid
				)
			);
		} catch (Throwable $e) {
			throw new RuntimeException(message: $failure . $e->getMessage(), code: 0, previous: $e);
		}

		return $this->storedFields(row: $stored);

	}//end saveRowForFile()
}//end trait
