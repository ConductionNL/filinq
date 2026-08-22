<template>
	<div
		class="word-viewer"
		:class="{ 'dd-marking-cursor': isAddMode }"
		@mouseup="captureSelection">
		<div v-if="loading" class="word-viewer__loading">
			<NcLoadingIcon :size="48" />
			<span>{{ t('filinq', 'Loading document…') }}</span>
		</div>
		<div v-else-if="error" class="word-viewer__error">
			{{ error }}
		</div>
		<div v-else class="word-viewer__page">
			<!-- mammoth output is HTML extracted from the docx; safe enough here
				since it strips scripts and we never execute it. -->
			<!-- eslint-disable-next-line vue/no-v-html -->
			<div ref="content" class="word-viewer__content" v-html="html" />
		</div>
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { NcLoadingIcon } from '@nextcloud/vue'
import { fetchFileAsArrayBuffer } from '../../services/fileViewerService.js'
import {
	applyDomHighlights,
	clearDomHighlights,
} from '../../services/highlightDom.js'
import { fileViewerStore } from '../../store/store.js'

let mammothPromise = null

/**
 * Lazy-load mammoth (only fetched when the user actually opens a docx).
 *
 * @return {Promise<object>} mammoth module.
 */
async function loadMammoth() {
	if (!mammothPromise) {
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
			return fileViewerStore.addMode ? fileViewerStore.selection || '' : ''
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

	beforeUnmount() {
		clearDomHighlights(this.$refs.content)
	},

	methods: {
		/**
		 * Fetch the docx as ArrayBuffer and convert it to HTML via mammoth.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/specs/document-preview/spec.md#requirement-format-specific-in-app-document-preview-req-ddprv-001
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
				this.error = err.message || t('filinq', 'Failed to load document')
			} finally {
				this.loading = false
			}
			// Highlight only after `loading` is false: the content element
			// (ref="content") sits behind `v-else`, so it is not in the DOM
			// while loading. Scheduling the highlight pass here ensures the
			// re-render that mounts the element is queued before the
			// $nextTick highlight callback runs — otherwise `$refs.content`
			// is null and nothing is marked (notably on a cache-hit reopen,
			// where the entity list is pre-set and no later change retriggers
			// the highlight watcher).
			if (!this.error) {
				this.scheduleHighlights()
			}
		},

		/**
		 * Re-apply the entity highlights after the DOM has rendered the latest
		 * mammoth HTML / store change.
		 *
		 * @return {void}
		 */
		scheduleHighlights() {
			this.$nextTick(() => {
				applyDomHighlights(
					this.$refs.content,
					this.highlightEntities,
					this.pendingValue,
				)
			})
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
	box-shadow: var(--dd-shadow-doc);
	border-radius: var(--border-radius);
}

.word-viewer__content {
	font-family: 'Calibri', 'Arial', sans-serif;
	font-size: 14px;
	line-height: 1.6;
}
</style>
