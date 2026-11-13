<?php

/**
 * Filinq IntegrityScanService
 *
 * Looks across filinq's records for files that went wrong behind its back
 * (whole-collection-integrity-scan REQ-DIS-01): a record whose file id no
 * longer resolves is `missing`, a final document whose file no longer matches
 * its frozen checksum is `changed`, and an anonymised copy whose source is
 * gone is `orphaned`. It only reads: no file and no record is changed, except
 * the `integrityScan` object that stores the run.
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
 * @spec openspec/changes/whole-collection-integrity-scan/tasks.md#task-1-the-scan-service-finds-the-three-kinds
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use DateTimeImmutable;
use DateTimeInterface;
use OCP\Files\IRootFolder;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Scan the collection for missing, changed and orphaned files.
 *
 * @spec openspec/changes/whole-collection-integrity-scan/tasks.md#task-1-the-scan-service-finds-the-three-kinds
 */
class IntegrityScanService {
	use ReadsStoredObjectRows;

	/**
	 * The register every filinq schema lives in.
	 */
	private const REGISTER = 'filinq';

	/**
	 * The schema a run is stored in.
	 */
	public const SCHEMA = 'integrityScan';

	/**
	 * The three kinds of finding.
	 */
	public const KINDS = ['missing', 'changed', 'orphaned'];

	/**
	 * Constructor.
	 *
	 * @param DocumentObjectServiceResolver $objects        Resolver for OpenRegister's ObjectService.
	 * @param IRootFolder                   $rootFolder     Resolves file ids across all storage.
	 * @param FinalDocumentService          $finalDocuments Compares a final document with its frozen checksum.
	 * @param LoggerInterface               $logger         Records what could not be read.
	 */
	public function __construct(
		private readonly DocumentObjectServiceResolver $objects,
		private readonly IRootFolder $rootFolder,
		private readonly FinalDocumentService $finalDocuments,
		private readonly LoggerInterface $logger,
	) {
	}//end __construct()

	/**
	 * Scan every dossier, final document and anonymisation link, and store the run.
	 *
	 * @param string $trigger What started it: nightly, command or admin.
	 *
	 * @return array<string, mixed> The stored run: times, `checked`, a count per kind and `findings`.
	 *
	 * @spec openspec/changes/whole-collection-integrity-scan/tasks.md#task-1-the-scan-service-finds-the-three-kinds
	 */
	public function run(string $trigger): array {
		$startedAt = (new DateTimeImmutable())->format(DateTimeInterface::ATOM);
		$checked   = 0;
		$findings  = array_merge(
			$this->dossierFindings(checked: $checked),
			$this->finalDocumentFindings(checked: $checked),
			$this->anonymizationFindings(checked: $checked)
		);

		$run = [
			'trigger' => $trigger,
			'startedAt' => $startedAt,
			'finishedAt' => (new DateTimeImmutable())->format(DateTimeInterface::ATOM),
			'checked' => $checked,
		];
		foreach (self::KINDS as $kind) {
			$run[$kind] = count(array_filter($findings, static fn (array $finding): bool => $finding['kind'] === $kind));
		}

		$run['findings'] = $findings;
		$this->objects->resolve()->saveObject(object: $run, register: self::REGISTER, schema: self::SCHEMA, _rbac: false);

		return $run;
	}//end run()

	/**
	 * A dossier listing a file that does not resolve.
	 *
	 * @param int $checked Counts the records looked at.
	 *
	 * @return array<int, array<string, mixed>> The findings.
	 */
	private function dossierFindings(int &$checked): array {
		$findings = [];
		foreach ($this->rows(schema: 'dossier') as $dossier) {
			$checked++;
			foreach ((array)($dossier['documents'] ?? []) as $reference) {
				$fileId = (int)$reference;
				if ($fileId > 0 && $this->resolves(fileId: $fileId) === false) {
					$findings[] = $this->finding(kind: 'missing', schema: 'dossier', record: $dossier, fileId: $fileId);
				}
			}
		}

		return $findings;
	}//end dossierFindings()

	/**
	 * A final document whose file is gone or no longer matches its checksum.
	 *
	 * @param int $checked Counts the records looked at.
	 *
	 * @return array<int, array<string, mixed>> The findings.
	 */
	private function finalDocumentFindings(int &$checked): array {
		$findings = [];
		foreach ($this->rows(schema: 'documentVersion') as $version) {
			if ($this->finalDocuments->isFinal(record: $version) === false) {
				continue;
			}

			$checked++;
			$fileId = (int)($version['fileId'] ?? 0);
			if ($this->resolves(fileId: $fileId) === false) {
				$findings[] = $this->finding(kind: 'missing', schema: 'documentVersion', record: $version, fileId: $fileId);
				continue;
			}

			if ($this->matchesChecksum(fileId: $fileId) === false) {
				$findings[] = $this->finding(kind: 'changed', schema: 'documentVersion', record: $version, fileId: $fileId);
			}
		}

		return $findings;
	}//end finalDocumentFindings()

	/**
	 * An anonymised copy that is gone, or whose source is gone.
	 *
	 * @param int $checked Counts the records looked at.
	 *
	 * @return array<int, array<string, mixed>> The findings.
	 */
	private function anonymizationFindings(int &$checked): array {
		$findings = [];
		foreach ($this->rows(schema: 'anonymizationLink') as $link) {
			$checked++;
			$copy = (int)($link['anonymizedFileId'] ?? 0);
			if ($this->resolves(fileId: $copy) === false) {
				$findings[] = $this->finding(kind: 'missing', schema: 'anonymizationLink', record: $link, fileId: $copy);
				continue;
			}

			$source = (int)($link['sourceFileId'] ?? 0);
			if ($this->resolves(fileId: $source) === false) {
				$findings[] = $this->finding(kind: 'orphaned', schema: 'anonymizationLink', record: $link, fileId: $copy);
			}
		}

		return $findings;
	}//end anonymizationFindings()

	/**
	 * Whether a final document's file still matches its frozen checksum. A
	 * check that throws counts as a mismatch: it cannot vouch for the file.
	 *
	 * @param int $fileId The file id.
	 *
	 * @return bool
	 */
	private function matchesChecksum(int $fileId): bool {
		try {
			return ($this->finalDocuments->verifyChecksum(fileId: $fileId)['matches'] ?? false) === true;
		} catch (Throwable $e) {
			$this->logger->warning('[IntegrityScan] the checksum could not be compared', ['fileId' => $fileId, 'error' => $e->getMessage()]);
			return false;
		}
	}//end matchesChecksum()

	/**
	 * Whether a file id resolves to a node anywhere on storage.
	 *
	 * @param int $fileId The file id.
	 *
	 * @return bool
	 */
	private function resolves(int $fileId): bool {
		if ($fileId <= 0) {
			return false;
		}

		try {
			return $this->rootFolder->getFirstNodeById($fileId) !== null;
		} catch (Throwable $e) {
			return false;
		}
	}//end resolves()

	/**
	 * Every stored row of a schema, as its payload with its uuid.
	 *
	 * @param string $schema The schema slug.
	 *
	 * @return array<int, array<string, mixed>> The rows.
	 */
	private function rows(string $schema): array {
		try {
			$found = $this->objects->resolve()->searchObjectsBySlug(
				registerSlug: self::REGISTER,
				schemaSlug: $schema,
				filters: [],
				_rbac: false,
				_multitenancy: false
			);
		} catch (Throwable $e) {
			$this->logger->warning('[IntegrityScan] a schema could not be read', ['schema' => $schema, 'error' => $e->getMessage()]);
			return [];
		}

		if (is_array($found) === false) {
			return [];
		}

		$rows = [];
		foreach ($found as $row) {
			$payload = $this->storedFields(row: $row);
			if ($payload !== []) {
				$rows[] = $payload;
			}
		}

		return $rows;
	}//end rows()

	/**
	 * One finding: its kind, the record it belongs to and the file id.
	 *
	 * @param string               $kind   missing, changed or orphaned.
	 * @param string               $schema The record's schema.
	 * @param array<string, mixed> $record The record.
	 * @param int                  $fileId The file id.
	 *
	 * @return array<string, mixed>
	 */
	private function finding(string $kind, string $schema, array $record, int $fileId): array {
		return [
			'kind' => $kind,
			'schema' => $schema,
			'record' => (string)($record['uuid'] ?? ($record['id'] ?? '')),
			'name' => (string)($record['name'] ?? ($record['documentName'] ?? ($record['anonymizedFileName'] ?? ''))),
			'fileId' => $fileId,
		];
	}//end finding()
}//end class
