/**
 * PdfViewer.renderPage replaces a page wrapper's previous content with the
 * freshly rendered canvas and text layer.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @vitest-environment jsdom
 * @spec exclude unit test of the PDF page render helper
 */

import { join } from 'node:path'
import { describe, expect, it, vi } from 'vitest'
import { createInstance, loadSfcOptions } from './sfc/sfcOptions.js'

vi.mock('@nextcloud/l10n', () => ({
	translate: (app, text) => text,
}))

vi.mock('@nextcloud/vue', () => ({}))

vi.mock('pdfjs-dist/build/pdf.mjs', () => ({
	GlobalWorkerOptions: {},
	getDocument: () => ({ promise: Promise.resolve({ numPages: 0 }) }),
	TextLayer: class {
		async render() {}
	},
	renderTextLayer: undefined,
}))

vi.mock('pdfjs-dist/build/pdf.worker.min.mjs?url', () => ({ default: 'w' }))

vi.mock('../../src/services/fileViewerService.js', () => ({
	fetchFileAsArrayBuffer: async () => ({}),
	fetchUrlAsArrayBuffer: async () => ({}),
}))

vi.mock('../../src/store/store.js', () => ({
	fileViewerStore: {},
}))

vi.mock('../../src/services/highlightDom.js', () => ({
	applyDomHighlights: () => {},
	clearDomHighlights: () => {},
}))

/**
 * A pdfjs page double with a fixed 100x200 viewport.
 *
 * @return {object} Page.
 */
function fakePage() {
	return {
		getViewport: ({ scale }) => ({ width: 100 * scale, height: 200 * scale }),
		render: () => ({ promise: Promise.resolve() }),
		getTextContent: async () => ({ items: [] }),
	}
}

describe('PdfViewer.renderPage', () => {
	it('replaces the previous page content with the canvas and the text layer', async () => {
		vi.spyOn(HTMLCanvasElement.prototype, 'getContext').mockReturnValue({})
		const options = await loadSfcOptions(
			join(__dirname, '../../src/components/viewers/PdfViewer.vue'),
		)
		const vm = createInstance(options)
		const wrapper = document.createElement('div')
		wrapper.appendChild(document.createElement('p')).textContent = 'stale page'
		vm.pageRefs = { 1: wrapper }
		vm.pdfDoc = { getPage: async () => fakePage() }
		vm.applyHighlightsTo = vi.fn()

		await vm.renderPage(1)

		expect(Array.from(wrapper.children, (el) => el.className)).toEqual([
			'pdf-viewer__canvas',
			'pdf-viewer__text-layer',
		])
		expect(wrapper.textContent).toBe('')
		expect(vm.applyHighlightsTo).toHaveBeenCalledWith(wrapper.children[1])
	})
})
