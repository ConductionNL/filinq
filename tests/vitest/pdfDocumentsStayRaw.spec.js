/**
 * A loaded pdf.js document is kept out of Vue's reactivity.
 *
 * pdf.js keeps its state in private class fields (`#field`). Read through a
 * reactive proxy, a getter such as `numPages` throws "Cannot read private
 * member ... from an object whose class did not declare it". On a live
 * instance that made the signing field placement editor answer "This document
 * cannot be shown as a PDF" for every PDF. The components store the document
 * with markRaw(), so a reactive read hands back the document itself.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @vitest-environment jsdom
 * @spec openspec/specs/document-preview/spec.md#requirement-format-specific-in-app-document-preview-req-ddprv-001
 */

import { join } from 'node:path'
import { reactive } from 'vue'
import { describe, expect, it, vi } from 'vitest'
import { createInstance, loadSfcOptions } from './sfc/sfcOptions.js'

/** A pdf.js document double that, like pdf.js, keeps its page count in a private field. */
class PrivateFieldDocument {
	#pages = 3

	/** @return {number} The page count. */
	get numPages() {
		return this.#pages
	}

	/** @return {void} */
	destroy() {}
}

const pdfjs = {
	GlobalWorkerOptions: {},
	getDocument: () => ({ promise: Promise.resolve(new PrivateFieldDocument()) }),
	TextLayer: class {
		async render() {}
	},
}

vi.mock('@nextcloud/l10n', () => ({ translate: (app, text) => text }))
vi.mock('@nextcloud/router', () => ({ generateUrl: (url) => url }))
vi.mock('@nextcloud/vue', () => ({}))
vi.mock('pdfjs-dist/build/pdf.mjs', () => pdfjs)
vi.mock('pdfjs-dist/build/pdf.worker.min.mjs?url', () => ({ default: 'w' }))
vi.mock('../../src/services/pdfjsLoader.js', () => ({ loadPdfjs: async () => pdfjs }))
vi.mock('../../src/services/fileViewerService.js', () => ({
	fetchFileAsArrayBuffer: async () => new ArrayBuffer(4),
	fetchUrlAsArrayBuffer: async () => new ArrayBuffer(4),
}))
vi.mock('../../src/store/store.js', () => ({ fileViewerStore: {} }))
vi.mock('../../src/services/highlightDom.js', () => ({
	applyDomHighlights: () => {},
	clearDomHighlights: () => {},
}))

describe('a loaded pdf.js document stays raw', () => {
	it('the signing field placement editor reads the page count through a reactive proxy', async () => {
		const options = await loadSfcOptions(
			join(__dirname, '../../src/views/signing/FieldPlacementEditor.vue'),
		)
		const vm = createInstance(options, { props: { fileId: 7 } })
		vm.showPage = vi.fn(async () => {})

		await vm.load()

		expect(vm.error).toBe('')
		expect(vm.pageCount).toBe(3)
		expect(reactive(vm).pdfDoc.numPages).toBe(3)
	})

	it('the PDF viewer reads the page count through a reactive proxy', async () => {
		const options = await loadSfcOptions(
			join(__dirname, '../../src/components/viewers/PdfViewer.vue'),
		)
		const vm = createInstance(options, { props: { url: '/file.pdf' } })
		vm.renderPage = vi.fn(async () => {})

		await vm.load()

		expect(vm.error).toBe(null)
		expect(reactive(vm).pdfDoc.numPages).toBe(3)
	})
})
