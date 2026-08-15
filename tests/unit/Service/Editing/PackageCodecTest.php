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
