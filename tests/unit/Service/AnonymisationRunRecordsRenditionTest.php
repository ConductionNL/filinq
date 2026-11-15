<?php

/**
 * Unit tests for the review rendition the run records copy onto the link.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-5.1
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\AnonymisationRunRecords;
use OCA\Filinq\Service\AnonymizationPersistenceService;
use OCA\Filinq\Service\Pseudonymisation\PseudonymMapRecorder;
use OCA\Filinq\Service\Redaction\RedactionVerdictRecorder;
use OCA\Filinq\Service\Review\ReviewRenditionRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * The link records the rendition the review ran on, its six fields and nothing else.
 *
 * @covers \OCA\Filinq\Service\AnonymisationRunRecords
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class AnonymisationRunRecordsRenditionTest extends TestCase {

	/**
	 * @var AnonymizationPersistenceService&MockObject
	 */
	private AnonymizationPersistenceService&MockObject $persistence;

	/**
	 * @var ReviewRenditionRepository&MockObject
	 */
	private ReviewRenditionRepository&MockObject $renditions;

	/**
	 * The records over doubles that pass the result through.
	 *
	 * @return AnonymisationRunRecords The records.
	 */
	private function records(): AnonymisationRunRecords {
		$verdicts = $this->createMock(RedactionVerdictRecorder::class);
		$verdicts->method('record')->willReturnArgument(0);
		$keys = $this->createMock(PseudonymMapRecorder::class);
		$keys->method('record')->willReturnArgument(0);
		$this->persistence = $this->createMock(AnonymizationPersistenceService::class);
		$this->renditions = $this->createMock(ReviewRenditionRepository::class);

		return new AnonymisationRunRecords(
			verdicts: $verdicts,
			persistence: $this->persistence,
			keys: $keys,
			renditions: $this->renditions
		);
	}//end records()

	/**
	 * The stored rendition's six fields go onto the link.
	 *
	 * @return void
	 */
	public function testTheLinkGetsTheRendition(): void {
		$records = $this->records();
		$this->renditions->expects($this->once())->method('findFor')->with(10)->willReturn(
			[
				'uuid' => 'r-1', 'id' => 'r-1', 'sourceFileId' => 10, 'fileId' => 11, 'sha256' => 'a1', 'kind' => 'converted',
				'backend' => 'libreoffice', 'textLayer' => 'native', 'preparedAt' => '2026-11-15T10:00:00+00:00',
			]
		);
		$this->persistence->expects($this->once())->method('recordAnonymizationLink')
			->with(
				10,
				'source',
				[
					'anonymizedFileId' => 99,
					'reviewRendition' => [
						'fileId' => 11, 'sha256' => 'a1', 'kind' => 'converted', 'backend' => 'libreoffice',
						'textLayer' => 'native', 'preparedAt' => '2026-11-15T10:00:00+00:00',
					],
				]
			)
			->willReturnArgument(2);

		$result = $records->record(resultInfo: ['anonymizedFileId' => 99], context: ['fileId' => 10, 'sourceNode' => 'source', 'anonymisedNode' => null]);

		$this->assertSame('a1', $result['reviewRendition']['sha256']);
	}//end testTheLinkGetsTheRendition()

	/**
	 * Without a rendition the link is written as before.
	 *
	 * @return void
	 */
	public function testNoRenditionLeavesTheLinkAsItWas(): void {
		$records = $this->records();
		$this->renditions->method('findFor')->willReturn(null);
		$this->persistence->expects($this->once())->method('recordAnonymizationLink')
			->with(10, 'source', ['anonymizedFileId' => 99])
			->willReturnArgument(2);

		$this->assertSame(
			['anonymizedFileId' => 99],
			$records->record(resultInfo: ['anonymizedFileId' => 99], context: ['fileId' => 10, 'sourceNode' => 'source', 'anonymisedNode' => null])
		);
	}//end testNoRenditionLeavesTheLinkAsItWas()

	/**
	 * A run without an anonymised file writes no link and reads no rendition.
	 *
	 * @return void
	 */
	public function testNoAnonymisedFileReadsNoRendition(): void {
		$records = $this->records();
		$this->renditions->expects($this->never())->method('findFor');
		$this->persistence->expects($this->never())->method('recordAnonymizationLink');

		$records->record(resultInfo: ['anonymizedFileId' => null], context: ['fileId' => 10, 'sourceNode' => 'source', 'anonymisedNode' => null]);
	}//end testNoAnonymisedFileReadsNoRendition()
}//end class
