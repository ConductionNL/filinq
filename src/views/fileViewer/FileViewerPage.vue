<template>
	<div class="file-viewer-page">
		<DdPageHeader :title="pageTitle">
			<template #icon>
				<component :is="fileTypeIcon" :size="28" />
			</template>
			<template #actions>
				<NcButton v-if="showBack" type="tertiary" @click="onBack">
					<template #icon>
						<ArrowLeft :size="18" />
					</template>
					{{ t('docudesk', 'Back') }}
				</NcButton>
				<!-- Toggle between original and anonymised version — enabled once
				     the sidebar attaches the anonymised variant via `setAnonymizedVariant`. -->
				<NcButton
					v-if="canToggleAnonymized"
					type="secondary"
					:disabled="!fileViewerStore.canToggleVariant"
					:title="toggleTitle"
					@click="fileViewerStore.toggleAnonymized()">
					<template #icon>
						<EyeOffOutline v-if="fileViewerStore.showAnonymized" :size="18" />
						<Eye v-else :size="18" />
					</template>
					{{ fileViewerStore.showAnonymized ? t('docudesk', 'Show original') : t('docudesk', 'Show anonymised') }}
				</NcButton>
			</template>
		</DdPageHeader>

		<div class="file-viewer-page__body">
			<component
				:is="viewerComponent"
				v-if="viewerComponent && fileViewerStore.currentFile"
				:path="fileViewerStore.currentFile.path" />
			<div v-else-if="fileViewerStore.currentFile" class="file-viewer-page__unsupported">
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
</template>

<script>
import { NcButton } from '@nextcloud/vue'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import ArrowLeft from 'vue-material-design-icons/ArrowLeft.vue'
import Eye from 'vue-material-design-icons/Eye.vue'
import EyeOffOutline from 'vue-material-design-icons/EyeOffOutline.vue'
import Download from 'vue-material-design-icons/Download.vue'
import FilePdfBox from 'vue-material-design-icons/FilePdfBox.vue'
import FileWordBox from 'vue-material-design-icons/FileWordBox.vue'
import FileDocumentOutline from 'vue-material-design-icons/FileDocumentOutline.vue'
import FileAlertOutline from 'vue-material-design-icons/FileAlertOutline.vue'
import { fileViewerStore, myDocumentsStore } from '../../store/store.js'
import DdPageHeader from '../../components/DdPageHeader.vue'
import PdfViewer from '../../components/viewers/PdfViewer.vue'
import WordViewer from '../../components/viewers/WordViewer.vue'
import TextViewer from '../../components/viewers/TextViewer.vue'

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
	name: 'FileViewerPage',
	components: {
		NcButton,
		ArrowLeft,
		Eye,
		EyeOffOutline,
		Download,
		FilePdfBox,
		FileWordBox,
		FileDocumentOutline,
		FileAlertOutline,
		DdPageHeader,
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
		 * Page title — current file name with a generic fallback.
		 *
		 * @return {string}
		 */
		pageTitle() {
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
		 * Show the Back button only for single-file context (root of My
		 * Documents). Inside a dossier the user returns via the navigation
		 * link, so a header Back button would duplicate that affordance.
		 *
		 * @return {boolean}
		 */
		showBack() {
			return myDocumentsStore.currentPath === '/DocuDesk'
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
		/** Navigate back to the previous view via the store. */
		onBack() {
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
