<script setup>
import { translate as t } from '@nextcloud/l10n'
import { generateRemoteUrl } from '@nextcloud/router'
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
				<!-- Once files are anonymised, offer a one-click download of
				     every result in the dossier, bundled as a single zip (T14). -->
				<NcButton v-if="completedCount > 0 && !batchState.running"
					wide
					type="secondary"
					:disabled="zipping"
					@click="downloadAll">
					<template #icon>
						<NcLoadingIcon v-if="zipping" :size="20" />
						<Download v-else :size="20" />
					</template>
					{{ zipping
						? t('docudesk', 'Preparing download…')
						: t('docudesk', 'Download all anonymised files ({count})', { count: completedCount }) }}
				</NcButton>
				<p v-if="zipError" class="dossier-batch-summary dossier-batch-summary--error">
					{{ zipError }}
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
import Download from 'vue-material-design-icons/Download.vue'
import axios from '@nextcloud/axios'
import JSZip from 'jszip'

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
		Download,
	},
	data() {
		return {
			// True while the "Download all" zip is being fetched + built.
			zipping: false,
			// Set when one or more files could not be added to the zip.
			zipError: '',
		}
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
		 * Completed (anonymised) dossier files with a downloadable result —
		 * drives the "Download all" button count and visibility (T14).
		 *
		 * @return {Array<object>}
		 */
		completedEntries() {
			const fileIds = this.files.map((f) => f.fileId)
			return anonymizationStore.completedInFiles(fileIds)
		},
		/**
		 * Number of anonymised files available to download.
		 *
		 * @return {number}
		 */
		completedCount() {
			return this.completedEntries.length
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
		 * Build the WebDAV download URL for an anonymised result path. Mirrors
		 * the per-file logic in `FileViewerSidebar.downloadUrl`: strip the
		 * leading `.../files/` segment and append the user-relative remainder.
		 *
		 * @param {string} anonymizedFilePath Absolute storage path of the result.
		 * @return {string} The WebDAV URL, or '' when no path is given.
		 */
		downloadUrlFor(anonymizedFilePath) {
			if (!anonymizedFilePath) {
				return ''
			}
			const parts = anonymizedFilePath.split('/')
			const filesIndex = parts.indexOf('files')
			if (filesIndex >= 0) {
				// Encode each segment so a dossier/file name containing `?`,
				// `#` or `&` doesn't corrupt the download URL.
				const relativePath = parts.slice(filesIndex + 1).map(encodeURIComponent).join('/')
				return generateRemoteUrl('webdav') + '/' + relativePath
			}
			return generateRemoteUrl('webdav')
		},
		/**
		 * Download every anonymised result in the dossier as a single zip.
		 *
		 * Frontend-only: each result is fetched over WebDAV, bundled in-memory
		 * with JSZip and saved as one archive — no backend zip endpoint
		 * required (T14). A file that fails to fetch is skipped and reported in
		 * `zipError` rather than aborting the whole bundle.
		 *
		 * @return {Promise<void>}
		 */
		async downloadAll() {
			if (this.zipping) {
				return
			}
			this.zipping = true
			this.zipError = ''
			const zip = new JSZip()
			const usedNames = new Set()
			let failed = 0
			try {
				for (const entry of this.completedEntries) {
					const url = this.downloadUrlFor(entry.anonymizedFilePath)
					if (!url) {
						failed++
						continue
					}
					try {
						const res = await axios.get(url, { responseType: 'arraybuffer' })
						zip.file(this.uniqueZipName(entry, usedNames), res.data)
					} catch (err) {
						console.error('Download-all: could not fetch', url, err)
						failed++
					}
				}
				if (!usedNames.size) {
					this.zipError = t('docudesk', 'Could not download any of the anonymised files.')
					return
				}
				const blob = await zip.generateAsync({ type: 'blob' })
				this.triggerBlobDownload(blob, `${this.dossierName || 'dossier'}-anonymised.zip`)
				if (failed > 0) {
					this.zipError = t('docudesk', '{failed} file(s) could not be added to the download.', { failed })
				}
			} catch (err) {
				console.error('Download-all: zip generation failed', err)
				this.zipError = t('docudesk', 'Preparing the download failed.')
			} finally {
				this.zipping = false
			}
		},
		/**
		 * Pick a collision-free entry name for the zip. Anonymised results can
		 * share a file name across the dossier, which would otherwise overwrite
		 * each other inside the archive.
		 *
		 * @param {object} entry      Completed queue entry.
		 * @param {Set<string>} used  Names already taken in this archive.
		 * @return {string} A unique name for the zip entry.
		 */
		uniqueZipName(entry, used) {
			const base = entry.anonymizedFileName || `file-${entry.anonymizedFileId || entry.fileId}`
			let name = base
			let i = 1
			while (used.has(name)) {
				const dot = base.lastIndexOf('.')
				name = dot > 0
					? `${base.slice(0, dot)} (${i})${base.slice(dot)}`
					: `${base} (${i})`
				i++
			}
			used.add(name)
			return name
		},
		/**
		 * Save a Blob to disk via a transient object-URL anchor.
		 *
		 * @param {Blob} blob     The data to save.
		 * @param {string} name   Suggested file name.
		 */
		triggerBlobDownload(blob, name) {
			const url = URL.createObjectURL(blob)
			const link = document.createElement('a')
			link.href = url
			link.download = name
			document.body.appendChild(link)
			link.click()
			document.body.removeChild(link)
			URL.revokeObjectURL(url)
		},
		/**
		 * Open a file in the in-app viewer.
		 *
		 * When the file has already been anonymised (a completed queue entry
		 * with a result), attach the anonymised counterpart and switch the
		 * viewer to it, so opening a finished dossier file lands directly on
		 * the review + download of the result instead of the original (T14).
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
			const entry = anonymizationStore.findByFileId(file.fileId)
			if (entry?.status === 'completed' && entry.anonymizedFileId && entry.anonymizedFilePath) {
				fileViewerStore.setAnonymizedVariant({
					fileId: entry.anonymizedFileId,
					fileName: entry.anonymizedFileName || file.fileName,
					mimeType: file.mimeType,
					path: entry.anonymizedFilePath,
				})
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

.dossier-batch-summary--error {
	color: var(--color-error);
}

.dd-file-status--done {
	color: var(--color-success);
}

.dd-file-status--error {
	color: var(--color-error);
}
</style>
