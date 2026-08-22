<?php

/**
 * Unit tests for XmlBlockScanner — locating element spans by tag.
 *
 * 🔴 The property these tests exist for: a tag name must match a whole element
 * name, not a prefix. `<text:p>` and `<text:page-number>` both begin with
 * `<text:p`, so a scanner that stops at the prefix rewrites the wrong element —
 * and the document still parses afterwards, which is what makes the damage hard
 * to attribute.
 *
 * @category Test
 * @package  OCA\Filinq\Tests\Unit\Service\Editing
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.1
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Editing;

use OCA\Filinq\Service\Editing\XmlBlockScanner;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the XML block scanner.
 *
 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.1
 */
class XmlBlockScannerTest extends TestCase {
	/**
	 * The scanner under test.
	 *
	 * @var XmlBlockScanner
	 */
	private XmlBlockScanner $scanner;

	/**
	 * Build the scanner.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->scanner = new XmlBlockScanner();
	}//end setUp()

	/**
	 * The substring a span points at.
	 *
	 * @param string             $xml  The document.
	 * @param array{0:int,1:int} $span The span.
	 *
	 * @return string The slice.
	 */
	private function slice(string $xml, array $span): string {
		return substr($xml, $span[0], $span[1]);
	}//end slice()

	/**
	 * A span covers the element from its opening tag to its closing tag.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.1
	 */
	public function testASpanCoversTheWholeElement(): void {
		$xml = '<body><text:p>one</text:p></body>';

		$spans = $this->scanner->spans(xml: $xml, tag: 'text:p');

		$this->assertCount(1, $spans);
		$this->assertSame('<text:p>one</text:p>', $this->slice($xml, $spans[0]));
	}//end testASpanCoversTheWholeElement()

	/**
	 * 🔴 A longer element name that merely STARTS with the tag is not matched.
	 *
	 * `<text:page-number>` begins with `<text:p`. A scanner that stops at the
	 * prefix returns a span over the wrong element, and the rewritten document
	 * still parses — so nothing reports the damage.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.1
	 */
	public function testAPrefixMatchIsNotAnElementMatch(): void {
		$xml = '<body><text:page-number>7</text:page-number><text:p>one</text:p></body>';

		$spans = $this->scanner->spans(xml: $xml, tag: 'text:p');

		$this->assertCount(1, $spans, 'only the real text:p element may match');
		$this->assertSame('<text:p>one</text:p>', $this->slice($xml, $spans[0]));
	}//end testAPrefixMatchIsNotAnElementMatch()

	/**
	 * An element carrying attributes is still matched.
	 *
	 * The control for the test above: if the scanner required `<text:p>`
	 * exactly, that test would pass while the scanner found nothing useful.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.1
	 */
	public function testAnElementWithAttributesIsMatched(): void {
		$xml = '<body><text:p text:style-name="P1">one</text:p></body>';

		$spans = $this->scanner->spans(xml: $xml, tag: 'text:p');

		$this->assertCount(1, $spans);
		$this->assertStringContainsString('style-name', $this->slice($xml, $spans[0]));
	}//end testAnElementWithAttributesIsMatched()

	/**
	 * A self-closing element is a span of its own.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.1
	 */
	public function testASelfClosingElementIsASpan(): void {
		$xml = '<body><text:p/></body>';

		$spans = $this->scanner->spans(xml: $xml, tag: 'text:p');

		$this->assertCount(1, $spans);
		$this->assertSame('<text:p/>', $this->slice($xml, $spans[0]));
	}//end testASelfClosingElementIsASpan()

	/**
	 * Every occurrence is found, in document order.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.1
	 */
	public function testEveryOccurrenceIsFoundInOrder(): void {
		$xml = '<body><text:p>one</text:p><text:p>two</text:p><text:p>three</text:p></body>';

		$spans = $this->scanner->spans(xml: $xml, tag: 'text:p');

		$this->assertCount(3, $spans);
		$this->assertSame('<text:p>one</text:p>', $this->slice($xml, $spans[0]));
		$this->assertSame('<text:p>three</text:p>', $this->slice($xml, $spans[2]));
		$this->assertGreaterThan($spans[0][0], $spans[1][0], 'spans must come back in document order');
	}//end testEveryOccurrenceIsFoundInOrder()

	/**
	 * A tag that is not present yields no spans.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.1
	 */
	public function testAnAbsentTagYieldsNothing(): void {
		$this->assertSame([], $this->scanner->spans(xml: '<body><a/></body>', tag: 'text:p'));
		$this->assertSame([], $this->scanner->spans(xml: '', tag: 'text:p'));
	}//end testAnAbsentTagYieldsNothing()

	/**
	 * Spans for several tags come back merged in document order.
	 *
	 * Order is the point: a rewriter walking these applies offsets in sequence,
	 * and an out-of-order span shifts every later edit onto the wrong bytes.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.1
	 */
	public function testSpansForSeveralTagsAreOrdered(): void {
		$xml = '<body><text:h>title</text:h><text:p>one</text:p><text:h>second</text:h></body>';

		$spans = $this->scanner->spansForTags(xml: $xml, tags: ['text:p', 'text:h']);

		$this->assertCount(3, $spans);
		$this->assertSame('<text:h>title</text:h>', $this->slice($xml, $spans[0]));
		$this->assertSame('<text:p>one</text:p>', $this->slice($xml, $spans[1]));
		$this->assertSame('<text:h>second</text:h>', $this->slice($xml, $spans[2]));
	}//end testSpansForSeveralTagsAreOrdered()
}//end class
