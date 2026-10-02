<?php

/**
 * Unit tests for ScanBatchService
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
 */

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Event\IntakeDocumentReceivedEvent;
use OCA\Filinq\Service\PdfDocumentFactory;
use OCA\Filinq\Service\ScanBatchRepository;
use OCA\Filinq\Service\ScanBatchService;
use OCA\Filinq\Service\ScanPageReader;
use OCA\Filinq\Service\SeparatorSheetService;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\File;
use OCP\Files\Folder;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Asserts where a batch is cut, what reaches the inbox, and what happens when
 * the instance cannot read a page at all.
 *
 * The pages are REAL: the fixture batches are built by the same document
 * factory the splitter uses, so the segments are assembled out of genuine PDF
 * pages. What is doubled is the page READER, because reading text off a scan
 * needs tesseract and imagick, and a unit test that needed those would be
 * skipped exactly where the interesting cases are.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class ScanBatchServiceTest extends TestCase {

	/**
	 * Batches the fake register was asked to store.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $stored = [];

	/**
	 * The segments written to the target folder, in order.
	 *
	 * @var array<int, string>
	 */
	private array $written = [];

	/**
	 * The intake events that were dispatched, in order.
	 *
	 * @var array<int, IntakeDocumentReceivedEvent>
	 */
	private array $dispatched = [];

	/**
	 * A one-page PDF carrying the given text.
	 *
	 * @param string $text What the page says.
	 *
	 * @return string The PDF bytes.
	 */
	private function page(string $text): string {
		$factory = new PdfDocumentFactory();
		$document = $factory->create();
		$document->AddPage();
		$document->SetFont('Helvetica', '', 14);
		$document->Cell(0, 10, $text);

		return $factory->output(document: $document);

	}//end page()

	/**
	 * Build the service over a batch whose pages read as the given texts.
	 *
	 * @param array<int, string> $texts What each page reads as, in order.
	 * @param bool $readerAvailable Whether the instance can read a scanned page at all.
	 * @param array<int, int> $weights The byte weight each page reports, for blank-page mode.
	 *
	 * @return ScanBatchService The service under test.
	 */
	private function service(
		array $texts,
		bool $readerAvailable = true,
		array $weights = [],
	): ScanBatchService {
		$pagesByBytes = [];
		$pages = [];
		foreach ($texts as $index => $text) {
			$pdf = $this->page(text: ($text === '' ? ' ' : $text));
			$pages[] = $pdf;
			$pagesByBytes[$pdf] = ['text' => $text, 'weight' => ($weights[$index] ?? strlen($pdf))];
		}

		$reader = $this->createMock(ScanPageReader::class);
		$reader->method('isAvailable')->willReturn($readerAvailable);
		$reader->method('pages')->willReturn($pages);
		$reader->method('text')->willReturnCallback(
			static fn (string $page): string => (string)($pagesByBytes[$page]['text'] ?? '')
		);
		$reader->method('weight')->willReturnCallback(
			static fn (string $page): int => (int)($pagesByBytes[$page]['weight'] ?? strlen($page))
		);

		$repository = $this->createMock(ScanBatchRepository::class);
		$repository->method('save')->willReturnCallback(
			function (array $batch, string $uuid = ''): array {
				$batch['uuid'] = ($uuid === '' ? 'batch-1' : $uuid);
				$this->stored[] = $batch;

				return $batch;
			}
		);

		$events = $this->createMock(IEventDispatcher::class);
		$events->method('dispatchTyped')->willReturnCallback(
			function (object $event): void {
				if ($event instanceof IntakeDocumentReceivedEvent) {
					$this->dispatched[] = $event;
				}
			}
		);

		return new ScanBatchService(
			$repository,
			$reader,
			new SeparatorSheetService($this->createMock(\OCA\Filinq\Service\PdfService::class)),
			new PdfDocumentFactory(),
			$events,
			$this->createMock(LoggerInterface::class)
		);

	}//end service()

	/**
	 * The batch file double.
	 *
	 * @return File The double.
	 */
	private function batchFile(): File {
		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn(4711);
		$file->method('getName')->willReturn('batch.pdf');
		$file->method('getContent')->willReturn('%PDF-1.4 fixture');

		return $file;

	}//end batchFile()

	/**
	 * The target folder double, recording what is written to it.
	 *
	 * @return Folder The double.
	 */
	private function targetFolder(): Folder {
		$folder = $this->createMock(Folder::class);
		$folder->method('getNonExistingName')->willReturnArgument(0);
		$folder->method('newFile')->willReturnCallback(
			function (string $name, string $content): File {
				$this->written[] = $name;
				$file = $this->createMock(File::class);
				$file->method('getId')->willReturn((9000 + count($this->written)));
				$file->method('getName')->willReturn($name);
				$file->method('getContent')->willReturn($content);

				return $file;
			}
		);

		return $folder;

	}//end targetFolder()

	/**
	 * The stored batch after a split.
	 *
	 * @return array<string, mixed> The last batch that was stored.
	 */
	private function lastBatch(): array {
		return $this->stored[(count($this->stored) - 1)];

	}//end lastBatch()

	/**
	 * Nine pages with separators on four and seven become three documents.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
	 */
	public function testABatchWithTwoSeparatorsYieldsThreeDocuments(): void {
		$service = $this->service(
			texts: [
				'aanvraag 1',
				'aanvraag 2',
				'aanvraag 3',
				'filinq:sep:v1:postkamer',
				'bijlage 1',
				'bijlage 2',
				'filinq:sep:v1:postkamer:2026-0042',
				'besluit 1',
				'besluit 2',
			]
		);

		$batch = $service->split(
			batch: ['uuid' => 'batch-1', 'scannerId' => 'postkamer'],
			profile: ['id' => 'postkamer', 'separatorMode' => 'qr'],
			file: $this->batchFile(),
			target: $this->targetFolder()
		);

		$this->assertSame('split', $batch['status']);
		$this->assertCount(3, $batch['segments']);
		$this->assertSame([3, 2, 2], array_column($batch['segments'], 'pages'));
		$this->assertCount(3, $this->dispatched);

		// The case number on the SECOND separator belongs to the third segment.
		$this->assertSame('', $this->dispatched[0]->getSourceRef());
		$this->assertSame('', $this->dispatched[1]->getSourceRef());
		$this->assertSame('2026-0042', $this->dispatched[2]->getSourceRef());

		foreach ($this->dispatched as $event) {
			$this->assertSame('scan', $event->getChannel());
			$this->assertSame('postkamer', $event->getSender());
		}

	}//end testABatchWithTwoSeparatorsYieldsThreeDocuments()

	/**
	 * A batch with no separator is one document, not zero.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
	 */
	public function testABatchWithoutSeparatorsIsOneDocument(): void {
		$service = $this->service(texts: ['blad 1', 'blad 2', 'blad 3', 'blad 4']);

		$batch = $service->split(
			batch: ['uuid' => 'batch-1', 'scannerId' => 'postkamer'],
			profile: ['id' => 'postkamer', 'separatorMode' => 'qr'],
			file: $this->batchFile(),
			target: $this->targetFolder()
		);

		$this->assertSame('split', $batch['status']);
		$this->assertCount(1, $batch['segments']);
		$this->assertSame(4, $batch['segments'][0]['pages']);
		$this->assertCount(1, $this->dispatched);

	}//end testABatchWithoutSeparatorsIsOneDocument()

	/**
	 * The separator itself is in neither segment.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
	 */
	public function testTheSeparatorBelongsToNeitherSide(): void {
		$service = $this->service(
			texts: ['een', 'filinq:sep:v1:postkamer', 'twee']
		);

		$batch = $service->split(
			batch: ['uuid' => 'batch-1', 'scannerId' => 'postkamer'],
			profile: ['id' => 'postkamer', 'separatorMode' => 'qr'],
			file: $this->batchFile(),
			target: $this->targetFolder()
		);

		$this->assertSame([1, 1], array_column($batch['segments'], 'pages'));
		$this->assertSame([1, 3], array_column($batch['segments'], 'firstPage'));

	}//end testTheSeparatorBelongsToNeitherSide()

	/**
	 * Two separators in a row produce no empty document.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
	 */
	public function testTwoSeparatorsInARowProduceNoEmptyDocument(): void {
		$service = $this->service(
			texts: ['een', 'filinq:sep:v1:postkamer', 'filinq:sep:v1:postkamer:2026-0001', 'twee']
		);

		$batch = $service->split(
			batch: ['uuid' => 'batch-1', 'scannerId' => 'postkamer'],
			profile: ['id' => 'postkamer', 'separatorMode' => 'qr'],
			file: $this->batchFile(),
			target: $this->targetFolder()
		);

		$this->assertCount(2, $batch['segments']);
		$this->assertSame('2026-0001', $this->dispatched[1]->getSourceRef(), 'The LAST separator before the pages is the one that names them.');

	}//end testTwoSeparatorsInARowProduceNoEmptyDocument()

	/**
	 * In blankPage mode a light page separates and belongs to neither side.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
	 */
	public function testABlankPageSeparatesInBlankPageMode(): void {
		$service = $this->service(
			texts: ['een', 'twee', '', 'vier', 'vijf'],
			weights: [90000, 90000, 100, 90000, 90000]
		);

		$batch = $service->split(
			batch: ['uuid' => 'batch-1', 'scannerId' => 'postkamer'],
			profile: ['id' => 'postkamer', 'separatorMode' => 'blankPage', 'inkThreshold' => 4096],
			file: $this->batchFile(),
			target: $this->targetFolder()
		);

		$this->assertSame('split', $batch['status']);
		$this->assertSame([2, 2], array_column($batch['segments'], 'pages'));
		$this->assertSame([1, 4], array_column($batch['segments'], 'firstPage'));

	}//end testABlankPageSeparatesInBlankPageMode()

	/**
	 * A page with text is not blank, however light it is.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
	 */
	public function testALightPageWithTextIsNotBlank(): void {
		$service = $this->service(
			texts: ['een', 'een enkel woord', 'drie'],
			weights: [90000, 100, 90000]
		);

		$batch = $service->split(
			batch: ['uuid' => 'batch-1', 'scannerId' => 'postkamer'],
			profile: ['id' => 'postkamer', 'separatorMode' => 'blankPage', 'inkThreshold' => 4096],
			file: $this->batchFile(),
			target: $this->targetFolder()
		);

		$this->assertCount(1, $batch['segments']);
		$this->assertSame(3, $batch['segments'][0]['pages']);

	}//end testALightPageWithTextIsNotBlank()

	/**
	 * An instance that cannot read a page FAILS the batch rather than
	 * delivering it whole with its separators unread.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
	 */
	public function testAnInstanceThatCannotReadAPageFailsTheBatch(): void {
		$service = $this->service(
			texts: ['een', 'filinq:sep:v1:postkamer', 'twee'],
			readerAvailable: false
		);

		$batch = $service->split(
			batch: ['uuid' => 'batch-1', 'scannerId' => 'postkamer'],
			profile: ['id' => 'postkamer', 'separatorMode' => 'qr'],
			file: $this->batchFile(),
			target: $this->targetFolder()
		);

		$this->assertSame('failed', $batch['status']);
		$this->assertStringContainsString('separator sheets', $batch['lastError']);
		$this->assertSame([], $this->dispatched, 'A failed batch delivers nothing.');
		$this->assertSame([], $this->written);

	}//end testAnInstanceThatCannotReadAPageFailsTheBatch()

	/**
	 * A batch that is nothing but separators fails rather than delivering nothing quietly.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
	 */
	public function testABatchOfNothingButSeparatorsFails(): void {
		$service = $this->service(
			texts: ['filinq:sep:v1:postkamer', 'filinq:sep:v1:postkamer']
		);

		$batch = $service->split(
			batch: ['uuid' => 'batch-1', 'scannerId' => 'postkamer'],
			profile: ['id' => 'postkamer', 'separatorMode' => 'qr'],
			file: $this->batchFile(),
			target: $this->targetFolder()
		);

		$this->assertSame('failed', $batch['status']);
		$this->assertSame([], $this->dispatched);

	}//end testABatchOfNothingButSeparatorsFails()

	/**
	 * Every segment is written as its own file, named after the scanner.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
	 */
	public function testEverySegmentIsWrittenAsItsOwnFile(): void {
		$service = $this->service(
			texts: ['een', 'filinq:sep:v1:postkamer', 'twee']
		);

		$service->split(
			batch: ['uuid' => 'batch-1', 'scannerId' => 'postkamer'],
			profile: ['id' => 'postkamer', 'separatorMode' => 'qr'],
			file: $this->batchFile(),
			target: $this->targetFolder()
		);

		$this->assertSame(['scan-postkamer-01.pdf', 'scan-postkamer-02.pdf'], $this->written);

	}//end testEverySegmentIsWrittenAsItsOwnFile()

	/**
	 * Nothing is assigned: that stays the clerk's decision.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
	 */
	public function testTheSplitterAssignsNothing(): void {
		$service = $this->service(
			texts: ['filinq:sep:v1:postkamer:2026-0042', 'stuk']
		);

		$service->split(
			batch: ['uuid' => 'batch-1', 'scannerId' => 'postkamer'],
			profile: ['id' => 'postkamer', 'separatorMode' => 'qr'],
			file: $this->batchFile(),
			target: $this->targetFolder()
		);

		// The case number travels as a SOURCE REFERENCE. The event carries no
		// target, and there is nowhere in it to put one.
		$this->assertSame('2026-0042', $this->dispatched[0]->getSourceRef());
		$this->assertSame('scan', $this->dispatched[0]->getChannel());

	}//end testTheSplitterAssignsNothing()
}//end class
