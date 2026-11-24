<?php

/**
 * Unit tests for BatchAnonymizeService — the opt-in final-artifact sanitize flag
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link https://conduction.nl
 *
 * @spec openspec/changes/archive/2026-11-21-document-sanitization/tasks.md#3-4
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\AnonymizationService;
use OCA\Filinq\Service\AnonymizeOptions;
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
 * The batch forwards `sanitize` to every file's run, carries the outcome onto
 * the batch entry, and moves the sanitized derivative beside the moved
 * anonymised file. Runs through the real OutputLayoutMover and resolver.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://conduction.nl
 *
 * @covers \OCA\Filinq\Service\BatchAnonymizeService
 * @uses   \OCA\Filinq\Service\AnonymizeOptions
 * @uses   \OCA\Filinq\Service\AnonymizeRun
 * @uses   \OCA\Filinq\Service\BatchAnonymizeRequest
 * @uses   \OCA\Filinq\Service\BatchAnonymizeRun
 * @uses   \OCA\Filinq\Service\Conversion\OutputLayoutMover
 * @uses   \OCA\Filinq\Service\Conversion\OutputLayoutResolver
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class BatchAnonymizeServiceSanitizeTest extends TestCase {

	/** @var BatchAnonymizeService */
	private BatchAnonymizeService $service;

	/** @var AnonymizationService|MockObject */
	private AnonymizationService|MockObject $anon;

	/** @var array<string, mixed> The batch as last persisted. */
	private array $saved = [];

	/** @var list<string> Target paths of every move. */
	private array $moves = [];

	/** @var list<AnonymizeOptions> The options of every per-file run. */
	private array $runOptions = [];

	protected function setUp(): void {
		parent::setUp();

		$this->anon = $this->createMock(AnonymizationService::class);
		$state = $this->createMock(BatchStateService::class);
		$state->method('getBatch')->willReturn(
			['batchId' => 'b-1', 'userId' => 'admin', 'status' => 'review', 'files' => [['fileId' => 10, 'status' => 'extracted']]]
		);
		$state->method('updateBatch')->willReturnCallback(
			function (string $id, array $batch): void {
				$this->saved = $batch;
			}
		);

		$dossier = $this->createMock(Folder::class);
		$dossier->method('getPath')->willReturn('/admin/files/dossier');
		$dossier->method('nodeExists')->willReturn(false);
		$dossier->method('newFolder')->willReturn($this->createMock(Folder::class));
		$files = [
			99 => $this->file(name: 'foo_anonymized.pdf', parent: $dossier),
			100 => $this->file(name: 'foo_anonymized_sanitized.pdf', parent: $dossier),
		];
		$userFolder = $this->createMock(Folder::class);
		$userFolder->method('getById')->willReturnCallback(
			static fn (int $id): array => (isset($files[$id]) === true ? [$files[$id]] : [])
		);
		$rootFolder = $this->createMock(IRootFolder::class);
		$rootFolder->method('getUserFolder')->with('admin')->willReturn($userFolder);

		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturnCallback(
			static fn (string $app, string $key, string $default): string => $default
		);
		$logger = $this->createMock(LoggerInterface::class);

		$this->service = new BatchAnonymizeService(
			anonService: $this->anon,
			stateService: $state,
			layoutMover: new OutputLayoutMover(new OutputLayoutResolver($config, $logger), $rootFolder, $logger)
		);

	}//end setUp()

	/**
	 * A file node whose moves are recorded.
	 *
	 * @param string $name   The name.
	 * @param Folder $parent The folder it is in.
	 *
	 * @return File
	 */
	private function file(string $name, Folder $parent): File {
		$file = $this->createMock(File::class);
		$file->method('getName')->willReturn($name);
		$file->method('getParent')->willReturn($parent);
		$file->method('move')->willReturnCallback(
			function (string $target) use ($file): File {
				$this->moves[] = $target;
				return $file;
			}
		);
		return $file;

	}//end file()

	/**
	 * The per-file run answers with $result and keeps the options it got.
	 *
	 * @param array<string, mixed> $result The run result.
	 *
	 * @return void
	 */
	private function runsReturn(array $result): void {
		$keep = function (int $fileId, array $entities, AnonymizeOptions $options) use ($result): array {
			$this->runOptions[] = $options;
			return $result;
		};
		$this->anon->method('anonymizeDocument')->willReturnCallback($keep);
		$this->anon->method('anonymizeDocumentWithBasisSummary')->willReturnCallback($keep);

	}//end runsReturn()

	/**
	 * With `sanitize`, each run asks for the pass, the entry carries the outcome and the derivative follows the anonymised file.
	 *
	 * @return void
	 */
	public function testSanitizeIsForwardedAndTheDerivativeFollowsTheMove(): void {
		$this->runsReturn(
			[
				'anonymizedFileId' => 99,
				'anonymizedFilePath' => '/admin/files/dossier/foo_anonymized.pdf',
				'sanitization' => ['sanitized' => true, 'fileId' => 99, 'sanitizedFileId' => 100, 'sanitizedFileName' => 'foo_anonymized_sanitized.pdf', 'report' => [], 'recordId' => 'r-1'],
			]
		);

		$this->service->anonymizeBatch(batchId: 'b-1', entities: [], sanitize: true);

		$this->assertCount(1, $this->runOptions);
		$this->assertTrue($this->runOptions[0]->runFor(fileId: 10, entities: [])->options['sanitize']);
		$this->assertSame(
			['/admin/files/dossier/anonymised/foo.pdf', '/admin/files/dossier/anonymised/foo_anonymized_sanitized.pdf'],
			$this->moves
		);
		$entry = $this->saved['files'][0];
		$this->assertSame(100, $entry['sanitization']['sanitizedFileId']);
		$this->assertSame('/admin/files/dossier/anonymised/foo_anonymized_sanitized.pdf', $entry['sanitization']['sanitizedFilePath']);

	}//end testSanitizeIsForwardedAndTheDerivativeFollowsTheMove()

	/**
	 * A sanitize warning reaches the batch entry, with only the anonymised file moved.
	 *
	 * @return void
	 */
	public function testASanitizeWarningReachesTheEntry(): void {
		$warning = ['code' => 'pdf_sanitizer_unavailable', 'message' => 'kept', 'unsanitizedFileId' => 99];
		$this->runsReturn(
			[
				'anonymizedFileId' => 99,
				'anonymizedFilePath' => '/admin/files/dossier/foo_anonymized.pdf',
				'sanitization' => ['sanitized' => false, 'fileId' => 99, 'unsanitizedFileId' => 99],
				'sanitizationWarning' => $warning,
			]
		);

		$this->service->anonymizeBatchWithBasisSummary(batchId: 'b-1', entities: [], sanitize: true);

		$this->assertTrue($this->runOptions[0]->summaryRunFor(fileId: 10, entities: [])->options['sanitize']);
		$this->assertSame($warning, $this->saved['files'][0]['sanitizationWarning']);
		$this->assertSame(['/admin/files/dossier/anonymised/foo.pdf'], $this->moves);

	}//end testASanitizeWarningReachesTheEntry()

	/**
	 * Without the flag no run asks for the pass and the entry carries nothing new.
	 *
	 * @return void
	 */
	public function testWithoutTheFlagNothingChanges(): void {
		$this->runsReturn(['anonymizedFileId' => 99, 'anonymizedFilePath' => '/admin/files/dossier/foo_anonymized.pdf']);

		$this->service->anonymizeBatch(batchId: 'b-1', entities: []);

		$this->assertArrayNotHasKey('sanitize', $this->runOptions[0]->runFor(fileId: 10, entities: [])->options);
		$this->assertArrayNotHasKey('sanitization', $this->saved['files'][0]);
		$this->assertArrayNotHasKey('sanitizationWarning', $this->saved['files'][0]);
		$this->assertSame(['/admin/files/dossier/anonymised/foo.pdf'], $this->moves);

	}//end testWithoutTheFlagNothingChanges()
}//end class
