// @vitest-environment jsdom
/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The file viewer's routing: which viewer opens a file, and which props it
 * gets. An EML message is shown as the server-rendered PDF preview, so it
 * routes through PdfViewer with the preview URL keyed by the file id, and
 * every other kind loads its bytes from the WebDAV path.
 *
 * @spec openspec/changes/archive/2026-10-09-eml-viewer-preview/tasks.md#task-10
 */

import { describe, expect, it } from 'vitest'
import { emlPreviewUrl } from '../../src/services/fileViewerService.js'
import {
	detectViewer,
	viewerComponentFor,
	viewerPropsFor,
} from '../../src/services/viewerRouting.js'

describe('detectViewer', () => {
	it('routes message/rfc822 to the eml viewer', () => {
		expect(detectViewer({ fileName: 'mail', mimeType: 'message/rfc822' })).toBe(
			'eml',
		)
	})

	it('routes an .eml name to the eml viewer whatever the mime says', () => {
		expect(
			detectViewer({
				fileName: 'Bericht.EML',
				mimeType: 'application/octet-stream',
			}),
		).toBe('eml')
	})

	it('keeps pdf, word, odt and text on their own viewers', () => {
		expect(detectViewer({ fileName: 'a.pdf', mimeType: '' })).toBe('pdf')
		expect(detectViewer({ fileName: 'a.docx', mimeType: '' })).toBe('word')
		expect(detectViewer({ fileName: 'a.odt', mimeType: '' })).toBe('odt')
		expect(detectViewer({ fileName: 'a.txt', mimeType: '' })).toBe('text')
	})

	it('answers null for no file and for an unsupported type', () => {
		expect(detectViewer(null)).toBe(null)
		expect(
			detectViewer({ fileName: 'a.zip', mimeType: 'application/zip' }),
		).toBe(null)
	})
})

describe('viewerComponentFor', () => {
	it('shows an eml message in the PDF viewer', () => {
		expect(viewerComponentFor('eml')).toBe('PdfViewer')
	})

	it('maps the other kinds and nothing for an unknown one', () => {
		expect(viewerComponentFor('pdf')).toBe('PdfViewer')
		expect(viewerComponentFor('word')).toBe('WordViewer')
		expect(viewerComponentFor('odt')).toBe('OdtViewer')
		expect(viewerComponentFor('text')).toBe('TextViewer')
		expect(viewerComponentFor(null)).toBe(null)
	})
})

describe('viewerPropsFor', () => {
	it('gives an eml message the preview URL of its file id beside the path', () => {
		const props = viewerPropsFor({ fileId: 42, path: '/Mail/a.eml' }, 'eml')
		expect(props.path).toBe('/Mail/a.eml')
		expect(props.url).toBe(emlPreviewUrl(42))
		expect(props.url).toContain('/apps/filinq/api/anonymization/eml-preview/')
	})

	it('gives every other kind the path only, so it loads over WebDAV', () => {
		expect(viewerPropsFor({ fileId: 42, path: '/a.pdf' }, 'pdf')).toEqual({
			path: '/a.pdf',
		})
	})

	it('gives nothing when there is no file', () => {
		expect(viewerPropsFor(null, 'eml')).toEqual({})
	})
})
