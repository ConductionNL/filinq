<?php

/**
 * Unit tests for BatchReportAggregator and the batch report summary (REQ-DDRAS-006).
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/redaction-at-scale
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\BatchReportAggregator;
use OCA\Filinq\Service\BatchReportService;
use OCA\Filinq\Service\BatchStateService;
use PHPUnit\Framework\TestCase;

/**
 * Failures are grouped by reason, throughput and phase durations are computed
 * at read time, and entity values stay out of the report.
 *
 * @covers \OCA\Filinq\Service\BatchReportAggregator
 * @covers \OCA\Filinq\Service\BatchReportService
 */
class BatchReportAggregatorTest extends TestCase {

	/**
	 * A completed batch: 476 succeeded files, 3 conversion lock timeouts and 1
	 * extraction error, run over six hours.
	 *
	 * @return array<string, mixed>
	 */
	private static function batch(): array {
		$files = [];
		for ($i = 1; $i <= 476; $i++) {
			$files[] = ['fileId' => $i, 'fileName' => 'f' . $i . '.pdf', 'status' => 'anonymized', 'extractMs' => 1000, 'anonymizeMs' => 2000, 'entities' => [['value' => 'Jan Jansen']]];
		}

		for ($i = 1; $i <= 3; $i++) {
			$files[] = ['fileId' => (900 + $i), 'fileName' => 'lock' . $i . '.docx', 'status' => 'error', 'error' => 'soffice lock timeout', 'conversionAttempts' => [['engine' => 'soffice']]];
		}

		$files[] = ['fileId' => 999, 'fileName' => 'broken.pdf', 'status' => 'error', 'error' => 'Extraction failed: no text layer'];
		$files[1]['qaSampled'] = true;
		$files[2]['qaSampled'] = true;
		$files[2]['qaOutcome'] = 'corrected';

		return ['batchId' => 'b-480', 'status' => 'completed', 'createdAt' => 1792000000, 'startedAt' => 1792000000, 'completedAt' => (1792000000 + (6 * 3600)), 'files' => $files];
	}//end batch()

	/**
	 * The summary of the batch.
	 *
	 * @return void
	 */
	public function testTheSummaryGroupsFailuresAndComputesThroughput(): void {
		$summary = (new BatchReportAggregator())->summarise(self::batch());

		$this->assertSame(
			[
				'files' => 480,
				'succeeded' => 476,
				'failed' => 4,
				'documentsPerHour' => 79.3,
				'phaseMs' => ['extract' => 476000, 'anonymize' => 952000],
				'failures' => ['conversion-failed' => 3, 'Extraction failed: no text layer' => 1],
				'qaSamples' => ['2' => 'pending', '3' => 'corrected'],
			],
			$summary
		);
	}//end testTheSummaryGroupsFailuresAndComputesThroughput()

	/**
	 * Without timestamps or phase durations those figures are null, not guessed;
	 * a recorded category wins over the message.
	 *
	 * @return void
	 */
	public function testMissingInputsGiveNullAndACategoryWins(): void {
		$summary = (new BatchReportAggregator())->summarise(
			[
				'files' => [
					['fileId' => 1, 'status' => 'error', 'error' => 'x', 'errorCategory' => 'lock-miss', 'conversionAttempts' => []],
					['fileId' => 2, 'status' => 'error'],
					'not-a-file',
				],
				'startedAt' => 100,
				'completedAt' => 100,
			]
		);

		$this->assertNull($summary['documentsPerHour']);
		$this->assertSame(['extract' => null, 'anonymize' => null], $summary['phaseMs']);
		$this->assertSame(['lock-miss' => 1, 'unknown' => 1], $summary['failures']);
		$this->assertSame(2, $summary['files']);
	}//end testMissingInputsGiveNullAndACategoryWins()

	/**
	 * The CSV carries each failed row's reason, the summary and the samples,
	 * and never an entity value.
	 *
	 * @return void
	 */
	public function testTheReportCarriesReasonsAndSummaryWithoutEntityValues(): void {
		$state = $this->createMock(BatchStateService::class);
		$state->method('getBatch')->with('b-480')->willReturn(self::batch());

		$csv = (new BatchReportService($state))->generateReport('b-480');
		$lines = explode("\n", trim($csv));

		$this->assertSame('fileName,originalFileId,anonymizedFileId,entityCount,replacementCount,status,timestamp,reason,qaOutcome', $lines[0]);
		$this->assertStringEndsWith(',error,' . date('c', 1792000000) . ',conversion-failed,', $lines[477]);
		$this->assertStringEndsWith(',anonymized,' . date('c', 1792000000) . ',,corrected', $lines[3]);
		$this->assertContains('summary,documentsPerHour,79.3', $lines);
		$this->assertContains('failures,conversion-failed,3', $lines);
		$this->assertContains('failures,"Extraction failed: no text layer",1', $lines);
		$this->assertContains('qaSample,2,pending', $lines);
		$this->assertStringNotContainsString('Jan Jansen', $csv);
	}//end testTheReportCarriesReasonsAndSummaryWithoutEntityValues()
}//end class
