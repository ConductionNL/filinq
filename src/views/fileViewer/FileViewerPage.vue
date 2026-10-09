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
					:title="
						t('filinq', 'Text recognised by OCR, with its confidence')
					">
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
					{{
						ocrRunning
							? t('filinq', 'Running OCR…')
							: t('filinq', 'Run OCR')
					}}
				</NcButton>
				<NcButton
					v-if="pdfaOffered"
					variant="secondary"
					data-testid="open-conformance-report"
					@click="conformanceOpen = true">
					<template #icon>
						<FileCheckOutline :size="18" />
					</template>
					{{ t('filinq', 'PDF/A report') }}
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
		<AccessibilityPublishWarningModal
			:show="publishWarning.show"
			:checked="publishWarning.checked"
			:findings="publishWarning.findings"
			:blocking="publishWarning.blocking"
			@close="publishWarning.show = false"
			@confirm="startPublishing" />
		<ConformanceReportModal
			:show="conformanceOpen"
			:fileId="Number(fileViewerStore.currentFile?.fileId || 0)"
			@close="conformanceOpen = false" />
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
import FileCheckOutline from 'vue-material-design-icons/FileCheckOutline.vue'
import FileDocumentOutline from 'vue-material-design-icons/FileDocumentOutline.vue'
import FilePdfBox from 'vue-material-design-icons/FilePdfBox.vue'
import FileWordBox from 'vue-material-design-icons/FileWordBox.vue'
import TextRecognition from 'vue-material-design-icons/TextRecognition.vue'
import DdFileViewerHeader from '../../components/DdFileViewerHeader.vue'
import OdtViewer from '../../components/viewers/OdtViewer.vue'
import PdfViewer from '../../components/viewers/PdfViewer.vue'
import TextViewer from '../../components/viewers/TextViewer.vue'
import WordViewer from '../../components/viewers/WordViewer.vue'
import AccessibilityPublishWarningModal from '../../modals/AccessibilityPublishWarningModal.vue'
import ConformanceReportModal from '../../modals/ConformanceReportModal.vue'
import {
	fetchOcrStatus,
	isOcrCandidate,
	ocrBadgeLabel,
	ocrErrorMessage,
	runOcr,
} from '../../services/ocr.js'
import { startPublication } from '../../services/publications.js'
import { publicationReadiness } from '../../services/validationService.js'
import {
	detectViewer,
	viewerComponentFor,
	viewerPropsFor,
} from '../../services/viewerRouting.js'
import { fileViewerStore } from '../../store/store.js'

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
		FileCheckOutline,
		AccessibilityPublishWarningModal,
		ConformanceReportModal,
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
			conformanceOpen: false,
			publishWarning: {
				show: false,
				checked: true,
				findings: [],
				blocking: false,
			},
		}
	},

	computed: {
		/**
		 * Whether the PDF/A report is offered: a stored PDF.
		 *
		 * @return {boolean}
		 * @spec openspec/changes/archive/2026-09-29-verapdf-validation/tasks.md#task-3.1
		 */
		pdfaOffered() {
			const file = fileViewerStore.currentFile
			return Boolean(file?.fileId) && detectViewer(file) === 'pdf'
		},

		/**
		 * Whether Run OCR is offered: an image or PDF, and OCR can run here.
		 * The server checks again when it is pressed.
		 *
		 * @return {boolean}
		 * @spec openspec/changes/archive/2026-09-29-ocr-trigger-surface/tasks.md#task-3.1
		 */
		ocrOffered() {
			const file = fileViewerStore.currentFile
			return (
				Boolean(file?.fileId)
				&& isOcrCandidate(file.mimeType)
				&& this.ocrAvailable
			)
		},

		/**
		 * The OCR badge, when the file was OCR'd.
		 *
		 * @return {string}
		 * @spec openspec/changes/archive/2026-09-29-ocr-trigger-surface/tasks.md#task-3.1
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
		 * @spec openspec/changes/archive/2026-10-09-eml-viewer-preview/tasks.md#task-10
		 */
		viewerComponent() {
			return viewerComponentFor(this.viewerKind)
		},

		/**
		 * Props bound to the active viewer component. EML routes through
		 * PdfViewer but loads its bytes from the server-rendered preview
		 * endpoint (keyed by file id) rather than the WebDAV path.
		 *
		 * @return {object}
		 * @spec openspec/changes/archive/2026-10-09-eml-viewer-preview/tasks.md#task-10
		 */
		viewerProps() {
			return viewerPropsFor(fileViewerStore.currentFile, this.viewerKind)
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
			/**
			 * Read the OCR status when the files on screen change.
			 *
			 * @spec openspec/changes/archive/2026-09-29-ocr-trigger-surface/tasks.md#task-3.1
			 */
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
		 * @spec openspec/changes/archive/2026-09-29-ocr-trigger-surface/tasks.md#task-3.1
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
		 * @spec openspec/changes/archive/2026-09-29-ocr-trigger-surface/tasks.md#task-3.1
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
		 * Publish: first the document's open accessibility findings, as a
		 * warning; without any, straight on.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/changes/archive/2026-09-29-pdfua-accessible-output/tasks.md#task-3.2
		 */
		async publish() {
			this.publishing = true
			const readiness = await publicationReadiness(
				fileViewerStore.currentFile.fileId,
			)
			this.publishing = false
			if (readiness.checked && readiness.findings.length === 0) {
				await this.startPublishing()
				return
			}
			this.publishWarning = { show: true, ...readiness }
		},

		/**
		 * Start a Woo publication for this document and open it.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/changes/archive/2026-09-29-woo-publicatie-pipeline/tasks.md#task-3.2
		 */
		async startPublishing() {
			this.publishWarning.show = false
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
