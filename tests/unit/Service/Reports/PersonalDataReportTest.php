<?php

/**
 * Unit tests for PersonalDataReport
 *
 * The report lists the readable documents in which detection found personal
 * data, with counts per entity type and whether an anonymised copy exists,
 * and never a detected value (specify-capability-rows REQ-SCR-010).
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service\Reports
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/archive/2026-11-24-specify-capability-rows/specs/management-reports/spec.md#requirement-the-personal-data-report-lists-which-documents-hold-personal-data-req-scr-010
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Reports;

use OCA\Filinq\Service\FileEntityStatsService;
use OCA\Filinq\Service\ReadableFileResolver;
use OCA\Filinq\Service\Redaction\AnonymizationLinkReader;
use OCA\Filinq\Service\Reports\PersonalDataReport;
use OCA\OpenRegister\Db\EntityRelationMapper;
use OCP\Files\Node;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * @covers \OCA\Filinq\Service\Reports\PersonalDataReport
 */
#[CoversClass(PersonalDataReport::class)]
class PersonalDataReportTest extends TestCase {

	/**
	 * The entity counts per file in these tests.
	 *
	 * @var array<int, array<string, int>>
	 */
	private const COUNTS = [
		11 => ['BSN' => 1, 'PERSON' => 3],
		12 => ['PERSON' => 2],
		13 => [],
	];

	/**
	 * Two documents with findings are listed with their counts, only the one
	 * without an anonymised copy says so, and no value is in the report.
	 *
	 * @return void
	 */
	public function testTwoDocumentsWithPersonalDataAreListed(): void {
		$mapper = $this->mapper(pages: [[11, 12, 13]]);

		$report = $this->report(mapper: $mapper, readable: [11, 12, 13], anonymised: [12])->forReader();

		$this->assertSame(
			[
				'results' => [
					['fileId' => 11, 'name' => 'file-11.docx', 'entityCounts' => ['BSN' => 1, 'PERSON' => 3], 'entityTypes' => 'BSN 1, PERSON 3', 'entityTotal' => 4, 'anonymisedCopy' => false, 'copy' => 'missing'],
					['fileId' => 12, 'name' => 'file-12.docx', 'entityCounts' => ['PERSON' => 2], 'entityTypes' => 'PERSON 2', 'entityTotal' => 2, 'anonymisedCopy' => true, 'copy' => 'present'],
				],
				'available' => true,
				'truncated' => false,
			],
			$report
		);

	}//end testTwoDocumentsWithPersonalDataAreListed()

	/**
	 * A document the reader may not read is left out, and its counts and copy are never read.
	 *
	 * @return void
	 */
	public function testADocumentTheReaderMayNotReadIsLeftOut(): void {
		$mapper = $this->mapper(pages: [[11, 12]]);
		$stats = $this->stats(mapper: $mapper);
		$stats->expects($this->once())->method('entityCountsByType')->with(fileId: 12, entityRelationMapper: $mapper)->willReturn(['PERSON' => 2]);
		$links = $this->createMock(AnonymizationLinkReader::class);
		$links->expects($this->once())->method('forSource')->with(sourceFileId: 12)->willReturn(null);

		$report = (new PersonalDataReport($stats, $this->files(readable: [12]), $links, $this->createMock(LoggerInterface::class)))->forReader();

		$this->assertSame([12], array_column($report['results'], 'fileId'));

	}//end testADocumentTheReaderMayNotReadIsLeftOut()

	/**
	 * The scan reads the next page after the last id of a full page, and stops after a short one.
	 *
	 * @return void
	 */
	public function testTheScanWalksEveryPage(): void {
		$first = range(1, PersonalDataReport::PAGE_SIZE);
		$mapper = $this->createMock(EntityRelationMapper::class);
		$mapper->expects($this->exactly(2))->method('findFileIdsWithEntities')->willReturnCallback(
			static fn (int $afterFileId, int $limit): array => match ($afterFileId) {
				0 => $first,
				PersonalDataReport::PAGE_SIZE => [PersonalDataReport::PAGE_SIZE + 1],
				default => throw new RuntimeException('unexpected page after ' . $afterFileId),
			}
		);
		$stats = $this->stats(mapper: $mapper);
		$stats->method('entityCountsByType')->willReturn(['PERSON' => 1]);
		$links = $this->createMock(AnonymizationLinkReader::class);

		$report = (new PersonalDataReport($stats, $this->files(readable: [PersonalDataReport::PAGE_SIZE + 1]), $links, $this->createMock(LoggerInterface::class)))->forReader();

		$this->assertSame([PersonalDataReport::PAGE_SIZE + 1], array_column($report['results'], 'fileId'));
		$this->assertFalse($report['truncated']);

	}//end testTheScanWalksEveryPage()

	/**
	 * The report stops at the row cap and says it was truncated.
	 *
	 * @return void
	 */
	public function testTheReportStopsAtTheRowCap(): void {
		$ids = range(1, PersonalDataReport::MAX_ROWS + 1);
		$mapper = $this->createMock(EntityRelationMapper::class);
		$mapper->method('findFileIdsWithEntities')->willReturnCallback(
			static fn (int $afterFileId, int $limit): array => array_slice(array_values(array_filter($ids, static fn (int $id): bool => $id > $afterFileId)), 0, $limit)
		);
		$stats = $this->stats(mapper: $mapper);
		$stats->method('entityCountsByType')->willReturn(['PERSON' => 1]);

		$report = (new PersonalDataReport($stats, $this->files(readable: $ids), $this->createMock(AnonymizationLinkReader::class), $this->createMock(LoggerInterface::class)))->forReader();

		$this->assertCount(PersonalDataReport::MAX_ROWS, $report['results']);
		$this->assertTrue($report['truncated']);

	}//end testTheReportStopsAtTheRowCap()

	/**
	 * Without OpenRegister the report is empty and says it is unavailable.
	 *
	 * @return void
	 */
	public function testWithoutOpenRegisterTheReportIsUnavailable(): void {
		$stats = $this->createMock(FileEntityStatsService::class);
		$stats->method('tryGetEntityRelationMapper')->willReturn(null);
		$stats->expects($this->never())->method('entityCountsByType');

		$report = (new PersonalDataReport($stats, $this->files(readable: []), $this->createMock(AnonymizationLinkReader::class), $this->createMock(LoggerInterface::class)))->forReader();

		$this->assertSame(['results' => [], 'available' => false, 'truncated' => false], $report);

	}//end testWithoutOpenRegisterTheReportIsUnavailable()

	/**
	 * A listing that fails ends the scan with what was read, and is logged.
	 *
	 * @return void
	 */
	public function testAFailingListingEndsTheScan(): void {
		$mapper = $this->createMock(EntityRelationMapper::class);
		$mapper->method('findFileIdsWithEntities')->willThrowException(new RuntimeException('database gone'));
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->once())->method('warning');

		$report = (new PersonalDataReport($this->stats(mapper: $mapper), $this->files(readable: []), $this->createMock(AnonymizationLinkReader::class), $logger))->forReader();

		$this->assertSame(['results' => [], 'available' => true, 'truncated' => false], $report);

	}//end testAFailingListingEndsTheScan()

	/**
	 * The report over these pages, readable files and anonymised sources.
	 *
	 * @param EntityRelationMapper $mapper     The mapper double.
	 * @param list<int>            $readable   The files the reader may read.
	 * @param list<int>            $anonymised The files with an anonymised copy.
	 *
	 * @return PersonalDataReport
	 */
	private function report(EntityRelationMapper $mapper, array $readable, array $anonymised): PersonalDataReport {
		$stats = $this->stats(mapper: $mapper);
		$stats->method('entityCountsByType')->willReturnCallback(
			static fn (int $fileId): array => self::COUNTS[$fileId] ?? []
		);
		$links = $this->createMock(AnonymizationLinkReader::class);
		$links->method('forSource')->willReturnCallback(
			static fn (int $sourceFileId): ?array => in_array($sourceFileId, $anonymised, true) === true ? ['anonymizedFileId' => (100 + $sourceFileId)] : null
		);

		return new PersonalDataReport($stats, $this->files(readable: $readable), $links, $this->createMock(LoggerInterface::class));

	}//end report()

	/**
	 * A mapper that answers these pages in turn.
	 *
	 * @param list<list<int>> $pages The pages.
	 *
	 * @return EntityRelationMapper&MockObject
	 */
	private function mapper(array $pages): EntityRelationMapper {
		$mapper = $this->createMock(EntityRelationMapper::class);
		$mapper->method('findFileIdsWithEntities')->willReturnOnConsecutiveCalls(...$pages);

		return $mapper;

	}//end mapper()

	/**
	 * Entity stats that hand out this mapper.
	 *
	 * @param EntityRelationMapper $mapper The mapper.
	 *
	 * @return FileEntityStatsService&MockObject
	 */
	private function stats(EntityRelationMapper $mapper): FileEntityStatsService {
		$stats = $this->createMock(FileEntityStatsService::class);
		$stats->method('tryGetEntityRelationMapper')->willReturn($mapper);

		return $stats;

	}//end stats()

	/**
	 * A resolver that opens only these files, each named file-<id>.docx.
	 *
	 * @param list<int> $readable The readable file ids.
	 *
	 * @return ReadableFileResolver&MockObject
	 */
	private function files(array $readable): ReadableFileResolver {
		$files = $this->createMock(ReadableFileResolver::class);
		$files->method('node')->willReturnCallback(
			function (int $fileId) use ($readable): ?Node {
				if (in_array($fileId, $readable, true) === false) {
					return null;
				}

				$node = $this->createMock(Node::class);
				$node->method('getName')->willReturn('file-' . $fileId . '.docx');

				return $node;
			}
		);

		return $files;

	}//end files()
}//end class
