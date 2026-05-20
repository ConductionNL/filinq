<script setup>
import { translate as t } from '@nextcloud/l10n'
import { anonymizationStore } from '../../store/store.js'
</script>

<template>
	<div class="anonymization-widget">
		<h2 class="page-title">
			{{ t('docudesk', 'Good afternoon {name},', { name: userName }) }}<br>
			{{ t('docudesk', 'what would you like to anonymize today?') }}
		</h2>
		<p class="page-description">
			{{ t('docudesk', 'Upload documents. Each file is uploaded and scanned for entities. Review the detected entities, optionally assign Woo Art. 5 grondslagen or mark entities to skip, then run anonymisation. (Smoke-test surface — not a production publication-prep page.)') }}
		</p>

		<!-- Results table -->
		<div v-if="anonymizationStore.hasFiles" class="results-area">
			<table class="results-table">
				<thead>
					<tr>
						<th>{{ t('docudesk', 'File') }}</th>
						<th>{{ t('docudesk', 'Dossier') }}</th>
						<th class="col-number">
							{{ t('docudesk', 'Entities') }}
						</th>
						<th class="col-number">
							{{ t('docudesk', 'Removed') }}
						</th>
						<th class="col-action">
							{{ t('docudesk', 'Result') }}
						</th>
					</tr>
				</thead>
				<tbody>
					<tr v-for="file in anonymizationStore.files" :key="file.id">
						<td class="col-file" :title="file.name">
							{{ file.name }}
						</td>
						<td class="col-dossier">
							<span v-if="file.dossier" class="dossier-tag">
								<FolderOutline :size="14" />
								{{ file.dossier }}
							</span>
							<span v-else class="status-label">&mdash;</span>
						</td>
						<td class="col-number">
							<template v-if="file.status === 'completed' || file.status === 'anonymizing' || file.status === 'extracted'">
								{{ file.entityCount }}
							</template>
							<template v-else-if="file.status === 'error'">
								&mdash;
							</template>
							<NcLoadingIcon v-else :size="16" />
						</td>
						<td class="col-number">
							<template v-if="file.status === 'completed'">
								{{ file.replacementCount }}
							</template>
							<template v-else-if="file.status === 'error'">
								&mdash;
							</template>
							<NcLoadingIcon v-else-if="file.status === 'anonymizing'" :size="16" />
							<template v-else>
								&mdash;
							</template>
						</td>
						<td class="col-action">
							<a
								v-if="file.status === 'completed' && file.anonymizedFilePath"
								:href="downloadUrl(file.anonymizedFilePath)"
								download
								class="download-link">
								{{ t('docudesk', 'Download') }}
							</a>
							<span v-else-if="file.status === 'completed'" class="status-clean">
								{{ t('docudesk', 'Clean') }}
							</span>
							<span v-else-if="file.status === 'error'" class="status-error" :title="file.error">
								{{ t('docudesk', 'Error') }}
							</span>
							<span v-else class="status-label">
								{{ statusLabel(file.status) }}
							</span>
						</td>
					</tr>
				</tbody>
			</table>

			<div v-if="anonymizationStore.hasCompleted" class="table-actions">
				<NcButton type="tertiary" @click="anonymizationStore.clearCompleted()">
					{{ t('docudesk', 'Clear completed') }}
				</NcButton>
			</div>
		</div>

		<!-- Per-file entity review. Every file the store left in `extracted`
		     gets its own review card so the operator can adjust grondslagen
		     and skip flags before triggering anonymisation. The store's
		     uploadAndExtract stops at `extracted` by design; without this
		     section the file would be stuck with a spinner forever. -->
		<div
			v-for="file in extractedFiles"
			:key="'review-' + file.id"
			class="review-card">
			<h3 class="review-title">
				{{ t('docudesk', 'Review entities for {name}', { name: file.name }) }}
			</h3>
			<EntityReviewTable
				:entities="file.entities"
				:file-count="1"
				@toggle="anonymizationStore.toggleEntity(file, $event)"
				@bulk-select="anonymizationStore.setVisibleEntities(file, $event, true)"
				@bulk-deselect="anonymizationStore.setVisibleEntities(file, $event, false)"
				@bases-change="anonymizationStore.setEntityBases(file, $event.idx, $event.bases)"
				@skip-change="anonymizationStore.setEntitySkip(file, $event.idx, $event.skip)" />
			<div class="review-actions">
				<NcButton
					type="primary"
					:disabled="includedCount(file) === 0"
					@click="anonymizationStore.anonymiseEntry(file)">
					{{ t('docudesk', 'Anonymize %n entity', 'Anonymize %n entities', includedCount(file)) }}
				</NcButton>
			</div>
		</div>

		<!-- Drop zone -->
		<div class="upload-area" :class="{ compact: anonymizationStore.hasFiles }">
			<div
				class="drop-zone"
				:class="{ dragging: isDragging }"
				@dragover.prevent="isDragging = true"
				@dragleave.prevent="isDragging = false"
				@drop.prevent="handleDrop"
				@click="$refs.fileInput.click()">
				<img :src="uploadIcon" alt="" class="upload-icon">
				<div class="drop-content">
					<p class="drop-title">
						{{ t('docudesk', 'Drag and drop one or more documents') }}
					</p>
					<p class="drop-subtitle">
						{{ t('docudesk', 'Only PDF, Word, TXT or EML files are supported. Maximum file size 500 MB.') }}
					</p>
					<span class="fake-button">
						{{ t('docudesk', '+ Select files') }}
					</span>
				</div>
				<input
					ref="fileInput"
					type="file"
					multiple
					class="file-input"
					@change="handleFileSelect">
			</div>
		</div>

		<!-- Dossier name dialog -->
		<NcDialog
			v-if="showDossierDialog"
			:name="t('docudesk', 'Create dossier')"
			:can-close="!dossierSubmitting"
			size="normal"
			@closing="cancelDossier">
			<div class="dossier-dialog">
				<p class="dossier-dialog__intro">
					{{ t('docudesk', 'You selected {count} files. Give this dossier a name to group them in one folder.', { count: pendingFiles.length }) }}
				</p>
				<NcTextField
					ref="dossierInput"
					:value.sync="dossierName"
					:label="t('docudesk', 'Folder name')"
					:disabled="dossierSubmitting"
					:error="!!dossierError"
					:helper-text="dossierError"
					@keyup.enter="confirmDossier" />
			</div>
			<template #actions>
				<NcButton type="tertiary" :disabled="dossierSubmitting" @click="cancelDossier">
					{{ t('docudesk', 'Cancel') }}
				</NcButton>
				<NcButton type="primary" :disabled="dossierSubmitting || !dossierName.trim()" @click="confirmDossier">
					<template v-if="dossierSubmitting" #icon>
						<NcLoadingIcon :size="18" />
					</template>
					{{ t('docudesk', 'Create and upload') }}
				</NcButton>
			</template>
		</NcDialog>
	</div>
</template>

<script>
import { NcButton, NcDialog, NcLoadingIcon, NcTextField } from '@nextcloud/vue'
import { generateRemoteUrl } from '@nextcloud/router'
import { getCurrentUser } from '@nextcloud/auth'
import FolderOutline from 'vue-material-design-icons/FolderOutline.vue'
import EntityReviewTable from './EntityReviewTable.vue'
import uploadIcon from '../../assets/upload.png'

// Woo Art. 5 grondslagen are owned by `EntityReviewTable` (it ships its own
// BASES_OPTIONS for the per-row dropdown). The widget used to declare its
// own copy too — removed since nothing on this surface consumed it, and
// it left a no-unused-vars lint error behind.

export default {
	name: 'AnonymizationWidget',
	components: {
		NcButton,
		NcDialog,
		NcLoadingIcon,
		NcTextField,
		FolderOutline,
		EntityReviewTable,
	},
	data() {
		return {
			isDragging: false,
			showDossierDialog: false,
			pendingFiles: [],
			dossierName: '',
			dossierSubmitting: false,
			dossierError: '',
			uploadIcon,
		}
	},
	computed: {
		/**
		 * Display name of the currently logged-in Nextcloud user.
		 *
		 * @return {string} The user's display name, or their uid as fallback, or empty string when unauthenticated.
		 */
		userName() {
			const user = getCurrentUser()
			return user?.displayName || user?.uid || ''
		},
		/**
		 * Files that have finished extraction and are awaiting per-entity
		 * review before anonymisation. Drives the review cards below the
		 * results table.
		 *
		 * @return {object[]} Queue entries in the `extracted` state.
		 */
		extractedFiles() {
			return anonymizationStore.files.filter((f) => f.status === 'extracted')
		},
	},
	methods: {
		/**
		 * Drop handler for the drag-and-drop zone.
		 * Delegates to dispatchFiles so the dossier-dialog logic applies.
		 *
		 * @param {DragEvent} event Native drop event from the drop zone.
		 * @return {void}
		 */
		handleDrop(event) {
			this.isDragging = false
			const files = event.dataTransfer?.files
			if (files && files.length > 0) {
				this.dispatchFiles(files)
			}
		},
		/**
		 * Change handler for the hidden file input.
		 * Resets the input value so the same file(s) can be picked again.
		 *
		 * @param {Event} event Native change event from the file input.
		 * @return {void}
		 */
		handleFileSelect(event) {
			const files = event.target?.files
			if (files && files.length > 0) {
				this.dispatchFiles(files)
			}
			event.target.value = ''
		},
		/**
		 * Route the incoming files to the right flow:
		 *   - 2+ files → open the dossier dialog (user picks a folder name).
		 *   - single file → straight into the queue under /DocuDesk/.
		 *
		 * @param {File[] | FileList} fileList Files from drop or input.
		 */
		dispatchFiles(fileList) {
			const files = Array.from(fileList)
			if (files.length >= 2) {
				this.openDossierDialog(files)
			} else {
				anonymizationStore.addFiles(files)
			}
		},
		/**
		 * Open the dossier-name dialog, pre-fill a timestamp-based name,
		 * and focus the input on next tick.
		 *
		 * @param {File[]} files Files that will be placed into the dossier.
		 */
		openDossierDialog(files) {
			this.pendingFiles = files
			this.dossierName = this.defaultDossierName()
			this.dossierError = ''
			this.showDossierDialog = true
			this.$nextTick(() => {
				this.$refs.dossierInput?.focus?.()
			})
		},
		/**
		 * Confirm handler for the dossier dialog: creates the folder,
		 * moves the files in, and starts the pipeline. Keeps the dialog
		 * open on failure so the user sees the error inline.
		 */
		async confirmDossier() {
			const name = this.dossierName.trim()
			if (!name) return
			this.dossierSubmitting = true
			this.dossierError = ''
			try {
				await anonymizationStore.addFilesAsDossier(this.pendingFiles, name)
				this.closeDossierDialog()
			} catch (err) {
				this.dossierError = err?.response?.data?.error || err?.message || 'Failed to create dossier'
			} finally {
				this.dossierSubmitting = false
			}
		},
		/**
		 * Cancel handler — ignored while a submit is in flight to avoid
		 * leaving the store in a half-created state.
		 */
		cancelDossier() {
			if (this.dossierSubmitting) return
			this.closeDossierDialog()
		},
		/** Reset all dialog state back to its initial values. */
		closeDossierDialog() {
			this.showDossierDialog = false
			this.pendingFiles = []
			this.dossierName = ''
			this.dossierError = ''
		},
		/**
		 * Suggest a default dossier name based on the current date+time,
		 * so the user can immediately confirm without typing.
		 *
		 * @return {string} e.g. "Dossier-2026-04-23-1045"
		 */
		defaultDossierName() {
			const d = new Date()
			const yyyy = d.getFullYear()
			const mm = String(d.getMonth() + 1).padStart(2, '0')
			const dd = String(d.getDate()).padStart(2, '0')
			const hh = String(d.getHours()).padStart(2, '0')
			const mi = String(d.getMinutes()).padStart(2, '0')
			return `Dossier-${yyyy}-${mm}-${dd}-${hh}${mi}`
		},
		/**
		 * Turn a Nextcloud file path ("/admin/files/DocuDesk/...") into
		 * a WebDAV download URL. Falls back to the WebDAV root when the
		 * path cannot be parsed.
		 *
		 * @param {string} filePath Path as returned by the upload endpoint.
		 * @return {string} Download URL.
		 */
		downloadUrl(filePath) {
			const parts = filePath.split('/')
			const filesIndex = parts.indexOf('files')
			if (filesIndex >= 0) {
				const relativePath = parts.slice(filesIndex + 1).join('/')
				return generateRemoteUrl('webdav') + '/' + relativePath
			}
			return generateRemoteUrl('webdav')
		},
		/**
		 * Human-readable label for in-progress queue statuses.
		 *
		 * @param {string} status Raw entry.status value.
		 * @return {string} Translated label.
		 */
		statusLabel(status) {
			const labels = {
				queued: t('docudesk', 'Queued'),
				uploading: t('docudesk', 'Uploading...'),
				moving: t('docudesk', 'Moving...'),
				extracting: t('docudesk', 'Detecting...'),
				extracted: t('docudesk', 'Review needed'),
				anonymizing: t('docudesk', 'Anonymizing...'),
			}
			return labels[status] || status
		},
		/**
		 * Number of entities currently included for anonymisation on a
		 * file. Drives the per-file "Anonymize N entities" button label
		 * and disabled state in the review card.
		 *
		 * @param {object} file Queue entry.
		 * @return {number} Count of entities with `included !== false`.
		 */
		includedCount(file) {
			return (file?.entities || []).filter((e) => e.included !== false).length
		},
	},
}
</script>

<style scoped>
.anonymization-widget {
	display: flex;
	flex-direction: column;
	padding: 20px;
	max-width: 900px;
	margin-inline: auto;
	width: 100%;
}

.page-title {
	margin: 0 0 16px 0;
}

.results-area {
	margin-bottom: 16px;
}

.results-table {
	width: 100%;
	border-collapse: collapse;
	font-size: 0.9rem;
}

.results-table th {
	text-align: left;
	font-weight: 600;
	padding: 8px 10px;
	border-bottom: 1px solid var(--color-border);
	color: var(--color-text-maxcontrast);
	font-size: 0.85rem;
	white-space: nowrap;
}

.results-table td {
	padding: 8px 10px;
	border-bottom: 1px solid var(--color-border-dark, var(--color-border));
	vertical-align: middle;
}

.col-file {
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
	max-width: 280px;
}

.col-dossier {
	white-space: nowrap;
}

.dossier-tag {
	display: inline-flex;
	align-items: center;
	gap: 4px;
	padding: 2px 8px;
	border-radius: 12px;
	background-color: var(--color-background-dark);
	color: var(--color-text-maxcontrast);
	font-size: 0.8rem;
}

.col-number {
	text-align: center;
	width: 80px;
}

.col-action {
	text-align: right;
	width: 120px;
	white-space: nowrap;
}

.download-link {
	color: var(--color-primary);
	text-decoration: none;
	font-weight: 500;
}

.download-link:hover {
	text-decoration: underline;
}

.status-clean {
	color: var(--color-text-maxcontrast);
	font-style: italic;
}

.status-error {
	color: var(--color-error);
	cursor: help;
}

.status-label {
	color: var(--color-text-maxcontrast);
}

.table-actions {
	display: flex;
	justify-content: flex-end;
	margin-top: 8px;
}

.upload-area {
	padding: 4px 0;
}

.drop-zone {
	width: 100%;
	display: flex;
	align-items: center;
	gap: 24px;
	border: 2px dashed var(--color-border);
	border-radius: var(--border-radius-large);
	padding: 32px;
	background-color: #fff;
	cursor: pointer;
	transition: border-color 0.2s, background-color 0.2s;
}

.drop-zone:hover,
.drop-zone.dragging {
	border-color: var(--color-primary);
	background-color: #fff;
}

.upload-icon {
	width: 107px;
	height: 103px;
	flex-shrink: 0;
	object-fit: contain;
}

.drop-content {
	flex: 1;
	min-width: 0;
	display: flex;
	flex-direction: column;
	align-items: flex-start;
	gap: 8px;
	text-align: left;
}

.drop-title {
	margin: 0;
	font-size: 1rem;
	font-weight: 600;
	color: var(--color-main-text);
}

.drop-subtitle {
	margin: 0;
	font-size: 0.85rem;
	color: var(--color-text-maxcontrast);
}

.fake-button {
	margin-top: 4px;
	padding: 8px 16px;
	border-radius: var(--border-radius);
	background-color: var(--color-primary-element);
	color: var(--color-primary-element-text);
	font-size: 0.9rem;
	font-weight: 500;
	white-space: nowrap;
}

.file-input {
	display: none;
}

.dossier-dialog {
	padding: 8px 4px;
}

.dossier-dialog__intro {
	margin: 0 0 16px 0;
	color: var(--color-text-maxcontrast);
}

.review-card {
	margin: 24px 0;
	padding: 16px;
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius-large);
	background-color: var(--color-main-background);
}

.review-title {
	margin: 0 0 12px 0;
	font-size: 1rem;
}

.review-actions {
	display: flex;
	justify-content: flex-end;
	margin-top: 12px;
}
</style>
