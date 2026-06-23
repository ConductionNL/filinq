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

		<!-- Batch action: anonymise every extracted file in this dossier. -->
		<template #footer>
			<div class="dossier-batch-footer">
				<NcButton v-if="batchCount > 0 || batchState.running"
					wide
					type="primary"
					:disabled="batchState.running || batchCount === 0"
					@click="anonymizeAll">
					<template #icon>
						<NcLoadingIcon v-if="batchState.running" :size="20" />
						<ShieldLockOutline v-else :size="20" />
					</template>
					{{ batchButtonLabel }}
				</NcButton>
				<p v-if="batchSummary" class="dossier-batch-summary">
					{{ batchSummary }}
				</p>
			</div>
		</template>
	</NcAppNavigation>
</template>

<script>
import {
	NcAppNavigation,
	NcAppNavigationItem,
	NcAppNavigationCaption,
	NcButton,
	NcLoadingIcon,
} from '@nextcloud/vue'

import ArrowLeft from 'vue-material-design-icons/ArrowLeft.vue'
import FilePdfBox from 'vue-material-design-icons/FilePdfBox.vue'
import FileWordBox from 'vue-material-design-icons/FileWordBox.vue'
import FileDocumentOutline from 'vue-material-design-icons/FileDocumentOutline.vue'
import FileAlertOutline from 'vue-material-design-icons/FileAlertOutline.vue'
import CheckCircle from 'vue-material-design-icons/CheckCircle.vue'
import AlertCircleOutline from 'vue-material-design-icons/AlertCircleOutline.vue'
import ShieldLockOutline from 'vue-material-design-icons/ShieldLockOutline.vue'

export default {
	name: 'FolderFilesNavigation',
	components: {
		NcAppNavigation,
		NcAppNavigationItem,
		NcAppNavigationCaption,
		NcButton,
		NcLoadingIcon,
		ArrowLeft,
		FilePdfBox,
		FileWordBox,
		FileDocumentOutline,
		FileAlertOutline,
		CheckCircle,
		AlertCircleOutline,
		ShieldLockOutline,
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
		/**
		 * Live batch-run progress from the anonymization store.
		 *
		 * @return {{running: boolean, total: number, done: number, failed: number}}
		 */
		batchState() {
			return anonymizationStore.batch
		},
		/**
		 * Number of files in this dossier still awaiting anonymisation
		 * (queue entries in the `extracted` state). Drives the button count
		 * and disabled state.
		 *
		 * @return {number}
		 */
		batchCount() {
			const fileIds = this.files.map((f) => f.fileId)
			return anonymizationStore.extractedInFiles(fileIds).length
		},
		/**
		 * Label for the batch button: a live progress count while running,
		 * otherwise "Anonymize all files (N)".
		 *
		 * @return {string}
		 */
		batchButtonLabel() {
			if (this.batchState.running) {
				const processed = this.batchState.done + this.batchState.failed
				return t('docudesk', 'Anonymizing… ({processed}/{total})', {
					processed,
					total: this.batchState.total,
				})
			}
			return t('docudesk', 'Anonymize all files ({count})', { count: this.batchCount })
		},
		/**
		 * One-line summary shown after a finished batch run; empty while
		 * idle or running.
		 *
		 * @return {string}
		 */
		batchSummary() {
			const { running, total, done, failed } = this.batchState
			if (running || total === 0) {
				return ''
			}
			if (failed > 0) {
				return t('docudesk', '{done} anonymized, {failed} failed.', { done, failed })
			}
			return t('docudesk', 'All {total} files anonymized.', { total })
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
		 * Anonymise every extracted file in this dossier in one action.
		 *
		 * Scopes the run to this dossier's files (by fileId) and forwards the
		 * grondslagen options — when the grondslagen toggle is on, each file
		 * gets the basis-summary page appended and is rendered to PDF, mirroring
		 * the per-file sidebar's `onAnonymise`.
		 *
		 * @return {Promise<void>}
		 */
		async anonymizeAll() {
			const fileIds = this.files.map((f) => f.fileId)
			const options = fileViewerStore.grondslagen
				? { fileIds, appendBasisSummary: true, outputFormat: 'pdf' }
				: { fileIds }
			await anonymizationStore.anonymiseAllExtracted(options)
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

.dossier-batch-footer {
	display: flex;
	flex-direction: column;
	gap: 6px;
	padding: 12px;
}

.dossier-batch-summary {
	margin: 0;
	text-align: center;
	font-size: 0.85rem;
	color: var(--color-text-maxcontrast);
}

.dd-file-status--done {
	color: var(--color-success);
}

.dd-file-status--error {
	color: var(--color-error);
}
</style>
