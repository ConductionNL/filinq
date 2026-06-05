<template>
	<div class="pdf-viewer" @mouseup="captureSelection">
		<div v-if="loading" class="pdf-viewer__loading">
			<NcLoadingIcon :size="48" />
			<span>{{ t('docudesk', 'Loading document…') }}</span>
		</div>
		<div v-else-if="error" class="pdf-viewer__error">
			{{ error }}
		</div>
		<div v-else class="pdf-viewer__pages">
			<div
				v-for="page in pageCount"
				:key="page"
				:ref="(el) => setPageRef(el, page)"
				class="pdf-viewer__page" />
		</div>
	</div>
</template>

<script>
import { NcLoadingIcon } from '@nextcloud/vue'
import { translate as t } from '@nextcloud/l10n'
import { fetchFileAsArrayBuffer } from '../../services/fileViewerService.js'
import { fileViewerStore } from '../../store/store.js'

let pdfjsLibPromise = null

/**
 * Lazy-load pdfjs-dist plus its worker. Module is heavy (~2MB) so we only pull
 * it in when the user actually opens a PDF.
 *
 * @return {Promise<object>} pdfjsLib module.
 */
async function loadPdfjs() {
	if (!pdfjsLibPromise) {
		pdfjsLibPromise = (async () => {
			// eslint-disable-next-line import/no-unresolved
			const pdfjsLib = await import('pdfjs-dist/build/pdf.mjs')
			const workerUrl = new URL(
				'pdfjs-dist/build/pdf.worker.min.mjs',
				import.meta.url,
			).toString()
			pdfjsLib.GlobalWorkerOptions.workerSrc = workerUrl
			return pdfjsLib
		})()
	}
	return pdfjsLibPromise
}

export default {
	name: 'PdfViewer',
	components: {
		NcLoadingIcon,
	},
	props: {
		path: {
			type: String,
			required: true,
		},
	},
	data() {
		return {
			loading: true,
			error: null,
			pageCount: 0,
			pdfDoc: null,
			pageRefs: {},
		}
	},
	watch: {
		path: {
			immediate: true,
			handler() {
				this.load()
			},
		},
	},
	beforeDestroy() {
		if (this.pdfDoc) {
			this.pdfDoc.destroy()
			this.pdfDoc = null
		}
	},
	methods: {
		/**
		 * Store a ref to each page wrapper so we can render into it once pdfjs
		 * resolves the page. Vue 2's `:ref` callback gets called with `null`
		 * when the element is unmounted, so we filter that out.
		 *
		 * @param {HTMLElement|null} el  Page wrapper element.
		 * @param {number}           page 1-based page number.
		 */
		setPageRef(el, page) {
			if (el) {
				this.pageRefs[page] = el
			}
		},
		/**
		 * Fetch the PDF, parse it, then render every page sequentially.
		 *
		 * @return {Promise<void>}
		 */
		async load() {
			this.loading = true
			this.error = null
			try {
				const [pdfjsLib, data] = await Promise.all([
					loadPdfjs(),
					fetchFileAsArrayBuffer(this.path),
				])
				const loadingTask = pdfjsLib.getDocument({ data })
				this.pdfDoc = await loadingTask.promise
				this.pageCount = this.pdfDoc.numPages
				this.loading = false
				await this.$nextTick()
				for (let p = 1; p <= this.pageCount; p++) {
					await this.renderPage(p)
				}
			} catch (err) {
				console.error('[PdfViewer] failed to load PDF:', err)
				this.error = err.message || t('docudesk', 'Failed to load PDF')
				this.loading = false
			}
		},
		/**
		 * Render one page at a fixed CSS scale and overlay a text layer so the
		 * user can select text (and we can hand the selection off to anonymisation later).
		 *
		 * @param {number} pageNumber 1-based page number.
		 * @return {Promise<void>}
		 */
		async renderPage(pageNumber) {
			const wrapper = this.pageRefs[pageNumber]
			if (!wrapper || !this.pdfDoc) return

			const pdfjsLib = await loadPdfjs()
			const page = await this.pdfDoc.getPage(pageNumber)
			const desiredWidth = wrapper.clientWidth || 800
			const unscaled = page.getViewport({ scale: 1 })
			const scale = desiredWidth / unscaled.width
			const viewport = page.getViewport({ scale })

			const canvas = document.createElement('canvas')
			const ctx = canvas.getContext('2d')
			canvas.width = viewport.width
			canvas.height = viewport.height
			canvas.className = 'pdf-viewer__canvas'
			wrapper.style.position = 'relative'
			wrapper.style.width = `${viewport.width}px`
			wrapper.style.height = `${viewport.height}px`

			wrapper.innerHTML = ''
			wrapper.appendChild(canvas)

			await page.render({ canvasContext: ctx, viewport }).promise

			// Text layer for selection.
			const textLayerDiv = document.createElement('div')
			textLayerDiv.className = 'pdf-viewer__text-layer'
			textLayerDiv.style.width = `${viewport.width}px`
			textLayerDiv.style.height = `${viewport.height}px`
			wrapper.appendChild(textLayerDiv)

			const textContent = await page.getTextContent()
			if (pdfjsLib.TextLayer) {
				const textLayer = new pdfjsLib.TextLayer({
					textContentSource: textContent,
					container: textLayerDiv,
					viewport,
				})
				await textLayer.render()
			} else if (pdfjsLib.renderTextLayer) {
				await pdfjsLib.renderTextLayer({
					textContentSource: textContent,
					container: textLayerDiv,
					viewport,
				}).promise
			}
		},
		/**
		 * Push the current text selection into the viewer store so future
		 * features (e.g. "send selection to anonymisation") can pick it up.
		 */
		captureSelection() {
			const text = window.getSelection()?.toString() || ''
			fileViewerStore.setSelection(text)
		},
	},
}
</script>

<style scoped>
.pdf-viewer {
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 12px;
	padding: 16px;
	background: var(--color-background-dark);
	min-height: 100%;
}

.pdf-viewer__loading,
.pdf-viewer__error {
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 8px;
	padding: 32px;
	color: var(--color-text-maxcontrast);
}

.pdf-viewer__pages {
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 12px;
	width: 100%;
}

.pdf-viewer__page {
	background: white;
	box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
	max-width: 100%;
}

.pdf-viewer__canvas {
	display: block;
}
</style>
