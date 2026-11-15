<?php

/**
 * Unit tests for BatchAnonymizeService — output folder layout post-processing
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link https://conduction.nl
 * @spec openspec/changes/anonymisation-batch-output-folder-layout/tasks.md#task-10
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\AnonymizationService;
use OCA\Filinq\Service\BatchAnonymizeService;
use OCA\Filinq\Service\BatchStateService;
use OCA\Filinq\Service\Conversion\OutputLayoutMover;
use OCA\Filinq\Service\Conversion\OutputLayoutResolver;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * The batch runs through the REAL OutputLayoutMover and OutputLayoutResolver;
 * only the Nextcloud file nodes and OpenRegister's anonymise call are doubles.
 * Each test reads the batch state the service persisted, so what is asserted
 * is what a reader of the batch sees.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://conduction.nl
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
	 * @var IRootFolder|MockObject
	 */
	private IRootFolder|MockObject $rootFolder;

	/**
	 * The last batch state the service persisted.
	 *
	 * @var array<string, mixed>
	 */
	private array $saved = [];

	/**
	 * Set up test environment.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->mockAnonService  = $this->createMock(AnonymizationService::class);
		$this->mockStateService = $this->createMock(BatchStateService::class);
		$this->mockStateService->method('updateBatch')->willReturnCallback(
			function (string $id, array $batch): void {
				$this->saved = $batch;
			}
		);
		$this->rootFolder = $this->createMock(IRootFolder::class);

		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturnCallback(
			static fn (string $app, string $key, string $default): string => $default
		);
		$logger = $this->createMock(LoggerInterface::class);

		$this->service = new BatchAnonymizeService(
			anonService: $this->mockAnonService,
			stateService: $this->mockStateService,
			layoutMover: new OutputLayoutMover(new OutputLayoutResolver($config, $logger), $this->rootFolder, $logger)
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
			'userId' => 'admin',
			'status' => 'review',
			'files' => [
				['fileId' => $fileId, 'status' => 'extracted'],
			],
		];

	}//end makeBatch()

	/**
	 * Wire the redacted output OpenRegister wrote at `<dossier>/<name>`.
	 *
	 * @param string $name File name of the output.
	 * @param bool $moveFails Whether the move throws.
	 *
	 * @return File|MockObject
	 */
	private function stubOutput(string $name, bool $moveFails = false): File|MockObject {
		$dossier = $this->createMock(Folder::class);
		$dossier->method('getPath')->willReturn('/admin/files/dossier');
		$dossier->method('nodeExists')->willReturn(false);
		$dossier->method('newFolder')->willReturn($this->createMock(Folder::class));

		$file = $this->createMock(File::class);
		$file->method('getName')->willReturn($name);
		$file->method('getParent')->willReturn($dossier);
		if ($moveFails === true) {
			$file->method('move')->willThrowException(new \Exception('Permission denied'));
		}

		$userFolder = $this->createMock(Folder::class);
		$userFolder->method('getById')->willReturnCallback(
			static fn (int $id): array => ($id === 99 ? [$file] : [])
		);
		$this->rootFolder->method('getUserFolder')->with('admin')->willReturn($userFolder);
		return $file;

	}//end stubOutput()

	/**
	 * The batch moves the output into `anonymised/` and records that path.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/anonymisation-batch-output-folder-layout/tasks.md#task-10
	 */
	public function testPostProcessMovePlacesFileAtExpectedPath(): void {
		$this->mockStateService->method('getBatch')->willReturn($this->makeBatch());
		$file = $this->stubOutput(name: 'foo_anonymized.pdf');
		$file->expects($this->once())->method('move')->with('/admin/files/dossier/anonymised/foo.pdf');

		$this->mockAnonService->method('anonymizeDocument')->willReturn(
			['replacementCount' => 2, 'anonymizedFileId' => 99, 'anonymizedFilePath' => '/admin/files/dossier/foo_anonymized.pdf']
		);

		$result = $this->service->anonymizeBatch(batchId: 'batch-layout-1', entities: []);

		$this->assertSame(1, $result['processedFiles']);
		$this->assertSame('/admin/files/dossier/anonymised/foo.pdf', $this->saved['files'][0]['anonymizedFilePath']);
		$this->assertArrayNotHasKey('warning', $this->saved['files'][0]);

	}//end testPostProcessMovePlacesFileAtExpectedPath()

	/**
	 * A failed move keeps the legacy path with a warning; the file still counts.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/anonymisation-batch-output-folder-layout/tasks.md#task-10
	 */
	public function testMoveFailurePreservesFileAtLegacyPathWithWarning(): void {
		$this->mockStateService->method('getBatch')->willReturn($this->makeBatch());
		$this->stubOutput(name: 'foo_anonymized.pdf', moveFails: true);

		$legacyPath = '/admin/files/dossier/foo_anonymized.pdf';
		$this->mockAnonService->method('anonymizeDocument')->willReturn(
			['replacementCount' => 1, 'anonymizedFileId' => 99, 'anonymizedFilePath' => $legacyPath]
		);

		$result = $this->service->anonymizeBatch(batchId: 'batch-layout-1', entities: []);

		$this->assertSame(1, $result['processedFiles']);
		$this->assertSame('anonymized', $this->saved['files'][0]['status']);
		$this->assertSame($legacyPath, $this->saved['files'][0]['anonymizedFilePath']);
		$this->assertSame(OutputLayoutMover::MOVE_FAILED, $this->saved['files'][0]['warning']['code']);

	}//end testMoveFailurePreservesFileAtLegacyPathWithWarning()

	/**
	 * Without an output file id nothing is moved and the path stays as returned.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/anonymisation-batch-output-folder-layout/tasks.md#task-10
	 */
	public function testNoMoveWhenAnonymizedFileIdIsNull(): void {
		$this->mockStateService->method('getBatch')->willReturn($this->makeBatch());
		$this->rootFolder->expects($this->never())->method('getUserFolder');

		$this->mockAnonService->method('anonymizeDocument')
			->willReturn(['replacementCount' => 0, 'anonymizedFileId' => null, 'anonymizedFilePath' => null]);

		$result = $this->service->anonymizeBatch(batchId: 'batch-layout-1', entities: []);

		$this->assertSame(1, $result['processedFiles']);
		$this->assertNull($this->saved['files'][0]['anonymizedFilePath']);

	}//end testNoMoveWhenAnonymizedFileIdIsNull()

	/**
	 * Only extracted files are anonymised and moved.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/anonymisation-batch-output-folder-layout/tasks.md#task-10
	 */
	public function testSourceDiscoveryExcludedFilesAreSkipped(): void {
		$batch          = $this->makeBatch(fileId: 1);
		$batch['files'][] = ['fileId' => 2, 'status' => 'uploaded'];
		$this->mockStateService->method('getBatch')->willReturn($batch);
		$file = $this->stubOutput(name: 'clean_anonymized.pdf');
		$file->expects($this->once())->method('move');

		$this->mockAnonService->expects($this->once())
			->method('anonymizeDocument')
			->willReturn(['replacementCount' => 1, 'anonymizedFileId' => 99, 'anonymizedFilePath' => '/src/clean_anonymized.pdf']);

		$result = $this->service->anonymizeBatch(batchId: 'batch-layout-2', entities: []);

		$this->assertSame(1, $result['processedFiles']);
		$this->assertSame(2, $result['totalFiles']);

	}//end testSourceDiscoveryExcludedFilesAreSkipped()
}//end class
