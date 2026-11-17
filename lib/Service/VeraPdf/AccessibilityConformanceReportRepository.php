<?php

/**
 * Read and write PDF/UA-1 accessibility conformance reports.
 *
 * @category Service
 * @package  OCA\Filinq\Service\VeraPdf
 * @author   Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 * @spec     openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#requirement-a-persisted-accessibility-conformance-report-is-stored-per-document-and-re-runnable-req-ddpum-002
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\VeraPdf;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\KeepsRowsPerFile;
use OCA\Filinq\Service\ReadsStoredObjectRows;
use RuntimeException;

/**
 * One accessibilityConformanceReport per file, apart from the PDF/A
 * conformanceReport so that neither check overwrites the other.
 */
class AccessibilityConformanceReportRepository {
	use ReadsStoredObjectRows;
	use KeepsRowsPerFile;

	public const SCHEMA = 'accessibilityConformanceReport';

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
	 * The report stored for a file.
	 *
	 * @param int $fileId The file id.
	 *
	 * @return array<string, mixed>|null The report, or null when none is stored.
	 *
	 * @throws RuntimeException When the register cannot be read.
	 *
	 * @spec openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#requirement-a-persisted-accessibility-conformance-report-is-stored-per-document-and-re-runnable-req-ddpum-002
	 */
	public function findForFile(int $fileId): ?array {
		return ($this->rowsForFile(schema: self::SCHEMA, fileId: $fileId, failure: 'The accessibility conformance report could not be read: ')[0] ?? null);

	}//end findForFile()

	/**
	 * Store a report, updating the file's existing one.
	 *
	 * @param array<string, mixed> $report The report, fileId set.
	 *
	 * @return array<string, mixed> The stored report with its uuid.
	 *
	 * @throws RuntimeException When the register refuses it.
	 *
	 * @spec openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#requirement-a-persisted-accessibility-conformance-report-is-stored-per-document-and-re-runnable-req-ddpum-002
	 */
	public function save(array $report): array {
		return $this->saveRowForFile(
			schema: self::SCHEMA,
			row: $report,
			existing: $this->findForFile(fileId: (int) $report['fileId']),
			failure: 'Could not store the accessibility conformance report: '
		);

	}//end save()
}//end class
