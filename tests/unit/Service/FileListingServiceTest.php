<?php

/**
 * Unit tests for FileListingService
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2025 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 */

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\FileEntityStatsService;
use OCA\Filinq\Service\FileListingService;
use OCA\Filinq\Service\FileUploadService;
use OCA\Filinq\Tests\Unit\Service\Ocr\OcrDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for FileListingService
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class FileListingServiceTest extends TestCase {

	use OcrDoubles;

	/**
	 * @var FileListingService
	 */
	private FileListingService $service;

	/**
	 * @var LoggerInterface|MockObject
	 */
	private LoggerInterface|MockObject $mockLogger;

	/**
	 * @var FileUploadService|MockObject
	 */
	private FileUploadService|MockObject $mockFileUploadService;

	/**
	 * @var FileEntityStatsService|MockObject
	 */
	private FileEntityStatsService|MockObject $mockEntityStatsService;

	/**
	 * Set up test environment
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->mockLogger = $this->createMock(LoggerInterface::class);
		$this->mockFileUploadService = $this->createMock(FileUploadService::class);
		$this->mockEntityStatsService = $this->createMock(FileEntityStatsService::class);

		$this->service = new FileListingService(
			$this->mockLogger,
			$this->mockFileUploadService,
			$this->mockEntityStatsService,
			$this->ocrResultRepository(),
			$this->ocrRunService($this->ocrService())
		);

	}//end setUp()

	/**
	 * Test uploadFile delegates to FileUploadService
	 *
	 * @return void
	 */
	public function testUploadFileDelegates(): void {
		$expected = [
			'fileId' => 1,
			'filePath' => '/path/test.pdf',
			'fileName' => 'test.pdf',
			'fileSize' => 1024,
		];

		$this->mockFileUploadService->method('uploadFile')
			->with('test.pdf', 'content')
			->willReturn($expected);

		$result = $this->service->uploadFile('test.pdf', 'content');
		$this->assertEquals($expected, $result);

	}//end testUploadFileDelegates()

	/**
	 * Test listProcessedFiles throws when user not logged in
	 *
	 * @return void
	 */
	public function testListProcessedFilesThrowsOnError(): void {
		$this->expectException(\Exception::class);

		$this->mockFileUploadService->method('getCurrentUserId')
			->willThrowException(new \Exception('No user is currently logged in.', 401));

		$this->service->listProcessedFiles();

	}//end testListProcessedFilesThrowsOnError()

	/**
	 * Test listProcessedFiles returns empty for empty folder
	 *
	 * @return void
	 */
	public function testListProcessedFilesReturnsEmptyForEmptyFolder(): void {
		$this->mockFileUploadService->method('getCurrentUserId')
			->willReturn('admin');

		$mockFolder = $this->createMock(\OCP\Files\Folder::class);
		$mockFolder->method('getDirectoryListing')
			->willReturn([]);

		$this->mockFileUploadService->method('getFilinqFolder')
			->willReturn($mockFolder);

		$this->mockEntityStatsService->method('tryGetEntityRelationMapper')
			->willReturn(null);
		$this->mockEntityStatsService->method('tryGetRiskLevelService')
			->willReturn(null);

		$result = $this->service->listProcessedFiles();
		$this->assertIsArray($result);
		$this->assertEmpty($result);

	}//end testListProcessedFilesReturnsEmptyForEmptyFolder()

	/**
	 * A scan that was OCR'd reports its real confidence; a born-digital PDF
	 * that was only extracted is no longer reported as OCR'd.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/archive/2026-09-29-ocr-trigger-surface/tasks.md#task-2.4
	 */
	public function testTheListingReflectsRealOcrRunsOnly(): void {
		$this->ocrRunService($this->ocrService())->run(file: $this->ocrFile(id: 812004), trigger: 'manual');
		$service = new FileListingService(
			$this->mockLogger,
			$this->mockFileUploadService,
			$this->mockEntityStatsService,
			$this->ocrResultRepository(),
			$this->ocrRunService($this->ocrService())
		);

		$this->mockFileUploadService->method('getCurrentUserId')->willReturn('admin');
		$folder = $this->createMock(\OCP\Files\Folder::class);
		$folder->method('getDirectoryListing')->willReturn(
			[$this->ocrFile(id: 812004), $this->ocrFile(id: 7), $this->ocrFile(id: 8, mimeType: 'text/plain')]
		);
		$this->mockFileUploadService->method('getFilinqFolder')->willReturn($folder);
		$this->mockEntityStatsService->method('getEntityStats')->willReturn(
			['entityCount' => 2, 'anonymizedCount' => 0, 'status' => 'extracted']
		);

		$rows = [];
		foreach ($service->listProcessedFiles() as $row) {
			$rows[$row['fileId']] = $row;
		}

		$this->assertTrue($rows[812004]['ocrProcessed']);
		$this->assertSame(91.4, $rows[812004]['ocrConfidence']);
		$this->assertFalse($rows[7]['ocrProcessed']);
		$this->assertNull($rows[7]['ocrConfidence']);
		$this->assertTrue($rows[7]['ocrAvailable']);
		$this->assertFalse($rows[8]['ocrAvailable']);

	}//end testTheListingReflectsRealOcrRunsOnly()
}//end class
