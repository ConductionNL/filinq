<?php

/**
 * Reading on arrival: a new document whose file needs OCR is marked
 * `queued` with the clock's moment, everything else passes through
 * untouched, and only a stored, queued document gets a reading job.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Intake
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Intake;

use DateTime;
use DateTimeZone;
use OCA\Filinq\Kernel\JobQueueInterface;
use OCA\Filinq\Service\Intake\IntakeOcrQueue;
use OCA\Filinq\Service\Intake\IntakeReadingProgress;
use OCA\Filinq\Service\OcrService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \OCA\Filinq\Service\Intake\IntakeOcrQueue
 * @uses   \OCA\Filinq\Service\Intake\IntakeReadingProgress
 */
class IntakeOcrQueueTest extends TestCase {

	/**
	 * The ocr_on_arrival setting.
	 *
	 * @var string
	 */
	private string $setting = '1';

	/**
	 * The OCR service.
	 *
	 * @var OcrService&MockObject
	 */
	private OcrService&MockObject $ocr;

	/**
	 * The root folder.
	 *
	 * @var IRootFolder&MockObject
	 */
	private IRootFolder&MockObject $rootFolder;

	/**
	 * The job queue.
	 *
	 * @var JobQueueInterface&MockObject
	 */
	private JobQueueInterface&MockObject $jobs;

	/**
	 * The queue under test.
	 *
	 * @var IntakeOcrQueue
	 */
	private IntakeOcrQueue $queue;

	/**
	 * A queue over mocked boundaries and the real reading progress.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturnCallback(
			fn (string $app, string $key, string $default = ''): string => ($app === 'filinq' && $key === IntakeOcrQueue::SETTING ? $this->setting : $default)
		);
		$this->ocr = $this->createMock(OcrService::class);
		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->jobs = $this->createMock(JobQueueInterface::class);
		$time = $this->createMock(ITimeFactory::class);
		$time->method('getDateTime')->willReturnCallback(
			static fn (): DateTime => new DateTime('2026-03-04T05:06:07', new DateTimeZone('UTC'))
		);

		$this->queue = new IntakeOcrQueue($config, $this->ocr, $this->rootFolder, $this->jobs, new IntakeReadingProgress(), $time);

	}//end setUp()

	/**
	 * A stored file with the given mime type.
	 *
	 * @param int    $fileId   The file id.
	 * @param string $mimeType The mime type.
	 *
	 * @return void
	 */
	private function fileWith(int $fileId, string $mimeType): void {
		$file = $this->createMock(File::class);
		$file->method('getMimeType')->willReturn($mimeType);
		$this->rootFolder->method('getFirstNodeById')->with($fileId)->willReturn($file);

	}//end fileWith()

	/**
	 * A scanned document is marked queued at the clock's moment, keeping its fields.
	 *
	 * @return void
	 */
	public function testMarkQueuesAFileThatNeedsReading(): void {
		$this->fileWith(fileId: 12, mimeType: 'image/tiff');
		$this->ocr->expects($this->once())->method('needsOcr')->with('image/tiff')->willReturn(true);

		$marked = $this->queue->mark(document: ['file' => 12, 'title' => 'Scan']);

		$this->assertSame(
			[
				'file' => 12,
				'title' => 'Scan',
				'readingState' => IntakeReadingProgress::QUEUED,
				'readingUpdatedAt' => '2026-03-04T05:06:07+00:00',
				'readingError' => '',
			],
			$marked
		);

	}//end testMarkQueuesAFileThatNeedsReading()

	/**
	 * A file that already carries text is not marked.
	 *
	 * @return void
	 */
	public function testMarkLeavesAFileThatNeedsNoReading(): void {
		$this->fileWith(fileId: 12, mimeType: 'text/plain');
		$this->ocr->method('needsOcr')->willReturn(false);

		$document = ['file' => 12, 'title' => 'Note'];
		$this->assertSame($document, $this->queue->mark(document: $document));

	}//end testMarkLeavesAFileThatNeedsNoReading()

	/**
	 * With reading on arrival switched off nothing is looked up or marked.
	 *
	 * @return void
	 */
	public function testMarkDoesNothingWhenTheSettingIsOff(): void {
		$this->setting = '0';
		$this->rootFolder->expects($this->never())->method('getFirstNodeById');
		$this->ocr->expects($this->never())->method('needsOcr');

		$document = ['file' => 12];
		$this->assertSame($document, $this->queue->mark(document: $document));

	}//end testMarkDoesNothingWhenTheSettingIsOff()

	/**
	 * A document without a usable file id is not looked up.
	 *
	 * @return void
	 */
	public function testMarkSkipsADocumentWithoutAFile(): void {
		$this->rootFolder->expects($this->never())->method('getFirstNodeById');

		$this->assertSame(['title' => 'x'], $this->queue->mark(document: ['title' => 'x']));
		$this->assertSame(['file' => 0], $this->queue->mark(document: ['file' => 0]));
		$this->assertSame(['file' => -3], $this->queue->mark(document: ['file' => -3]));

	}//end testMarkSkipsADocumentWithoutAFile()

	/**
	 * A file lookup that throws leaves the document unmarked instead of failing the intake.
	 *
	 * @return void
	 */
	public function testMarkSurvivesALookupFailure(): void {
		$this->rootFolder->method('getFirstNodeById')->willThrowException(new \RuntimeException('storage gone'));
		$this->ocr->expects($this->never())->method('needsOcr');

		$this->assertSame(['file' => 12], $this->queue->mark(document: ['file' => 12]));

	}//end testMarkSurvivesALookupFailure()

	/**
	 * A node that is not a file (a folder, or nothing) is not marked.
	 *
	 * @return void
	 */
	public function testMarkSkipsANodeThatIsNotAFile(): void {
		$this->rootFolder->method('getFirstNodeById')->willReturnOnConsecutiveCalls($this->createMock(Folder::class), null);
		$this->ocr->expects($this->never())->method('needsOcr');

		$this->assertSame(['file' => 12], $this->queue->mark(document: ['file' => 12]));
		$this->assertSame(['file' => 12], $this->queue->mark(document: ['file' => 12]));

	}//end testMarkSkipsANodeThatIsNotAFile()

	/**
	 * A stored, queued document gets one reading job with its uuid and file id.
	 *
	 * @return void
	 */
	public function testQueueAddsTheReadingJob(): void {
		$this->jobs->expects($this->once())->method('add')
			->with(JobQueueInterface::INTAKE_OCR, ['uuid' => 'doc-1', 'fileId' => 12]);

		$this->queue->queue(document: ['uuid' => 'doc-1', 'file' => '12', 'readingState' => IntakeReadingProgress::QUEUED]);

	}//end testQueueAddsTheReadingJob()

	/**
	 * A document not marked queued, or not yet stored, gets no job.
	 *
	 * @return void
	 */
	public function testQueueSkipsDocumentsNotQueuedOrNotStored(): void {
		$this->jobs->expects($this->never())->method('add');

		$this->queue->queue(document: ['uuid' => 'doc-1', 'file' => 12]);
		$this->queue->queue(document: ['uuid' => 'doc-1', 'file' => 12, 'readingState' => IntakeReadingProgress::READ]);
		$this->queue->queue(document: ['file' => 12, 'readingState' => IntakeReadingProgress::QUEUED]);
		$this->queue->queue(document: ['uuid' => '', 'file' => 12, 'readingState' => IntakeReadingProgress::QUEUED]);

	}//end testQueueSkipsDocumentsNotQueuedOrNotStored()
}//end class
