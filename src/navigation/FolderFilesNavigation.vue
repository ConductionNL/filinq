<script setup>
import { translate as t } from '@nextcloud/l10n'
import { myDocumentsStore, fileViewerStore, anonymizationStore } from '../store/store.js'
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
				<template #counter>
					<CheckCircle v-if="statusFor(file) === 'completed'"
						:size="20"
						class="dd-file-status dd-file-status--done"
						:title="t('docudesk', 'Anonymized')" />
					<AlertCircleOutline v-else-if="statusFor(file) === 'error'"
						:size="20"
						class="dd-file-status dd-file-status--error"
						:title="t('docudesk', 'Could not be processed')" />
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
import CheckCircle from 'vue-material-design-icons/CheckCircle.vue'
import AlertCircleOutline from 'vue-material-design-icons/AlertCircleOutline.vue'

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
		CheckCircle,
		AlertCircleOutline,
	},
	computed: {
		/**
		 * Files only — dossiers are flat by design, but defensively skip folders.
		 * Orphaned anonymized outputs (a leftover `_anonymized` whose source was
		 * replaced by a re-upload and moved to trash) are skipped too, so a fresh
		 * re-upload isn't shadowed by a stale "anonymized version". Superseded
		 * duplicate outputs (older `_anonymized` copies left by earlier re-runs)
		 * are collapsed to the newest as well, matching the main overview
		 * (`visibleDocuments`) so the dossier doesn't list stale duplicates.
		 *
		 * @return {Array}
		 */
		files() {
			const orphanedOutputIds = myDocumentsStore.orphanedOutputIds
			const newestOutputBySource = myDocumentsStore.newestOutputBySource
			const { anonToSource } = myDocumentsStore.linkMaps
			return myDocumentsStore.documents.filter((d) => {
				if (d.isFolder) return false
				const fileId = Number(d.fileId)
				if (orphanedOutputIds.has(fileId)) return false
				// Hide non-newest outputs of a re-anonymised source.
				const src = anonToSource.get(fileId)
				if (src != null) {
					const newest = newestOutputBySource.get(src)
					if (newest && Number(newest.fileId) !== fileId) return false
				}
				return true
			})
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
		 * Anonymisation status of a dossier file, read from its queue entry.
		 * Drives the per-row status icon (done / error).
		 *
		 * @param {object} file Document descriptor.
		 * @return {string|null} The entry status, or null if not tracked.
		 */
		statusFor(file) {
			return anonymizationStore.findByFileId(file.fileId)?.status || null
		},
		/**
		 * Open a file in the in-app viewer.
		 *
		 * The concept (original) is always the viewer base and the anonymised
		 * copy is attached as its variant, so both are linked and the "Show
		 * original / anonymised" toggle works. The viewer then shows whichever
		 * file the user actually clicked: click the anonymised row → show the
		 * result; click the original row → show the original. The pair stays
		 * linked either way — we don't hijack the view to the other side.
		 *
		 * The pairing is read from the durable `anonymizationLink` register
		 * (`conceptFor` / `anonymizedFor`), mirroring `MyDocumentsIndex.viewFile`.
		 * Reading it from the in-memory anonymisation queue instead would only
		 * work for files anonymised in the current session, so a dossier
		 * anonymised earlier would open without its counterpart and lose the
		 * toggle until a file happened to seed the queue.
		 *
		 * @param {object} file Document descriptor.
		 */
		onFileClick(file) {
			if (!file) return
			const concept = file.isAnonymized ? myDocumentsStore.conceptFor(file) : file
			const anonymized = file.isAnonymized ? file : myDocumentsStore.anonymizedFor(file)
			const base = concept || file
			fileViewerStore.open({
				fileId: base.fileId,
				fileName: base.fileName,
				mimeType: base.mimeType,
				path: `${myDocumentsStore.currentPath}/${base.fileName}`,
			})
			if (anonymized && anonymized.fileId !== base.fileId) {
				fileViewerStore.setAnonymizedVariant({
					fileId: anonymized.fileId,
					fileName: anonymized.fileName,
					mimeType: anonymized.mimeType,
					path: `${myDocumentsStore.currentPath}/${anonymized.fileName}`,
				}, { show: file.isAnonymized })
			}
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
	--color-main-background-blur: var(--dd-glass-bg, rgba(255, 255, 255, 0.54));
	border-radius: var(--dd-radius-panel);
	box-shadow: var(--dd-shadow-panel);
	margin-right: 8px;
}

:deep(.app-navigation-entry) {
	--default-clickable-area: 48px;
	--border-radius-element: 11px;
	--color-background-hover: var(--dd-surface-hover, #efefef);
}

:deep(.app-navigation-entry.active) {
	--color-primary-element: var(--dd-active-pill-bg, #fff);
	--color-primary-element-hover: var(--dd-active-pill-bg, #fff);
	--color-primary-element-text: var(--dd-ink);
	box-shadow: var(--dd-shadow-popout);
}

.dd-file-status--done {
	color: var(--color-success);
}

.dd-file-status--error {
	color: var(--color-error);
}
</style>
