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
import { fileViewerStore } from '../../src/store/store.js'
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
	it('replaces the previous page content with the canvas, the text layer and the region layer', async () => {
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
			'pdf-viewer__region-layer',
		])
		expect(wrapper.textContent).toBe('')
		expect(vm.applyHighlightsTo).toHaveBeenCalledWith(wrapper.children[1])
	})

	// @spec openspec/changes/image-redaction/tasks.md#task-4.1
	it('draws the image regions of the page into its region layer, and redraws them on a new decision', async () => {
		vi.spyOn(HTMLCanvasElement.prototype, 'getContext').mockReturnValue({})
		const options = await loadSfcOptions(
			join(__dirname, '../../src/components/viewers/PdfViewer.vue'),
		)
		const vm = createInstance(options)
		const wrapper = document.createElement('div')
		vm.pageRefs = { 1: wrapper }
		vm.pdfDoc = { getPage: async () => fakePage() }
		vm.applyHighlightsTo = vi.fn()
		const region = { index: 0, page: 1, x: 0.5, y: 0.5, w: 0.25, h: 0.1, type: 'BSN', state: 'redact' }
		fileViewerStore.imageRegions = [region, { ...region, page: 2 }]

		await vm.renderPage(1)

		const layer = wrapper.querySelector('.pdf-viewer__region-layer')
		expect(Array.from(layer.children, (el) => el.className)).toEqual(['dd-region dd-region--redact'])
		expect(layer.children[0].style.left).toBe('50%')

		fileViewerStore.imageRegions = [{ ...region, state: 'keep' }]
		vm.applyRegions()
		expect(Array.from(layer.children, (el) => el.className)).toEqual(['dd-region dd-region--keep'])
		fileViewerStore.imageRegions = undefined
	})
})
