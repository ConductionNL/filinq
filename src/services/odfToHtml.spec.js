/**
 * Tests for the ODF → HTML / text transform used by the ODT preview.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 */

import { odfXmlToHtml, odfXmlToText } from './odfToHtml.js'

const content = (body) =>
	'<?xml version="1.0" encoding="UTF-8"?>'
	+ '<office:document-content'
	+ ' xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"'
	+ ' xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0"'
	+ ' xmlns:table="urn:oasis:names:tc:opendocument:xmlns:table:1.0">'
	+ '<office:body><office:text>'
	+ body
	+ '</office:text></office:body>'
	+ '</office:document-content>'

describe('odfXmlToHtml', () => {
	it('renders headings with the outline level', () => {
		const html = odfXmlToHtml(
			content('<text:h text:outline-level="1">Titel</text:h>'),
		)
		expect(html).toContain('<h1>Titel</h1>')
	})

	it('renders a paragraph and unwraps text:span, escaping entities', () => {
		const html = odfXmlToHtml(
			content(
				'<text:p>Betrokkene <text:span>Jan Jansen</text:span> &amp; co</text:p>',
			),
		)
		expect(html).toContain('<p>Betrokkene Jan Jansen &amp; co</p>')
	})

	it('renders tables as table/tr/td', () => {
		const html = odfXmlToHtml(
			content(
				'<table:table><table:table-row><table:table-cell><text:p>BSN 123456789</text:p></table:table-cell></table:table-row></table:table>',
			),
		)
		expect(html).toContain(
			'<table><tr><td><p>BSN 123456789</p></td></tr></table>',
		)
	})

	it('renders lists as ul/li and line breaks as <br>', () => {
		const html = odfXmlToHtml(
			content(
				'<text:list><text:list-item><text:p>Punt<text:line-break/>een</text:p></text:list-item></text:list>',
			),
		)
		expect(html).toContain('<ul><li><p>Punt<br>een</p></li></ul>')
	})

	it('never emits executable markup from document text (XSS-safe)', () => {
		const html = odfXmlToHtml(
			content('<text:p>&lt;script&gt;alert(1)&lt;/script&gt;</text:p>'),
		)
		expect(html).not.toContain('<script>')
		expect(html).toContain('&lt;script&gt;')
	})

	it('returns an empty string for unparseable input', () => {
		expect(odfXmlToHtml('not xml <<<')).toBe('')
		expect(odfXmlToHtml('')).toBe('')
	})
})

describe('odfXmlToText', () => {
	it('concatenates paragraph, heading and table-cell text (one paragraph per line)', () => {
		const text = odfXmlToText(
			content(
				'<text:h text:outline-level="1">Titel</text:h>'
					+ '<text:p>Betrokkene <text:span>Jan Jansen</text:span> hier</text:p>'
					+ '<table:table><table:table-row><table:table-cell><text:p>BSN 123456789</text:p></table:table-cell></table:table-row></table:table>',
			),
		)
		expect(text).toContain('Titel')
		expect(text).toContain('Betrokkene Jan Jansen hier')
		expect(text).toContain('BSN 123456789')
	})

	it('returns an empty string for unparseable input', () => {
		expect(odfXmlToText('not xml <<<')).toBe('')
	})
})
