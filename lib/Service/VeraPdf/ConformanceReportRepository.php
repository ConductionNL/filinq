<?php

/**
 * conformanceReport rows in OpenRegister, one per file and subject.
 *
 * A new check of the same file updates its row, so re-running never piles
 * up reports. The stored file and the PDF/A-3 copy a conversion made of it
 * are different bytes, so each keeps its own row.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\VeraPdf
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-verapdf-validation/tasks.md#task-2.3
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\VeraPdf;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\IntakeRepository;
use OCA\Filinq\Service\ReadsStoredObjectRows;
use RuntimeException;
use Throwable;

/**
 * Read and write conformance reports.
 */
class ConformanceReportRepository {
	use ReadsStoredObjectRows;

	public const SCHEMA = 'conformanceReport';

	public const SUBJECT_FILE = 'file';

	public const SUBJECT_CONVERSION = 'conversionOutput';

	/**
	 * Constructor.
	 *
	 * @param DocumentObjectServiceResolver $objectResolver Resolves OpenRegister's ObjectService.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly DocumentObjectServiceResolver $objectResolver,
	) {

	}//end __construct()

	/**
	 * The reports stored for a file, keyed by subject.
	 *
	 * @param int $fileId The file id.
	 *
	 * @return array<string, array<string, mixed>> The reports, keyed by subject.
	 *
	 * @throws RuntimeException When the register cannot be read.
	 *
	 * @spec openspec/changes/archive/2026-09-29-verapdf-validation/tasks.md#task-2.3
	 */
	public function findForFile(int $fileId): array {
		try {
			$results = $this->objectResolver->resolve()->searchObjectsBySlug(
				registerSlug: IntakeRepository::REGISTER,
				schemaSlug: self::SCHEMA,
				filters: ['fileId' => $fileId]
			);
		} catch (Throwable $e) {
			throw new RuntimeException(message: 'The conformance report could not be read: ' . $e->getMessage(), code: 0, previous: $e);
		}

		$reports = [];
		foreach ((array) $results as $result) {
			$row = $this->storedFields(row: $result);
			// The filter is the search's; the match is ours.
			if ((int) ($row['fileId'] ?? 0) === $fileId && isset($reports[(string) ($row['subject'] ?? '')]) === false) {
				$reports[(string) $row['subject']] = $row;
			}
		}

		return $reports;

	}//end findForFile()

	/**
	 * Store a report, updating the row for the same file and subject.
	 *
	 * @param array<string, mixed> $report The report, fileId and subject set.
	 *
	 * @return array<string, mixed> The stored report with its uuid.
	 *
	 * @throws RuntimeException When the register refuses it.
	 *
	 * @spec openspec/changes/archive/2026-09-29-verapdf-validation/tasks.md#task-2.3
	 */
	public function save(array $report): array {
		unset($report['uuid']);
		$arguments = [
			'object' => $report,
			'register' => IntakeRepository::REGISTER,
			'schema' => self::SCHEMA,
		];
		$existing = ($this->findForFile(fileId: (int) $report['fileId'])[(string) $report['subject']] ?? null);
		if ($existing !== null && ($existing['uuid'] ?? '') !== '') {
			$arguments['uuid'] = $existing['uuid'];
		}

		try {
			$stored = $this->objectResolver->resolve()->saveObject(...$arguments);
		} catch (Throwable $e) {
			throw new RuntimeException(message: 'Could not store the conformance report: ' . $e->getMessage(), code: 0, previous: $e);
		}

		return $this->storedFields(row: $stored);

	}//end save()
}//end class
