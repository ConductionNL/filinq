<?php

/**
 * Tests for cutting a scanned batch at its separator sheets.
 *
 * This is the part of scan intake where a mistake is expensive and invisible:
 * a batch cut in the wrong place delivers half of one person's file appended to
 * somebody else's, into a case that reads as complete. Nobody notices, because
 * every segment that comes out looks like a document.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\PdfDocumentFactory;
use OCA\Filinq\Service\ScanBatchRepository;
use OCA\Filinq\Service\ScanBatchService;
use OCA\Filinq\Service\ScanPageReader;
use OCA\Filinq\Service\SeparatorSheetService;
use OCP\EventDispatcher\IEventDispatcher;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Covers ScanBatchService::segmentsOf() over fixture batches.
 */
class ScanBatchSegmentationTest extends TestCase {

	/**
	 * A page reader that answers from a map, so a "page" here is a label
	 * rather than real PDF bytes.
	 *
	 * The split decision is about what a page SAYS and how much ink it
	 * carries; making the fixtures real PDFs would test FPDI, not the cut.
	 *
	 * @param array<string, string> $text   Page label to the text on it.
	 * @param array<string, int>    $weight Page label to its ink weight.
	 *
	 * @return ScanPageReader
	 */
	private function reader(array $text, array $weight = []): ScanPageReader {
		return new class($text, $weight) extends ScanPageReader {

			/**
			 * @param array<string, string> $text   The text per page.
			 * @param array<string, int>    $weight The weight per page.
			 */
			public function __construct(
				private readonly array $text,
				private readonly array $weight,
			) {
			}

			/**
			 * @return bool Always available: the fixture reader needs no tooling.
			 */
			public function isAvailable(): bool {
				return true;
			}

			/**
			 * @param string $batch The batch bytes, unused by the fixture.
			 *
			 * @return array<int, string> No pages: the cut is driven from the caller's list.
			 */
			public function pages(string $batch): array {
				return [];
			}

			/**
			 * @param string $page The page label.
			 *
			 * @return string The text the fixture put on it.
			 */
			public function text(string $page): string {
				return ($this->text[$page] ?? '');
			}

			/**
			 * @param string $page The page label.
			 *
			 * @return int Its ink weight; heavy by default, so a page is an ordinary one unless a test says otherwise.
			 */
			public function weight(string $page): int {
				return ($this->weight[$page] ?? 100000);
			}
		};
	}//end reader()

	/**
	 * The service under test, with only the collaborators the cut uses.
	 *
	 * @param ScanPageReader $reader The page reader.
	 *
	 * @return ScanBatchService
	 */
	private function service(ScanPageReader $reader): ScanBatchService {
		return new ScanBatchService(
			$this->createMock(ScanBatchRepository::class),
			$reader,
			new SeparatorSheetService(...$this->separatorDependencies()),
			$this->createMock(PdfDocumentFactory::class),
			$this->createMock(IEventDispatcher::class),
			new NullLogger()
		);
	}//end service()

	/**
	 * Build the separator service's constructor arguments as doubles.
	 *
	 * `readPayload()` is pure string work, so what it is constructed with does
	 * not matter — but it has to be constructed with something.
	 *
	 * @return array<int, mixed> The arguments.
	 */
	private function separatorDependencies(): array {
		$reflection = new \ReflectionClass(SeparatorSheetService::class);
		$arguments = [];
		foreach (($reflection->getConstructor()?->getParameters() ?? []) as $parameter) {
			$type = $parameter->getType();
			if ($type instanceof \ReflectionNamedType === true && $type->isBuiltin() === false) {
				$arguments[] = $this->createMock($type->getName());
				continue;
			}

			$arguments[] = ($parameter->isDefaultValueAvailable() === true ? $parameter->getDefaultValue() : null);
		}

		return $arguments;
	}//end separatorDependencies()

	/**
	 * A separator page's text, naming a case or naming none.
	 *
	 * @param string $caseNumber The case, or '' for a separator naming none.
	 *
	 * @return string The text.
	 */
	private function separatorText(string $caseNumber = ''): string {
		if ($caseNumber === '') {
			return SeparatorSheetService::PAYLOAD_PREFIX . 'profile-1';
		}

		return SeparatorSheetService::PAYLOAD_PREFIX . 'profile-1:' . $caseNumber;
	}//end separatorText()

	/**
	 * Two separators cut a batch into the documents between them.
	 *
	 * @return void
	 */
	public function testTwoSeparatorsCutTheBatchIntoThreeDocuments(): void {
		$pages = ['a1', 'a2', 'sep1', 'b1', 'sep2', 'c1', 'c2'];
		$service = $this->service(
			$this->reader(
				[
					'sep1' => $this->separatorText('ZAAK-1'),
					'sep2' => $this->separatorText('ZAAK-2'),
				]
			)
		);

		$segments = $service->segmentsOf(pages: $pages, mode: ScanBatchService::MODE_QR, profile: []);

		$this->assertSame(
			[['a1', 'a2'], ['b1'], ['c1', 'c2']],
			array_column($segments, 'pages')
		);
	}//end testTwoSeparatorsCutTheBatchIntoThreeDocuments()

	/**
	 * 🔴 THE SEPARATOR BELONGS TO NEITHER SIDE. It is a sheet somebody put in
	 * the stack, not the last page of the document before it or the first of
	 * the one after. Keeping it would put a QR code page into somebody's file.
	 *
	 * @return void
	 */
	public function testTheSeparatorItselfIsInNoDocument(): void {
		$service = $this->service($this->reader(['sep1' => $this->separatorText('ZAAK-1')]));

		$segments = $service->segmentsOf(
			pages: ['a1', 'sep1', 'b1'],
			mode: ScanBatchService::MODE_QR,
			profile: []
		);

		foreach ($segments as $segment) {
			$this->assertNotContains('sep1', $segment['pages']);
		}
	}//end testTheSeparatorItselfIsInNoDocument()

	/**
	 * A separator carries its case number to the document AFTER it, and only
	 * to that one.
	 *
	 * Carrying it further would file every later document in the batch into
	 * the first case somebody happened to write on a sheet.
	 *
	 * @return void
	 */
	public function testACaseNumberReachesTheDocumentAfterItAndNoFurther(): void {
		$service = $this->service(
			$this->reader(
				[
					'sep1' => $this->separatorText('ZAAK-1'),
					'sep2' => $this->separatorText(),
				]
			)
		);

		$segments = $service->segmentsOf(
			pages: ['sep1', 'a1', 'sep2', 'b1'],
			mode: ScanBatchService::MODE_QR,
			profile: []
		);

		$this->assertSame(['ZAAK-1', ''], array_column($segments, 'caseNumber'));
	}//end testACaseNumberReachesTheDocumentAfterItAndNoFurther()

	/**
	 * A batch with no separators at all is one document, not none.
	 *
	 * Answering with nothing would make a scanner that was loaded without
	 * separator sheets look like a scanner that jammed.
	 *
	 * @return void
	 */
	public function testABatchWithNoSeparatorsIsOneDocument(): void {
		$service = $this->service($this->reader([]));

		$segments = $service->segmentsOf(
			pages: ['a1', 'a2', 'a3'],
			mode: ScanBatchService::MODE_QR,
			profile: []
		);

		$this->assertCount(1, $segments);
		$this->assertSame(['a1', 'a2', 'a3'], $segments[0]['pages']);
		$this->assertSame(1, $segments[0]['firstPage']);
	}//end testABatchWithNoSeparatorsIsOneDocument()

	/**
	 * Two separators back to back produce no empty document.
	 *
	 * Somebody puts two sheets in by accident constantly. An empty segment
	 * would be delivered as a nought-page PDF into a case, which is a document
	 * nobody can open and nobody asked for.
	 *
	 * @return void
	 */
	public function testARunOfSeparatorsProducesNoEmptyDocument(): void {
		$service = $this->service(
			$this->reader(
				[
					'sep1' => $this->separatorText('ZAAK-1'),
					'sep2' => $this->separatorText('ZAAK-2'),
				]
			)
		);

		$segments = $service->segmentsOf(
			pages: ['sep1', 'sep2', 'a1'],
			mode: ScanBatchService::MODE_QR,
			profile: []
		);

		$this->assertCount(1, $segments);
		$this->assertSame(['a1'], $segments[0]['pages']);
		$this->assertSame('ZAAK-2', $segments[0]['caseNumber'], 'the LAST separator before the document is the one that names it');
	}//end testARunOfSeparatorsProducesNoEmptyDocument()

	/**
	 * A separator at the very end produces no trailing empty document.
	 *
	 * @return void
	 */
	public function testATrailingSeparatorProducesNoEmptyDocument(): void {
		$service = $this->service($this->reader(['sep1' => $this->separatorText('ZAAK-1')]));

		$segments = $service->segmentsOf(
			pages: ['a1', 'sep1'],
			mode: ScanBatchService::MODE_QR,
			profile: []
		);

		$this->assertCount(1, $segments);
		$this->assertSame(['a1'], $segments[0]['pages']);
	}//end testATrailingSeparatorProducesNoEmptyDocument()

	/**
	 * A page whose code cannot be read is an ORDINARY PAGE, not a separator.
	 *
	 * This is the failure direction that matters. Treating an unreadable page
	 * as a separator cuts a document in half at a random sheet and delivers
	 * both halves as whole documents; treating it as an ordinary page leaves
	 * one document too long, which a person notices immediately.
	 *
	 * @return void
	 */
	public function testAnUndecodablePageIsAnOrdinaryPage(): void {
		$service = $this->service(
			$this->reader(
				[
					'smudged' => 'fil1nq:s3p:v?:...',
					'sep1' => $this->separatorText('ZAAK-1'),
				]
			)
		);

		$segments = $service->segmentsOf(
			pages: ['a1', 'smudged', 'a2', 'sep1', 'b1'],
			mode: ScanBatchService::MODE_QR,
			profile: []
		);

		$this->assertSame([['a1', 'smudged', 'a2'], ['b1']], array_column($segments, 'pages'));
	}//end testAnUndecodablePageIsAnOrdinaryPage()

	/**
	 * In blank-page mode a page is a separator only when it is both light and
	 * wordless.
	 *
	 * A nearly blank page with a sentence on it is somebody's covering note,
	 * and cutting there splits the note off the document it belongs to.
	 *
	 * @return void
	 */
	public function testBlankModeNeedsBothNoInkAndNoWords(): void {
		$service = $this->service(
			$this->reader(
				['note' => 'Zie bijlage.', 'blank' => '   '],
				['note' => 10, 'blank' => 10, 'a1' => 50000, 'b1' => 50000]
			)
		);

		$segments = $service->segmentsOf(
			pages: ['a1', 'note', 'blank', 'b1'],
			mode: ScanBatchService::MODE_BLANK,
			profile: ['inkThreshold' => 4096]
		);

		$this->assertSame([['a1', 'note'], ['b1']], array_column($segments, 'pages'));
	}//end testBlankModeNeedsBothNoInkAndNoWords()

	/**
	 * The ink threshold is the profile's to set, and a heavy page is never a
	 * separator however wordless it is.
	 *
	 * @return void
	 */
	public function testTheInkThresholdComesFromTheProfile(): void {
		$service = $this->service($this->reader(['grey' => ''], ['grey' => 5000, 'a1' => 50000]));

		$kept = $service->segmentsOf(
			pages: ['a1', 'grey'],
			mode: ScanBatchService::MODE_BLANK,
			profile: ['inkThreshold' => 4096]
		);
		$this->assertSame([['a1', 'grey']], array_column($kept, 'pages'));

		$cut = $service->segmentsOf(
			pages: ['a1', 'grey'],
			mode: ScanBatchService::MODE_BLANK,
			profile: ['inkThreshold' => 6000]
		);
		$this->assertSame([['a1']], array_column($cut, 'pages'));
	}//end testTheInkThresholdComesFromTheProfile()

	/**
	 * Each segment knows which page of the batch it started on, so a person
	 * checking a delivery against the paper can find it.
	 *
	 * @return void
	 */
	public function testEachSegmentKnowsWhereItStartedInTheBatch(): void {
		$service = $this->service($this->reader(['sep1' => $this->separatorText('ZAAK-1')]));

		$segments = $service->segmentsOf(
			pages: ['a1', 'a2', 'sep1', 'b1'],
			mode: ScanBatchService::MODE_QR,
			profile: []
		);

		$this->assertSame([1, 4], array_column($segments, 'firstPage'));
	}//end testEachSegmentKnowsWhereItStartedInTheBatch()

	/**
	 * An empty batch produces no segments, and does not throw.
	 *
	 * @return void
	 */
	public function testAnEmptyBatchProducesNoSegments(): void {
		$this->assertSame(
			[],
			$this->service($this->reader([]))->segmentsOf(pages: [], mode: ScanBatchService::MODE_QR, profile: [])
		);
	}//end testAnEmptyBatchProducesNoSegments()
}//end class
