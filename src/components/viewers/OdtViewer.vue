<!--
  - SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
  - SPDX-License-Identifier: EUPL-1.2
-->
<template>
	<div
		class="odt-viewer dd-doc-viewer"
		:class="{ 'dd-marking-cursor': isAddMode }"
		@mouseup="captureSelection">
		<div v-if="loading" class="odt-viewer__loading dd-doc-viewer__state">
			<NcLoadingIcon :size="48" />
			<span>{{ t('filinq', 'Loading document…') }}</span>
		</div>
		<div v-else-if="error" class="odt-viewer__error dd-doc-viewer__state">
			{{ error }}
		</div>
		<div v-else class="odt-viewer__page dd-doc-viewer__sheet">
			<!-- HTML is produced by odfXmlToHtml, a whitelist transform that
				escapes all text and emits no attributes/scripts — safe here. -->
			<!-- eslint-disable vue/no-v-html -->
			<div
				ref="content"
				class="odt-viewer__content dd-doc-viewer__html"
				v-html="html" />
			<!-- eslint-enable vue/no-v-html -->
		</div>
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { NcLoadingIcon } from '@nextcloud/vue'
import { htmlDocumentViewer } from '../../mixins/documentViewer.js'
import { odfXmlToHtml } from '../../services/odfToHtml.js'

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

	mixins: [htmlDocumentViewer],
	loadFailedLog: '[OdtViewer] failed to load odt:',

	methods: {
		loadConverter: loadJsZip,

		/**
		 * Unzip the odt and transform its content.xml to HTML.
		 *
		 * @param {object} jsZipModule The JSZip module.
		 * @param {ArrayBuffer} arrayBuffer The odt bytes.
		 * @return {Promise<string>} The HTML.
		 * @spec openspec/changes/odt-viewer-preview/specs/document-preview/spec.md#requirement-the-anonymisation-viewer-must-render-an-odt-preview
		 */
		async toHtml(jsZipModule, arrayBuffer) {
			const JSZip = jsZipModule.default || jsZipModule
			const zip = await JSZip.loadAsync(arrayBuffer)
			const contentFile = zip.file('content.xml')
			if (!contentFile) {
				throw new Error(t('filinq', 'Not a valid ODT document'))
			}
			return odfXmlToHtml(await contentFile.async('string'))
		},
	},
}
</script>

<style scoped src="./documentViewer.css"></style>

<style scoped>
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
