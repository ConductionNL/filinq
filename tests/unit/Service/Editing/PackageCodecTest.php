<?php

/**
 * Unit tests for PackageCodec
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service\Editing
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.DocuDesk.app
 */

namespace OCA\DocuDesk\Tests\Unit\Service\Editing;

use OCA\DocuDesk\Service\Editing\PackageCodec;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use ZipArchive;

/**
 * Unit tests for the byte-surgical ODF/OOXML codec.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service\Editing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class PackageCodecTest extends TestCase {

	/**
	 * The codec under test.
	 *
	 * @var PackageCodec
	 */
	private PackageCodec $codec;

	/**
	 * Temporary files created by a test, removed in tearDown.
	 *
	 * @var array<int, string>
	 */
	private array $spilled = [];

	/**
	 * Set up.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->codec = new PackageCodec();

	}//end setUp()

	/**
	 * Remove any temporary packages.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		foreach ($this->spilled as $path) {
			if (is_file($path) === true) {
				unlink($path);
			}
		}

		$this->spilled = [];
		parent::tearDown();

	}//end tearDown()

	/**
	 * A `.docx` carrying a comment, a tracked change, a header, a table, a text
	 * box and a binary part -- everything a naive parse-and-re-serialise codec
	 * silently drops.
	 *
	 * @return string The package bytes.
	 */
	private function docx(): string {
		$document = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
			. '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"'
			. ' xmlns:w14="http://schemas.microsoft.com/office/word/2010/wordml"><w:body>'
			. '<w:p w14:paraId="1A2B3C4D"><w:pPr><w:pStyle w:val="Title"/></w:pPr>'
			. '<w:r><w:rPr><w:b/></w:rPr><w:t>Subsidy decision 2026</w:t></w:r></w:p>'
			. '<w:p><w:r><w:t xml:space="preserve">Dear </w:t></w:r>'
			. '<w:r><w:rPr><w:i/></w:rPr><w:t>applicant</w:t></w:r><w:r><w:t>,</w:t></w:r></w:p>'
			. '<w:p><w:commentRangeStart w:id="1"/><w:r><w:t>Your request has been denied.</w:t></w:r>'
			. '<w:commentRangeEnd w:id="1"/><w:r><w:commentReference w:id="1"/></w:r></w:p>'
			. '<w:p><w:ins w:id="9" w:author="Ruben"><w:r><w:t>Inserted under track changes.</w:t></w:r></w:ins>'
			. '<w:del w:id="10" w:author="Ruben"><w:r><w:delText>removed text</w:delText></w:r></w:del></w:p>'
			. '<w:p/>'
			. '<w:p><w:r><w:t>Kind regards,</w:t></w:r></w:p>'
			. '<w:p><w:r><w:t>Kind regards,</w:t></w:r></w:p>'
			. '<w:tbl><w:tr><w:tc><w:p><w:r><w:t>In a table cell</w:t></w:r></w:p></w:tc></w:tr></w:tbl>'
			. '<w:p><w:r><w:pict><w:txbxContent><w:p><w:r><w:t>Inside a text box</w:t></w:r></w:p>'
			. '</w:txbxContent></w:pict></w:r></w:p>'
			. '</w:body></w:document>';

		return $this->zip(
			[
				'[Content_Types].xml' => '<Types/>',
				'word/document.xml' => $document,
				'word/comments.xml' => '<w:comments xmlns:w="x"><w:comment w:id="1"><w:p><w:r>'
					. '<w:t>Check the legal basis.</w:t></w:r></w:p></w:comment></w:comments>',
				'word/header1.xml' => '<w:hdr xmlns:w="x"><w:p><w:r><w:t>Municipality</w:t></w:r></w:p></w:hdr>',
				'word/media/logo.png' => "\x89PNG\r\n\x1a\n" . str_repeat("\x42", 256),
			]
		);

	}//end docx()

	/**
	 * A minimal `.odt`.
	 *
	 * @return string The package bytes.
	 */
	private function odt(): string {
		$content = '<?xml version="1.0" encoding="UTF-8"?>'
			. '<office:document-content xmlns:office="urn:office" xmlns:text="urn:text"><office:body><office:text>'
			. '<text:p text:style-name="Title">Besluit</text:p>'
			. '<text:p>Beste <text:span text:style-name="Emphasis">aanvrager</text:span>,</text:p>'
			. '<text:p/>'
			. '</office:text></office:body></office:document-content>';

		return $this->zip(
			[
				'mimetype' => 'application/vnd.oasis.opendocument.text',
				'content.xml' => $content,
				'styles.xml' => '<office:document-styles xmlns:office="urn:office"/>',
			]
		);

	}//end odt()

	/**
	 * Build a ZIP package in memory.
	 *
	 * @param array<string, string> $entries The entries.
	 *
	 * @return string The package bytes.
	 */
	private function zip(array $entries): string {
		$path = tempnam(sys_get_temp_dir(), 'docudesk-test-');
		$this->spilled[] = $path;
		unlink($path);

		$zip = new ZipArchive();
		$zip->open($path, ZipArchive::CREATE);
		foreach ($entries as $name => $body) {
			$zip->addFromString($name, $body);
		}

		$zip->close();

		return (string)file_get_contents($path);

	}//end zip()

	/**
	 * Read one entry out of package bytes.
	 *
	 * @param string $bytes The package bytes.
	 * @param string $entry The entry name.
	 *
	 * @return string|false The entry body.
	 */
	private function entry(string $bytes, string $entry): string|false {
		$path = tempnam(sys_get_temp_dir(), 'docudesk-test-');
		$this->spilled[] = $path;
		file_put_contents($path, $bytes);

		$zip = new ZipArchive();
		$zip->open($path);
		$body = $zip->getFromName($entry);
		$zip->close();

		return $body;

	}//end entry()


	/**
	 * A .docx as COLLABORA ACTUALLY WRITES ONE, base64-encoded.
	 *
	 * Produced by round-tripping a document through the real
	 * `soffice --convert-to docx` shipped in richdocumentscode 26.4.104, then
	 * captured verbatim. Ten package entries — styles, theme, fontTable,
	 * settings, docProps — against the four of a hand-built fixture.
	 *
	 * WHY THIS EXISTS SEPARATELY FROM `docx()`
	 * ---------------------------------------
	 * `docx()` is authored to be hostile in specific ways (a comment range, a
	 * tracked insert and delete, a text box, a table). This one is hostile in the
	 * way real files are: markup nobody chose, in an order nobody predicted, with
	 * a run and paragraph structure an office suite produced rather than a test.
	 * A codec that passes a fixture it was written alongside and fails a real
	 * document is the normal outcome, not a surprising one.
	 *
	 * @var array<int, string>
	 */
	private const COLLABORA_DOCX_B64 = [
		'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',
		'fwIAAKwRAAAAAA==',
	];

	/**
	 * Decode the real-Collabora fixture.
	 *
	 * @return string The package bytes.
	 */
	private function collaboraDocx(): string {
		return (string)base64_decode(implode('', self::COLLABORA_DOCX_B64));

	}//end collaboraDocx()

	/**
	 * The codec reads and edits a document COLLABORA wrote, not just one this
	 * test authored, and leaves every other part of that real package alone.
	 *
	 * The entry count is asserted so the fixture cannot silently decay into a
	 * trivial package and keep passing.
	 *
	 * @return void
	 */
	public function testARealCollaboraDocumentRoundTrips(): void {
		$before = $this->collaboraDocx();

		$read = $this->codec->readBlocks($before, 'docx');
		$texts = array_column($read['blocks'], 'text');

		$this->assertContains('Subsidieverzoek 2026 - concept', $texts);
		$target = null;
		foreach ($read['blocks'] as $block) {
			if (str_contains($block['text'], 'weken') === true) {
				$target = $block;
				break;
			}
		}

		$this->assertNotNull($target, 'the fixture must carry the paragraph under test');

		$after = $this->codec->applyEdits(
			$before,
			'docx',
			[['anchor' => $target['anchor'], 'action' => 'replace', 'text' => 'Binnen vier weken.']]
		)['bytes'];

		$this->assertContains('Binnen vier weken.', array_column($this->codec->readBlocks($after, 'docx')['blocks'], 'text'));

		// Every entry except the body part comes back byte-identical — on a package
		// this codec did not author.
		$path = tempnam(sys_get_temp_dir(), 'docudesk-test-');
		$this->spilled[] = $path;
		file_put_contents($path, $before);
		$zip = new ZipArchive();
		$zip->open($path);
		$names = [];
		for ($i = 0; $i < $zip->numFiles; $i++) {
			$names[] = (string)$zip->getNameIndex($i);
		}

		$zip->close();

		$this->assertGreaterThanOrEqual(10, count($names), 'the fixture must stay a REAL package, not decay to a stub');

		foreach ($names as $entry) {
			if ($entry === 'word/document.xml') {
				continue;
			}

			$this->assertSame(
				$this->entry($before, $entry),
				$this->entry($after, $entry),
				$entry . ' must survive an edit to the document body byte-identical'
			);
		}

		$this->assertNotFalse(simplexml_load_string((string)$this->entry($after, 'word/document.xml')));

	}//end testARealCollaboraDocumentRoundTrips()

	/**
	 * Only word-processing packages are addressable. A spreadsheet's block is a
	 * cell, not a paragraph, so accepting `.xlsx` here would hand back anchors
	 * that resolve to nothing.
	 *
	 * @return void
	 */
	public function testOnlyWordProcessingPackagesAreSupported(): void {
		$this->assertTrue($this->codec->supports('docx'));
		$this->assertTrue($this->codec->supports('ODT'));
		$this->assertFalse($this->codec->supports('xlsx'));
		$this->assertFalse($this->codec->supports('pdf'));
		$this->assertSame(['docx', 'odt'], $this->codec->supportedExtensions());

	}//end testOnlyWordProcessingPackagesAreSupported()

	/**
	 * Reading yields one anchored block per paragraph, with the visible text of
	 * each -- runs joined, deleted text excluded.
	 *
	 * @return void
	 */
	public function testReadingYieldsTheVisibleTextOfEachParagraph(): void {
		$read = $this->codec->readBlocks($this->docx(), 'docx');

		$texts = array_column($read['blocks'], 'text');

		$this->assertSame(PackageCodec::FORMAT_OOXML, $read['format']);
		$this->assertSame('Subsidy decision 2026', $texts[0]);
		$this->assertSame('Dear applicant,', $texts[1], 'Runs are joined into one readable paragraph.');
		$this->assertSame('Inserted under track changes.', $texts[3]);
		$this->assertStringNotContainsString(
			'removed text',
			implode(' ', $texts),
			'`w:delText` is struck-through text; it is not what the document says.'
		);

	}//end testReadingYieldsTheVisibleTextOfEachParagraph()

	/**
	 * Anchors come from CONTENT, not position. Two paragraphs with identical
	 * text therefore share a hash and are separated by an occurrence ordinal --
	 * without which one of them would be unaddressable.
	 *
	 * @return void
	 */
	public function testIdenticalParagraphsGetDistinctAnchors(): void {
		$blocks = $this->codec->readBlocks($this->docx(), 'docx')['blocks'];
		$anchors = array_column($blocks, 'anchor');

		$this->assertSame(count($anchors), count(array_unique($anchors)));
		$this->assertSame('Kind regards,', $blocks[5]['text']);
		$this->assertSame('Kind regards,', $blocks[6]['text']);
		$this->assertNotSame($blocks[5]['anchor'], $blocks[6]['anchor']);
		$this->assertSame(
			substr($blocks[5]['anchor'], 0, -2),
			substr($blocks[6]['anchor'], 0, -2),
			'Same text, same hash — only the ordinal differs.'
		);

	}//end testIdenticalParagraphsGetDistinctAnchors()

	/**
	 * The same document read twice gives the same anchors, which is what makes
	 * a read-then-edit round trip possible at all.
	 *
	 * @return void
	 */
	public function testAnchorsAreStableAcrossReads(): void {
		$bytes = $this->docx();

		$this->assertSame(
			array_column($this->codec->readBlocks($bytes, 'docx')['blocks'], 'anchor'),
			array_column($this->codec->readBlocks($bytes, 'docx')['blocks'], 'anchor')
		);

	}//end testAnchorsAreStableAcrossReads()

	/**
	 * Editing rewrites ONLY the body part. Comments, headers, content types and
	 * binary media come back byte-identical -- not "probably fine", identical.
	 *
	 * @return void
	 */
	public function testEveryOtherPackagePartSurvivesByteIdentical(): void {
		$before = $this->docx();
		$anchor = $this->codec->readBlocks($before, 'docx')['blocks'][2]['anchor'];

		$after = $this->codec->applyEdits(
			$before,
			'docx',
			[['anchor' => $anchor, 'action' => 'replace', 'text' => 'Your request has been granted.']]
		)['bytes'];

		foreach (['[Content_Types].xml', 'word/comments.xml', 'word/header1.xml', 'word/media/logo.png'] as $part) {
			$this->assertSame(
				$this->entry($before, $part),
				$this->entry($after, $part),
				$part . ' must not be touched by an edit to the document body.'
			);
		}

		$this->assertNotSame($this->entry($before, 'word/document.xml'), $this->entry($after, 'word/document.xml'));

	}//end testEveryOtherPackagePartSurvivesByteIdentical()

	/**
	 * Inside the edited part, everything the edit did not target survives:
	 * comment ranges, tracked insertions and deletions, paragraph styles, run
	 * formatting and text boxes. These are the losses that are invisible in a
	 * diff of the visible text.
	 *
	 * @return void
	 */
	public function testMarkupAroundTheEditSurvives(): void {
		$before = $this->docx();
		$anchor = $this->codec->readBlocks($before, 'docx')['blocks'][2]['anchor'];

		$after = $this->codec->applyEdits(
			$before,
			'docx',
			[['anchor' => $anchor, 'action' => 'replace', 'text' => 'Your request has been granted.']]
		)['bytes'];

		$document = (string)$this->entry($after, 'word/document.xml');

		foreach (
			[
				'w:commentRangeStart' => 'the comment range',
				'<w:ins ' => 'the tracked insertion',
				'w:delText' => 'the tracked deletion',
				'w:pStyle' => 'the paragraph style',
				'<w:i/>' => 'the italic run',
				'txbxContent' => 'the text box',
				'w14:paraId' => 'the native paragraph id',
			] as $needle => $label
		) {
			$this->assertStringContainsString($needle, $document, $label . ' must survive an unrelated edit');
		}

		$this->assertNotFalse(simplexml_load_string($document), 'The rewritten part must still be well-formed XML.');

	}//end testMarkupAroundTheEditSurvives()

	/**
	 * A replaced paragraph keeps its own properties and its first run's
	 * formatting -- the edit changes what it says, not how it looks.
	 *
	 * @return void
	 */
	public function testReplacementKeepsTheParagraphsOwnFormatting(): void {
		$before = $this->docx();
		$anchor = $this->codec->readBlocks($before, 'docx')['blocks'][0]['anchor'];

		$after = $this->codec->applyEdits(
			$before,
			'docx',
			[['anchor' => $anchor, 'action' => 'replace', 'text' => 'Subsidy decision 2027']]
		)['bytes'];

		$document = (string)$this->entry($after, 'word/document.xml');

		$this->assertStringContainsString('<w:pStyle w:val="Title"/>', $document);
		$this->assertStringContainsString('<w:b/>', $document);
		$this->assertSame('Subsidy decision 2027', $this->codec->readBlocks($after, 'docx')['blocks'][0]['text']);

	}//end testReplacementKeepsTheParagraphsOwnFormatting()

	/**
	 * Text that would break the XML is escaped, not injected. Without this an
	 * agent writing "R&D <urgent>" would corrupt the package.
	 *
	 * @return void
	 */
	public function testTextIsEscapedRatherThanInjected(): void {
		$before = $this->docx();
		$anchor = $this->codec->readBlocks($before, 'docx')['blocks'][0]['anchor'];

		$after = $this->codec->applyEdits(
			$before,
			'docx',
			[['anchor' => $anchor, 'action' => 'replace', 'text' => 'R&D <urgent> "note" € 12.500']]
		)['bytes'];

		$document = (string)$this->entry($after, 'word/document.xml');

		$this->assertStringContainsString('R&amp;D &lt;urgent&gt;', $document);
		$this->assertNotFalse(simplexml_load_string($document));
		$this->assertSame(
			'R&D <urgent> "note" € 12.500',
			$this->codec->readBlocks($after, 'docx')['blocks'][0]['text'],
			'What was written is what reads back.'
		);

	}//end testTextIsEscapedRatherThanInjected()

	/**
	 * Insert, replace and delete in one call, all landing where they were aimed
	 * even though each one moves the offsets of the others.
	 *
	 * @return void
	 */
	public function testMultipleEditsInOneCallAllLandCorrectly(): void {
		$before = $this->docx();
		$blocks = $this->codec->readBlocks($before, 'docx')['blocks'];

		$result = $this->codec->applyEdits(
			$before,
			'docx',
			[
				['anchor' => $blocks[0]['anchor'], 'action' => 'replace', 'text' => 'Subsidy decision 2027'],
				['anchor' => $blocks[4]['anchor'], 'action' => 'delete'],
				['anchor' => $blocks[5]['anchor'], 'action' => 'insertAfter', 'text' => 'Team Subsidies'],
			]
		);

		$texts = array_column($this->codec->readBlocks($result['bytes'], 'docx')['blocks'], 'text');

		$this->assertCount(3, $result['applied']);
		$this->assertSame('Subsidy decision 2027', $texts[0]);
		$this->assertNotContains('', array_slice($texts, 0, 4), 'The empty paragraph was deleted.');
		$this->assertSame(['Kind regards,', 'Team Subsidies', 'Kind regards,'], array_slice($texts, 4, 3));

	}//end testMultipleEditsInOneCallAllLandCorrectly()

	/**
	 * An inserted paragraph inherits the anchor paragraph's markup, so a new
	 * line in a styled document does not arrive unstyled.
	 *
	 * @return void
	 */
	public function testInsertedParagraphInheritsTheAnchorsMarkup(): void {
		$before = $this->docx();
		$anchor = $this->codec->readBlocks($before, 'docx')['blocks'][0]['anchor'];

		$after = $this->codec->applyEdits(
			$before,
			'docx',
			[['anchor' => $anchor, 'action' => 'insertAfter', 'text' => 'Reference 2026/117']]
		)['bytes'];

		$document = (string)$this->entry($after, 'word/document.xml');

		$this->assertSame(2, substr_count($document, '<w:pStyle w:val="Title"/>'));
		$this->assertSame('Reference 2026/117', $this->codec->readBlocks($after, 'docx')['blocks'][1]['text']);

	}//end testInsertedParagraphInheritsTheAnchorsMarkup()

	/**
	 * An empty paragraph has no run to carry text, so replacing its text means
	 * adding one rather than silently doing nothing.
	 *
	 * @return void
	 */
	public function testAnEmptyParagraphCanBeGivenText(): void {
		$before = $this->docx();
		$anchor = $this->codec->readBlocks($before, 'docx')['blocks'][4]['anchor'];

		$after = $this->codec->applyEdits(
			$before,
			'docx',
			[['anchor' => $anchor, 'action' => 'replace', 'text' => 'With kind regards from the board']]
		)['bytes'];

		$this->assertSame(
			'With kind regards from the board',
			$this->codec->readBlocks($after, 'docx')['blocks'][4]['text']
		);

	}//end testAnEmptyParagraphCanBeGivenText()

	/**
	 * Leading and trailing spaces survive, because XML collapses them unless the
	 * element says otherwise.
	 *
	 * @return void
	 */
	public function testSignificantWhitespaceIsPreserved(): void {
		$before = $this->docx();
		$anchor = $this->codec->readBlocks($before, 'docx')['blocks'][0]['anchor'];

		$after = $this->codec->applyEdits(
			$before,
			'docx',
			[['anchor' => $anchor, 'action' => 'replace', 'text' => 'Heading']]
		)['bytes'];

		$this->assertStringContainsString('xml:space="preserve"', (string)$this->entry($after, 'word/document.xml'));

	}//end testSignificantWhitespaceIsPreserved()

	/**
	 * A stale anchor fails the WHOLE edit set. A partially applied set leaves
	 * the document in a state nobody asked for, and no caller can tell which
	 * half landed.
	 *
	 * @return void
	 */
	public function testOneStaleAnchorRejectsTheWholeEditSet(): void {
		$before = $this->docx();
		$blocks = $this->codec->readBlocks($before, 'docx')['blocks'];

		try {
			$this->codec->applyEdits(
				$before,
				'docx',
				[
					['anchor' => $blocks[0]['anchor'], 'action' => 'replace', 'text' => 'Applied?'],
					['anchor' => 'bdeadbeef-1', 'action' => 'replace', 'text' => 'Never'],
				]
			);
			$this->fail('A stale anchor must raise.');
		} catch (RuntimeException $e) {
			$this->assertStringContainsString('bdeadbeef-1', $e->getMessage());
			$this->assertStringContainsString('re-read', strtolower($e->getMessage()));
		}

		$this->assertSame(
			'Subsidy decision 2026',
			$this->codec->readBlocks($before, 'docx')['blocks'][0]['text'],
			'The valid edit in the same set must not have been applied either.'
		);

	}//end testOneStaleAnchorRejectsTheWholeEditSet()

	/**
	 * Editing a paragraph changes its anchor, which is exactly how a second
	 * application of the same edit is caught instead of being applied twice.
	 *
	 * @return void
	 */
	public function testAnAnchorIsSpentOnceTheTextChanges(): void {
		$before = $this->docx();
		$anchor = $this->codec->readBlocks($before, 'docx')['blocks'][0]['anchor'];
		$edit = [['anchor' => $anchor, 'action' => 'replace', 'text' => 'Subsidy decision 2027']];

		$after = $this->codec->applyEdits($before, 'docx', $edit)['bytes'];

		$this->expectException(RuntimeException::class);
		$this->codec->applyEdits($after, 'docx', $edit);

	}//end testAnAnchorIsSpentOnceTheTextChanges()

	/**
	 * An unknown action is refused rather than quietly treated as a replace.
	 *
	 * @return void
	 */
	public function testUnknownActionIsRefused(): void {
		$before = $this->docx();
		$anchor = $this->codec->readBlocks($before, 'docx')['blocks'][0]['anchor'];

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessageMatches('/unknown action/i');

		$this->codec->applyEdits($before, 'docx', [['anchor' => $anchor, 'action' => 'rewrite', 'text' => 'x']]);

	}//end testUnknownActionIsRefused()

	/**
	 * A replace with no text is refused. Silently emptying a paragraph is not
	 * what "change this" means, and delete is the explicit way to say it.
	 *
	 * @return void
	 */
	public function testReplaceWithoutTextIsRefused(): void {
		$before = $this->docx();
		$anchor = $this->codec->readBlocks($before, 'docx')['blocks'][0]['anchor'];

		$this->expectException(RuntimeException::class);

		$this->codec->applyEdits($before, 'docx', [['anchor' => $anchor, 'action' => 'replace']]);

	}//end testReplaceWithoutTextIsRefused()

	/**
	 * An empty edit set is refused rather than rewriting the package for nothing.
	 *
	 * @return void
	 */
	public function testEmptyEditSetIsRefused(): void {
		$this->expectException(RuntimeException::class);

		$this->codec->applyEdits($this->docx(), 'docx', []);

	}//end testEmptyEditSetIsRefused()

	/**
	 * An unsupported extension names the formats that ARE editable, so the
	 * caller can act on the refusal instead of retrying.
	 *
	 * @return void
	 */
	public function testUnsupportedFormatRefusalNamesWhatIsSupported(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessageMatches('/docx.*odt/');

		$this->codec->readBlocks($this->docx(), 'xlsx');

	}//end testUnsupportedFormatRefusalNamesWhatIsSupported()

	/**
	 * Bytes that are not a package raise rather than returning an empty outline,
	 * which would read as "this document has no text".
	 *
	 * @return void
	 */
	public function testNonPackageBytesRaise(): void {
		$this->expectException(RuntimeException::class);

		$this->codec->readBlocks('this is a plain text file, not a zip', 'docx');

	}//end testNonPackageBytesRaise()

	/**
	 * A package missing its body part raises, naming the part.
	 *
	 * @return void
	 */
	public function testPackageWithoutABodyPartRaises(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessageMatches('/word\/document\.xml/');

		$this->codec->readBlocks($this->zip(['[Content_Types].xml' => '<Types/>']), 'docx');

	}//end testPackageWithoutABodyPartRaises()

	/**
	 * ODF is read and edited through the same anchors, and the paragraph's own
	 * style survives.
	 *
	 * @return void
	 */
	public function testOpenDocumentTextIsReadAndEdited(): void {
		$before = $this->odt();
		$read = $this->codec->readBlocks($before, 'odt');

		$this->assertSame(PackageCodec::FORMAT_ODF, $read['format']);
		$this->assertSame(['Besluit', 'Beste aanvrager,', ''], array_column($read['blocks'], 'text'));

		$after = $this->codec->applyEdits(
			$before,
			'odt',
			[['anchor' => $read['blocks'][0]['anchor'], 'action' => 'replace', 'text' => 'Besluit 2027']]
		)['bytes'];

		$content = (string)$this->entry($after, 'content.xml');

		$this->assertSame('Besluit 2027', $this->codec->readBlocks($after, 'odt')['blocks'][0]['text']);
		$this->assertStringContainsString('text:style-name="Title"', $content, 'The paragraph style survives.');
		$this->assertNotFalse(simplexml_load_string($content));

	}//end testOpenDocumentTextIsReadAndEdited()

	/**
	 * ODF's `mimetype` entry must survive a rewrite: it is what identifies the
	 * package to every reader, and a codec that recompressed the archive would
	 * break it without breaking any visible text.
	 *
	 * @return void
	 */
	public function testOdfMimetypeEntrySurvivesARewrite(): void {
		$before = $this->odt();
		$anchor = $this->codec->readBlocks($before, 'odt')['blocks'][0]['anchor'];

		$after = $this->codec->applyEdits(
			$before,
			'odt',
			[['anchor' => $anchor, 'action' => 'replace', 'text' => 'Besluit 2027']]
		)['bytes'];

		$this->assertSame('application/vnd.oasis.opendocument.text', $this->entry($after, 'mimetype'));
		$this->assertSame($this->entry($before, 'styles.xml'), $this->entry($after, 'styles.xml'));

	}//end testOdfMimetypeEntrySurvivesARewrite()
}//end class
