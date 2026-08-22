<?php

/**
 * Unit tests for PackageCodec
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Editing
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 */

namespace OCA\Filinq\Tests\Unit\Service\Editing;

use OCA\Filinq\Service\Editing\PackageCodec;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use ZipArchive;

/**
 * Unit tests for the byte-surgical ODF/OOXML codec.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Editing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
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
		$path = tempnam(sys_get_temp_dir(), 'filinq-test-');
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
		$path = tempnam(sys_get_temp_dir(), 'filinq-test-');
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
		$path = tempnam(sys_get_temp_dir(), 'filinq-test-');
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
	 * An .odt as COLLABORA ACTUALLY WRITES ONE, base64-encoded.
	 *
	 * Same provenance and same reason as {@see self::COLLABORA_DOCX_B64}, for the
	 * OTHER package family. ODF is not a variation on OOXML — different body part,
	 * different paragraph element, and one property no other format has: the
	 * `mimetype` entry, which must stay first and STORED for readers to identify
	 * the package at all. A codec that recompressed the archive would break that
	 * without breaking one word of visible text, so it is asserted here against a
	 * package this test did not build.
	 *
	 * @var array<int, string>
	 */
	private const COLLABORA_ODT_B64 = [
		'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',
		'sAwTh9I5BNN2bVpag5oli4/3w7tQ1DiWPciWnIgmf+zeRSM7+9jD2qJbdttcXoMTjCNrG+UrBKkqt+CDVJxy9rh92f0uGEUZbaw0',
		'un6pa/ByigmoqnFbDjkI6rbJITbnedmoi/bHKr9+B37fxqtvSs6MpLoKOtV7XNMSlddHXeVKMJzV4Xir1zrSIse+E4y69UvxsS7G',
		'bG7J76aNXQ5UIxhyErwcXtpQyWffbF1j8b5so6x7NSnIbt6aM8abaML3PWi7eeNzuHXtjj6sMcHMz3svKk5KnbHIF/VpO3LTeoz3',
		'YNY6t6eNcUnqz6WP1auoYvbGOiuTguzXqEM+jLNzlTXvvz6GXNalEy/OzYt6GtG35LP8qY/JIdfaHvYqvfz8t+x7zXuSLiDJ21LG',
		'fpQijS2tJ8vkRJFuRIYBR2v7Id1JlgW221xLT/P5rCLXcTmuXZCiseaaPu+hdXckF7uP0lMEuerNnZTS8/BRVpL+3buSbc3zAd7U',
		'pDM5XM3taPKB+b1HDvKNrGwzyBBklw80L/2OO9rcwlylHr8+xvdC/L/xsGu1rv17C37LZ2j/wQr8q3bd/6Et9F/gm/1Pnhn980T/',
		'859PE+PHgv6vq3/e0ses/udZf17/j/PG5/39ebd/WNp/yzNDRqQfHVv/9vyS+fzomZ0Fgv/Z8/lPFVSRfuJvXYfnSkE+6MqfRtXh',
		'e5v+sWs9vq8XPyPs7fe/THkz7qWENvripUIKQ5l9ZNeqNFUNssgP6XB9bbtvc1T7aMMUUsx1CdXfuvSoRWZl6R1EbZcVUjjkv9aD',
		'9PIh++ZvbXgl/Xo9fGkxSF0hwwHfgiv1cNJd7++icbh+fOx2MW1+FZ9ldj9kC1IC7uMmg+Jj9vZDuvcc42+JYYfTm5ZxvIxo46KN',
		'jAhTlhGjDC91WKXsUj5sZg2blFhOmrTta9ziLUhhGDe/FSkPjdOxuG1WHDdppNu+HovWRoquNd7nVuSsUFZquGWXEa+Tzax+3bd9',
		'k/Grl8FxueWRlyQLpcCRcamvUoiue5bZ7T63ENc5oB4ywJ0loZUqMm7H74jhhtk2GYP3VSa0jMiluEpWueMw25DSKidn15S8r0p1',
		'J6fLrpxuqkutK4VAyEtOUsv55NYS1zCvdbIho7IUeUfSbetqt4IRq3rUcVIAahWSfFjFS3CzUkyzpAtyULix2V12l2RL1s5vNKrq',
		'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',
	];

	/**
	 * Decode the real-Collabora ODF fixture.
	 *
	 * @return string The package bytes.
	 */
	private function collaboraOdt(): string {
		return (string)base64_decode(implode('', self::COLLABORA_ODT_B64));

	}//end collaboraOdt()

	/**
	 * The ODF path survives a real Collabora package too, `mimetype` included.
	 *
	 * @return void
	 */
	public function testARealCollaboraOpenDocumentRoundTrips(): void {
		$before = $this->collaboraOdt();

		$read = $this->codec->readBlocks($before, 'odt');
		$this->assertSame(PackageCodec::FORMAT_ODF, $read['format']);

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
			'odt',
			[['anchor' => $target['anchor'], 'action' => 'replace', 'text' => 'Binnen drie weken beoordeeld.']]
		)['bytes'];

		$this->assertContains(
			'Binnen drie weken beoordeeld.',
			array_column($this->codec->readBlocks($after, 'odt')['blocks'], 'text')
		);

		$path = tempnam(sys_get_temp_dir(), 'filinq-test-');
		$this->spilled[] = $path;
		file_put_contents($path, $before);
		$zip = new ZipArchive();
		$zip->open($path);
		$names = [];
		for ($i = 0; $i < $zip->numFiles; $i++) {
			$names[] = (string)$zip->getNameIndex($i);
		}

		$zip->close();

		$this->assertGreaterThanOrEqual(9, count($names), 'the fixture must stay a REAL package');

		foreach ($names as $entry) {
			if ($entry === 'content.xml') {
				continue;
			}

			$this->assertSame(
				$this->entry($before, $entry),
				$this->entry($after, $entry),
				$entry . ' must survive an edit to content.xml byte-identical'
			);
		}

		$this->assertSame(
			'application/vnd.oasis.opendocument.text',
			$this->entry($after, 'mimetype'),
			'the ODF mimetype entry identifies the package to every reader'
		);

	}//end testARealCollaboraOpenDocumentRoundTrips()

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
