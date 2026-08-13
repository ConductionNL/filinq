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
							? t('docudesk', 'Show original')
							: t('docudesk', 'Show anonymised')
					}}
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
				<p>{{ t('docudesk', 'This file type cannot be previewed.') }}</p>
				<NcButton variant="primary" @click="downloadCurrent">
					<template #icon>
						<Download :size="18" />
					</template>
					{{ t('docudesk', 'Download') }}
				</NcButton>
			</div>
		</div>
	</div>
</template>

<script>
import { NcButton } from '@nextcloud/vue'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import Eye from 'vue-material-design-icons/Eye.vue'
import EyeOffOutline from 'vue-material-design-icons/EyeOffOutline.vue'
import Download from 'vue-material-design-icons/Download.vue'
import FilePdfBox from 'vue-material-design-icons/FilePdfBox.vue'
import FileWordBox from 'vue-material-design-icons/FileWordBox.vue'
import FileDocumentOutline from 'vue-material-design-icons/FileDocumentOutline.vue'
import FileAlertOutline from 'vue-material-design-icons/FileAlertOutline.vue'
import { fileViewerStore } from '../../store/store.js'
import { emlPreviewUrl } from '../../services/fileViewerService.js'
import DdFileViewerHeader from '../../components/DdFileViewerHeader.vue'
import PdfViewer from '../../components/viewers/PdfViewer.vue'
import WordViewer from '../../components/viewers/WordViewer.vue'
import OdtViewer from '../../components/viewers/OdtViewer.vue'
import TextViewer from '../../components/viewers/TextViewer.vue'

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
		}
	},
	computed: {
		/**
		 * Page title — current file name with a generic fallback.
		 *
		 * @return {string}
		 */
		pageTitle() {
			return (
				fileViewerStore.currentFile?.fileName
				|| t('docudesk', 'File preview')
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
		 */
		toggleTitle() {
			if (!fileViewerStore.canToggleVariant) {
				return t('docudesk', 'Anonymised version not available yet')
			}
			return fileViewerStore.showAnonymized
				? t('docudesk', 'Switch to the original file')
				: t('docudesk', 'Switch to the anonymised file')
		},
	},
	methods: {
		t,
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
