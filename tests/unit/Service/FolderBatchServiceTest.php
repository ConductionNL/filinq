<?php

/**
 * Unit tests for FolderBatchService
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2025 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.DocuDesk.app
 *
 * @spec openspec/changes/folder-batch-accept-folder-id/tasks.md#task-3
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\DocuDesk\Tests\Unit\Service;

use Exception;
use OCA\DocuDesk\BackgroundJob\FolderExtractionJob;
use OCA\DocuDesk\Service\AnonymizationService;
use OCA\DocuDesk\Service\BatchStateService;
use OCA\DocuDesk\Service\ConfidentialityLabelService;
use OCA\DocuDesk\Service\Conversion\OutputLayoutResolver;
use OCA\DocuDesk\Service\FolderBatchService;
use OCP\BackgroundJob\IJobList;
use OCP\Constants;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\IAppConfig;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for FolderBatchService
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress                                 PropertyNotSetInConstructor
 * @phpstan-extends                                TestCase
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FolderBatchServiceTest extends TestCase {

	/**
	 * The FolderBatchService under test
	 *
	 * @var FolderBatchService
	 */
	private FolderBatchService $service;

	/**
	 * Mocked LoggerInterface
	 *
	 * @var LoggerInterface|MockObject
	 */
	private LoggerInterface|MockObject $mockLogger;

	/**
	 * Mocked IRootFolder
	 *
	 * @var IRootFolder|MockObject
	 */
	private IRootFolder|MockObject $mockRootFolder;

	/**
	 * Mocked IUserSession
	 *
	 * @var IUserSession|MockObject
	 */
	private IUserSession|MockObject $mockUserSession;

	/**
	 * Mocked BatchStateService
	 *
	 * @var BatchStateService|MockObject
	 */
	private BatchStateService|MockObject $mockStateService;

	/**
	 * Mocked IJobList
	 *
	 * @var IJobList|MockObject
	 */
	private IJobList|MockObject $mockJobList;

	/**
	 * Real output-layout resolver wired to a stub IAppConfig that returns the
	 * default subfolder name. Final class so we can't mock it; we construct
	 * the real one and rely on its pure helpers
	 * (`isLegacyAnonymizedOutput`).
	 *
	 * @var OutputLayoutResolver
	 */
	private OutputLayoutResolver $layout;

	/**
	 * Mocked ConfidentialityLabelService — default mock behaviour (null
	 * return) is the "no signal" case (files-confidential-labels).
	 *
	 * @var ConfidentialityLabelService|MockObject
	 */
	private ConfidentialityLabelService|MockObject $mockConfidentialityLabel;

	/**
	 * Mocked IAppConfig — default mock behaviour (false) keeps the
	 * confidentiality priority hint off, matching the pre-change ordering
	 * (files-confidential-labels).
	 *
	 * @var IAppConfig|MockObject
	 */
	private IAppConfig|MockObject $mockAppConfig;

	/**
	 * Set up test environment
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->mockLogger = $this->createMock(LoggerInterface::class);
		$this->mockRootFolder = $this->createMock(IRootFolder::class);
		$this->mockUserSession = $this->createMock(IUserSession::class);
		$this->mockStateService = $this->createMock(BatchStateService::class);
		$this->mockJobList = $this->createMock(IJobList::class);
		$this->mockConfidentialityLabel = $this->createMock(ConfidentialityLabelService::class);
		$this->mockAppConfig = $this->createMock(IAppConfig::class);

		$mockUser = $this->createMock(IUser::class);
		$mockUser->method('getUID')->willReturn('testuser');
		$this->mockUserSession->method('getUser')->willReturn($mockUser);

		$this->layout = $this->makeLayoutResolver();

		$this->service = new FolderBatchService(
			$this->mockLogger,
			$this->mockRootFolder,
			$this->mockUserSession,
			$this->mockStateService,
			$this->mockJobList,
			$this->createMock(AnonymizationService::class),
			$this->layout,
			$this->mockConfidentialityLabel,
			$this->mockAppConfig
		);

	}//end setUp()

	/**
	 * Build a mocked Folder with a given directory listing, path, id and permissions.
	 *
	 * @param array $children Child nodes for getDirectoryListing()
	 * @param int $id The node id
	 * @param int $permissions Folder permissions (default: all)
	 * @param string $absolutePath Absolute path returned by getPath()
	 *
	 * @return Folder|MockObject
	 */
	private function buildFolder(
		array $children,
		int $id = 500,
		int $permissions = Constants::PERMISSION_ALL,
		string $absolutePath = '/testuser/files/Documents/WOB',
	): Folder|MockObject {
		$folder = $this->createMock(Folder::class);
		$folder->method('getDirectoryListing')->willReturn($children);
		$folder->method('getId')->willReturn($id);
		$folder->method('getPermissions')->willReturn($permissions);
		$folder->method('getPath')->willReturn($absolutePath);
		return $folder;
	}//end buildFolder()

	/**
	 * Build a mocked File child.
	 *
	 * @param int $id File id
	 * @param string $name File name
	 *
	 * @return File|MockObject
	 */
	private function buildFile(int $id, string $name): File|MockObject {
		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn($id);
		$file->method('getName')->willReturn($name);
		return $file;
	}//end buildFile()

	/**
	 * Build a user folder mock wrapping a target folder for get()/getById() lookups.
	 *
	 * @param array $getByIdNodes Array returned by getById(), or null to not stub
	 * @param Node|null $getPathNode Node returned by get(), or null to not stub
	 * @param bool $pathThrowsNotFound When true, get() throws NotFoundException
	 * @param string $relativePath The relative path reported by getRelativePath()
	 *
	 * @return Folder|MockObject
	 */
	private function buildUserFolder(
		?array $getByIdNodes = null,
		?Node $getPathNode = null,
		bool $pathThrowsNotFound = false,
		string $relativePath = '/Documents/WOB',
	): Folder|MockObject {
		$userFolder = $this->createMock(Folder::class);

		if ($getByIdNodes !== null) {
			$userFolder->method('getById')->willReturn($getByIdNodes);
		}

		if ($pathThrowsNotFound === true) {
			$userFolder->method('get')->willThrowException(new NotFoundException());
		} elseif ($getPathNode !== null) {
			$userFolder->method('get')->willReturn($getPathNode);
		}

		$userFolder->method('getRelativePath')->willReturn($relativePath);

		return $userFolder;
	}//end buildUserFolder()

	/**
	 * Construct a real OutputLayoutResolver wired to a stub IAppConfig that
	 * returns the default subfolder name. The resolver is `final`; we
	 * exercise its pure helpers (`isLegacyAnonymizedOutput`,
	 * `stripLegacyAnonymizedSuffix`) which depend on neither config nor
	 * logger state.
	 *
	 * @return OutputLayoutResolver
	 */
	private function makeLayoutResolver(): OutputLayoutResolver {
		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturn(OutputLayoutResolver::DEFAULT_SUBFOLDER_NAME);
		return new OutputLayoutResolver($config, $this->createMock(LoggerInterface::class));
	}//end makeLayoutResolver()

	/**
	 * Test successful folder batch creation via path — existing behaviour preserved,
	 * now also stores the resolved folderId alongside folderPath.
	 *
	 * @return void
	 */
	public function testCreateFolderBatchByPathHappyPath(): void {
		$file1 = $this->buildFile(101, 'report.pdf');
		$file2 = $this->buildFile(102, 'letter.docx');

		$folder = $this->buildFolder([$file1, $file2], 500);

		$userFolder = $this->buildUserFolder(null, $folder, false, '/Documents/WOB');
		$this->mockRootFolder->method('getUserFolder')->willReturn($userFolder);

		$this->mockStateService->method('getMaxFiles')->willReturn(100);
		$this->mockStateService->method('createBatch')->willReturn(
			[
				'batchId' => 'test-uuid',
				'userId' => 'testuser',
				'status' => 'uploading',
				'files' => [
					['fileId' => 101, 'fileName' => 'report.pdf', 'status' => 'uploaded'],
					['fileId' => 102, 'fileName' => 'letter.docx', 'status' => 'uploaded'],
				],
			]
		);

		$this->mockStateService->expects($this->once())->method('updateBatch');
		$this->mockJobList->expects($this->once())->method('add')
			->with(FolderExtractionJob::class, ['batchId' => 'test-uuid']);

		$result = $this->service->createFolderBatch(null, '/Documents/WOB');

		$this->assertEquals('test-uuid', $result['batchId']);
		$this->assertEquals('folder', $result['sourceType']);
		$this->assertEquals('/Documents/WOB', $result['folderPath']);
		$this->assertEquals(500, $result['folderId']);

	}//end testCreateFolderBatchByPathHappyPath()

	/**
	 * Test successful folder batch creation via ID — node resolved via getById,
	 * both identifiers captured on the batch.
	 *
	 * @return void
	 */
	public function testCreateFolderBatchByIdHappyPath(): void {
		$file1 = $this->buildFile(201, 'a.pdf');
		$folder = $this->buildFolder([$file1], 12345);

		$userFolder = $this->buildUserFolder([$folder], null, false, '/Shared/Cases');
		$this->mockRootFolder->method('getUserFolder')->willReturn($userFolder);

		$this->mockStateService->method('getMaxFiles')->willReturn(100);
		$this->mockStateService->method('createBatch')->willReturn(
			[
				'batchId' => 'id-uuid',
				'userId' => 'testuser',
				'status' => 'uploading',
				'files' => [['fileId' => 201, 'fileName' => 'a.pdf', 'status' => 'uploaded']],
			]
		);

		$result = $this->service->createFolderBatch(12345, null);

		$this->assertEquals('id-uuid', $result['batchId']);
		$this->assertEquals(12345, $result['folderId']);
		$this->assertEquals('/Shared/Cases', $result['folderPath']);

	}//end testCreateFolderBatchByIdHappyPath()

	/**
	 * Test that when getById returns multiple mounts (read-only + writable)
	 * the writable one is preferred and its path is stored.
	 *
	 * @return void
	 */
	public function testCreateFolderBatchByIdPrefersWritableMount(): void {
		$file1 = $this->buildFile(301, 'x.pdf');

		$readOnlyFolder = $this->buildFolder(
			[$file1],
			12345,
			Constants::PERMISSION_READ,
			'/testuser/files/Groupfolders/Legal'
		);
		$writableFolder = $this->buildFolder(
			[$file1],
			12345,
			Constants::PERMISSION_ALL,
			'/testuser/files/Shared/Cases'
		);

		// Return read-only first so the code actually has to iterate to find the writable one.
		$userFolder = $this->buildUserFolder([$readOnlyFolder, $writableFolder], null, false, '/Shared/Cases');
		$this->mockRootFolder->method('getUserFolder')->willReturn($userFolder);

		// Assert that getRelativePath is called against the WRITABLE mount's path,
		// not the read-only one.
		$userFolder->expects($this->atLeastOnce())
			->method('getRelativePath')
			->with('/testuser/files/Shared/Cases')
			->willReturn('/Shared/Cases');

		$this->mockStateService->method('getMaxFiles')->willReturn(100);
		$this->mockStateService->method('createBatch')->willReturn(
			[
				'batchId' => 'uuid',
				'userId' => 'testuser',
				'status' => 'uploading',
				'files' => [['fileId' => 301, 'fileName' => 'x.pdf', 'status' => 'uploaded']],
			]
		);

		$result = $this->service->createFolderBatch(12345, null);

		$this->assertEquals('/Shared/Cases', $result['folderPath']);

	}//end testCreateFolderBatchByIdPrefersWritableMount()

	/**
	 * Test that when no writable mount exists, the first readable node is used.
	 *
	 * @return void
	 */
	public function testCreateFolderBatchByIdFallsBackToReadableWhenNoneWritable(): void {
		$file1 = $this->buildFile(401, 'y.pdf');

		$readOnlyFolder = $this->buildFolder(
			[$file1],
			12345,
			Constants::PERMISSION_READ,
			'/testuser/files/Readonly'
		);

		$userFolder = $this->buildUserFolder([$readOnlyFolder], null, false, '/Readonly');
		$this->mockRootFolder->method('getUserFolder')->willReturn($userFolder);

		$this->mockStateService->method('getMaxFiles')->willReturn(100);
		$this->mockStateService->method('createBatch')->willReturn(
			[
				'batchId' => 'uuid',
				'userId' => 'testuser',
				'status' => 'uploading',
				'files' => [['fileId' => 401, 'fileName' => 'y.pdf', 'status' => 'uploaded']],
			]
		);

		$result = $this->service->createFolderBatch(12345, null);

		$this->assertEquals('/Readonly', $result['folderPath']);
		$this->assertEquals(12345, $result['folderId']);

	}//end testCreateFolderBatchByIdFallsBackToReadableWhenNoneWritable()

	/**
	 * Test that a folder id that resolves to no nodes returns 404.
	 *
	 * @return void
	 */
	public function testCreateFolderBatchByIdReturns404WhenIdNotResolvable(): void {
		$userFolder = $this->buildUserFolder([], null);
		$this->mockRootFolder->method('getUserFolder')->willReturn($userFolder);

		$this->expectException(Exception::class);
		$this->expectExceptionCode(404);
		$this->expectExceptionMessage('Folder not found');

		$this->service->createFolderBatch(99999, null);

	}//end testCreateFolderBatchByIdReturns404WhenIdNotResolvable()

	/**
	 * Test that a folder id resolving to a File (not Folder) returns 400.
	 *
	 * @return void
	 */
	public function testCreateFolderBatchByIdReturns400WhenNodeIsFile(): void {
		$fileNode = $this->createMock(File::class);
		$fileNode->method('getId')->willReturn(12345);
		$fileNode->method('getPermissions')->willReturn(Constants::PERMISSION_ALL);

		$userFolder = $this->buildUserFolder([$fileNode], null);
		$this->mockRootFolder->method('getUserFolder')->willReturn($userFolder);

		$this->expectException(Exception::class);
		$this->expectExceptionCode(400);
		$this->expectExceptionMessage('Path is not a folder');

		$this->service->createFolderBatch(12345, null);

	}//end testCreateFolderBatchByIdReturns400WhenNodeIsFile()

	/**
	 * Test that a folder id resolving to an empty folder returns 400.
	 *
	 * @return void
	 */
	public function testCreateFolderBatchByIdReturns400WhenFolderEmpty(): void {
		$folder = $this->buildFolder([], 12345);

		$userFolder = $this->buildUserFolder([$folder], null);
		$this->mockRootFolder->method('getUserFolder')->willReturn($userFolder);

		$this->expectException(Exception::class);
		$this->expectExceptionCode(400);
		$this->expectExceptionMessage('No files found in folder');

		$this->service->createFolderBatch(12345, null);

	}//end testCreateFolderBatchByIdReturns400WhenFolderEmpty()

	/**
	 * Test that a folder id resolving to too many files returns 400.
	 *
	 * @return void
	 */
	public function testCreateFolderBatchByIdReturns400WhenTooManyFiles(): void {
		$children = [];
		for ($i = 0; $i < 3; $i++) {
			$children[] = $this->buildFile($i, "file{$i}.pdf");
		}

		$folder = $this->buildFolder($children, 12345);

		$userFolder = $this->buildUserFolder([$folder], null);
		$this->mockRootFolder->method('getUserFolder')->willReturn($userFolder);

		$this->mockStateService->method('getMaxFiles')->willReturn(2);

		$this->expectException(Exception::class);
		$this->expectExceptionCode(400);
		$this->expectExceptionMessageMatches('/too many files/i');

		$this->service->createFolderBatch(12345, null);

	}//end testCreateFolderBatchByIdReturns400WhenTooManyFiles()

	/**
	 * Test that providing both folderId and folderPath is rejected with 400.
	 *
	 * @return void
	 */
	public function testCreateFolderBatchRejectsBothParams(): void {
		$userFolder = $this->createMock(Folder::class);
		$this->mockRootFolder->method('getUserFolder')->willReturn($userFolder);

		$this->expectException(Exception::class);
		$this->expectExceptionCode(400);
		$this->expectExceptionMessage('Provide only one of folderId or folderPath');

		$this->service->createFolderBatch(12345, '/Documents/WOB');

	}//end testCreateFolderBatchRejectsBothParams()

	/**
	 * Test that providing neither folderId nor folderPath is rejected with 400.
	 *
	 * @return void
	 */
	public function testCreateFolderBatchRejectsNeitherParam(): void {
		$userFolder = $this->createMock(Folder::class);
		$this->mockRootFolder->method('getUserFolder')->willReturn($userFolder);

		$this->expectException(Exception::class);
		$this->expectExceptionCode(400);
		$this->expectExceptionMessage('Either folderId or folderPath must be provided');

		$this->service->createFolderBatch(null, null);

	}//end testCreateFolderBatchRejectsNeitherParam()

	/**
	 * Test that directories inside the folder are skipped when enumerating.
	 *
	 * @return void
	 */
	public function testSkipsSubdirectories(): void {
		$file = $this->buildFile(101, 'report.pdf');

		$subFolder = $this->createMock(Folder::class);

		$folder = $this->buildFolder([$file, $subFolder], 500);

		$userFolder = $this->buildUserFolder(null, $folder);
		$this->mockRootFolder->method('getUserFolder')->willReturn($userFolder);

		$this->mockStateService->method('getMaxFiles')->willReturn(100);
		$this->mockStateService->method('createBatch')->willReturnCallback(
			function (string $userId, array $files) {
				$this->assertCount(1, $files);
				$this->assertEquals(101, $files[0]['fileId']);
				return ['batchId' => 'uuid', 'userId' => $userId, 'status' => 'uploading', 'files' => $files];
			}
		);

		$this->service->createFolderBatch(null, '/test');

	}//end testSkipsSubdirectories()

	/**
	 * Test folder-path not found throws 404.
	 *
	 * @return void
	 */
	public function testFolderPathNotFoundThrows404(): void {
		$userFolder = $this->buildUserFolder(null, null, true);
		$this->mockRootFolder->method('getUserFolder')->willReturn($userFolder);

		$this->expectException(Exception::class);
		$this->expectExceptionCode(404);

		$this->service->createFolderBatch(null, '/nonexistent');

	}//end testFolderPathNotFoundThrows404()

	/**
	 * Test path pointing to a file throws 400.
	 *
	 * @return void
	 */
	public function testFolderPathIsFileThrows400(): void {
		$fileNode = $this->createMock(File::class);

		$userFolder = $this->buildUserFolder(null, $fileNode);
		$this->mockRootFolder->method('getUserFolder')->willReturn($userFolder);

		$this->expectException(Exception::class);
		$this->expectExceptionCode(400);

		$this->service->createFolderBatch(null, '/somefile.pdf');

	}//end testFolderPathIsFileThrows400()

	/**
	 * Test no user session throws 401.
	 *
	 * @return void
	 */
	public function testNoUserThrows401(): void {
		$mockUserSession = $this->createMock(IUserSession::class);
		$mockUserSession->method('getUser')->willReturn(null);

		$service = new FolderBatchService(
			$this->mockLogger,
			$this->mockRootFolder,
			$mockUserSession,
			$this->mockStateService,
			$this->mockJobList,
			$this->createMock(AnonymizationService::class),
			$this->makeLayoutResolver(),
			$this->mockConfidentialityLabel,
			$this->mockAppConfig
		);

		$this->expectException(Exception::class);
		$this->expectExceptionCode(401);

		$service->createFolderBatch(null, '/any');

	}//end testNoUserThrows401()

	/**
	 * Source-discovery filter: files whose base name ends with the legacy
	 * `_anonymized` suffix MUST be excluded from the batch so re-runs do not
	 * pick up redacted copies as fresh source material.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/anonymisation-folder-output-folder-layout/tasks.md#task-3
	 */
	public function testEnumerateFilesExcludesLegacyAnonymizedOutputs(): void {
		$clean = $this->buildFile(701, 'report.pdf');
		$anonymized = $this->buildFile(702, 'report_anonymized.pdf');
		$anothCl = $this->buildFile(703, 'letter.docx');

		$folder = $this->buildFolder([$clean, $anonymized, $anothCl], 600);
		$userFolder = $this->buildUserFolder(null, $folder, false, '/Documents/WOB');
		$this->mockRootFolder->method('getUserFolder')->willReturn($userFolder);

		// Real resolver — the `_anonymized` suffix discrimination is the
		// pure helper under test (no config-coupled state).
		$service = new FolderBatchService(
			$this->mockLogger,
			$this->mockRootFolder,
			$this->mockUserSession,
			$this->mockStateService,
			$this->mockJobList,
			$this->createMock(AnonymizationService::class),
			$this->makeLayoutResolver(),
			$this->mockConfidentialityLabel,
			$this->mockAppConfig
		);

		$this->mockStateService->method('getMaxFiles')->willReturn(100);
		$capturedFiles = null;
		$this->mockStateService->method('createBatch')->willReturnCallback(
			function (string $userId, array $files) use (&$capturedFiles): array {
				$capturedFiles = $files;
				return [
					'batchId' => 'filter-uuid',
					'userId' => $userId,
					'status' => 'uploading',
					'files' => array_map(
						static fn (array $f): array => $f + ['status' => 'uploaded'],
						$files
					),
				];
			}
		);

		$result = $service->createFolderBatch(null, '/Documents/WOB');

		$this->assertNotNull($capturedFiles, 'createBatch must be invoked');
		$this->assertCount(2, $capturedFiles, 'The `_anonymized` file MUST be filtered out');
		$names = array_map(static fn (array $f): string => (string)$f['fileName'], $capturedFiles);
		$this->assertNotContains('report_anonymized.pdf', $names);
		$this->assertContains('report.pdf', $names);
		$this->assertContains('letter.docx', $names);
		$this->assertSame('filter-uuid', $result['batchId']);

	}//end testEnumerateFilesExcludesLegacyAnonymizedOutputs()

	/**
	 * If every file in the folder is a prior anonymisation output, the batch
	 * is rejected with the standard "no files found" error — re-running on a
	 * folder full of redacted copies MUST NOT silently produce an empty batch.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/anonymisation-folder-output-folder-layout/tasks.md#task-3
	 */
	public function testEnumerateFilesAllLegacyOutputsThrows400(): void {
		$a = $this->buildFile(801, 'a_anonymized.pdf');
		$b = $this->buildFile(802, 'b_anonymized.docx');

		$folder = $this->buildFolder([$a, $b], 610);
		$userFolder = $this->buildUserFolder(null, $folder, false, '/Documents/WOB');
		$this->mockRootFolder->method('getUserFolder')->willReturn($userFolder);

		$service = new FolderBatchService(
			$this->mockLogger,
			$this->mockRootFolder,
			$this->mockUserSession,
			$this->mockStateService,
			$this->mockJobList,
			$this->createMock(AnonymizationService::class),
			$this->makeLayoutResolver(),
			$this->mockConfidentialityLabel,
			$this->mockAppConfig
		);

		$this->expectException(Exception::class);
		$this->expectExceptionCode(400);

		$service->createFolderBatch(null, '/Documents/WOB');

	}//end testEnumerateFilesAllLegacyOutputsThrows400()

	/**
	 * `docudesk.confidentiality.prioritise_analysis` off (the default —
	 * unconfigured mock `getValueBool` returns false) MUST leave the
	 * enumeration order byte-for-byte identical to the pre-change directory
	 * listing order, even though a confidentiality label is resolvable.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/files-confidential-labels/specs/files-confidential-labels/spec.md#scenario-flag-off-leaves-ordering-unchanged
	 */
	public function testPriorityOffLeavesOrderingUnchanged(): void {
		$secret = $this->buildFile(901, 'secret.pdf');
		$unlabelled = $this->buildFile(902, 'plain.pdf');

		$folder = $this->buildFolder([$secret, $unlabelled], 700);
		$userFolder = $this->buildUserFolder(null, $folder, false, '/Documents/WOB');
		$this->mockRootFolder->method('getUserFolder')->willReturn($userFolder);

		// Even though a label WOULD resolve, the flag is off (default mock
		// return for getValueBool), so the service must not even need to
		// consult it for ordering purposes.
		$this->mockConfidentialityLabel->method('getLabelForFile')
			->willReturnCallback(
				static fn (int $fileId) => $fileId === 901 ? new \OCA\DocuDesk\Service\ConfidentialityLabel('Secret', 3) : null
			);

		$this->mockStateService->method('getMaxFiles')->willReturn(100);
		$capturedFiles = null;
		$this->mockStateService->method('createBatch')->willReturnCallback(
			function (string $userId, array $files) use (&$capturedFiles): array {
				$capturedFiles = $files;
				return [
					'batchId' => 'priority-off-uuid',
					'userId' => $userId,
					'status' => 'uploading',
					'files' => array_map(
						static fn (array $f): array => $f + ['status' => 'uploaded'],
						$files
					),
				];
			}
		);

		$this->service->createFolderBatch(null, '/Documents/WOB');

		$this->assertNotNull($capturedFiles);
		$this->assertSame(
			[901, 902],
			array_map(static fn (array $f): int => (int)$f['fileId'], $capturedFiles),
			'Original directory-listing order must be preserved when the priority flag is off'
		);

	}//end testPriorityOffLeavesOrderingUnchanged()

	/**
	 * `docudesk.confidentiality.prioritise_analysis` on orders a higher
	 * (Secret, level 3) confidentiality file ahead of an unlabelled
	 * (level 0) file that appeared first in the directory listing — a pure
	 * secondary/tie-break sort, never a skip/block/redaction.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/files-confidential-labels/specs/files-confidential-labels/spec.md#scenario-flag-on-prioritises-higher-confidentiality
	 */
	public function testPriorityOnOrdersSecretAheadOfUnlabelled(): void {
		// Directory-listing order deliberately puts the unlabelled file first.
		$unlabelled = $this->buildFile(902, 'plain.pdf');
		$internal = $this->buildFile(903, 'internal.pdf');
		$secret = $this->buildFile(901, 'secret.pdf');

		$folder = $this->buildFolder([$unlabelled, $internal, $secret], 701);
		$userFolder = $this->buildUserFolder(null, $folder, false, '/Documents/WOB');
		$this->mockRootFolder->method('getUserFolder')->willReturn($userFolder);

		$this->mockAppConfig->method('getValueBool')->willReturn(true);
		$this->mockConfidentialityLabel->method('getLabelForFile')->willReturnCallback(
			static function (int $fileId) {
				return match ($fileId) {
					901 => new \OCA\DocuDesk\Service\ConfidentialityLabel('Secret', 3),
					903 => new \OCA\DocuDesk\Service\ConfidentialityLabel('Internal', 1),
					default => null,
				};
			}
		);

		$this->mockStateService->method('getMaxFiles')->willReturn(100);
		$capturedFiles = null;
		$this->mockStateService->method('createBatch')->willReturnCallback(
			function (string $userId, array $files) use (&$capturedFiles): array {
				$capturedFiles = $files;
				return [
					'batchId' => 'priority-on-uuid',
					'userId' => $userId,
					'status' => 'uploading',
					'files' => array_map(
						static fn (array $f): array => $f + ['status' => 'uploaded'],
						$files
					),
				];
			}
		);

		$this->service->createFolderBatch(null, '/Documents/WOB');

		$this->assertNotNull($capturedFiles);
		$this->assertSame(
			[901, 903, 902],
			array_map(static fn (array $f): int => (int)$f['fileId'], $capturedFiles),
			'Secret (level 3) must sort ahead of Internal (level 1), which sorts ahead of the unlabelled (level 0) file'
		);

	}//end testPriorityOnOrdersSecretAheadOfUnlabelled()

}//end class
