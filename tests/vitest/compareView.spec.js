// @vitest-environment jsdom
/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The compare view's contract with other apps (anonymization-review-workbench
 * REQ-DDARW-014): `OCA.Filinq.mountCompare(el, { original, delivered, labels })`
 * picks the file viewer's own viewer per side, reads a file from a URL when it
 * is not in the user's storage, refuses unusable input by naming the side, and
 * returns a handle that unmounts once.
 */

import { describe, expect, it, vi } from 'vitest'
import { assertCompareFiles, comparePane } from '../../src/compare/comparePanes.js'
import { mountCompare } from '../../src/compare/mountCompare.js'

const pdf = {
	fileName: 'besluit.pdf',
	mimeType: 'application/pdf',
	url: '/apps/dossiq/x/original',
}
const docx = {
	fileName: 'nota.docx',
	mimeType:
		'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
	path: '/Woo/nota.docx',
}

describe('comparePane', () => {
	it("uses the file viewer's viewer for the type and reads a URL file from its URL", () => {
		const pane = comparePane(pdf)
		expect(pane.component).toBe('PdfViewer')
		expect(pane.props).toEqual({ path: pdf.url, url: pdf.url })
		expect(pane.downloadUrl).toBe(pdf.url)
	})

	it("reads a file in the user's storage over its path", () => {
		const pane = comparePane(docx)
		expect(pane.component).toBe('WordViewer')
		expect(pane.props).toEqual({ path: '/Woo/nota.docx' })
		expect(pane.downloadUrl).toBe('')
	})

	it('passes the URL to the Word viewer too, not only to the PDF viewer', () => {
		const pane = comparePane({
			...docx,
			path: undefined,
			url: '/x/delivered',
			downloadUrl: '/x/delivered?download=1',
		})
		expect(pane.component).toBe('WordViewer')
		expect(pane.props.url).toBe('/x/delivered')
		expect(pane.downloadUrl).toBe('/x/delivered?download=1')
	})

	it('has no viewer for a type nothing renders, and keeps the download link', () => {
		const pane = comparePane({
			fileName: 'plan.dwg',
			mimeType: 'image/vnd.dwg',
			url: '/x/plan',
		})
		expect(pane.component).toBeNull()
		expect(pane.downloadUrl).toBe('/x/plan')
	})
})

describe('assertCompareFiles', () => {
	it('accepts two usable files', () => {
		expect(() =>
			assertCompareFiles({ original: pdf, delivered: docx }),
		).not.toThrow()
	})

	it('names the missing side', () => {
		expect(() => assertCompareFiles({ original: pdf })).toThrow(
			'the delivered file is missing',
		)
	})

	it('refuses a file with neither a path nor a URL', () => {
		expect(() =>
			assertCompareFiles({ original: { fileName: 'a.pdf' }, delivered: pdf }),
		).toThrow('the original file needs a path or a url')
	})

	it('refuses no options at all', () => {
		expect(() => assertCompareFiles(null)).toThrow(TypeError)
	})
})

describe('mountCompare', () => {
	/**
	 * A fake createApp recording what it was given.
	 *
	 * @return {object}
	 */
	function fakeVue() {
		const app = { mount: vi.fn(), unmount: vi.fn() }
		const createApp = vi.fn(() => app)
		return { app, createApp }
	}

	const el = { nodeType: 1 }

	it('mounts the component with both files and the labels into the host element', () => {
		const { app, createApp } = fakeVue()
		const component = { name: 'DocumentCompare' }
		mountCompare(
			el,
			{ original: pdf, delivered: docx, labels: { delivered: 'Geleverd' } },
			{ createApp, component },
		)
		expect(createApp).toHaveBeenCalledWith(component, {
			original: pdf,
			delivered: docx,
			labels: { delivered: 'Geleverd' },
		})
		expect(app.mount).toHaveBeenCalledWith(el)
	})

	it('unmounts once, however often the host asks', () => {
		const { app, createApp } = fakeVue()
		const handle = mountCompare(
			el,
			{ original: pdf, delivered: docx },
			{ createApp, component: {} },
		)
		handle.unmount()
		handle.unmount()
		expect(app.unmount).toHaveBeenCalledTimes(1)
	})

	it('refuses something that is not an element, before creating an app', () => {
		const { createApp } = fakeVue()
		expect(() =>
			mountCompare(
				'#compare',
				{ original: pdf, delivered: docx },
				{ createApp, component: {} },
			),
		).toThrow('pass the element to render into')
		expect(createApp).not.toHaveBeenCalled()
	})

	it('refuses an unusable file before creating an app', () => {
		const { createApp } = fakeVue()
		expect(() =>
			mountCompare(el, { original: pdf }, { createApp, component: {} }),
		).toThrow('delivered')
		expect(createApp).not.toHaveBeenCalled()
	})
})
