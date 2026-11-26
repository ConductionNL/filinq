<?php

/**
 * Unit tests for the per-entity-type counts AnonymisationRunRecords records.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\AnonymisationRunRecords;
use OCA\Filinq\Service\AnonymizationPersistenceService;
use OCA\Filinq\Service\BridgeResultRecorder;
use OCA\Filinq\Service\Pseudonymisation\PseudonymMapRecorder;
use OCA\Filinq\Service\Redaction\RedactionVerdictRecorder;
use PHPUnit\Framework\TestCase;

/**
 * The link gets entity TYPES and counts, never a value (document-waarmerk-certification).
 *
 * @covers \OCA\Filinq\Service\AnonymisationRunRecords
 */
class AnonymisationRunRecordsEntityCountsTest extends TestCase {

	/**
	 * The counts reach the link write and contain no entity text.
	 *
	 * @return void
	 */
	public function testCountsPerTypeReachTheLink(): void {
		$verdicts = $this->createMock(RedactionVerdictRecorder::class);
		$verdicts->method('record')->willReturnArgument(0);
		$keys = $this->createMock(PseudonymMapRecorder::class);
		$keys->method('record')->willReturnArgument(0);
		$persistence = $this->createMock(AnonymizationPersistenceService::class);
		$persistence->expects($this->once())->method('recordAnonymizationLink')
			->with(
				7,
				'node',
				$this->callback(
					function (array $resultInfo): bool {
						$this->assertSame(['BSN' => 2, 'PERSON' => 1, 'UNKNOWN' => 1], $resultInfo['entityTypeCounts']);
						$this->assertStringNotContainsString('Jansen', (string) json_encode($resultInfo));
						return true;
					}
				)
			)
			->willReturnArgument(2);

		$records = new AnonymisationRunRecords(verdicts: $verdicts, persistence: $persistence, keys: $keys);
		$result = $records->record(
			resultInfo: ['anonymizedFileId' => 9],
			context: [
				'fileId' => 7,
				'sourceNode' => 'node',
				'anonymisedNode' => 'out',
				'redactedValues' => [
					['text' => 'Jan Jansen', 'entityType' => 'PERSON'],
					['text' => '123456782', 'entityType' => 'BSN'],
					['text' => '111222333', 'entityType' => 'bsn'],
					['text' => 'x'],
					'not an entity',
				],
			]
		);

		$this->assertSame(['BSN' => 2, 'PERSON' => 1, 'UNKNOWN' => 1], $result['entityTypeCounts']);
	}//end testCountsPerTypeReachTheLink()

	/**
	 * A run that wrote no file records no link and no counts.
	 *
	 * @return void
	 */
	public function testNoFileNoCounts(): void {
		$verdicts = $this->createMock(RedactionVerdictRecorder::class);
		$verdicts->method('record')->willReturnArgument(0);
		$persistence = $this->createMock(AnonymizationPersistenceService::class);
		$persistence->expects($this->never())->method('recordAnonymizationLink');

		$records = new AnonymisationRunRecords(verdicts: $verdicts, persistence: $persistence, keys: $this->createMock(PseudonymMapRecorder::class));
		$result = $records->record(resultInfo: [], context: ['anonymisedNode' => null, 'redactedValues' => [['entityType' => 'BSN']]]);
		$this->assertArrayNotHasKey('entityTypeCounts', $result);
		$bridge = $this->createMock(BridgeResultRecorder::class);
		$bridge->expects($this->never())->method('record');
		(new AnonymisationRunRecords(verdicts: $verdicts, persistence: $persistence, keys: $this->createMock(PseudonymMapRecorder::class), bridge: $bridge))
			->record(resultInfo: [], context: ['anonymisedNode' => null]);
	}//end testNoFileNoCounts()

	/**
	 * The output of a run reaches the bridge with its source file, after the link.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/zgw-document-bridge/tasks.md#task-2.6
	 */
	public function testTheOutputReachesTheBridge(): void {
		$verdicts = $this->createMock(RedactionVerdictRecorder::class);
		$verdicts->method('record')->willReturnArgument(0);
		$keys = $this->createMock(PseudonymMapRecorder::class);
		$keys->method('record')->willReturnArgument(0);
		$persistence = $this->createMock(AnonymizationPersistenceService::class);
		$persistence->method('recordAnonymizationLink')->willReturnArgument(2);
		$bridge = $this->createMock(BridgeResultRecorder::class);
		$bridge->expects($this->once())->method('record')->with(1101, 2201)->willReturn(1);

		$records = new AnonymisationRunRecords(verdicts: $verdicts, persistence: $persistence, keys: $keys, bridge: $bridge);
		$result = $records->record(
			resultInfo: ['anonymizedFileId' => 2201],
			context: ['fileId' => 1101, 'sourceNode' => 'node', 'anonymisedNode' => 'out', 'redactedValues' => []]
		);

		$this->assertSame(2201, $result['anonymizedFileId']);
	}//end testTheOutputReachesTheBridge()
}//end class
