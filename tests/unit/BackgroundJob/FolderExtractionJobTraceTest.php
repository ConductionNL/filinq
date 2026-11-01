<?php

/**
 * Characterization tests for FolderExtractionJob's per-file processing
 *
 * Pins the arguments handed to the anonymiser and the output-layout mover,
 * and the entry and log line left by each failing step.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\BackgroundJob
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/anonymisation-folder-output-folder-layout/tasks.md#task-2
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\BackgroundJob;

use Exception;
use OCA\Filinq\BackgroundJob\FolderExtractionJob;
use OCA\Filinq\Kernel\Contracts\AnonymizationServiceInterface;
use OCA\Filinq\Kernel\Contracts\BatchStateServiceInterface;
use OCA\Filinq\Kernel\Contracts\OutputLayoutMoverInterface;
use OCA\Filinq\Kernel\Contracts\OutputLayoutResolverInterface;
use OCP\AppFramework\Utility\ITimeFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Per-file processing of the folder extraction job.
 *
 * @covers \OCA\Filinq\BackgroundJob\FolderExtractionJob
 */
class FolderExtractionJobTraceTest extends TestCase {

	private AnonymizationServiceInterface&MockObject $anon;

	private OutputLayoutMoverInterface&MockObject $mover;

	private LoggerInterface&MockObject $logger;

	/**
	 * Fresh doubles.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->anon = $this->createMock(AnonymizationServiceInterface::class);
		$this->mover = $this->createMock(OutputLayoutMoverInterface::class);
		$this->logger = $this->createMock(LoggerInterface::class);

	}//end setUp()

	/**
	 * The entities reach the anonymiser and the output moves into the batch owner's folder.
	 *
	 * @return void
	 */
	public function testOutputMovesIntoTheBatchOwnersFolder(): void {
		$this->anon->method('extractAndDetectEntities')->with(10)->willReturn(['entityCount' => 2, 'entities' => [['text' => 'Jan']]]);
		$this->anon->expects($this->once())->method('anonymizeDocument')
			->with(10, [['text' => 'Jan']])
			->willReturn(['anonymizedFileId' => 999, 'anonymizedFilePath' => '/owner/files/a_anonymized.pdf', 'detection' => 'ok']);
		$this->mover->expects($this->once())->method('relocate')
			->with('owner', 999, '/owner/files/a_anonymized.pdf')
			->willReturn(['path' => '/owner/files/anonymised/a.pdf', 'warning' => null]);

		$entry = $this->runOne(entry: ['fileId' => 10, 'fileName' => 'a.pdf', 'status' => 'uploaded', 'warning' => 'stale']);

		$this->assertSame(
			['fileId' => 10, 'fileName' => 'a.pdf', 'status' => 'anonymized', 'entityCount' => 2, 'anonymizedFilePath' => '/owner/files/anonymised/a.pdf', 'detection' => 'ok'],
			$entry
		);

	}//end testOutputMovesIntoTheBatchOwnersFolder()

	/**
	 * A failed move keeps the mover's path and warning.
	 *
	 * @return void
	 */
	public function testMoveWarningIsRecorded(): void {
		$this->anon->method('extractAndDetectEntities')->willReturn(['entityCount' => 0, 'entities' => []]);
		$this->anon->method('anonymizeDocument')->willReturn(['anonymizedFileId' => 5, 'anonymizedFilePath' => '/legacy.pdf']);
		$this->mover->method('relocate')->willReturn(['path' => '/legacy.pdf', 'warning' => 'MOVE_FAILED']);

		$entry = $this->runOne(entry: ['fileId' => 10, 'fileName' => 'a.pdf', 'status' => 'uploaded']);

		$this->assertSame('/legacy.pdf', $entry['anonymizedFilePath']);
		$this->assertSame('MOVE_FAILED', $entry['warning']);
		$this->assertNull($entry['detection']);

	}//end testMoveWarningIsRecorded()

	/**
	 * Without an output node nothing is moved and the anonymiser's path stays.
	 *
	 * @return void
	 */
	public function testNoOutputNodeMeansNoMove(): void {
		$this->anon->method('extractAndDetectEntities')->willReturn(['entityCount' => 0, 'entities' => []]);
		$this->anon->method('anonymizeDocument')->willReturn(['anonymizedFilePath' => '/legacy.pdf']);
		$this->mover->expects($this->never())->method('relocate');

		$entry = $this->runOne(entry: ['fileId' => 10, 'fileName' => 'a.pdf', 'status' => 'uploaded']);

		$this->assertSame('anonymized', $entry['status']);
		$this->assertSame('/legacy.pdf', $entry['anonymizedFilePath']);

	}//end testNoOutputNodeMeansNoMove()

	/**
	 * A failing extraction marks the entry and logs the step with the batch id.
	 *
	 * @return void
	 */
	public function testFailingExtractionIsLoggedWithTheBatch(): void {
		$this->anon->method('extractAndDetectEntities')->willThrowException(new Exception('ocr down'));
		$this->anon->expects($this->never())->method('anonymizeDocument');
		$this->logger->expects($this->once())->method('warning')
			->with('FolderExtractionJob: extraction failed for file', ['batchId' => 'b-1', 'fileId' => 10, 'error' => 'ocr down']);

		$entry = $this->runOne(entry: ['fileId' => 10, 'fileName' => 'a.pdf', 'status' => 'uploaded']);

		$this->assertSame(['fileId' => 10, 'fileName' => 'a.pdf', 'status' => 'error', 'error' => 'ocr down'], $entry);

	}//end testFailingExtractionIsLoggedWithTheBatch()

	/**
	 * A failing anonymisation keeps the entity count and logs its own step.
	 *
	 * @return void
	 */
	public function testFailingAnonymisationKeepsTheCount(): void {
		$this->anon->method('extractAndDetectEntities')->willReturn(['entityCount' => 4, 'entities' => []]);
		$this->anon->method('anonymizeDocument')->willThrowException(new Exception('no detector'));
		$this->mover->expects($this->never())->method('relocate');
		$this->logger->expects($this->once())->method('warning')
			->with('FolderExtractionJob: anonymization failed for file', ['batchId' => 'b-1', 'fileId' => 10, 'error' => 'no detector']);

		$entry = $this->runOne(entry: ['fileId' => 10, 'fileName' => 'a.pdf', 'status' => 'uploaded']);

		$this->assertSame(['fileId' => 10, 'fileName' => 'a.pdf', 'status' => 'error', 'entityCount' => 4, 'error' => 'no detector'], $entry);

	}//end testFailingAnonymisationKeepsTheCount()

	/**
	 * A prior output is skipped without running anything.
	 *
	 * @return void
	 */
	public function testPriorOutputIsSkipped(): void {
		$this->anon->expects($this->never())->method('extractAndDetectEntities');

		$entry = $this->runOne(entry: ['fileId' => 10, 'fileName' => 'a_anonymized.pdf', 'status' => 'uploaded']);

		$this->assertSame('skipped', $entry['status']);

	}//end testPriorOutputIsSkipped()

	/**
	 * Run the job over a batch `b-1` owned by `owner` holding one entry, and return that entry as last saved.
	 *
	 * @param array<string, mixed> $entry The file entry.
	 *
	 * @return array<string, mixed> The saved entry.
	 */
	private function runOne(array $entry): array {
		$state = $this->createMock(BatchStateServiceInterface::class);
		$state->method('getBatch')->with('b-1')->willReturn(['batchId' => 'b-1', 'userId' => 'owner', 'status' => 'uploading', 'files' => [$entry]]);
		$saved = [];
		$state->method('updateBatch')->willReturnCallback(
			static function (string $id, array $batch) use (&$saved): void {
				$saved = $batch;
			}
		);

		$resolver = $this->createMock(OutputLayoutResolverInterface::class);
		$resolver->method('hasAnonymizedSuffix')->willReturnCallback(static fn (string $name): bool => str_contains($name, '_anonymized'));

		$job = new FolderExtractionJob(
			time: $this->createMock(ITimeFactory::class),
			anonService: $this->anon,
			stateService: $state,
			logger: $this->logger,
			layoutResolver: $resolver,
			layoutMover: $this->mover,
		);
		(new \ReflectionMethod($job, 'run'))->invoke($job, ['batchId' => 'b-1']);

		return $saved['files'][0];
	}//end runOne()
}//end class
