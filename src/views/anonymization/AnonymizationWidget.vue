<script setup>
import { translate as t } from '@nextcloud/l10n'
import { anonymizationStore, fileViewerStore, myDocumentsStore } from '../../store/store.js'
</script>

<template>
	<div class="anonymization-widget">
		<h2 class="page-title">
			{{ greeting }}<br>
			{{ t('docudesk', 'what would you like to anonymize today?') }}
		</h2>
		<!-- Drop zone -->
		<div class="upload-area">
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
						{{ t('docudesk', 'Only Word (.docx) or TXT files are supported. Maximum file size 500 MB.') }}
					</p>
					<span class="fake-button">
						{{ t('docudesk', '+ Select files') }}
					</span>
				</div>
				<input
					ref="fileInput"
					type="file"
					multiple
					accept=".docx,.txt,application/vnd.openxmlformats-officedocument.wordprocessingml.document,text/plain"
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
import { getCurrentUser } from '@nextcloud/auth'
import { showError } from '@nextcloud/dialogs'
import uploadIcon from '../../assets/upload.png'

// Anonymisation only produces real redactions for formats the backend can
// edit in place: Word via PHPWord, plain text via byte-level replace. PDF
// (and other binary formats) fall through to the str_ireplace path that
// returns a byte-identical copy — see project-anonymization-pipeline for
// the upstream OR limitation. Restrict the upload widget so users can't
// accidentally pick a format that won't actually redact.
const ALLOWED_EXTENSIONS = ['docx', 'txt']
const ALLOWED_MIMES = new Set([
	'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
	'text/plain',
])

/**
 * Split a FileList into accepted (docx/txt) and rejected files.
 *
 * Matches on both MIME and filename extension because drag-and-drop sometimes
 * omits MIME (e.g. for .docx on certain browsers) and the input's `accept`
 * attribute is advisory only.
 *
 * @param {FileList | File[]} files Incoming files.
 * @return {{ accepted: File[], rejected: File[] }}
 */
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

// Widget only handles upload + the dossier dialog. After upload the user is
// routed to the file viewer (/my-documents host), where `FileViewerSidebar`
// renders detected entities, grondslagen pickers and the anonymise button.

export default {
	name: 'AnonymizationWidget',
	components: {
		NcButton,
		NcDialog,
		NcLoadingIcon,
		NcTextField,
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
		 * Time-of-day greeting interpolated with the user's display name.
		 *
		 * Morning: 05:00–11:59. Afternoon: 12:00–17:59. Evening: 18:00–04:59.
		 *
		 * @return {string} Localised greeting like 'Good morning Marco,'.
		 */
		greeting() {
			const hour = new Date().getHours()
			if (hour >= 5 && hour < 12) {
				return t('docudesk', 'Good morning {name},', { name: this.userName })
			}
			if (hour >= 12 && hour < 18) {
				return t('docudesk', 'Good afternoon {name},', { name: this.userName })
			}
			return t('docudesk', 'Good evening {name},', { name: this.userName })
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
				const filtered = this.filterAllowed(files)
				if (filtered.length > 0) {
					this.dispatchFiles(filtered)
				}
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
				const filtered = this.filterAllowed(files)
				if (filtered.length > 0) {
					this.dispatchFiles(filtered)
				}
			}
			event.target.value = ''
		},
		/**
		 * Drop files whose extension/MIME isn't in the supported set.
		 * Surfaces a toast naming each rejected file so the user knows why
		 * nothing happened for that file.
		 *
		 * @param {FileList | File[]} files Incoming files from drop or input.
		 * @return {File[]} Accepted subset.
		 */
		filterAllowed(files) {
			const { accepted, rejected } = partitionFiles(files)
			if (rejected.length > 0) {
				const names = rejected.map((f) => f.name).join(', ')
				showError(t('docudesk', 'Only Word (.docx) and TXT files are supported. Skipped: {names}', { names }))
			}
			return accepted
		},
		/**
		 * Route the incoming files to the right flow:
		 *   - 2+ files → open the dossier dialog (user picks a folder name).
		 *   - single file → straight into the queue under /DocuDesk/, then
		 *     open the file in the in-app viewer and route to /my-documents
		 *     so `FileViewerPage` mounts and the sidebar lists entities.
		 *
		 * @param {File[] | FileList} fileList Files from drop or input.
		 * @return {Promise<void>}
		 */
		async dispatchFiles(fileList) {
			const files = Array.from(fileList)
			if (files.length >= 2) {
				this.openDossierDialog(files)
				return
			}

			// Capture MIME type before addFiles consumes the File blob,
			// and the queue length so we can find the new entry afterwards.
			const mimeType = files[0]?.type || ''
			const before = anonymizationStore.files.length
			await anonymizationStore.addFiles(files)
			const entry = anonymizationStore.files[before]
			if (entry && entry.fileId) {
				fileViewerStore.open({
					fileId: entry.fileId,
					fileName: entry.name,
					mimeType,
					path: entry.filePath,
				})
				this.gotoViewer()
			}
		},
		/**
		 * Route to the file-viewer host page when not already there.
		 * Hash-mode router throws NavigationDuplicated if we push the same
		 * route — swallowed so the upload flow stays clean.
		 *
		 * @return {void}
		 */
		gotoViewer() {
			if (this.$route?.name === 'MyDocuments') {
				return
			}
			this.$router.push({ name: 'MyDocuments' }).catch(() => { /* duplicate nav */ })
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
				// Capture metadata before addFilesAsDossier consumes the
				// File blobs — used to seed the file viewer afterwards.
				const firstMime = this.pendingFiles[0]?.type || ''
				const before = anonymizationStore.files.length

				await anonymizationStore.addFilesAsDossier(this.pendingFiles, name)

				// Bind the WebDAV folder to an OpenRegister dossier object
				// (PROPFIND + POST). Best-effort: files are uploaded fine
				// regardless of OR binding, so we surface the error in
				// the dialog but don't roll the upload back.
				try {
					await anonymizationStore.bindDossier(name)
				} catch (err) {
					console.error('Failed to bind dossier to OpenRegister:', err)
				}

				// Switch the left-hand navigation to the new dossier folder
				// so `FolderFilesNavigation` lists every file we just put
				// inside it.
				try {
					await myDocumentsStore.fetchDocuments(`/DocuDesk/${name}`)
				} catch (err) {
					console.error('Failed to open dossier folder:', err)
				}

				// Open the first uploaded file in the viewer.
				const firstEntry = anonymizationStore.files[before]
				if (firstEntry && firstEntry.fileId) {
					fileViewerStore.open({
						fileId: firstEntry.fileId,
						fileName: firstEntry.name,
						mimeType: firstMime,
						path: firstEntry.filePath,
					})
					this.gotoViewer()
				}

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

</style>
