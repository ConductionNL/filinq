<?php

/**
 * The personal data report: which documents hold personal data
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Reports
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

namespace OCA\Filinq\Service\Reports;

use OCA\Filinq\Service\FileEntityStatsService;
use OCA\Filinq\Service\ReadableFileResolver;
use OCA\Filinq\Service\Redaction\AnonymizationLinkReader;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Lists, across the organisation, the documents the reader may read in which
 * detection found personal data: per document the entity types with their
 * counts and whether an anonymised copy exists.
 *
 * Counts only. A detected value never leaves this class: the rows are built
 * from the per-type counts of {@see FileEntityStatsService::entityCountsByType()}.
 *
 * The candidates come from OpenRegister's entity relations, page by page.
 * Every candidate passes {@see ReadableFileResolver::node()} first, so a
 * document the reader may not read is left out without a trace.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Reports
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/specify-capability-rows/specs/management-reports/spec.md#requirement-the-personal-data-report-lists-which-documents-hold-personal-data-req-scr-010
 */
class PersonalDataReport {

	/**
	 * How many file ids are read from OpenRegister per page.
	 */
	public const PAGE_SIZE = 500;

	/**
	 * At most this many candidate files are looked at in one report.
	 */
	public const MAX_SCANNED = 5000;

	/**
	 * At most this many rows are listed in one report.
	 */
	public const MAX_ROWS = 1000;

	/**
	 * The `copy` value of a document that has an anonymised copy.
	 */
	public const COPY_PRESENT = 'present';

	/**
	 * The `copy` value of a document without an anonymised copy.
	 */
	public const COPY_MISSING = 'missing';

	/**
	 * The OpenRegister mapper method that lists the files holding entities.
	 */
	private const LISTING_METHOD = 'findFileIdsWithEntities';

	/**
	 * Constructor.
	 *
	 * @param FileEntityStatsService  $stats  Entity counts per file.
	 * @param ReadableFileResolver    $files  The file behind an id, as the reader may read it.
	 * @param AnonymizationLinkReader $links  Pairs a source file with its anonymised copy.
	 * @param LoggerInterface         $logger Logger for diagnostics.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly FileEntityStatsService $stats,
		private readonly ReadableFileResolver $files,
		private readonly AnonymizationLinkReader $links,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * The report for the signed-in reader.
	 *
	 * `available` is false when the installed OpenRegister cannot list the
	 * files that hold entities; the report is then empty rather than partial.
	 * `truncated` is true when the scan stopped at {@see self::MAX_SCANNED}
	 * candidates or {@see self::MAX_ROWS} rows.
	 *
	 * @return array<string, mixed> `results` (the rows {@see self::row()} builds), `available` and `truncated`.
	 *
	 * @spec openspec/changes/specify-capability-rows/specs/management-reports/spec.md#requirement-the-personal-data-report-lists-which-documents-hold-personal-data-req-scr-010
	 */
	public function forReader(): array {
		$mapper = $this->stats->tryGetEntityRelationMapper();
		if ($mapper === null || method_exists($mapper, self::LISTING_METHOD) === false) {
			return ['results' => [], 'available' => false, 'truncated' => false];
		}

		$rows = [];
		$scanned = 0;
		$after = 0;
		do {
			$page = $this->page(mapper: $mapper, afterFileId: $after);
			foreach ($page as $fileId) {
				if ($scanned >= self::MAX_SCANNED || count($rows) >= self::MAX_ROWS) {
					return ['results' => $rows, 'available' => true, 'truncated' => true];
				}

				$scanned++;
				$after = max($after, $fileId);
				$row = $this->row(fileId: $fileId, mapper: $mapper);
				if ($row !== null) {
					$rows[] = $row;
				}
			}
			$full = count($page) === self::PAGE_SIZE;
		} while ($full === true);

		return ['results' => $rows, 'available' => true, 'truncated' => false];

	}//end forReader()

	/**
	 * One page of candidate file ids, or none when the read fails.
	 *
	 * @param object $mapper      OpenRegister's entity relation mapper.
	 * @param int    $afterFileId Only ids above this one.
	 *
	 * @return list<int> The ids, ascending.
	 */
	private function page(object $mapper, int $afterFileId): array {
		try {
			$ids = $mapper->{self::LISTING_METHOD}(afterFileId: $afterFileId, limit: self::PAGE_SIZE);
		} catch (Throwable $e) {
			$this->logger->warning(
				message: '[PersonalDataReport] the files holding entities could not be listed',
				context: ['afterFileId' => $afterFileId, 'error' => $e->getMessage()]
			);
			return [];
		}

		$page = [];
		foreach ((array) $ids as $id) {
			if ((int) $id > $afterFileId) {
				$page[] = (int) $id;
			}
		}

		return $page;

	}//end page()

	/**
	 * The row of one file, or null when the reader may not read it or detection found nothing.
	 *
	 * @param int                                       $fileId The file.
	 * @param \OCA\OpenRegister\Db\EntityRelationMapper $mapper OpenRegister's entity relation mapper.
	 *
	 * @return array<string, mixed>|null fileId, name, entityCounts, entityTypes, entityTotal, anonymisedCopy and copy.
	 */
	private function row(int $fileId, object $mapper): ?array {
		$node = $this->files->node(fileId: $fileId);
		if ($node === null) {
			return null;
		}

		$counts = $this->stats->entityCountsByType(fileId: $fileId, entityRelationMapper: $mapper);
		if ($counts === []) {
			return null;
		}

		$anonymised = $this->links->forSource(sourceFileId: $fileId) !== null;
		$types = [];
		foreach ($counts as $type => $count) {
			$types[] = $type . ' ' . $count;
		}

		$copy = self::COPY_MISSING;
		if ($anonymised === true) {
			$copy = self::COPY_PRESENT;
		}

		return [
			'fileId' => $fileId,
			'name' => $node->getName(),
			'entityCounts' => $counts,
			'entityTypes' => implode(', ', $types),
			'entityTotal' => array_sum($counts),
			'anonymisedCopy' => $anonymised,
			'copy' => $copy,
		];

	}//end row()
}//end class
