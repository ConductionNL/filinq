<template>
	<div class="word-viewer" @mouseup="captureSelection">
		<div v-if="loading" class="word-viewer__loading">
			<NcLoadingIcon :size="48" />
			<span>{{ t('docudesk', 'Loading document…') }}</span>
		</div>
		<div v-else-if="error" class="word-viewer__error">
			{{ error }}
		</div>
		<div v-else class="word-viewer__page">
			<!-- mammoth output is HTML extracted from the docx; safe enough here
				since it strips scripts and we never execute it. -->
			<!-- eslint-disable-next-line vue/no-v-html -->
			<div class="word-viewer__content" v-html="html" />
		</div>
	</div>
</template>

<script>
import { NcLoadingIcon } from '@nextcloud/vue'
import { translate as t } from '@nextcloud/l10n'
import { fetchFileAsArrayBuffer } from '../../services/fileViewerService.js'
import { fileViewerStore } from '../../store/store.js'

let mammothPromise = null

/**
 * Lazy-load mammoth (only fetched when the user actually opens a docx).
 *
 * @return {Promise<object>} mammoth module.
 */
async function loadMammoth() {
	if (!mammothPromise) {
		// eslint-disable-next-line import/no-unresolved
		mammothPromise = import('mammoth/mammoth.browser.js')
	}
	return mammothPromise
}

export default {
	name: 'WordViewer',
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
	watch: {
		path: {
			immediate: true,
			handler() {
				this.load()
			},
		},
	},
	methods: {
		/**
		 * Fetch the docx as ArrayBuffer and convert it to HTML via mammoth.
		 *
		 * @return {Promise<void>}
		 */
		async load() {
			this.loading = true
			this.error = null
			try {
				const [mammothModule, arrayBuffer] = await Promise.all([
					loadMammoth(),
					fetchFileAsArrayBuffer(this.path),
				])
				const mammoth = mammothModule.default || mammothModule
				const result = await mammoth.convertToHtml({ arrayBuffer })
				this.html = result.value
			} catch (err) {
				console.error('[WordViewer] failed to load docx:', err)
				this.error = err.message || t('docudesk', 'Failed to load document')
			} finally {
				this.loading = false
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
.word-viewer {
	display: flex;
	justify-content: center;
	padding: 16px;
	background: var(--color-background-dark);
	min-height: 100%;
}

.word-viewer__loading,
.word-viewer__error {
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 8px;
	padding: 32px;
	color: var(--color-text-maxcontrast);
}

.word-viewer__page {
	background: white;
	color: black;
	max-width: 800px;
	width: 100%;
	padding: 48px;
	box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
	border-radius: 4px;
}

.word-viewer__content {
	font-family: 'Calibri', 'Arial', sans-serif;
	font-size: 14px;
	line-height: 1.6;
}
</style>
