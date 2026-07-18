/**
 * Tests for the anonymisation upload allow-list + file partitioning.
 *
 * Verifies ODT (OpenDocument Text) is accepted alongside docx/pdf/txt/eml
 * (odt-anonymisation-frontend), by both MIME and extension, and that
 * unsupported formats are still rejected.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 */

import {
	ACCEPT_ATTR,
	ALLOWED_EXTENSIONS,
	ALLOWED_MIMES,
	partitionFiles,
} from './anonymizationUpload.js'

const ODT_MIME = 'application/vnd.oasis.opendocument.text'

/**
 * Build a minimal File-like stub.
 *
 * @param {string} name File name.
 * @param {string} type MIME type.
 * @return {{name: string, type: string}}
 */
const file = (name, type = '') => ({ name, type })

describe('anonymizationUpload allow-list', () => {
	it('includes odt in the allowed extensions, MIME set and accept attribute', () => {
		expect(ALLOWED_EXTENSIONS).toContain('odt')
		expect(ALLOWED_MIMES.has(ODT_MIME)).toBe(true)
		expect(ACCEPT_ATTR).toContain('.odt')
		expect(ACCEPT_ATTR).toContain(ODT_MIME)
	})

	it('keeps accepting the previously-supported formats', () => {
		expect(ALLOWED_EXTENSIONS).toEqual(expect.arrayContaining(['docx', 'txt', 'pdf', 'eml']))
	})
})

describe('partitionFiles', () => {
	it('accepts an .odt file matched by MIME type', () => {
		const { accepted, rejected } = partitionFiles([file('brief.odt', ODT_MIME)])
		expect(accepted).toHaveLength(1)
		expect(rejected).toHaveLength(0)
	})

	it('accepts an .odt file matched by extension when MIME is missing (drag-and-drop)', () => {
		const { accepted, rejected } = partitionFiles([file('brief.odt', '')])
		expect(accepted).toHaveLength(1)
		expect(rejected).toHaveLength(0)
	})

	it('accepts a mix of supported formats including odt', () => {
		const { accepted, rejected } = partitionFiles([
			file('a.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
			file('b.odt', ODT_MIME),
			file('c.pdf', 'application/pdf'),
			file('d.txt', 'text/plain'),
			file('e.eml', 'message/rfc822'),
		])
		expect(accepted).toHaveLength(5)
		expect(rejected).toHaveLength(0)
	})

	it('rejects unsupported formats while accepting odt in the same batch', () => {
		const { accepted, rejected } = partitionFiles([
			file('keep.odt', ODT_MIME),
			file('drop.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
			file('drop.png', 'image/png'),
		])
		expect(accepted.map((f) => f.name)).toEqual(['keep.odt'])
		expect(rejected.map((f) => f.name)).toEqual(['drop.xlsx', 'drop.png'])
	})
})
