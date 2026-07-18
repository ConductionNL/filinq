<template>
	<div class="pdf-viewer" :class="{ 'dd-marking-cursor': isAddMode }" @mouseup="captureSelection">
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
import { fetchFileAsArrayBuffer, fetchUrlAsArrayBuffer } from '../../services/fileViewerService.js'
import { fileViewerStore } from '../../store/store.js'
import { applyDomHighlights } from '../../services/highlightDom.js'

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
		// Optional: fetch the PDF bytes from this URL instead of deriving a
		// WebDAV URL from `path`. Used for server-rendered content that isn't a
		// plain file — e.g. the EML original preview endpoint.
		url: {
			type: String,
			default: '',
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
	computed: {
		/**
		 * Entities the sidebar asked to mark in the document. Read through a
		 * computed (not a store string-path) so the watcher fires reliably.
		 *
		 * @return {Array<{value: string, type: string}>}
		 */
		highlightEntities() {
			return fileViewerStore.highlightEntities || []
		},
		/**
		 * Pending selection to mark distinctly — only while in add mode.
		 *
		 * @return {string}
		 */
		pendingValue() {
			return fileViewerStore.addMode ? (fileViewerStore.selection || '') : ''
		},
		/**
		 * Whether the viewer is in add mode — drives the marking (highlighter)
		 * cursor so it is obvious the user can select text to add an entity.
		 *
		 * @return {boolean}
		 */
		isAddMode() {
			return fileViewerStore.addMode
		},
	},
	watch: {
		path: {
			immediate: true,
			handler() {
				this.load()
			},
		},
		url() {
			this.load()
		},
		highlightEntities: {
			deep: true,
			handler() {
				this.scheduleHighlights()
			},
		},
		pendingValue() {
			this.scheduleHighlights()
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
					this.url ? fetchUrlAsArrayBuffer(this.url) : fetchFileAsArrayBuffer(this.path),
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

			// Text layer for selection. pdfjs v5 positions the text spans purely
			// through CSS custom properties: it writes per-span `--font-height` /
			// `--scale-x` / `--rotate`, but the container must supply the scale
			// factors the layout maths reads back. Without `--total-scale-factor`
			// the layer collapses to 0×0 and the (transparent) spans get no
			// font-size, so nothing is selectable. The official viewer sets these
			// on the page element; we set them on our text-layer container.
			const textLayerDiv = document.createElement('div')
			textLayerDiv.className = 'pdf-viewer__text-layer'
			textLayerDiv.style.setProperty('--scale-factor', scale)
			textLayerDiv.style.setProperty('--total-scale-factor', scale)
			textLayerDiv.style.setProperty('--scale-round-x', '1px')
			textLayerDiv.style.setProperty('--scale-round-y', '1px')
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

			// Mark detected entities as soon as this page's text layer exists,
			// so highlights stream in with the pages instead of waiting for the
			// whole document.
			this.applyHighlightsTo(textLayerDiv)
		},
		/**
		 * Re-apply entity highlights to every rendered page after the entity
		 * list or the pending selection changed. pdfjs spans live outside Vue's
		 * render tree, so we query them from the DOM rather than template refs.
		 *
		 * @return {void}
		 */
		scheduleHighlights() {
			this.$nextTick(() => {
				const layers = this.$el
					? this.$el.querySelectorAll('.pdf-viewer__text-layer')
					: []
				layers.forEach((layer) => this.applyHighlightsTo(layer))
			})
		},
		/**
		 * Wrap matching entity values inside one page's transparent text layer
		 * in highlight spans. The spans sit over the canvas glyphs; `multiply`
		 * blending (see CSS) keeps the printed text readable through the tint.
		 *
		 * Known limitation: matching is per text node, and pdfjs splits a line
		 * into several positioned spans — so a value spread across a span break
		 * (e.g. a name wrapping mid-line) is not highlighted. Single-span values
		 * (most names, emails, numbers) mark correctly.
		 *
		 * @param {HTMLElement} layer Text-layer container for one page.
		 * @return {void}
		 */
		applyHighlightsTo(layer) {
			applyDomHighlights(layer, this.highlightEntities, this.pendingValue)
		},
		/**
		 * Push the current text selection into the viewer store so the
		 * "add selection as entity" flow can pick it up.
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
	box-shadow: var(--dd-shadow-doc);
	max-width: 100%;
}

.pdf-viewer__canvas {
	display: block;
}
</style>

<style>
/*
 * Unscoped: pdfjs writes its text-layer spans into our container without our
 * scope hash. These rules mirror the parts of pdfjs' own pdf_viewer.css that
 * the v5 TextLayer relies on — the spans are laid out via CSS custom
 * properties (font-size derived from --font-height, transform from --scale-x /
 * --rotate), so they stay invisible-but-selectable on top of the canvas.
 */
.pdf-viewer__text-layer {
	position: absolute;
	inset: 0;
	overflow: hidden;
	opacity: 1;
	line-height: 1;
	text-align: initial;
	forced-color-adjust: none;
	transform-origin: 0 0;
	z-index: 1;
	--min-font-size: 1;
	--text-scale-factor: calc(var(--total-scale-factor) * var(--min-font-size));
	--min-font-size-inv: calc(1 / var(--min-font-size));
}

.pdf-viewer__text-layer :is(span, br) {
	color: transparent;
	position: absolute;
	white-space: pre;
	cursor: text;
	transform-origin: 0% 0%;
}

.pdf-viewer__text-layer > :not(.markedContent),
.pdf-viewer__text-layer .markedContent span:not(.markedContent) {
	z-index: 1;
	--font-height: 0;
	font-size: calc(var(--text-scale-factor) * var(--font-height));
	--scale-x: 1;
	--rotate: 0deg;
	transform: rotate(var(--rotate)) scaleX(var(--scale-x)) scale(var(--min-font-size-inv));
}

.pdf-viewer__text-layer .markedContent {
	display: contents;
}

/*
 * Entity highlights injected by applyDomHighlights live inside the transparent
 * text spans, on top of the canvas where the actual glyphs are painted. The
 * fill must be translucent or it covers the glyph and the text becomes
 * unreadable. `mix-blend-mode` cannot help here: pdfjs puts a `transform` on
 * every span, which creates a stacking context that traps the blend inside the
 * (transparent) span, so it never reaches the canvas. `opacity` composites the
 * highlight semi-transparently regardless, letting the glyph show through.
 * We also drop the base .dd-hl padding/shadow so the tint stays aligned to the
 * glyphs underneath.
 *
 * The position/font-size/transform resets are essential: pdfjs' own rules
 * above (`:is(span, br)` → position: absolute, and the markedContent transform
 * rule) target *every* span in the layer, written on the assumption that the
 * only spans present are its positioned glyph runs. Our highlight spans are
 * nested inside those runs, so without these resets they get pulled out of the
 * inline flow (absolute, no offsets) and stack on top of each other — breaking
 * the line so following text renders behind them. Forcing them back to a plain
 * inline box lets them flow normally while the parent run keeps its transform.
 */
.pdf-viewer__text-layer .dd-hl {
	position: static;
	font-size: inherit;
	transform: none;
	padding: 0;
	box-shadow: none;
	border-radius: 2px;
	opacity: 0.45;
}

/*
 * Keep the text transparent while selected too. The spans are transparent at
 * rest, but a browser's ::selection paints selected text in its own colour,
 * which makes the (slightly misaligned) overlay glyphs visible on top of the
 * canvas. Forcing the selected colour transparent leaves only the selection
 * background, so the canvas glyphs stay the single source of visible text.
 */
.pdf-viewer__text-layer ::selection {
	color: transparent;
	background: rgba(0, 100, 255, 0.4);
}
</style>
