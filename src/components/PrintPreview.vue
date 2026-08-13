<script setup>
import { ref, onMounted, watch } from 'vue'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'

const props = defineProps({
	templateId: {
		type: String,
		default: '',
	},
	templateContent: {
		type: String,
		default: '',
	},
	data: {
		type: Object,
		default: () => ({}),
	},
	options: {
		type: Object,
		default: () => ({}),
	},
})

const emit = defineEmits(['close'])

const previewHtml = ref('')
const previewTitle = ref('document')
const loading = ref(false)
const error = ref('')
const iframeRef = ref(null)

/**
 * Fetch the print preview HTML from the backend.
 */
async function fetchPreview() {
	loading.value = true
	error.value = ''

	try {
		const body = {
			data: props.data,
		}

		if (props.templateId) {
			body.templateId = props.templateId
		} else if (props.templateContent) {
			body.template = props.templateContent
			body.options = props.options
		}

		const url = generateUrl('/apps/docudesk/api/print/preview')
		const response = await axios.post(url, body)

		previewHtml.value = response.data.html || ''
		previewTitle.value = response.data.title || 'document'
	} catch (err) {
		error.value = err.response?.data?.error || err.message
	} finally {
		loading.value = false
	}
}

/**
 * Write the preview HTML into the iframe for sandboxed display.
 */
function updateIframe() {
	if (iframeRef.value === null || previewHtml.value === '') {
		return
	}

	const doc =
		iframeRef.value.contentDocument || iframeRef.value.contentWindow.document
	doc.open()
	doc.write(previewHtml.value)
	doc.close()
}

/**
 * Trigger the browser's native print dialog on the iframe content.
 */
function handlePrint() {
	if (iframeRef.value === null) {
		return
	}

	iframeRef.value.contentWindow.print()
}

/**
 * Download a PDF/A-3b compliant document via the backend.
 */
async function handleDownloadPdfA() {
	loading.value = true
	error.value = ''

	try {
		const body = {
			data: props.data,
			filename: previewTitle.value + '.pdf',
		}

		if (props.templateId) {
			body.templateId = props.templateId
		} else if (props.templateContent) {
			body.template = props.templateContent
			body.options = props.options
		}

		const url = generateUrl('/apps/docudesk/api/print/pdf-a')
		const response = await axios.post(url, body, {
			responseType: 'blob',
		})

		const blob = new Blob([response.data], { type: 'application/pdf' })
		const downloadUrl = window.URL.createObjectURL(blob)
		const link = document.createElement('a')
		link.href = downloadUrl
		link.download = previewTitle.value + '.pdf'
		document.body.appendChild(link)
		link.click()
		document.body.removeChild(link)
		window.URL.revokeObjectURL(downloadUrl)
	} catch (err) {
		error.value = err.response?.data?.error || err.message
	} finally {
		loading.value = false
	}
}

onMounted(() => {
	fetchPreview()
})

watch(previewHtml, () => {
	updateIframe()
})
</script>

<template>
	<div class="print-preview">
		<div class="print-preview__header">
			<h2>{{ t('docudesk', 'Print Preview') }}: {{ previewTitle }}</h2>
			<div class="print-preview__actions">
				<button
					class="primary"
					:disabled="loading || !previewHtml"
					@click="handlePrint">
					{{ t('docudesk', 'Print') }}
				</button>
				<button :disabled="loading" @click="handleDownloadPdfA">
					{{ t('docudesk', 'Download PDF/A') }}
				</button>
				<button @click="emit('close')">
					{{ t('docudesk', 'Close') }}
				</button>
			</div>
		</div>

		<div v-if="loading" class="print-preview__loading">
			<p>{{ t('docudesk', 'Loading preview...') }}</p>
		</div>

		<div v-else-if="error" class="print-preview__error">
			<p>{{ t('docudesk', 'Preview failed: {error}', { error }) }}</p>
		</div>

		<div v-else class="print-preview__content">
			<iframe
				ref="iframeRef"
				class="print-preview__iframe"
				sandbox="allow-same-origin"
				:title="t('docudesk', 'Print Preview')" />
		</div>
	</div>
</template>

<style scoped>
.print-preview {
	display: flex;
	flex-direction: column;
	height: 100%;
	padding: 16px;
}

.print-preview__header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 16px;
	flex-wrap: wrap;
	gap: 8px;
}

.print-preview__header h2 {
	margin: 0;
}

.print-preview__actions {
	display: flex;
	gap: 8px;
}

.print-preview__actions button {
	padding: 8px 16px;
	border: 1px solid var(--color-border-dark, #ccc);
	border-radius: var(--border-radius, 4px);
	background: var(--color-main-background, #fff);
	color: var(--color-main-text, #000);
	cursor: pointer;
}

.print-preview__actions button.primary {
	background: var(--color-primary, #0082c9);
	color: var(--color-primary-text, #fff);
	border-color: var(--color-primary, #0082c9);
}

.print-preview__actions button:disabled {
	opacity: 0.5;
	cursor: not-allowed;
}

.print-preview__loading,
.print-preview__error {
	padding: 32px;
	text-align: center;
}

.print-preview__error {
	color: var(--color-error, #e00);
}

.print-preview__content {
	flex: 1;
	min-height: 0;
}

.print-preview__iframe {
	width: 100%;
	height: 100%;
	min-height: 600px;
	border: 1px solid var(--color-border, #eee);
	border-radius: var(--border-radius, 4px);
	background: #fff;
}
</style>
