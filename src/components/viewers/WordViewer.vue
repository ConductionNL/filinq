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
			<!-- mammoth does not sanitise; the HTML is sanitised in toHtml(). -->
			<!-- eslint-disable-next-line vue/no-v-html -->
			<div ref="content" class="word-viewer__content" v-html="html" />
		</div>
	</div>
</template>

<script>
import { NcLoadingIcon } from '@nextcloud/vue'
import { htmlDocumentViewer } from '../../mixins/documentViewer.js'
import { sanitizeHtml } from '../../utils/sanitizeHtml.js'

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

	mixins: [htmlDocumentViewer],
	loadFailedLog: '[WordViewer] failed to load docx:',

	methods: {
		loadConverter: loadMammoth,

		/**
		 * Convert the docx to HTML via mammoth. mammoth does not sanitise, so
		 * the HTML is sanitised here.
		 *
		 * @param {object} mammothModule The mammoth module.
		 * @param {ArrayBuffer} arrayBuffer The docx bytes.
		 * @return {Promise<string>} The sanitised HTML.
		 * @spec openspec/specs/document-preview/spec.md#requirement-format-specific-in-app-document-preview-req-ddprv-001
		 */
		async toHtml(mammothModule, arrayBuffer) {
			const mammoth = mammothModule.default || mammothModule
			const result = await mammoth.convertToHtml({ arrayBuffer })
			return sanitizeHtml(result.value)
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
