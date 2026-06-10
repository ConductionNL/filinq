<script setup>
import { translate as t } from '@nextcloud/l10n'
import { myDocumentsStore, fileViewerStore } from '../store/store.js'
</script>

<template>
	<NcAppNavigation>
		<template #list>
			<NcAppNavigationItem
				:name="t('docudesk', 'Back to menu')"
				@click.prevent="onBack">
				<template #icon>
					<ArrowLeft :size="24" />
				</template>
			</NcAppNavigationItem>
			<NcAppNavigationCaption :name="dossierName" />
			<NcAppNavigationItem
				v-for="file in files"
				:key="file.fileId"
				:active="fileViewerStore.currentFile?.fileId === file.fileId"
				:name="file.fileName"
				@click.prevent="onFileClick(file)">
				<template #icon>
					<component :is="iconFor(file)" :size="24" />
				</template>
			</NcAppNavigationItem>
			<NcAppNavigationItem
				v-if="!files.length && !myDocumentsStore.loading"
				:name="t('docudesk', 'No files in this dossier')"
				:disabled="true">
				<template #icon>
					<FileAlertOutline :size="24" />
				</template>
			</NcAppNavigationItem>
		</template>
	</NcAppNavigation>
</template>

<script>
import {
	NcAppNavigation,
	NcAppNavigationItem,
	NcAppNavigationCaption,
} from '@nextcloud/vue'

import ArrowLeft from 'vue-material-design-icons/ArrowLeft.vue'
import FilePdfBox from 'vue-material-design-icons/FilePdfBox.vue'
import FileWordBox from 'vue-material-design-icons/FileWordBox.vue'
import FileDocumentOutline from 'vue-material-design-icons/FileDocumentOutline.vue'
import FileAlertOutline from 'vue-material-design-icons/FileAlertOutline.vue'

export default {
	name: 'FolderFilesNavigation',
	components: {
		NcAppNavigation,
		NcAppNavigationItem,
		NcAppNavigationCaption,
		ArrowLeft,
		FilePdfBox,
		FileWordBox,
		FileDocumentOutline,
		FileAlertOutline,
	},
	computed: {
		/**
		 * Files only — dossiers are flat by design, but defensively skip folders.
		 *
		 * @return {Array}
		 */
		files() {
			return myDocumentsStore.documents.filter((d) => !d.isFolder)
		},
		/**
		 * Display name of the current dossier (last path segment).
		 *
		 * @return {string}
		 */
		dossierName() {
			const parts = (myDocumentsStore.currentPath || '').split('/').filter(Boolean)
			return parts[parts.length - 1] || ''
		},
	},
	methods: {
		/**
		 * Pick an icon component name based on MIME type / extension.
		 *
		 * @param {object} file Document descriptor.
		 * @return {string} Component name.
		 */
		iconFor(file) {
			const mime = file.mimeType || ''
			const name = (file.fileName || '').toLowerCase()
			if (mime.includes('pdf') || name.endsWith('.pdf')) return 'FilePdfBox'
			if (mime.includes('word') || name.match(/\.(docx?|odt)$/)) return 'FileWordBox'
			return 'FileDocumentOutline'
		},
		/**
		 * Open a file in the in-app viewer.
		 *
		 * @param {object} file Document descriptor.
		 */
		onFileClick(file) {
			const path = `${myDocumentsStore.currentPath}/${file.fileName}`
			fileViewerStore.open({
				fileId: file.fileId,
				fileName: file.fileName,
				mimeType: file.mimeType,
				path,
			})
		},
		/**
		 * Leave the dossier: close any open viewer, return to root path,
		 * and restore the main menu via the My Documents view.
		 */
		async onBack() {
			if (fileViewerStore.currentFile) {
				fileViewerStore.close()
			}
			await myDocumentsStore.fetchDocuments('/DocuDesk')
			if (this.$route.name !== 'MyDocuments') {
				this.$router.push({ name: 'MyDocuments' })
			}
		},
	},
}
</script>

<style scoped>
.app-navigation {
	--app-navigation-padding: 16px;
	--color-main-background-blur: var(--color-white-54, rgba(255, 255, 255, 0.54));
	border-radius: var(--dd-radius-panel);
	box-shadow: var(--dd-shadow-panel);
	margin-right: 8px;
}

:deep(.app-navigation-entry) {
	--default-clickable-area: 48px;
	--border-radius-element: 11px;
	--color-background-hover: #efefef;
}

:deep(.app-navigation-entry.active) {
	--color-primary-element: #fff;
	--color-primary-element-hover: #fff;
	--color-primary-element-text: var(--color-main-text);
	box-shadow: var(--dd-shadow-popout);
}
</style>
