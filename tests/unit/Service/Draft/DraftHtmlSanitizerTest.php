<?php

/**
 * Tests for DraftHtmlSanitizer.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service\Draft
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Draft;

use OCA\Filinq\Service\Draft\DraftHtmlSanitizer;
use PHPUnit\Framework\TestCase;

/**
 * Active content goes; layout, inline style, image data and typed braces stay.
 *
 * @covers \OCA\Filinq\Service\Draft\DraftHtmlSanitizer
 */
class DraftHtmlSanitizerTest extends TestCase {

	private function sanitize(string $html): string {
		return (new DraftHtmlSanitizer())->sanitize(html: $html);
	}//end sanitize()

	public function testScriptLikeElementsAreRemovedWithTheirContent(): void {
		$this->assertSame(
			'<p>Brief</p>',
			$this->sanitize('<p>Brief</p><script>alert(1)</script><iframe src="https://x"></iframe><object data="x"></object><form><input name="a"></form>')
		);
	}//end testScriptLikeElementsAreRemovedWithTheirContent()

	public function testEventHandlersAndScriptUrlsAreRemoved(): void {
		$this->assertSame(
			'<p>a</p><a>link</a><a href="https://example.org/">ok</a><img>',
			$this->sanitize('<p onclick="x()" ONMOUSEOVER="y()">a</p><a href=" JaVaScRiPt:alert(1)">link</a><a href="https://example.org/">ok</a><img src="data:text/html;base64,PHNjcmlwdD4=">')
		);
	}//end testEventHandlersAndScriptUrlsAreRemoved()

	public function testAnInlinedImageAndLayoutAreKept(): void {
		$html = '<h1 style="color: #333">Titel</h1><table><tr><td class="c">1</td></tr></table><img src="data:image/png;base64,AAAA" alt="logo">';

		$this->assertSame($html, $this->sanitize($html));
	}//end testAnInlinedImageAndLayoutAreKept()

	public function testTypedBracesAndAccentsStayText(): void {
		$this->assertSame('<p>Beste {{ naam }}, één {% if x %}</p>', $this->sanitize('<p>Beste {{ naam }}, één {% if x %}</p>'));
	}//end testTypedBracesAndAccentsStayText()

	public function testScriptInAStyleAttributeIsRemoved(): void {
		$this->assertSame('<p>a</p>', $this->sanitize('<p style="background:url(javascript:alert(1))">a</p>'));
	}//end testScriptInAStyleAttributeIsRemoved()

	public function testAWholeDocumentKeepsItsHeadStyle(): void {
		$result = $this->sanitize('<html><head><style>p { margin: 0 }</style><script>x()</script></head><body><p>Hi</p></body></html>');

		$this->assertSame('<html><head><style>p { margin: 0 }</style></head><body><p>Hi</p></body></html>', $result);
	}//end testAWholeDocumentKeepsItsHeadStyle()

	public function testEmptyHtmlStaysEmpty(): void {
		$this->assertSame('', $this->sanitize("  \n "));
	}//end testEmptyHtmlStaysEmpty()
}//end class
