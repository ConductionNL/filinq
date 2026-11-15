<!--
  - SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
  - SPDX-License-Identifier: EUPL-1.2
-->
<template>
	<div
		class="word-viewer dd-doc-viewer"
		:class="{ 'dd-marking-cursor': isAddMode }"
		@mouseup="captureSelection">
		<div v-if="loading" class="word-viewer__loading dd-doc-viewer__state">
			<NcLoadingIcon :size="48" />
			<span>{{ t('filinq', 'Loading document…') }}</span>
		</div>
		<div v-else-if="error" class="word-viewer__error dd-doc-viewer__state">
			{{ error }}
		</div>
		<div v-else class="word-viewer__page dd-doc-viewer__sheet">
			<!-- mammoth does not sanitise; the HTML is sanitised in toHtml(). -->
			<!-- eslint-disable vue/no-v-html -->
			<div
				ref="content"
				class="word-viewer__content dd-doc-viewer__html"
				v-html="html" />
			<!-- eslint-enable vue/no-v-html -->
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

<style scoped src="./documentViewer.css"></style>
