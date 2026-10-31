/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */
import { sanitizeHtml } from './sanitizeHtml.js'

describe('sanitizeHtml', () => {
	it('removes scripts, event handlers and javascript: links', () => {
		const out = sanitizeHtml('<p onclick="steal()">Hi<script>steal()</script> <a href="javascript:steal()">link</a></p>')

		expect(out).not.toContain('<script')
		expect(out).not.toContain('onclick')
		expect(out).not.toContain('javascript:')
		expect(out).toContain('Hi')
		expect(out).toContain('link')
	})

	it('keeps formatting and ordinary links', () => {
		const html = '<h1>Title</h1><p><strong>bold</strong> <a href="https://example.org">site</a></p>'

		expect(sanitizeHtml(html)).toBe(html)
	})

	it('answers an empty string for nothing', () => {
		expect(sanitizeHtml(null)).toBe('')
		expect(sanitizeHtml(undefined)).toBe('')
	})
})
