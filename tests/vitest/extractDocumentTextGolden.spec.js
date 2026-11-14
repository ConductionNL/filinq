// @vitest-environment jsdom
/**
 * Golden: which extractor extractDocumentText picks and what text it returns,
 * recorded on the single-function implementation. pdfjs, mammoth, JSZip and
 * axios are replaced by recording fakes.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec exclude characterization test of existing text extraction behaviour
 */

import { describe, it, vi } from 'vitest'
import { golden } from './golden/golden.js'

const calls = []

vi.mock('@nextcloud/axios', () => ({
	default: {
		get: vi.fn(async (url, opts) => {
			calls.push(['get', url, opts.responseType])
			return {
				data:
					opts.responseType === 'text'
						? 'plain text of ' + url
						: { bytesOf: url },
			}
		}),
	},
}))

vi.mock('pdfjs-dist/build/pdf.mjs', () => ({
	GlobalWorkerOptions: {},
	getDocument: ({ data }) => {
		calls.push(['pdf', data])
		return {
			promise: Promise.resolve({
				numPages: data.bytesOf.includes('empty') ? 0 : 2,
				getPage: async (n) => ({
					getTextContent: async () => ({
						items: [{ str: 'p' + n + 'a' }, { str: '' }, { str: 'b' }],
					}),
				}),
			}),
		}
	},
}))

vi.mock('mammoth/mammoth.browser.js', () => ({
	default: {
		extractRawText: async ({ arrayBuffer }) => {
			calls.push(['mammoth', arrayBuffer])
			if (arrayBuffer.bytesOf.includes('novalue')) {
				return {}
			}
			return {
				value: arrayBuffer.bytesOf.includes('blank') ? '' : 'word text',
			}
		},
	},
}))

vi.mock('jszip', () => ({
	default: {
		loadAsync: async (buf) => {
			calls.push(['zip', buf])
			const xml = {
				'content.xml':
					'<office:document-content xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0"><text:p>Body line</text:p></office:document-content>',
				'styles.xml': buf.bytesOf.includes('blankstyles')
					? '<office:document-styles xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"/>'
					: buf.bytesOf.includes('nostyles')
						? undefined
						: '<office:document-styles xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0"><text:p>Header</text:p></office:document-styles>',
			}
			return {
				file: (part) =>
					xml[part] === undefined
						? null
						: {
								async: async (kind) =>
									kind === 'string' ? xml[part] : null,
							},
			}
		},
	},
}))

const FILES = [
	{ path: '/a/doc.pdf', mimeType: 'application/pdf', fileName: 'doc.pdf' },
	{ path: '/a/empty.pdf', mimeType: '', fileName: 'EMPTY.PDF' },
	{ path: '/a/x', mimeType: 'APPLICATION/PDF' },
	{
		path: '/a/w.docx',
		mimeType:
			'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		fileName: 'w.docx',
	},
	{ path: '/a/old.doc', mimeType: 'application/msword', fileName: 'old.doc' },
	{ path: '/a/blank.docx', fileName: 'blank.DOCX' },
	{
		path: '/a/t.odt',
		mimeType: 'application/vnd.oasis.opendocument.text',
		fileName: 't.odt',
	},
	{ path: '/a/nostyles.odt', fileName: 'nostyles.odt' },
	{ path: '/a/blankstyles.odt', fileName: 'blankstyles.odt' },
	{ path: '/a/novalue.docx', fileName: 'novalue.docx' },
	{ path: '/a/n.txt', mimeType: 'text/plain', fileName: 'n.txt' },
	{ path: '/a/n.md', fileName: 'n.md' },
	{ path: '/a/both.pdf', mimeType: 'application/msword', fileName: 'both.pdf' },
	{ path: '/a/both.odt', mimeType: 'application/msword', fileName: 'both.odt' },
	{ path: '/a/s.ods', mimeType: 'application/vnd.oasis.opendocument.spreadsheet' },
	{ path: '/a/none' },
]

describe('extractDocumentText golden', () => {
	it('extracts every format the recorded way', async () => {
		const { extractDocumentText } =
			await import('../../src/services/fileViewerService.js')
		const out = []
		for (const file of FILES) {
			calls.length = 0
			const text = await extractDocumentText(file)
			out.push({ file, text, calls: [...calls] })
		}
		golden('extractDocumentText', out)
	})
})
