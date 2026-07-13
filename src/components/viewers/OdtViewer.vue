<template>
	<div class="odt-viewer" :class="{ 'dd-marking-cursor': isAddMode }" @mouseup="captureSelection">
		<div v-if="loading" class="odt-viewer__loading">
			<NcLoadingIcon :size="48" />
			<span>{{ t('docudesk', 'Loading document…') }}</span>
		</div>
		<div v-else-if="error" class="odt-viewer__error">
			{{ error }}
		</div>
		<div v-else class="odt-viewer__page">
			<!-- HTML is produced by odfXmlToHtml, a whitelist transform that
				escapes all text and emits no attributes/scripts — safe here. -->
			<!-- eslint-disable-next-line vue/no-v-html -->
			<div ref="content" class="odt-viewer__content" v-html="html" />
		</div>
	</div>
</template>

<script>
import { NcLoadingIcon } from '@nextcloud/vue'
import { translate as t } from '@nextcloud/l10n'
import { fetchFileAsArrayBuffer } from '../../services/fileViewerService.js'
import { odfXmlToHtml } from '../../services/odfToHtml.js'
import { fileViewerStore } from '../../store/store.js'
import { applyDomHighlights, clearDomHighlights } from '../../services/highlightDom.js'

let jsZipPromise = null

/**
 * Lazy-load JSZip (only fetched when the user actually opens an odt).
 *
 * @return {Promise<object>} JSZip module.
 */
async function loadJsZip() {
	if (!jsZipPromise) {
		jsZipPromise = import('jszip')
	}
	return jsZipPromise
}

export default {
	name: 'OdtViewer',
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
			html: '',
		}
	},
	computed: {
		/**
		 * Entities the sidebar asked to mark in the document.
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
		 * Whether the viewer is in add mode — drives the marking cursor.
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
		clearDomHighlights(this.$refs.content)
	},
	methods: {
		/**
		 * Fetch the odt as ArrayBuffer, unzip it, and transform content.xml to
		 * HTML for display.
		 *
		 * @return {Promise<void>}
		 */
		async load() {
			this.loading = true
			this.error = null
			try {
				const [jsZipModule, arrayBuffer] = await Promise.all([
					loadJsZip(),
					fetchFileAsArrayBuffer(this.path),
				])
				const JSZip = jsZipModule.default || jsZipModule
				const zip = await JSZip.loadAsync(arrayBuffer)
				const contentFile = zip.file('content.xml')
				if (!contentFile) {
					throw new Error(t('docudesk', 'Not a valid ODT document'))
				}
				const contentXml = await contentFile.async('string')
				this.html = odfXmlToHtml(contentXml)
			} catch (err) {
				console.error('[OdtViewer] failed to load odt:', err)
				this.error = err.message || t('docudesk', 'Failed to load document')
			} finally {
				this.loading = false
			}
			// Highlight only after `loading` is false — the content element sits
			// behind `v-else`, so it is not in the DOM while loading (mirrors
			// WordViewer's ordering, incl. the cache-hit reopen case).
			if (!this.error) {
				this.scheduleHighlights()
			}
		},
		/**
		 * Re-apply entity highlights after the DOM has rendered the latest HTML.
		 *
		 * @return {void}
		 */
		scheduleHighlights() {
			this.$nextTick(() => {
				applyDomHighlights(this.$refs.content, this.highlightEntities, this.pendingValue)
			})
		},
		/**
		 * Push the current text selection into the viewer store (add mode).
		 *
		 * @return {void}
		 */
		captureSelection() {
			const text = window.getSelection()?.toString() || ''
			fileViewerStore.setSelection(text)
		},
	},
}
</script>

<style scoped>
.odt-viewer {
	display: flex;
	justify-content: center;
	padding: 16px;
	background: var(--color-background-dark);
	min-height: 100%;
}

.odt-viewer__loading,
.odt-viewer__error {
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 8px;
	padding: 32px;
	color: var(--color-text-maxcontrast);
}

.odt-viewer__page {
	background: white;
	color: black;
	max-width: 800px;
	width: 100%;
	padding: 48px;
	box-shadow: var(--dd-shadow-doc);
	border-radius: var(--border-radius);
}

.odt-viewer__content {
	font-family: 'Calibri', 'Arial', sans-serif;
	font-size: 14px;
	line-height: 1.6;
}

/* :deep — the transform injects raw HTML that our scope hash cannot reach. */
.odt-viewer__content :deep(h1),
.odt-viewer__content :deep(h2),
.odt-viewer__content :deep(h3) {
	margin-top: 1.2em;
	margin-bottom: 0.4em;
	color: #000;
}

.odt-viewer__content :deep(p) {
	margin: 0 0 1em 0;
}

.odt-viewer__content :deep(table) {
	border-collapse: collapse;
	margin: 1em 0;
}

.odt-viewer__content :deep(table td),
.odt-viewer__content :deep(table th) {
	border: 1px solid #ddd;
	padding: 6px 10px;
}
</style>
