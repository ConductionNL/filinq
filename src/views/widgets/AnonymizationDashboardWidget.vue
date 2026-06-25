<script setup>
import { translate as t } from '@nextcloud/l10n'
import { anonymizationStore } from '../../store/store.js'
</script>

<template>
	<div class="docudesk-anon-widget">
		<!-- Results table: shown when files are in the queue -->
		<div v-if="anonymizationStore.hasFiles" class="results-area">
			<table class="results-table">
				<thead>
					<tr>
						<th>{{ t('docudesk', 'File') }}</th>
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
						<td class="col-file">
							<a
								v-if="file.filePath"
								:href="fileLink(file.filePath)"
								target="_blank"
								class="file-link"
								:title="file.name">
								{{ file.name }}
							</a>
							<span v-else :title="file.name">{{ file.name }}</span>
						</td>
						<td class="col-number">
							<template v-if="file.status === 'completed' || file.status === 'anonymizing'">
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
							<span v-else-if="file.status === 'completed' && !file.anonymizedFilePath" class="status-clean">
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
				<img v-if="!anonymizationStore.hasFiles"
					:src="uploadIcon"
					alt=""
					class="upload-icon">
				<div class="drop-content">
					<p class="drop-title">
						{{ anonymizationStore.hasFiles
							? t('docudesk', 'Drop more files to anonymize')
							: t('docudesk', 'Drag and drop one or more documents')
						}}
					</p>
					<p v-if="!anonymizationStore.hasFiles" class="drop-subtitle">
						{{ t('docudesk', 'Only Word (.docx), PDF or TXT files are supported. Maximum file size 500 MB.') }}
					</p>
					<span class="fake-button">
						{{ anonymizationStore.hasFiles ? t('docudesk', '+ Add more files') : t('docudesk', '+ Select files') }}
					</span>
				</div>
				<input
					ref="fileInput"
					type="file"
					multiple
					accept=".docx,.txt,.pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document,text/plain,application/pdf"
					class="file-input"
					@change="handleFileSelect">
			</div>
		</div>

		<!-- Footer: shown in the Nextcloud dashboard widget context, hidden in-app -->
		<a v-if="!inApp" class="widget-footer" :href="appUrl">
			{{ t('docudesk', 'Open DocuDesk') }}
		</a>

		<!-- Dossier name dialog (multi-file upload) -->
		<NcDialog
			v-if="showDossierDialog"
			:name="t('docudesk', 'Create dossier')"
			:can-close="!dossierSubmitting"
			size="normal"
			@closing="cancelDossier">
			<div class="dossier-dialog">
				<NcTextField
					ref="dossierInput"
					:value.sync="dossierName"
					:label="t('docudesk', 'Dossier name')"
					:placeholder="t('docudesk', 'e.g. Buurtinitiatieven 2026')"
					:disabled="dossierSubmitting"
					:error="!!dossierError"
					:helper-text="dossierError"
					@keyup.enter="confirmDossier" />
				<NcNoteCard type="info">
					{{ t('docudesk', 'You uploaded multiple documents. Enter a title to automatically create a dossier from them. No title? Then they will stay as separate documents.') }}
				</NcNoteCard>
			</div>
			<template #actions>
				<NcButton type="tertiary" :disabled="dossierSubmitting" @click="cancelDossier">
					{{ t('docudesk', 'Cancel') }}
				</NcButton>
				<NcButton type="primary" :disabled="dossierSubmitting" @click="confirmDossier">
					<template v-if="dossierSubmitting" #icon>
						<NcLoadingIcon :size="18" />
					</template>
					{{ t('docudesk', 'Continue to anonymization') }}
				</NcButton>
			</template>
		</NcDialog>
	</div>
</template>

<script>
import { NcButton, NcDialog, NcLoadingIcon, NcNoteCard, NcTextField } from '@nextcloud/vue'
import { generateUrl, generateRemoteUrl } from '@nextcloud/router'
import { showError } from '@nextcloud/dialogs'
import uploadIcon from '../../assets/upload.png'

const ALLOWED_EXTENSIONS = ['docx', 'txt', 'pdf']
const ALLOWED_MIMES = new Set([
	'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
	'text/plain',
	'application/pdf',
])

function partitionFiles(files) {
	const accepted = []
	const rejected = []
	for (const file of Array.from(files)) {
		const ext = (file.name.split('.').pop() || '').toLowerCase()
		if (ALLOWED_MIMES.has(file.type) || ALLOWED_EXTENSIONS.includes(ext)) {
			accepted.push(file)
		} else {
			rejected.push(file)
		}
	}
	return { accepted, rejected }
}

export default {
	name: 'AnonymizationDashboardWidget',
	components: {
		NcButton,
		NcDialog,
		NcLoadingIcon,
		NcNoteCard,
		NcTextField,
	},
	props: {
		title: {
			type: String,
			default: '',
		},
		/**
		 * Set to true when embedded in the in-app dashboard to hide the
		 * "Open DocuDesk" footer link (redundant when already in the app).
		 */
		inApp: {
			type: Boolean,
			default: false,
		},
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
		 * @spec openspec/specs/dashboard/spec.md#requirement-nextcloud-dashboard-widgets-req-dash-02
		 */
		appUrl() {
			return generateUrl('/apps/docudesk')
		},
	},
	methods: {
		/**
		 * @param event
		 * @spec openspec/specs/dashboard/spec.md#requirement-nextcloud-dashboard-widgets-req-dash-02
		 */
		handleDrop(event) {
			this.isDragging = false
			const files = event.dataTransfer?.files
			if (files && files.length > 0) {
				const filtered = this.filterAllowed(files)
				if (filtered.length > 0) {
					this.dispatchFiles(filtered)
				}
			}
		},
		/**
		 * @param event
		 * @spec openspec/specs/dashboard/spec.md#requirement-nextcloud-dashboard-widgets-req-dash-02
		 */
		handleFileSelect(event) {
			const files = event.target?.files
			if (files && files.length > 0) {
				const filtered = this.filterAllowed(files)
				if (filtered.length > 0) {
					this.dispatchFiles(filtered)
				}
			}
			event.target.value = ''
		},
		filterAllowed(files) {
			const { accepted, rejected } = partitionFiles(files)
			if (rejected.length > 0) {
				const names = rejected.map((f) => f.name).join(', ')
				showError(t('docudesk', 'Only Word (.docx), PDF and TXT files are supported. Skipped: {names}', { names }))
			}
			return accepted
		},
		async dispatchFiles(fileList) {
			const files = Array.from(fileList)
			if (files.length >= 2) {
				this.openDossierDialog(files)
				return
			}
			const mimeType = files[0]?.type || ''
			const before = anonymizationStore.files.length
			await anonymizationStore.addFiles(files)
			const entry = anonymizationStore.files[before]
			if (entry && entry.fileId) {
				this.gotoViewer(entry, mimeType)
			}
		},
		/**
		 * Navigate to the file-viewer when inside the DocuDesk app.
		 * Safe to call from the NC dashboard context — $router is absent there
		 * and the optional chaining prevents any error.
		 * @param entry
		 * @param mimeType
		 */
		gotoViewer(entry, mimeType) {
			if (!this.$router) return
			if (this.$route?.name !== 'MyDocuments') {
				this.$router.push({ name: 'MyDocuments' }).catch(() => {})
			}
		},
		openDossierDialog(files) {
			this.pendingFiles = files
			this.dossierName = ''
			this.dossierError = ''
			this.showDossierDialog = true
			this.$nextTick(() => {
				this.$refs.dossierInput?.focus?.()
			})
		},
		async confirmDossier() {
			const name = this.dossierName.trim()
			this.dossierSubmitting = true
			this.dossierError = ''
			try {
				const before = anonymizationStore.files.length
				if (name) {
					await anonymizationStore.addFilesAsDossier(this.pendingFiles, name)
					try {
						await anonymizationStore.bindDossier(name)
					} catch (err) {
						console.error('Failed to bind dossier to OpenRegister:', err)
					}
				} else {
					await anonymizationStore.addFiles(this.pendingFiles)
				}
				const firstEntry = anonymizationStore.files[before]
				if (firstEntry && firstEntry.fileId) {
					this.gotoViewer(firstEntry, this.pendingFiles[0]?.type || '')
				}
				this.closeDossierDialog()
			} catch (err) {
				this.dossierError = err?.response?.data?.error || err?.message || 'Failed to upload'
			} finally {
				this.dossierSubmitting = false
			}
		},
		cancelDossier() {
			if (this.dossierSubmitting) return
			this.closeDossierDialog()
		},
		closeDossierDialog() {
			this.showDossierDialog = false
			this.pendingFiles = []
			this.dossierName = ''
			this.dossierError = ''
		},
		/**
		 * @param filePath
		 * @spec openspec/specs/dashboard/spec.md#requirement-nextcloud-dashboard-widgets-req-dash-02
		 */
		fileLink(filePath) {
			const parts = filePath.split('/')
			const filesIndex = parts.indexOf('files')
			if (filesIndex >= 0) {
				const relativePath = '/' + parts.slice(filesIndex + 1).join('/')
				const dir = relativePath.substring(0, relativePath.lastIndexOf('/')) || '/'
				const file = relativePath.substring(relativePath.lastIndexOf('/') + 1)
				return generateUrl('/apps/files/?dir={dir}&scrollto={file}', { dir, file })
			}
			return generateUrl('/apps/files')
		},
		/**
		 * @param filePath
		 * @spec openspec/specs/dashboard/spec.md#requirement-nextcloud-dashboard-widgets-req-dash-02
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
		 * @param status
		 * @spec openspec/specs/dashboard/spec.md#requirement-nextcloud-dashboard-widgets-req-dash-02
		 */
		statusLabel(status) {
			const labels = {
				queued: t('docudesk', 'Queued'),
				uploading: t('docudesk', 'Uploading...'),
				extracting: t('docudesk', 'Detecting...'),
				anonymizing: t('docudesk', 'Anonymizing...'),
			}
			return labels[status] || status
		},
	},
}
</script>

<style scoped>
.docudesk-anon-widget {
	display: flex;
	flex-direction: column;
	padding: 20px;
	height: 100%;
}

.anon-widget__title {
	margin: 0 0 16px 0;
	font-size: 1.2rem;
}

/* Results table */
.results-area {
	flex-shrink: 0;
	overflow-y: auto;
	max-height: 200px;
	margin-bottom: 8px;
}

.results-table {
	width: 100%;
	border-collapse: collapse;
	font-size: 0.85rem;
}

.results-table th {
	text-align: left;
	font-weight: 600;
	padding: 4px 6px;
	border-bottom: 1px solid var(--color-border);
	color: var(--color-text-maxcontrast);
	font-size: 0.8rem;
	white-space: nowrap;
}

.results-table td {
	padding: 4px 6px;
	border-bottom: 1px solid var(--color-border);
	vertical-align: middle;
}

.col-file {
	max-width: 140px;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}

.col-number {
	text-align: center;
	width: 60px;
}

.col-action {
	text-align: right;
	width: 80px;
	white-space: nowrap;
}

.file-link {
	color: var(--color-primary);
	text-decoration: none;
}

.file-link:hover {
	text-decoration: underline;
}

.download-link {
	color: var(--color-success);
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

/* Drop zone */
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
	background-color: var(--color-main-background);
	cursor: pointer;
	transition: border-color 0.2s, background-color 0.2s;
}

.upload-area.compact .drop-zone {
	padding: 12px 16px;
}

.drop-zone:hover,
.drop-zone.dragging {
	border-color: var(--color-primary);
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

/* Footer (NC dashboard context only) */
.widget-footer {
	display: block;
	text-align: center;
	padding: 8px;
	margin-top: auto;
	border-top: 1px solid var(--color-border);
	color: var(--color-text-maxcontrast);
	font-size: 0.85rem;
	text-decoration: none;
}

.widget-footer:hover {
	background-color: var(--color-background-hover);
	color: var(--color-main-text);
}

/* Dossier dialog */
.dossier-dialog {
	display: flex;
	flex-direction: column;
	gap: 16px;
	padding: 20px;
}

.dossier-dialog :deep(.notecard) {
	margin: 0;
}
</style>
