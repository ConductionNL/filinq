<?php

/**
 * Unit tests for BatchExtractionService
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.DocuDesk.app
 *
 * @spec openspec/changes/unit-test-coverage-75/tasks.md#task-4.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Tests\Unit\Service;

use Exception;
use OCA\DocuDesk\Service\AnonymizationService;
use OCA\DocuDesk\Service\BatchExtractionService;
use OCA\DocuDesk\Service\BatchStateService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for BatchExtractionService
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class BatchExtractionServiceTest extends TestCase {

	/**
	 * @var BatchExtractionService
	 */
	private BatchExtractionService $service;

	/**
	 * @var AnonymizationService|MockObject
	 */
	private AnonymizationService|MockObject $mockAnonService;

	/**
	 * @var BatchStateService|MockObject
	 */
	private BatchStateService|MockObject $mockStateService;

	/**
	 * @var LoggerInterface|MockObject
	 */
	private LoggerInterface|MockObject $mockLogger;

	protected function setUp(): void {
		parent::setUp();

		$this->mockLogger = $this->createMock(LoggerInterface::class);
		$this->mockAnonService = $this->createMock(AnonymizationService::class);
		$this->mockStateService = $this->createMock(BatchStateService::class);

		$this->service = new BatchExtractionService(
			logger: $this->mockLogger,
			anonService: $this->mockAnonService,
			stateService: $this->mockStateService,
		);

	}//end setUp()

	/**
	 * Test extractNext throws when batch not found.
	 *
	 * @return void
	 */
	public function testExtractNextThrowsWhenBatchNotFound(): void {
		$this->mockStateService->method('getBatch')->willReturn(null);

		$this->expectException(Exception::class);
		$this->expectExceptionCode(404);

		$this->service->extractNext('non-existent');

	}//end testExtractNextThrowsWhenBatchNotFound()

	/**
	 * Test extractNext transitions batch to review when all files done.
	 *
	 * @return void
	 */
	public function testExtractNextReturnsReviewWhenAllExtracted(): void {
		$batch = [
			'batchId' => 'abc',
			'status' => 'extracting',
			'files' => [
				['fileId' => 1, 'fileName' => 'a.pdf', 'status' => 'extracted', 'entityCount' => 2],
			],
		];

		$this->mockStateService->method('getBatch')->willReturn($batch);

		$result = $this->service->extractNext('abc');

		$this->assertSame('review', $result['batchStatus']);
		$this->assertArrayHasKey('message', $result);

	}//end testExtractNextReturnsReviewWhenAllExtracted()

	/**
	 * Test extractNext processes pending file and returns progress.
	 *
	 * @return void
	 */
	public function testExtractNextProcessesPendingFile(): void {
		$batch = [
			'batchId' => 'abc',
			'status' => 'uploading',
			'files' => [
				['fileId' => 1, 'fileName' => 'a.pdf', 'status' => 'uploaded'],
			],
		];

		$this->mockStateService->method('getBatch')->willReturn($batch);
		$this->mockStateService->method('updateBatch')->willReturnCallback(fn () => null);
		$this->mockAnonService->method('extractAndDetectEntities')
			->willReturn(['entityCount' => 3]);

		$result = $this->service->extractNext('abc');

		$this->assertArrayHasKey('batchStatus', $result);
		$this->assertSame(1, $result['fileId']);

	}//end testExtractNextProcessesPendingFile()

	/**
	 * Test extractNext records error when extraction fails.
	 *
	 * @return void
	 */
	public function testExtractNextRecordsErrorOnExtractionFailure(): void {
		$batch = [
			'batchId' => 'abc',
			'status' => 'uploading',
			'files' => [
				['fileId' => 2, 'fileName' => 'b.pdf', 'status' => 'uploaded'],
			],
		];

		$this->mockStateService->method('getBatch')->willReturn($batch);
		$this->mockStateService->method('updateBatch')->willReturnCallback(fn () => null);
		$this->mockAnonService->method('extractAndDetectEntities')
			->willThrowException(new Exception('Extraction failed'));

		$result = $this->service->extractNext('abc');

		$this->assertNotNull($result['error']);

	}//end testExtractNextRecordsErrorOnExtractionFailure()
}//end class
