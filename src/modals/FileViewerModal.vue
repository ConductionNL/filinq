<template>
	<NcModal
		v-if="fileViewerStore.isOpen"
		size="large"
		:name="modalTitle"
		@close="onClose">
		<div class="file-viewer-modal">
			<div class="file-viewer-modal__header">
				<div class="file-viewer-modal__title">
					<component :is="fileTypeIcon" :size="24" />
					<span>{{ modalTitle }}</span>
				</div>
				<div class="file-viewer-modal__actions">
					<!-- Stub: toggle between original and anonymised version. Wired up in a follow-up task. -->
					<NcButton
						v-if="canToggleAnonymized"
						type="secondary"
						:disabled="true"
						:title="t('docudesk', 'Toggle between original and anonymised (coming soon)')"
						@click="fileViewerStore.toggleAnonymized()">
						<template #icon>
							<EyeOffOutline v-if="fileViewerStore.showAnonymized" :size="18" />
							<Eye v-else :size="18" />
						</template>
						{{ fileViewerStore.showAnonymized ? t('docudesk', 'Show original') : t('docudesk', 'Show anonymised') }}
					</NcButton>
					<NcButton type="tertiary" @click="downloadCurrent">
						<template #icon>
							<Download :size="18" />
						</template>
						{{ t('docudesk', 'Download') }}
					</NcButton>
				</div>
			</div>

			<div class="file-viewer-modal__body">
				<component
					:is="viewerComponent"
					v-if="viewerComponent"
					:path="fileViewerStore.currentFile.path" />
				<div v-else class="file-viewer-modal__unsupported">
					<FileAlertOutline :size="48" />
					<p>{{ t('docudesk', 'This file type cannot be previewed.') }}</p>
					<NcButton type="primary" @click="downloadCurrent">
						<template #icon>
							<Download :size="18" />
						</template>
						{{ t('docudesk', 'Download') }}
					</NcButton>
				</div>
			</div>
		</div>
	</NcModal>
</template>

<script>
import { NcModal, NcButton } from '@nextcloud/vue'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import Eye from 'vue-material-design-icons/Eye.vue'
import EyeOffOutline from 'vue-material-design-icons/EyeOffOutline.vue'
import Download from 'vue-material-design-icons/Download.vue'
import FilePdfBox from 'vue-material-design-icons/FilePdfBox.vue'
import FileWordBox from 'vue-material-design-icons/FileWordBox.vue'
import FileDocumentOutline from 'vue-material-design-icons/FileDocumentOutline.vue'
import FileAlertOutline from 'vue-material-design-icons/FileAlertOutline.vue'
import { fileViewerStore } from '../store/store.js'
import PdfViewer from '../components/viewers/PdfViewer.vue'
import WordViewer from '../components/viewers/WordViewer.vue'
import TextViewer from '../components/viewers/TextViewer.vue'

/**
 * Match a file (by MIME + name) to one of the supported in-app viewers.
 *
 * @param {object} file Current file descriptor from the store.
 * @return {string|null} 'pdf' | 'word' | 'text' | null when unsupported.
 */
function detectViewer(file) {
	if (!file) return null
	const name = (file.fileName || '').toLowerCase()
	const mime = (file.mimeType || '').toLowerCase()
	if (mime.includes('pdf') || name.endsWith('.pdf')) return 'pdf'
	if (
		mime === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
		|| name.endsWith('.docx')
	) return 'word'
	if (mime.startsWith('text/') || name.match(/\.(txt|md|markdown|log|csv)$/)) return 'text'
	return null
}

export default {
	name: 'FileViewerModal',
	components: {
		NcModal,
		NcButton,
		Eye,
		EyeOffOutline,
		Download,
		FilePdfBox,
		FileWordBox,
		FileDocumentOutline,
		FileAlertOutline,
		PdfViewer,
		WordViewer,
		TextViewer,
	},
	data() {
		return {
			fileViewerStore,
		}
	},
	computed: {
		/**
		 * Title shown in the modal header — falls back to a generic label
		 * when no file is loaded yet.
		 *
		 * @return {string}
		 */
		modalTitle() {
			return fileViewerStore.currentFile?.fileName || t('docudesk', 'File preview')
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
			case 'pdf': return 'PdfViewer'
			case 'word': return 'WordViewer'
			case 'text': return 'TextViewer'
			default: return null
			}
		},
		/**
		 * Icon shown in the header next to the file name.
		 *
		 * @return {string}
		 */
		fileTypeIcon() {
			switch (this.viewerKind) {
			case 'pdf': return 'FilePdfBox'
			case 'word': return 'FileWordBox'
			default: return 'FileDocumentOutline'
			}
		},
		/**
		 * The toggle is shown for any previewable type so the placement is
		 * consistent, but it is disabled until anonymisation-swap is wired up.
		 *
		 * @return {boolean}
		 */
		canToggleAnonymized() {
			return this.viewerComponent !== null
		},
	},
	methods: {
		t,
		/** Close the modal via the store. */
		onClose() {
			fileViewerStore.close()
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
.file-viewer-modal {
	display: flex;
	flex-direction: column;
	height: 80vh;
}

.file-viewer-modal__header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	gap: 16px;
	padding: 12px 16px;
	border-bottom: 1px solid var(--color-border);
	background: var(--color-main-background);
}

.file-viewer-modal__title {
	display: flex;
	align-items: center;
	gap: 8px;
	font-weight: 600;
	min-width: 0;
}

.file-viewer-modal__title span {
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}

.file-viewer-modal__actions {
	display: flex;
	gap: 8px;
	flex-shrink: 0;
}

.file-viewer-modal__body {
	flex: 1;
	overflow: auto;
	background: var(--color-background-dark);
}

.file-viewer-modal__unsupported {
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 12px;
	padding: 64px 16px;
	color: var(--color-text-maxcontrast);
	text-align: center;
}
</style>
