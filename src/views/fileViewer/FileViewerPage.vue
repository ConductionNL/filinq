<template>
	<div class="file-viewer-page">
		<DdFileViewerHeader :title="pageTitle">
			<template #icon>
				<component :is="fileTypeIcon" :size="28" />
			</template>
			<template #actions>
				<!-- Toggle between original and anonymised version — enabled once
				     the sidebar attaches the anonymised variant via `setAnonymizedVariant`. -->
				<NcButton
					v-if="canToggleAnonymized"
					variant="secondary"
					:disabled="!fileViewerStore.canToggleVariant"
					:title="toggleTitle"
					@click="fileViewerStore.toggleAnonymized()">
					<template #icon>
						<EyeOffOutline
							v-if="fileViewerStore.showAnonymized"
							:size="18" />
						<Eye v-else :size="18" />
					</template>
					{{
						fileViewerStore.showAnonymized
							? t('filinq', 'Show original')
							: t('filinq', 'Show anonymised')
					}}
				</NcButton>
				<span
					v-if="ocrBadge"
					class="file-viewer-page__ocr-badge"
					:title="t('filinq', 'Text recognised by OCR, with its confidence')">
					{{ ocrBadge }}
				</span>
				<NcButton
					v-if="ocrOffered"
					variant="secondary"
					:disabled="ocrRunning"
					@click="runOcrNow">
					<template #icon>
						<NcLoadingIcon v-if="ocrRunning" :size="18" />
						<TextRecognition v-else :size="18" />
					</template>
					{{ ocrRunning ? t('filinq', 'Running OCR…') : t('filinq', 'Run OCR') }}
				</NcButton>
				<NcButton
					v-if="fileViewerStore.currentFile?.fileId"
					variant="secondary"
					:disabled="publishing"
					@click="publish">
					{{ t('filinq', 'Publish') }}
				</NcButton>
			</template>
		</DdFileViewerHeader>

		<div class="file-viewer-page__body">
			<component
				:is="viewerComponent"
				v-if="viewerComponent && fileViewerStore.currentFile"
				v-bind="viewerProps" />
			<div
				v-else-if="fileViewerStore.currentFile"
				class="file-viewer-page__unsupported">
				<FileAlertOutline :size="48" />
				<p>{{ t('filinq', 'This file type cannot be previewed.') }}</p>
				<NcButton variant="primary" @click="downloadCurrent">
					<template #icon>
						<Download :size="18" />
					</template>
					{{ t('filinq', 'Download') }}
				</NcButton>
			</div>
		</div>
	</div>
</template>

<script>
import { showError, showSuccess } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { NcButton, NcLoadingIcon } from '@nextcloud/vue'
import Download from 'vue-material-design-icons/Download.vue'
import Eye from 'vue-material-design-icons/Eye.vue'
import EyeOffOutline from 'vue-material-design-icons/EyeOffOutline.vue'
import FileAlertOutline from 'vue-material-design-icons/FileAlertOutline.vue'
import FileDocumentOutline from 'vue-material-design-icons/FileDocumentOutline.vue'
import FilePdfBox from 'vue-material-design-icons/FilePdfBox.vue'
import FileWordBox from 'vue-material-design-icons/FileWordBox.vue'
import TextRecognition from 'vue-material-design-icons/TextRecognition.vue'
import DdFileViewerHeader from '../../components/DdFileViewerHeader.vue'
import OdtViewer from '../../components/viewers/OdtViewer.vue'
import PdfViewer from '../../components/viewers/PdfViewer.vue'
import TextViewer from '../../components/viewers/TextViewer.vue'
import WordViewer from '../../components/viewers/WordViewer.vue'
import { emlPreviewUrl } from '../../services/fileViewerService.js'
import {
	fetchOcrStatus,
	isOcrCandidate,
	ocrBadgeLabel,
	ocrErrorMessage,
	runOcr,
} from '../../services/ocr.js'
import { startPublication } from '../../services/publications.js'
import { fileViewerStore } from '../../store/store.js'

/**
 * Match a file (by MIME + name) to one of the supported in-app viewers.
 *
 * @param {object} file Current file descriptor from the store.
 * @return {string|null} 'pdf' | 'word' | 'odt' | 'text' | 'eml' | null when unsupported.
 */
function detectViewer(file) {
	if (!file) return null
	const name = (file.fileName || '').toLowerCase()
	const mime = (file.mimeType || '').toLowerCase()
	if (mime.includes('pdf') || name.endsWith('.pdf')) return 'pdf'
	if (
		mime
			=== 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
		|| name.endsWith('.docx')
	)
		return 'word'
	if (mime === 'application/vnd.oasis.opendocument.text' || name.endsWith('.odt'))
		return 'odt'
	if (mime === 'message/rfc822' || name.endsWith('.eml')) return 'eml'
	if (mime.startsWith('text/') || name.match(/\.(txt|md|markdown|log|csv)$/))
		return 'text'
	return null
}

export default {
	name: 'FileViewerPage',
	components: {
		NcLoadingIcon,
		TextRecognition,
		NcButton,
		Eye,
		EyeOffOutline,
		Download,
		FilePdfBox,
		FileWordBox,
		FileDocumentOutline,
		FileAlertOutline,
		DdFileViewerHeader,
		PdfViewer,
		WordViewer,
		OdtViewer,
		TextViewer,
	},

	data() {
		return {
			fileViewerStore,
			publishing: false,
			ocrAvailable: false,
			ocrResult: null,
			ocrRunning: false,
		}
	},

	computed: {
		/**
		 * Whether Run OCR is offered: an image or PDF, and OCR can run here.
		 * The server checks again when it is pressed.
		 *
		 * @return {boolean}
		 * @spec openspec/changes/ocr-trigger-surface/tasks.md#task-3.1
		 */
		ocrOffered() {
			const file = fileViewerStore.currentFile
			return Boolean(file?.fileId) && isOcrCandidate(file.mimeType) && this.ocrAvailable
		},

		/**
		 * The OCR badge, when the file was OCR'd.
		 *
		 * @return {string}
		 * @spec openspec/changes/ocr-trigger-surface/tasks.md#task-3.1
		 */
		ocrBadge() {
			return ocrBadgeLabel(this.ocrResult)
		},

		/**
		 * Page title — current file name with a generic fallback.
		 *
		 * @return {string}
		 * @spec openspec/specs/document-preview/spec.md#requirement-format-specific-in-app-document-preview-req-ddprv-001
		 */
		pageTitle() {
			return (
				fileViewerStore.currentFile?.fileName || t('filinq', 'File preview')
			)
		},

		/**
		 * Resolve the viewer kind for the current file.
		 *
		 * @return {string|null}
		 */
		viewerKind() {
			return detectViewer(fileViewerStore.currentFile)
		},

		/**
		 * Map viewer kind to the actual component name.
		 *
		 * @return {string|null}
		 */
		viewerComponent() {
			switch (this.viewerKind) {
				case 'pdf':
					return 'PdfViewer'
				case 'word':
					return 'WordViewer'
				case 'odt':
					return 'OdtViewer'
				case 'text':
					return 'TextViewer'
				// EML is rendered as a server-side PDF preview via PdfViewer.
				case 'eml':
					return 'PdfViewer'
				default:
					return null
			}
		},

		/**
		 * Props bound to the active viewer component. EML routes through
		 * PdfViewer but loads its bytes from the server-rendered preview
		 * endpoint (keyed by file id) rather than the WebDAV path.
		 *
		 * @return {object}
		 */
		viewerProps() {
			const file = fileViewerStore.currentFile
			if (!file) return {}
			if (this.viewerKind === 'eml') {
				return { path: file.path, url: emlPreviewUrl(file.fileId) }
			}
			return { path: file.path }
		},

		/**
		 * Icon shown in the header next to the file name.
		 *
		 * @return {string}
		 */
		fileTypeIcon() {
			switch (this.viewerKind) {
				case 'pdf':
					return 'FilePdfBox'
				case 'word':
					return 'FileWordBox'
				case 'odt':
					return 'FileWordBox'
				default:
					return 'FileDocumentOutline'
			}
		},

		/**
		 * The toggle is rendered for any previewable type so the placement
		 * is consistent; the button is only enabled when both the original
		 * and the anonymised variant are loaded in the store.
		 *
		 * @return {boolean}
		 */
		canToggleAnonymized() {
			return this.viewerComponent !== null
		},

		/**
		 * Tooltip for the toggle button — explains the disabled state when
		 * the anonymised variant is not (yet) available.
		 *
		 * @return {string}
		 * @spec openspec/specs/anonymization-link/spec.md#requirement-bidirectional-lookup-via-or-search-api-req-alink-03
		 */
		toggleTitle() {
			if (!fileViewerStore.canToggleVariant) {
				return t('filinq', 'Anonymised version not available yet')
			}
			return fileViewerStore.showAnonymized
				? t('filinq', 'Switch to the original file')
				: t('filinq', 'Switch to the anonymised file')
		},
	},

	watch: {
		'fileViewerStore.currentFile.fileId': {
			immediate: true,
			handler() {
				this.loadOcrStatus()
			},
		},
	},

	methods: {
		t,

		/**
		 * Read whether OCR can run and this file's last result.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/changes/ocr-trigger-surface/tasks.md#task-3.1
		 */
		async loadOcrStatus() {
			const file = fileViewerStore.currentFile
			this.ocrResult = null
			this.ocrAvailable = false
			if (!file?.fileId || !isOcrCandidate(file.mimeType)) {
				return
			}
			try {
				const status = await fetchOcrStatus([file.fileId])
				this.ocrAvailable = status.capability?.available === true
				this.ocrResult = status.results?.[String(file.fileId)] ?? null
			} catch {
				// No status: no button. The file itself still shows.
			}
		},

		/**
		 * Run OCR on the open file and show the new badge without a reload.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/changes/ocr-trigger-surface/tasks.md#task-3.1
		 */
		async runOcrNow() {
			this.ocrRunning = true
			try {
				const result = await runOcr(fileViewerStore.currentFile.fileId)
				if (result.ocrProcessed) {
					this.ocrResult = result
					showSuccess(
						t('filinq', 'OCR read {length} characters.', {
							length: result.textLength,
						}),
					)
				} else {
					showError(ocrErrorMessage(result))
				}
			} catch (error) {
				showError(ocrErrorMessage(error))
				if ([409, 503].includes(error?.response?.status)) {
					this.ocrAvailable = false
				}
			} finally {
				this.ocrRunning = false
			}
		},

		/**
		 * Start a Woo publication for this document and open it.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/changes/archive/2026-09-29-woo-publicatie-pipeline/tasks.md#task-3.2
		 */
		async publish() {
			this.publishing = true
			try {
				const record = await startPublication(
					fileViewerStore.currentFile.fileId,
				)
				this.$router.push({
					name: 'Publications',
					params: { id: record.uuid },
				})
			} catch {
				showError(t('filinq', 'The publication could not be started.'))
			} finally {
				this.publishing = false
			}
		},

		/** Download the currently previewed file via Nextcloud's file URL. */
		downloadCurrent() {
			const file = fileViewerStore.currentFile
			if (!file?.fileId) return
			window.open(generateUrl(`/f/${file.fileId}`), '_blank')
		},
	},
}
</script>

<style scoped>
.file-viewer-page {
	display: flex;
	flex-direction: column;
	height: 100%;
	min-height: 0;
}

.file-viewer-page__body {
	flex: 1;
	min-height: 0;
	overflow: auto;
	background: var(--color-background-dark);
	border-top: 1px solid var(--color-border);
}

.file-viewer-page__ocr-badge {
	align-self: center;
	padding: 2px 8px;
	border-radius: var(--border-radius-pill);
	background: var(--color-primary-element-light);
	color: var(--color-primary-element-light-text);
	font-size: 0.9em;
}

.file-viewer-page__unsupported {
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 12px;
	padding: 64px 16px;
	color: var(--color-text-maxcontrast);
	text-align: center;
}
</style>
