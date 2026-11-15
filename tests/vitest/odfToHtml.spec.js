// @vitest-environment jsdom
/**
 * The ODF content.xml to HTML and text conversion: headings clamped to
 * h1..h6, paragraphs, breaks, spaces, tables and lists, escaped text,
 * dropped images, and empty output for empty or malformed XML.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 */

import { describe, expect, it } from 'vitest'
import { odfXmlToHtml, odfXmlToText } from '../../src/services/odfToHtml.js'

const NS = 'xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" '
	+ 'xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0" '
	+ 'xmlns:table="urn:oasis:names:tc:opendocument:xmlns:table:1.0" '
	+ 'xmlns:draw="urn:oasis:names:tc:opendocument:xmlns:drawing:1.0"'

const doc = (body) => `<office:document-content ${NS}><office:body><office:text>${body}</office:text></office:body></office:document-content>`

describe('odfXmlToHtml', () => {
	it('renders headings at their outline level, clamped to h1..h6', () => {
		expect(odfXmlToHtml(doc(
			'<text:h text:outline-level="1">A</text:h>'
			+ '<text:h text:outline-level="9">B</text:h>'
			+ '<text:h>C</text:h>'
			+ '<text:h text:outline-level="0">D</text:h>',
		))).toBe('<h1>A</h1><h6>B</h6><h2>C</h2><h1>D</h1>')
	})

	it('renders paragraphs with breaks, tabs and repeated spaces', () => {
		expect(odfXmlToHtml(doc(
			'<text:p>a<text:line-break/>b<text:tab/>c<text:s text:c="3"/>d<text:s/>e<text:s text:c="0"/>f</text:p>',
		))).toBe('<p>a<br>b c   d e f</p>')
	})

	it('renders tables and lists', () => {
		expect(odfXmlToHtml(doc(
			'<table:table><table:table-row><table:table-cell><text:p>x</text:p></table:table-cell></table:table-row></table:table>'
			+ '<text:list><text:list-item><text:p>y</text:p></text:list-item></text:list>',
		))).toBe('<table><tr><td><p>x</p></td></tr></table><ul><li><p>y</p></li></ul>')
	})

	it('escapes text and drops images and embedded objects', () => {
		expect(odfXmlToHtml(doc(
			'<text:p>&lt;b&gt; &amp; co<draw:image/><draw:object/></text:p>',
		))).toBe('<p>&lt;b&gt; &amp; co</p>')
	})

	it('renders unknown elements by their children only', () => {
		expect(odfXmlToHtml(doc('<text:p><text:span>in</text:span></text:p>'))).toBe('<p>in</p>')
	})

	it('falls back to the document element when there is no body', () => {
		expect(odfXmlToHtml(`<text:p ${NS}>only</text:p>`)).toBe('<p>only</p>')
	})

	it('is empty for empty, non-string or malformed input', () => {
		expect(odfXmlToHtml('')).toBe('')
		expect(odfXmlToHtml('   ')).toBe('')
		expect(odfXmlToHtml(null)).toBe('')
		expect(odfXmlToHtml('<unclosed>')).toBe('')
	})
})

describe('odfXmlToText', () => {
	it('lists paragraph texts first, then headings, skipping empty ones', () => {
		expect(odfXmlToText(doc(
			'<text:h text:outline-level="1">Kop</text:h><text:p>een</text:p><text:p/><text:p>twee</text:p>',
		))).toBe('een\ntwee\nKop')
	})

	it('is empty for malformed input', () => {
		expect(odfXmlToText('<a>')).toBe('')
		expect(odfXmlToText(42)).toBe('')
	})
})
