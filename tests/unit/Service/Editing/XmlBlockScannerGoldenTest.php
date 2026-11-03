<?php

/**
 * Golden cases of the element spans the block scanner finds.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Editing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Editing;

use OCA\Filinq\Service\Editing\XmlBlockScanner;
use OCA\Filinq\Tests\Unit\Support\RecordsCalls;
use PHPUnit\Framework\TestCase;

/**
 * The offset/length pairs spans() and spansForTags() return, compared with
 * JSON recorded from the scanner whose close-tag walk took five parameters.
 *
 * @covers \OCA\Filinq\Service\Editing\XmlBlockScanner
 *
 * @spec openspec/specs/document-editing/spec.md#requirement-untouched-parts-of-a-document-package-survive-an-edit-unchanged
 */
class XmlBlockScannerGoldenTest extends TestCase {
	use RecordsCalls;

	private const GOLDEN = __DIR__.'/golden/xml-block-scanner.json';

	/**
	 * The cases: the XML and the tags.
	 *
	 * @return array<string, array{0: string, 1: array<int, string>}>
	 */
	public static function cases(): array {
		return [
			'siblings'                        => ['<b><w:p>a</w:p><w:p x="1">b</w:p></b>', ['w:p']],
			'nested same name'                => ['<w:p><w:p>in</w:p>out</w:p><w:p/>', ['w:p']],
			'deeply nested'                   => ['<t><t><t>x</t></t></t><t>y</t>', ['t']],
			'a longer name is not the tag'    => ['<w:p><w:pPr/><w:pPr>s</w:pPr>t</w:p>', ['w:p']],
			'self-closing'                    => ['<w:p/><w:p />', ['w:p']],
			'nested self-closing'             => ['<w:p><w:p/>x</w:p>', ['w:p']],
			'nested longer name inside'       => ['<w:tbl><w:tblPr></w:tblPr><w:tbl>i</w:tbl></w:tbl>', ['w:tbl']],
			'tab and newline after the name'  => ["<w:p\tx=\"1\">a</w:p><w:p\n>b</w:p>", ['w:p']],
			'no close tag'                    => ['<w:p>a<w:p>b</w:p>', ['w:p']],
			'an open tag never ends'          => ['<w:p>a</w:p><w:p', ['w:p']],
			'a nested open tag never ends'    => ['<w:p>a<w:p', ['w:p']],
			'only a close tag'                => ['x</w:p>', ['w:p']],
			'no tag at all'                   => ['<a>b</a>', ['w:p']],
			'two tags, sorted by offset'      => ['<w:tbl>t</w:tbl><w:p>p</w:p><w:tbl/>', ['w:p', 'w:tbl']],
			'close before the nested open'    => ['<w:p>a</w:p>b<w:p>c</w:p>', ['w:p']],
		];

	}//end cases()

	/**
	 * The spans match the recorded ones.
	 *
	 * @param string             $xml  The XML.
	 * @param array<int, string> $tags The tags.
	 *
	 * @dataProvider cases
	 *
	 * @return void
	 */
	public function testSpans(string $xml, array $tags): void {
		$scanner = new XmlBlockScanner();
		$this->traceCall(fn () => $scanner->spans(xml: $xml, tag: $tags[0]));
		$this->traceCall(fn () => $scanner->spansForTags(xml: $xml, tags: $tags));

		$this->assertTrace(file: self::GOLDEN, name: (string) $this->dataName());

	}//end testSpans()
}//end class
