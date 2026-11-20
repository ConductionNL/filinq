<?php

/**
 * Unit tests for FolderBatchService at scale: background routing, the background cap, recursive folders.
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
 * @spec openspec/changes/redaction-at-scale/specs/folder-batch-analysis/spec.md#requirement-optional-recursive-folder-enumeration-req-ddras-011
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use Exception;
use OCA\Filinq\BackgroundJob\JobQueue;
use OCA\Filinq\Service\AnonymizationService;
use OCA\Filinq\Service\BatchRouting;
use OCA\Filinq\Service\BatchStateService;
use OCA\Filinq\Service\ConfidentialityLabelService;
use OCA\Filinq\Service\Conversion\OutputLayoutResolver;
use OCA\Filinq\Service\FolderBatchService;
use OCA\Filinq\Service\Support\BatchQueue;
use OCA\Filinq\Service\Support\FolderEnumerationSources;
use OCP\BackgroundJob\IJobList;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IAppConfig;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * How a folder batch is routed by its (recursive) size, and what a recursive batch records.
 *
 * @covers \OCA\Filinq\Service\FolderBatchService
 * @covers \OCA\Filinq\Service\FolderFileEnumerator
 * @uses   \OCA\Filinq\Service\BatchRouting
 * @uses   \OCA\Filinq\Service\Support\BatchQueue
 * @uses   \OCA\Filinq\Service\Support\FolderEnumerationSources
 * @uses   \OCA\Filinq\Service\FolderReference
 * @uses   \OCA\Filinq\BackgroundJob\JobQueue
 * @uses   \OCA\Filinq\Service\Conversion\OutputLayoutResolver
 */
class FolderBatchServiceScaleTest extends TestCase {

	/**
	 * Batch store double.
	 *
	 * @var BatchStateService&MockObject
	 */
	private BatchStateService&MockObject $state;

	/**
	 * Job list double.
	 *
	 * @var IJobList&MockObject
	 */
	private IJobList&MockObject $jobList;

	/**
	 * Root folder double.
	 *
	 * @var IRootFolder&MockObject
	 */
	private IRootFolder&MockObject $rootFolder;

	/**
	 * The last batch written.
	 *
	 * @var array<string, mixed>|null
	 */
	private ?array $written = null;

	/**
	 * Subject.
	 *
	 * @var FolderBatchService
	 */
	private FolderBatchService $service;

	/**
	 * Build the subject with a synchronous cap of 100 and a background cap of 1000.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->state = $this->createMock(BatchStateService::class);
		$this->state->method('getMaxFiles')->willReturn(100);
		$this->state->method('createBatch')->willReturnCallback(
			static fn (string $userId, array $files): array => ['batchId' => 'b-1', 'userId' => $userId, 'status' => 'uploading', 'files' => $files]
		);
		$this->state->method('updateBatch')->willReturnCallback(
			function (string $batchId, array $batch): void {
				$this->written = $batch;
			}
		);
		$this->jobList = $this->createMock(IJobList::class);
		$this->rootFolder = $this->createMock(IRootFolder::class);
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('w.devries');
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);

		$appConfig = $this->createMock(IAppConfig::class);
		$appConfig->method('getValueString')->willReturnCallback(
			static fn (string $app, string $key, string $default): string => $default
		);
		$layoutConfig = $this->createMock(IAppConfig::class);
		$layoutConfig->method('getValueString')->willReturn(OutputLayoutResolver::DEFAULT_SUBFOLDER_NAME);
		$layout = new OutputLayoutResolver($layoutConfig, $this->createMock(LoggerInterface::class));

		$this->service = new FolderBatchService(
			logger: $this->createMock(LoggerInterface::class),
			rootFolder: $this->rootFolder,
			userSession: $session,
			queue: new BatchQueue(
				state: $this->state,
				jobs: new JobQueue(jobList: $this->jobList),
				routing: new BatchRouting(stateService: $this->state, appConfig: $appConfig)
			),
			anonService: $this->createMock(AnonymizationService::class),
			enumeration: new FolderEnumerationSources(
				layout: $layout,
				confidentialityLabel: $this->createMock(ConfidentialityLabelService::class),
				appConfig: $appConfig
			)
		);

	}//end setUp()

	/**
	 * A file node.
	 *
	 * @param int    $id   File id.
	 * @param string $name File name.
	 * @param string $path Absolute path.
	 *
	 * @return File&MockObject
	 */
	private function file(int $id, string $name, string $path=''): File&MockObject {
		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn($id);
		$file->method('getName')->willReturn($name);
		$file->method('getPath')->willReturn($path);
		return $file;

	}//end file()

	/**
	 * A folder node.
	 *
	 * @param string                $name     Folder name.
	 * @param array<int, mixed>     $children Its listing.
	 * @param array<string, string> $relative Absolute path => path relative to this folder.
	 *
	 * @return Folder&MockObject
	 */
	private function folder(string $name, array $children, array $relative=[]): Folder&MockObject {
		$folder = $this->createMock(Folder::class);
		$folder->method('getName')->willReturn($name);
		$folder->method('getId')->willReturn(731001);
		$folder->method('getPath')->willReturn('/w.devries/files/Dossiers/' . $name);
		$folder->method('getDirectoryListing')->willReturn($children);
		$folder->method('getRelativePath')->willReturnCallback(static fn (string $path): ?string => ($relative[$path] ?? null));
		return $folder;

	}//end folder()

	/**
	 * Serve a folder as the requested path.
	 *
	 * @param Folder $target The batch folder.
	 *
	 * @return void
	 */
	private function serve(Folder $target): void {
		$userFolder = $this->createMock(Folder::class);
		$userFolder->method('get')->willReturn($target);
		$userFolder->method('getRelativePath')->willReturn('/Dossiers/Woo-2025-014');
		$this->rootFolder->method('getUserFolder')->willReturn($userFolder);

	}//end serve()

	/**
	 * Flat files.
	 *
	 * @param int $count Number of files.
	 *
	 * @return array<int, File&MockObject>
	 */
	private function files(int $count): array {
		$files = [];
		for ($i = 1; $i <= $count; $i++) {
			$files[] = $this->file(id: $i, name: 'f' . $i . '.pdf');
		}

		return $files;

	}//end files()

	/**
	 * 480 files are accepted for the background path: no shutdown run, no queued job.
	 *
	 * @return void
	 */
	public function testAnOversizedFolderIsRoutedToTheBackground(): void {
		$this->serve(target: $this->folder(name: 'Woo-2025-014', children: $this->files(count: 480)));
		$this->jobList->expects($this->never())->method('add');

		$batch = $this->service->createFolderBatch(folderPath: '/Dossiers/Woo-2025-014');

		$this->assertTrue($batch['background']);
		$this->assertSame('extracting', $batch['status']);
		$this->assertSame(480, $batch['totalFiles']);
		$this->assertTrue($this->written['background']);
		$this->assertSame('extracting', $this->written['status']);

	}//end testAnOversizedFolderIsRoutedToTheBackground()

	/**
	 * Above the background cap the batch is refused with a 400 naming the maximum, before anything is created.
	 *
	 * @return void
	 */
	public function testAFolderAboveTheBackgroundCapIsRefused(): void {
		$this->serve(target: $this->folder(name: 'Woo-2025-014', children: $this->files(count: 1200)));
		$this->state->expects($this->never())->method('createBatch');

		try {
			$this->service->createFolderBatch(folderPath: '/Dossiers/Woo-2025-014');
			$this->fail('A 1200-file folder must be refused.');
		} catch (Exception $e) {
			$this->assertSame(400, $e->getCode());
			$this->assertStringContainsString('maximum: 1000', $e->getMessage());
		}

	}//end testAFolderAboveTheBackgroundCapIsRefused()

	/**
	 * A recursive batch holds every subfolder's files with their relative paths, but not the output subfolder's.
	 *
	 * @return void
	 */
	public function testARecursiveBatchCoversTheWholeTree(): void {
		$root = '/w.devries/files/Dossiers/Woo-2025-014';
		$deep = $this->file(id: 3, name: 'advies.pdf', path: $root . '/stukken/2025/advies.pdf');
		$sub = $this->file(id: 2, name: 'besluit.pdf', path: $root . '/stukken/besluit.pdf');
		$top = $this->file(id: 1, name: 'brief.pdf', path: $root . '/brief.pdf');
		$output = $this->file(id: 9, name: 'brief.pdf', path: $root . '/' . OutputLayoutResolver::DEFAULT_SUBFOLDER_NAME . '/brief.pdf');
		$tree = $this->folder(
			name: 'Woo-2025-014',
			children: [
				$top,
				$this->folder(name: 'stukken', children: [$sub, $this->folder(name: '2025', children: [$deep])]),
				$this->folder(name: OutputLayoutResolver::DEFAULT_SUBFOLDER_NAME, children: [$output]),
			],
			relative: [
				$root . '/brief.pdf' => '/brief.pdf',
				$root . '/stukken/besluit.pdf' => '/stukken/besluit.pdf',
				$root . '/stukken/2025/advies.pdf' => '/stukken/2025/advies.pdf',
			]
		);
		$this->serve(target: $tree);

		$batch = $this->service->createRecursiveFolderBatch(folderPath: '/Dossiers/Woo-2025-014');

		$this->assertSame([1, 2, 3], array_column($batch['files'], 'fileId'));
		$this->assertSame(['brief.pdf', 'stukken/besluit.pdf', 'stukken/2025/advies.pdf'], array_column($batch['files'], 'relativePath'));
		$this->assertTrue($this->written['recursive']);
		$this->assertArrayNotHasKey('background', $this->written);

	}//end testARecursiveBatchCoversTheWholeTree()

	/**
	 * Without the flag the scan stays flat and records no relative paths.
	 *
	 * @return void
	 */
	public function testTheDefaultScanStaysFlat(): void {
		$tree = $this->folder(
			name: 'Woo-2025-014',
			children: [
				$this->file(id: 1, name: 'brief.pdf'),
				$this->folder(name: 'stukken', children: [$this->file(id: 2, name: 'besluit.pdf')]),
			]
		);
		$this->serve(target: $tree);

		$batch = $this->service->createFolderBatch(folderPath: '/Dossiers/Woo-2025-014');

		$this->assertSame([1], array_column($batch['files'], 'fileId'));
		$this->assertArrayNotHasKey('relativePath', $batch['files'][0]);
		$this->assertArrayNotHasKey('recursive', $this->written);

	}//end testTheDefaultScanStaysFlat()
}//end class
