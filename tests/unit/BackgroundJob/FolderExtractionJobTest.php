<?php

/**
 * Unit tests for FolderExtractionJob
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\BackgroundJob
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2025 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.DocuDesk.app
 *
 * @spec openspec/changes/anonymisation-folder-output-folder-layout/tasks.md#task-7
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\DocuDesk\Tests\Unit\BackgroundJob;

use Exception;
use OCA\DocuDesk\BackgroundJob\FolderExtractionJob;
use OCA\DocuDesk\Service\AnonymizationService;
use OCA\DocuDesk\Service\BatchStateService;
use OCA\DocuDesk\Service\Conversion\OutputLayoutResolver;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for FolderExtractionJob
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\BackgroundJob
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress                                 PropertyNotSetInConstructor
 * @phpstan-extends                                TestCase
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FolderExtractionJobTest extends TestCase {

	/**
	 * The FolderExtractionJob under test
	 *
	 * @var FolderExtractionJob
	 */
	private FolderExtractionJob $job;

	/**
	 * Mocked AnonymizationService
	 *
	 * @var AnonymizationService|MockObject
	 */
	private AnonymizationService|MockObject $mockAnonService;

	/**
	 * Mocked BatchStateService
	 *
	 * @var BatchStateService|MockObject
	 */
	private BatchStateService|MockObject $mockStateService;

	/**
	 * Mocked LoggerInterface
	 *
	 * @var LoggerInterface|MockObject
	 */
	private LoggerInterface|MockObject $mockLogger;

	/**
	 * Mocked OutputLayoutResolver
	 *
	 * @var OutputLayoutResolver|MockObject
	 */
	private OutputLayoutResolver|MockObject $mockLayoutResolver;

	/**
	 * Mocked IRootFolder
	 *
	 * @var IRootFolder|MockObject
	 */
	private IRootFolder|MockObject $mockRootFolder;

	/**
	 * Set up test environment
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		// phpcs:disable CustomSn.Functions.NamedParameters
		$this->mockAnonService = $this->createMock(AnonymizationService::class);
		$this->mockStateService = $this->createMock(BatchStateService::class);
		$this->mockLogger = $this->createMock(LoggerInterface::class);
		$this->mockLayoutResolver = $this->createMock(OutputLayoutResolver::class);
		$this->mockRootFolder = $this->createMock(IRootFolder::class);
		$mockTime = $this->createMock(ITimeFactory::class);
		// phpcs:enable CustomSn.Functions.NamedParameters

		// phpcs:disable CustomSn.Functions.NamedParameters
		$this->job = new FolderExtractionJob(
			$mockTime,
			$this->mockAnonService,
			$this->mockStateService,
			$this->mockLogger,
			$this->mockLayoutResolver,
			$this->mockRootFolder
		);
		// phpcs:enable CustomSn.Functions.NamedParameters

	}//end setUp()

	/**
	 * Build a mock Folder for use in post-process move stubs.
	 *
	 * @param string $path Absolute path of the folder.
	 *
	 * @return Folder|MockObject
	 */
	private function buildFolder(string $path = '/testuser/files/Dossier'): Folder|MockObject {
		$folder = $this->createMock(Folder::class);
		// phpcs:disable CustomSn.Functions.NamedParameters
		$folder->method('getPath')->willReturn($path);
		$folder->method('nodeExists')->willReturn(false);
		$folder->method('newFolder')->willReturn($this->createMock(Folder::class));
		// phpcs:enable CustomSn.Functions.NamedParameters
		return $folder;
	}//end buildFolder()

	/**
	 * Build a mock anonymized file node for post-process move stubs.
	 *
	 * @param string $name File name returned by getName().
	 *
	 * @return File|MockObject
	 */
	private function buildAnonFile(string $name = 'report_anonymized.pdf'): File|MockObject {
		$anonFile = $this->createMock(File::class);
		// phpcs:disable CustomSn.Functions.NamedParameters
		$anonFile->method('getName')->willReturn($name);
		$anonFile->method('getParent')->willReturn($this->buildFolder());
		// phpcs:enable CustomSn.Functions.NamedParameters
		return $anonFile;
	}//end buildAnonFile()

	/**
	 * Stub the IRootFolder so applyOutputLayout can look up the anonymized file.
	 *
	 * @param int $anonymizedFileId The file ID to return from getById.
	 * @param File|null $anonFile The file node to return, or null for empty.
	 *
	 * @return void
	 */
	private function stubRootFolderForMove(int $anonymizedFileId, ?File $anonFile = null): void {
		// phpcs:disable CustomSn.Functions.NamedParameters
		$mockUserFolder = $this->createMock(Folder::class);
		$mockUserFolder->method('getById')->willReturnCallback(
			function (int $id) use ($anonymizedFileId, $anonFile) {
				if ($id !== $anonymizedFileId) {
					return [];
				}

				if ($anonFile !== null) {
					return [$anonFile];
				}

				return [];
			}
		);
		$this->mockRootFolder->method('getUserFolder')->willReturn($mockUserFolder);
		// phpcs:enable CustomSn.Functions.NamedParameters

	}//end stubRootFolderForMove()

	/**
	 * Test sequential extraction + anonymization of all files
	 *
	 * @return void
	 *
	 * @spec openspec/changes/anonymisation-folder-output-folder-layout/tasks.md#task-7
	 */
	public function testProcessesAllFilesSequentially(): void {
		$batch = [
			'batchId' => 'batch-1',
			'userId' => 'testuser',
			'status' => 'uploading',
			'files' => [
				['fileId' => 1, 'fileName' => 'a.pdf', 'status' => 'uploaded', 'entityCount' => 0, 'error' => null],
				['fileId' => 2, 'fileName' => 'b.pdf', 'status' => 'uploaded', 'entityCount' => 0, 'error' => null],
			],
		];

		$this->mockStateService->method('getBatch')->willReturn($batch);

		// phpcs:disable CustomSn.Functions.NamedParameters
		$this->mockAnonService->expects($this->exactly(2))
			->method('extractAndDetectEntities')
			->willReturnMap(
				[
					[1, ['entityCount' => 5, 'entities' => [['type' => 'PERSON', 'value' => 'Alice']]]],
					[2, ['entityCount' => 3, 'entities' => [['type' => 'EMAIL', 'value' => 'b@b.nl']]]],
				]
			);

		$this->mockAnonService->expects($this->exactly(2))
			->method('anonymizeDocument')
			->willReturn(['anonymizedFileId' => null, 'anonymizedFilePath' => '/legacy/path.pdf', 'replacementCount' => 1]);
		// phpcs:enable CustomSn.Functions.NamedParameters

		// Capture all updateBatch calls to verify state transitions.
		$updates = [];
		$this->mockStateService->method('updateBatch')
			->willReturnCallback(
				function (string $id, array $b) use (&$updates) {
					$updates[] = $b;
				}
			);

		// Use reflection to call protected run().
		$ref = new \ReflectionMethod($this->job, 'run');
		$ref->setAccessible(true);
		$ref->invoke($this->job, ['batchId' => 'batch-1']);

		// Final update should be 'completed', files 'anonymized'.
		$last = end($updates);
		// phpcs:disable CustomSn.Functions.NamedParameters
		$this->assertEquals('completed', $last['status']);
		$this->assertEquals('anonymized', $last['files'][0]['status']);
		$this->assertEquals('anonymized', $last['files'][1]['status']);
		$this->assertEquals(5, $last['files'][0]['entityCount']);
		// phpcs:enable CustomSn.Functions.NamedParameters

	}//end testProcessesAllFilesSequentially()

	/**
	 * Test that a single file extraction failure does not abort the batch
	 *
	 * @return void
	 *
	 * @spec openspec/changes/anonymisation-folder-output-folder-layout/tasks.md#task-7
	 */
	public function testSingleFileExtractionFailureDoesNotAbortBatch(): void {
		$batch = [
			'batchId' => 'batch-2',
			'userId' => 'testuser',
			'status' => 'uploading',
			'files' => [
				['fileId' => 1, 'fileName' => 'a.pdf', 'status' => 'uploaded', 'entityCount' => 0, 'error' => null],
				['fileId' => 2, 'fileName' => 'b.pdf', 'status' => 'uploaded', 'entityCount' => 0, 'error' => null],
				['fileId' => 3, 'fileName' => 'c.pdf', 'status' => 'uploaded', 'entityCount' => 0, 'error' => null],
			],
		];

		$this->mockStateService->method('getBatch')->willReturn($batch);

		$this->mockAnonService->method('extractAndDetectEntities')
			->willReturnCallback(
				function (int $fileId) {
					if ($fileId === 2) {
						throw new Exception('Extraction failed');
					}

					return ['entityCount' => 3, 'entities' => []];
				}
			);

		// phpcs:disable CustomSn.Functions.NamedParameters
		$this->mockAnonService->method('anonymizeDocument')
			->willReturn(['anonymizedFileId' => null, 'anonymizedFilePath' => '/legacy/p.pdf', 'replacementCount' => 1]);
		// phpcs:enable CustomSn.Functions.NamedParameters

		$updates = [];
		$this->mockStateService->method('updateBatch')
			->willReturnCallback(
				function (string $id, array $b) use (&$updates) {
					$updates[] = $b;
				}
			);

		$ref = new \ReflectionMethod($this->job, 'run');
		$ref->setAccessible(true);
		$ref->invoke($this->job, ['batchId' => 'batch-2']);

		$last = end($updates);
		// phpcs:disable CustomSn.Functions.NamedParameters
		$this->assertEquals('completed', $last['status']);
		$this->assertEquals('anonymized', $last['files'][0]['status']);
		$this->assertEquals('error', $last['files'][1]['status']);
		$this->assertEquals('anonymized', $last['files'][2]['status']);
		$this->assertNotEmpty($last['files'][1]['error']);
		// phpcs:enable CustomSn.Functions.NamedParameters

	}//end testSingleFileExtractionFailureDoesNotAbortBatch()

	/**
	 * Test that missing batchId logs error and returns
	 *
	 * @return void
	 */
	public function testMissingBatchIdLogsError(): void {
		$this->mockLogger->expects($this->once())->method('error');
		$this->mockStateService->expects($this->never())->method('getBatch');

		$ref = new \ReflectionMethod($this->job, 'run');
		$ref->setAccessible(true);
		$ref->invoke($this->job, []);

	}//end testMissingBatchIdLogsError()

	/**
	 * Test that expired batch logs error and returns
	 *
	 * @return void
	 */
	public function testExpiredBatchLogsError(): void {
		$this->mockStateService->method('getBatch')->willReturn(null);
		$this->mockLogger->expects($this->once())->method('error');
		$this->mockAnonService->expects($this->never())->method('extractAndDetectEntities');

		$ref = new \ReflectionMethod($this->job, 'run');
		$ref->setAccessible(true);
		$ref->invoke($this->job, ['batchId' => 'expired-batch']);

	}//end testExpiredBatchLogsError()

	/**
	 * Test status transitions through extracting to completed
	 *
	 * @return void
	 *
	 * @spec openspec/changes/anonymisation-folder-output-folder-layout/tasks.md#task-7
	 */
	public function testStatusTransitionsExtractingToCompleted(): void {
		$batch = [
			'batchId' => 'batch-3',
			'userId' => 'testuser',
			'status' => 'uploading',
			'files' => [
				['fileId' => 1, 'fileName' => 'a.pdf', 'status' => 'uploaded', 'entityCount' => 0, 'error' => null],
			],
		];

		$this->mockStateService->method('getBatch')->willReturn($batch);
		$this->mockAnonService->method('extractAndDetectEntities')->willReturn(['entityCount' => 1, 'entities' => []]);
		// phpcs:disable CustomSn.Functions.NamedParameters
		$this->mockAnonService->method('anonymizeDocument')
			->willReturn(['anonymizedFileId' => null, 'anonymizedFilePath' => '/p.pdf', 'replacementCount' => 0]);
		// phpcs:enable CustomSn.Functions.NamedParameters

		$statuses = [];
		$this->mockStateService->method('updateBatch')
			->willReturnCallback(
				function (string $id, array $b) use (&$statuses) {
					$statuses[] = $b['status'];
				}
			);

		$ref = new \ReflectionMethod($this->job, 'run');
		$ref->setAccessible(true);
		$ref->invoke($this->job, ['batchId' => 'batch-3']);

		// phpcs:disable CustomSn.Functions.NamedParameters
		$this->assertEquals('extracting', $statuses[0]);
		$this->assertEquals('completed', end($statuses));
		// phpcs:enable CustomSn.Functions.NamedParameters

	}//end testStatusTransitionsExtractingToCompleted()

	/**
	 * Test that _anonymized-suffixed files are skipped during extraction.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/anonymisation-folder-output-folder-layout/tasks.md#task-3
	 */
	public function testSkipsLegacyAnonymizedSuffixedFiles(): void {
		$batch = [
			'batchId' => 'batch-filter',
			'userId' => 'testuser',
			'status' => 'uploading',
			'files' => [
				['fileId' => 1, 'fileName' => 'report.pdf',            'status' => 'uploaded', 'entityCount' => 0, 'error' => null],
				['fileId' => 2, 'fileName' => 'report_anonymized.pdf', 'status' => 'uploaded', 'entityCount' => 0, 'error' => null],
				['fileId' => 3, 'fileName' => 'letter.docx',           'status' => 'uploaded', 'entityCount' => 0, 'error' => null],
			],
		];

		$this->mockStateService->method('getBatch')->willReturn($batch);

		// hasAnonymizedSuffix returns true only for the legacy file.
		$this->mockLayoutResolver->method('hasAnonymizedSuffix')
			->willReturnCallback(
				function (string $fileName): bool {
					return str_ends_with(pathinfo($fileName, PATHINFO_FILENAME), '_anonymized');
				}
			);

		// extractAndDetectEntities must NOT be called for the _anonymized file.
		$this->mockAnonService->expects($this->exactly(2))
			->method('extractAndDetectEntities')
			->willReturn(['entityCount' => 2, 'entities' => []]);

		// phpcs:disable CustomSn.Functions.NamedParameters
		$this->mockAnonService->method('anonymizeDocument')
			->willReturn(['anonymizedFileId' => null, 'anonymizedFilePath' => '/p.pdf', 'replacementCount' => 0]);
		// phpcs:enable CustomSn.Functions.NamedParameters

		$updates = [];
		$this->mockStateService->method('updateBatch')
			->willReturnCallback(
				function (string $id, array $b) use (&$updates) {
					$updates[] = $b;
				}
			);

		$ref = new \ReflectionMethod($this->job, 'run');
		$ref->setAccessible(true);
		$ref->invoke($this->job, ['batchId' => 'batch-filter']);

		$last = end($updates);
		// phpcs:disable CustomSn.Functions.NamedParameters
		$this->assertEquals('anonymized', $last['files'][0]['status'], 'clean file should be anonymized');
		$this->assertEquals('skipped', $last['files'][1]['status'], 'legacy file should be skipped');
		$this->assertEquals('anonymized', $last['files'][2]['status'], 'clean file should be anonymized');
		// phpcs:enable CustomSn.Functions.NamedParameters

	}//end testSkipsLegacyAnonymizedSuffixedFiles()

	/**
	 * Test that retry is idempotent: files already in 'anonymized' status are skipped.
	 *
	 * When the shutdown handler ran first and anonymized file 1, the job
	 * running as a fallback skips it (not 'uploaded') and processes only file 2.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/anonymisation-folder-output-folder-layout/tasks.md#task-2
	 */
	public function testRetryIsIdempotentForAlreadyAnonymizedFiles(): void {
		$batch = [
			'batchId' => 'batch-retry',
			'userId' => 'testuser',
			'status' => 'extracting',
			'files' => [
				['fileId' => 1, 'fileName' => 'a.pdf', 'status' => 'anonymized', 'entityCount' => 3, 'error' => null],
				['fileId' => 2, 'fileName' => 'b.pdf', 'status' => 'uploaded',   'entityCount' => 0, 'error' => null],
			],
		];

		$this->mockStateService->method('getBatch')->willReturn($batch);

		// Only the un-processed file should be extracted and anonymized.
		$this->mockAnonService->expects($this->once())
			->method('extractAndDetectEntities')
			->with(2)
			->willReturn(['entityCount' => 5, 'entities' => []]);

		// phpcs:disable CustomSn.Functions.NamedParameters
		$this->mockAnonService->expects($this->once())
			->method('anonymizeDocument')
			->willReturn(['anonymizedFileId' => null, 'anonymizedFilePath' => '/p.pdf', 'replacementCount' => 0]);
		// phpcs:enable CustomSn.Functions.NamedParameters

		$updates = [];
		$this->mockStateService->method('updateBatch')
			->willReturnCallback(
				function (string $id, array $b) use (&$updates) {
					$updates[] = $b;
				}
			);

		$ref = new \ReflectionMethod($this->job, 'run');
		$ref->setAccessible(true);
		$ref->invoke($this->job, ['batchId' => 'batch-retry']);

		$last = end($updates);
		// phpcs:disable CustomSn.Functions.NamedParameters
		$this->assertEquals('completed', $last['status']);
		$this->assertEquals('anonymized', $last['files'][0]['status'], 'already-anonymized file unchanged');
		$this->assertEquals('anonymized', $last['files'][1]['status'], 'newly processed file anonymized');
		// phpcs:enable CustomSn.Functions.NamedParameters

	}//end testRetryIsIdempotentForAlreadyAnonymizedFiles()

	/**
	 * Test that the job applies the output layout and records the target path.
	 *
	 * The anonymized file is moved from OR's legacy path to the subfolder.
	 * anonymizedFilePath in the file entry reflects the new target path.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/anonymisation-folder-output-folder-layout/tasks.md#task-2
	 * @spec openspec/changes/anonymisation-folder-output-folder-layout/tasks.md#task-4
	 */
	public function testJobAppliesOutputLayoutAndRecordsTargetPath(): void {
		$batch = [
			'batchId' => 'batch-layout',
			'userId' => 'testuser',
			'status' => 'uploading',
			'files' => [
				['fileId' => 10, 'fileName' => 'report.pdf', 'status' => 'uploaded', 'entityCount' => 0, 'error' => null],
			],
		];

		$this->mockStateService->method('getBatch')->willReturn($batch);
		$this->mockAnonService->method('extractAndDetectEntities')
			->willReturn(['entityCount' => 2, 'entities' => []]);

		$anonFileId = 999;
		$legacyPath = '/testuser/files/Dossier/report_anonymized.pdf';
		$targetPath = '/testuser/files/Dossier/anonymised/report.pdf';

		// phpcs:disable CustomSn.Functions.NamedParameters
		$this->mockAnonService->method('anonymizeDocument')
			->willReturn(
				[
					'anonymizedFileId' => $anonFileId,
					'anonymizedFilePath' => $legacyPath,
					'replacementCount' => 3,
				]
			);
		// phpcs:enable CustomSn.Functions.NamedParameters

		// Set up the anonFile node.
		$anonFile = $this->buildAnonFile('report_anonymized.pdf');
		$subfolder = $this->createMock(Folder::class);
		// phpcs:disable CustomSn.Functions.NamedParameters
		$subfolder->method('nodeExists')->willReturn(false);
		$sourceFolder = $this->createMock(Folder::class);
		$sourceFolder->method('getPath')->willReturn('/testuser/files/Dossier');
		$sourceFolder->method('nodeExists')->willReturn(false);
		$sourceFolder->method('newFolder')->willReturn($subfolder);
		$anonFile->method('getParent')->willReturn($sourceFolder);
		$anonFile->expects($this->once())->method('move')->with($targetPath);
		// phpcs:enable CustomSn.Functions.NamedParameters

		$this->stubRootFolderForMove(anonymizedFileId: $anonFileId, anonFile: $anonFile);

		// Resolver returns 'anonymised' subfolder and strips no suffix here.
		$this->mockLayoutResolver->method('readSubfolderName')->willReturn('anonymised');
		$this->mockLayoutResolver->method('resolveBatchDestination')->willReturn($targetPath);

		$updates = [];
		$this->mockStateService->method('updateBatch')
			->willReturnCallback(
				function (string $id, array $b) use (&$updates) {
					$updates[] = $b;
				}
			);

		$ref = new \ReflectionMethod($this->job, 'run');
		$ref->setAccessible(true);
		$ref->invoke($this->job, ['batchId' => 'batch-layout']);

		$last = end($updates);
		// phpcs:disable CustomSn.Functions.NamedParameters
		$this->assertEquals('anonymized', $last['files'][0]['status']);
		$this->assertEquals($targetPath, $last['files'][0]['anonymizedFilePath']);
		$this->assertArrayNotHasKey('warning', $last['files'][0]);
		// phpcs:enable CustomSn.Functions.NamedParameters

	}//end testJobAppliesOutputLayoutAndRecordsTargetPath()

	/**
	 * Test that a move failure preserves the file at the legacy path with a warning.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/anonymisation-folder-output-folder-layout/tasks.md#task-2
	 */
	public function testMoveFailurePreservesLegacyPathWithWarning(): void {
		$batch = [
			'batchId' => 'batch-move-fail',
			'userId' => 'testuser',
			'status' => 'uploading',
			'files' => [
				['fileId' => 20, 'fileName' => 'letter.pdf', 'status' => 'uploaded', 'entityCount' => 0, 'error' => null],
			],
		];

		$this->mockStateService->method('getBatch')->willReturn($batch);
		$this->mockAnonService->method('extractAndDetectEntities')
			->willReturn(['entityCount' => 1, 'entities' => []]);

		$anonFileId = 888;
		$legacyPath = '/testuser/files/Dossier/letter_anonymized.pdf';
		$targetPath = '/testuser/files/Dossier/anonymised/letter.pdf';

		// phpcs:disable CustomSn.Functions.NamedParameters
		$this->mockAnonService->method('anonymizeDocument')
			->willReturn(
				[
					'anonymizedFileId' => $anonFileId,
					'anonymizedFilePath' => $legacyPath,
					'replacementCount' => 1,
				]
			);
		// phpcs:enable CustomSn.Functions.NamedParameters

		// anonFile throws on move() — simulates move failure.
		$anonFile = $this->buildAnonFile('letter_anonymized.pdf');
		// phpcs:disable CustomSn.Functions.NamedParameters
		$sourceFolder = $this->createMock(Folder::class);
		$sourceFolder->method('getPath')->willReturn('/testuser/files/Dossier');
		$sourceFolder->method('nodeExists')->willReturn(false);
		$sourceFolder->method('newFolder')->willReturn($this->createMock(Folder::class));
		$anonFile->method('getParent')->willReturn($sourceFolder);
		$anonFile->method('move')->willThrowException(new \Exception('Move failed'));
		// phpcs:enable CustomSn.Functions.NamedParameters

		$this->stubRootFolderForMove(anonymizedFileId: $anonFileId, anonFile: $anonFile);
		$this->mockLayoutResolver->method('readSubfolderName')->willReturn('anonymised');
		$this->mockLayoutResolver->method('resolveBatchDestination')->willReturn($targetPath);

		$updates = [];
		$this->mockStateService->method('updateBatch')
			->willReturnCallback(
				function (string $id, array $b) use (&$updates) {
					$updates[] = $b;
				}
			);

		$ref = new \ReflectionMethod($this->job, 'run');
		$ref->setAccessible(true);
		$ref->invoke($this->job, ['batchId' => 'batch-move-fail']);

		$last = end($updates);
		// phpcs:disable CustomSn.Functions.NamedParameters
		$this->assertEquals('anonymized', $last['files'][0]['status'], 'file is still anonymized despite move failure');
		$this->assertEquals($legacyPath, $last['files'][0]['anonymizedFilePath'], 'path stays at legacy');
		$this->assertArrayHasKey('warning', $last['files'][0]);
		$this->assertEquals('MOVE_FAILED', $last['files'][0]['warning']['code']);
		// phpcs:enable CustomSn.Functions.NamedParameters

	}//end testMoveFailurePreservesLegacyPathWithWarning()

	/**
	 * Test that the anonymization step failure marks the file as 'error'.
	 *
	 * Extraction succeeds but anonymizeDocument throws.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/anonymisation-folder-output-folder-layout/tasks.md#task-7
	 */
	public function testAnonymizationFailureMarksFileAsError(): void {
		$batch = [
			'batchId' => 'batch-anon-fail',
			'userId' => 'testuser',
			'status' => 'uploading',
			'files' => [
				['fileId' => 30, 'fileName' => 'doc.pdf', 'status' => 'uploaded', 'entityCount' => 0, 'error' => null],
			],
		];

		$this->mockStateService->method('getBatch')->willReturn($batch);
		$this->mockAnonService->method('extractAndDetectEntities')
			->willReturn(['entityCount' => 1, 'entities' => []]);

		$this->mockAnonService->method('anonymizeDocument')
			->willThrowException(new Exception('Backend unavailable'));

		$updates = [];
		$this->mockStateService->method('updateBatch')
			->willReturnCallback(
				function (string $id, array $b) use (&$updates) {
					$updates[] = $b;
				}
			);

		$ref = new \ReflectionMethod($this->job, 'run');
		$ref->setAccessible(true);
		$ref->invoke($this->job, ['batchId' => 'batch-anon-fail']);

		$last = end($updates);
		// phpcs:disable CustomSn.Functions.NamedParameters
		$this->assertEquals('completed', $last['status']);
		$this->assertEquals('error', $last['files'][0]['status']);
		$this->assertStringContainsString('Backend unavailable', $last['files'][0]['error']);
		// phpcs:enable CustomSn.Functions.NamedParameters

	}//end testAnonymizationFailureMarksFileAsError()
}//end class
