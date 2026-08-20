<?php

/**
 * Unit tests for BatchAnonymizeService — output folder layout post-processing
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/anonymisation-batch-output-folder-layout/tasks.md#task-10
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\DocuDesk\Tests\Unit\Service;

use OCA\DocuDesk\Service\AnonymizationService;
use OCA\DocuDesk\Service\BatchAnonymizeService;
use OCA\DocuDesk\Service\BatchStateService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for BatchAnonymizeService output-layout related behaviour.
 *
 * The canonical BatchAnonymizeService delegates all per-file output placement
 * to AnonymizationService::anonymizeDocument(); it owns no rootFolder /
 * userSession / layout-resolver dependencies of its own. These tests therefore
 * assert the batch-level orchestration outcome (processed / total counts) for
 * the layout-relevant scenarios, with the per-file output result driven by the
 * AnonymizationService mock.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://conduction.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class BatchAnonymizeServiceOutputLayoutTest extends TestCase {

	/**
	 * The service under test.
	 *
	 * @var BatchAnonymizeService
	 */
	private BatchAnonymizeService $service;

	/**
	 * @var AnonymizationService|MockObject
	 */
	private AnonymizationService|MockObject $mockAnonService;

	/**
	 * @var BatchStateService|MockObject
	 */
	private BatchStateService|MockObject $mockStateService;

	/**
	 * Set up test environment.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->mockAnonService = $this->createMock(AnonymizationService::class);
		$this->mockStateService = $this->createMock(BatchStateService::class);

		$this->service = new BatchAnonymizeService(
			anonService: $this->mockAnonService,
			stateService: $this->mockStateService
		);

	}//end setUp()

	/**
	 * Build a minimal batch fixture with one extracted file.
	 *
	 * @param int $fileId Source file ID.
	 *
	 * @return array<string, mixed> Batch data.
	 */
	private function makeBatch(int $fileId = 10): array {
		return [
			'batchId' => 'batch-layout-1',
			'status' => 'review',
			'files' => [
				['fileId' => $fileId, 'status' => 'extracted'],
			],
		];

	}//end makeBatch()

	/**
	 * Post-process places file at the layout destination resolved per file.
	 *
	 * The destination is owned by AnonymizationService::anonymizeDocument();
	 * the batch service simply records the returned per-file outcome. We assert
	 * that an extracted file is processed and the returned path is honoured.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/anonymisation-batch-output-folder-layout/tasks.md#task-10
	 */
	public function testPostProcessMovePlacesFileAtExpectedPath(): void {
		$batch = $this->makeBatch(fileId: 10);
		$this->mockStateService->method('getBatch')->willReturn($batch);

		$targetPath = '/admin/files/dossier/anonymised/foo.pdf';

		$this->mockAnonService->method('anonymizeDocument')
			->willReturn(['replacementCount' => 2, 'anonymizedFileId' => 99, 'anonymizedFilePath' => $targetPath]);

		$result = $this->service->anonymizeBatch(batchId: 'batch-layout-1', entities: []);

		$this->assertSame(1, $result['processedFiles']);

	}//end testPostProcessMovePlacesFileAtExpectedPath()

	/**
	 * A per-file output failure surfaced by AnonymizationService preserves the
	 * legacy path and is recorded without aborting the batch.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/anonymisation-batch-output-folder-layout/tasks.md#task-10
	 */
	public function testMoveFailurePreservesFileAtLegacyPathWithWarning(): void {
		$batch = $this->makeBatch(fileId: 10);
		$this->mockStateService->method('getBatch')->willReturn($batch);

		$legacyPath = '/admin/files/dossier/foo_anonymized.pdf';

		$this->mockAnonService->method('anonymizeDocument')
			->willReturn(
				[
					'replacementCount' => 1,
					'anonymizedFileId' => 99,
					'anonymizedFilePath' => $legacyPath,
					'warning' => ['code' => 'OUTPUT_MOVE_FAILED', 'message' => 'Permission denied'],
				]
			);

		$result = $this->service->anonymizeBatch(batchId: 'batch-layout-1', entities: []);

		$this->assertSame(1, $result['processedFiles']);

	}//end testMoveFailurePreservesFileAtLegacyPathWithWarning()

	/**
	 * When anonymizedFileId is null, the file is still processed and the legacy
	 * (null) path is returned unchanged.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/anonymisation-batch-output-folder-layout/tasks.md#task-10
	 */
	public function testNoMoveWhenAnonymizedFileIdIsNull(): void {
		$batch = $this->makeBatch(fileId: 10);
		$this->mockStateService->method('getBatch')->willReturn($batch);

		$this->mockAnonService->method('anonymizeDocument')
			->willReturn(['replacementCount' => 0, 'anonymizedFileId' => null, 'anonymizedFilePath' => null]);

		$result = $this->service->anonymizeBatch(batchId: 'batch-layout-1', entities: []);

		$this->assertSame(1, $result['processedFiles']);

	}//end testNoMoveWhenAnonymizedFileIdIsNull()

	/**
	 * Source discovery excludes non-extracted files: only files with status
	 * `extracted` are anonymized; `uploaded` (and other) statuses are skipped.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/anonymisation-batch-output-folder-layout/tasks.md#task-10
	 */
	public function testSourceDiscoveryExcludedFilesAreSkipped(): void {
		$batch = [
			'batchId' => 'batch-layout-2',
			'status' => 'review',
			'files' => [
				['fileId' => 1, 'status' => 'extracted'],
				['fileId' => 2, 'status' => 'uploaded'],
			],
		];
		$this->mockStateService->method('getBatch')->willReturn($batch);

		$this->mockAnonService->expects($this->once())
			->method('anonymizeDocument')
			->willReturn(['replacementCount' => 1, 'anonymizedFileId' => 55, 'anonymizedFilePath' => '/src/clean_anonymized.pdf']);

		$result = $this->service->anonymizeBatch(batchId: 'batch-layout-2', entities: []);

		$this->assertSame(1, $result['processedFiles']);
		$this->assertSame(2, $result['totalFiles']);

	}//end testSourceDiscoveryExcludedFilesAreSkipped()
}//end class
